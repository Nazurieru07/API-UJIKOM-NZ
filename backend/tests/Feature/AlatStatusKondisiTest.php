<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\Kategori;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlatStatusKondisiTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_kondisi_mayoritas_dihitung_dari_stok(): void
    {
        $this->assertSame('Baik', Alat::kondisiMayoritas(10, 0, 0));
        $this->assertSame('Rusak', Alat::kondisiMayoritas(2, 8, 0));
        $this->assertSame('Rusak Parah', Alat::kondisiMayoritas(1, 2, 7));
    }

    public function test_alat_baru_selalu_kondisi_baik(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post(route('admin.alat.store'), [
            'nama_alat' => 'Alat Test Kondisi',
            'kategori_id' => Kategori::factory()->create()->id,
            'stok' => 5,
            'deskripsi' => 'Test',
        ]);

        $response->assertRedirect(route('admin.alat.index'));

        $alat = Alat::where('nama_alat', 'Alat Test Kondisi')->first();

        $this->assertNotNull($alat);
        $this->assertSame('Baik', $alat->status_kondisi);
        $this->assertSame(5, (int) $alat->stok_baik);
        $this->assertSame(0, (int) $alat->stok_rusak);
        $this->assertSame(0, (int) $alat->stok_rusak_parah);
    }

    public function test_edit_alat_tidak_mengubah_status_kondisi_manual(): void
    {
        $admin = $this->admin();

        $alat = Alat::factory()->create([
            'stok' => 10,
            'stok_baik' => 2,
            'stok_rusak' => 8,
            'stok_rusak_parah' => 0,
            'status_kondisi' => 'Rusak',
        ]);

        // Admin nyoba set manual ke Baik via form edit
        $this->actingAs($admin)->put(route('admin.alat.update', $alat->id), [
            'nama_alat' => $alat->nama_alat,
            'kategori_id' => $alat->kategori_id,
            'stok' => 10,
            'status_kondisi' => 'Baik',
            'deskripsi' => $alat->deskripsi,
        ]);

        $alat->refresh();

        // Kondisi mayoritas tetap Rusak (8 > 2)
        $this->assertSame('Rusak', $alat->status_kondisi);
        $this->assertSame(2, (int) $alat->stok_baik);
        $this->assertSame(8, (int) $alat->stok_rusak);
    }

    public function test_edit_alat_status_kondisi_diupdate_saat_stok_berubah(): void
    {
        $admin = $this->admin();

        $alat = Alat::factory()->create([
            'stok' => 10,
            'stok_baik' => 2,
            'stok_rusak' => 8,
            'stok_rusak_parah' => 0,
            'status_kondisi' => 'Rusak',
        ]);

        // Admin ubah stok jadi 10 (semua baik, rusak tetap 0 krn dari updateAlat:
        // stok_baik = stok_baru - total_rusak)
        $this->actingAs($admin)->put(route('admin.alat.update', $alat->id), [
            'nama_alat' => $alat->nama_alat,
            'kategori_id' => $alat->kategori_id,
            'stok' => 18,
            'deskripsi' => $alat->deskripsi,
        ]);

        $alat->refresh();

        // stok_baik = 18 - 8 = 10, stok_rusak = 8 -> mayoritas Baik
        $this->assertSame(10, (int) $alat->stok_baik);
        $this->assertSame(8, (int) $alat->stok_rusak);
        $this->assertSame('Baik', $alat->status_kondisi);
    }
}
