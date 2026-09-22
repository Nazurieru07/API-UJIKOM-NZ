<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class LoginThrottleTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_diblokir_setelah_5_kali_gagal(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        // 5 percobaan gagal
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => $user->email,
                'password' => 'password-salah',
            ]);
        }

        // Percobaan ke-6 harus diblokir
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password-salah',
        ]);

        $response->assertSessionHasErrors('email');
        $response->assertSessionHasErrorsIn('default', ['email']);

        $this->assertStringContainsString(
            'Terlalu banyak percobaan login',
            session('errors')->first('email')
        );
    }

    public function test_login_berhasil_menghitung_rate_limiter(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);

        // Rate limiter harus bersih setelah login berhasil
        $key = strtolower($user->email) . '|' . request()->ip();
        $this->assertFalse(RateLimiter::tooManyAttempts($key, 5));
    }
}
