<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Alat extends Model
{
    use HasFactory;

    protected $table = 'alat';

    protected $fillable = [
    'kategori_id',
    'nama_alat',
    'stok',
    'stok_baik',
    'stok_rusak',
    'stok_rusak_parah',
    'status_kondisi',
    'deskripsi',
    'gambar',
];

    protected function casts(): array
{
    return [
        'stok' => 'integer',
        'stok_baik' => 'integer',
        'stok_rusak' => 'integer',
        'stok_rusak_parah' => 'integer',
    ];
}

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class);
    }

    public function detailPinjam(): HasMany
    {
        return $this->hasMany(DetailPinjam::class);
    }
}