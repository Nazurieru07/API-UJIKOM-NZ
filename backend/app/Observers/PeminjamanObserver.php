<?php

namespace App\Observers;

use App\Models\Peminjaman;
use App\Models\LogAktivitas;
use Illuminate\Support\Facades\Auth;

class PeminjamanObserver
{
    /**
     * Ketika peminjaman dibuat.
     */
    public function created(Peminjaman $peminjaman): void
    {
        if (!Auth::check()) {
            return;
        }

        $peminjaman->loadMissing('user');

        $namaUser = $peminjaman->user->name ?? 'User';

        LogAktivitas::create([
            'user_id' => Auth::id(),
            'aktivitas' => "Membuat peminjaman untuk '{$namaUser}'.",
        ]);
    }

    /**
     * Ketika peminjaman diperbarui.
     *
     * PENTING: observer hanya terpanggil jika model disimpan per
     * instance ($peminjaman->save() / ->update()). Mass update lewat
     * query builder -- Peminjaman::where(...)->update() -- MELEWATI
     * observer, jadi log perubahan status tidak tercatat.
     * Itu sebabnya pengecekan status telat dipindah ke
     * app/Console/Commands/HitungPeminjamanTelat.php.
     */
    public function updated(Peminjaman $peminjaman): void
    {
        if (!Auth::check()) {
            return;
        }

        $peminjaman->loadMissing('user');

        $namaUser = $peminjaman->user->name ?? 'User';

        // isDirty('status') = status sebelum vs sesudah berbeda.
        // getOriginal = nilai lama dari database, bukan nilai baru.
        if ($peminjaman->isDirty('status')) {

            $statusLama = $peminjaman->getOriginal('status');
            $statusBaru = $peminjaman->status;

            LogAktivitas::create([
                'user_id' => Auth::id(),
                'aktivitas' =>
                    "Mengubah status peminjaman '{$namaUser}' dari '{$statusLama}' menjadi '{$statusBaru}'.",
            ]);

            return;
        }

        // Jika tanggal peminjaman berubah
        if (
            $peminjaman->isDirty('tgl_pinjam') ||
            $peminjaman->isDirty('tgl_kembali_plan')
        ) {
            LogAktivitas::create([
                'user_id' => Auth::id(),
                'aktivitas' =>
                    "Mengubah data tanggal peminjaman '{$namaUser}'.",
            ]);
        }
    }

    /**
     * Ketika peminjaman dihapus.
     */
    public function deleted(Peminjaman $peminjaman): void
    {
        if (!Auth::check()) {
            return;
        }

        $peminjaman->loadMissing('user');

        $namaUser = $peminjaman->user->name ?? 'User';

        LogAktivitas::create([
            'user_id' => Auth::id(),
            'aktivitas' => "Menghapus data peminjaman '{$namaUser}'.",
        ]);
    }
}