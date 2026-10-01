<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    |----------------------------------------------------------------------
    | detail_pinjam: alat_id + jumlah -> alat_unit_id
    |----------------------------------------------------------------------
    | SCHEMA SEBELUMNYA: detail_pinjam.(alat_id, jumlah) artinya "peminjam
    | mengambil 3 pcs Kamera". Tidak ada cara tahu Kamera yang mana.
    |
    | SEKARANG: detail_pinjam.alat_unit_id = satu unit serial spesifik.
    | Kolom jumlah dihapus -- 1 baris selalu 1 unit.
    |
    | Pemetaan data lama: semua peminjaman saat migrasi ini berstatus
    | 'dikembalikan', jadi tidak ada unit yang sedang aktif. Setiap
    | detail_pinjam lama diberikan unit 'tersedia' pertama untuk alat
    | yang sama. Pemetaan ini historis, tidak naratif -- yang penting
    | tidak ada orphan dan setiap detail bisa ditelusuri balik ke unit.
    |
    | Kolom lama (alat_id, jumlah) TIDAK di-drop di sini. Drop menunggu
    | kode aplikasi (model, controller, view) berhenti memakainya,
    | dilakukan di migrasi terpisah.
    */

    public function up(): void
    {
        Schema::table('detail_pinjam', function (Blueprint $table) {
            $table->foreignId('alat_unit_id')->nullable()->after('jumlah');
        });

        // Tracking unit mana yang sudah dipakai supaya tidak dobel.
        $terpakai = [];

        $details = DB::table('detail_pinjam')
            ->join('peminjaman', 'peminjaman.id', '=', 'detail_pinjam.peminjaman_id')
            ->orderBy('detail_pinjam.id')
            ->select('detail_pinjam.id', 'detail_pinjam.alat_id')
            ->get();

        foreach ($details as $detail) {
            $unitId = $this->cariUnitTidakTerpakai($detail->alat_id, $terpakai);

            if ($unitId === null) {
                // Alat tidak punya unit sama sekali (misal stok 0).
                // Biarkan null; akan diurus manual, bukan di-drop.
                continue;
            }

            DB::table('detail_pinjam')
                ->where('id', $detail->id)
                ->update(['alat_unit_id' => $unitId]);

            $terpakai[] = $unitId;
        }
    }

    /**
     * Unit pertama untuk alat ini yang belum dipakai di batch ini.
     *
     * Urut: 'tersedia' lebih dulu. Kalau habis, boleh ambil unit 'rusak'
     * sebagai fallback. Data detail_pinjam lama sudah terjadi -- lebih
     * penting setiap baris punya unit (tidak orphan, bisa ditelusuri
     * balik) daripada kondisi unit cocok 100% dengan riwayatnya.
     * Semua peminjaman terkait sudah 'dikembalikan' jadi tidak ada
     * efek samping ke status unit sekarang.
     */
    private function cariUnitTidakTerpakai(int $alatId, array $terpakai): ?int
    {
        $unit = DB::table('alat_unit')
            ->where('alat_id', $alatId)
            ->orderByRaw("CASE WHEN kondisi = 'tersedia' THEN 0 ELSE 1 END")
            ->orderBy('serial_number')
            ->pluck('id');

        foreach ($unit as $id) {
            if (! in_array($id, $terpakai, true)) {
                return $id;
            }
        }

        return null;
    }

    public function down(): void
    {
        DB::table('detail_pinjam')->update(['alat_unit_id' => null]);

        Schema::table('detail_pinjam', function (Blueprint $table) {
            $table->dropColumn('alat_unit_id');
        });
    }
};
