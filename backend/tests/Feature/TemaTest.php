<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fitur tema gelap/terang.
 *
 * Toggle muncul di ketiga panel (admin, petugas, peminjam) plus
 * halaman login. Preferensi disimpan ke kolom users.tema. Welcome
 * dan PDF laporan sengaja tidak ikut (welcome tema gelap fix, PDF
 * tetap terang untuk kertas cetak).
 */
class TemaTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    /**
     * @dataProvider roleProvider
     */
    public function test_toggle_tema_muncul_di_semua_panel(string $role, string $route): void
    {
        $body = $this->actingAs($this->user($role))
            ->get(route($route))
            ->assertStatus(200)
            ->getContent();

        $this->assertStringContainsString(
            'id="themeToggle"',
            $body,
            "Tombol toggle tema tidak muncul di panel $role."
        );

        // Script anti-FOUC harus ada di <head> supaya tidak ada
        // flash putih saat dark mode dimuat.
        $this->assertStringContainsString('__initTema', $body);
    }

    /**
     * @dataProvider roleProvider
     */
    public function test_halaman_membaca_tema_dari_db_user(string $role, string $route): void
    {
        $user = $this->user($role);
        $user->tema = 'dark';
        $user->save();

        $body = $this->actingAs($user)
            ->get(route($route))
            ->assertStatus(200)
            ->getContent();

        // Server-side: script inisialisasi harus menulis 'dark' saat
        // user menyimpan tema gelap. Tanpa ini, localStorage kosong
        // (browser baru) menyebabkan flash ke light.
        $this->assertStringContainsString(
            "let tema = localStorage.getItem(TEMA_KEY) || 'dark';",
            $body,
            "Tema dari DB ($role) tidak terbawa ke script inisialisasi."
        );
    }

    /**
     * @dataProvider roleProvider
     */
    public function test_default_light_ketika_kolom_tema_kosong(string $role, string $route): void
    {
        $user = $this->user($role);

        // Kolom tema nullable; user lama (sebelum fitur ini) punya NULL.
        $this->assertNull($user->fresh()->tema);

        $body = $this->actingAs($user)
            ->get(route($route))
            ->assertStatus(200)
            ->getContent();

        $this->assertStringContainsString(
            "let tema = localStorage.getItem(TEMA_KEY) || 'light';",
            $body,
            "Tema NULL harus jatuh ke 'light', bukan kosong/null."
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

    public function test_halaman_login_punya_toggle_tema(): void
    {
        $body = $this->get(route('login'))
            ->assertStatus(200)
            ->getContent();

        // Login belum login: sumber temanya localStorage saja. Tombol
        // tetap muncul supaya peminjam bisa pilih tema sebelum masuk.
        $this->assertStringContainsString('id="themeToggle"', $body);
        $this->assertStringContainsString('__initTema', $body);
    }

    public function test_simpan_tema_light_ke_db(): void
    {
        $user = $this->user('admin');

        $this->actingAs($user)
            ->post(route('tema.simpan'), ['tema' => 'light'])
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertSame('light', $user->fresh()->tema);
    }

    public function test_simpan_tema_dark_ke_db(): void
    {
        $user = $this->user('admin');

        $this->actingAs($user)
            ->post(route('tema.simpan'), ['tema' => 'dark'])
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertSame('dark', $user->fresh()->tema);
    }

    public function test_tema_gagal_validasi_nilai_di_luar_enum(): void
    {
        $user = $this->user('admin');

        // Hanya 'light'/'dark' yang sah. Nilai lain (mis. 'blue' atau
        // 'DARK') tidak boleh lolos -- akan merusak CSS selector
        // html[data-tema="..."].
        $this->actingAs($user)
            ->postJson(route('tema.simpan'), ['tema' => 'blue'])
            ->assertStatus(422);

        $this->assertNull($user->fresh()->tema);
    }

    public function test_tema_tidak_tersimpan_tanpa_login(): void
    {
        // Route pakai middleware auth + user.aktif.
        $this->post(route('tema.simpan'), ['tema' => 'dark'])
            ->assertRedirect(route('login'));
    }

    public function test_css_peta_warna_gelap_ada_di_halaman(): void
    {
        $body = $this->actingAs($this->user('admin'))
            ->get(route('admin.alat.index'))
            ->getContent();

        // Override utility class dengan spesifisitas lebih tinggi.
        // Tanpa !important ini, utility Tailwind menang dan bg-white
        // tetap putih di dark mode.
        foreach (['bg-white', 'text-gray-900', 'border-gray-200'] as $cls) {
            $this->assertStringContainsString(
                'html[data-tema="dark"] .' . $cls,
                $body,
                "Override dark untuk .$cls tidak ada."
            );
        }
    }

    public function test_welcome_tidak_pakai_fitur_tema(): void
    {
        $body = $this->get('/')
            ->assertStatus(200)
            ->getContent();

        // Welcome tema gelap permanen (Rimuru Tempest). Tidak boleh
        // terkena toggle atau data-tema, supaya cyan glow-nya utuh.
        $this->assertStringNotContainsString('__initTema', $body);
        $this->assertStringNotContainsString('id="themeToggle"', $body);
    }

    public function test_pdf_laporan_tetap_terang(): void
    {
        // PDF adalah kertas cetak: harus tetap terang walau user
        // memilih dark mode.
        $user = $this->user('admin');
        $user->tema = 'dark';
        $user->save();

        $body = $this->actingAs($user)
            ->get(route('admin.laporan.pdf'))
            ->assertStatus(200)
            ->getContent();

        $this->assertStringNotContainsString('__initTema', $body);
        $this->assertStringNotContainsString('id="themeToggle"', $body);
    }
}
