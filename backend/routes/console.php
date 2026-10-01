<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Jadwal pengecekan peminjaman terlambat
|--------------------------------------------------------------------------
| Dijalankan setiap jam, bukan saat user membuka halaman index peminjaman.
| Lihat app/Console/Commands/HitungPeminjamanTelat.php untuk alasan
| kenapa ini tidak boleh mass update di dalam controller.
*/
Schedule::command('app:hitung-peminjaman-telat')->hourly();

/*
| Unit 'dipinjam' tanpa detail peminjaman = keadaan tidak mungkin yang
| membuat unit hilang dari sirkulasi selamanya. Bersihkan tiap jam
| bersamaan dengan cek telat, jadi operator tidak perlu menjalankan
| command manual kalau ada data yang tidak sinkron.
*/
Schedule::command('app:lepas-unit-yatim')->hourly();
