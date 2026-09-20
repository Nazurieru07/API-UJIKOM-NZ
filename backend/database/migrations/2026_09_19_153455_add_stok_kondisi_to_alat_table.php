<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('alat', function (Blueprint $table) {
            $table->integer('stok_baik')->default(0)->after('stok');
            $table->integer('stok_rusak')->default(0)->after('stok_baik');
            $table->integer('stok_rusak_parah')->default(0)->after('stok_rusak');
        });

        // Menyesuaikan data lama berdasarkan status_kondisi
        DB::table('alat')
            ->where('status_kondisi', 'Baik')
            ->update([
                'stok_baik' => DB::raw('stok'),
            ]);

        DB::table('alat')
            ->where('status_kondisi', 'Rusak')
            ->update([
                'stok_rusak' => DB::raw('stok'),
            ]);

        DB::table('alat')
            ->where('status_kondisi', 'Rusak Parah')
            ->update([
                'stok_rusak_parah' => DB::raw('stok'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alat', function (Blueprint $table) {
            $table->dropColumn([
                'stok_baik',
                'stok_rusak',
                'stok_rusak_parah',
            ]);
        });
    }
};