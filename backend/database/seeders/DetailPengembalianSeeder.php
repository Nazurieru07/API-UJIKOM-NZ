<?php

namespace Database\Seeders;

use App\Models\DetailPengembalian;
use App\Models\DetailPinjam;
use App\Models\Pengembalian;
use Illuminate\Database\Seeder;
use RuntimeException;

class DetailPengembalianSeeder extends Seeder
{
    public function run(): void
    {
        // Kondisi unit yang sudah dikembalikan.
        // Urutan kondisi mengikuti urutan detail peminjaman.
        $details = [
            [
                'peminjaman_id' => 1,
                'kondisi' => [
                    'Baik',
                    'Baik',
                ],
            ],
            [
                'peminjaman_id' => 2,
                'kondisi' => [
                    'Baik',
                ],
            ],
            [
                'peminjaman_id' => 3,
                'kondisi' => [
                    'Rusak',
                ],
            ],
        ];

        foreach ($details as $item) {
            $pengembalian = Pengembalian::where(
                'peminjaman_id',
                $item['peminjaman_id']
            )->firstOrFail();

            // Ambil detail unit dari peminjaman yang sesuai.
            $detailPinjams = DetailPinjam::where(
                'peminjaman_id',
                $item['peminjaman_id']
            )
                ->orderBy('id')
                ->get();

            // Pastikan jumlah detail peminjaman mencukupi.
            if ($detailPinjams->count() < count($item['kondisi'])) {
                throw new RuntimeException(
                    "Detail peminjaman ID {$item['peminjaman_id']} tidak mencukupi."
                );
            }

            foreach ($item['kondisi'] as $index => $kondisi) {
                $detailPinjam = $detailPinjams->get($index);

                $unit = $detailPinjam->alatUnit;

                if (!$unit) {
                    throw new RuntimeException(
                        "Unit alat pada detail peminjaman ID {$detailPinjam->id} tidak ditemukan."
                    );
                }

                DetailPengembalian::updateOrCreate(
                    [
                        'detail_pinjam_id' => $detailPinjam->id,
                    ],
                    [
                        'pengembalian_id' => $pengembalian->id,
                        'kondisi_kembali' => $kondisi,
                        'denda_kerusakan' => 0,
                    ]
                );

                // Unit yang sudah kembali tidak lagi berstatus dipinjam.
                $unit->update([
                    'kondisi' => $kondisi === 'Rusak'
                        ? 'rusak'
                        : 'tersedia',
                ]);
            }
        }
    }
}
