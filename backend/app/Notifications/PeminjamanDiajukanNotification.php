<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Peminjaman;

class PeminjamanDiajukanNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Peminjaman $peminjaman
    ) {
    }

    /**
     * Tentukan notifikasi dikirim melalui database.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Data yang disimpan ke tabel notifications.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'judul' => 'Pengajuan Peminjaman Baru',
            'pesan' => $this->peminjaman->user->name
                . ' mengajukan peminjaman alat dan menunggu persetujuan.',
            'peminjaman_id' => $this->peminjaman->id,
        ];
    }
}