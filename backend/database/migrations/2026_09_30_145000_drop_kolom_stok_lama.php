<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    |----------------------------------------------------------------------
    | Drop kolom stok lama
    |----------------------------------------------------------------------
    | Dijalankan TERAKHIR dalam rangkaian migrasi serial, setelah:
    | - alat_unit terisi 93 unit dari stok lama (93 = 93, verifikasi)
    | - detail_pinjam.alat_unit_id terisi 27 dari 27 baris (0 orphan)
    |
    | Kolom yang di-drop:
    | - alat.stok, alat.stok_baik, alat.stok_rusak, alat.stok_rusak_parah,
    |   alat.status_kondisi : kondisi kini per unit, bukan agregat per alat
    | - detail_pinjam.alat_id, detail_pinjam.jumlah : 1 baris = 1 unit serial
    |
    | Kenapa MySQL dan SQLite butuh jalur berbeda:
    |
    | MySQL bisa drop kolom biasa. Tapi detail_pinjam.alat_id punya foreign
    | key, dan MySQL menolak drop kolom yang jadi target FK -- FK itu harus
    | dilepas lebih dulu (dropForeign + dropIndex), baru kolomnya.
    |
    | SQLite tidak punya perintah ALTER untuk menghapus FK, dan reorder
    | kolom foreign key saat drop column gagal dengan:
    |   "error in table detail_pinjam after drop column: unknown column
    |    alat_id in foreign key definition"
    | Jadi tabel harus dibangun ulang (create new, copy, drop old, rename).
    |
    | TIDAK ada data yang hilang -- semua nilai stok lama sudah menjadi
    | baris alat_unit dan setiap detail_pinjam lama sudah punya unit.
    | Backup penuh: _serial_backup/pre_serial_dump.sql (teruji restore).
    */

    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            $this->dropKolomSqlite();

            return;
        }

        /*
        | Lepas FK + index dulu: MySQL tidak bisa drop kolom yang jadi
        | target foreign key.
        |
        | Cek dulu apakah constraint-nya ada: migrasi ini pernah dirollback
        | lalu dijalankan ulang, dan rollback menambahkan kembali kolom
        | alat_id tanpa mengembalikan FK lamanya -- dropForeign langsung
        | akan melempar "cannot drop index" di kondisi itu.
        */
        $adaConstraint = (bool) DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'detail_pinjam')
            ->where('CONSTRAINT_NAME', 'detail_pinjam_alat_id_foreign')
            ->exists();

        $adaIndex = (bool) DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'detail_pinjam')
            ->where('INDEX_NAME', 'detail_pinjam_alat_id_foreign')
            ->exists();

        Schema::table('detail_pinjam', function (Blueprint $table) use ($adaConstraint, $adaIndex) {
            if ($adaConstraint) {
                $table->dropForeign('detail_pinjam_alat_id_foreign');
            }

            if ($adaIndex) {
                $table->dropIndex('detail_pinjam_alat_id_foreign');
            }
        });

        /*
        | Kolom alat_id + jumlah mungkin sudah tidak ada: migrasi ini pernah
        | jalan sebagian lalu di-rollback, dan rollback hanya mengembalikan
        | definisi kolom tanpa mengembalikan isinya (run pertama sudah
        | menjatuhkannya). Jadi cek dulu sebelum drop.
        */
        $kolom = fn (string $nama): bool => in_array(
            $nama,
            Schema::getColumnListing('detail_pinjam'),
            true
        );

        Schema::table('detail_pinjam', function (Blueprint $table) use ($kolom) {
            $jatuhkan = array_filter(['alat_id', 'jumlah'], $kolom);
            if ($jatuhkan !== []) {
                $table->dropColumn(array_values($jatuhkan));
            }
        });

        /*
        | Kolom stok di alat mungkin sudah ter-drop: run pertama migrasi ini
        | menjatuhkannya, lalu rollback ditolak MySQL (dropForeign nama index
        | yang tidak ada) dan hanya berhasil mengembalikan detail_pinjam.
        | Cek dulu sebelum drop.
        */
        $kolomAlat = fn (string $nama): bool => in_array(
            $nama,
            Schema::getColumnListing('alat'),
            true
        );

        $jatuhkanAlat = array_filter(
            ['stok', 'stok_baik', 'stok_rusak', 'stok_rusak_parah', 'status_kondisi'],
            $kolomAlat
        );

        if ($jatuhkanAlat !== []) {
            Schema::table('alat', function (Blueprint $table) use ($jatuhkanAlat) {
                $table->dropColumn(array_values($jatuhkanAlat));
            });
        }

        // Terapkan FK untuk kolom baru: alat_unit yang sudah punya riwayat
        // peminjaman tidak boleh hilang diam-diam.
        // Lewati kalau sudah ada (migrasi pernah jalan sebagian).
        $fkAda = (bool) DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'detail_pinjam')
            ->where('CONSTRAINT_NAME', 'detail_pinjam_alat_unit_id_foreign')
            ->exists();

        if (!$fkAda) {
            Schema::table('detail_pinjam', function (Blueprint $table) {
                $table->foreign('alat_unit_id')
                    ->references('id')
                    ->on('alat_unit')
                    ->restrictOnDelete();
            });
        }
    }

    /**
     * SQLite: bangun ulang detail_pinjam tanpa kolom alat_id/jumlah.
     * Kolom alat juga dibangun ulang karena SQLite punya batasan yang
     * sama untuk drop column pada tabel dengan FK.
     */
    private function dropKolomSqlite(): void
    {
        // 1. detail_pinjam -> tabel baru
        Schema::create('detail_pinjam_new', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peminjaman_id')->constrained('peminjaman')->cascadeOnDelete();
            $table->foreignId('alat_unit_id')->nullable();
            $table->timestamps();
        });

        DB::statement(
            'INSERT INTO detail_pinjam_new (id, peminjaman_id, alat_unit_id, created_at, updated_at)
             SELECT id, peminjaman_id, alat_unit_id, created_at, updated_at FROM detail_pinjam'
        );

        Schema::dropIfExists('detail_pinjam');

        Schema::rename('detail_pinjam_new', 'detail_pinjam');

        // 2. alat -> buang kolom stok
        //    SQLite di versi modern mendukung drop column, tapi hanya kalau
        //    kolom itu tidak dipakai index apa pun. Tabel alat tidak punya
        //    index pada kolom stok, jadi drop langsung aman di sini.
        Schema::table('alat', function (Blueprint $table) {
            $table->dropColumn([
                'stok',
                'stok_baik',
                'stok_rusak',
                'stok_rusak_parah',
                'status_kondisi',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('alat', function (Blueprint $table) {
            $table->integer('stok')->default(0)->after('nama_alat');
            $table->integer('stok_baik')->default(0)->after('stok');
            $table->integer('stok_rusak')->default(0)->after('stok_baik');
            $table->integer('stok_rusak_parah')->default(0)->after('stok_rusak');
            $table->string('status_kondisi')->nullable()->after('stok_rusak_parah');
        });

        Schema::table('detail_pinjam', function (Blueprint $table) {
            $table->foreignId('alat_id')->nullable()->after('peminjaman_id');
            $table->integer('jumlah')->default(1)->after('alat_id');
        });
    }
};
