<?php

namespace Tests\Unit;

use App\Models\Alat;
use App\Models\AlatUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlatModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_saran_kode_alat_dua_kata_ambil_inisial(): void
    {
        $this->assertSame('RM', Alat::saranKodeAlat('Router Mikrotik'));
        $this->assertSame('KC', Alat::saranKodeAlat('Kamera Canon'));
    }

    public function test_saran_kode_alat_satu_kata_ambil_dua_huruf(): void
    {
        $this->assertSame('TE', Alat::saranKodeAlat('Teleporter'));
        $this->assertSame('SW', Alat::saranKodeAlat('Switch'));
    }

    public function test_saran_kode_alat_nama_kosong_kembalikan_default(): void
    {
        $this->assertSame('AL', Alat::saranKodeAlat(''));
        $this->assertSame('AL', Alat::saranKodeAlat('   '));
    }

    public function test_factory_dengan_unit_membuat_n_unit_tersedia(): void
    {
        $alat = Alat::factory()->denganUnit(4)->create();

        $this->assertSame(4, $alat->alatUnit()->count());
        $this->assertSame(4, $alat->alatUnit()->tersedia()->count());
        $this->assertSame(0, $alat->alatUnit()->dipinjam()->count());
        $this->assertSame(0, $alat->alatUnit()->rusak()->count());

        // Serial dibuat berurutan mulai dari 001.
        $serials = $alat->alatUnit()->orderBy('serial_number')->pluck('serial_number');
        $this->assertSame($alat->kode_alat.'-001', $serials->first());
        $this->assertSame($alat->kode_alat.'-004', $serials->last());
    }

    public function test_serial_berikutnya_melanjutkan_nomor_terbesar(): void
    {
        $alat = Alat::factory()->denganUnit(2)->create();

        // Hapus unit pertama: count() jadi 1, tapi nomor terbesar tetap 2,
        // jadi serial baru tidak bentrok dengan unit yang tersisa.
        $alat->alatUnit()->orderBy('serial_number')->first()->delete();

        $berikutnya = AlatUnit::serialBerikutnya($alat->id, $alat->kode_alat);

        $this->assertSame($alat->kode_alat.'-003', $berikutnya);
        $this->assertSame(1, $alat->alatUnit()->count());
    }

    public function test_serial_berikutnya_saat_belum_ada_unit(): void
    {
        $alat = Alat::factory()->create(['kode_alat' => 'ZZ']);

        $this->assertSame('ZZ-001', AlatUnit::serialBerikutnya($alat->id, 'ZZ'));
    }

    public function test_scope_unit_tersedia_dipinjam_rusak_memfilter_kondisi(): void
    {
        $alat = Alat::factory()->denganUnit(3)->create();

        $units = $alat->alatUnit()->orderBy('serial_number')->get();
        $units[0]->update(['kondisi' => 'rusak']);
        $units[1]->update(['kondisi' => 'dipinjam']);

        $this->assertSame(1, $alat->unitTersedia()->count());
        $this->assertSame(1, $alat->unitDipinjam()->count());
        $this->assertSame(1, $alat->unitRusak()->count());
        $this->assertSame(3, $alat->alatUnit()->count());

        // Shortcut relation wajib konsisten dengan scope di AlatUnit.
        $this->assertSame(
            $alat->alatUnit()->tersedia()->pluck('id')->all(),
            $alat->unitTersedia()->pluck('id')->all()
        );

        $this->assertSame('tersedia', $alat->unitTersedia()->first()->kondisi);
        $this->assertSame('dipinjam', $alat->unitDipinjam()->first()->kondisi);
        $this->assertSame('rusak', $alat->unitRusak()->first()->kondisi);
    }

    public function test_alat_unit_is_helper_mencerminkan_kondisi(): void
    {
        $alat = Alat::factory()->denganUnit(1)->create();
        $unit = $alat->alatUnit()->first();

        $this->assertTrue($unit->isTersedia());
        $this->assertFalse($unit->isDipinjam());
        $this->assertFalse($unit->isRusak());

        $unit->update(['kondisi' => 'rusak']);
        $unit->refresh();

        $this->assertTrue($unit->isRusak());
        $this->assertFalse($unit->isTersedia());
    }
}
