<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailPengembalian extends Model
{
    use HasFactory;

    protected $table = 'detail_pengembalian';

    protected $fillable = [
        'pengembalian_id',
        'detail_pinjam_id',
        'diproses_oleh_id',
        'kondisi_kembali',
        'denda_kerusakan',
    ];

    protected $casts = [
        'denda_kerusakan' => 'integer',
    ];

    /**
     * Relasi ke pengembalian utama.
     */
    public function pengembalian(): BelongsTo
    {
        return $this->belongsTo(Pengembalian::class);
    }

    /**
     * Relasi ke detail peminjaman.
     */
    public function detailPinjam(): BelongsTo
    {
        return $this->belongsTo(DetailPinjam::class);
    }

    /**
     * Relasi ke Admin/Petugas yang memproses unit ini.
     */
    public function pemroses(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diproses_oleh_id');
    }

    /**
     * Relasi ke alat unit melalui detail pinjam.
     */
    public function alatUnit()
    {
        return $this->hasOneThrough(
            AlatUnit::class,
            DetailPinjam::class,
            'id',
            'id',
            'detail_pinjam_id',
            'alat_unit_id'
        );
    }

    /**
     * Scope untuk unit dengan kondisi baik.
     */
    public function scopeKondisiBaik($query)
    {
        return $query->where('kondisi_kembali', 'Baik');
    }

    /**
     * Scope untuk unit dengan kondisi rusak.
     */
    public function scopeKondisiRusak($query)
    {
        return $query->where('kondisi_kembali', 'Rusak');
    }

    /**
     * Cek apakah unit sudah dikembalikan.
     */
    public static function sudahDikembalikan($detailPinjamId)
    {
        return self::where('detail_pinjam_id', $detailPinjamId)->exists();
    }
}
