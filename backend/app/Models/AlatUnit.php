<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlatUnit extends Model
{
    use HasFactory;

    /*
    |----------------------------------------------------------------------
    | Satu unit fisik dari sebuah alat
    |----------------------------------------------------------------------
    | Sebelumnya satu baris `alat` mewakili N barang seragam (dilacak
    | via stok). Sekarang setiap barang punya baris sendiri disini dengan
    | serial number yang unik secara global.
    |
    | Kondisi (enum, 3 nilai):
    | - tersedia : siap dipinjam, muncul di katalog
    | - dipinjam : sedang keluar, di-set saat peminjaman disetujui
    |              dan dikembalikan ke 'tersedia'/'rusak' saat
    |              pengembalian diproses
    | - rusak    : tidak layak dipinjam, disembunyikan dari katalog
    |              sampai diperbaiki admin di menu Kelola Unit
    */

    protected $table = 'alat_unit';

    protected $fillable = [
        'alat_id',
        'serial_number',
        'kondisi',
    ];

    protected function casts(): array
    {
        return [
            'serial_number' => 'string',
        ];
    }

    public function alat(): BelongsTo
    {
        return $this->belongsTo(Alat::class);
    }

    /*
    |----------------------------------------------------------------------
    | Scope
    |----------------------------------------------------------------------
    */

    public function scopeTersedia($query)
    {
        return $query->where('kondisi', 'tersedia');
    }

    public function scopeRusak($query)
    {
        return $query->where('kondisi', 'rusak');
    }

    public function scopeDipinjam($query)
    {
        return $query->where('kondisi', 'dipinjam');
    }

    /*
    |----------------------------------------------------------------------
    | Helper kondisi
    |----------------------------------------------------------------------
    */

    public function isTersedia(): bool
    {
        return $this->kondisi === 'tersedia';
    }

    public function isRusak(): bool
    {
        return $this->kondisi === 'rusak';
    }

    public function isDipinjam(): bool
    {
        return $this->kondisi === 'dipinjam';
    }

    /*
    |----------------------------------------------------------------------
    | Serial number berikutnya
    |----------------------------------------------------------------------
    | Nomor urut diambil dari SELURUH tabel untuk prefix tersebut, bukan
    | per alat_id, karena serial_number punya constraint UNIQUE global.
    | Kalau hanya melihat alat ini, dua alat dengan kode_alat sama
    | (mis. "Future Furniture" dan "ffg", keduanya "FF") akan sama-sama
    | menghasilkan FF-001 dan bentrok.
    |
    | Konsekuensinya: alat kedua dengan prefix sama mulai dari nomor
    | setelah milik alat pertama (FF-004, bukan FF-001). Nomor melompat
    | tapi tidak pernah bentrok -- nomor bolong akan menyesatkan saat
    | dicari, sedangkan bentrok membuat unit gagal dibuat.
    |
    | Dipanggil dari KelolaUnitAlat::storeUnit dan AdminController::storeAlat.
    */

    public static function serialBerikutnya(int $alatId, string $kodeAlat): string
    {
        return static::serialBerikutnyaBatch($kodeAlat, 1)[0];
    }

    /**
     * Serial berikutnya SEKALIGUS untuk beberapa unit.
     *
     * Dipakai AdminController::storeAlat yang harus membuat N unit dalam
     * satu request. Kalau serialBerikutnya() dipanggil di dalam loop,
     * setiap iterasi membaca DB yang belum berubah -- semua unit dapat
     * nomor sama lalu kena constraint UNIQUE.
     *
     * @param  string  $kodeAlat  Prefix, mis. "RM"
     * @param  int  $jumlah  Berapa serial yang perlu disiapkan
     * @return list<string>
     */
    public static function serialBerikutnyaBatch(string $kodeAlat, int $jumlah): array
    {
        $kodeAlat = strtoupper(trim($kodeAlat));

        /*
        | Nomor terbesar dihitung di PHP, bukan dengan CAST/SUBSTRING_INDEX
        | di SQL: fungsi itu MySQL-only, sedangkan test suite jalan di
        | SQLite in-memory -- query itu akan gagal di test padahal jalan
        | di produksi. Jumlah unit per alat di sekolah kecil, jadi memuat
        | serial ke memori bukan masalah.
        */
        $terbesar = 0;

        static::where('serial_number', 'like', $kodeAlat . '-%')
            ->pluck('serial_number')
            ->each(function (string $serial) use (&$terbesar) {
                $nomor = (int) substr($serial, strrpos($serial, '-') + 1);

                if ($nomor > $terbesar) {
                    $terbesar = $nomor;
                }
            });

        $serials = [];

        for ($i = 1; $i <= $jumlah; $i++) {
            $serials[] = $kodeAlat . '-' . str_pad((string) ($terbesar + $i), 3, '0', STR_PAD_LEFT);
        }

        return $serials;
    }
}
