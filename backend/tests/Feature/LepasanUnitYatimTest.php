<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\AlatUnit;
use App\Models\DetailPinjam;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit yatim: kondisi 'dipinjam' tapi tidak punya detail peminjaman.
 * Command app:lepas-unit-yatim meresetnya supaya tidak hilang dari
 * sirkulasi selamanya.
 */
class LepasanUnitYatimTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_reset_unit_yatim_ke_tersedia(): void
    {
        $alat = Alat::factory()->denganUnit(3)->create();
        $unit = $alat->alatUnit()->first();

        // Simulasi yatim: dipinjam tapi detail sudah dihapus.
        $unit->update(['kondisi' => 'dipinjam']);
        $this->assertSame(0, DetailPinjam::count());

        $this->artisan('app:lepas-unit-yatim')
            ->assertSuccessful();

        $this->assertSame('tersedia', $unit->fresh()->kondisi);
        $this->assertSame(3, $alat->alatUnit()->tersedia()->count());
    }

    public function test_command_tidak_sentuh_unit_yang_masih_dipinjam(): void
    {
        $alat = Alat::factory()->denganUnit(2)->create();
        $peminjam = User::factory()->create(['role' => 'peminjam']);
        $unit = $alat->alatUnit()->first();

        $peminjaman = Peminjaman::create([
            'user_id' => $peminjam->id,
            'tgl_pinjam' => now()->toDateString(),
            'tgl_kembali_plan' => now()->addDays(3)->toDateString(),
            'status' => 'dipinjam',
        ]);

        DetailPinjam::create([
            'peminjaman_id' => $peminjaman->id,
            'alat_unit_id' => $unit->id,
        ]);
        $unit->update(['kondisi' => 'dipinjam']);

        $this->artisan('app:lepas-unit-yatim')
            ->assertSuccessful();

        // Masih dipinjam: detail-nya ada, jadi bukan yatim.
        $this->assertSame('dipinjam', $unit->fresh()->kondisi);
    }

    public function test_command_aman_kalau_tidak_ada_yatim(): void
    {
        Alat::factory()->denganUnit(2)->create();

        $this->artisan('app:lepas-unit-yatim')
            ->assertSuccessful()
            ->expectsOutputToContain('Tidak ada unit yatim');
    }

    public function test_unit_dipinjam_dengan_detail_di_peminjaman_lain_tidak_disentuh(): void
    {
        $alat = Alat::factory()->denganUnit(4)->create();
        $unitA = $alat->alatUnit()->skip(1)->first();
        $unitB = $alat->alatUnit()->skip(2)->first();

        $peminjam = User::factory()->create(['role' => 'peminjam']);
        $peminjaman = Peminjaman::create([
            'user_id' => $peminjam->id,
            'tgl_pinjam' => now()->toDateString(),
            'tgl_kembali_plan' => now()->addDays(3)->toDateString(),
            'status' => 'dipinjam',
        ]);

        DetailPinjam::create([
            'peminjaman_id' => $peminjaman->id,
            'alat_unit_id' => $unitA->id,
        ]);
        $unitA->update(['kondisi' => 'dipinjam']);

        // unitB yatim: dipinjam tanpa detail.
        $unitB->update(['kondisi' => 'dipinjam']);

        $this->artisan('app:lepas-unit-yatim')->assertSuccessful();

        $this->assertSame('dipinjam', $unitA->fresh()->kondisi);
        $this->assertSame('tersedia', $unitB->fresh()->kondisi);
    }
}
