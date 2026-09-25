<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permintaan_edit_peminjaman', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peminjaman_id')->constrained('peminjaman')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->date('tgl_kembali_plan_baru')->nullable();
            $table->text('alasan')->nullable();
            $table->enum('status', ['menunggu', 'disetujui', 'ditolak'])->default('menunggu');
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('catatan_penolakan')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('detail_permintaan_edit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permintaan_edit_id')->constrained('permintaan_edit_peminjaman')->onDelete('cascade');
            $table->foreignId('alat_id')->constrained('alat')->onDelete('cascade');
            $table->integer('jumlah')->default(1);
            $table->enum('aksi', ['tambah', 'hapus'])->default('tambah');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_permintaan_edit');
        Schema::dropIfExists('permintaan_edit_peminjaman');
    }
};
