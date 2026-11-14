<?php

namespace Database\Seeders;

use App\Models\Alat;
use App\Models\AlatUnit;
use App\Models\DetailPinjam;
use App\Models\DetailPengembalian;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class DetailPinjamSeeder extends Seeder
{
    public function run(): void
    {
        // Data contoh peminjaman menggunakan email dan tanggal,
        // bukan ID peminjaman yang nilainya bisa berbeda.
        $details = [
            [
                'email' => 'rian@gmail.com',
                'tgl_pinjam' => '2026-06-01',
                'tgl_kembali_plan' => '2026-06-04',
                'nama_alat' => 'Router Mikrotik RB941-2nD',
                'jumlah' => 2,
            ],
            [
                'email' => 'siti@gmail.com',
                'tgl_pinjam' => '2026-06-02',
                'tgl_kembali_plan' => '2026-06-05',
                'nama_alat' => 'Kamera DSLR Canon EOS 3000D',
                'jumlah' => 1,
            ],
            [
                'email' => 'eka@gmail.com',
                'tgl_pinjam' => '2026-06-03',
                'tgl_kembali_plan' => '2026-06-06',
                'nama_alat' => 'Mini PC Intel NUC 11',
                'jumlah' => 2,
            ],
            [
                'email' => 'rian@gmail.com',
                'tgl_pinjam' => '2026-06-08',
                'tgl_kembali_plan' => '2026-06-11',
                'nama_alat' => 'Tang Crimping RJ45/RJ11 Proskit',
                'jumlah' => 2,
            ],
            [
                'email' => 'siti@gmail.com',
                'tgl_pinjam' => '2026-06-09',
                'tgl_kembali_plan' => '2026-06-12',
                'nama_alat' => 'Adapter HDMI to VGA dengan Audio',
                'jumlah' => 3,
            ],
        ];

        foreach ($details as $item) {
            // Cari pengguna berdasarkan email.
            $user = User::where('email', $item['email'])->firstOrFail();

            // Cari peminjaman berdasarkan pengguna dan tanggalnya.
            $peminjaman = Peminjaman::where('user_id', $user->id)
                ->where('tgl_pinjam', $item['tgl_pinjam'])
                ->where('tgl_kembali_plan', $item['tgl_kembali_plan'])
                ->firstOrFail();

            // Cari alat berdasarkan nama.
            $alat = Alat::where(
                'nama_alat',
                $item['nama_alat']
            )->firstOrFail();

            // Pertahankan unit yang sudah terhubung dengan peminjaman ini.
            $detailYangAda = DetailPinjam::where(
                'peminjaman_id',
                $peminjaman->id
            )
                ->whereHas('alatUnit', function ($query) use ($alat) {
                    $query->where('alat_id', $alat->id);
                })
                ->with('alatUnit')
                ->orderBy('id')
                ->get();

            if ($detailYangAda->count() > $item['jumlah']) {
                throw new RuntimeException(
                    "Jumlah detail peminjaman {$peminjaman->id} " .
                    "melebihi jumlah contoh untuk alat {$item['nama_alat']}."
                );
            }

            $kekurangan = $item['jumlah'] - $detailYangAda->count();

            // Ambil unit tersedia yang tidak sedang dialokasikan
            // ke peminjaman yang belum selesai.
            $unitBaru = collect();

            if ($kekurangan > 0) {
                $unitBaru = AlatUnit::where('alat_id', $alat->id)
                    ->where('kondisi', 'tersedia')
                    ->whereNotIn('id', function ($query) {
                        $query->select('dp.alat_unit_id')
                            ->from('detail_pinjam as dp')
                            ->join('peminjaman as p', 'p.id', '=', 'dp.peminjaman_id')
                            ->whereNotNull('dp.alat_unit_id')
                            ->whereIn('p.status', [
                                'diajukan',
                                'dipinjam',
                                'telat',
                            ])
                            ->whereNotExists(function ($subQuery) {
                                $subQuery->selectRaw('1')
                                    ->from('detail_pengembalian as dr')
                                    ->whereColumn('dr.detail_pinjam_id', 'dp.id');
                            });
                    })
                    ->orderBy('serial_number')
                    ->limit($kekurangan)
                    ->get();
            }

            if ($unitBaru->count() !== $kekurangan) {
                throw new RuntimeException(
                    "Unit tersedia untuk {$item['nama_alat']} tidak mencukupi. " .
                    "Dibutuhkan tambahan {$kekurangan} unit."
                );
            }

            // Gabungkan unit lama dan unit yang baru dipilih.
            $semuaUnit = $detailYangAda
                ->pluck('alatUnit')
                ->merge($unitBaru);

            foreach ($semuaUnit as $unit) {
                // Pastikan detail peminjaman tidak dibuat dua kali.
                $detail = DetailPinjam::firstOrCreate([
                    'peminjaman_id' => $peminjaman->id,
                    'alat_unit_id' => $unit->id,
                ]);

                // Periksa apakah unit sudah memiliki detail pengembalian.
                $sudahDikembalikan = DetailPengembalian::where(
                    'detail_pinjam_id',
                    $detail->id
                )->exists();

                // Unit hanya diubah menjadi dipinjam jika status
                // peminjamannya aktif dan belum dikembalikan.
                if (
                    in_array($peminjaman->status, ['dipinjam', 'telat'], true)
                    && !$sudahDikembalikan
                ) {
                    $unit->update([
                        'kondisi' => 'dipinjam',
                    ]);
                }
            }
        }
    }
}
