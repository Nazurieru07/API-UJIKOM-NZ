<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use App\Models\Peminjaman;
use App\Models\DetailPinjam;
use App\Models\User;
use App\Notifications\PeminjamanDiajukanNotification;
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
            ->where('stok', '>', 0)
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

            // Pastikan jumlah tidak melebihi stok
            if ($jumlah > $alat->stok) {

                throw new \Exception(
                    "Jumlah {$alat->nama_alat} yang dipinjam tidak boleh melebihi stok tersedia ({$alat->stok})."
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
}