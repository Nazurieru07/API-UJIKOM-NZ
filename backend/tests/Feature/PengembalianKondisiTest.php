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
 * Pengajuan pengembalian oleh petugas.
 *
 * Domain kondisi_kembali hanya dua nilai sejak migrasi 144000
 * (Baik / Rusak -- Rusak Ringan dan Rusak Berat dinormalisasi jadi Rusak).
 * Kalau rule validasi masih menerima nilai lama, pilihan "Rusak" dari
 * form selalu ditolak: submit memantul balik ke form tanpa pesan yang
 * terlihat, yaitu bug "looping" yang dilaporkan user.
 */
class PengembalianKondisiTest extends TestCase
{
    use RefreshDatabase;

    private function petugas(): User
    {
        return User::factory()->create(['role' => 'petugas']);
    }

    private function peminjamanAktif(): Peminjaman
    {
        $alat = Alat::factory()->denganUnit(2)->create();
        $unit = $alat->alatUnit()->first();
        $unit->update(['kondisi' => 'dipinjam']);

        $peminjaman = Peminjaman::create([
            'user_id' => User::factory()->create(['role' => 'peminjam'])->id,
            'tgl_pinjam' => now()->subDay()->toDateString(),
            'tgl_kembali_plan' => now()->addDays(3)->toDateString(),
            'status' => 'dipinjam',
        ]);

        DetailPinjam::create([
            'peminjaman_id' => $peminjaman->id,
            'alat_unit_id' => $unit->id,
        ]);

        return $peminjaman;
    }

    public function test_pengajuan_kondisi_baik_diterima(): void
    {
        $peminjaman = $this->peminjamanAktif();

        $this->actingAs($this->petugas())
            ->post(route('petugas.pengembalian.ajukan', $peminjaman->id), [
                'kondisi_kembali' => 'Baik',
                'denda_kerusakan' => 0,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('pengembalian', [
            'peminjaman_id' => $peminjaman->id,
            'kondisi_kembali' => 'Baik',
            'status_request' => 'menunggu',
        ]);
    }

    public function test_pengajuan_kondisi_rusak_diterima(): void
    {
        $peminjaman = $this->peminjamanAktif();

        // Regression guard: rule lama 'in:Baik,Rusak Ringan,Rusak Berat'
        // menolak nilai "Rusak" yang memang dikirim dropdown.
        $this->actingAs($this->petugas())
            ->post(route('petugas.pengembalian.ajukan', $peminjaman->id), [
                'kondisi_kembali' => 'Rusak',
                'denda_kerusakan' => 15000,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('pengembalian', [
            'peminjaman_id' => $peminjaman->id,
            'kondisi_kembali' => 'Rusak',
            'denda_kerusakan' => 15000,
        ]);
    }

    public function test_kondisi_lama_yang_sudah_dihapus_ditolak(): void
    {
        $peminjaman = $this->peminjamanAktif();

        foreach (['Rusak Ringan', 'Rusak Berat'] as $kondisi) {
            $this->actingAs($this->petugas())
                ->post(route('petugas.pengembalian.ajukan', $peminjaman->id), [
                    'kondisi_kembali' => $kondisi,
                    'denda_kerusakan' => 0,
                ])
                ->assertSessionHasErrors('kondisi_kembali');
        }

        $this->assertDatabaseCount('pengembalian', 0);
    }

    public function test_kondisi_dan_denda_wajib_diisi(): void
    {
        $peminjaman = $this->peminjamanAktif();

        $this->actingAs($this->petugas())
            ->post(route('petugas.pengembalian.ajukan', $peminjaman->id), [])
            ->assertSessionHasErrors(['kondisi_kembali', 'denda_kerusakan']);
    }

    public function test_halaman_pemantauan_pengembalian_render(): void
    {
        // Form pengembalian hanya dirender untuk peminjaman yang masih
        // berjalan, jadi butuh data agar option kondisinya ada di output.
        $this->peminjamanAktif();

        $response = $this->actingAs($this->petugas())
            ->get(route('petugas.pengembalian.index'))
            ->assertStatus(200);

        // Dropdown hanya menawarkan dua nilai yang valid. Cek mentah:
        // assertSee meng-escape tanda kutip.
        $this->assertStringContainsString(
            'value="Rusak"',
            $response->getContent()
        );

        // Nilai lama tidak boleh lagi muncul sebagai pilihan.
        $this->assertStringNotContainsString('Rusak Ringan', $response->getContent());
        $this->assertStringNotContainsString('Rusak Berat', $response->getContent());
    }

    public function test_admin_store_pengembalian_juga_pakai_domain_baru(): void
    {
        // Guard: rule di AdminController harus sama dengan petugas,
        // kalau tidak admin akan ditolak untuk nilai yang dropdown kirim.
        $source = file_get_contents(base_path('app/Http/Controllers/AdminController.php'));

        $this->assertStringContainsString(
            "'kondisi_kembali' => 'required|in:Baik,Rusak',",
            $source
        );
        $this->assertStringNotContainsString('Rusak Ringan', $source);
    }
}
