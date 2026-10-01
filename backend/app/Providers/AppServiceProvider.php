<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Models\Alat;
use App\Models\AlatUnit;
use App\Models\Peminjaman;
use App\Models\Pengembalian;

use App\Observers\AlatObserver;
use App\Observers\AlatUnitObserver;
use App\Observers\PeminjamanObserver;
use App\Observers\PengembalianObserver;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        /*
        | Zona Waktu Aplikasi
        | Asia/Jakarta = WIB (UTC+7). Semua perhitungan tanggal
        | (denda keterlambatan, status telat, tgl_pinjam) pakai WIB.
        */

        date_default_timezone_set('Asia/Jakarta');

        /*
        | Register Observer
        |
        | Observer mencatat setiap perubahan model ke log_aktivitas
        | secara otomatis. DENGAN SYARAT: model harus disimpan per
        | instance (save/update), bukan mass update query builder.
        |
        | Mass update (Model::where(...)->update()) melewati observer,
        | jadi log aktivitas tidak tercatat. Lihat app/Console/Commands/
        | HitungPeminjamanTelat.php untuk contoh implementasi yang benar.
        |
        | AlatUnitObserver menggantikan logika stok yang dulu di
        | controller: perubahan kondisi tiap unit sekarang punya jejak
        | audit sendiri (tersedia -> dipinjam -> tersedia/rusak).
        */

        Alat::observe(AlatObserver::class);
        AlatUnit::observe(AlatUnitObserver::class);
        Peminjaman::observe(PeminjamanObserver::class);
        Pengembalian::observe(PengembalianObserver::class);
    }
}
