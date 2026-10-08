<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\DetailPinjam;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiPeminjamanResourceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Regression test untuk bug K-01 (audit 2026-10-07).
     *
     * PeminjamanResource memakai whenLoaded('detailPinjam') padahal nama
     * relasi di model Peminjaman adalah 'detailPinjams' (jamak). Akibatnya
     * field item_dipinjam dihapus dari response API seluruhnya.
     */
    public function test_response_peminjaman_memuat_daftar_unit_dipinjam(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin, ['*']);

        $peminjam = User::factory()->create(['role' => 'peminjam']);

        $alat = Alat::factory()->denganUnit(2)->create();
        $units = $alat->alatUnit()->orderBy('id')->limit(2)->get();

        $peminjaman = Peminjaman::create([
            'user_id' => $peminjam->id,
            'tgl_pinjam' => now()->toDateString(),
            'tgl_kembali_plan' => now()->addDays(3)->toDateString(),
            'status' => 'diajukan',
        ]);

        foreach ($units as $unit) {
            DetailPinjam::create([
                'peminjaman_id' => $peminjaman->id,
                'alat_unit_id' => $unit->id,
            ]);
        }

        $response = $this->getJson('/api/peminjaman/' . $peminjaman->id);

        $response->assertOk();

        // Sebelum fix: key item_dipinjam tidak ada sama sekali di response.
        $response->assertJsonCount(2, 'data.item_dipinjam');
        $response->assertJsonPath('data.item_dipinjam.0.serial_number', $units[0]->serial_number);
        $response->assertJsonPath('data.item_dipinjam.0.nama_alat', $alat->nama_alat);
        $response->assertJsonPath('data.item_dipinjam.1.serial_number', $units[1]->serial_number);
    }
}
