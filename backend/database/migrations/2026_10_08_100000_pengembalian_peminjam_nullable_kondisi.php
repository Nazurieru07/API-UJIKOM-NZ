<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /*
    | Pengembalian diajukan sendiri oleh peminjam (bukan petugas):
    | saat mengajukan, peminjam tidak tahu kondisi barang dan tidak
    | menentukan denda. Kondisi_kembali dan denda_kerusakan diisi
    | oleh petugas/admin saat pemeriksaan. Karena itu kolomnya harus
    | nullable; sebelumnya NOT NULL memaksa petugas selalu mengisi.
    |
    | catatan_peminjam: alasan/keterangan dari peminjam saat
    | mengajukan (mis. "sudah saya titip di pos satpam").
    */
    public function up(): void
    {
        Schema::table('pengembalian', function (Blueprint $table) {
            $table->text('catatan_peminjam')->nullable()->after('petugas_id');
        });

        // Kondisi & denda kerusakan hanya diisi saat pemeriksaan.
        // Doctrine DBAL tidak terpasang (->change() tak bisa), dan
        // ALTER ... MODIFY cuma ada di MySQL -- test suite pakai
        // SQLite, jadi bangun SQL per grammar.
        if (DB::getDriverName() === 'sqlite') {
            DB::statement(
                'ALTER TABLE pengembalian RENAME COLUMN kondisi_kembali TO kondisi_kembali_old'
            );
            DB::statement(
                'ALTER TABLE pengembalian ADD COLUMN kondisi_kembali VARCHAR(255)'
            );
            DB::table('pengembalian')->update([
                'kondisi_kembali' => DB::raw('kondisi_kembali_old'),
            ]);
            DB::statement(
                'ALTER TABLE pengembalian DROP COLUMN kondisi_kembali_old'
            );
        } else {
            DB::statement(
                'ALTER TABLE pengembalian MODIFY kondisi_kembali VARCHAR(255) NULL'
            );
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement(
                'ALTER TABLE pengembalian RENAME COLUMN kondisi_kembali TO kondisi_kembali_new'
            );
            DB::statement(
                'ALTER TABLE pengembalian ADD COLUMN kondisi_kembali VARCHAR(255) NOT NULL'
            );
            DB::table('pengembalian')->update([
                'kondisi_kembali' => DB::raw('kondisi_kembali_new'),
            ]);
            DB::statement(
                'ALTER TABLE pengembalian DROP COLUMN kondisi_kembali_new'
            );
        } else {
            DB::statement(
                "ALTER TABLE pengembalian MODIFY kondisi_kembali VARCHAR(255) NOT NULL"
            );
        }

        Schema::table('pengembalian', function (Blueprint $table) {
            $table->dropColumn('catatan_peminjam');
        });
    }
};
