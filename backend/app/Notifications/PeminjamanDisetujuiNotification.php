<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Peminjaman;

class PeminjamanDisetujuiNotification extends Notification
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
            'judul' => 'Peminjaman Disetujui',
            'pesan' => 'Peminjaman kamu telah disetujui oleh Petugas dan alat sekarang berstatus dipinjam.',
            'peminjaman_id' => $this->peminjaman->id,
        ];
    }
}