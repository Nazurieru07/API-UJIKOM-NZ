<?php

namespace Tests\Feature;

use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeminjamanTelatTest extends TestCase
{
    use RefreshDatabase;

    public function test_peminjaman_lewat_tenggat_ditandai_telat(): void
    {
        Peminjaman::create([
            'user_id' => User::factory()->create(['role' => 'peminjam'])->id,
            'tgl_pinjam' => now()->subDays(10),
            'tgl_kembali_plan' => now()->subDays(5),
            'status' => 'dipinjam',
        ]);

        $this->artisan('app:hitung-peminjaman-telat')
            ->assertSuccessful()
            ->expectsOutputToContain('1 peminjaman ditandai sebagai telat.');

        $this->assertSame('telat', Peminjaman::first()->status);
    }

    public function test_peminjaman_belum_lewat_tenggat_tidak_diubah(): void
    {
        Peminjaman::create([
            'user_id' => User::factory()->create(['role' => 'peminjam'])->id,
            'tgl_pinjam' => now(),
            'tgl_kembali_plan' => now()->addDays(3),
            'status' => 'dipinjam',
        ]);

        $this->artisan('app:hitung-peminjaman-telat')
            ->assertSuccessful();

        $this->assertSame('dipinjam', Peminjaman::first()->status);
    }

    public function test_observer_terpanggil_saat_ditandai_telat(): void
    {
        // Mass update lewat query builder melewati observer.
        // Command harus iterate + save per instance agar log aktivitas
        // "dipinjam -> telat" tetap tercatat.
        $user = User::factory()->create(['role' => 'peminjam']);

        Peminjaman::create([
            'user_id' => $user->id,
            'tgl_pinjam' => now()->subDays(10),
            'tgl_kembali_plan' => now()->subDays(5),
            'status' => 'dipinjam',
        ]);

        // Observer hanya mencatat jika ada user yang login.
        // Command artisan tidak punya session, jadi login sebagai admin
        // lewat actingAs sebelum menjalankannya.
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->artisan('app:hitung-peminjaman-telat')->assertSuccessful();

        $this->assertDatabaseHas('log_aktivitas', [
            'aktivitas' => "Mengubah status peminjaman '{$user->name}' dari 'dipinjam' menjadi 'telat'.",
        ]);
    }
}
