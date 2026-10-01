<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\AlatUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KelolaUnitTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_bisa_tambah_unit_dengan_serial_manual(): void
    {
        $alat = Alat::factory()->denganUnit(2)->create();

        $this->actingAs($this->admin())
            ->post(route('admin.alat.unit.store', $alat->id), [
                'serial_number' => ' custom-009 ',
            ])
            ->assertRedirect(route('admin.alat.index'));

        // Serial dinormalisasi: huruf besar + tanpa spasi.
        $this->assertDatabaseHas('alat_unit', [
            'alat_id' => $alat->id,
            'serial_number' => 'CUSTOM-009',
            'kondisi' => 'tersedia',
        ]);
    }

    public function test_serial_kosong_auto_generate_dari_kode_alat(): void
    {
        $alat = Alat::factory()->denganUnit(2)->create(['kode_alat' => 'MK']);

        $this->actingAs($this->admin())
            ->post(route('admin.alat.unit.store', $alat->id), ['serial_number' => '']);

        $this->assertDatabaseHas('alat_unit', [
            'alat_id' => $alat->id,
            'serial_number' => 'MK-003',
        ]);
    }

    public function test_serial_duplikat_ditolak(): void
    {
        $alatA = Alat::factory()->denganUnit(1)->create(['kode_alat' => 'AA']);
        $alatB = Alat::factory()->create(['kode_alat' => 'AA']);

        // AA-001 sudah dipakai alat A.
        $this->actingAs($this->admin())
            ->post(route('admin.alat.unit.store', $alatB->id), ['serial_number' => 'AA-001'])
            ->assertSessionHas('error');

        $this->assertSame(1, AlatUnit::where('serial_number', 'AA-001')->count());
    }

    public function test_tandai_rusak_unit_tersedia(): void
    {
        $alat = Alat::factory()->denganUnit(3)->create();
        $unit = $alat->alatUnit()->tersedia()->first();

        $this->actingAs($this->admin())
            ->post(route('admin.alat.unit.tandaiRusak', $alat->id), [
                'unit_id' => [$unit->id],
            ]);

        $this->assertSame('rusak', $unit->fresh()->kondisi);
        $this->assertSame(2, $alat->alatUnit()->tersedia()->count());
        $this->assertSame(1, $alat->alatUnit()->rusak()->count());
    }

    public function test_unit_dipinjam_tidak_bisa_ditandai_rusak(): void
    {
        $alat = Alat::factory()->denganUnit(2)->create();
        $unit = $alat->alatUnit()->first();
        $unit->update(['kondisi' => 'dipinjam']);

        $this->actingAs($this->admin())
            ->post(route('admin.alat.unit.tandaiRusak', $alat->id), [
                'unit_id' => [$unit->id],
            ])
            ->assertSessionHas('error');

        // Kondisi tidak berubah.
        $this->assertSame('dipinjam', $unit->fresh()->kondisi);
    }

    public function test_unit_milik_alat_lain_tidak_bisa_disentuh(): void
    {
        $alatA = Alat::factory()->denganUnit(2)->create();
        $alatB = Alat::factory()->denganUnit(2)->create();
        $unitB = $alatB->alatUnit()->first();

        $this->actingAs($this->admin())
            ->post(route('admin.alat.unit.tandaiRusak', $alatA->id), [
                'unit_id' => [$unitB->id],
            ])
            ->assertSessionHas('error');

        $this->assertSame('tersedia', $unitB->fresh()->kondisi);
    }

    public function test_perbaiki_unit_rusak_kembali_tersedia(): void
    {
        $alat = Alat::factory()->denganUnit(3)->create();
        $rusak = $alat->alatUnit()->tersedia()->first();
        $rusak->update(['kondisi' => 'rusak']);

        $this->actingAs($this->admin())
            ->post(route('admin.alat.unit.perbaiki', $alat->id), [
                'unit_id' => [$rusak->id],
            ]);

        $this->assertSame('tersedia', $rusak->fresh()->kondisi);
        $this->assertSame(3, $alat->alatUnit()->tersedia()->count());
    }

    public function test_perbaiki_hanya_berpengaruh_ke_unit_rusak(): void
    {
        $alat = Alat::factory()->denganUnit(3)->create();
        $units = $alat->alatUnit()->get();
        $units[0]->update(['kondisi' => 'rusak']);
        $units[1]->update(['kondisi' => 'dipinjam']);

        // Unit dipinjam sengaja ikut dikirim untuk memastikan di-skip,
        // bukan di-override jadi tersedia.
        $this->actingAs($this->admin())
            ->post(route('admin.alat.unit.perbaiki', $alat->id), [
                'unit_id' => [$units[0]->id, $units[1]->id],
            ])
            ->assertSessionHas('success');

        $this->assertSame('tersedia', $units[0]->fresh()->kondisi);
        // Unit dipinjam tetap dipinjam, tidak terpengaruh.
        $this->assertSame('dipinjam', $units[1]->fresh()->kondisi);
    }

    public function test_aksi_unit_hanya_bisa_oleh_admin(): void
    {
        $alat = Alat::factory()->denganUnit(1)->create();
        $petugas = User::factory()->create(['role' => 'petugas']);

        $this->actingAs($petugas)
            ->post(route('admin.alat.unit.store', $alat->id))
            ->assertStatus(403);
    }
}
