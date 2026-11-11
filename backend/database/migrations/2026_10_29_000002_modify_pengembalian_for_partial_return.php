<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | Modifikasi Tabel Pengembalian untuk Pengembalian Parsial
    |--------------------------------------------------------------------------
    | Perubahan:
    | 1. kondisi_kembali menjadi nullable (karena sekarang di detail_pengembalian)
    | 2. denda_kerusakan menjadi nullable (karena sekarang di detail_pengembalian)
    | 3. Tambah kolom is_parsial untuk menandai pengembalian parsial
    |
    | Catatan: Data lama tetap kompatibel karena nullable
    */
    public function up(): void
    {
        Schema::table('pengembalian', function (Blueprint $table) {
            // Jadikan nullable karena sekarang kondisi ada di detail
            $table->string('kondisi_kembali')->nullable()->change();
            $table->integer('denda_kerusakan')->nullable()->change();
            
            // Tambah kolom untuk menandai pengembalian parsial
            $table->boolean('is_parsial')->default(false)->after('denda_kerusakan');
            
            // Tambah kolom untuk jumlah unit yang sudah dikembalikan
            $table->integer('jumlah_unit_dikembalikan')->default(0)->after('is_parsial');
        });
    }

    public function down(): void
    {
        Schema::table('pengembalian', function (Blueprint $table) {
            $table->string('kondisi_kembali')->nullable(false)->change();
            $table->integer('denda_kerusakan')->nullable(false)->change();
            $table->dropColumn(['is_parsial', 'jumlah_unit_dikembalikan']);
        });
    }
};