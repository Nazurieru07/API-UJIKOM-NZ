<?php

namespace Database\Seeders;

use App\Models\LogAktivitas;
use Illuminate\Database\Seeder;

class LogAktivitasSeeder extends Seeder
{
    public function run(): void
    {
        $logs = [
            ['user_id' => 1, 'aktivitas' => 'Melakukan import data master alat baru sebanyak 5 entitas.'],
            ['user_id' => 2, 'aktivitas' => 'Menyetujui permohonan peminjaman ID #4.'],
            ['user_id' => 3, 'aktivitas' => 'Mengajukan peminjaman alat baru untuk kebutuhan praktik kelompok.'],
            ['user_id' => 2, 'aktivitas' => 'Memproses pengembalian alat telat untuk peminjaman ID #3 dan mengenakan denda.'],
            ['user_id' => 1, 'aktivitas' => 'Mengubah konfigurasi hak akses aplikasi.'],
        ];

        foreach ($logs as $log) {
            LogAktivitas::create($log);
        }
    }
}