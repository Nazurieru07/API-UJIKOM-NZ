<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PermintaanEditPeminjaman extends Model
{
    protected $table = 'permintaan_edit_peminjaman';

    protected $fillable = [
        'peminjaman_id',
        'user_id',
        'tgl_kembali_plan_baru',
        'alasan',
        'status',
        'processed_by',
        'catatan_penolakan',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'tgl_kembali_plan_baru' => 'date:Y-m-d',
            'processed_at' => 'datetime',
        ];
    }

    public function peminjaman(): BelongsTo
    {
        return $this->belongsTo(Peminjaman::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function detailEdits(): HasMany
    {
        return $this->hasMany(DetailPermintaanEdit::class, 'permintaan_edit_id');
    }
}
