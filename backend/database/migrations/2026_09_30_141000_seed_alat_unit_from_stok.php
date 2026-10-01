<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    |----------------------------------------------------------------------
    | Isi data serial dari stok lama
    |----------------------------------------------------------------------
    | Stok 18 unit Router Mikrotik -> 18 baris alat_unit dengan serial
    | RM-001 s/d RM-018. Prefix diambil dari huruf pertama tiap kata
    | nama_alat (dua huruf pertama, uppercase).
    |
    | Logika prefix ada juga di App\Models\Alat::saranKodeAlat() agar form
    | admin menampilkan saran yang sama. Kalau diubah di satu tempat,
    | ubah juga di tempat lain.
    |
    | Unit "rusak": diambil dari stok_rusak + stok_rusak_parah lama,
    | serial ditarik dari urutan terakhir (yang paling besar) supaya
    | unit pertama (yang paling sering dipakai) tetap "tersedia".
    |
    | Setelah migrasi ini kolom stok lama tidak lagi dipakai dan akan
    | dihapus pada migrasi berikutnya, SETELAH data terkonfirmasi benar.
    */

    public function up(): void
    {
        // Faktor 10 untuk padding: RM-001, ..., RM-010, RM-018.
        $pad = fn (int $n) => str_pad((string) $n, 3, '0', STR_PAD_LEFT);

        $alats = DB::table('alat')->orderBy('id')->get();

        foreach ($alats as $alat) {
            // 1. Prefix dari nama alat.
            $prefix = $this->saranKode($alat->nama_alat);

            DB::table('alat')
                ->where('id', $alat->id)
                ->update(['kode_alat' => $prefix]);

            // 2. Total unit = stok lama (semua kondisi).
            $total = (int) $alat->stok;
            $rusak = (int) $alat->stok_rusak + (int) $alat->stok_rusak_parah;

            if ($total < 1) {
                // Alat tanpa stok sama sekali tidak punya unit.
                // Tetap dibuatkan 0 baris -- bukan error.
                continue;
            }

            $tersedia = $total - $rusak;

            $rows = [];

            // Unit tersedia lebih dulu (serial kecil).
            for ($i = 1; $i <= $tersedia; $i++) {
                $rows[] = $this->rowUnit($alat->id, $prefix, $pad($i), 'tersedia');
            }

            // Unit rusak di urutan terakhir.
            for ($i = $tersedia + 1; $i <= $total; $i++) {
                $rows[] = $this->rowUnit($alat->id, $prefix, $pad($i), 'rusak');
            }

            $rows = $this->hindariDuplikatSerial($rows);

            // Chunk agar insert besar tidak membengkakkan memori.
            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('alat_unit')->insert($chunk);
            }
        }
    }

    /*
    | Serial number unik GLOBAL, jadi dua alat dengan prefix sama
    | (misal "Future Furniture" dan "ffg" -> keduanya "FF") akan
    | bentrok di FF-001. Tambah suffix huruf sampai unik:
    | FF-001 -> FFA-001. Dipilih suffix, bukan ubah prefix, supaya
    | prefix utama tetap mencerminkan nama alat aslinya.
    */
    private function hindariDuplikatSerial(array $rows): array
    {
        // Serial yang sudah dipakai di tabel + yang ada di batch ini.
        $ada = DB::table('alat_unit')->pluck('serial_number')->all();

        foreach ($rows as &$row) {
            $serial = $row['serial_number'];

            if (in_array($serial, $ada, true)) {
                $base = explode('-', $serial, 2);
                $prefix = $base[0];
                $nomor = $base[1];
                $suffix = 'A';

                while (in_array($prefix . $suffix . '-' . $nomor, $ada, true)) {
                    $suffix++;
                }

                $serial = $prefix . $suffix . '-' . $nomor;
            }

            $row['serial_number'] = $serial;
            $ada[] = $serial;
        }

        return $rows;
    }

    /**
     * Satu baris alat_unit.
     */
    private function rowUnit(int $alatId, string $prefix, string $nomor, string $kondisi): array
    {
        return [
            'alat_id' => $alatId,
            'serial_number' => $prefix . '-' . $nomor,
            'kondisi' => $kondisi,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Prefix: huruf pertama dari tiap kata, ambil 2 huruf pertama.
     * "Router Mikrotik RB941" -> "RM"
     * "Kamera DSLR Canon"     -> "KD"
     * "Tang Crimping"         -> "TC"
     * "ffg" (satu kata)       -> "FF"
     */
    private function saranKode(string $nama): string
    {
        $kata = preg_split('/\s+/', trim((string) $nama));
        $kata = array_filter($kata, fn ($k) => $k !== '');

        if (count($kata) === 1) {
            // Satu kata: ambil 2 huruf pertama kata itu.
            // "Teleporter" -> "TE", "ffg" -> "FF".
            // Ambil 2 huruf pertama lebih konsisten dari pada 1 huruf,
            // yang bisa bentrok antar alat satu kata.
            return strtoupper(substr($kata[0], 0, 2));
        }

        $inisial = '';
        foreach ($kata as $k) {
            $inisial .= strtoupper(substr($k, 0, 1));
            if (strlen($inisial) >= 2) {
                break;
            }
        }

        return substr($inisial, 0, 2);
    }

    public function down(): void
    {
        DB::table('alat_unit')->truncate();
        DB::table('alat')->update(['kode_alat' => null]);
    }
};
