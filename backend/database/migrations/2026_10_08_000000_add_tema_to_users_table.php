<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Preferensi tema user: 'light' (default) atau 'dark'. NULL =
    // light, supaya user yang dibuat sebelum migration ini tidak
    // butuh backfill.
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('tema', 10)->nullable()->after('jenis_kelamin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('tema');
        });
    }
};
