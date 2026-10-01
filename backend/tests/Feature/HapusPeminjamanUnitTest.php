<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\DetailPinjam;
use App\Models\Peminjaman;
use App\Models\Pengembalian;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BUG 4: hapus peminjaman harus mengembalikan unit ke 'tersedia'.
 * User melaporkan unit ffg tetap "dipinjam" dan jumlah tersedia
 * tidak bertambah setelah peminjaman dihapus dari menu Kelola Peminjam.
 */
class HapusPeminjamanUnitTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function peminjamanDipinjamDenganUnit(Alat $alat): Peminjaman
    {
        $peminjam = User::factory()->create(['role' => 'peminjam']);
        $unit = $alat->alatUnit()->tersedia()->first();

        $peminjaman = Peminjaman::create([
            'user_id' => $peminjam->id,
            'tgl_pinjam' => now()->subDays(2)->toDateString(),
            'tgl_kembali_plan' => now()->addDays(3)->toDateString(),
            'status' => 'dipinjam',
        ]);

        DetailPinjam::create([
            'peminjaman_id' => $peminjaman->id,
            'alat_unit_id' => $unit->id,
        ]);

        $unit->update(['kondisi' => 'dipinjam']);

        return $peminjaman->fresh();
    }

    public function test_hapus_peminjaman_kembalikan_unit_ke_tersedia(): void
    {
        $alat = Alat::factory()->denganUnit(3)->create();
        $peminjaman = $this->peminjamanDipinjamDenganUnit($alat);

        $unit = $alat->alatUnit()->where('kondisi', 'dipinjam')->first();
        $this->assertNotNull($unit);
        $this->assertSame(2, $alat->alatUnit()->tersedia()->count());

        $this->actingAs($this->admin())
            ->delete(route('admin.peminjaman.destroy', $peminjaman->id))
            ->assertRedirect(route('admin.peminjaman.index'));

        $this->assertSame('tersedia', $unit->fresh()->kondisi);
        $this->assertSame(3, $alat->alatUnit()->tersedia()->count());
        $this->assertSame(0, $alat->alatUnit()->dipinjam()->count());
    }

    public function test_hapus_peminjaman_telat_juga_kembalikan_unit(): void
    {
        $alat = Alat::factory()->denganUnit(2)->create();
        $peminjaman = $this->peminjamanDipinjamDenganUnit($alat);
        $peminjaman->update(['status' => 'telat']);

        $unit = $alat->alatUnit()->where('kondisi', 'dipinjam')->first();

        $this->actingAs($this->admin())
            ->delete(route('admin.peminjaman.destroy', $peminjaman->id));

        $this->assertSame('tersedia', $unit->fresh()->kondisi);
    }

    public function test_hapus_peminjaman_yang_sudah_punya_pengembalian_tidak_ubah_kondisi_unit(): void
    {
        $alat = Alat::factory()->denganUnit(2)->create();
        $peminjaman = $this->peminjamanDipinjamDenganUnit($alat);

        $petugas = User::factory()->create(['role' => 'petugas']);
        Pengembalian::create([
            'peminjaman_id' => $peminjaman->id,
            'tgl_kembali' => now()->toDateString(),
            'kondisi_kembali' => 'Rusak',
            'denda' => 0,
            'denda_kerusakan' => 0,
            'petugas_id' => $petugas->id,
            'status_request' => 'disetujui',
        ]);

        $unit = $alat->alatUnit()->where('kondisi', 'dipinjam')->first();

        $this->actingAs($this->admin())
            ->delete(route('admin.peminjaman.destroy', $peminjaman->id));

        // Unit dipinjam + sudah ada pengembalian disetujui: kondisi
        // dibiarkan, pengembalian yang memegang jawab. Regression guard
        // supaya fix bug 4 tidak merusak jalur ini.
        $this->assertSame('dipinjam', $unit->fresh()->kondisi);
    }

    public function test_hapus_peminjaman_banyak_unit_semua_kembali(): void
    {
        $alat = Alat::factory()->denganUnit(5)->create();
        $peminjam = User::factory()->create(['role' => 'peminjam']);

        $peminjaman = Peminjaman::create([
            'user_id' => $peminjam->id,
            'tgl_pinjam' => now()->toDateString(),
            'tgl_kembali_plan' => now()->addDays(3)->toDateString(),
            'status' => 'dipinjam',
        ]);

        $units = $alat->alatUnit()->tersedia()->take(3)->get();

        foreach ($units as $unit) {
            DetailPinjam::create([
                'peminjaman_id' => $peminjaman->id,
                'alat_unit_id' => $unit->id,
            ]);
            $unit->update(['kondisi' => 'dipinjam']);
        }

        $this->actingAs($this->admin())
            ->delete(route('admin.peminjaman.destroy', $peminjaman->id));

        $this->assertSame(5, $alat->alatUnit()->tersedia()->count());
        $this->assertSame(0, $alat->alatUnit()->dipinjam()->count());
    }
}
