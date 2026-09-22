<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\Peminjaman;
use App\Models\Pengembalian;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaporanPengembalianTest extends TestCase
{
    use RefreshDatabase;

    private function petugas(): User
    {
        return User::factory()->create(['role' => 'petugas']);
    }

    public function test_laporan_menampilkan_admin_saat_petugas_id_null(): void
    {
        $petugas = $this->petugas();
        $peminjam = User::factory()->create(['role' => 'peminjam']);
        $alat = Alat::factory()->create();

        $peminjaman = Peminjaman::create([
            'user_id' => $peminjam->id,
            'tgl_pinjam' => now(),
            'tgl_kembali_plan' => now()->addDays(3),
            'status' => 'dipinjam',
        ]);

        // Pengembalian dengan petugas_id NULL (alur admin)
        Pengembalian::create([
            'peminjaman_id' => $peminjaman->id,
            'tgl_kembali' => now(),
            'kondisi_kembali' => 'Baik',
            'denda' => 0,
            'denda_kerusakan' => 0,
            'petugas_id' => null,
            'status_request' => 'disetujui',
        ]);

        $response = $this->actingAs($petugas)
            ->get(route('petugas.laporan.index'));

        $response->assertStatus(200);
        $response->assertSee('Admin');
        $response->assertDontSee('Petugas Dihapus');
    }

    public function test_laporan_menampilkan_nama_petugas_saat_ada(): void
    {
        $petugas = $this->petugas();
        $peminjam = User::factory()->create(['role' => 'peminjam']);

        $peminjaman = Peminjaman::create([
            'user_id' => $peminjam->id,
            'tgl_pinjam' => now(),
            'tgl_kembali_plan' => now()->addDays(3),
            'status' => 'dipinjam',
        ]);

        Pengembalian::create([
            'peminjaman_id' => $peminjaman->id,
            'tgl_kembali' => now(),
            'kondisi_kembali' => 'Baik',
            'denda' => 0,
            'denda_kerusakan' => 0,
            'petugas_id' => $petugas->id,
            'status_request' => 'disetujui',
        ]);

        $response = $this->actingAs($petugas)
            ->get(route('petugas.laporan.index'));

        $response->assertStatus(200);
        $response->assertSee($petugas->name);
    }

    public function test_laporan_hanya_menampilkan_pengembalian_disetujui(): void
    {
        $petugas = $this->petugas();
        $peminjam = User::factory()->create(['role' => 'peminjam']);

        $peminjaman = Peminjaman::create([
            'user_id' => $peminjam->id,
            'tgl_pinjam' => now(),
            'tgl_kembali_plan' => now()->addDays(3),
            'status' => 'dipinjam',
        ]);

        // Pengembalian yang masih menunggu tidak boleh muncul
        Pengembalian::create([
            'peminjaman_id' => $peminjaman->id,
            'tgl_kembali' => now(),
            'kondisi_kembali' => 'Baik',
            'denda' => 0,
            'denda_kerusakan' => 0,
            'petugas_id' => $petugas->id,
            'status_request' => 'menunggu',
        ]);

        $response = $this->actingAs($petugas)
            ->get(route('petugas.laporan.index'));

        $response->assertStatus(200);
        $response->assertSee('Belum ada data pengembalian.');
    }

    public function test_pdf_menampilkan_admin_saat_petugas_id_null(): void
    {
        $petugas = $this->petugas();
        $peminjam = User::factory()->create(['role' => 'peminjam']);

        $peminjaman = Peminjaman::create([
            'user_id' => $peminjam->id,
            'tgl_pinjam' => now(),
            'tgl_kembali_plan' => now()->addDays(3),
            'status' => 'dipinjam',
        ]);

        Pengembalian::create([
            'peminjaman_id' => $peminjaman->id,
            'tgl_kembali' => now(),
            'kondisi_kembali' => 'Baik',
            'denda' => 0,
            'denda_kerusakan' => 0,
            'petugas_id' => null,
            'status_request' => 'disetujui',
        ]);

        $response = $this->actingAs($petugas)
            ->get(route('petugas.laporan.pdf'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }
}
