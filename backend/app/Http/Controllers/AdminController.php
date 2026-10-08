<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use App\Models\AlatUnit;
use App\Models\Kategori;
use App\Models\LogAktivitas;
use App\Models\User;
use App\Models\Peminjaman;
use App\Models\Pengembalian;
use App\Models\DetailPinjam;
use App\Notifications\PengembalianDisetujuiNotification;
use App\Notifications\PengembalianDitolakNotification;
use App\Notifications\PengembalianSelesaiNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    // Logika CRUD unit serial (tambah / tandai rusak / perbaiki).
    // Dipisah ke trait karena AdminController sudah lewat 2000 baris.
    use KelolaUnitAlat;

    // =========================================================
    // DASHBOARD
    // =========================================================

    /**
     * Menampilkan dashboard Admin dan log aktivitas.
     */
    public function index()
{
    $jumlahAlat = Alat::count();
    $jumlahUser = User::count();
    $jumlahKategori = Kategori::count();
    $jumlahPeminjaman = Peminjaman::count();
    $jumlahPengembalian = Pengembalian::count();

    $logs = LogAktivitas::with('user')
        ->latest()
        ->take(10)
        ->get();

    return view('admin.dashboard', compact(
        'jumlahAlat',
        'jumlahUser',
        'jumlahKategori',
        'jumlahPeminjaman',
        'jumlahPengembalian',
        'logs'
    ));
}


    // =========================================================
    // CRUD ALAT
    // =========================================================

    /**
     * Menampilkan daftar alat.
     *
     * Tidak ada lagi stok agregat: jumlah per kondisi dihitung dari
     * alat_unit lewat withCount bertarget di query ini.
     */
    public function indexAlat(Request $request)
{
    $search = $request->input('search');
    $kategoriFilter = $request->input('kategori');
    $kondisi = $request->input('kondisi');

    // Ambil semua kategori untuk dropdown filter
    $kategori = Kategori::orderBy('nama_kategori')->get();

    $alats = Alat::with('kategori')

        // =========================
        // HITUNG UNIT PER KONDISI
        // =========================
        // Satu query, tanpa N+1. Key dipakai langsung di view
        // sebagai $alat->jumlah_tersedia dll.
        ->withCount([
            'alatUnit as jumlah_tersedia' => fn ($q) => $q->where('kondisi', 'tersedia'),
            'alatUnit as jumlah_dipinjam' => fn ($q) => $q->where('kondisi', 'dipinjam'),
            'alatUnit as jumlah_rusak' => fn ($q) => $q->where('kondisi', 'rusak'),
        ])

        // =========================
        // SEARCH
        // =========================
        ->when($search !== null && $search !== '', function ($query) use ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_alat', 'like', "%{$search}%")
                    ->orWhereHas('kategori', function ($kategoriQuery) use ($search) {
                        $kategoriQuery->where(
                            'nama_kategori',
                            'like',
                            "%{$search}%"
                        );
                    });
            });
        })

        // =========================
        // FILTER KATEGORI
        // =========================
        ->when($kategoriFilter !== null && $kategoriFilter !== '', function ($query) use ($kategoriFilter) {
            $query->where('kategori_id', $kategoriFilter);
        })

        // =========================
        // FILTER KONDISI
        // =========================
        // Label filter lama (Baik/Rusak/Rusak Parah) dipetakan ke
        // kondisi unit: Tersedia = siap dipinjam, Rusak = rusak.
        ->when($kondisi !== null && $kondisi !== '', function ($query) use ($kondisi) {
            $kondisiUnit = [
                'Baik' => 'tersedia',
                'Rusak' => 'rusak',
                'Rusak Parah' => 'rusak',
            ][$kondisi] ?? null;

            if ($kondisiUnit !== null) {
                $query->whereHas('alatUnit', fn ($q) => $q->where('kondisi', $kondisiUnit));
            }
        })

        ->latest()
        ->paginate(10)
        ->withQueryString();

    return view(
        'admin.alat.index',
        compact(
            'alats',
            'search',
            'kategori',
            'kategoriFilter',
            'kondisi'
        )
    );
}


    /**
     * Menampilkan form tambah alat.
     */
    public function createAlat()
    {
        $kategoris = Kategori::all();

        return view(
            'admin.alat.create',
            compact('kategoris')
        );
    }


    /**
     * Menyimpan alat baru.
     *
     * Input `jumlah_unit` menentukan berapa baris alat_unit dibuat;
     * tiap unit dapat serial_number dari AlatUnit::serialBerikutnya().
     */
    public function storeAlat(Request $request)
    {
        $request->validate([
            'nama_alat' => 'required|string|max:255',
            'kategori_id' => 'required|exists:kategori,id',
            'kode_alat' => 'required|string|max:10',
            'jumlah_unit' => 'required|integer|min:1|max:1000',
            'deskripsi' => 'nullable|string',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $data = $request->only([
            'nama_alat',
            'kategori_id',
            'kode_alat',
            'deskripsi',
        ]);

        DB::beginTransaction();

        try {
            // Upload gambar
            if ($request->hasFile('gambar')) {
                $file = $request->file('gambar');

                $filename = time() . '_' . $file->getClientOriginalName();

                $file->move(
                    public_path('storage/alat'),
                    $filename
                );

                $data['gambar'] = 'storage/alat/' . $filename;
            }

            // Simpan alat dulu, baru generasi unit serialnya.
            $alat = Alat::create($data);

            $jumlahUnit = (int) $request->jumlah_unit;
            $now = now();

            /*
            | Serial dihitung sekali untuk seluruh batch, bukan per unit di
            | dalam loop: serialBerikutnya() membaca DB, dan DB belum berubah
            | selama loop berjalan -- semua unit akan dapat nomor sama lalu
            | kena constraint UNIQUE.
            |
            | serialBerikutnyaBatch() juga sudah melewati nomor yang dipakai
            | alat lain dengan kode_alat sama, karena serial_number UNIQUE
            | global (mis. "Future Furniture" dan "ffg" -> dua-duanya FF).
            |
            | Insert massal, bukan create per unit: ribuan unit tetap satu
            | query. Konsekuensinya event model AlatUnit tidak ikut
            | terpicu --(created observer hanya menulis log_aktivitas, tidak
            | ada efek samping bisnis, jadi aman).
            */
            $rows = collect(AlatUnit::serialBerikutnyaBatch($alat->kode_alat, $jumlahUnit))
                ->map(fn (string $serial) => [
                    'alat_id' => $alat->id,
                    'serial_number' => $serial,
                    'kondisi' => 'tersedia',
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
                ->all();

            // Insert massal, bukan create per unit: serial tidak perlu
            // lewat model event dan 1000 insert tetap satu query.
            AlatUnit::insert($rows);

            DB::commit();

            return redirect()
                ->route('admin.alat.index')
                ->with(
                    'success',
                    "Data alat berhasil ditambahkan ({$jumlahUnit} unit)."
                );

        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Gagal menambahkan alat: ' . $e->getMessage()
                );
        }
    }


    /**
     * Menampilkan form edit alat.
     *
     * Unit dimuat urut serial_number untuk ditampilkan di halaman edit.
     */
    public function editAlat($id)
    {
        $alat = Alat::with(['alatUnit' => fn ($q) => $q->orderBy('serial_number')])
            ->findOrFail($id);
        $kategoris = Kategori::all();

        return view(
            'admin.alat.edit',
            compact('alat', 'kategoris')
        );
    }


    /**
     * Memperbarui data alat.
     *
     * Hanya field alat. Unit dikelola terpisah, tidak dibuat/dihapus di
     * sini.
     */
    public function updateAlat(Request $request, $id)
{
    $alat = Alat::findOrFail($id);

    $request->validate([
        'nama_alat' => 'required|string|max:255',
        'kategori_id' => 'required|exists:kategori,id',
        'deskripsi' => 'nullable|string',
        'gambar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
    ]);

    $data = [
        'nama_alat' => $request->nama_alat,
        'kategori_id' => $request->kategori_id,
        'deskripsi' => $request->deskripsi,
    ];

    // Upload gambar baru
    if ($request->hasFile('gambar')) {

        // Hapus gambar lama
        if (
            $alat->gambar &&
            file_exists(public_path($alat->gambar))
        ) {
            unlink(public_path($alat->gambar));
        }

        $file = $request->file('gambar');

        $filename =
            time() . '_' . $file->getClientOriginalName();

        $file->move(
            public_path('storage/alat'),
            $filename
        );

        $data['gambar'] = 'storage/alat/' . $filename;
    }

    $alat->update($data);

    return redirect()
        ->route('admin.alat.index')
        ->with(
            'success',
            'Data alat berhasil diperbarui.'
        );
}

/**
 * Memperbaiki unit alat yang rusak menjadi kondisi tersedia.
 *
 * Input berupa daftar unit_id, bukan jumlah. Tiap unit dipilih
 * individual dari daftar serial di halaman alat.
 */
public function perbaikiAlat(Request $request, $id)
{
    $alat = Alat::findOrFail($id);

    $request->validate([
        'unit_id' => 'required|array|min:1',
        'unit_id.*' => 'required|integer|exists:alat_unit,id',
    ]);

    DB::beginTransaction();

    try {
        // Pastikan setiap unit milik alat ini DAN sedang rusak.
        // Update per instance lewat model supaya observer event jalan.
        $unitDiperbaiki = [];

        foreach ($request->unit_id as $unitId) {
            $unit = AlatUnit::where('alat_id', $alat->id)
                ->where('kondisi', 'rusak')
                ->lockForUpdate()
                ->find($unitId);

            if ($unit === null) {
                throw new \Exception(
                    "Unit #{$unitId} bukan milik alat ini atau tidak dalam kondisi rusak."
                );
            }

            $unit->kondisi = 'tersedia';
            $unit->save();

            $unitDiperbaiki[] = $unit->serial_number;
        }

        DB::commit();

        $jumlah = count($unitDiperbaiki);
        $daftarSerial = implode(', ', $unitDiperbaiki);

        // Catat aktivitas Admin
        LogAktivitas::create([
            'user_id' => auth()->id(),
            'aktivitas' =>
                "Memperbaiki {$jumlah} unit alat '{$alat->nama_alat}' " .
                "menjadi kondisi tersedia ({$daftarSerial}).",
        ]);

        return redirect()
            ->route('admin.alat.index')
            ->with(
                'success',
                "{$jumlah} unit {$alat->nama_alat} berhasil diperbaiki ({$daftarSerial})."
            );

    } catch (\Exception $e) {

        DB::rollBack();

        return redirect()
            ->route('admin.alat.index')
            ->with(
                'error',
                'Perbaikan alat gagal: ' . $e->getMessage()
            );
    }
}

    /**
     * Mengubah kondisi unit alat.
     *
     * Input berupa daftar unit_id dan kondisi_tujuan (tersedia/rusak).
     * Setiap unit harus milik alat ini; kondisi diubah per instance.
     */
    public function ubahKondisiAlat(Request $request, $id)
    {
        $alat = Alat::findOrFail($id);

        $request->validate([
            'unit_id' => 'required|array|min:1',
            'unit_id.*' => 'required|integer|exists:alat_unit,id',
            'kondisi_tujuan' => 'required|in:tersedia,rusak',
        ]);

        $kondisiTujuan = $request->kondisi_tujuan;

        DB::beginTransaction();

        try {
            $unitDiubah = [];

            foreach ($request->unit_id as $unitId) {
                $unit = AlatUnit::where('alat_id', $alat->id)
                    ->lockForUpdate()
                    ->find($unitId);

                if ($unit === null) {
                    throw new \Exception(
                        "Unit #{$unitId} bukan milik alat ini."
                    );
                }

                // Update per instance, bukan update() massal:
                // observer/log per unit tetap jalan.
                $unit->kondisi = $kondisiTujuan;
                $unit->save();

                $unitDiubah[] = $unit->serial_number;
            }

            DB::commit();

            $jumlah = count($unitDiubah);
            $daftarSerial = implode(', ', $unitDiubah);

            LogAktivitas::create([
                'user_id' => auth()->id(),
                'aktivitas' =>
                    "Mengubah {$jumlah} unit alat '{$alat->nama_alat}' menjadi kondisi {$kondisiTujuan} ({$daftarSerial}).",
            ]);

            return redirect()
                ->route('admin.alat.index')
                ->with(
                    'success',
                    "{$jumlah} unit {$alat->nama_alat} berhasil diubah menjadi kondisi {$kondisiTujuan} ({$daftarSerial})."
                );

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with(
                'error',
                'Gagal mengubah kondisi alat: ' . $e->getMessage()
            );
        }
    }

    /**
     * Menghapus alat.
     *
     * Alat yang sudah punya unit (alat_unit) atau pernah dipakai
     * peminjaman (detail_pinjam) tidak bisa dihapus permanen --
     * identitas fisik + riwayatnya akan hilang. Alat "digunakan"
     * di-arsip, bukan dihapus.
     */
    public function destroyAlat($id)
    {
        $alat = Alat::withCount('alatUnit')->findOrFail($id);

        /*
        | Alat yang PERNAH DIPINJAM tidak bisa dihapus permanen: riwayat
        | peminjaman dan pengembaliannya akan kehilangan identitas barang,
        | dan laporan lama jadi tidak bisa ditelusuri. Alat itu diarsip
        | (is_arsip) -- hilang dari katalog tapi datanya utuh.
        |
        | Alat yang belum pernah dipinjam TIDAK perlu diarsip, walau
        | sudah punya unit. Unit adalah stok inventaris, bukan riwayat;
        | membuat alat baru selalu menghasilkan unit otomatis, jadi
        | syarat "punya unit" membuat setiap alat baru tidak bisa
        | dihapus -- tidak ada bedanya dengan alat yang benar-benar
        | terpakai. Hapus saja; unit ikut terhapus lewat FK CASCADE.
        */
        $pernahDipinjam = $alat->detailPinjam()->exists();

        if ($pernahDipinjam) {
            $alat->update(['is_arsip' => true]);

            LogAktivitas::create([
                'user_id' => auth()->id(),
                'aktivitas' => "Mengarsipkan alat '{$alat->nama_alat}' karena memiliki riwayat peminjaman.",
            ]);

            return redirect()
                ->route('admin.alat.index')
                ->with(
                    'success',
                    "Alat '{$alat->nama_alat}' diarsipkan karena pernah dipinjam. Data riwayat tetap disimpan untuk laporan."
                );
        }

        // Baru dipakai? Ambil nama dulu, baris + gambar setelahnya.
        DB::beginTransaction();

        try {
            $nama = $alat->nama_alat;
            $gambar = $alat->gambar;

            $alat->delete();

            // Hapus gambar fisik HANYA setelah baris DB benar-benar hilang.
            // Kalau delete gagal karena FK, gambar tidak ikut lenyap.
            if (
                $gambar &&
                file_exists(public_path($gambar))
            ) {
                unlink(public_path($gambar));
            }

            DB::commit();

            return redirect()
                ->route('admin.alat.index')
                ->with(
                    'success',
                    "Data alat '{$nama}' berhasil dihapus."
                );

        } catch (\Exception $e) {

            DB::rollBack();

            return back()->with(
                'error',
                'Gagal menghapus alat: ' . $e->getMessage()
            );
        }
    }


    /**
     * Membuka kembali alat yang sudah diarsipkan.
     */
    public function restoreAlat($id)
    {
        $alat = Alat::findOrFail($id);

        $alat->update(['is_arsip' => false]);

        return redirect()
            ->route('admin.alat.index')
            ->with(
                'success',
                "Alat '{$alat->nama_alat}' dikembalikan ke katalog."
            );
    }


    // =========================================================
    // PENGEMBALIAN
    // =========================================================

    /**
     * Menampilkan daftar pengembalian.
     *
     * Admin melihat pengembalian status_request=menunggu yang harus
     * diapprove. Yang sudah ditolak/disetujui sudah selesai dikelola.
     */
    public function indexPengembalian(Request $request)
    {
        $search = $request->input('search');
        $kondisi = $request->input('kondisi');
        $statusRequest = $request->input('status_request');
        $tanggalDari = $request->input('tanggal_dari');
        $tanggalSampai = $request->input('tanggal_sampai');
        $jenisKelamin = $request->input('jenis_kelamin');

        $pengembalians = Pengembalian::with([
            'peminjaman.user',
            'peminjaman.detailPinjams.alatUnit.alat',
            'petugas'
        ])
            ->when($search, function ($query, $search) {
                $query->whereHas(
                    'peminjaman.user',
                    function ($q) use ($search) {
                        $q->where(
                            'name',
                            'like',
                            "%{$search}%"
                        );
                    }
                );
            })

            ->when($kondisi, function ($query, $kondisi) {
    $query->where('kondisi_kembali', $kondisi);
})

->when($statusRequest, function ($query, $statusRequest) {
    $query->where('status_request', $statusRequest);
})

->when($tanggalDari, function ($query, $tanggalDari) {
    $query->whereDate('tgl_kembali', '>=', $tanggalDari);
})
->when($tanggalSampai, function ($query, $tanggalSampai) {
    $query->whereDate('tgl_kembali', '<=', $tanggalSampai);
})

->when($jenisKelamin, function ($query, $jenisKelamin) {
    $query->whereHas('peminjaman.user', function ($user) use ($jenisKelamin) {
        $user->where('jenis_kelamin', $jenisKelamin);
    });
})

            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view(
            'admin.pengembalian.index',
            compact('pengembalians', 'search', 'kondisi', 'statusRequest', 'tanggalDari', 'tanggalSampai', 'jenisKelamin')
        );
    }


    /**
     * Method lama untuk halaman create pengembalian.
     *
     * Tidak digunakan dalam alur baru.
     */
    public function createPengembalian()
    {
        return redirect()
            ->route('admin.pengembalian.index')
            ->with(
                'error',
                'Pengembalian sekarang diajukan oleh Petugas dan harus disetujui Admin.'
            );
    }

    /**
     * Method lama untuk menyimpan pengembalian langsung.
     *
     * Tidak digunakan agar Admin tidak dapat melewati
     * proses pengajuan Petugas.
     */
    public function storePengembalian(Request $request)
    {
        return redirect()
            ->route('admin.pengembalian.index')
            ->with(
                'error',
                'Pengembalian harus diajukan oleh Petugas terlebih dahulu.'
            );
    }

    /**
     * Menampilkan detail pengajuan pengembalian
     * untuk diperiksa Admin.
     */
    public function editPengembalian($id)
    {
        $pengembalian = Pengembalian::with([
            'peminjaman.user',
            'peminjaman.detailPinjams.alatUnit.alat',
            'petugas'
        ])->findOrFail($id);

        // Hanya pengajuan yang masih menunggu
        // yang dapat diperiksa.
        if ($pengembalian->status_request !== 'menunggu') {
            return redirect()
                ->route('admin.pengembalian.index')
                ->with(
                    'error',
                    'Pengajuan pengembalian ini sudah diproses.'
                );
        }

        return view(
            'admin.pengembalian.edit',
            compact('pengembalian')
        );
    }


    /**
     * Menyetujui pengajuan pengembalian.
     *
     * Petugas menentukan:
     * - kondisi alat
     * - denda kerusakan
     *
     * Admin menentukan validasi akhir.
     *
     * Denda keterlambatan dihitung otomatis
     * sebesar Rp1.000 per hari.
     */
    public function setujuiPengembalian($id)
    {
        /*
        | Titik final alur pengembalian. Di sini:
        | 1. denda keterlambatan dihitung (butuh tanggal kembali aktual),
        | 2. status_request -> disetujui,
        | 3. stok alat dikembalikan sesuai kondisi (baik/rusak/rusak parah),
        | 4. status peminjaman -> dikembalikan.
        | Semua dalam transaksi: gagal salah satu -> semua rollback.
        */
        DB::beginTransaction();

        try {
           $pengembalian = Pengembalian::with([
            'petugas',
            'peminjaman.user',
            'peminjaman.detailPinjams.alatUnit.alat'
        ])->findOrFail($id);

            if ($pengembalian->status_request !== 'menunggu') {
                throw new \Exception(
                    'Pengajuan pengembalian ini sudah diproses.'
                );
            }

            $peminjaman = $pengembalian->peminjaman;

            // Pengajuan dari peminjam belum punya kondisi sampai Admin
            // memeriksa. Tanpa ini, NULL dibaca sebagai 'rusak' dan
            // semua unit tersembunyi dari katalog padahal barang baik.
            if (empty($pengembalian->kondisi_kembali)) {
                throw new \Exception(
                    'Kondisi barang belum diperiksa. Isi hasil pemeriksaan sebelum menyetujui.'
                );
            }

            if (!in_array($peminjaman->status, ['dipinjam', 'telat'])) {
                throw new \Exception(
                    'Peminjaman ini tidak dapat diproses sebagai pengembalian.'
                );
            }

            $tglRencana = Carbon::parse($peminjaman->tgl_kembali_plan);
            $tglKembali = Carbon::parse($pengembalian->tgl_kembali);

            if ($tglKembali->greaterThan(Carbon::today())) {
                throw new \Exception(
                    'Tanggal pengembalian tidak boleh melebihi hari ini.'
                );
            }

            $hariTerlambat = 0;

            if ($tglKembali->greaterThan($tglRencana)) {
                $hariTerlambat = $tglRencana->diffInDays($tglKembali);
            }

            // Tarif denda ada di config/denda.php, bukan hardcode.
            // Ubah tarif di config, semua perhitungan ikut.
                $dendaKeterlambatan =
                $hariTerlambat * config('denda.keterlambatan_per_hari');

            // Denda kerusakan sudah ditentukan oleh Petugas.
            $dendaKerusakan = (int) ($pengembalian->denda_kerusakan ?? 0);

            // Simpan masing-masing denda ke kolomnya sendiri.
            $pengembalian->update([
                'denda' => $dendaKeterlambatan,
                'status_request' => 'disetujui',
            ]);

            /*
            | Unit kembali setelah Admin menyetujui pengembalian.
            |
            | Sebelum serial number, ini menaikkan kolom stok agregat per
            | tingkat kerusakan. Sekarang tiap unit punya kondisinya sendiri,
            | jadi yang di-update hanya alat_unit.kondisi:
            | - kondisi_kembali 'Baik' -> unit 'tersedia' (katalog tampilkan)
            | - kondisi_kembali 'Rusak' -> unit 'rusak' (tersembunyi dari
            |   katalog sampai admin menandai perbaikan di menu Kelola Unit)
            |
            | Kondisi kembali hanya dua nilai: 'Baik' dan 'Rusak'. Nilai
            | tingkat yang dulu ada sudah dinormalisasi jadi 'Rusak' oleh
            | migrasi 2026_09_30_144000, jadi tidak perlu menangani
            | nilai lama di sini.
            |
            | Update per instance (bukan mass update) supaya
            | AlatUnitObserver mencatat jejak audit tiap transisi.
            */
            $kondisiUnit = strcasecmp($pengembalian->kondisi_kembali, 'Baik') === 0
                ? 'tersedia'
                : 'rusak';

            foreach ($peminjaman->detailPinjams as $detail) {
                $unit = $detail->alatUnit;

                if (!$unit) {
                    continue;
                }

                $unit->update(['kondisi' => $kondisiUnit]);
            }

            $peminjaman->update([
                'status' => 'dikembalikan',
            ]);

            DB::commit();

            // petugas_id NULL = admin menangani sendiri, tidak ada
            // petugas yang perlu dinotifikasi -> dilewati.
if ($pengembalian->petugas) {
    $pengembalian->petugas->notify(
        new PengembalianDisetujuiNotification($pengembalian)
    );
}

// Kirim notifikasi kepada Peminjam
if ($pengembalian->peminjaman->user) {
    $pengembalian->peminjaman->user->notify(
        new PengembalianSelesaiNotification($pengembalian)
    );
}

            return redirect()
                ->route('admin.pengembalian.index')
                ->with(
                    'success',
                    'Pengembalian berhasil disetujui, stok alat dikembalikan, dan total denda telah dihitung.'
                );
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with(
                'error',
                'Pengembalian gagal disetujui: ' . $e->getMessage()
            );
        }
    }

    public function formPengembalianAdmin($id)
{
    $peminjaman = Peminjaman::with([
        'user',
        'detailPinjams.alatUnit.alat',
        'pengembalian'
    ])->findOrFail($id);

    if (!in_array($peminjaman->status, ['dipinjam', 'telat'])) {
        return redirect()
            ->route('admin.peminjaman.index')
            ->with(
                'error',
                'Peminjaman ini tidak dapat dibuatkan pengembalian.'
            );
    }

    if (
        $peminjaman->pengembalian &&
        $peminjaman->pengembalian->status_request === 'menunggu'
    ) {
        return redirect()
            ->route('admin.peminjaman.index')
            ->with(
                'error',
                'Pengajuan pengembalian ini masih menunggu proses.'
            );
    }

    if (
        $peminjaman->pengembalian &&
        $peminjaman->pengembalian->status_request === 'disetujui'
    ) {
        return redirect()
            ->route('admin.peminjaman.index')
            ->with(
                'error',
                'Pengembalian ini sudah disetujui.'
            );
    }

    return view(
        'admin.pengembalian.create-from-peminjaman',
        compact('peminjaman')
    );
}


    /**
 * Admin membuat pengajuan pengembalian secara langsung.
 *
 * Digunakan sebagai jalur alternatif apabila Petugas
 * tidak dapat melakukan proses pengembalian.
 */
public function ajukanPengembalianAdmin(Request $request, $peminjamanId)
{
    $request->validate([
        'kondisi_kembali' => 'required|in:Baik,Rusak',
        'denda_kerusakan' => 'required|integer|min:0',
    ]);

    DB::beginTransaction();

    try {
        $peminjaman = Peminjaman::with('pengembalian')
            ->findOrFail($peminjamanId);

        // Pastikan peminjaman masih aktif
        if (!in_array($peminjaman->status, ['dipinjam', 'telat'])) {
            throw new \Exception(
                'Peminjaman ini tidak dapat diajukan sebagai pengembalian.'
            );
        }

        $pengembalian = $peminjaman->pengembalian;

        // Jika masih menunggu Admin
        if (
            $pengembalian &&
            $pengembalian->status_request === 'menunggu'
        ) {
            throw new \Exception(
                'Pengajuan pengembalian ini masih menunggu proses.'
            );
        }

        // Jika sudah disetujui
        if (
            $pengembalian &&
            $pengembalian->status_request === 'disetujui'
        ) {
            throw new \Exception(
                'Pengembalian untuk peminjaman ini sudah disetujui.'
            );
        }

        /*
         * Jika sebelumnya pernah ditolak,
         * gunakan data pengembalian yang sama.
         */
        if ($pengembalian) {

            $pengembalian->update([
                'tgl_kembali' => now()->toDateString(),
                'kondisi_kembali' => $request->kondisi_kembali,
                'denda' => 0,
                'denda_kerusakan' => $request->denda_kerusakan,
                'status_request' => 'menunggu',
            ]);
} else {

    $pengembalian = Pengembalian::create([
        'peminjaman_id' => $peminjaman->id,
        'tgl_kembali' => now()->toDateString(),
        'kondisi_kembali' => $request->kondisi_kembali,
        'denda' => 0,
        'denda_kerusakan' => $request->denda_kerusakan,
        'petugas_id' => auth()->id(),
        'status_request' => 'menunggu',
    ]);
}

        DB::commit();

        return redirect()
            ->route('admin.pengembalian.index')
            ->with(
                'success',
                'Pengajuan pengembalian berhasil dibuat dan menunggu proses persetujuan.'
            );

    } catch (\Exception $e) {

        DB::rollBack();

        return back()->with(
            'error',
            'Pengajuan pengembalian gagal: ' . $e->getMessage()
        );
    }
}


    /**
     * Menolak pengajuan pengembalian.
     *
     * Data tidak dihapus agar Petugas dapat
     * memperbaiki dan mengajukan kembali.
     *
     * Status peminjaman TIDAK diubah ke dipinjam lagi di sini:
     * peminjaman tetap aktif karena alat belum kembali. Petugas
     * mengajukan ulang dari menu pengembalian petugas (pengembalian
     * ditolak tetap tampil di sana untuk diajukan ulang).
     */
    public function tolakPengembalian($id)
    {
        DB::beginTransaction();

        try {
            $pengembalian = Pengembalian::with('petugas')->findOrFail($id);

            if ($pengembalian->status_request !== 'menunggu') {
                throw new \Exception(
                    'Pengajuan pengembalian ini sudah diproses.'
                );
            }

            $pengembalian->update([
                'status_request' => 'ditolak',
                'denda' => 0,
            ]);

            DB::commit();

// Kirim notifikasi kepada Petugas yang mengajukan
if ($pengembalian->petugas) {
    $pengembalian->petugas->notify(
        new PengembalianDitolakNotification($pengembalian)
    );
}

return redirect()
    ->route('admin.pengembalian.index')
    ->with(
        'success',
        'Pengajuan pengembalian ditolak dan dapat diperbaiki oleh Petugas.'
    );

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with(
                'error',
                'Pengajuan gagal ditolak: ' . $e->getMessage()
            );
        }
    }

    /**
     * Admin mencatat hasil pemeriksaan barang: kondisi dan denda
     * kerusakan.
     *
     * Wajib untuk pengajuan yang datang dari peminjam: peminjam
     * tidak tahu kondisi barang, jadi kondisi_kembali masih NULL
     * sampai diisi di sini. Tanpa langkah ini, setujuiPengembalian
     * akan membaca NULL dan semua unit otomatis ditandai 'rusak'.
     *
     * Denda KETERLAMBATAN tidak diisi di sini -- itu dihitung
     * sistem dari tanggal rencana vs aktual saat persetujuan.
     *
     * Yang TIDAK berubah: kondisi unit dan status peminjaman. Keduanya
     * baru berubah saat Admin menekan Setujui, supaya tidak ada dua
     * sumber kebenaran soal kondisi barang.
     */
    public function updatePengembalian(Request $request, $id)
    {
        $validated = $request->validate([
            'kondisi_kembali' => ['required', 'string', Rule::in(['Baik', 'Rusak'])],
            'denda_kerusakan' => ['required', 'integer', 'min:0'],
        ]);

        $pengembalian = Pengembalian::findOrFail($id);

        if ($pengembalian->status_request !== 'menunggu') {
            return redirect()
                ->route('admin.pengembalian.index')
                ->with('error', 'Pengajuan pengembalian ini sudah diproses.');
        }

        $pengembalian->update([
            'kondisi_kembali' => $validated['kondisi_kembali'],
            'denda_kerusakan' => $validated['denda_kerusakan'],
        ]);

        return redirect()
            ->route('admin.pengembalian.edit', $pengembalian->id)
            ->with(
                'success',
                'Hasil pemeriksaan tersimpan. Periksa kembali lalu setujui pengembalian.'
            );
    }

    /**
     * Menghapus data pengembalian.
     *
     * Jika sudah disetujui, stok akan dikurangi kembali
     * karena sebelumnya sudah ditambahkan saat persetujuan.
     */
    public function destroyPengembalian($id)
    {
        DB::beginTransaction();

        try {
            $pengembalian = Pengembalian::with([
                'peminjaman.detailPinjams.alatUnit'
            ])->findOrFail($id);

            $peminjaman = $pengembalian->peminjaman;

            /*
            | Hanya pengembalian yang sudah disetujui yang pernah
            | mengubah kondisi unit, sehingga hanya itu yang perlu
            | dibatalkan: unit dikembalikan ke 'dipinjam' (barang fisik
            | belum benar-benar masuk gudang lagi).
            |
            | Unit yang tidak ditemukan (mis. sudah dihapus permanen)
            | dilewati, bukan menggagalkan rollback -- data pengembalian
            | harus tetap bisa dihapus.
            */
            if ($pengembalian->status_request === 'disetujui') {
                foreach ($peminjaman->detailPinjams as $detail) {
                    $unit = $detail->alatUnit;

                    if (!$unit) {
                        continue;
                    }

                    $unit->update(['kondisi' => 'dipinjam']);
                }

                $peminjaman->update([
                    'status' => 'dipinjam',
                ]);
            }

            // Jika masih menunggu atau ditolak, stok tidak diubah.
            $pengembalian->delete();

            DB::commit();

            return redirect()
                ->route('admin.pengembalian.index')
                ->with(
                    'success',
                    'Data pengembalian berhasil dihapus.'
                );
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with(
                'error',
                $e->getMessage()
            );
        }
    }

    /**
     * Method lama untuk memproses pengembalian langsung.
     *
     * Tidak digunakan agar pengembalian tidak dapat
     * melewati persetujuan Admin.
     */
    public function prosesPengembalian($id)
    {
        return redirect()
            ->route('admin.pengembalian.index')
            ->with(
                'error',
                'Pengembalian harus melalui pengajuan Petugas dan persetujuan Admin.'
            );
    }

    // =========================================================
    // CRUD USER
    // =========================================================

    /**
     * Menampilkan daftar user.
     *
     * User yang masih punya relasi (peminjaman/log) tidak bisa
     * dihapus: foreign key akan gagal. Pengecekan di destroyUser.
     */
    public function indexUser(Request $request)
{
    $search = $request->input('search');
    $jenisKelamin = $request->input('jenis_kelamin');
    $role = $request->input('role');
    $status = $request->input('status');

    $users = User::when($search, function ($query, $search) {
        $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('role', 'like', "%{$search}%");
        });
    })
        ->when($jenisKelamin, function ($query, $jenisKelamin) {
            $query->where('jenis_kelamin', $jenisKelamin);
        })
        ->when($role, function ($query, $role) {
            $query->where('role', $role);
        })
        ->when($status === 'aktif', fn ($query) => $query->aktif())
        ->when($status === 'nonaktif', fn ($query) => $query->nonaktif())
        ->orderByRaw("CASE
    WHEN role = 'admin' THEN 1
    WHEN role = 'petugas' THEN 2
    WHEN role = 'peminjam' THEN 3
    ELSE 4
END")
->latest()
->paginate(10)
->withQueryString();

    return view(
        'admin.user.index',
        compact('users', 'search', 'jenisKelamin', 'role', 'status')
    );
}


    /**
     * Menampilkan form tambah user.
     */
    public function createUser()
    {
        return view('admin.user.create');
    }


    /**
     * Menyimpan user baru.
     */
    public function storeUser(Request $request)
    {
        $request->validate([
    'name' => 'required|string|max:255',
    'email' => 'required|email|unique:users,email',
    'password' => 'required|string|min:8',
    'role' => 'required|in:admin,petugas,peminjam',
    'no_hp' => 'nullable|string|max:20',
    'jenis_kelamin' => 'nullable|in:Laki-laki,Perempuan',
    'foto_profile' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'no_hp' => $request->no_hp,
            'jenis_kelamin' => $request->jenis_kelamin,
        ];

        // Upload foto profil
        if ($request->hasFile('foto_profile')) {

            $file = $request->file('foto_profile');

            $filename =
                time() . '_' .
                $file->getClientOriginalName();

            $file->move(
                public_path('storage/profile'),
                $filename
            );

            $data['foto_profile'] =
                'storage/profile/' . $filename;
        }

        User::create($data);

        return redirect()
            ->route('admin.user.index')
            ->with(
                'success',
                'User berhasil ditambahkan.'
            );
    }


    /**
     * Menampilkan form edit user.
     */
    public function editUser($id)
    {
        $user = User::findOrFail($id);

        return view(
            'admin.user.edit',
            compact('user')
        );
    }


    /**
     * Memperbarui data user.
     */
    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' =>
                'required|string|email|max:255|unique:users,email,' . $id,
            'role' => 'required|in:admin,petugas,peminjam',
            'no_hp' => 'nullable|string|max:20',
            'jenis_kelamin' => 'nullable|in:Laki-laki,Perempuan',
            'password' => 'nullable|string|min:8',
            'foto_profile' =>
                'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'no_hp' => $request->no_hp,
            'jenis_kelamin' => $request->jenis_kelamin,
        ];

        // Update password jika diisi
        if ($request->filled('password')) {
            $data['password'] =
                Hash::make($request->password);
        }

        // Upload foto baru
        if ($request->hasFile('foto_profile')) {

            if (
                $user->foto_profile &&
                file_exists(
                    public_path($user->foto_profile)
                )
            ) {
                unlink(
                    public_path($user->foto_profile)
                );
            }

            $file = $request->file('foto_profile');

            $filename =
                time() . '_' .
                $file->getClientOriginalName();

            $file->move(
                public_path('storage/profile'),
                $filename
            );

            $data['foto_profile'] =
                'storage/profile/' . $filename;
        }

        $user->update($data);

        return redirect()
            ->route('admin.user.index')
            ->with(
                'success',
                'Data user berhasil diperbarui.'
            );
    }


    /*
    |----------------------------------------------------------------------
    | Nonaktifkan / aktifkan kembali user
    |----------------------------------------------------------------------
    | User yang masih punya peminjaman aktif tidak boleh dihapus
    | (lihat destroyUser), tapi admin harus tetap bisa mencabut aksesnya.
    | is_aktif=false memutus seluruh session dan token milik user tersebut
    | tanpa menghapus satu baris riwayat pun.
    |
    | Log aktivitas dicatat manual: toggling bukan perubahan pada model
    | Peminjaman, jadi tidak ada observer yang akan mencatatnya otomatis.
    */
    public function toggleUserAktif($id)
    {
        $user = User::findOrFail($id);

        // Admin tidak boleh menonaktifkan akun sendiri. Tanpa ini admin
        // bisa mengunci dirinya sendiri di luar sistem tanpa jalan kembali
        // selain reset password langsung di database.
        if ($user->id === auth()->id()) {
            return redirect()
                ->route('admin.user.index')
                ->with('error', 'Anda tidak dapat menonaktifkan akun sendiri.');
        }

        $user->is_aktif = ! $user->is_aktif;
        $user->save();

        $status = $user->is_aktif ? 'diaktifkan kembali' : 'dinonaktifkan';

        LogAktivitas::create([
            'user_id' => auth()->id(),
            'aktivitas' => "User {$user->name} ({$user->email}) {$status}.",
        ]);

        return redirect()
            ->route('admin.user.index')
            ->with('success', "Akun {$user->name} berhasil {$status}.");
    }

    /**
     * Menghapus user.
     */
    public function destroyUser($id)
{
    $user = User::findOrFail($id);

    // Admin tidak boleh menghapus akun sendiri
    if ($user->id === auth()->id()) {
        return redirect()
            ->route('admin.user.index')
            ->with('error', 'Anda tidak dapat menghapus akun sendiri.');
    }

    // User yang masih memiliki peminjaman aktif tidak boleh dihapus
    // karena data peminjaman & stok alat akan ikut terhapus (cascade).
    $peminjamanAktif = Peminjaman::where('user_id', $user->id)
        ->whereIn('status', ['diajukan', 'dipinjam', 'telat'])
        ->exists();

    if ($peminjamanAktif) {
        return redirect()
            ->route('admin.user.index')
            ->with(
                'error',
                'User ini masih memiliki peminjaman aktif. Selesaikan atau hapus data peminjamannya terlebih dahulu.'
            );
    }

    // Hapus foto profil
    if (
        $user->foto_profile &&
        file_exists(
            public_path($user->foto_profile)
        )
    ) {
        unlink(
            public_path($user->foto_profile)
        );
    }

    LogAktivitas::create([
        'user_id' => auth()->id(),
        'aktivitas' => "User {$user->name} ({$user->email}) dihapus permanen.",
    ]);

    $user->delete();

    return redirect()
        ->route('admin.user.index')
        ->with(
            'success',
            'User berhasil dihapus.'
        );
}


    // =========================================================
    // CRUD KATEGORI
    // =========================================================

    /**
     * Menampilkan daftar kategori.
     *
     * Kategori yang masih dipakai alat tidak bisa dihapus.
     * Pengecekan ada di destroyKategori.
     */
    public function indexKategori(Request $request)
{
    $search = $request->input('search');

    $kategoris = Kategori::withCount('alat')
        ->when(
            $search,
            function ($query, $search) {
                $query->where(
                    'nama_kategori',
                    'like',
                    "%{$search}%"
                );
            }
        )
        ->latest()
        ->paginate(5)
        ->withQueryString();

    return view(
        'admin.kategori.index',
        compact('kategoris', 'search')
    );
}

/**show Kategori */
public function showKategori($id)
{
    $kategori = Kategori::with([
        'alat' => fn ($q) => $q->withCount([
            'alatUnit as jumlah_tersedia' => fn ($u) => $u->where('kondisi', 'tersedia'),
            'alatUnit as jumlah_dipinjam' => fn ($u) => $u->where('kondisi', 'dipinjam'),
            'alatUnit as jumlah_rusak' => fn ($u) => $u->where('kondisi', 'rusak'),
        ]),
    ])
        ->withCount('alat')
        ->findOrFail($id);

    // Jumlah unit per kondisi, dijumlahkan dari alat di kategori ini.
    $kategori->jumlah_tersedia = $kategori->alat->sum('jumlah_tersedia');
    $kategori->jumlah_dipinjam = $kategori->alat->sum('jumlah_dipinjam');
    $kategori->jumlah_rusak = $kategori->alat->sum('jumlah_rusak');

    return view(
        'admin.kategori.show',
        compact('kategori')
    );
}


    /**
     * Menampilkan form tambah kategori.
     */
    public function createKategori()
    {
        return view('admin.kategori.create');
    }


    /**
     * Menyimpan kategori baru.
     */
    public function storeKategori(Request $request)
    {
        $request->validate([
            'nama_kategori' =>
                'required|string|max:255|unique:kategori,nama_kategori',
        ]);

        Kategori::create([
            'nama_kategori' => $request->nama_kategori,
        ]);

        return redirect()
            ->route('admin.kategori.index')
            ->with(
                'success',
                'Kategori berhasil ditambahkan.'
            );
    }


    /**
     * Menampilkan form edit kategori.
     */
    public function editKategori($id)
    {
        $kategori = Kategori::findOrFail($id);

        return view(
            'admin.kategori.edit',
            compact('kategori')
        );
    }


    /**
     * Memperbarui kategori.
     */
    public function updateKategori(Request $request, $id)
    {
        $kategori = Kategori::findOrFail($id);

        $request->validate([
            'nama_kategori' =>
                'required|string|max:255|unique:kategori,nama_kategori,' . $id,
        ]);

        $kategori->update([
            'nama_kategori' => $request->nama_kategori,
        ]);

        return redirect()
            ->route('admin.kategori.index')
            ->with(
                'success',
                'Kategori berhasil diperbarui.'
            );
    }


    /**
     * Menghapus kategori.
     *
     * Kategori yang masih memiliki alat tidak boleh dihapus -- alat
     * itu punya riwayat peminjaman yang akan ikut lenyap. Diblokir juga
     * di level DB lewat FK alat.kategori_id RESTRICT.
     */
    public function destroyKategori($id)
    {
        $kategori = Kategori::findOrFail($id);

        if ($kategori->alat()->exists()) {

            $jumlah = $kategori->alat()->count();

            return back()->with(
                'error',
                "Kategori '{$kategori->nama_kategori}' masih memiliki {$jumlah} alat dan tidak dapat dihapus."
            );
        }

        $kategori->delete();

        return redirect()
            ->route('admin.kategori.index')
            ->with(
                'success',
                'Kategori berhasil dihapus.'
            );
    }


    // =========================================================
    // CRUD PEMINJAMAN
    // =========================================================

    /**
     * Menampilkan daftar peminjaman.
     *
     * Pengecekan status telat TIDAK lagi dilakukan di sini.
     * Sebelumnya ada mass update (Peminjaman::where(...)->update())
     * di method ini, yang dilewati observer -> log aktivitas
     * "dipinjam -> telat" tidak pernah tercatat. Sekarang pakai
     * command app:hitung-peminjaman-telat yang dijadwal tiap jam.
     | Lihat app/Console/Commands/HitungPeminjamanTelat.php.
     */
    public function indexPeminjaman(Request $request)
{
    /*
    |--------------------------------------------------------------------------
    | Cek otomatis peminjaman yang terlambat
    |--------------------------------------------------------------------------
    | Dipindahkan ke command `app:hitung-peminjaman-telat` + scheduler harian.
    | Lihat app/Console/Commands/HitungPeminjamanTelat.php.
    |
    | Alasan: sebelumnya update massal langsung di method ini (GET request).
    | Mass update lewat query builder tidak memanggil model observer,
    | sehingga log aktivitas status "dipinjam -> telat" hilang, dan setiap
    | user yang membuka halaman ini memicu write ke database.
    */

    $search = $request->input('search');
    $status = $request->input('status');
    $jenisKelamin = $request->input('jenis_kelamin');
    $tanggalDari = $request->input('tanggal_dari');
    $tanggalSampai = $request->input('tanggal_sampai');

    $peminjamans = Peminjaman::with([
        'user',
        'detailPinjams.alatUnit.alat'
    ])
        ->when($search, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where(
                    'status',
                    'like',
                    "%{$search}%"
                )
                ->orWhereHas('user', function ($user) use ($search) {
                    $user->where(
                        'name',
                        'like',
                        "%{$search}%"
                    );
                });
            });
        })
        ->when($status, function ($query, $status) {
            $query->where('status', $status);
        })
        ->when($jenisKelamin, function ($query, $jenisKelamin) {
            $query->whereHas('user', function ($user) use ($jenisKelamin) {
                $user->where(
                    'jenis_kelamin',
                    $jenisKelamin
                );
            });
        })
        ->when($tanggalDari, function ($query, $tanggalDari) {
            $query->whereDate(
                'tgl_pinjam',
                '>=',
                $tanggalDari
            );
        })
        ->when($tanggalSampai, function ($query, $tanggalSampai) {
            $query->whereDate(
                'tgl_pinjam',
                '<=',
                $tanggalSampai
            );
        })
        ->latest()
        ->paginate(10)
        ->withQueryString();

    return view(
        'admin.peminjaman.index',
        compact(
            'peminjamans',
            'search',
            'status',
            'jenisKelamin',
            'tanggalDari',
            'tanggalSampai'
        )
    );
}


    /**
     * Menampilkan form tambah peminjaman.
     */
    public function createPeminjaman()
    {
        $users = User::where(
            'role',
            'peminjam'
        )->get();

        // Alat diarsipkan disembunyikan dari form peminjaman, tapi
        // tetap ada di DB demi riwayat/laporan (FK detail_pinjam RESTRICT).
        //
        // Satu unit serial = satu barang, jadi filter "bisa dipinjam"
        // berarti alat punya minimal satu unit berstatus 'tersedia'.
        // alatUnit di-eager-load karena form create perlu daftar serial
        // untuk dropdown -- tanpa itu setiap alat memicu satu query (N+1).
        $alats = Alat::tidakTerarsip()
            ->whereHas('alatUnit', fn ($q) => $q->where('kondisi', 'tersedia'))
            ->with(['alatUnit' => fn ($q) => $q->where('kondisi', 'tersedia')->orderBy('serial_number')])
            ->get();

        return view(
            'admin.peminjaman.create',
            compact('users', 'alats')
        );
    }


    /**
     * Menyimpan peminjaman baru.
     */
    public function storePeminjaman(Request $request)
    {
        $request->validate([
            'user_id' =>
                'required|exists:users,id',

            'tgl_pinjam' =>
                'required|date',

            'tgl_kembali_plan' =>
                'required|date|after_or_equal:tgl_pinjam',

            // Satu baris peminjaman = satu unit serial.
            'alat_unit_id' =>
                'required|array',

            'alat_unit_id.*' =>
                'required|distinct|exists:alat_unit,id',
        ]);

        DB::beginTransaction();

        try {

            $peminjaman = Peminjaman::create([
                'user_id' => $request->user_id,
                'tgl_pinjam' => $request->tgl_pinjam,
                'tgl_kembali_plan' =>
                    $request->tgl_kembali_plan,
                'status' => 'diajukan',
            ]);

            /*
            | Tiap unit dipinjam satu kali. Ketersediaan dicek ulang di
            | bawah (bukan hanya lewat validasi), karena antara submit
            | form dan eksekusi baris ini unit bisa diapprove pengajuan
            | lain -- race condition yang validasi alone tidak tangkap.
            */
            foreach ($request->alat_unit_id as $unitId) {

                $unit = AlatUnit::lockForUpdate()->findOrFail($unitId);

                if ($unit->kondisi !== 'tersedia') {
                    $alat = $unit->alat;

                    throw new \Exception(
                        "Unit '{$unit->serial_number}' ({$alat->nama_alat}) tidak tersedia (kondisi: {$unit->kondisi})."
                    );
                }

                DetailPinjam::create([
                    'peminjaman_id' =>
                        $peminjaman->id,

                    'alat_unit_id' =>
                        $unit->id,
                ]);
            }

            DB::commit();

            return redirect()
                ->route('admin.peminjaman.index')
                ->with(
                    'success',
                    'Data peminjaman berhasil diajukan.'
                );

        } catch (\Exception $e) {

            DB::rollBack();

            return back()
                ->withInput()
                ->with(
                    'error',
                    $e->getMessage()
                );
        }
    }


    /**
     * Mengubah status peminjaman.
     *
     * Pengembalian TIDAK dapat dilakukan langsung
     * dari sini. Pengembalian harus melalui:
     *
     * Petugas → Pengajuan → Admin → Persetujuan.
     */
    public function updateStatusPeminjaman(
        Request $request,
        $id
    ) {
        $peminjaman = Peminjaman::with([
            'detailPinjams.alatUnit.alat',
            'pengembalian'
        ])->findOrFail($id);

        $request->validate([
            'status' =>
                'required|in:diajukan,dipinjam,telat,dikembalikan',
        ]);

        DB::beginTransaction();

        try {

            $statusLama = $peminjaman->status;
            $statusBaru = $request->status;


            // =====================================================
            // DIAJUKAN → DIPINJAM
            // =====================================================

            if (
                $statusLama === 'diajukan' &&
                $statusBaru === 'dipinjam'
            ) {

                foreach (
                    $peminjaman->detailPinjams
                    as $detail
                ) {

                    $unit = $detail->alatUnit;

                    if (!$unit) {
                        throw new \Exception(
                            'Detail peminjaman tidak terhubung ke unit mana pun.'
                        );
                    }

                    /*
                    | Atomic: hanya satu request yang bisa memenangkan
                    | kondisi 'tersedia'. Ini pengaman race condition --
                    | kalau unit sudah dikunci oleh approve paralel,
                    | update ini menyerah tanpa exception dan rowCount
                    | jadi 0.
                    */
                    $affected = AlatUnit::where('id', $unit->id)
                        ->where('kondisi', 'tersedia')
                        ->update(['kondisi' => 'dipinjam']);

                    if ($affected !== 1) {
                        throw new \Exception(
                            "Unit '{$unit->serial_number}' ({$unit->alat->nama_alat}) sudah tidak tersedia -- sedang dipinjam atau rusak."
                        );
                    }
                }
            }


            // =====================================================
            // DIPINJAM / TELAT → DIKEMBALIKAN
            // =====================================================

            if (
                $statusBaru === 'dikembalikan' &&
                $statusLama !== 'dikembalikan'
            ) {
                throw new \Exception(
                    'Status dikembalikan hanya dapat diberikan setelah pengajuan pengembalian disetujui Admin.'
                );
            }


            // Update status biasa
            $peminjaman->update([
                'status' => $statusBaru
            ]);

            DB::commit();

            return redirect()
                ->route('admin.peminjaman.index')
                ->with(
                    'success',
                    'Status peminjaman berhasil diperbarui.'
                );

        } catch (\Exception $e) {

            DB::rollBack();

            return back()->with(
                'error',
                $e->getMessage()
            );
        }
    }


    /**
     * Menghapus data peminjaman.
     */
    public function destroyPeminjaman($id)
    {
        $peminjaman = Peminjaman::with([
            'detailPinjams.alatUnit'
        ])->findOrFail($id);

        /*
        | Peminjaman yang masih dipinjam dikapus tanpa pengembalian:
        | barang dianggap balik ke gudang, jadi unit kembali 'tersedia'.
        |
        | Kalau sudah ada pengembalian tercatat (unit diapprove petugas
        | lalu sudah diproses), kondisi unit dibiarkan apa adanya --
        | pengembalian itu yang memegang jawabnya. Memaksa unit ke
        | 'tersedia' di sini akan menghapus jejak kondisi rusak yang
        | sudah disetujui petugas.
        */
        if (in_array($peminjaman->status, ['dipinjam', 'telat'])) {

            foreach (
                $peminjaman->detailPinjams
                as $detail
            ) {

                $unit = $detail->alatUnit;

                if (!$unit) {
                    continue;
                }

                $sudahAdaPengembalian = Pengembalian::where(
                    'peminjaman_id',
                    $peminjaman->id
                )->exists();

                if ($sudahAdaPengembalian) {
                    continue;
                }

                $unit->update(['kondisi' => 'tersedia']);
            }
        }

        $peminjaman->delete();

        return redirect()
            ->route('admin.peminjaman.index')
            ->with(
                'success',
                'Data peminjaman berhasil dihapus.'
            );
    }


    // =========================================================
    // SEARCH USER
    // =========================================================

    /**
     * Pencarian user untuk form peminjaman.
     */
    public function searchUser(Request $request)
    {
        $keyword = $request->input('search');

        if (strlen($keyword) < 3) {
            return response()->json([]);
        }

        $users = User::where(
            'role',
            'peminjam'
        )
            ->where(function ($query) use ($keyword) {
                $query->where(
                    'name',
                    'like',
                    "%{$keyword}%"
                )
                    ->orWhere(
                        'email',
                        'like',
                        "%{$keyword}%"
                    );
            })
            ->limit(10)
            ->get([
                'id',
                'name',
                'email'
            ]);

        return response()->json($users);
    }


    // =========================================================
    // SEARCH ALAT
    // =========================================================

    /**
     * Pencarian alat untuk form peminjaman.
     */
    public function searchAlat(Request $request)
    {
        $keyword = $request->input('search');

        if (strlen($keyword) < 3) {
            return response()->json([]);
        }

        $alats = Alat::tidakTerarsip()
            ->whereHas(
                'alatUnit',
                fn ($q) => $q->where('kondisi', 'tersedia')
            )
            ->where(
                'nama_alat',
                'like',
                "%{$keyword}%"
            )
            ->limit(10)
            ->get([
                'id',
                'nama_alat',
            ]);

        /*
        | Sertakan unit serialnya, tidak cuma hitungannya: form peminjaman
        | membangun dropdown "Pilih serial" dari list ini. Tanpa units,
        | dropdown kosong dan field required-nya menghalangi submit
        | (user tidak bisa menambahkan peminjaman sama sekali).
        |
        | Hanya unit tersedia yang dipakai -- unit dipinjam/rusak tidak
        | boleh jadi pilihan peminjaman baru.
        |
        | Key 'units' dibuat manual: relasi bernama alatUnit, dan default
        | serialisasi Eloquent menghasilkan 'alat_unit'. Frontend membaca
        | alat.units, jadi petakan eksplisit supaya keduanya cocok tanpa
        | mengubah nama relasi di model.
        */
        $alats->load([
            'alatUnit' => fn ($q) => $q
                ->where('kondisi', 'tersedia')
                ->orderBy('serial_number')
                ->select(['id', 'alat_id', 'serial_number', 'kondisi']),
        ]);

        $alats->loadCount([
            'alatUnit as jumlah_tersedia' => fn ($q) => $q->where('kondisi', 'tersedia'),
        ]);

        return response()->json(
            $alats->map(fn (Alat $a) => array_merge($a->toArray(), [
                'units' => $a->alatUnit->map(fn (AlatUnit $u) => [
                    'id' => $u->id,
                    'serial_number' => $u->serial_number,
                    'kondisi' => $u->kondisi,
                ]),
            ]))
        );
    }

    // =========================================================
    // CETAK LAPORAN
    // Admin melihat semua data pengembalian (semua petugas).
    // =========================================================

    /**
     * Menampilkan halaman Cetak Laporan untuk Admin.
     * Mencakup seluruh data pengembalian disetujui dari semua petugas.
     */
    public function indexLaporan(Request $request)
    {
        $tanggalMulai = $request->input('tanggal_mulai');
        $tanggalSelesai = $request->input('tanggal_selesai');

        $query = Pengembalian::with([
            'peminjaman.user',
            'peminjaman.detailPinjams.alatUnit.alat',
            'petugas',
        ])
            ->where('status_request', 'disetujui');

        if (!empty($tanggalMulai)) {
            $query->whereDate('tgl_kembali', '>=', $tanggalMulai);
        }

        if (!empty($tanggalSelesai)) {
            $query->whereDate('tgl_kembali', '<=', $tanggalSelesai);
        }

        $pengembalians = $query
            ->orderBy('tgl_kembali', 'desc')
            ->paginate(10)
            ->withQueryString();

        return view('admin.laporan.index', [
            'pengembalians' => $pengembalians,
            'tanggalMulai' => $tanggalMulai,
            'tanggalSelesai' => $tanggalSelesai,
        ]);
    }

    /**
     * Mengekspor laporan pengembalian ke file Excel (.xlsx).
     * Admin melihat semua data (seluruh petugas).
     */
    public function cetakLaporanExcel(Request $request)
    {
        $request->validate([
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
        ]);

        $tanggalMulai = $request->input('tanggal_mulai');
        $tanggalSelesai = $request->input('tanggal_selesai');

        $query = Pengembalian::with([
            'peminjaman.user',
            'peminjaman.detailPinjams.alatUnit.alat',
            'petugas',
        ])
            ->where('status_request', 'disetujui');

        if ($tanggalMulai) {
            $query->whereDate('tgl_kembali', '>=', $tanggalMulai);
        }

        if ($tanggalSelesai) {
            $query->whereDate('tgl_kembali', '<=', $tanggalSelesai);
        }

        $pengembalians = $query->orderBy('tgl_kembali', 'desc')->get();

        $namaFile = 'laporan-pengembalian';
        if ($tanggalMulai || $tanggalSelesai) {
            $namaFile .= '-' . ($tanggalMulai ?? 'awal') . '-sd-' . ($tanggalSelesai ?? 'akhir');
        }

        return (new \App\Exports\LaporanPengembalianExport(
            $pengembalians,
            $tanggalMulai,
            $tanggalSelesai,
            auth()->user()->name
        ))->download($namaFile . '.xlsx');
    }

    /**
     * Mencetak laporan pengembalian ke PDF.
     * Admin melihat semua data (seluruh petugas).
     */
    public function cetakLaporanPdf(Request $request)
    {
        $request->validate([
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
        ]);

        $tanggalMulai = $request->input('tanggal_mulai');
        $tanggalSelesai = $request->input('tanggal_selesai');

        $query = Pengembalian::with([
            'peminjaman.user',
            'peminjaman.detailPinjams.alatUnit.alat',
            'petugas',
        ])
            ->where('status_request', 'disetujui');

        if ($tanggalMulai) {
            $query->whereDate('tgl_kembali', '>=', $tanggalMulai);
        }

        if ($tanggalSelesai) {
            $query->whereDate('tgl_kembali', '<=', $tanggalSelesai);
        }

        $pengembalians = $query->orderBy('tgl_kembali', 'desc')->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.laporan.pdf', [
            'pengembalians' => $pengembalians,
            'tanggalMulai' => $tanggalMulai,
            'tanggalSelesai' => $tanggalSelesai,
        ]);

        $pdf->setPaper('a4', 'landscape');

        return $pdf->stream('laporan-pengembalian.pdf');
    }

    //=====================================
    //LOG AKTIVITAS//
    //=====================================
    public function indexLogAktivitas(Request $request)
{
    $logs = LogAktivitas::with('user')
        ->latest()
        ->paginate(10)
        ->withQueryString();

    return view('admin.log_aktivitas.index', compact('logs'));
}
}
