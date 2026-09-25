<?php

namespace App\Http\Controllers;

use App\Models\Alat;
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
     */
    public function katalogAlat()
    {
        $alats = Alat::with('kategori')
            ->where('stok_baik', '>', 0)
            ->orderBy('nama_alat')
            ->get();

        return view('peminjam.katalog', compact('alats'));
    }


    /**
     * Memproses pengajuan peminjaman dari peminjam.
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
            'required',
            'array',
            'min:1',
        ],

        'alat_id.*' => [
            'required',
            'integer',
            'distinct',
            'exists:alat,id',
        ],

        'jumlah' => [
            'required',
            'array',
            'min:1',
        ],

        'jumlah.*' => [
            'required',
            'integer',
            'min:1',
        ],
    ], [
        'tgl_kembali_plan.required' => 'Tanggal rencana kembali wajib diisi.',
        'tgl_kembali_plan.after' => 'Tanggal rencana kembali harus setelah hari ini.',

        'alat_id.required' => 'Silakan pilih minimal satu alat.',
        'alat_id.min' => 'Silakan pilih minimal satu alat.',
        'alat_id.*.exists' => 'Alat yang dipilih tidak ditemukan.',
        'alat_id.*.distinct' => 'Alat yang sama tidak boleh dipilih lebih dari satu kali.',

        'jumlah.required' => 'Jumlah alat wajib diisi.',
        'jumlah.min' => 'Jumlah alat wajib diisi.',
        'jumlah.*.required' => 'Jumlah alat wajib diisi.',
        'jumlah.*.integer' => 'Jumlah alat harus berupa angka.',
        'jumlah.*.min' => 'Jumlah alat minimal 1.',
    ]);

    DB::beginTransaction();

    try {

        // Buat data utama peminjaman
        $peminjaman = Peminjaman::create([
            'user_id' => auth()->id(),
            'tgl_pinjam' => now(),
            'tgl_kembali_plan' => $request->tgl_kembali_plan,
            'status' => 'diajukan',
        ]);

        // Simpan setiap alat yang dipilih
        foreach ($request->alat_id as $alatId) {

            // Kunci data alat selama transaksi
            $alat = Alat::lockForUpdate()->findOrFail($alatId);

            // Ambil jumlah berdasarkan ID alat
            $jumlah = (int) $request->jumlah[$alatId];

            // Pastikan jumlah tidak melebihi stok baik
            if ($jumlah > $alat->stok_baik) {

                throw new \Exception(
                    "Jumlah {$alat->nama_alat} yang dipinjam tidak boleh melebihi stok baik tersedia ({$alat->stok_baik})."
                );
            }

            // Simpan detail peminjaman
            DetailPinjam::create([
                'peminjaman_id' => $peminjaman->id,
                'alat_id' => $alat->id,
                'jumlah' => $jumlah,
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
            'detailPinjams.alat',
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
     * Form pengajuan edit peminjaman (tambah/hapus alat, ubah tanggal).
     */
    public function formEditPeminjaman($id)
    {
        $peminjaman = Peminjaman::with('detailPinjams.alat')
            ->where('user_id', auth()->id())
            ->whereIn('status', ['dipinjam', 'telat'])
            ->findOrFail($id);

        $alats = Alat::with('kategori')
            ->where('stok_baik', '>', 0)
            ->orderBy('nama_alat')
            ->get();

        return view('peminjam.edit-peminjaman', compact('peminjaman', 'alats'));
    }

    /**
     * Simpan pengajuan edit peminjaman (status menunggu, menunggu persetujuan petugas).
     */
    public function ajukanEditPeminjaman(Request $request, $id)
    {
        $peminjaman = Peminjaman::with('detailPinjams')
            ->where('user_id', auth()->id())
            ->whereIn('status', ['dipinjam', 'telat'])
            ->findOrFail($id);

        $validated = $request->validate([
            'tgl_kembali_plan_baru' => 'required|date|after_or_equal:today',
            'alasan' => 'nullable|string|max:500',
            'alat_id' => 'required|array|min:1',
            'alat_id.*' => 'required|integer|distinct|exists:alat,id',
            'jumlah' => 'required|array',
            'jumlah.*' => 'required|integer|min:1',
            'aksi' => 'required|array',
            'aksi.*' => 'required|in:tambah,hapus',
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

            foreach ($validated['alat_id'] as $i => $alatId) {
                DetailPermintaanEdit::create([
                    'permintaan_edit_id' => $permintaanEdit->id,
                    'alat_id' => $alatId,
                    'jumlah' => $validated['jumlah'][$i],
                    'aksi' => $validated['aksi'][$i],
                ]);
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
