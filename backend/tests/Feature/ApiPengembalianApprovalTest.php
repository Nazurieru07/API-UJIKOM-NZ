<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\AlatUnit;
use App\Models\DetailPinjam;
use App\Models\Peminjaman;
use App\Models\Pengembalian;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiPengembalianApprovalTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Fixture: satu peminjaman berstatus 'dipinjam' dengan 2 unit
     * yang sudah ditandai 'dipinjam' (seolah-olah sudah di-approve).
     */
    private function peminjamanDipinjam(): array
    {
        $peminjam = User::factory()->create(['role' => 'peminjam']);
        $petugas = User::factory()->create(['role' => 'petugas']);
        $admin = User::factory()->create(['role' => 'admin']);

        $alat = Alat::factory()->denganUnit(2)->create();
        $units = $alat->alatUnit()->orderBy('id')->limit(2)->get();

        $peminjaman = Peminjaman::create([
            'user_id' => $peminjam->id,
            'tgl_pinjam' => now()->toDateString(),
            'tgl_kembali_plan' => now()->addDays(3)->toDateString(),
            'status' => 'dipinjam',
        ]);

        foreach ($units as $unit) {
            DetailPinjam::create([
                'peminjaman_id' => $peminjaman->id,
                'alat_unit_id' => $unit->id,
            ]);

            $unit->update(['kondisi' => 'dipinjam']);
        }

        return [$peminjaman, $units, $petugas, $admin];
    }

    /**
     * Pengajuan petugas tidak boleh langsung memproses: unit tetap
     * 'dipinjam' dan status peminjaman tetap 'dipinjam' sampai admin
     * menyetujui.
     */
    public function test_ajukan_pengembalian_tidak_membebaskan_unit(): void
    {
        [$peminjaman, $units, $petugas] = $this->peminjamanDipinjam();
        Sanctum::actingAs($petugas, ['*']);

        $response = $this->postJson('/api/pengembalian', [
            'peminjaman_id' => $peminjaman->id,
            'kondisi_kembali' => 'Baik',
            'denda_kerusakan' => 0,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.status_request', 'menunggu');

        // Belum boleh ada perubahan unit / peminjaman.
        $units->each(fn ($u) => $this->assertSame('dipinjam', $u->fresh()->kondisi));
        $this->assertSame('dipinjam', $peminjaman->fresh()->status);
        $this->assertSame(0, (int) $response->json('data.denda'));
    }

    /**
     * Kondisi di luar 'Baik'/'Rusak' ditolak -- sebelumnya bebas teks.
     */
    public function test_kondisi_di_luar_baik_dan_rusak_ditolak(): void
    {
        [$peminjaman, , $petugas] = $this->peminjamanDipinjam();
        Sanctum::actingAs($petugas, ['*']);

        $response = $this->postJson('/api/pengembalian', [
            'peminjaman_id' => $peminjaman->id,
            'kondisi_kembali' => 'Rusak Ringan',
            'denda_kerusakan' => 5000,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('kondisi_kembali');
        $this->assertDatabaseMissing('pengembalian', ['peminjaman_id' => $peminjaman->id]);
    }

    /**
     * Setuju admin: unit kembali, status peminjaman selesai.
     */
    public function test_approve_admin_membebaskan_unit(): void
    {
        [$peminjaman, $units, , $admin] = $this->peminjamanDipinjam();

        $pengembalian = Pengembalian::create([
            'peminjaman_id' => $peminjaman->id,
            'tgl_kembali' => now()->toDateString(),
            'kondisi_kembali' => 'Baik',
            'denda' => 0,
            'denda_kerusakan' => 0,
            'petugas_id' => User::factory()->create(['role' => 'petugas'])->id,
            'status_request' => 'menunggu',
        ]);

        Sanctum::actingAs($admin, ['*']);

        $response = $this->postJson('/api/pengembalian/' . $pengembalian->id . '/approve');

        $response->assertOk();
        $response->assertJsonPath('data.status_request', 'disetujui');

        $units->each(fn ($u) => $this->assertSame('tersedia', $u->fresh()->kondisi));
        $this->assertSame('dikembalikan', $peminjaman->fresh()->status);
    }

    /**
     * Kondisi 'Rusak' menyembunyikan unit dari katalog.
     */
    public function test_approbe_rusak_menyembunyikan_unit(): void
    {
        [$peminjaman, $units, , $admin] = $this->peminjamanDipinjam();

        $pengembalian = Pengembalian::create([
            'peminjaman_id' => $peminjaman->id,
            'tgl_kembali' => now()->toDateString(),
            'kondisi_kembali' => 'Rusak',
            'denda' => 0,
            'denda_kerusakan' => 10000,
            'petugas_id' => User::factory()->create(['role' => 'petugas'])->id,
            'status_request' => 'menunggu',
        ]);

        Sanctum::actingAs($admin, ['*']);

        $this->postJson('/api/pengembalian/' . $pengembalian->id . '/approve')
            ->assertOk();

        $units->each(fn ($u) => $this->assertSame('rusak', $u->fresh()->kondisi));
    }

    /**
     * Approve dua kali = idempotensi rusak, harus ditolak.
     */
    public function test_approve_dua_kali_ditolak(): void
    {
        [$peminjaman, , , $admin] = $this->peminjamanDipinjam();

        $petugas = User::factory()->create(['role' => 'petugas']);
        $pengembalian = Pengembalian::create([
            'peminjaman_id' => $peminjaman->id,
            'tgl_kembali' => now()->toDateString(),
            'kondisi_kembali' => 'Baik',
            'denda' => 0,
            'denda_kerusakan' => 0,
            'petugas_id' => $petugas->id,
            'status_request' => 'menunggu',
        ]);

        Sanctum::actingAs($admin, ['*']);

        $this->postJson('/api/pengembalian/' . $pengembalian->id . '/approve')->assertOk();

        // Approve kedua harus gagal, bukan menambah denda atau
        // mengembalikan unit dua kali.
        $response = $this->postJson('/api/pengembalian/' . $pengembalian->id . '/approve');
        $response->assertStatus(422);
        $response->assertJsonPath('message', 'Pengajuan pengembalian ini sudah diproses.');
    }

    /**
     * Tolak admin: status 'ditolak', unit tetap dipinjam.
     */
    public function test_reject_admin_menjaga_unit_dipinjam(): void
    {
        [$peminjaman, $units, , $admin] = $this->peminjamanDipinjam();

        $petugas = User::factory()->create(['role' => 'petugas']);
        $pengembalian = Pengembalian::create([
            'peminjaman_id' => $peminjaman->id,
            'tgl_kembali' => now()->toDateString(),
            'kondisi_kembali' => 'Baik',
            'denda' => 0,
            'denda_kerusakan' => 0,
            'petugas_id' => $petugas->id,
            'status_request' => 'menunggu',
        ]);

        Sanctum::actingAs($admin, ['*']);

        $response = $this->postJson('/api/pengembalian/' . $pengembalian->id . '/reject');
        $response->assertOk();
        $response->assertJsonPath('data.status_request', 'ditolak');

        $units->each(fn ($u) => $this->assertSame('dipinjam', $u->fresh()->kondisi));
        $this->assertSame('dipinjam', $peminjaman->fresh()->status);
        $this->assertSame(0, (int) $response->json('data.denda'));
    }

    /**
     * Approve menghitung denda keterlambatan dari config, bukan input.
     */
    public function test_approve_menghitung_denda_keterlambatan(): void
    {
        $peminjam = User::factory()->create(['role' => 'peminjam']);
        $petugas = User::factory()->create(['role' => 'petugas']);
        $admin = User::factory()->create(['role' => 'admin']);

        $alat = Alat::factory()->denganUnit(1)->create();
        $unit = $alat->alatUnit()->first();

        // Rencana kembali 5 hari yang lalu -> terlambat.
        $peminjaman = Peminjaman::create([
            'user_id' => $peminjam->id,
            'tgl_pinjam' => now()->subDays(10)->toDateString(),
            'tgl_kembali_plan' => now()->subDays(5)->toDateString(),
            'status' => 'telat',
        ]);

        DetailPinjam::create([
            'peminjaman_id' => $peminjaman->id,
            'alat_unit_id' => $unit->id,
        ]);
        $unit->update(['kondisi' => 'dipinjam']);

        $pengembalian = Pengembalian::create([
            'peminjaman_id' => $peminjaman->id,
            'tgl_kembali' => now()->toDateString(),
            'kondisi_kembali' => 'Baik',
            'denda' => 0,
            'denda_kerusakan' => 0,
            'petugas_id' => $petugas->id,
            'status_request' => 'menunggu',
        ]);

        Sanctum::actingAs($admin, ['*']);

        $response = $this->postJson('/api/pengembalian/' . $pengembalian->id . '/approve');
        $response->assertOk();

        $tarif = (int) config('denda.keterlambatan_per_hari');
        $expected = 5 * $tarif;
        $this->assertSame($expected, (int) $response->json('data.denda'));
        $this->assertSame($expected, (int) $pengembalian->fresh()->denda);
    }

    /**
     * Petugas tidak boleh approve sendiri pengajuannya.
     */
    public function test_petugas_tidak_bisa_approve(): void
    {
        [$peminjaman, , $petugas] = $this->peminjamanDipinjam();

        $pengembalian = Pengembalian::create([
            'peminjaman_id' => $peminjaman->id,
            'tgl_kembali' => now()->toDateString(),
            'kondisi_kembali' => 'Baik',
            'denda' => 0,
            'denda_kerusakan' => 0,
            'petugas_id' => $petugas->id,
            'status_request' => 'menunggu',
        ]);

        Sanctum::actingAs($petugas, ['*']);

        $this->postJson('/api/pengembalian/' . $pengembalian->id . '/approve')
            ->assertForbidden();
    }
}
