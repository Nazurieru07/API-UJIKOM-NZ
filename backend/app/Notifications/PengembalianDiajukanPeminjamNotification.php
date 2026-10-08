<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use App\Models\Pengembalian;

/*
| Dikirim ketika PEMINJAM mengajukan pengembalian sendiri (bukan
| petugas). Beda dari PengembalianDiajukanNotification yang isinya
| petugas: di sini pelakunya peminjam dan barang belum diperiksa,
| jadi pesannya mengarah penerima untuk melakukan pemeriksaan.
*/
class PengembalianDiajukanPeminjamNotification extends Notification implements ShouldQueue
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
        // eager-load di sini, bukan di controller, supaya tidak
        // peduli dari mana notifikasi ini dibangun.
        $this->pengembalian->load('peminjaman.user');

        $peminjam = $this->pengembalian->peminjaman?->user?->name ?? 'Peminjam';

        return [
            'judul' => 'Pengajuan Pengembalian dari Peminjam',
            'pesan' => $peminjam
                . ' mengajukan pengembalian. Periksa kondisi barang dan tentukan denda kerusakan.',
            'pengembalian_id' => $this->pengembalian->id,
        ];
    }
}
