<?php

namespace Database\Seeders;

use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Database\Seeder;

class PeminjamanSeeder extends Seeder
{
    public function run(): void
    {
        // Data contoh peminjaman menggunakan email sebagai identitas pengguna.
        $peminjaman = [
            [
                'email' => 'rian@gmail.com',
                'tgl_pinjam' => '2026-06-01',
                'tgl_kembali_plan' => '2026-06-04',
                'status' => 'dikembalikan',
            ],
            [
                'email' => 'siti@gmail.com',
                'tgl_pinjam' => '2026-06-02',
                'tgl_kembali_plan' => '2026-06-05',
                'status' => 'dikembalikan',
            ],
            [
                'email' => 'eka@gmail.com',
                'tgl_pinjam' => '2026-06-03',
                'tgl_kembali_plan' => '2026-06-06',
                'status' => 'telat',
            ],
            [
                'email' => 'rian@gmail.com',
                'tgl_pinjam' => '2026-06-08',
                'tgl_kembali_plan' => '2026-06-11',
                'status' => 'dipinjam',
            ],
            [
                'email' => 'siti@gmail.com',
                'tgl_pinjam' => '2026-06-09',
                'tgl_kembali_plan' => '2026-06-12',
                'status' => 'diajukan',
            ],
        ];

        foreach ($peminjaman as $pinjam) {
            // Cari pengguna berdasarkan email, bukan ID tetap.
            $user = User::where('email', $pinjam['email'])->firstOrFail();

            // Buat peminjaman hanya jika data dengan identitas tersebut belum ada.
            Peminjaman::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'tgl_pinjam' => $pinjam['tgl_pinjam'],
                    'tgl_kembali_plan' => $pinjam['tgl_kembali_plan'],
                ],
                [
                    'status' => $pinjam['status'],
                ]
            );
        }
    }
}
