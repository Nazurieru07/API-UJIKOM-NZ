<?php

namespace Database\Seeders;

use App\Models\Pengembalian;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Database\Seeder;

class PengembalianSeeder extends Seeder
{
    public function run(): void
    {
        // Data contoh pengembalian menggunakan email dan tanggal
        // agar tidak bergantung pada ID tetap.
        $pengembalian = [
            [
                'email' => 'rian@gmail.com',
                'tgl_pinjam' => '2026-06-01',
                'tgl_kembali_plan' => '2026-06-04',
                'tgl_kembali' => '2026-06-04',
                'kondisi_kembali' => 'Baik',
                'denda' => 0,
                'denda_kerusakan' => 0,
                'status_request' => 'disetujui',
                'is_parsial' => false,
                'jumlah_unit_dikembalikan' => 2,
            ],
            [
                'email' => 'siti@gmail.com',
                'tgl_pinjam' => '2026-06-02',
                'tgl_kembali_plan' => '2026-06-05',
                'tgl_kembali' => '2026-06-05',
                'kondisi_kembali' => 'Baik',
                'denda' => 0,
                'denda_kerusakan' => 0,
                'status_request' => 'disetujui',
                'is_parsial' => false,
                'jumlah_unit_dikembalikan' => 1,
            ],
            [
                'email' => 'eka@gmail.com',
                'tgl_pinjam' => '2026-06-03',
                'tgl_kembali_plan' => '2026-06-06',
                'tgl_kembali' => '2026-06-09',
                'kondisi_kembali' => 'Rusak',
                'denda' => 0,
                'denda_kerusakan' => 0,
                'status_request' => 'disetujui',
                'is_parsial' => true,
                'jumlah_unit_dikembalikan' => 1,
            ],
        ];

        // Cari petugas berdasarkan email, bukan ID tetap.
        $petugas = User::where('email', 'petugas@gmail.com')->firstOrFail();

        foreach ($pengembalian as $item) {
            // Cari pengguna yang melakukan peminjaman.
            $user = User::where('email', $item['email'])->firstOrFail();

            // Cari peminjaman berdasarkan pengguna dan tanggal.
            $peminjaman = Peminjaman::where('user_id', $user->id)
                ->where('tgl_pinjam', $item['tgl_pinjam'])
                ->where('tgl_kembali_plan', $item['tgl_kembali_plan'])
                ->firstOrFail();

            // Buat pengembalian hanya jika belum ada.
            Pengembalian::firstOrCreate(
                [
                    'peminjaman_id' => $peminjaman->id,
                ],
                [
                    'tgl_kembali' => $item['tgl_kembali'],
                    'kondisi_kembali' => $item['kondisi_kembali'],
                    'denda' => $item['denda'],
                    'denda_kerusakan' => $item['denda_kerusakan'],
                    'petugas_id' => $petugas->id,
                    'status_request' => $item['status_request'],
                    'is_parsial' => $item['is_parsial'],
                    'jumlah_unit_dikembalikan' => $item['jumlah_unit_dikembalikan'],
                ]
            );
        }
    }
}
