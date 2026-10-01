<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailPermintaanEdit extends Model
{
    /*
    |----------------------------------------------------------------------
    | Pengajuan edit peminjaman per unit serial
    |----------------------------------------------------------------------
    | aksi 'tambah' = minta tambahkan unit ini ke peminjaman.
    | aksi 'hapus'  = minta lepaskan unit ini dari peminjaman.
    | 1 baris = 1 unit. Kolom jumlah lama tidak dipakai lagi.
    */

    protected $table = 'detail_permintaan_edit';

    protected $fillable = [
        'permintaan_edit_id',
        'alat_unit_id',
        'aksi',
    ];

    public function permintaanEdit(): BelongsTo
    {
        return $this->belongsTo(PermintaanEditPeminjaman::class, 'permintaan_edit_id');
    }

    public function alatUnit(): BelongsTo
    {
        return $this->belongsTo(AlatUnit::class);
    }
}
