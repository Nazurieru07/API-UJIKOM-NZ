<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /*
    |----------------------------------------------------------------------
    | Normalisasi kondisi_kembali lama
    |----------------------------------------------------------------------
    | Sebelumnya pengembalian punya tiga kondisi: Baik / Rusak Ringan /
    | Rusak Berat. Sekarang kondisi unit cuma tiga nilai, dan untuk unit
    | tidak ada tingkat kerusakan -- unit yang tidak layak dipinjam
    | otomatis berstatus 'rusak'.
    |
    | Kolom pengembalian.kondisi_kembali (varchar, bukan enum) TIDAK diubah
    | strukturnya. Yang dinormalisasi supaya filter kondisi di laporan
    | memakai satu nilai saja ('Rusak'), bukan tiga.
    */

    public function up(): void
    {
        DB::table('pengembalian')
            ->whereIn('kondisi_kembali', ['Rusak Ringan', 'Rusak Berat'])
            ->update(['kondisi_kembali' => 'Rusak']);
    }

    public function down(): void
    {
        // Tidak ada pemetaan balik yang lossless: 'Rusak' lama bisa berarti
        // ringan atau berat. Biarkan 'Rusak' tetap 'Rusak'.
    }
};
