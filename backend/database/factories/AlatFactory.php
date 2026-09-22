<?php

namespace Database\Factories;

use App\Models\Kategori;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Alat>
 */
class AlatFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kategori_id' => Kategori::factory(),
            'nama_alat' => fake()->words(2, true),
            'stok' => 10,
            'stok_baik' => 10,
            'stok_rusak' => 0,
            'stok_rusak_parah' => 0,
            'status_kondisi' => 'Baik',
            'deskripsi' => fake()->sentence(),
            'gambar' => null,
        ];
    }
}
