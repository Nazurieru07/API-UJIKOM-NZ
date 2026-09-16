<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\PetugasController;
use App\Http\Controllers\PeminjamController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\DB;

Route::get('/', function () {
    return view('welcome');
});

// admin
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');

    //ROUTE KATEGORI
    Route::get('/kategori', [AdminController::class, 'indexKategori'])->name('kategori.index');
    Route::get('/kategori/create', [AdminController::class, 'createKategori'])->name('kategori.create');
    Route::post('/kategori', [AdminController::class, 'storeKategori'])->name('kategori.store');
    Route::get('/kategori/{id}/edit', [AdminController::class, 'editKategori'])->name('kategori.edit');
    Route::put('/kategori/{id}', [AdminController::class, 'updateKategori'])->name('kategori.update');
    Route::delete('/kategori/{id}', [AdminController::class, 'destroyKategori'])->name('kategori.destroy');

   // CRUD Pengembalian
Route::get('/pengembalian', [AdminController::class, 'indexPengembalian'])
    ->name('pengembalian.index');

Route::get('/pengembalian/create', [AdminController::class, 'createPengembalian'])
    ->name('pengembalian.create');

Route::post('/pengembalian', [AdminController::class, 'storePengembalian'])
    ->name('pengembalian.store');

Route::delete('/pengembalian/{id}', [AdminController::class, 'destroyPengembalian'])
    ->name('pengembalian.destroy');

Route::get('/pengembalian/{id}/edit', [AdminController::class, 'editPengembalian'])
    ->name('pengembalian.edit');

Route::post('/pengembalian/{id}/setujui', [AdminController::class, 'setujuiPengembalian'])
    ->name('pengembalian.setujui');

    Route::post('/pengembalian/{id}/tolak', [AdminController::class, 'tolakPengembalian'])
    ->name('pengembalian.reject');

    //CRUD Peminjaman
    Route::get('/peminjaman', [AdminController::class, 'indexPeminjaman'])->name('peminjaman.index');
    Route::get('/peminjaman/create', [AdminController::class, 'createPeminjaman'])->name('peminjaman.create');
    Route::post('/peminjaman', [AdminController::class, 'storePeminjaman'])->name('peminjaman.store');

    // Search User & Alat untuk Form Peminjaman
Route::get('/search/users', [AdminController::class, 'searchUser'])
    ->name('search.users');

Route::get('/search/alats', [AdminController::class, 'searchAlat'])
    ->name('search.alats');

    Route::put('/peminjaman/{id}/status', [AdminController::class, 'updateStatusPeminjaman'])->name('peminjaman.updateStatus');
    Route::delete('/peminjaman/{id}', [AdminController::class, 'destroyPeminjaman'])->name('peminjaman.destroy');

    // CRUD Alat
    Route::get('/alat', [AdminController::class, 'indexAlat'])->name('alat.index');
    Route::post('/alat', [AdminController::class, 'storeAlat'])->name('alat.store');
    Route::get('/alat/create', [AdminController::class, 'createAlat'])->name('alat.create');
    Route::get('/alat/{id}/edit', [AdminController::class, 'editAlat'])->name('alat.edit');
    Route::put('/alat/{id}', [AdminController::class, 'updateAlat'])->name('alat.update');
    Route::delete('/alat/{id}', [AdminController::class, 'destroyAlat'])->name('alat.destroy');

    // CRUD User
    Route::get('/users', [AdminController::class, 'indexUser'])->name('user.index');
    Route::get('/users/create', [AdminController::class, 'createUser'])->name('user.create');
    Route::post('/users', [AdminController::class, 'storeUser'])->name('user.store');
    Route::get('/users/{id}/edit', [AdminController::class, 'editUser'])->name('user.edit');
    Route::put('/users/{id}', [AdminController::class, 'updateUser'])->name('user.update');
    Route::delete('/users/{id}', [AdminController::class, 'destroyUser'])->name('user.destroy');

});

// petugas
Route::middleware(['auth', 'role:petugas'])->prefix('petugas')->name('petugas.')->group(function () {
    
    Route::get('/peminjaman', [PetugasController::class, 'indexPeminjaman'])
        ->name('peminjaman.index');

    Route::post('/peminjaman/{id}/setujui', [PetugasController::class, 'setujuiPeminjaman'])
        ->name('peminjaman.setujui');

    Route::post('/peminjaman/{id}/tolak', [PetugasController::class, 'tolakPeminjaman'])
        ->name('peminjaman.tolak');


    Route::get('/pengembalian', [PetugasController::class, 'indexPengembalian'])
        ->name('pengembalian.index');

    Route::post('/pengembalian/{id}/ajukan', [PetugasController::class, 'ajukanPengembalian'])
    ->name('pengembalian.ajukan');


    Route::get('/laporan', [PetugasController::class, 'indexLaporan'])
        ->name('laporan.index');

    // ROUTE PDF
    Route::get('/laporan/pdf', [PetugasController::class, 'cetakLaporan'])
        ->name('laporan.pdf');
});

// peminjam
Route::middleware(['auth', 'role:peminjam'])->prefix('peminjam')->name('peminjam.')->group(function () {

    // Katalog
    Route::get('/katalog', [PeminjamController::class, 'katalogAlat'])
        ->name('katalog');

    Route::post('/peminjaman/ajukan', [PeminjamController::class, 'ajukanPeminjaman'])
        ->name('peminjaman.ajukan');

    Route::get('/riwayat', [PeminjamController::class, 'riwayatPeminjaman'])
        ->name('riwayat');
});

// Route Tamu (Belum Login)
Route::middleware('guest')->group(function () {

    Route::get('/login', [AuthController::class, 'showLoginForm'])
        ->name('login');

    Route::post('/login', [AuthController::class, 'login']);
});

// Route Logout (Harus sudah login)
Route::post('/logout', [AuthController::class, 'logout'])
    ->name('logout')
    ->middleware('auth');

Route::get('/debug-db', function () {
    return response()->json([
        'database' => DB::selectOne("SELECT DATABASE() AS db")->db,
        'hostname' => DB::selectOne("SELECT @@hostname AS host")->host,
        'columns' => DB::select("SHOW COLUMNS FROM pengembalian"),
    ]);
});

Route::get('/who-am-i', function () {
    return response()->json([
        'php' => PHP_VERSION,
        'hostname' => gethostname(),
        'time' => now()->toDateTimeString(),
    ]);
});