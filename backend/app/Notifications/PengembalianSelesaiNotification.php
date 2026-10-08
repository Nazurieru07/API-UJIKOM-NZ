<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use App\Models\Pengembalian;

class PengembalianSelesaiNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Pengembalian $pengembalian
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        // Siapa yang menyetujui: pengembalian bisa diproses admin
        // atau petugas. Tanpa ini pesan selalu bilang "Admin" meski
        // yang sebenarnya menyetujui adalah petugas.
        $approver = $this->pengembalian->petugas;
        $namaApprover = $approver
            ? trim(explode(' ', $approver->name)[0])
            : 'Admin';

        $pesan = $approver
            ? 'Pengembalian alat yang kamu pinjam telah disetujui oleh Petugas '
                . $namaApprover
                . '. Peminjaman kamu sekarang berstatus dikembalikan.'
            : 'Pengembalian alat yang kamu pinjam telah disetujui oleh Admin. '
                . 'Peminjaman kamu sekarang berstatus dikembalikan.';

        return [
            'judul' => 'Pengembalian Disetujui',
            'pesan' => $pesan,
            'pengembalian_id' => $this->pengembalian->id,
        ];
    }
}