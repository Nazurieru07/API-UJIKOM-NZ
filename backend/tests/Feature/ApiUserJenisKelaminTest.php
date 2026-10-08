<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiUserJenisKelaminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /**
     * Regression untuk K-03 (audit 2026-10-07).
     *
     * Sebelumnya StoreUserRequest / UpdateUserRequest tidak memvalidasi
     * jenis_kelamin sama sekali, padahal web sudah membatasi nilainya ke
     * 'Laki-laki' / 'Perempuan'.
     */
    public function test_store_dengan_jenis_kelamin_valid_disimpan(): void
    {
        Sanctum::actingAs($this->admin(), ['*']);

        $response = $this->postJson('/api/users', [
            'name' => 'Budi',
            'email' => 'budi@example.com',
            'password' => 'Password123',
            'role' => 'peminjam',
            'jenis_kelamin' => 'Laki-laki',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('users', [
            'email' => 'budi@example.com',
            'jenis_kelamin' => 'Laki-laki',
        ]);
    }

    public function test_store_tanpa_jenis_kelamin_tetap_berhasil(): void
    {
        Sanctum::actingAs($this->admin(), ['*']);

        $response = $this->postJson('/api/users', [
            'name' => 'Sari',
            'email' => 'sari@example.com',
            'password' => 'Password123',
            'role' => 'peminjam',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('users', ['email' => 'sari@example.com']);
    }

    public function test_store_dengan_jenis_kelamin_ngasal_ditolak(): void
    {
        Sanctum::actingAs($this->admin(), ['*']);

        $response = $this->postJson('/api/users', [
            'name' => 'Budi',
            'email' => 'budi2@example.com',
            'password' => 'Password123',
            'role' => 'peminjam',
            'jenis_kelamin' => 'xyz',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('jenis_kelamin');
        $this->assertDatabaseMissing('users', ['email' => 'budi2@example.com']);
    }

    public function test_update_dengan_jenis_kelamin_valid_disimpan(): void
    {
        Sanctum::actingAs($this->admin(), ['*']);

        $user = User::factory()->create(['role' => 'peminjam']);

        $response = $this->putJson('/api/users/' . $user->id, [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'jenis_kelamin' => 'Perempuan',
        ]);

        $response->assertOk();
        $this->assertSame('Perempuan', $user->fresh()->jenis_kelamin);
    }

    public function test_update_dengan_jenis_kelamin_ngasal_ditolak(): void
    {
        Sanctum::actingAs($this->admin(), ['*']);

        $user = User::factory()->create(['role' => 'peminjam', 'jenis_kelamin' => 'Laki-laki']);

        $response = $this->putJson('/api/users/' . $user->id, [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'jenis_kelamin' => 'random',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('jenis_kelamin');

        // Data lama tidak berubah.
        $this->assertSame('Laki-laki', $user->fresh()->jenis_kelamin);
    }
}
