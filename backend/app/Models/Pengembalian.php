<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pengembalian extends Model
{
    // Relasi 1:1 ke peminjaman (peminjaman_id unik).
    // Alur: petugas mengajukan (status_request=diproses),
    // admin menyetujui (status_request=disetujui, stok dikembalikan),
    // atau admin menolak (status_request=ditolak, pengembalian dihapus
    // dan peminjaman tetap aktif).
    protected $table = 'pengembalian';

    protected $fillable = [
    'peminjaman_id',
    'tgl_kembali',
    'kondisi_kembali',
    'denda',
    'denda_kerusakan',
    'petugas_id',
    'status_request',
];

    protected function casts(): array
    {
        return [
            'tgl_kembali' => 'date:Y-m-d',
            'denda' => 'integer',
            'denda_kerusakan' => 'integer',
        ];
    }

    public function peminjaman(): BelongsTo
    {
        return $this->belongsTo(Peminjaman::class);
    }

    // Petugas yang memproses pengembalian ini.
    // Bisa NULL: admin memproses sendiri tanpa petugas,
    // di laporan ditampilkan sebagai "Admin" (bukan error).
    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }
}