<?php

namespace Tests\Feature;

use App\Models\Peminjaman;
use App\Models\Pengembalian;
use App\Models\User;
use App\Notifications\PengembalianSelesaiNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pesan notifikasi pengembalian selesai harus menyebut siapa yang
 * menyetujui. Petugas bisa menyetujui, jadi pesan yang selalu
 * berbunyi "disetujui oleh Admin" akan salah.
 */
class NotifikasiPengembalianSelesaiTest extends TestCase
{
    use RefreshDatabase;

    public function test_pesan_menyebut_petugas_yang_menyetujui(): void
    {
        $petugas = User::factory()->create([
            'role' => 'petugas',
            'name' => 'Diablo',
        ]);

        $peminjaman = Peminjaman::factory()->create([
            'user_id' => User::factory()->create(['role' => 'peminjam'])->id,
        ]);

        $pengembalian = Pengembalian::create([
            'peminjaman_id' => $peminjaman->id,
            'petugas_id' => $petugas->id,
            'tgl_kembali' => now(),
            'kondisi_kembali' => 'Baik',
            'denda_kerusakan' => 0,
            'denda' => 0,
            'status_request' => 'disetujui',
        ]);

        $notif = new PengembalianSelesaiNotification(
            $pengembalian->load('petugas')
        );

        $pesan = $notif->toArray($peminjaman->user)['pesan'];

        $this->assertStringContainsString('Petugas', $pesan);
        $this->assertStringContainsString('Diablo', $pesan);
        $this->assertStringNotContainsString('oleh Admin', $pesan);
    }

    public function test_pesan_menyebut_admin_kala_tidak_ada_petugas(): void
    {
        $peminjaman = Peminjaman::factory()->create([
            'user_id' => User::factory()->create(['role' => 'peminjam'])->id,
        ]);

        $pengembalian = Pengembalian::create([
            'peminjaman_id' => $peminjaman->id,
            // Admin menyetujui -> petugas_id kosong.
            'petugas_id' => null,
            'tgl_kembali' => now(),
            'kondisi_kembali' => 'Baik',
            'denda_kerusakan' => 0,
            'denda' => 0,
            'status_request' => 'disetujui',
        ]);

        $notif = new PengembalianSelesaiNotification(
            $pengembalian->load('petugas')
        );

        $pesan = $notif->toArray($peminjaman->user)['pesan'];

        $this->assertStringContainsString('oleh Admin', $pesan);
        $this->assertStringNotContainsString('Petugas', $pesan);
    }
}
