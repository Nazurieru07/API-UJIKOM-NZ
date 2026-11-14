<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class LogAktivitasSeeder extends Seeder
{
    public function run(): void
    {
        // Log aktivitas dibuat otomatis oleh observer aplikasi.
        // Seeder tidak menambahkan log simulasi agar riwayat audit
        // tetap mencerminkan aktivitas pengguna yang sebenarnya.
    }
}
