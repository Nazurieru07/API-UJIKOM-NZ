<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogAktivitas extends Model
{
    // Audit trail aplikasi. Diisi OTOMATIS oleh 3 observer
    // (Alat, Peminjaman, Pengembalian), bukan ditulis manual.
    // Catatan: pengecualian if (!Auth::check()) di observer berarti
    // operasi lewat seeder/artisan TANPA user login tidak tercatat.
    protected $table = 'log_aktivitas';

    protected $fillable = [
        'user_id', 'aktivitas'
    ];

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }
}
