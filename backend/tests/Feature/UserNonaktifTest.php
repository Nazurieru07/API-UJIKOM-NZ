<?php

namespace Tests\Feature;

use App\Models\LogAktivitas;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserNonaktifTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_aktif_bisa_login(): void
    {
        $user = User::factory()->create([
            'role' => 'peminjam',
            'is_aktif' => true,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('peminjam.katalog'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_nonaktif_tidak_bisa_login(): void
    {
        $user = User::factory()->create([
            'role' => 'peminjam',
            'is_aktif' => false,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_login_api_menolak_user_nonaktif(): void
    {
        $user = User::factory()->create([
            'role' => 'peminjam',
            'is_aktif' => false,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    }

    public function test_middleware_memutus_session_user_nonaktif(): void
    {
        // User SUDAH login, lalu admin menonaktifkannya.
        $user = User::factory()->create([
            'role' => 'peminjam',
            'is_aktif' => true,
        ]);

        $this->be($user);

        $user->update(['is_aktif' => false]);

        $response = $this->get(route('peminjam.katalog'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_api_token_user_nonaktif_ditolak(): void
    {
        $user = User::factory()->create([
            'role' => 'peminjam',
            'is_aktif' => true,
        ]);

        $token = $user->createToken('test')->plainTextToken;

        // Token valid sebelum dinonaktifkan.
        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/me')
            ->assertOk();

        $user->update(['is_aktif' => false]);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/me')
            ->assertOk();

        $user->update(['is_aktif' => false]);

        /*
        | Guard instance di-cache per test (auth()->user() mengembalikan
        | model yang SUDAH dimuat sebelum is_aktif berubah). ForgetGuards
        | memaksa guard sanctum me-reload user dari token, jadi middleware
        | melihat nilai is_aktif yang baru. Perilaku produksi tidak terkena:
        | tiap HTTP request selalu guard baru.
        */
        \Illuminate\Support\Facades\Auth::forgetGuards();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/me')
            ->assertStatus(403);
    }

    public function test_admin_bisa_menonaktifkan_user_lain(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create([
            'role' => 'peminjam',
            'is_aktif' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.user.toggle-aktif', $target->id))
            ->assertRedirect(route('admin.user.index'));

        $this->assertFalse($target->fresh()->is_aktif);
        $this->assertDatabaseHas('log_aktivitas', [
            'user_id' => $admin->id,
        ]);

        // Riwayat peminjaman user tidak boleh terhapus.
        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    public function test_admin_bisa_aktifkan_kembali_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create([
            'role' => 'peminjam',
            'is_aktif' => false,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.user.toggle-aktif', $target->id));

        $this->assertTrue($target->fresh()->is_aktif);
    }

    public function test_admin_tidak_bisa_menonaktifkan_akun_sendiri(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.user.toggle-aktif', $admin->id))
            ->assertRedirect(route('admin.user.index'));

        $this->assertTrue($admin->fresh()->is_aktif);
    }

    public function test_filter_status_hanya_menampilkan_nonaktif(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['is_aktif' => true]);
        User::factory()->create(['is_aktif' => false]);

        $this->actingAs($admin)
            ->get(route('admin.user.index', ['status' => 'nonaktif']))
            ->assertOk()
            ->assertViewHas('users', fn ($users) => $users->count() === 1
                && $users->first()->is_aktif === false);
    }
}
