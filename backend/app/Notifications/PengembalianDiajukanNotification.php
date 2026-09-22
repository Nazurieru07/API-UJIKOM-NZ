<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use App\Models\Pengembalian;

class PengembalianDiajukanNotification extends Notification implements ShouldQueue
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
            'judul' => 'Pengajuan Pengembalian Baru',
            'pesan' => 'Petugas '
                . ($this->pengembalian->petugas->name ?? 'Petugas')
                . ' mengajukan pengembalian untuk diperiksa.',
            'pengembalian_id' => $this->pengembalian->id,
        ];
    }
}