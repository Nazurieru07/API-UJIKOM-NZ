<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\Kategori;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AlatKondisiTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_bisa_mengubah_kondisi_alat_per_pcs(): void
    {
        $admin = $this->admin();
        $alat = Alat::factory()->create([
            'stok' => 10,
            'stok_baik' => 10,
            'stok_rusak' => 0,
            'stok_rusak_parah' => 0,
            'status_kondisi' => 'Baik',
        ]);

        $response = $this->actingAs($admin)->post(
            route('admin.alat.ubahKondisi', $alat->id),
            [
                'kondisi_asal' => 'Baik',
                'kondisi_tujuan' => 'Rusak',
                'jumlah' => 3,
            ]
        );

        $response->assertRedirect(route('admin.alat.index'));

        $alat->refresh();

        $this->assertSame(7, (int) $alat->stok_baik);
        $this->assertSame(3, (int) $alat->stok_rusak);
        $this->assertSame(10, (int) $alat->stok);
    }

    public function test_kondisi_asal_dan_tujuan_harus_berbeda(): void
    {
        $admin = $this->admin();
        $alat = Alat::factory()->create();

        $response = $this->actingAs($admin)->post(
            route('admin.alat.ubahKondisi', $alat->id),
            [
                'kondisi_asal' => 'Baik',
                'kondisi_tujuan' => 'Baik',
                'jumlah' => 1,
            ]
        );

        $response->assertSessionHasErrors('kondisi_tujuan');

        $this->assertSame((int) $alat->stok_baik, (int) $alat->fresh()->stok_baik);
    }

    public function test_jumlah_melebihi_stok_ditolak(): void
    {
        $admin = $this->admin();
        $alat = Alat::factory()->create([
            'stok_baik' => 2,
            'stok' => 2,
        ]);

        $response = $this->actingAs($admin)->post(
            route('admin.alat.ubahKondisi', $alat->id),
            [
                'kondisi_asal' => 'Baik',
                'kondisi_tujuan' => 'Rusak Parah',
                'jumlah' => 99,
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertSame(2, (int) $alat->fresh()->stok_baik);
        $this->assertSame(0, (int) $alat->fresh()->stok_rusak_parah);
    }

    public function test_status_kondisi_mengikuti_majoritas(): void
    {
        $admin = $this->admin();
        $alat = Alat::factory()->create([
            'stok' => 10,
            'stok_baik' => 2,
            'stok_rusak' => 8,
            'stok_rusak_parah' => 0,
            'status_kondisi' => 'Baik',
        ]);

        $this->actingAs($admin)->post(
            route('admin.alat.ubahKondisi', $alat->id),
            [
                'kondisi_asal' => 'Baik',
                'kondisi_tujuan' => 'Rusak Parah',
                'jumlah' => 2,
            ]
        );

        $alat->refresh();

        $this->assertSame(0, (int) $alat->stok_baik);
        $this->assertSame(2, (int) $alat->stok_rusak_parah);
        $this->assertSame('Rusak', $alat->status_kondisi);
    }

    public function test_petugas_tidak_bisa_setujui_peminjaman_dua_kali(): void
    {
        $petugas = User::factory()->create(['role' => 'petugas']);
        $peminjam = User::factory()->create(['role' => 'peminjam']);
        $alat = Alat::factory()->create([
            'stok' => 5,
            'stok_baik' => 5,
        ]);

        $peminjaman = Peminjaman::create([
            'user_id' => $peminjam->id,
            'tgl_pinjam' => now(),
            'tgl_kembali_plan' => now()->addDays(3),
            'status' => 'diajukan',
        ]);

        DB::table('detail_pinjam')->insert([
            'peminjaman_id' => $peminjaman->id,
            'alat_id' => $alat->id,
            'jumlah' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Approve pertama: sukses
        $this->actingAs($petugas)
            ->post(route('petugas.peminjaman.setujui', $peminjaman->id))
            ->assertRedirect();

        $this->assertSame('dipinjam', $peminjaman->fresh()->status);
        $this->assertSame(4, (int) $alat->fresh()->stok_baik);

        // Approve kedua: harus ditolak, stok tidak boleh berkurang lagi
        $this->actingAs($petugas)
            ->post(route('petugas.peminjaman.setujui', $peminjaman->id))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(4, (int) $alat->fresh()->stok_baik);
    }
}
