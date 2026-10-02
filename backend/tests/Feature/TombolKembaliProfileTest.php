<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tombol "Kembali" di halaman profil harus membawa user ke halaman
 * terakhir yang benar-benar dibuka sebelumnya.
 *
 * Jebakan yang diuji: `url()->previous()` bawaan Laravel TIDAK bisa
 * dipakai di sini. Session `_previous.url` diisi pada setiap request
 * GET -- termasuk request ke /profile itu sendiri -- sehingga begitu
 * halaman profil direfresh, "url sebelumnya" berubah jadi /profile dan
 * tombol Kembali hanya memuat ulang halaman yang sama. SimpanRiwayatHalaman
 * menyimpan daftar halaman dan mengambil entri terakhir yang berbeda,
 * sehingga test ini mengunci perilaku yang benar itu.
 */
class TombolKembaliProfileTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'peminjam'): User
    {
        return User::factory()->create(['role' => $role]);
    }

    /**
     * Ambil href dari anchor bertuliskan "Kembali" (bukan "Profil").
     */
    private function hrefKembali(string $html): ?string
    {
        if (preg_match('#<a[^>]*href="([^"]*)"[^>]*>\s*(?:(?!</a>).)*?Kembali#s', $html, $m)) {
            return $m[1];
        }

        return null;
    }

    public function test_kembali_menunjuk_halaman_sebelum_profile(): void
    {
        $user = $this->user();

        $this->actingAs($user)->get(route('peminjam.katalog'));
        $this->actingAs($user)->get(route('profile.edit'));

        $html = $this->actingAs($user)
            ->get(route('profile.edit'))
            ->getContent();

        $this->assertSame(route('peminjam.katalog'), $this->hrefKembali($html));
    }

    public function test_kembali_tetap_benar_setelah_profil_direfresh_berulang(): void
    {
        // Dua GET berturut-turut ke /profile meniru refresh browser.
        // url()->previous() akan salah di sini;(list histori harus tetap
        // menunjuk katalog.
        $user = $this->user();

        $this->actingAs($user)->get(route('peminjam.katalog'));
        $this->actingAs($user)->get(route('profile.edit'));
        $this->actingAs($user)->get(route('profile.edit'));
        $this->actingAs($user)->get(route('profile.edit'));

        $html = $this->actingAs($user)
            ->get(route('profile.edit'))
            ->getContent();

        $this->assertSame(
            route('peminjam.katalog'),
            $this->hrefKembali($html),
            'Setelah refresh berulang, tombol Kembali jadi salah.'
        );
    }

    public function test_kembali_dari_layout_admin_ke_halaman_admin(): void
    {
        $admin = $this->user('admin');

        $this->actingAs($admin)->get(route('admin.user.index'));
        $this->actingAs($admin)->get(route('profile.edit'));

        $html = $this->actingAs($admin)
            ->get(route('profile.edit'))
            ->getContent();

        $this->assertSame(route('admin.user.index'), $this->hrefKembali($html));
    }

    public function test_kembali_dari_layout_petugas_ke_halaman_petugas(): void
    {
        $petugas = $this->user('petugas');

        $this->actingAs($petugas)->get(route('petugas.peminjaman.index'));
        $this->actingAs($petugas)->get(route('profile.edit'));

        $html = $this->actingAs($petugas)
            ->get(route('profile.edit'))
            ->getContent();

        $this->assertSame(route('petugas.peminjaman.index'), $this->hrefKembali($html));
    }

    public function test_kembali_ikut_membawa_query_string_halaman_sebelum(): void
    {
        // Membuka daftar user dengan filter lalu kembali harus
        // mempertahankan filter itu, bukan ke daftar tanpa filter.
        $admin = $this->user('admin');
        $berfilter = route('admin.user.index', ['role' => 'petugas']);

        $this->actingAs($admin)->get($berfilter);
        $this->actingAs($admin)->get(route('profile.edit'));

        $html = $this->actingAs($admin)
            ->get(route('profile.edit'))
            ->getContent();

        $this->assertSame($berfilter, $this->hrefKembali($html));
        $this->assertStringContainsString('role=petugas', $html);
    }

    public function test_kembali_menunjuk_halaman_yang_berbeda_bukan_yang_paling_akhir(): void
    {
        // Buka beberapa halaman beruntun, baru masuk ke profil. Kembali
        // harus ke halaman yang benar-benar terakhir sebelum profil.
        $user = $this->user();

        $this->actingAs($user)->get(route('peminjam.katalog'));
        $this->actingAs($user)->get(route('admin.log_aktivitas.index'));
        $this->actingAs($user)->get(route('admin.peminjaman.index'));
        $this->actingAs($user)->get(route('profile.edit'));

        $html = $this->actingAs($user)
            ->get(route('profile.edit'))
            ->getContent();

        $this->assertSame(route('admin.peminjaman.index'), $this->hrefKembali($html));
    }

    public function test_tanpa_riwayat_tidak_ada_link_kembali(): void
    {
        // Halaman profil dibuka langsung (session baru): tidak ada
        // tujuan kembali, jadi jangan tampilkan link yang menunjuk
        // ke halaman sendiri.
        $user = $this->user();

        $html = $this->actingAs($user)
            ->get(route('profile.edit'))
            ->getContent();

        $this->assertNull($this->hrefKembali($html));
        $this->assertStringContainsString('Profil', $html);
    }
}
