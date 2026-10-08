<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\KategoriController;
use App\Http\Controllers\API\AlatController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\PeminjamanController;
use App\Http\Controllers\API\PengembalianController;
use App\Http\Controllers\API\LogAktivitasController;
use App\Http\Controllers\API\LaporanController;

// Public Routes (Tidak perlu token)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected Routes (Wajib membawa Bearer Token dari Sanctum)
Route::middleware(['auth:sanctum', 'user.aktif'])->group(function () {

    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::middleware('role:petugas,admin')->group(function () {
    // Route untuk hak akses petugas dan admin
    Route::post('/peminjaman/{peminjaman}/approve', [PeminjamanController::class, 'approve']);
    Route::post('/peminjaman', [PeminjamanController::class, 'store']);
    Route::get('/riwayat-pinjam', [PeminjamanController::class, 'riwayat']);

    // Pengembalian butuh persetujuan admin, jadi terbagi dua langkah:
    // petugas mengajukan di sini, admin memprosesnya di route approve
    // di bawah. Alur ini sama dengan versi web: tanpa persetujuan,
    // unit tidak kembali ke katalog dan denda tidak dihitung.
    Route::post('/pengembalian', [PengembalianController::class, 'store']);
});
    Route::middleware('role:admin,petugas')->group(function () {
    Route::get('/laporan-peminjaman', [LaporanController::class, 'index']);
    Route::get('/peminjaman', [PeminjamanController::class, 'index']);
    Route::get('/pengembalian', [PengembalianController::class, 'index']);
});

    // Hanya Admin
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('kategori', KategoriController::class);
        Route::apiResource('alat', AlatController::class);
        Route::get('/katalog', [AlatController::class, 'katalog']);
        Route::apiResource('users', UserController::class);
        Route::get('/peminjaman/{peminjaman}', [PeminjamanController::class, 'show']);        Route::put('/peminjaman/{peminjaman}', [PeminjamanController::class, 'update']);
        Route::delete('/peminjaman/{peminjaman}', [PeminjamanController::class, 'destroy']);
        Route::get('/pengembalian/{pengembalian}', [PengembalianController::class, 'show']);
        Route::put('/pengembalian/{pengembalian}', [PengembalianController::class, 'update']);
        Route::delete('/pengembalian/{pengembalian}', [PengembalianController::class, 'destroy']);

        // Persetujuan / penolakan pengembalian adalah hak Admin, seperti
        // menu admin/pengembalian di web.
        Route::post('/pengembalian/{pengembalian}/approve', [PengembalianController::class, 'approve']);
        Route::post('/pengembalian/{pengembalian}/reject', [PengembalianController::class, 'reject']);

        Route::get('/log-aktivitas', [LogAktivitasController::class, 'index']);
    });

    // Hanya Peminjam
    Route::middleware('role:admin,peminjam')->group(function () {
    Route::get('/katalog', [AlatController::class, 'katalog']);
    });
});
