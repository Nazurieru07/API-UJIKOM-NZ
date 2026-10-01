<?php

namespace App\Console\Commands;

use App\Models\AlatUnit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LepaskanUnitYatim extends Command
{
    /**
     * Jalankan tiap jam bersama HitungPeminjamanTelat.
     *
     * Unit 'dipinjam' tanpa detail_pinjam mana pun adalah keadaan tidak
     * mungkin secara domain: kalau unit dipinjam, harus ada baris detail
     * yang menunjuknya. Kalau tidak ada, unit ini kehilangan pemegangnya
     * -- peminjamannya dihapus tanpa reset (bisa terjadi lewat rollback
     * migrasi, hapus manual lewat SQL, atau bug lama sebelum destroy
     * peminjaman ditulis ulang).
     *
     * Kalau dibiarkan, unit itu hilang selamanya dari sirkulasi: jumlah
     * tersedia lebih kecil dari jumlah fisik, dan tidak ada cara admin
     * bisa memperbaikinya dari UI (Kelola Unit menolak menyentuh unit
     * dipinjam -- dengan benar).
     *
     * Jalankan hanya untuk unit tanpa detail_pinjam sama sekali.
     * Unit yang masih punya detail TIDAK disentuh, sekalipun statusnya
     * 'yatim' dari sudut lain -- itu domain peminjaman, bukan command ini.
     */
    protected $signature = 'app:lepas-unit-yatim';

    protected $description = 'Reset unit dipinjam yang tidak punya detail peminjaman (unit yatim).';

    public function handle(): int
    {
        $yatim = AlatUnit::where('kondisi', 'dipinjam')
            ->whereNotIn('id', function ($query): void {
                // DetailPinjam bisa di-soft-delete? Tidak -- FK-nya
                // RESTRICT, jadi unit dengan detail aktif pasti tertangkap.
                $query->select('alat_unit_id')
                    ->from('detail_pinjam')
                    ->whereNotNull('alat_unit_id');
            })
            ->get();

        if ($yatim->isEmpty()) {
            $this->comment('Tidak ada unit yatim. Semua unit dipinjam punya detail.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($yatim): void {
            foreach ($yatim as $unit) {
                $unit->update(['kondisi' => 'tersedia']);
            }
        });

        $this->info("{$yatim->count()} unit yatim dikembalikan ke tersedia:");
        foreach ($yatim as $unit) {
            $this->line("  {$unit->serial_number}");
        }

        return self::SUCCESS;
    }
}
