<?php

namespace Tests\Feature;

use App\Models\Peminjaman;
use App\Models\Pengembalian;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Peminjam mengajukan pengembalian sendiri.
 *
 * Use case: peminjam sudah selesai memakai alat, menyerahkan ke
 * petugas, lalu mengajukan dari riwayatnya sendiri. Peminjam tidak
 * tahu kondisi barang atau denda, jadi hanya mengirim catatan;
 * kondisi dan denda kerusakan diisi petugas/admin saat pemeriksaan.
 */
class PeminjamPengembalianTest extends TestCase
{
    use RefreshDatabase;

    private function peminjam(): User
    {
        return User::factory()->create(['role' => 'peminjam']);
    }

    private function peminjamanAktif(User $user): Peminjaman
    {
        // Pakai factory jika ada state dipinjam; kalau tidak, buat
        // manual. Cek factory dulu.
        $p = Peminjaman::factory()
            ->for($user)
            ->create(['status' => 'dipinjam']);

        return $p;
    }

    public function test_peminjam_bisa_mengajukan_pengembalian(): void
    {
        $user = $this->peminjam();
        $peminjaman = $this->peminjamanAktif($user);

        $this->actingAs($user)
            ->post(route('peminjam.pengembalian.ajukan', $peminjaman->id), [
                'catatan' => 'Sudah saya titip di pos satpam.',
                'diproses_oleh' => 'admin',
            ])
            ->assertRedirect(route('peminjam.riwayat'));

        $this->assertDatabaseHas('pengembalian', [
            'peminjaman_id' => $peminjaman->id,
            'status_request' => 'menunggu',
            'catatan_peminjam' => 'Sudah saya titip di pos satpam.',
            'diproses_oleh' => 'admin',
            // Kondisi & denda belum diisi: tugas pemeriksaan.
            'kondisi_kembali' => null,
            'denda_kerusakan' => 0,
            // petugas_id NULL: belum ada yang memeriksa.
            'petugas_id' => null,
        ]);

        // Status peminjaman TIDAK boleh berubah: barang belum diperiksa.
        $this->assertSame('dipinjam', $peminjaman->fresh()->status);
    }

    public function test_catatan_wajib_minimal_3_karakter(): void
    {
        $user = $this->peminjam();
        $peminjaman = $this->peminjamanAktif($user);

        $this->actingAs($user)
            ->post(route('peminjam.pengembalian.ajukan', $peminjaman->id), [
                'catatan' => 'ok',
                'diproses_oleh' => 'admin',
            ])
            ->assertSessionHasErrors('catatan');

        $this->assertDatabaseMissing('pengembalian', [
            'peminjaman_id' => $peminjaman->id,
        ]);
    }

    public function test_peminjam_lain_tidak_bisa_mengajukan_pengembalian_orang(): void
    {
        // Guard: where('user_id', auth()->id()) + findOrFail.
        // User B tidak boleh mengajukan pengembalian peminjaman user A.
        $pemilik = $this->peminjam();
        $lain = $this->peminjam();
        $peminjaman = $this->peminjamanAktif($pemilik);

        $this->actingAs($lain)
            ->post(route('peminjam.pengembalian.ajukan', $peminjaman->id), [
                'catatan' => 'Bukan barang saya tapi kubalikin.',
                'diproses_oleh' => 'admin',
            ])
            ->assertNotFound();

        $this->assertDatabaseMissing('pengembalian', [
            'peminjaman_id' => $peminjaman->id,
        ]);
    }

    public function test_tidak_bisa_mengajukan_pengembalian_yang_sudah_menunggu(): void
    {
        $user = $this->peminjam();
        $peminjaman = $this->peminjamanAktif($user);

        Pengembalian::create([
            'peminjaman_id' => $peminjaman->id,
            'tgl_kembali' => now()->toDateString(),
            'kondisi_kembali' => null,
            'denda' => 0,
            'denda_kerusakan' => 0,
            'petugas_id' => null,
            'status_request' => 'menunggu',
            'catatan_peminjam' => 'Pengajuan pertama.',
        ]);

        $this->actingAs($user)
            ->post(route('peminjam.pengembalian.ajukan', $peminjaman->id), [
                'catatan' => 'Mencoba mengajukan kedua kali.',
                'diproses_oleh' => 'admin',
            ])
            ->assertRedirect(route('peminjam.riwayat'));

        // Data pengembalian tidak terganti.
        $this->assertSame(
            'Pengajuan pertama.',
            $peminjaman->fresh()->pengembalian->catatan_peminjam
        );
    }

