<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    |----------------------------------------------------------------------
    | Serial number per unit alat (menggantikan stok)
    |----------------------------------------------------------------------
    | Sebelumnya: satu baris `alat` mewakili N barang yang seragam,
    | dilacak lewat kolom stok/stok_baik/stok_rusak/stok_rusak_parah.
    | Tidak ada cara melihat "barang yang mana" sedang dipinjam.
    |
    | Sekarang: setiap barang fisik jadi satu baris `alat_unit` dengan
    | serial number sendiri. Satu peminjaman memilih unit tertentu.
    |
    | Kondisi enum tiga nilai saja:
    | - tersedia  : siap dipinjam
    | - dipinjam  : sedang keluar
    | - rusak     : tidak layak dipinjam, disembunyikan dari katalog
    |               sampai diperbaiki (admin di menu Kelola Unit)
    |
    | Rusak tunggal (tidak ada ringan/berat): tanpa stok agregat, unit
    | cuma punya dua keadaan fisik -- layak dipakai atau tidak.
    */

    public function up(): void
    {
        Schema::table('alat', function (Blueprint $table) {
            // Prefix serial, editable admin. Misal "Router Mikrotik RB941"
            // -> "RM". Default diisi saat migrasi data dari nama_alat.
            $table->string('kode_alat', 10)->nullable()->after('nama_alat');
        });

        Schema::create('alat_unit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alat_id')->constrained('alat')->cascadeOnDelete();

            // Unique antar SEMUA unit, bukan per-alat: serial number
            // harus unik secara global agar tidak ambigu saat petugas
            // mengetik serial di pencarian.
            $table->string('serial_number', 50)->unique();

            $table->enum('kondisi', ['tersedia', 'dipinjam', 'rusak'])
                ->default('tersedia');

            $table->timestamps();

            $table->index(['alat_id', 'kondisi']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alat_unit');

        Schema::table('alat', function (Blueprint $table) {
            $table->dropColumn('kode_alat');
        });
    }
};
