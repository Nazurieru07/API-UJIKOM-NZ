<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'email'             => $this->email,
            'role'              => $this->role,
            'no_hp'             => $this->no_hp,
            'alamat'            => $this->alamat,
            'foto_profile'      => $this->foto_profile ? url ('storage/' . $this->foto_profile) : null,
            'bergabung_sejak'   => $this->created_at->format('Y-m-d'),
        ];
    }
}