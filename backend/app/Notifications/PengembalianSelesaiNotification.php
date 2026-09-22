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
        return [
            'judul' => 'Pengembalian Disetujui',
            'pesan' => 'Pengembalian alat yang kamu pinjam telah disetujui oleh Admin. Peminjaman kamu sekarang berstatus dikembalikan.',
            'pengembalian_id' => $this->pengembalian->id,
        ];
    }
}