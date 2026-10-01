<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailPinjam extends Model
{
    /*
    |----------------------------------------------------------------------
    | Junction peminjaman <-> unit serial
    |----------------------------------------------------------------------
    | SEBELUMNYA: (alat_id, jumlah) artinya "peminjam ambil 3 pcs Kamera".
    | Tidak bisa tau Kamera mana yang mana.
    |
    | SEKARANG: alat_unit_id = 1 unit serial spesifik. Satu baris selalu
    | 1 unit; untuk meminjam 3 Kamera, buat 3 baris dengan 3 serial beda.
    | Kolom jumlah dan alat_id dihapus dari skema.
    |
    | Untuk sampai ke alat: $detail->alatUnit->alat.
    */

    protected $table = 'detail_pinjam';

    protected $fillable = [
        'peminjaman_id', 'alat_unit_id'
    ];

    public function peminjaman(): BelongsTo
    {
        return $this->belongsTo(Peminjaman::class);
    }

    public function alatUnit(): BelongsTo
    {
        return $this->belongsTo(AlatUnit::class);
    }
}
