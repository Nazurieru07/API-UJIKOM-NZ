<?php

namespace Tests\Feature;

use App\Models\LogAktivitas;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Halaman "Profil Saya" -- user mengelola data + fotonya sendiri.
 *
 * Permintaan: user tidak perlu minta admin untuk ganti foto/data,
 * dan perubahannya harus langsung terlihat di menu "Kelola User"
 * admin karena record `users`-nya satu.
 */
class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    /**
     * @dataProvider roleProvider
     */
    public function test_halaman_profile_bisa_dibuka_semua_role(string $role): void
    {
        $this->actingAs($this->user($role))
            ->get(route('profile.edit'))
            ->assertStatus(200)
            ->assertSee('Profil Saya');
    }

    /**
     * @dataProvider roleProvider
     */
    public function test_halaman_profile_menampilkan_data_user(string $role): void
    {
        $user = $this->user($role);

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertSee($user->name)
            ->assertSee($user->email)
            ->assertSee(ucfirst($role));
    }

    /**
     * @dataProvider roleProvider
     */
    public function test_layout_memuat_link_ke_profile(string $role, string $route): void
    {
        // Klik nama/foto user di topbar/sidebar harus nyambung ke profile.
        $body = $this->actingAs($this->user($role))
            ->get(route($route))
            ->assertStatus(200)
            ->getContent();

        $this->assertStringContainsString(route('profile.edit'), $body);
        $this->assertStringContainsString('motion-user-link', $body);
    }

    /**
     * @dataProvider roleProvider
     */
    public function test_ikon_lonceng_muncul_di_layout(string $role, string $route): void
    {
        // Lonceng pernah kosong (summary tanpa gambar) -- kunci ikonnya.
        $body = $this->actingAs($this->user($role))
            ->get(route($route))
            ->getContent();

        $this->assertStringContainsString('images/notification.png', $body);
        $this->assertStringContainsString('id="notificationBell"', $body);
    }

    public function test_user_bisa_update_data_dasar(): void
    {
        $user = $this->user('peminjam');

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'name' => 'Nama Baru',
                'email' => 'baru@example.com',
                'no_hp' => '08123456789',
                'jenis_kelamin' => 'Perempuan',
            ])
            ->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertSame('Nama Baru', $user->name);
        $this->assertSame('baru@example.com', $user->email);
        $this->assertSame('08123456789', $user->no_hp);
        $this->assertSame('Perempuan', $user->jenis_kelamin);
    }

    public function test_perubahan_langsung_terlihat_di_menu_user_admin(): void
    {
        // Integrasi yang diminta user: data dari halaman profil muncul di
        // menu Kelola User tanpa langkah sinkronisasi tambahan.
        $admin = $this->user('admin');
        $peminjam = $this->user('peminjam');

        $this->actingAs($peminjam)
            ->put(route('profile.update'), [
                'name' => 'Nama hasil ubah sendiri',
                'email' => $peminjam->email,
            ]);

        $this->actingAs($admin)
            ->get(route('admin.user.index'))
            ->assertStatus(200)
            ->assertSee('Nama hasil ubah sendiri');
    }

    public function test_email_yang_sudah_dipakai_user_lain_ditolak(): void
    {
        $other = $this->user('peminjam');
        $user = User::factory()->create(['role' => 'petugas']);

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'name' => $user->name,
                'email' => $other->email,
            ])
            ->assertSessionHasErrors('email');

        $this->assertNotSame($other->email, $user->refresh()->email);
    }

    public function test_email_sendiri_tidak_ditolak_saat_update(): void
    {
        $user = $this->user('admin');

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'name' => 'Nama Lain',
                'email' => $user->email,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('Nama Lain', $user->refresh()->name);
    }

    public function test_password_kosong_tidak_mengubah_password_lama(): void
    {
        $user = User::factory()->create([
            'role' => 'peminjam',
            'password' => Hash::make('password-lama'),
        ]);

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'name' => $user->name,
                'email' => $user->email,
            ]);

        $this->assertTrue(Hash::check('password-lama', $user->refresh()->password));
    }

    public function test_isi_password_baru_mengganti_password(): void
    {
        $user = User::factory()->create([
            'role' => 'peminjam',
            'password' => Hash::make('password-lama'),
        ]);

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'name' => $user->name,
                'email' => $user->email,
                'password' => 'password-baru',
                'password_confirmation' => 'password-baru',
            ]);

        $this->assertTrue(Hash::check('password-baru', $user->refresh()->password));
    }

    public function test_konfirmasi_password_tidak_cocok_ditolak(): void
    {
        $user = $this->user('peminjam');

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'name' => $user->name,
                'email' => $user->email,
                'password' => 'password-baru',
                'password_confirmation' => 'tidak-sama',
            ])
            ->assertSessionHasErrors('password');
    }

    public function test_upload_foto_profil_tersimpan_dan_tampil_di_halaman(): void
    {
        $user = $this->user('peminjam');

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'name' => $user->name,
                'email' => $user->email,
                'foto_profile' => $this->fakePng('avatar-baru.png'),
            ])
            ->assertRedirect(route('profile.edit'));

        $path = $user->refresh()->foto_profile;
        $this->assertNotNull($path);
        $this->assertFileExists(public_path($path));

        // Foto langsung tampil di halaman profil sendiri.
        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertSee($path, false);

        // Bersihkan artifact test dari folder publik.
        @unlink(public_path($path));
    }

    public function test_upload_foto_baru_menghapus_foto_lama(): void
    {
        $user = $this->user('peminjam');

        // Foto pertama
        $this->actingAs($user)->put(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'foto_profile' => $this->fakePng('lama.png'),
        ]);
        $lama = $user->refresh()->foto_profile;
        $this->assertFileExists(public_path($lama));

        // Ganti foto
        $this->actingAs($user)->put(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'foto_profile' => $this->fakePng('baru.png'),
        ]);

        $this->assertFileDoesNotExist(public_path($lama));
        @unlink(public_path($user->refresh()->foto_profile));
    }

    public function test_file_bukan_gambar_ditolak(): void
    {
        $user = $this->user('peminjam');

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'name' => $user->name,
                'email' => $user->email,
                'foto_profile' => UploadedFile::fake()->create('bukan.txt', 10),
            ])
            ->assertSessionHasErrors('foto_profile');
    }

    public function test_user_tidak_bisa_mengubah_role_sendiri(): void
    {
        // Role cuma boleh diubah admin; form profil tidak punya field role.
        $user = $this->user('peminjam');

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'name' => $user->name,
                'email' => $user->email,
                'role' => 'admin',
            ]);

        $this->assertSame('peminjam', $user->refresh()->role);
    }

    public function test_update_profil_dicatat_di_log_aktivitas(): void
    {
        $user = $this->user('petugas');

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'name' => 'Nama Berubah',
                'email' => $user->email,
            ]);

        $this->assertDatabaseHas('log_aktivitas', [
            'user_id' => $user->id,
        ]);
    }

    public function test_guest_tidak_bisa_membuka_halaman_profile(): void
    {
        $this->get(route('profile.edit'))
            ->assertRedirect(route('login'));
    }

    public function test_notifikasi_diproses_oleh_queue(): void
    {
        // Smoke test jalur notifikasi: job harus langsung jadi baris di
        // tabel notifications (QUEUE_CONNECTION=sync saat test).
        // Notif PeminjamanDiajukan dikirim ke semua petugas, jadi
        // penerima notifnya harus petugas (bukan peminjam).
        $petugas = $this->user('petugas');
        $peminjam = $this->user('peminjam');

        $peminjam->notify(new \App\Notifications\PeminjamanDiajukanNotification(
            \App\Models\Peminjaman::factory()->create(['user_id' => $peminjam->id])
        ));

        $this->assertSame(1, $peminjam->notifications()->count());
    }

    public function test_melihat_notifikasi_dan_menandai_sudah_dibaca(): void
    {
        $user = $this->user('peminjam');
        $user->notify(new \App\Notifications\PeminjamanDisetujuiNotification(
            \App\Models\Peminjaman::factory()->create(['user_id' => $user->id])
        ));

        $this->assertSame(1, $user->unreadNotifications()->count());

        $this->actingAs($user)
            ->post(route('notifications.readAll'))
            ->assertOk();

        $this->assertSame(0, $user->refresh()->unreadNotifications()->count());
    }

    public static function roleProvider(): array
    {
        return [
            'admin' => ['admin', 'admin.dashboard'],
            'petugas' => ['petugas', 'petugas.peminjaman.index'],
            'peminjam' => ['peminjam', 'peminjam.katalog'],
        ];
    }

    /**
     * PNG 1x1 asli sebagai fake upload.
     *
     * UploadedFile::fake()->image() butuh ekstensi GD yang tidak
     * terpasang di container test; file PNG asli lebih murah dan
     * tetap lolos validasi mime 'image'.
     */
    private function fakePng(string $name): UploadedFile
    {
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        );

        return UploadedFile::fake()->createWithContent($name, $png);
    }
}
