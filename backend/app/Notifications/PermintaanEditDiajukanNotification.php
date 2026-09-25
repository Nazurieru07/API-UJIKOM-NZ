<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use App\Models\PermintaanEditPeminjaman;

class PermintaanEditDiajukanNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public PermintaanEditPeminjaman $permintaanEdit
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'judul' => 'Permintaan Edit Peminjaman',
            'pesan' => $this->permintaanEdit->user->name
                . ' mengajukan edit peminjaman, menunggu persetujuan.',
            'permintaan_edit_id' => $this->permintaanEdit->id,
        ];
    }
}
