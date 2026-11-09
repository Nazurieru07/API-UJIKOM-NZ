<?php

namespace Database\Seeders;

use App\Models\Pengembalian;
use Illuminate\Database\Seeder;

class PengembalianSeeder extends Seeder
{
    public function run(): void
    {
        $pengembalian = [
            [
                'peminjaman_id' => 1,
                'tgl_kembali' => '2026-06-04',
                'kondisi_kembali' => 'Baik',
                'denda' => 0,
                'denda_kerusakan' => 0,
                'petugas_id' => 2,
                'status_request' => 'disetujui',
                'is_parsial' => false,
                'jumlah_unit_dikembalikan' => 2,
            ],
            [
                'peminjaman_id' => 2,
                'tgl_kembali' => '2026-06-05',
                'kondisi_kembali' => 'Baik',
                'denda' => 0,
                'denda_kerusakan' => 0,
                'petugas_id' => 2,
                'status_request' => 'disetujui',
                'is_parsial' => false,
                'jumlah_unit_dikembalikan' => 1,
            ],
            [
                'peminjaman_id' => 3,
                'tgl_kembali' => '2026-06-09',
                'kondisi_kembali' => 'Rusak',
                'denda' => 0,
                'denda_kerusakan' => 0,
                'petugas_id' => 2,
                'status_request' => 'disetujui',
                'is_parsial' => true,
                'jumlah_unit_dikembalikan' => 1,
            ],
        ];

        foreach ($pengembalian as $item) {
            Pengembalian::updateOrCreate(
                ['peminjaman_id' => $item['peminjaman_id']],
                $item
            );
        }
    }
}
