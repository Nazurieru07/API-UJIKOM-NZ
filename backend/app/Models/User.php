<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    // 3 role: admin (kelola master data + approve), petugas (approve
    // peminjaman + input pengembalian), peminjam (ajukan peminjaman).
    // Akses tiap role dibatasi lewat middleware 'role' (CheckRole.php),
    // bukan hanya disembunyikan dari menu.
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'users';

    /*
    |--------------------------------------------------------------------------
    | Default Attributes
    |--------------------------------------------------------------------------
    | User::create() yang tidak menyebut is_aktif menghasilkan instance
    | dengan is_aktif = null. Kolom DB default true hanya diisi database,
    | nilainya tidak disalin ke model yang baru dibuat. Cast 'boolean'
    | mengubah null menjadi false, sehingga user yang sebenarnya aktif
    | ikut ter-logout oleh middleware CekUserAktif.
    |
    | $attributes menutup celah itu tanpa menyentuh semua call site.
    */
    protected $attributes = [
        'is_aktif' => true,
    ];

    protected $fillable = [
    'name',
    'email',
    'password',
    'role',
    'is_aktif',
    'no_hp',
    'alamat',
    'foto_profile',
    'jenis_kelamin',
];

    protected $hidden = [
    'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_aktif' => 'boolean',
        ];
    }

    public function peminjaman(): HasMany
    {
        return $this->hasMany(Peminjaman::class);
    }

    public function logAktivitas(): HasMany
    {
        return $this->hasMany(LogAktivitas::class);
    }

    /*
    |----------------------------------------------------------------------
    | Nonaktifkan user
    |----------------------------------------------------------------------
    | is_aktif=false berarti akun tetap ada dan seluruh riwayatnya utuh,
    | tapi tidak bisa login lagi dan sesi yang sedang berjalan langsung
    | diputus (CekAktifitas middleware).
    |
    | Tetap memakai kolom biasa, bukan SoftDeletes: akun nonaktif harus
    | bisa diaktifkan kembali tanpa mengubah email/riwayat, dan
    | SoftDeletes akan membiarkan user nonaktif lolos dari setiap query
    | tanpa sengaja.
    */

    public function scopeAktif($query)
    {
        return $query->where('is_aktif', true);
    }

    public function scopeNonaktif($query)
    {
        return $query->where('is_aktif', false);
    }

    public function isAktif(): bool
    {
        return (bool) $this->is_aktif;
    }
}
