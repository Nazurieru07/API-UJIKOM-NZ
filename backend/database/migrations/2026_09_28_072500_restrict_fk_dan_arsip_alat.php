<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | Kunci integrity: hapus data berelasi tidak boleh diam-diam
    |--------------------------------------------------------------------------
    | Sebelumnya SEMUA FK pakai CASCADE. Hapus kategori langsung menghapus
    | alat + seluruh riwayat peminjaman; hapus alat menghapus riwayatnya.
    | User tidak dapat pesan apa-apa, datanya saja lenyap.
    |
    | Diganti RESTRICT: controller wajib menolak penghapusan yang masih
    | punya relasi, jadi user melihat pesan jelas.
    |
    | Sengaja TETAP CASCADE (tidak diubah):
    | - pengembalian.peminjaman_id       -> hapus peminjaman menarik
    |                                      pengembaliannya (alur lama,
    |                                      stok tidak dobel karena
    |                                      pengembalian menunggu belum
    |                                      menambah stok).
    | - detail_pinjam.peminjaman_id     -> sama.
    */

    public function up(): void
    {
        // 1. Kolom arsip di alat.
        //    Alat yang sudah punya riwayat peminjaman tidak boleh dihapus
        //    permanen, tapi boleh disembunyikan dari katalog petugas.
        Schema::table('alat', function (Blueprint $table) {
            $table->boolean('is_arsip')->default(false)->after('status_kondisi');
        });

        // SQLite (dipakai phpunit) tidak bisa drop foreign key by name, dan
        // di sqlite FK tidak dicek -- guard ada di controller saja.
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        // 2. kategori <- alat: kategori yang masih dipakai alat tidak
        //    boleh dihapus.
        Schema::table('alat', function (Blueprint $table) {
            $table->dropForeign('alat_kategori_id_foreign');
            $table->foreign('kategori_id')->references('id')->on('kategori')->restrictOnDelete();
        });

        // 3. alat <- detail_pinjam: alat yang masih dirujuk riwayat
        //    peminjaman tidak boleh dihapus permanen.
        Schema::table('detail_pinjam', function (Blueprint $table) {
            $table->dropForeign('detail_pinjam_alat_id_foreign');
            $table->foreign('alat_id')->references('id')->on('alat')->restrictOnDelete();
        });

        // 4. peminjaman <- permintaan_edit_peminjaman: pengajuan edit
        //    tidak boleh lenyap saat peminjaman dihapus.
        Schema::table('permintaan_edit_peminjaman', function (Blueprint $table) {
            $table->dropForeign('permintaan_edit_peminjaman_peminjaman_id_foreign');
            $table->foreign('peminjaman_id')->references('id')->on('peminjaman')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            Schema::table('alat', function (Blueprint $table) {
                $table->dropColumn('is_arsip');
            });

            return;
        }

        Schema::table('permintaan_edit_peminjaman', function (Blueprint $table) {
            $table->dropForeign('permintaan_edit_peminjaman_peminjaman_id_foreign');
            $table->foreign('peminjaman_id')->references('id')->on('peminjaman')->cascadeOnDelete();
        });

        Schema::table('detail_pinjam', function (Blueprint $table) {
            $table->dropForeign('detail_pinjam_alat_id_foreign');
            $table->foreign('alat_id')->references('id')->on('alat')->cascadeOnDelete();
        });

        Schema::table('alat', function (Blueprint $table) {
            $table->dropForeign('alat_kategori_id_foreign');
            $table->foreign('kategori_id')->references('id')->on('kategori')->cascadeOnDelete();
            $table->dropColumn('is_arsip');
        });
    }
};
