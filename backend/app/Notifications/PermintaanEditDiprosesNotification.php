<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use App\Models\PermintaanEditPeminjaman;

class PermintaanEditDiprosesNotification extends Notification implements ShouldQueue
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
        $disetujui = $this->permintaanEdit->status === 'disetujui';

        return [
            'judul' => $disetujui
                ? 'Permintaan Edit Disetujui'
                : 'Permintaan Edit Ditolak',
            'pesan' => $disetujui
                ? 'Permintaan edit peminjaman kamu telah disetujui.'
                : 'Permintaan edit peminjaman kamu ditolak'
                    . ($this->permintaanEdit->catatan_penolakan
                        ? ': ' . $this->permintaanEdit->catatan_penolakan
                        : '.'),
            'permintaan_edit_id' => $this->permintaanEdit->id,
        ];
    }
}
