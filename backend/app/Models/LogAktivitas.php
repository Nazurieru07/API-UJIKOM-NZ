<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogAktivitas extends Model
{
    protected $table = 'log_aktivitas';

    protected $fillable = [
        'user_id', 'aktivitas'
    ];

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }
}
