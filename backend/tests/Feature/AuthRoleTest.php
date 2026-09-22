<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_redirect_ke_dashboard_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_petugas_redirect_ke_peminjaman_petugas(): void
    {
        $petugas = User::factory()->create(['role' => 'petugas']);

        $response = $this->post('/login', [
            'email' => $petugas->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('petugas.peminjaman.index'));
    }

    public function test_login_gagal_tidak_otentikasi(): void
    {
        User::factory()->create(['email' => 'salah@example.com']);

        $response = $this->post('/login', [
            'email' => 'salah@example.com',
            'password' => 'password-salah',
        ]);

        $this->assertGuest();
    }

    public function test_role_middleware_menolak_akses_silang(): void
    {
        $peminjam = User::factory()->create(['role' => 'peminjam']);

        $response = $this->actingAs($peminjam)
            ->get(route('admin.dashboard'));

        $response->assertStatus(403);
    }

    public function test_petugas_tidak_bisa_akses_menu_admin(): void
    {
        $petugas = User::factory()->create(['role' => 'petugas']);

        $response = $this->actingAs($petugas)
            ->get(route('admin.alat.index'));

        $response->assertStatus(403);
    }
}
