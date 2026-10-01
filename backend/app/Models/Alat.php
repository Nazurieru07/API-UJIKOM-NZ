<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Alat extends Model
{
    use HasFactory;

    protected $table = 'alat';

    /*
    |----------------------------------------------------------------------
    | Kolom stok lama (stok, stok_baik, stok_rusak, stok_rusak_parah,
    | status_kondisi) sudah dihapus: sekarang setiap unit fisik dilacak
    | di tabel alat_unit dengan serial number dan kondisinya sendiri.
    | Jumlah unit tersedia = $alat->alatUnit()->tersedia()->count().
    |----------------------------------------------------------------------
    */

    protected $fillable = [
        'kategori_id',
        'nama_alat',
        'kode_alat',
        'is_arsip',
        'deskripsi',
        'gambar',
    ];

    protected function casts(): array
    {
        return [
            'is_arsip' => 'boolean',
        ];
    }

    /*
    |----------------------------------------------------------------------
    | Prefix serial number
    |----------------------------------------------------------------------
    | Sama dengan logika di migrasi 2026_09_30_141000_seed_alat_unit_from_stok.
    | Digunakan form admin sebagai auto-saran; admin bebas menimpa.
    |
    | Bukan accessor, tapi static helper, karena harus bisa dipanggil
    | saat membuat alat baru (nama belum disimpan ke model).
    */

    public static function saranKodeAlat(string $nama): string
    {
        $kata = preg_split('/\s+/', trim((string) $nama));
        $kata = array_values(array_filter($kata, fn ($k) => $k !== ''));

        if ($kata === []) {
            return 'AL';
        }

        if (count($kata) === 1) {
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

    /*
    |----------------------------------------------------------------------
    | Relasi
    |----------------------------------------------------------------------
    */

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class);
    }

    public function alatUnit(): HasMany
    {
        return $this->hasMany(AlatUnit::class);
    }

    /*
    |----------------------------------------------------------------------
    | Shortcut hitungan unit
    |----------------------------------------------------------------------
    | Katalog dan laporan butuh angka "berapa unit layak pinjam".
    | Disimpan di accessor agar query bisa pakai withCount('alatUnit')
    | tanpa N+1.
    |
    | withCount tidak bisa memfilter per-kondisi tanpa closure, jadi
    | panggilan list wajib eager-load relasi atau pakai withCount
    | bertarget (lihat AdminController::indexAlat).
    */

    public function unitTersedia(): HasMany
    {
        return $this->alatUnit()->tersedia();
    }

    public function unitDipinjam(): HasMany
    {
        return $this->alatUnit()->dipinjam();
    }

    public function unitRusak(): HasMany
    {
        return $this->alatUnit()->rusak();
    }

    /*
    |----------------------------------------------------------------------
    | Alat diarsipkan, bukan dihapus (lihat AdminController::destroyAlat)
    |----------------------------------------------------------------------
    | Katalog (peminjam/petugas) hanya tampilkan alat aktif. Admin tetap
    | melihat semua untuk laporan riwayat.
    */

    public function scopeTidakTerarsip($query)
    {
        return $query->where('is_arsip', false);
    }

    public function scopeTerarsip($query)
    {
        return $query->where('is_arsip', true);
    }

    /*
    |----------------------------------------------------------------------
    | Detail peminjaman yang pernah memakai alat ini
    |----------------------------------------------------------------------
    | BUKAN hasMany(DetailPinjam::class) seperti dulu. Saat itu
    | detail_pinjam punya alat_id langsung; kolom itu sudah di-drop
    | (1 baris = 1 unit serial). Jadi hubungannya lewat tabel
    | perantara: alat -> alat_unit -> detail_pinjam.
    | Jangan pakai hasManyThrough dengan kolomforeign yang tidak ada --
    | query-nya akan gagal dengan "unknown column".
    */
    public function detailPinjam(): HasManyThrough
    {
        return $this->hasManyThrough(
            DetailPinjam::class,
            AlatUnit::class,
            'alat_id',    // kolom di tabel perantara (alat_unit)
            'alat_unit_id', // kolom di tabel tujuan (detail_pinjam)
            'id',         // kunci lokal (alat.id)
            'id',         // kolom terkait di perantara (alat_unit.id)
        );
    }
}