    public function test_pengajuan_ditolak_bisa_diajukan_lagi(): void
    {
        // Alur 1:1 (peminjaman_id unik): pengajuan ditolak diperbarui
        // di tempat, bukan dibuat baru.
        $user = $this->peminjam();
        $peminjaman = $this->peminjamanAktif($user);

        $existing = Pengembalian::create([
            'peminjaman_id' => $peminjaman->id,
            'tgl_kembali' => now()->toDateString(),
            'kondisi_kembali' => null,
            'denda' => 0,
            'denda_kerusakan' => 0,
            'petugas_id' => null,
            'status_request' => 'ditolak',
            'catatan_peminjam' => 'Ditolak karena tidak lengkap.',
        ]);

        $this->actingAs($user)
            ->post(route('peminjam.pengembalian.ajukan', $peminjaman->id), [
                'catatan' => 'Sudah dilengkapi, kubalikin lagi.',
                'diproses_oleh' => 'admin',
            ])
            ->assertRedirect(route('peminjam.riwayat'));

        $this->assertSame(1, Pengembalian::where('peminjaman_id', $peminjaman->id)->count());
        $fresh = $peminjaman->fresh()->pengembalian;
        $this->assertSame('menunggu', $fresh->status_request);
        $this->assertSame('Sudah dilengkapi, kubalikin lagi.', $fresh->catatan_peminjam);
    }

    public function test_notifikasi_hanya_ke_admin_kala_pilih_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $petugas = User::factory()->create(['role' => 'petugas']);

        $user = $this->peminjam();
        $peminjaman = $this->peminjamanAktif($user);

        \Illuminate\Support\Facades\Notification::fake();

        $this->actingAs($user)
            ->post(route('peminjam.pengembalian.ajukan', $peminjaman->id), [
                'catatan' => 'Sudah saya kembalikan.',
                'diproses_oleh' => 'admin',
            ])->assertSessionHasNoErrors();

