<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailPermintaanEdit extends Model
{
    protected $table = 'detail_permintaan_edit';

    protected $fillable = [
        'permintaan_edit_id',
        'alat_id',
        'jumlah',
        'aksi',
    ];

    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
        ];
    }

    public function permintaanEdit(): BelongsTo
    {
        return $this->belongsTo(PermintaanEditPeminjaman::class, 'permintaan_edit_id');
    }

    public function alat(): BelongsTo
    {
        return $this->belongsTo(Alat::class);
    }
}
