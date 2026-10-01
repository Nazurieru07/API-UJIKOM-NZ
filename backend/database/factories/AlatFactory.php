<?php

namespace Database\Factories;

use App\Models\Alat;
use App\Models\AlatUnit;
use App\Models\Kategori;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Alat>
 */
class AlatFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kategori_id' => Kategori::factory(),
            'nama_alat' => fake()->unique()->words(2, true),
            'kode_alat' => strtoupper(substr(fake()->unique()->word(), 0, 2)),
            'deskripsi' => fake()->sentence(),
            'gambar' => null,
            'is_arsip' => false,
        ];
    }

    /**
     * Alat dengan N unit tersedia (semua kondisi 'tersedia').
     */
    public function denganUnit(int $jumlah = 3): static
    {
        return $this->afterCreating(function (Alat $alat) use ($jumlah) {
            /*
            | Serial dihitung lewat serialBerikutnyaBatch(), bukan nomor
            | 1..N hardcoded. kode_alat diambil dari 2 huruf pertama nama
            | alat acak, jadi dua alat bisa kebagian kode yang sama
            | (mis. "Meja Lipat" dan "Merah Putih" -> MP). Nomor hardcode
            | akan menghasilkan MP-001 dua kali lalu kena UNIQUE.
            |
            | Konsekuensinya: alat kedua dapat nomor lanjutan (MP-004...)
            | -- tidak ada unit yang gagal dibuat.
            */
            foreach (AlatUnit::serialBerikutnyaBatch($alat->kode_alat, $jumlah) as $serial) {
                AlatUnit::create([
                    'alat_id' => $alat->id,
                    'serial_number' => $serial,
                    'kondisi' => 'tersedia',
                ]);
            }
        });
    }
}
