<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use App\Models\AlatUnit;
use App\Models\Peminjaman;
use App\Models\DetailPinjam;
use App\Models\User;
use App\Notifications\PeminjamanDiajukanNotification;
use App\Models\PermintaanEditPeminjaman;
use App\Models\DetailPermintaanEdit;
use App\Notifications\PermintaanEditDiajukanNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class PeminjamController extends Controller
{
    /**
     * Menampilkan katalog alat yang masih tersedia.
     *
     * Satu alat bisa punya banyak unit fisik dengan serial number
     * sendiri. Alat masuk katalog selama masih ada minimal satu unit
     * berkondisi 'tersedia'; jumlah unit tersedia dipakai view untuk
     * memilih serial mana yang dipinjam.
     */
    public function katalogAlat()
    {
        $alats = Alat::with('kategori')
            ->with(['alatUnit' => function ($q) {
                $q->tersedia()->orderBy('serial_number');
            }])
            ->withCount(['alatUnit as jumlah_unit_tersedia' => function ($q) {
                $q->where('kondisi', 'tersedia');
            }])
            ->tidakTerarsip()
            ->whereHas('alatUnit', function ($q) {
                $q->where('kondisi', 'tersedia');
            })
            ->orderBy('nama_alat')
            ->get();

        return view('peminjam.katalog', compact('alats'));
    }


    /**
     * Kembalikan unit serial tersedia milik sebuah alat sebagai JSON.
     *
     * Dipakai tombol "+N unit lainnya" di kartu katalog: kartu hanya
     * menampilkan 4 serial pertama, sisanya dimuat lewat endpoint ini
     * supaya katalog tidak memanjang ke bawah saat alat punya banyak
     * unit.
     *
     * Hanya unit tersedia: unit dipinjam/rusak tidak boleh dipilih
     * untuk peminjaman baru. Alat terarsip (is_arsip) di-skip juga.
     */
    public function unitAlatJson(Request $request, $id)
    {
        $alat = Alat::tidakTerarsip()->findOrFail($id);

        // Berapa unit yang sudah dirender server-side di kartu katalog.
        // Katalog memakai take(4); endpoint ini harus melewati serial yang
        // sama, kalau tidak tombol "+N unit lainnya" menampilkan ulang
        // AH-001 yang sudah ada di kartu.
        $sudahDitampilkan = min(
            max((int) $request->query('skip', 4), 0),
            100
        );

        // skip() tanpa limit menghasilkan "OFFSET tanpa LIMIT" -- MySQL
        // menolaknya (syntax error). Ambil 1000 baris lalu skip di PHP:
        // aman untuk jumlah unit sekolah dan portable antar driver.
        $units = $alat->alatUnit()
            ->tersedia()
            ->orderBy('serial_number')
            ->take(1000)
            ->skip($sudahDitampilkan)
            ->get(['id', 'serial_number'])
            ->map(fn ($u) => [
                'id' => $u->id,
                'serial_number' => $u->serial_number,
            ]);

        return response()->json([
            'nama_alat' => $alat->nama_alat,
            'skip' => $sudahDitampilkan,
            'units' => $units,
        ]);
    }

    /**
     * Memproses pengajuan peminjaman dari peminjam.
     *
     * Peminjam memilih serial number unit, bukan jumlah. Satu baris
     * detail_pinjam = satu unit, jadi untuk meminjam 3 kamera harus
     * dikirim 3 alat_unit_id berbeda.
     */
    public function ajukanPeminjaman(Request $request)
    {
        $request->validate([
            'tgl_kembali_plan' => [
                'required',
                'date',
                'after:today',
            ],

            'alat_id' => [
                'nullable',
                'array',
            ],

            'alat_id.*' => [
                'required_with:alat_id',
                'integer',
                'exists:alat,id',
            ],

            'alat_unit_id' => [
                'required',
                'array',
                'min:1',
            ],

            'alat_unit_id.*' => [
                'required',
                'integer',
                'distinct',
                'exists:alat_unit,id',
            ],
        ], [
            'tgl_kembali_plan.required' => 'Tanggal rencana kembali wajib diisi.',
            'tgl_kembali_plan.after' => 'Tanggal rencana kembali harus setelah hari ini.',

            'alat_id.*.exists' => 'Alat yang dipilih tidak ditemukan.',

            'alat_unit_id.required' => 'Silakan pilih minimal satu unit alat.',
            'alat_unit_id.min' => 'Silakan pilih minimal satu unit alat.',
            'alat_unit_id.*.required' => 'Unit alat wajib dipilih.',
            'alat_unit_id.*.exists' => 'Unit alat yang dipilih tidak ditemukan.',
            'alat_unit_id.*.distinct' => 'Unit yang sama tidak boleh dipilih lebih dari satu kali.',
        ]);

        // alat_id opsional: kalau dikirim, tiap unit harus milik salah
        // satu alat di daftar ini (guard anti manipulasi serial asing).
        $alatIds = $request->filled('alat_id')
            ? array_map('intval', (array) $request->alat_id)
            : null;

        DB::beginTransaction();

        try {

            // Buat data utama peminjaman
            $peminjaman = Peminjaman::create([
                'user_id' => auth()->id(),
                'tgl_pinjam' => now(),
                'tgl_kembali_plan' => $request->tgl_kembali_plan,
                'status' => 'diajukan',
            ]);

            // Simpan setiap unit yang dipilih (1 unit = 1 baris)
            foreach ($request->alat_unit_id as $unitId) {

                // Kunci unit selama transaksi supaya tidak diambil
                // pengajuan lain di antara cek dan simpan.
                $unit = AlatUnit::with('alat')
                    ->lockForUpdate()
                    ->findOrFail($unitId);

                // Unit harus benar-benar siap dipinjam
                if (!$unit->isTersedia()) {
                    throw new \Exception(
                        "Serial {$unit->serial_number} ({$unit->alat->nama_alat}) tidak tersedia, mungkin sedang dipinjam pihak lain."
                    );
                }

                // Kalau request membawa daftar alat, serial harus
                // milik salah satu alat tersebut.
                if ($alatIds !== null && !in_array($unit->alat_id, $alatIds, true)) {
                    throw new \Exception(
                        "Serial {$unit->serial_number} bukan milik alat yang dipilih."
                    );
                }

                // Simpan detail peminjaman (1 unit per baris)
                DetailPinjam::create([
                    'peminjaman_id' => $peminjaman->id,
                    'alat_unit_id' => $unit->id,
                ]);
            }

            DB::commit();

            // Kirim notifikasi ke semua Petugas
            User::where('role', 'petugas')
                ->get()
                ->each(function ($petugas) use ($peminjaman) {
                    $petugas->notify(
                        new PeminjamanDiajukanNotification($peminjaman)
                    );
                });

            return redirect()
                ->route('peminjam.riwayat')
                ->with(
                    'success',
                    'Pengajuan peminjaman berhasil dikirim dan menunggu persetujuan petugas.'
                );

        } catch (\Exception $e) {

            DB::rollBack();

            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'error',
                    'Gagal mengajukan peminjaman: ' . $e->getMessage()
                );
        }
    }


    /**
     * Menampilkan riwayat peminjaman
     * milik user yang sedang login.
     */
    public function riwayatPeminjaman()
    {
        $peminjamans = Peminjaman::with([
            'detailPinjams.alatUnit.alat',
        ])
        ->where('user_id', auth()->id())
        ->latest('created_at')
        ->get();

        return view(
            'peminjam.riwayat',
            compact('peminjamans')
        );
    }

    /**
     * Form pengajuan edit peminjaman (tambah/hapus unit, ubah tanggal).
     *
     * Alat yang ditampilkan di bagian "tambah unit" adalah alat yang
     * masih punya unit 'tersedia', lengkap dengan daftar serialnya.
     * Unit yang sudah dipinjam di peminjaman ini bisa diakses lewat
     * $peminjaman->detailPinjams (eager loaded di bawah).
     */
    public function formEditPeminjaman($id)
    {
        $peminjaman = Peminjaman::with('detailPinjams.alatUnit.alat')
            ->where('user_id', auth()->id())
            ->whereIn('status', ['dipinjam', 'telat'])
            ->findOrFail($id);

        $alats = Alat::with('kategori')
            ->with(['alatUnit' => function ($q) {
                $q->tersedia()->orderBy('serial_number');
            }])
            ->withCount(['alatUnit as jumlah_unit_tersedia' => function ($q) {
                $q->where('kondisi', 'tersedia');
            }])
            ->tidakTerarsip()
            ->whereHas('alatUnit', function ($q) {
                $q->where('kondisi', 'tersedia');
            })
            ->orderBy('nama_alat')
            ->get();

        return view('peminjam.edit-peminjaman', compact('peminjaman', 'alats'));
    }

    /**
     * Simpan pengajuan edit peminjaman (status menunggu, menunggu persetujuan petugas).
     *
     * Unit dipilih per serial: aksi 'tambah' = minta tambahkan unit
     * tersebut, 'hapus' = lepaskan unit tersebut. Pengecekan atomic
     * ketersediaan unit baru dilakukan saat petugas menyetujui
     * (PetugasController::setujuiEditPeminjaman).
     */
    public function ajukanEditPeminjaman(Request $request, $id)
    {
        $peminjaman = Peminjaman::with('detailPinjams')
            ->where('user_id', auth()->id())
            ->whereIn('status', ['dipinjam', 'telat'])
            ->findOrFail($id);

        // Cek apakah sudah ada permintaan edit yang menunggu untuk peminjaman ini
        $existingRequest = PermintaanEditPeminjaman::where('peminjaman_id', $peminjaman->id)
            ->where('status', 'menunggu')
            ->first();

        if ($existingRequest) {
            return redirect()
                ->route('peminjam.riwayat')
                ->with('error', 'Sudah ada permintaan edit yang menunggu untuk peminjaman ini.');
        }

        $validated = $request->validate([
            'tgl_kembali_plan_baru' => 'required|date',
            'alasan' => 'nullable|string|max:500',
            'alat_unit_id' => 'nullable|array',
            'alat_unit_id.*' => 'required_with:alat_unit_id|integer|distinct|exists:alat_unit,id',
            'aksi' => 'nullable|array',
            'aksi.*' => 'required_with:aksi|in:tambah,hapus',
        ]);

        DB::beginTransaction();

        try {
            $permintaanEdit = PermintaanEditPeminjaman::create([
                'peminjaman_id' => $peminjaman->id,
                'user_id' => auth()->id(),
                'tgl_kembali_plan_baru' => $validated['tgl_kembali_plan_baru'],
                'alasan' => $validated['alasan'] ?? null,
                'status' => 'menunggu',
            ]);

            // 1 unit = 1 baris permintaan edit
            if (!empty($validated['alat_unit_id'])) {
                foreach ($validated['alat_unit_id'] as $i => $unitId) {
                    DetailPermintaanEdit::create([
                        'permintaan_edit_id' => $permintaanEdit->id,
                        'alat_unit_id' => $unitId,
                        'aksi' => $validated['aksi'][$i],
                    ]);
                }
            }

            DB::commit();

            User::where('role', 'petugas')
                ->get()
                ->each(function ($petugas) use ($permintaanEdit) {
                    $petugas->notify(new PermintaanEditDiajukanNotification($permintaanEdit));
                });

            return redirect()
                ->route('peminjam.riwayat')
                ->with('success', 'Pengajuan edit peminjaman berhasil dikirim, menunggu persetujuan petugas.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Gagal mengajukan edit peminjaman: ' . $e->getMessage());
        }
    }
}
