<?php

namespace Database\Factories;

use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Peminjaman>
 */
class PeminjamanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'tgl_pinjam' => today(),
            'tgl_kembali_plan' => today()->addDays(7),
            'status' => 'diajukan',
        ];
    }
}
