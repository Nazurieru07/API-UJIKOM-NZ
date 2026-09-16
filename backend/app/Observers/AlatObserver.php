<?php

namespace App\Observers;

use App\Models\Alat;
use App\Models\LogAktivitas;
use Illuminate\Support\Facades\Auth;

class AlatObserver
{
    /**
     * Ketika alat berhasil dibuat.
     */
    public function created(Alat $alat): void
    {
        if (!Auth::check()) {
            return;
        }

        LogAktivitas::create([
            'user_id' => Auth::id(),
            'aktivitas' => "Menambahkan alat '{$alat->nama_alat}' dengan stok {$alat->stok}.",
        ]);
    }

    /**
     * Ketika data alat diperbarui.
     */
    public function updated(Alat $alat): void
    {
        if (!Auth::check()) {
            return;
        }

        $perubahan = [];

        if ($alat->isDirty('nama_alat')) {
            $perubahan[] = "nama alat menjadi '{$alat->nama_alat}'";
        }

        if ($alat->isDirty('stok')) {
            $perubahan[] = "stok menjadi {$alat->stok}";
        }

        if ($alat->isDirty('status_kondisi')) {
            $perubahan[] = "kondisi menjadi '{$alat->status_kondisi}'";
        }

        if ($alat->isDirty('deskripsi')) {
            $perubahan[] = "deskripsi diperbarui";
        }

        if ($alat->isDirty('kategori_id')) {
            $perubahan[] = "kategori diperbarui";
        }

        if (empty($perubahan)) {
            return;
        }

        LogAktivitas::create([
            'user_id' => Auth::id(),
            'aktivitas' => "Mengubah alat '{$alat->nama_alat}': " . implode(', ', $perubahan) . ".",
        ]);
    }

    /**
     * Ketika alat dihapus.
     */
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