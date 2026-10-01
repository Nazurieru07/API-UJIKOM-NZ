<?php

namespace App\Observers;

use App\Models\AlatUnit;
use App\Models\LogAktivitas;
use Illuminate\Support\Facades\Auth;

class AlatUnitObserver
{
    /*
    |----------------------------------------------------------------------
    | Log perubahan kondisi unit
    |----------------------------------------------------------------------
    | Menggantikan bagian stok dari observer lama. Sebelumnya perubahan
    | jumlah barang tidak tercatat di sini (hanya di controller);
    | sekarang setiap transisi kondisi unit (tersedia -> dipinjam ->
    | tersedia/rusak) punya jejak audit di log_aktivitas, karena status
    | unit adalah data yang dilihat petugas, bukan angka agregat.
    |
    | Semua transition DICATAT -- termasuk yang dipicu employee sistem
    | (setujuiPeminjaman, proses pengembalian). Auth::check() hanya
    | membatasi supaya seeder/artisan tanpa user login tidak menulis
    | baris log dengan user_id NULL.
    */

    public function created(AlatUnit $unit): void
    {
        if (!Auth::check()) {
            return;
        }

        $unit->loadMissing('alat');

        LogAktivitas::create([
            'user_id' => Auth::id(),
            'aktivitas' => "Menambah unit '{$unit->serial_number}' "
                . "({$unit->alat->nama_alat}) dengan kondisi {$unit->kondisi}.",
        ]);
    }

    public function updated(AlatUnit $unit): void
    {
        if (!Auth::check()) {
            return;
        }

        if (! $unit->isDirty('kondisi')) {
            return;
        }

        $unit->loadMissing('alat');

        LogAktivitas::create([
            'user_id' => Auth::id(),
            'aktivitas' => "Unit '{$unit->serial_number}' "
                . "({$unit->alat->nama_alat}) kondisi "
                . "{$unit->getOriginal('kondisi')} -> {$unit->kondisi}.",
        ]);
    }

    public function deleted(AlatUnit $unit): void
    {
        if (!Auth::check()) {
            return;
        }

        $unit->loadMissing('alat');

        LogAktivitas::create([
            'user_id' => Auth::id(),
            'aktivitas' => "Menghapus unit '{$unit->serial_number}' "
                . "({$unit->alat->nama_alat}).",
        ]);
    }
}
