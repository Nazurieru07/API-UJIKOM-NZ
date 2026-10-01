<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Topbar diam + modal konfirmasi logout.
 *
 * Dua permintaan user:
 * 1. Tombol logout ikut ter-scroll saat menggulir data/tabel. Header
 *    admin/petugas ada di dalam .motion-main yang overflow-y-auto, jadi
 *    ikut ter-scroll. Perbaikannya sticky top-0.
 * 2. Pesan sebelum logout dipercantik -- window.confirm()原生 terlalu
 *    standar dan tidak bisa di-style.
 *
 * Layout peminjam sudah sticky sejak awal (line 39 peminjam.blade.php),
 * jadi test ini mengunci ketiga layout.
 */
class TopbarLogoutTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    /**
     * @dataProvider roleProvider
     */
    public function test_header_sticky_di_semua_layout(string $role, string $route): void
    {
        $body = $this->actingAs($this->user($role))
            ->get(route($route))
            ->assertStatus(200)
            ->getContent();

        // Header harus punya sticky: tanpa ini tombol logout ikut ter-scroll.
        $this->assertStringContainsString(
            'motion-topbar sticky top-0',
            $body,
            "Header layout $role tidak sticky -- tombol logout akan ikut ter-scroll."
        );
    }

    public static function roleProvider(): array
    {
        return [
            'admin' => ['admin', 'admin.alat.index'],
            'petugas' => ['petugas', 'petugas.peminjaman.index'],
            'peminjam' => ['peminjam', 'peminjam.katalog'],
        ];
    }

    /**
     * @dataProvider roleProvider
     */
    public function test_tombol_logout_pakai_konfirmasi(string $role, string $route): void
    {
        $body = $this->actingAs($this->user($role))
            ->get(route($route))
            ->assertStatus(200)
            ->getContent();

        $this->assertStringContainsString('data-confirm-logout', $body);
    }

    /**
     * @dataProvider roleProvider
     */
    public function test_konfirmasi_logout_memakai_modal_bukan_window_confirm(string $role, string $route): void
    {
        $body = $this->actingAs($this->user($role))
            ->get(route($route))
            ->assertStatus(200)
            ->getContent();

        // Modal konfirmasi: markup, styling, dan handler JS.
        $this->assertStringContainsString('showLogoutConfirm', $body);
        $this->assertStringContainsString('logout-confirm-overlay', $body);
        $this->assertStringContainsString('Keluar dari akun?', $body);

        // window.confirm lama harus hilang -- itu yang tidak bisa di-style.
        $this->assertStringNotContainsString(
            "window.confirm('Yakin ingin keluar dari akun ini?')",
            $body
        );
    }

    public function test_modal_konfirmasi_punya_tombol_batal_dan_aksi(): void
    {
        $body = $this->actingAs($this->user('admin'))
            ->get(route('admin.alat.index'))
            ->getContent();

        // Dua jalan keluar: batal atau konfirmasi.
        $this->assertStringContainsString('Batal', $body);
        $this->assertStringContainsString('Ya, keluar', $body);

        // Accessibility: dialog yang bisa ditutup dengan Escape.
        $this->assertStringContainsString("event.key === 'Escape'", $body);

        // aria-modal di-set saat modal dibuat (JS), jadi cek panggilannya.
        $this->assertStringContainsString(
            "setAttribute('aria-modal', 'true')",
            $body
        );
    }

    public function test_logout_tetap_berfungsi_setelah_perubahan(): void
    {
        $user = $this->user('admin');

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_halaman_login_tidak_rusak_oleh_perubahan(): void
    {
        // motion.blade.php juga dipakai login; pastikan masih render.
        $this->get(route('login'))
            ->assertStatus(200)
            ->assertSee('password', false);
    }
}