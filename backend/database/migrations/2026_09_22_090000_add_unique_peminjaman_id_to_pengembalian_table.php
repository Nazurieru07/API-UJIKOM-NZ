<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Satu peminjaman hanya boleh memiliki satu data pengembalian.
     *
     * Mencegah duplikasi pengajuan pengembalian akibat race condition
     * antara Petugas dan Admin.
     */
    public function up(): void
    {
        Schema::table('pengembalian', function (Blueprint $table) {
            $table->unique('peminjaman_id');
        });
    }

    public function down(): void
    {
        Schema::table('pengembalian', function (Blueprint $table) {
            $table->dropUnique(['peminjaman_id']);
        });
    }
};
