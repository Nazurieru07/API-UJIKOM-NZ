<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    |----------------------------------------------------------------------
    | Hapus sisa kolom lama di detail_permintaan_edit
    |----------------------------------------------------------------------
    | Migrasi 143000 seharusnya menjatuhkan alat_id + jumlah dan menambah
    | FK alat_unit_id -> alat_unit, tapi jalur up-nya dihentikan sebelum
    | blok terakhir (rollback parsial + migrate ulang), jadi live DB
    | sekarang punya KEDUA skema: alat_id NOT NULL + jumlah + alat_unit_id.
    |
    | Akibatnya: insert DetailPermintaanEdit tanpa alat_id langsung gagal
    | dengan NOT NULL violation -- setiap pengajuan edit peminjaman
    | (tambah/hapus unit) pasti error.
    |
    | Tabel saat ini 0 baris (permintaan_edit tercatat terakhir sebelum
    | migrasi serial), jadi rebuild bisa dilakukan tanpa kehilangan data.
    | Tetap lewat tabel perantara agar FK lama yang masih nempel bisa
    | dilepas bersih (MySQL tidak bisa dropForeign nama index yang sudah
    | tidak tercatat sebagai constraint aktif -- drop tabel lama
    | menyelesaikannya).
    */

    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        $ada = DB::table('detail_permintaan_edit')->count();

        if ($ada > 0) {
            throw new RuntimeException(
                "detail_permintaan_edit punya {$ada} baris. Rebuild tabel hanya "
                . 'aman saat tabel kosong; jalankan backfill manual dulu.'
            );
        }

        // Siapkan ulang tabel tanpa alat_id/jumlah, dengan FK ke alat_unit.
        // Tabel lama dijatuhkan supaya index + FK lama ikut hilang --
        // MySQL tidak bisa dropForeign nama index yang tidak terdaftar lagi.
        Schema::create('detail_permintaan_edit_baru', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permintaan_edit_id')
                ->constrained('permintaan_edit_peminjaman')
                ->cascadeOnDelete();
            $table->foreignId('alat_unit_id')
                ->nullable()
                ->constrained('alat_unit')
                ->restrictOnDelete();
            $table->enum('aksi', ['tambah', 'hapus'])->default('tambah');
            $table->timestamps();
        });

        Schema::dropIfExists('detail_permintaan_edit');
        Schema::rename('detail_permintaan_edit_baru', 'detail_permintaan_edit');
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('detail_permintaan_edit', function (Blueprint $table) {
            $table->dropConstrainedForeignId('alat_unit_id');
        });

        Schema::table('detail_permintaan_edit', function (Blueprint $table) {
            $table->foreignId('alat_id')
                ->nullable()
                ->constrained('alat')
                ->cascadeOnDelete()
                ->after('permintaan_edit_id');
            $table->integer('jumlah')->default(1)->after('alat_id');
        });
    }
};
