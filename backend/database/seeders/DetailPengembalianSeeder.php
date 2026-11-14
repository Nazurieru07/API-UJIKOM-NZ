<?php

namespace Database\Seeders;

use App\Models\DetailPengembalian;
use App\Models\DetailPinjam;
use App\Models\Pengembalian;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class DetailPengembalianSeeder extends Seeder
{
    public function run(): void
    {
        // Data contoh kondisi unit yang dikembalikan.
        // Jumlah kondisi mengikuti jumlah unit pada peminjaman.
        $details = [
            [
                'email' => 'rian@gmail.com',
                'tgl_pinjam' => '2026-06-01',
                'tgl_kembali_plan' => '2026-06-04',
                'kondisi' => [
                    'Baik',
                    'Baik',
                ],
            ],
            [
                'email' => 'siti@gmail.com',
                'tgl_pinjam' => '2026-06-02',
                'tgl_kembali_plan' => '2026-06-05',
                'kondisi' => [
                    'Baik',
                ],
            ],
            [
                'email' => 'eka@gmail.com',
                'tgl_pinjam' => '2026-06-03',
                'tgl_kembali_plan' => '2026-06-06',
                'kondisi' => [
                    'Rusak',
                ],
            ],
        ];

        foreach ($details as $item) {
            // Cari peminjam berdasarkan email.
            $user = User::where('email', $item['email'])
                ->firstOrFail();

            // Cari peminjaman berdasarkan pengguna dan tanggal.
            $peminjaman = Peminjaman::where('user_id', $user->id)
                ->where('tgl_pinjam', $item['tgl_pinjam'])
                ->where('tgl_kembali_plan', $item['tgl_kembali_plan'])
                ->firstOrFail();

            // Cari data pengembalian milik peminjaman tersebut.
            $pengembalian = Pengembalian::where(
                'peminjaman_id',
                $peminjaman->id
            )->firstOrFail();

            // Ambil detail unit berdasarkan urutan ID.
            $detailPinjams = DetailPinjam::where(
                'peminjaman_id',
                $peminjaman->id
            )
                ->orderBy('id')
                ->get();

            // Pastikan jumlah unit sesuai dengan data kondisi.
            if ($detailPinjams->count() < count($item['kondisi'])) {
                throw new RuntimeException(
                    "Detail peminjaman {$peminjaman->id} tidak mencukupi."
                );
            }

            foreach ($item['kondisi'] as $index => $kondisi) {
                $detailPinjam = $detailPinjams->get($index);

                // Pastikan unit alat tersedia pada detail peminjaman.
                $unit = $detailPinjam->alatUnit;

                if (!$unit) {
                    throw new RuntimeException(
                        "Unit alat pada detail peminjaman ID {$detailPinjam->id} tidak ditemukan."
                    );
                }

                // Buat detail pengembalian hanya jika belum ada.
                $detailPengembalian = DetailPengembalian::firstOrCreate(
                    [
                        'detail_pinjam_id' => $detailPinjam->id,
                    ],
                    [
                        'pengembalian_id' => $pengembalian->id,
                        'kondisi_kembali' => $kondisi,
                        'denda_kerusakan' => 0,
                    ]
                );

                // Ubah kondisi unit hanya jika detail pengembalian baru dibuat.
                if ($detailPengembalian->wasRecentlyCreated) {
                    $unit->update([
                        'kondisi' => $kondisi === 'Rusak'
                            ? 'rusak'
                            : 'tersedia',
                    ]);
                }
            }
        }
    }
}
