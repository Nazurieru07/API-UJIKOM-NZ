<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\AlatUnit;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlatKondisiTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /** Alat dengan N unit, semua kondisi 'tersedia'. */
    private function alatDenganUnit(int $jumlah): Alat
    {
        return Alat::factory()->denganUnit($jumlah)->create();
    }

    public function test_alat_memiliki_n_unit_tersedia(): void
    {
        $alat = $this->alatDenganUnit(5);

        $this->assertSame(5, $alat->alatUnit()->count());
        $this->assertSame(5, $alat->unitTersedia()->count());
        $this->assertSame(5, $alat->alatUnit()->tersedia()->count());
    }

    public function test_alat_tanpa_unit_tidak_memiliki_unit_tersedia(): void
    {
        $alat = Alat::factory()->create();

        $this->assertSame(0, $alat->alatUnit()->count());
        $this->assertSame(0, $alat->unitTersedia()->count());
        $this->assertSame(0, $alat->unitRusak()->count());
    }

    public function test_satu_unit_jadi_rusak_mengurangi_unit_tersedia(): void
    {
        $alat = $this->alatDenganUnit(3);
        $unit = $alat->alatUnit()->first();

        $unit->update(['kondisi' => 'rusak']);

        $alat->refresh();

        $this->assertSame(2, $alat->unitTersedia()->count());
        $this->assertSame(1, $alat->unitRusak()->count());
        $this->assertSame(3, $alat->alatUnit()->count());

        // Serial yang dirusak tetap ada, hanya berpindih kondisi.
        $this->assertSame('rusak', $unit->fresh()->kondisi);
        $this->assertDatabaseHas('alat_unit', [
            'id' => $unit->id,
            'serial_number' => $unit->serial_number,
            'kondisi' => 'rusak',
        ]);
    }

    public function test_satu_unit_dipinjam_mengurangi_unit_tersedia(): void
    {
        $alat = $this->alatDenganUnit(2);
        $unit = $alat->alatUnit()->first();

        $unit->update(['kondisi' => 'dipinjam']);

        $this->assertSame(1, $alat->fresh()->unitTersedia()->count());
        $this->assertSame(1, $alat->fresh()->unitDipinjam()->count());
        $this->assertSame(0, $alat->fresh()->unitRusak()->count());
    }

    public function test_unit_rusak_diperbaiki_kembali_muncul_di_tersedia(): void
    {
        $alat = $this->alatDenganUnit(2);
        $unit = $alat->alatUnit()->first();
        $unit->update(['kondisi' => 'rusak']);

        $this->assertSame(1, $alat->fresh()->unitTersedia()->count());

        $unit->update(['kondisi' => 'tersedia']);

        $this->assertSame(2, $alat->fresh()->unitTersedia()->count());
        $this->assertSame(0, $alat->fresh()->unitRusak()->count());
    }

    public function test_katalog_filter_hanya_alat_yang_punya_unit_tersedia(): void
    {
        $adaUnit = $this->alatDenganUnit(2);
        $tanpaUnit = Alat::factory()->create();
        $semuaRusak = $this->alatDenganUnit(1);
        $semuaRusak->alatUnit()->update(['kondisi' => 'rusak']);

        $hasil = Alat::tidakTerarsip()
            ->whereHas('alatUnit', fn ($q) => $q->where('kondisi', 'tersedia'))
            ->pluck('id');

        $this->assertTrue($hasil->contains($adaUnit->id));
        $this->assertFalse($hasil->contains($tanpaUnit->id));
        $this->assertFalse($hasil->contains($semuaRusak->id));
    }

    public function test_katalog_menghitung_unit_tersedia_dengan_with_count(): void
    {
        $alat = $this->alatDenganUnit(3);
        $alat->alatUnit()->first()->update(['kondisi' => 'rusak']);

        $hasil = Alat::withCount(['alatUnit as jumlah_unit_tersedia' => fn ($q) => $q->where('kondisi', 'tersedia')])
            ->whereKey($alat->id)
            ->first();

        $this->assertSame(2, (int) $hasil->jumlah_unit_tersedia);
    }

    public function test_petugas_tidak_bisa_setujui_peminjaman_dua_kali(): void
    {
        $petugas = User::factory()->create(['role' => 'petugas']);
        $peminjam = User::factory()->create(['role' => 'peminjam']);
        $alat = $this->alatDenganUnit(1);
        $unit = $alat->alatUnit()->first();

        $peminjaman = Peminjaman::create([
            'user_id' => $peminjam->id,
            'tgl_pinjam' => now(),
            'tgl_kembali_plan' => now()->addDays(3),
            'status' => 'diajukan',
        ]);
        $peminjaman->detailPinjams()->create(['alat_unit_id' => $unit->id]);

        // Approve pertama: claim unit secara atomic.
        $affected = AlatUnit::where('id', $unit->id)
            ->where('kondisi', 'tersedia')
            ->update(['kondisi' => 'dipinjam']);

        $this->assertSame(1, $affected);
        $this->assertSame('dipinjam', $unit->fresh()->kondisi);
        $this->assertSame(0, $alat->fresh()->unitTersedia()->count());

        // Approve kedua petugas lain: unit sudah tidak tersedia, harus gagal.
        $affected = AlatUnit::where('id', $unit->id)
            ->where('kondisi', 'tersedia')
            ->update(['kondisi' => 'dipinjam']);

        $this->assertSame(0, $affected);
        $this->assertSame('dipinjam', $unit->fresh()->kondisi);
        $this->assertSame(0, $alat->fresh()->unitTersedia()->count());
    }

    public function test_admin_dapat_melihat_halaman_kelola_unit_alat(): void
    {
        $admin = $this->admin();
        $alat = $this->alatDenganUnit(2);

        $response = $this->actingAs($admin)->get(route('admin.alat.index'));

        $response->assertStatus(200);
        $response->assertSee($alat->nama_alat);
    }
}
