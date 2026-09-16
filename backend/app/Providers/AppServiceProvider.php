<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Models\Alat;
use App\Models\Peminjaman;
use App\Models\Pengembalian;

use App\Observers\AlatObserver;
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
        |--------------------------------------------------------------------------
        | Zona Waktu Aplikasi
        |--------------------------------------------------------------------------
        | Asia/Jakarta = WIB (UTC+7)
        */

        date_default_timezone_set('Asia/Jakarta');

        /*
        |--------------------------------------------------------------------------
        | Register Observer
        |--------------------------------------------------------------------------
        */

        Alat::observe(AlatObserver::class);
        Peminjaman::observe(PeminjamanObserver::class);
        Pengembalian::observe(PengembalianObserver::class);
    }
}