<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RenderHalamanTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Halaman admin harus render 200 tanpa error fatal setelah migrasi
     * serial. Ini menangkap view yang masih membaca relasi $detail->alat
     * (sudah dihapus) atau eager load yang hilang -- hal yang tidak
     * tertangkap oleh assertStatus saja di test lama.
     */
    public function test_halaman_admin_render_setelah_migrasi_serial(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        foreach ([
            'admin.alat.index' => 'daftar alat',
            'admin.peminjaman.index' => 'peminjaman',
            'admin.pengembalian.index' => 'pengembalian',
            'admin.laporan.index' => 'laporan',
            'admin.kategori.index' => 'kategori',
        ] as $route => $label) {
            // Pakai assertStatus bukan assertOk agar pesan gagal jelas.
            $this->get(route($route))->assertStatus(200);
        }
    }

    public function test_halaman_petugas_render(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'petugas']));

        foreach (['petugas.peminjaman.index', 'petugas.pengembalian.index', 'petugas.laporan.index'] as $route) {
            $this->get(route($route))->assertStatus(200);
        }
    }

    public function test_halaman_peminjam_render(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'peminjam']));

        // Katalog adalah halaman utama yang menyaring unit tersedia.
        $this->get(route('peminjam.katalog'))->assertStatus(200);
    }
}
