<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlatResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama_alat' => $this->nama_alat,
            'kode_alat' => $this->kode_alat,
            'deskripsi' => $this->deskripsi,
            'gambar' => $this->gambar ? url('storage/' . $this->gambar) : null,
            'is_arsip' => (bool) $this->is_arsip,

            'kategori' => new KategoriResource($this->whenLoaded('kategori')),

            // Hanya tampil kalau controller memakai withCount('alatUnit').
            // whenCounted menghindari error saat relasi tidak di-load.
            'jumlah_tersedia' => $this->whenCounted('alatUnit'),
            'jumlah_unit' => $this->whenCounted('alatUnit'),

            // Daftar unit serial. Dipakai form peminjaman untuk
            // menampilkan checkbox per unit. Hanya di-load saat diminta
            // (with('alatUnit')) supaya list besar tidak berat.
            'units' => AlatUnitResource::collection($this->whenLoaded('alatUnit')),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
