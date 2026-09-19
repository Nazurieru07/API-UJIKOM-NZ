<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Pengembalian;

class PengembalianDisetujuiNotification extends Notification
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
            'judul' => 'Pengajuan Pengembalian Disetujui',
            'pesan' => 'Pengajuan pengembalian yang kamu ajukan telah disetujui oleh Admin.',
            'pengembalian_id' => $this->pengembalian->id,
        ];
    }
}