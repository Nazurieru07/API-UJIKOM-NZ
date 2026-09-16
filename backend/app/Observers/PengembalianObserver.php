<?php

namespace App\Observers;

use App\Models\Pengembalian;
use App\Models\LogAktivitas;
use Illuminate\Support\Facades\Auth;

class PengembalianObserver
{
    /**
     * Ketika pengembalian dibuat.
     */
    public function created(Pengembalian $pengembalian): void
    {
        if (!Auth::check()) {
            return;
        }

        $pengembalian->loadMissing(
            'peminjaman.user'
        );

        $namaUser =
            $pengembalian->peminjaman->user->name
            ?? 'User';

        $denda = $pengembalian->denda ?? 0;

        LogAktivitas::create([
            'user_id' => Auth::id(),
            'aktivitas' =>
                "Mencatat pengembalian '{$namaUser}' dengan kondisi '{$pengembalian->kondisi_kembali}' dan denda Rp" .
                number_format($denda, 0, ',', '.') . ".",
        ]);
    }

    /**
     * Ketika data pengembalian diperbarui.
     */
    public function updated(Pengembalian $pengembalian): void
    {
        if (!Auth::check()) {
            return;
        }

        $pengembalian->loadMissing(
            'peminjaman.user'
        );

        $namaUser =
            $pengembalian->peminjaman->user->name
            ?? 'User';

        LogAktivitas::create([
            'user_id' => Auth::id(),
            'aktivitas' =>
                "Mengubah data pengembalian '{$namaUser}'.",
        ]);
    }

    /**
     * Ketika data pengembalian dihapus.
     */
    public function deleted(Pengembalian $pengembalian): void
    {
        if (!Auth::check()) {
            return;
        }

        $pengembalian->loadMissing(
            'peminjaman.user'
        );

        $namaUser =
            $pengembalian->peminjaman->user->name
            ?? 'User';

        LogAktivitas::create([
            'user_id' => Auth::id(),
            'aktivitas' =>
                "Menghapus data pengembalian '{$namaUser}'.",
        ]);
    }
}