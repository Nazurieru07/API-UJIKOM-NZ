<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengembalian', function (Blueprint $table) {
            $table->enum('status_request', [
                'menunggu',
                'disetujui',
                'ditolak'
            ])
            ->default('menunggu')
            ->after('denda_kerusakan');
        });

        // Data pengembalian lama dianggap sudah disetujui
        DB::table('pengembalian')->update([
            'status_request' => 'disetujui'
        ]);
    }

    public function down(): void
    {
        Schema::table('pengembalian', function (Blueprint $table) {
            $table->dropColumn('status_request');
        });
    }
};