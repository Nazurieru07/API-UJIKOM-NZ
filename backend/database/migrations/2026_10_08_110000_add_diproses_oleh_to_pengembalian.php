<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    | Peminjam memilih pengembalian diproses oleh siapa: 'admin' atau
    | 'petugas'. Menentukan pengajuan masuk ke antrean siapa, dan ke
    | laporan siapa setelah disetujui (petugas_id diisi oleh siapa
    | yang menyetujui).
    |
    | Default 'admin' untuk pengajuan lama (sebelum fitur ini) dan
    | untuk pengajuan yang dibuat petugas sendiri lewat menu lama.
    */
    public function up(): void
    {
        Schema::table('pengembalian', function (Blueprint $table) {
            $table->enum('diproses_oleh', ['admin', 'petugas'])
                ->default('admin')
                ->after('status_request');
        });
    }

    public function down(): void
    {
        Schema::table('pengembalian', function (Blueprint $table) {
            $table->dropColumn('diproses_oleh');
        });
    }
};
