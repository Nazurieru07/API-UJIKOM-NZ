<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regresi untuk 7 bug yang dilaporkan user setelah migrasi serial.
 */
class BugSerialRegressionTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    // ---- BUG 5: halaman detail kategori error 500 ----

    public function test_halaman_kategori_render_tanpa_error(): void
    {
        $admin = $this->admin();

        // Kategori dengan alat yang punya unit.
        $kategori = \App\Models\Kategori::factory()->create();
        Alat::factory()->denganUnit(3)->create(['kategori_id' => $kategori->id]);

        $this->actingAs($admin)
            ->get(route('admin.kategori.show', $kategori->id))
            ->assertStatus(200);
    }

    public function test_halaman_kategori_kosok_juga_render(): void
    {
        $kategori = \App\Models\Kategori::factory()->create();

        $this->actingAs($this->admin())
            ->get(route('admin.kategori.show', $kategori->id))
            ->assertStatus(200);
    }

    // ---- BUG 2: tambah alat tidak bisa (looping) ----

    public function test_tambah_alat_dengan_field_lengkap_berhasil(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.alat.store'), [
                'nama_alat' => 'Meja Ujian',
                'kategori_id' => \App\Models\Kategori::factory()->create()->id,
                'kode_alat' => 'MU',
                'jumlah_unit' => 3,
            ])
            ->assertRedirect(route('admin.alat.index'))
            ->assertSessionHas('success');

        $this->assertSame(3, Alat::where('kode_alat', 'MU')->first()->alatUnit()->count());
    }

    public function test_tambah_alat_tanpa_kode_alat_ditolak_dengan_pesan(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.alat.store'), [
                'nama_alat' => 'Meja Ujian',
                'kategori_id' => \App\Models\Kategori::factory()->create()->id,
                'jumlah_unit' => 3,
            ])
            ->assertSessionHasErrors('kode_alat');
    }

    // ---- BUG 1: tambah user tidak bisa ----

    public function test_tambah_user_berhasil(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.user.store'), [
                'name' => 'User Baru',
                'email' => 'baru@example.com',
                'password' => 'rahasia123',
                'password_confirmation' => 'rahasia123',
                'role' => 'peminjam',
            ])
            ->assertRedirect(route('admin.user.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', ['email' => 'baru@example.com']);
    }

    public function test_tambah_user_gagal_memberi_pesan_yang_mudah_dipahami(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.user.store'), [
                'name' => 'User Baru',
                'email' => 'baru2@example.com',
                'password' => 'pendek',
                'role' => 'peminjam',
            ])
            ->assertSessionHasErrors('password');
    }

    // ---- BUG 6: pesan login user nonaktif ----

    public function test_login_user_nonaktif_muncul_pesan_akun_dinonaktifkan(): void
    {
        $user = User::factory()->create([
            'email' => 'nonaktif@example.com',
            'is_aktif' => false,
        ]);

        $this->post(route('login'), [
            'email' => 'nonaktif@example.com',
            'password' => 'password',
        ])->assertSessionHasErrors(['email' => 'Akun Anda telah dinonaktifkan. Hubungi admin untuk mengaktifkan kembali.']);
    }
    // ---- BUG 3: dropdown serial kosong di form tambah peminjaman ----

    public function test_search_alat_mengirimkan_list_unit_serial(): void
    {
        $kategori = \App\Models\Kategori::factory()->create();
        $alat = Alat::factory()->denganUnit(3)->create([
            'kategori_id' => $kategori->id,
            'nama_alat' => 'Router Antik',
        ]);

        // Serial dibuat factory lewat serialBerikutnyaBatch, jadi prefix
        // mengikuti kode_alat alat ini -- bukan hardcode.
        $serialPertama = $alat->alatUnit()
            ->orderBy('serial_number')
            ->firstOrFail()
            ->serial_number;

        $response = $this->actingAs($this->admin())
            ->getJson(route('admin.search.alats', ['search' => 'Antik']))
            ->assertStatus(200)
            ->assertJsonPath('0.nama_alat', 'Router Antik')
            ->assertJsonPath('0.jumlah_tersedia', 3);

        $units = $response->json('0.units');

        // units wajib ada; sebelum fix searchAlat hanya mengirim
        // jumlah_tersedia, jadi dropdown serial kosong dan field
        // alat_unit_id[] required memblokir submit form peminjaman.
        $this->assertNotNull($units, 'searchAlat harus mengirimkan units[]');
        $this->assertCount(3, $units);
        $this->assertSame($serialPertama, $units[0]['serial_number']);
    }

    public function test_search_alat_hanya_kirim_unit_tersedia(): void
    {
        $kategori = \App\Models\Kategori::factory()->create();
        $alat = Alat::factory()->denganUnit(3)->create([
            'kategori_id' => $kategori->id,
            'nama_alat' => 'Kursor Nirkabel',
        ]);

        $alat->alatUnit()->first()->update(['kondisi' => 'rusak']);

        $response = $this->actingAs($this->admin())
            ->getJson(route('admin.search.alats', ['search' => 'Nirkabel']))
            ->assertStatus(200);

        $units = $response->json('0.units');

        $this->assertCount(2, $units);
        foreach ($units as $unit) {
            $this->assertSame('tersedia', $unit['kondisi']);
        }
    }

    // ---- BUG 7: modal Kelola Unit fixed, tombol Tambah Unit terjangkau ----

    public function test_halaman_index_alat_render_dengan_form_tambah_unit(): void
    {
        $kategori = \App\Models\Kategori::factory()->create();
        Alat::factory()->denganUnit(4)->create(['kategori_id' => $kategori->id]);

        $this->actingAs($this->admin())
            ->get(route('admin.alat.index'))
            ->assertStatus(200)
            ->assertSee('Tambah Unit (Serial Number)');
    }

    // ---- BUG modal Kelola Unit: teleport + field jumlah_unit ----

    public function test_halaman_index_alat_memakai_teleport_modal(): void
    {
        $kategori = \App\Models\Kategori::factory()->create();
        Alat::factory()->denganUnit(4)->create(['kategori_id' => $kategori->id]);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.alat.index'))
            ->assertStatus(200);

        // Modal dipindahkan ke <body> saat dibuka supaya lepas dari
        // transform ancestor (animasi motion) yang membatalkan
        // position:fixed -- sebelumnya modal ikut ter-scroll halaman
        // dan tombol Tambah Unit keluar viewport.
        $body = $response->getContent();
        $this->assertStringContainsString('data-teleport-modal', $body);
        $this->assertStringContainsString('modal-fixed', $body);
    }

    public function test_form_tambah_alat_memiliki_field_jumlah_unit(): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('admin.alat.create'))
            ->assertStatus(200);

        // assertSee meng-escape tanda kutip, jadi cek mentahnya.
        // Sebelumnya field ini tidak ada, jadi submit selalu memantul
        // kembali ke form = user melihat "looping".
        $this->assertStringContainsString(
            'name="jumlah_unit"',
            $response->getContent()
        );
    }

    // ---- Permintaan: konfirmasi logout + tombol tutup modal ----

    public function test_form_logout_memakai_konfirmasi(): void
    {
        // Semua layout: app (admin/petugas) dan peminjam.
        $this->actingAs($this->admin())
            ->get('/admin/dashboard')
            ->assertStatus(200)
            ->assertSee('data-confirm-logout');

        $peminjam = User::factory()->create(['role' => 'peminjam']);

        $this->actingAs($peminjam)
            ->get('/peminjam/katalog')
            ->assertStatus(200)
            ->assertSee('data-confirm-logout');
    }

    public function test_logout_masih_berfungsi_setelah_intercept(): void
    {
        // Interceptor confirm() jangan memutus logout beneran.
        $this->actingAs($this->admin())
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_modal_kelola_unit_punya_tombol_tutup(): void
    {
        $kategori = \App\Models\Kategori::factory()->create();
        $alat = Alat::factory()->denganUnit(3)->create(['kategori_id' => $kategori->id]);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.alat.index'))
            ->assertStatus(200);

        $body = $response->getContent();

        // Tombol X + id modal harus cocok supaya handler
        // data-close-modal bisa menutup details-nya.
        $this->assertStringContainsString('data-close-modal="unit-' . $alat->id . '"', $body);
        $this->assertStringContainsString('data-modal-id="unit-' . $alat->id . '"', $body);
        // Handler tombol tutup di motion.blade.php.
        $this->assertStringContainsString('data-close-modal', file_get_contents(base_path('resources/views/partials/motion.blade.php')));
    }

}
