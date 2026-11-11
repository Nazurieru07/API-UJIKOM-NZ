<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | Tabel Detail Pengembalian
    |--------------------------------------------------------------------------
    | Untuk mendukung pengembalian parsial (kembalikan sebagian alat saja).
    | Setiap baris merepresentasikan satu unit alat yang dikembalikan
    | dengan kondisinya masing-masing.
    |
    | Struktur:
    | - pengembalian_id: FK ke tabel pengembalian
    | - detail_pinjam_id: FK ke tabel detail_pinjam (unit yang dipinjam)
    | - kondisi_kembali: kondisi unit saat dikembalikan (Baik/Rusak)
    | - denda_kerusakan: denda untuk unit ini saja
    */
    public function up(): void
    {
        Schema::create('detail_pengembalian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengembalian_id')->constrained('pengembalian')->cascadeOnDelete();
            $table->foreignId('detail_pinjam_id')->constrained('detail_pinjam')->cascadeOnDelete();
            $table->enum('kondisi_kembali', ['Baik', 'Rusak'])->default('Baik');
            $table->integer('denda_kerusakan')->default(0);
            $table->timestamps();
            
            // Satu detail pinjam hanya bisa dikembalikan sekali
            $table->unique('detail_pinjam_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_pengembalian');
    }
};