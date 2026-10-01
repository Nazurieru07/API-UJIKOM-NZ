<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    |----------------------------------------------------------------------
    | Fitur nonaktifkan user (bukan hapus)
    |----------------------------------------------------------------------
    | User yang masih punya relasi peminjaman tidak boleh dihapus
    | (FK RESTRICT + guard di AdminController::destroyUser). Karena itu
    | admin butuh cara lain untuk melarang user beraktivitas tanpa
    | menghilangkan riwayatnya: flag is_aktif=false.
    |
    | Default true agar user yang sudah ada tetap bisa login.
    | Mencegah seluruh akun terkunci saat migrasi berjalan.
    */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_aktif')->default(true)->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_aktif');
        });
    }
};
