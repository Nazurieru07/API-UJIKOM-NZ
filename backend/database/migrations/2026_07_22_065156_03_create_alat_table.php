<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('alat', function (Blueprint $table) {
        $table->id();
        $table->foreignId('kategori_id')->constrained('kategori')->cascadeOnDelete();
        $table->string('nama_alat');
        $table->integer('stok')->default(0);
        $table->string('status_kondisi');
        $table->text('deskripsi')->nullable();
        $table->string('gambar')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