        \Illuminate\Support\Facades\Notification::assertSentTo(
            $admin,
            \App\Notifications\PengembalianDiajukanPeminjamNotification::class
        );
        // Petugas TIDAK diberi tahu: peminjam pilih admin.
        \Illuminate\Support\Facades\Notification::assertNotSentTo(
            $petugas,
            \App\Notifications\PengembalianDiajukanPeminjamNotification::class
        );
    }

    public function test_notifikasi_hanya_ke_petugas_kala_pilih_petugas(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $petugas = User::factory()->create(['role' => 'petugas']);

        $user = $this->peminjam();
        $peminjaman = $this->peminjamanAktif($user);

        \Illuminate\Support\Facades\Notification::fake();

        $this->actingAs($user)
            ->post(route('peminjam.pengembalian.ajukan', $peminjaman->id), [
                'catatan' => 'Sudah saya kembalikan ke petugas.',
                'diproses_oleh' => 'petugas',
            ])->assertSessionHasNoErrors();

        \Illuminate\Support\Facades\Notification::assertSentTo(
            $petugas,
            \App\Notifications\PengembalianDiajukanPeminjamNotification::class
        );
        \Illuminate\Support\Facades\Notification::assertNotSentTo(
            $admin,
            \App\Notifications\PengembalianDiajukanPeminjamNotification::class
        );
    }

    public function test_diproses_oleh_wajib_dan_hanya_nilai_sah(): void
    {
        $user = $this->peminjam();
        $peminjaman = $this->peminjamanAktif($user);

        // Tanpa pilihan: validasi gagal, tidak ada pengajuan.
        $this->actingAs($user)
            ->post(route('peminjam.pengembalian.ajukan', $peminjaman->id), [
                'catatan' => 'Tanpa pilihan diproses oleh.',
            ])
            ->assertSessionHasErrors('diproses_oleh');

        // Nilai di luar enum: ditolak (akan merusak filter antrean).
        $this->actingAs($user)
            ->post(route('peminjam.pengembalian.ajukan', $peminjaman->id), [
                'catatan' => 'Roe yang ngawur.',
                'diproses_oleh' => 'guru',
            ])
            ->assertSessionHasErrors('diproses_oleh');

        $this->assertDatabaseMissing('pengembalian', [
            'peminjaman_id' => $peminjaman->id,
        ]);
    }

    public function test_riwayat_menampilkan_status_pengembalian(): void
    {
        $user = $this->peminjam();
        $peminjaman = $this->peminjamanAktif($user);

        // Belum ada pengembalian: tombol "Kembalikan Alat" tampil.
        $body = $this->actingAs($user)
            ->get(route('peminjam.riwayat'))
            ->assertStatus(200)
            ->getContent();

        $this->assertStringContainsString('Kembalikan Alat', $body);

        // Setelah diajukan: badge "Pengembalian Diajukan" tampil,
        // tombol hilang (tidak bisa dobel).
        Pengembalian::create([
            'peminjaman_id' => $peminjaman->id,
            'tgl_kembali' => now()->toDateString(),
            'kondisi_kembali' => null,
            'denda' => 0,
            'denda_kerusakan' => 0,
            'petugas_id' => null,
            'status_request' => 'menunggu',
            'catatan_peminjam' => 'Sudah saya titip.',
        ]);

        $body = $this->actingAs($user)
            ->get(route('peminjam.riwayat'))
            ->getContent();

        $this->assertStringContainsString('Pengembalian Diajukan', $body);
        $this->assertStringContainsString('Menunggu pemeriksaan Admin', $body);
        $this->assertStringNotContainsString('Kembalikan Alat', $body);
    }

    public function test_peminjaman_yang_sudah_dikembalikan_tidak_bisa_diajukan(): void
    {
        $user = $this->peminjam();
        $peminjaman = Peminjaman::factory()
            ->for($user)
            ->create(['status' => 'dikembalikan']);

        $this->actingAs($user)
            ->post(route('peminjam.pengembalian.ajukan', $peminjaman->id), [
                'catatan' => 'Ini sudah selesai.',
                'diproses_oleh' => 'admin',
            ])
            ->assertNotFound();

        $this->assertDatabaseMissing('pengembalian', [
            'peminjaman_id' => $peminjaman->id,
        ]);
    }

    public function test_tamu_tidak_bisa_mengajukan(): void
    {
        $user = $this->peminjam();
        $peminjaman = $this->peminjamanAktif($user);

        $this->post(route('peminjam.pengembalian.ajukan', $peminjaman->id), [
            'catatan' => 'Saya belum login.',
            'diproses_oleh' => 'admin',
        ])
        ->assertRedirect(route('login'));

        $this->assertDatabaseMissing('pengembalian', [
            'peminjaman_id' => $peminjaman->id,
        ]);
    }
    /**
     * Bug lama: pengajuan yang DITOLAK punya tombol "Ajukan Lagi"
     * yang ternyata form POST tanpa field catatan/diproses_oleh,
     * jadi submit hanya gagal validasi dan redirect kembali --
     * peminjam menekan terus tanpa ada perubahan (kelihatan loop).
     *
     * Perbaikan: tombol membuka form, bukan submit. Saat validasi
     * gagal form terbuka otomatis beserta pesan errornya.
     */
    public function test_validasi_gagal_form_terbuka_dan_error_terlihat(): void
    {
        $user = $this->peminjam();
        $peminjaman = $this->peminjamanAktif($user);

        Pengembalian::create([
            'peminjaman_id' => $peminjaman->id,
            'tgl_kembali' => now()->toDateString(),
            'kondisi_kembali' => null,
            'denda' => 0,
            'denda_kerusakan' => 0,
            'petugas_id' => null,
            'status_request' => 'ditolak',
            'catatan_peminjam' => 'Ditolak.',
        ]);

        // Submit tanpa field -- seperti tombol lama.
        $response = $this->actingAs($user)
            ->from(route('peminjam.riwayat'))
            ->post(route('peminjam.pengembalian.ajukan', $peminjaman->id), [])
            ->assertRedirect(route('peminjam.riwayat'));

        $response->assertSessionHasErrors(['catatan', 'diproses_oleh']);

        // Halaman balik: form TIDAK hidden (bisa dilihat peminjam),
        // badge "Ditolak" masih ada.
        $html = $this->actingAs($user)
            ->get(route('peminjam.riwayat'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(
            'form-pengembalian-' . $peminjaman->id,
            $html
        );
        $this->assertStringNotContainsString(
            'form-pengembalian-' . $peminjaman->id . '" class="hidden',
            $html
        );
        $this->assertStringContainsString('Pengembalian Ditolak', $html);
    }

    public function test_ajukan_lagi_setelah_ditolak_masuk_antrean_yang_dipilih(): void
    {
        $petugas = User::factory()->create(['role' => 'petugas']);

        $user = $this->peminjam();
        $peminjaman = $this->peminjamanAktif($user);

        // Pengajuan lama DITOLAK saat masih ditujukan ke admin.
        Pengembalian::create([
            'peminjaman_id' => $peminjaman->id,
            'tgl_kembali' => now()->toDateString(),
            'kondisi_kembali' => null,
            'denda' => 0,
            'denda_kerusakan' => 0,
            'petugas_id' => null,
            'status_request' => 'ditolak',
            'catatan_peminjam' => 'Ditolak admin.',
            'diproses_oleh' => 'admin',
        ]);

        // Peminjam ajukan ulang, sekarang ke petugas.
        $this->actingAs($user)
            ->post(route('peminjam.pengembalian.ajukan', $peminjaman->id), [
                'catatan' => 'Sekarang saya bawa sendiri.',
                'diproses_oleh' => 'petugas',
            ])
            ->assertRedirect(route('peminjam.riwayat'));

        $fresh = $peminjaman->fresh()->pengembalian;

        // Satu row saja (update di tempat, bukan insert baru),
        // statusnya menunggu, dan tujuannya ikut pilihan terbaru.
        $this->assertSame(1, Pengembalian::where('peminjaman_id', $peminjaman->id)->count());
        $this->assertSame('menunggu', $fresh->status_request);
        $this->assertSame('petugas', $fresh->diproses_oleh);
        $this->assertSame('Sekarang saya bawa sendiri.', $fresh->catatan_peminjam);
    }
}