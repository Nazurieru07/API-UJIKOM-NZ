<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan identitas Admin/Petugas yang memproses
     * setiap unit pengembalian.
     *
     * Nullable agar riwayat lama tetap kompatibel.
     */
    public function up(): void
    {
        Schema::table('detail_pengembalian', function (Blueprint $table) {
            $table->foreignId('diproses_oleh_id')
                ->nullable()
                ->after('detail_pinjam_id')
                ->constrained('users');
        });
    }

    public function down(): void
    {
        Schema::table('detail_pengembalian', function (Blueprint $table) {
            $table->dropForeign(['diproses_oleh_id']);
            $table->dropColumn('diproses_oleh_id');
        });
    }
};
