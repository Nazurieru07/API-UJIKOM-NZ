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
 * Hapus unit dari Kelola Unit + aturan hapus alat.
 *
 * Dua bug yang dilaporkan user:
 * 1. Tidak ada tombol hapus unit di modal Kelola Unit -- admin tak bisa
 *    mengurangi unit kalau salah input.
 * 2. Alat yang BARU dibuat (unit otomatis dari form tambah alat) langsung
 *    diarsipkan saat dihapus, walau belum pernah dipinjam. Penyebabnya
 *    syarat "punya unit" -- padahal unit itu stok inventaris, bukan
 *    riwayat peminjaman.
 */
class HapusUnitDanAlatTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    // ---- Tombol / route hapus unit ----

    public function test_admin_bisa_hapus_unit_yang_belum_dipinjam(): void
    {
        $alat = Alat::factory()->denganUnit(3)->create();
        $unit = $alat->alatUnit()->first();

        $this->actingAs($this->admin())
            ->delete(route('admin.alat.unit.hapus', [$alat->id, $unit->id]))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('alat_unit', ['id' => $unit->id]);
        $this->assertSame(2, $alat->alatUnit()->count());
    }

    public function test_unit_dipinjam_tidak_bisa_dihapus(): void
    {
        $alat = Alat::factory()->denganUnit(2)->create();
        $unit = $alat->alatUnit()->first();
        $unit->update(['kondisi' => 'dipinjam']);

        $this->actingAs($this->admin())
            ->delete(route('admin.alat.unit.hapus', [$alat->id, $unit->id]))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('alat_unit', ['id' => $unit->id]);
    }

    public function test_unit_yang_pernah_dipinjam_tidak_bisa_dihapus(): void
    {
        $alat = Alat::factory()->denganUnit(2)->create();
        $unit = $alat->alatUnit()->first();

        $peminjaman = Peminjaman::create([
            'user_id' => User::factory()->create(['role' => 'peminjam'])->id,
            'tgl_pinjam' => now()->subDays(5)->toDateString(),
            'tgl_kembali_plan' => now()->subDays(2)->toDateString(),
            'status' => 'dikembalikan',
        ]);

        DetailPinjam::create([
            'peminjaman_id' => $peminjaman->id,
            'alat_unit_id' => $unit->id,
        ]);

        // Kondisi sudah kembali ke tersedia, tapi riwayatnya ada.
        $unit->update(['kondisi' => 'tersedia']);

        $this->actingAs($this->admin())
            ->delete(route('admin.alat.unit.hapus', [$alat->id, $unit->id]))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('alat_unit', ['id' => $unit->id]);
    }

    public function test_unit_milik_alat_lain_tidak_bisa_dihapus(): void
    {
        $alatA = Alat::factory()->denganUnit(2)->create();
        $alatB = Alat::factory()->denganUnit(2)->create();
        $unitB = $alatB->alatUnit()->first();

        $this->actingAs($this->admin())
            ->delete(route('admin.alat.unit.hapus', [$alatA->id, $unitB->id]))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('alat_unit', ['id' => $unitB->id]);
    }

    public function test_hapus_unit_hanya_bisa_oleh_admin(): void
    {
        $alat = Alat::factory()->denganUnit(1)->create();
        $unit = $alat->alatUnit()->first();
        $petugas = User::factory()->create(['role' => 'petugas']);

        $this->actingAs($petugas)
            ->delete(route('admin.alat.unit.hapus', [$alat->id, $unit->id]))
            ->assertStatus(403);
    }

    public function test_halaman_kelola_unit_punya_tombol_hapus(): void
    {
        $kategori = \App\Models\Kategori::factory()->create();
        $alat = Alat::factory()->denganUnit(2)->create(['kategori_id' => $kategori->id]);

        $body = $this->actingAs($this->admin())
            ->get(route('admin.alat.index'))
            ->assertStatus(200)
            ->getContent();

        // Blade merender nama route jadi URL, jadi cek URL delete-nya.
        $this->assertStringContainsString(
            route('admin.alat.unit.hapus', [$alat->id, $alat->alatUnit()->first()->id]),
            $body
        );
    }

    // ---- Aturan hapus alat ----

    public function test_alat_baru_yang_belum_dipinjam_bisa_dihapus(): void
    {
        // Alat dibuat lewat form tambah alat: selalu dapat unit otomatis,
        // tapi belum pernah dipinjam.
        $alat = Alat::factory()->denganUnit(5)->create();
        $this->assertSame(5, $alat->alatUnit()->count());

        $this->actingAs($this->admin())
            ->delete(route('admin.alat.destroy', $alat->id))
            ->assertRedirect(route('admin.alat.index'));

        // Regression guard: sebelumnya alat ini diarsipkan karena punya
        // unit, padahal belum pernah dipinjam.
        $this->assertDatabaseMissing('alat', ['id' => $alat->id]);
        $this->assertDatabaseMissing('alat_unit', ['alat_id' => $alat->id]);
    }

    public function test_alat_yang_pernah_dipinjam_diarsipkan_bukan_dihapus(): void
    {
        $alat = Alat::factory()->denganUnit(2)->create();
        $unit = $alat->alatUnit()->first();

        $peminjaman = Peminjaman::create([
            'user_id' => User::factory()->create(['role' => 'peminjam'])->id,
            'tgl_pinjam' => now()->subDays(5)->toDateString(),
            'tgl_kembali_plan' => now()->subDays(2)->toDateString(),
            'status' => 'dikembalikan',
        ]);

        DetailPinjam::create([
            'peminjaman_id' => $peminjaman->id,
            'alat_unit_id' => $unit->id,
        ]);

        $this->actingAs($this->admin())
            ->delete(route('admin.alat.destroy', $alat->id))
            ->assertRedirect(route('admin.alat.index'));

        $this->assertDatabaseHas('alat', [
            'id' => $alat->id,
            'is_arsip' => true,
        ]);
        // Unit ikut tertinggal supaya laporan lama tetap utuh.
        $this->assertDatabaseHas('alat_unit', ['alat_id' => $alat->id]);
    }

    public function test_alat_terarsip_tidak_muncul_di_katalog(): void
    {
        $alat = Alat::factory()->denganUnit(2)->create(['is_arsip' => true]);

        $this->actingAs(User::factory()->create(['role' => 'peminjam']))
            ->get(route('peminjam.katalog'))
            ->assertStatus(200)
            ->assertDontSee($alat->nama_alat);
    }
}
