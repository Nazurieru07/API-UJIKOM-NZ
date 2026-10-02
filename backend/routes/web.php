<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\PetugasController;
use App\Http\Controllers\PeminjamController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;

Route::get('/', function () {
    return view('welcome');
});

/*
|==========================================================================
| ROUTE ADMIN
|==========================================================================
| Middleware 'user.aktif' = cek server-side: user harus login DAN
| role-nya admin. Tanpa ini, siapapun bisa masuk menu admin dengan
| mengetik URL langsung. Middleware ini di-alias di bootstrap/app.php.
*/
Route::middleware(['auth', 'user.aktif', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');

    // ROUTE LAPORAN (admin melihat semua data pengembalian)
    Route::get('/laporan', [AdminController::class, 'indexLaporan'])
        ->name('laporan.index');

    Route::get('/laporan/excel', [AdminController::class, 'cetakLaporanExcel'])
        ->name('laporan.excel');

    Route::get('/laporan/pdf', [AdminController::class, 'cetakLaporanPdf'])
        ->name('laporan.pdf');

    // ROUTE KATEGORI

Route::get('/kategori', [AdminController::class, 'indexKategori'])->name('kategori.index');

Route::get('/kategori/create', [AdminController::class, 'createKategori'])->name('kategori.create');

Route::post('/kategori', [AdminController::class, 'storeKategori'])->name('kategori.store');

Route::get('/kategori/{id}/edit', [AdminController::class, 'editKategori'])->name('kategori.edit');

Route::put('/kategori/{id}', [AdminController::class, 'updateKategori'])->name('kategori.update');

Route::delete('/kategori/{id}', [AdminController::class, 'destroyKategori'])->name('kategori.destroy');

Route::get('/kategori/{id}', [AdminController::class, 'showKategori'])->name('kategori.show');


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

    // LOG_AKTIVITAS: hanya baca, tidak ada route create/update/delete
    // (log diisi otomatis oleh observer, tidak boleh diedit manual).
    Route::get('/log-aktivitas', [AdminController::class, 'indexLogAktivitas'])
    ->name('log_aktivitas.index');

    //CRUD Peminjaman
    Route::get('/peminjaman', [AdminController::class, 'indexPeminjaman'])->name('peminjaman.index');
    Route::get('/peminjaman/create', [AdminController::class, 'createPeminjaman'])->name('peminjaman.create');
    Route::post('/peminjaman', [AdminController::class, 'storePeminjaman'])->name('peminjaman.store');
    // Form pengembalian langsung oleh Admin
    Route::get('/peminjaman/{id}/pengembalian', [AdminController::class, 'formPengembalianAdmin'])
        ->name('peminjaman.pengembalian.form');

    // Proses pengajuan pengembalian oleh Admin
    Route::post('/peminjaman/{id}/pengembalian', [AdminController::class, 'ajukanPengembalianAdmin'])
        ->name('peminjaman.pengembalian.ajukan');

        
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
Route::post('/alat/{id}/restore', [AdminController::class, 'restoreAlat'])->name('alat.restore');
    Route::post('/alat/{id}/perbaiki', [AdminController::class, 'perbaikiAlat'])
    ->name('alat.perbaiki');

    Route::post('/alat/{id}/ubah-kondisi', [AdminController::class, 'ubahKondisiAlat'])
    ->name('alat.ubahKondisi');

    // CRUD User
    Route::get('/users', [AdminController::class, 'indexUser'])->name('user.index');
    Route::get('/users/create', [AdminController::class, 'createUser'])->name('user.create');
    Route::post('/users', [AdminController::class, 'storeUser'])->name('user.store');
    Route::get('/users/{id}/edit', [AdminController::class, 'editUser'])->name('user.edit');
    Route::put('/users/{id}', [AdminController::class, 'updateUser'])->name('user.update');
    Route::delete('/users/{id}', [AdminController::class, 'destroyUser'])->name('user.destroy');
    Route::post('/users/{id}/toggle-aktif', [AdminController::class, 'toggleUserAktif'])->name('user.toggle-aktif');

    // Route unit serial alat (menu "Kelola Unit" di daftar alat).
    // Nama route ini dipakai view admin/alat/index.blade.php lewat
    // Route::has() -- jangan diubah tanpa update view-nya.
    Route::post('/alat/{id}/unit', [AdminController::class, 'storeUnit'])->name('alat.unit.store');
    Route::post('/alat/{id}/unit/rusak', [AdminController::class, 'tandaiRusak'])->name('alat.unit.tandaiRusak');
    Route::post('/alat/{id}/unit/perbaiki', [AdminController::class, 'perbaiki'])->name('alat.unit.perbaiki');
    Route::delete('/alat/{id}/unit/{unitId}', [AdminController::class, 'hapusUnit'])->name('alat.unit.hapus');

});

// petugas
Route::middleware(['auth', 'user.aktif', 'role:petugas'])->prefix('petugas')->name('petugas.')->group(function () {
    
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

    // ROUTE EXCEL
    Route::get('/laporan/excel', [PetugasController::class, 'cetakLaporanExcel'])
        ->name('laporan.excel');
    Route::get('/edit-peminjaman', [PetugasController::class, 'indexEditPeminjaman'])
        ->name('edit-peminjaman.index');
    Route::post('/edit-peminjaman/{id}/setujui', [PetugasController::class, 'setujuiEditPeminjaman'])
        ->name('edit-peminjaman.setujui');
    Route::post('/edit-peminjaman/{id}/tolak', [PetugasController::class, 'tolakEditPeminjaman'])
        ->name('edit-peminjaman.tolak');

});

// peminjam
Route::middleware(['auth', 'user.aktif', 'role:peminjam'])->prefix('peminjam')->name('peminjam.')->group(function () {

    // Katalog
    Route::get('/katalog', [PeminjamController::class, 'katalogAlat'])
        ->name('katalog');

    // Sisa unit serial sebuah alat (dipakai tombol "+N unit lainnya"
    // di kartu katalog supaya kartu tidak memanjang).
    Route::get('/alat/{id}/unit', [PeminjamController::class, 'unitAlatJson'])
        ->name('unit.alat');

    Route::post('/peminjaman/ajukan', [PeminjamController::class, 'ajukanPeminjaman'])
        ->name('peminjaman.ajukan');

    Route::get('/riwayat', [PeminjamController::class, 'riwayatPeminjaman'])
        ->name('riwayat');
    Route::get('/peminjaman/{id}/edit', [PeminjamController::class, 'formEditPeminjaman'])
        ->name('edit.form');

    Route::post('/peminjaman/{id}/edit', [PeminjamController::class, 'ajukanEditPeminjaman'])
        ->name('edit.ajukan');

});

/*
|==========================================================================
| ROUTE PROFIL SAYANG
|==========================================================================
| Setiap user (admin, petugas, peminjam) mengelola data dan foto
| profilnya sendiri. Perubahan menulis ke record `users` yang sama, jadi
| menu "Kelola User" admin langsung memperlihatkan data terbaru.
*/
Route::middleware(['auth', 'user.aktif'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
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
    ->middleware(['auth', 'user.aktif']);



Route::post('/notifications/read-all', function () {
    auth()->user()->unreadNotifications->markAsRead();

    return response()->json([
        'success' => true
    ]);
})->name('notifications.readAll')->middleware(['auth', 'user.aktif']);