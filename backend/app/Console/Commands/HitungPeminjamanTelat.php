<?php

namespace App\Console\Commands;

use App\Models\Peminjaman;
use Illuminate\Console\Command;

/**
 * Tandai peminjaman yang sudah lewat tanggal kembali rencana.
 *
 * Kenapa command, bukan query di indexPeminjaman:
 * - Mass update via query builder TIDAK memanggil model observer,
 *   sehingga log aktivitas "dipinjam -> telat" tidak pernah tercatat.
 * - Menjalankan ini saat setiap GET halaman juga bikin request pengunjung
 *   melakukan write ke database (side-effect di endpoint read-only).
 *
 * Command ini iterate per instance lalu save(), jadi PeminjamanObserver
 * tetap mencatat setiap perubahan status.
 */
class HitungPeminjamanTelat extends Command
{
    protected $signature = 'app:hitung-peminjaman-telat';

    protected $description = 'Tandai peminjaman yang lewat tanggal kembali rencana sebagai telat';

    public function handle(): int
    {
        $hariIni = now()->toDateString();

        $peminjamans = Peminjaman::where('status', 'dipinjam')
            ->whereDate('tgl_kembali_plan', '<', $hariIni)
            ->get();

        if ($peminjamans->isEmpty()) {
            $this->info('Tidak ada peminjaman yang terlambat.');

            return self::SUCCESS;
        }

        $jumlah = 0;

        // Simpan per instance, bukan mass update,
        // agar PeminjamanObserver::updated terpanggil dan log aktivitas terisi.
        foreach ($peminjamans as $peminjaman) {
            $peminjaman->update(['status' => 'telat']);
            $jumlah++;
        }

        $this->info("{$jumlah} peminjaman ditandai sebagai telat.");

        return self::SUCCESS;
    }
}
