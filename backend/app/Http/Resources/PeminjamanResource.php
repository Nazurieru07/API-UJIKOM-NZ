<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PeminjamanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'peminjam' => $this->whenLoaded('user', fn() => $this->user?->name),

            'tgl_pinjam' => $this->tgl_pinjam?->format('Y-m-d'),

            'tgl_kembali_plan' => $this->tgl_kembali_plan?->format('Y-m-d'),

            'status' => $this->status,

            // Satu item = satu unit serial. Nama alat didapat lewat
            // relasi unit->alat, jadi controller WAJIB eager-load
            // 'detailPinjam.alatUnit.alat' (lihat audit note).
            'item_dipinjam' => $this->whenLoaded('detailPinjam', function () {
                return $this->detailPinjam->map(function ($detail) {
                    $unit = $detail->alatUnit;

                    return [
                        'serial_number' => $unit?->serial_number ?? 'Unit Tidak Ditemukan',
                        'nama_alat' => $unit?->alat?->nama_alat ?? 'Alat Dihapus/Tidak Ditemukan',
                    ];
                });
            }),

            'info_pengembalian' => $this->whenLoaded('pengembalian', function () {

                if (!$this->pengembalian) return null;

                return [
                    'tgl_kembali' => $this->pengembalian->tgl_kembali?->format('Y-m-d'),

                    'kondisi' => $this->pengembalian->kondisi_kembali,

                    'denda' => (int) $this->pengembalian->denda,

                    'petugas_penerima' => $this->pengembalian->petugas?->name ?? 'Sistem',
                ];
            }),
        ];
    }
}
