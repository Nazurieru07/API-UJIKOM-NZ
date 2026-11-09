<?php

namespace Database\Seeders;

use App\Models\Alat;
use App\Models\AlatUnit;
use App\Models\DetailPinjam;
use App\Models\DetailPengembalian;
use App\Models\Peminjaman;
use Illuminate\Database\Seeder;
use RuntimeException;

class DetailPinjamSeeder extends Seeder
{
    public function run(): void
    {
        // Data peminjaman dan jumlah unit sesuai data contoh.
        $details = [
            [
                'peminjaman_id' => 1,
                'nama_alat' => 'Router Mikrotik RB941-2nD',
                'jumlah' => 2,
            ],
            [
                'peminjaman_id' => 2,
                'nama_alat' => 'Kamera DSLR Canon EOS 3000D',
                'jumlah' => 1,
            ],
            [
                'peminjaman_id' => 3,
                'nama_alat' => 'Mini PC Intel NUC 11',
                'jumlah' => 2,
            ],
            [
                'peminjaman_id' => 4,
                'nama_alat' => 'Tang Crimping RJ45/RJ11 Proskit',
                'jumlah' => 2,
            ],
            [
                'peminjaman_id' => 5,
                'nama_alat' => 'Adapter HDMI to VGA dengan Audio',
                'jumlah' => 3,
            ],
        ];

        foreach ($details as $item) {
            $peminjaman = Peminjaman::findOrFail(
                $item['peminjaman_id']
            );

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

            // Ambil hanya unit tersedia yang belum dialokasikan
            // ke detail peminjaman mana pun.
            $unitBaru = collect();

            if ($kekurangan > 0) {
                $unitBaru = AlatUnit::where('alat_id', $alat->id)
                    ->where('kondisi', 'tersedia')
                    ->whereNotIn(
                        'id',
                        DetailPinjam::select('alat_unit_id')
                    )
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

                // Pengajuan yang masih menunggu tidak mengunci unit.
                // Unit yang sudah dikembalikan tidak boleh diubah lagi.
                $sudahDikembalikan = DetailPengembalian::where(
                    'detail_pinjam_id',
                    $detail->id
                )->exists();

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
