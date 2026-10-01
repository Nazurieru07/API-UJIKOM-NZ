<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlatUnitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'alat_id' => $this->alat_id,
            'serial_number' => $this->serial_number,
            'kondisi' => $this->kondisi,
        ];
    }
}
