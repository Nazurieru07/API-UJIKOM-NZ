<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    |----------------------------------------------------------------------
    | detail_permintaan_edit: alat_id + jumlah -> alat_unit_id
    |----------------------------------------------------------------------
    | Pengajuan edit peminjaman juga harus menyebut unit serial spesifik.
    | Aksi 'tambah' = menunjukkan unit mana yang mau ditambah;
    | aksi 'hapus' = unit mana yang mau dilepas dari peminjaman.
    |
    | Kolom lama di-drop di sini: 1 baris = 1 unit serial, jadi alat_id
    | bisa ditelusuri lewat $detail->alatUnit->alat. Kalau kolomnya
    | dipertahankan, ia harus terus diisi manual (tidak ada lagi di
    | request) padahal NOT NULL -- semua penyimpanan baru akan gagal.
    | Live DB hanya punya 1 baris permintaan edit (sudah diproses),
    | jadi drop tidak menghilangkan data yang masih dipakai.
    |
    | Kalau tidak di-drop, insert dari PeminjamController::ajukanEditPeminjaman
    | gagal dengan NOT NULL constraint violation pada alat_id.
    */

    public function up(): void
    {
        // 1. Tambah kolom baru dulu (backfill butuh kolom ini ada).
        Schema::table('detail_permintaan_edit', function (Blueprint $table) {
            $table->foreignId('alat_unit_id')->nullable()->after('permintaan_edit_id');
        });

        // 2. Backfill: petakan ke unit tersedia pertama untuk alat yang sama.
        $terpakai = [];

        $rows = DB::table('detail_permintaan_edit')
            ->orderBy('id')
            ->get(['id', 'alat_id']);

        foreach ($rows as $row) {
            $unitId = DB::table('alat_unit')
                ->where('alat_id', $row->alat_id)
                ->where('kondisi', 'tersedia')
                ->orderBy('serial_number')
                ->pluck('id')
                ->first(fn ($id) => ! in_array($id, $terpakai, true));

            if ($unitId !== null) {
                DB::table('detail_permintaan_edit')
                    ->where('id', $row->id)
                    ->update(['alat_unit_id' => $unitId]);

                $terpakai[] = $unitId;
            }
        }

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            // SQLite: dropColumn pada tabel ber-FK harus lewat tabel baru.
            Schema::create('detail_permintaan_edit_new', function (Blueprint $table) {
                $table->id();
                $table->foreignId('permintaan_edit_id')->constrained('permintaan_edit_peminjaman')->onDelete('cascade');
                $table->foreignId('alat_unit_id')->nullable();
                $table->enum('aksi', ['tambah', 'hapus'])->default('tambah');
                $table->timestamps();
            });

            DB::statement(
                'INSERT INTO detail_permintaan_edit_new (id, permintaan_edit_id, alat_unit_id, aksi, created_at, updated_at)
                 SELECT id, permintaan_edit_id, alat_unit_id, aksi, created_at, updated_at FROM detail_permintaan_edit'
            );

            Schema::dropIfExists('detail_permintaan_edit');
            Schema::rename('detail_permintaan_edit_new', 'detail_permintaan_edit');

            return;
        }

        Schema::table('detail_permintaan_edit', function (Blueprint $table) {
            $table->dropForeign('detail_permintaan_edit_alat_id_foreign');
            $table->dropColumn(['alat_id', 'jumlah']);
            $table->foreign('alat_unit_id')
                ->references('id')
                ->on('alat_unit')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            // Tidak ada jalur down untuk SQLite tanpa membuat ulang tabel;
            // data lama (alat_id, jumlah) sudah hilang. Rollback SQLite
            // untuk migrasi ini tidak didukung.
            return;
        }

        Schema::table('detail_permintaan_edit', function (Blueprint $table) {
            $table->dropForeign('detail_permintaan_edit_alat_unit_id_foreign');
            $table->dropColumn('alat_unit_id');
            $table->foreignId('alat_id')->constrained('alat')->onDelete('cascade');
            $table->integer('jumlah')->default(1);
        });
    }
};
