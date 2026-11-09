<?php

namespace Database\Seeders;

use App\Models\Alat;
use App\Models\AlatUnit;
use Illuminate\Database\Seeder;

class AlatUnitSeeder extends Seeder
{
    public function run(): void
    {
        $targetUnit = [
            'Router Mikrotik RB941-2nD' => 15,
            'Kamera DSLR Canon EOS 3000D' => 5,
            'Mini PC Intel NUC 11' => 8,
            'Tang Crimping RJ45/RJ11 Proskit' => 20,
            'Adapter HDMI to VGA dengan Audio' => 25,
        ];

        foreach ($targetUnit as $namaAlat => $target) {
            $alat = Alat::where('nama_alat', $namaAlat)->firstOrFail();

            $jumlahSekarang = $alat->alatUnit()->count();
            $kekurangan = $target - $jumlahSekarang;

            if ($kekurangan <= 0) {
                continue;
            }

            $serialNumbers = AlatUnit::serialBerikutnyaBatch(
                $alat->kode_alat,
                $kekurangan
            );

            foreach ($serialNumbers as $serial) {
                $alat->alatUnit()->create([
                    'serial_number' => $serial,
                    'kondisi' => 'tersedia',
                ]);
            }
        }
    }
}
