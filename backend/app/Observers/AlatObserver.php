<?php

namespace App\Observers;

use App\Models\Alat;
use App\Models\LogAktivitas;
use Illuminate\Support\Facades\Auth;

class AlatObserver
{
    /*
    |----------------------------------------------------------------------
    | Log aktivitas perubahan alat
    |----------------------------------------------------------------------
    | SEBELUMNYA observer ini juga mencatat perubahan stok agregat
    | (stok, stok_baik, stok_rusak, stok_rusak_parah, status_kondisi).
    | Itu semua sudah tidak ada: kondisi sekarang per unit di alat_unit.
    | Pengurangan/pengembalian unit ditangani AlatUnitObserver.
    |
    | Log hanya dibuat jika ada user login. Seeder dan command artisan
    | yang dijalankan tanpa session (Auth::check() false) dilewati,
    | karena log_aktivitas.user_id tidak boleh NULL.
    */

    public function created(Alat $alat): void
    {
        if (!Auth::check()) {
            return;
        }

        LogAktivitas::create([
            'user_id' => Auth::id(),
            'aktivitas' => "Menambahkan alat '{$alat->nama_alat}' (kode {$alat->kode_alat}).",
        ]);
    }

    public function updated(Alat $alat): void
    {
        if (!Auth::check()) {
            return;
        }

        // Hanya tulis log kalau ada perubahan nyata, bukan setiap save().
        $perubahan = [];

        if ($alat->isDirty('nama_alat')) {
            $perubahan[] = "nama alat menjadi '{$alat->nama_alat}'";
        }

        if ($alat->isDirty('kode_alat')) {
            $perubahan[] = "kode alat menjadi '{$alat->kode_alat}'";
        }

        if ($alat->isDirty('deskripsi')) {
            $perubahan[] = "deskripsi diperbarui";
        }

        if ($alat->isDirty('kategori_id')) {
            $perubahan[] = "kategori diperbarui";
        }

        if ($alat->isDirty('is_arsip')) {
            $perubahan[] = $alat->is_arsip
                ? 'alat diarsipkan'
                : 'alat tidak lagi diarsipkan';
        }

        if (empty($perubahan)) {
            return;
        }

        LogAktivitas::create([
            'user_id' => Auth::id(),
            'aktivitas' => "Mengubah alat '{$alat->nama_alat}': " . implode(', ', $perubahan) . ".",
        ]);
    }

    public function deleted(Alat $alat): void
    {
        if (!Auth::check()) {
            return;
        }

        LogAktivitas::create([
            'user_id' => Auth::id(),
            'aktivitas' => "Menghapus alat '{$alat->nama_alat}'.",
        ]);
    }
}
