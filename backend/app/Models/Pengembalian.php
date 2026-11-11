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
        // Keterangan dari peminjam saat mengajukan sendiri.
        // Kondisi & denda kerusakan diisi petugas/admin saat diperiksa.
        'catatan_peminjam',
        // Flag untuk pengembalian parsial
        'is_parsial',
        'jumlah_unit_dikembalikan',
    ];

    protected function casts(): array
    {
        return [
            'tgl_kembali' => 'date:Y-m-d',
            'denda' => 'integer',
            'denda_kerusakan' => 'integer',
            'is_parsial' => 'boolean',
            'jumlah_unit_dikembalikan' => 'integer',
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
    
    /**
     * Relasi ke detail pengembalian (untuk pengembalian parsial)
     */
    public function detailPengembalians()
    {
        return $this->hasMany(DetailPengembalian::class);
    }
    
    /**
     * Cek apakah pengembalian ini parsial
     */
    public function isParsial()
    {
        return $this->is_parsial;
    }
    
    /**
     * Cek apakah semua unit sudah dikembalikan
     */
    public function isLengkap()
    {
        if (!$this->is_parsial) {
            return true;
        }
        
        $totalUnit = $this->peminjaman->detailPinjams()->count();
        return $this->jumlah_unit_dikembalikan >= $totalUnit;
    }
    
    /**
     * Tambah unit yang dikembalikan
     */
    public function tambahUnitDikembalikan($jumlah = 1)
    {
        $this->increment('jumlah_unit_dikembalikan', $jumlah);
    }
}