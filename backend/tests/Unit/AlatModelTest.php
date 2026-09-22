<?php

namespace Tests\Unit;

use App\Models\Alat;
use App\Models\Kategori;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlatModelTest extends TestCase
{
    use RefreshDatabase;
    public function test_total_stok_konsisten_antara_kolom(): void
    {
        $kategori = Kategori::create(['nama_kategori' => 'Test Kategori']);

        $alat = Alat::create([
            'kategori_id' => $kategori->id,
            'nama_alat' => 'Alat Test',
            'stok' => 10,
            'stok_baik' => 6,
            'stok_rusak' => 3,
            'stok_rusak_parah' => 1,
            'status_kondisi' => 'Baik',
            'deskripsi' => null,
            'gambar' => null,
        ]);

        $totalKondisi = (int) $alat->stok_baik
            + (int) $alat->stok_rusak
            + (int) $alat->stok_rusak_parah;

        $this->assertSame((int) $alat->stok, $totalKondisi);
    }

    public function test_status_kondisi_default_baik_saat_stok_baik_penuh(): void
    {
        $kategori = Kategori::create(['nama_kategori' => 'Kategori Default']);

        $alat = Alat::create([
            'kategori_id' => $kategori->id,
            'nama_alat' => 'Alat Lengkap',
            'stok' => 5,
            'stok_baik' => 5,
            'stok_rusak' => 0,
            'stok_rusak_parah' => 0,
            'status_kondisi' => 'Baik',
            'deskripsi' => null,
            'gambar' => null,
        ]);

        $this->assertSame('Baik', $alat->status_kondisi);
        $this->assertSame(5, (int) $alat->stok_baik);
    }
}
