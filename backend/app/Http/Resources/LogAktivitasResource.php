<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LogAktivitasResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => [
                'id' => $this->user->id ?? null,
                'name' => $this->user->name ?? 'Sistem',
                'role' => $this->user->role ?? null,
            ],
            'aktivitas' => $this->aktivitas,
            'waktu' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}