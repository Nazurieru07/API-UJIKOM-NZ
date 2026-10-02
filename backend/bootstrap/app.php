<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'user.aktif' => \App\Http\Middleware\CekUserAktif::class,
        ]);

        // Catat halaman web yang dibuka user ke session. Dipakai tombol
        // "Kembali" di halaman profil; lihat SimpanRiwayatHalaman.
        //
        // HARUS di grup 'web', bukan global: middleware global jalan
        // SEBELUM StartSession (yang ada di grup web), jadi session
        // belum dibuka dan session()->put() diam-diam tidak menyimpan
        // apa pun. Di grup web, posisi setelah StartSession.
        $middleware->appendToGroup('web', \App\Http\Middleware\SimpanRiwayatHalaman::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
