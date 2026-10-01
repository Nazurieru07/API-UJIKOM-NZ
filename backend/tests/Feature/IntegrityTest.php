<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\AlatUnit;
use App\Models\DetailPinjam;
use App\Models\Kategori;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class IntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /**
     * Alat dengan N unit kondisi 'tersedia'.
     */
    private function alatDenganUnit(int $jumlah = 3): Alat
    {
        return Alat::factory()->denganUnit($jumlah)->create();
    }

    /**
     * Peminjaman berstatus 'dipinjam' yang memakai $jumlahUnit unit pertama,
     * dengan tiap unit ditandai 'dipinjam'.
     *
     * @return array{0: Peminjaman, 1: \Illuminate\Support\Collection<int, AlatUnit>}
     */
    private function peminjamanDipinjam(Alat $alat, int $jumlahUnit = 1): array
    {
        $user = User::factory()->create(['role' => 'peminjam']);

        $units = $alat->alatUnit()
            ->orderBy('serial_number')
            ->take($jumlahUnit)
            ->get();

        $peminjaman = Peminjaman::create([
            'user_id' => $user->id,
            'tgl_pinjam' => now()->toDateString(),
            'tgl_kembali_plan' => now()->addDays(3)->toDateString(),
            'status' => 'dipinjam',
        ]);

        foreach ($units as $unit) {
            DetailPinjam::create([
                'peminjaman_id' => $peminjaman->id,
                'alat_unit_id' => $unit->id,
            ]);

            $unit->update(['kondisi' => 'dipinjam']);
        }

        return [$peminjaman, $units];
    }

    /**
     * Unit 'dipinjam' yang tidak dipegang peminjaman berstatus
     * dipinjam/telat. Angka ini harus selalu 0 pada data yang sehat.
     */
    private function unitDipinjamTanpaPeminjamanAktif(): int
    {
        $unitDipinjamAktif = DetailPinjam::query()
            ->join('peminjaman', 'peminjaman.id', '=', 'detail_pinjam.peminjaman_id')
            ->whereIn('peminjaman.status', ['dipinjam', 'telat'])
            ->whereNotNull('detail_pinjam.alat_unit_id')
            ->pluck('detail_pinjam.alat_unit_id');

        return AlatUnit::where('kondisi', 'dipinjam')
            ->whereNotIn('id', $unitDipinjamAktif)
            ->count();
    }

    /*
    |--------------------------------------------------------------------------
    | detail_pinjam: satu baris = satu unit serial
    |--------------------------------------------------------------------------
    */

    public function test_alur_normal_tidak_pernah_menyimpan_detail_tanpa_unit(): void
    {
        $admin = $this->admin();
        $peminjam = User::factory()->create(['role' => 'peminjam']);
        $alat = $this->alatDenganUnit(3);

        $units = $alat->alatUnit()->orderBy('serial_number')->get();

        // 1. Peminjam mengajukan dua unit.
        $this->actingAs($peminjam)
            ->post(route('peminjam.peminjaman.ajukan'), [
                'tgl_kembali_plan' => now()->addDays(3)->toDateString(),
                'alat_id' => [$alat->id],
                'alat_unit_id' => [$units[0]->id, $units[1]->id],
            ])
            ->assertRedirect();

        $peminjaman = Peminjaman::where('user_id', $peminjam->id)->firstOrFail();

        // 2. Petugas/admin menyetujui: dua unit jadi 'dipinjam'.
        $this->actingAs($admin)
            ->put(route('admin.peminjaman.updateStatus', $peminjaman->id), [
                'status' => 'dipinjam',
            ])
            ->assertRedirect();

        $this->assertSame(2, $peminjaman->detailPinjams()->count());
        $this->assertSame(0, DetailPinjam::whereNull('alat_unit_id')->count());

        // Kedua unit yang dipinjam ditandai 'dipinjam'.
        foreach ($peminjaman->detailPinjams as $detail) {
            $this->assertSame('dipinjam', $detail->alatUnit->fresh()->kondisi);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Unit 'dipinjam' harus selalu dipegang peminjaman aktif
    |--------------------------------------------------------------------------
    */

    public function test_unit_dipinjam_selalu_dipegang_peminjaman_aktif(): void
    {
        $admin = $this->admin();
        $peminjam = User::factory()->create(['role' => 'peminjam']);
        $alat = $this->alatDenganUnit(2);
        $unit = $alat->alatUnit()->orderBy('serial_number')->first();

        $this->actingAs($peminjam)
            ->post(route('peminjam.peminjaman.ajukan'), [
                'tgl_kembali_plan' => now()->addDays(3)->toDateString(),
                'alat_id' => [$alat->id],
                'alat_unit_id' => [$unit->id],
            ])
            ->assertRedirect();

        $peminjaman = Peminjaman::where('user_id', $peminjam->id)->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.peminjaman.updateStatus', $peminjaman->id), [
                'status' => 'dipinjam',
            ])
            ->assertRedirect();

        // Status 'dipinjam' + unit 'dipinjam' = konsisten.
        $this->assertSame(0, $this->unitDipinjamTanpaPeminjamanAktif());

        // Status 'telat' juga aktif, jadi tetap konsisten.
        $peminjaman->update(['status' => 'telat']);
        $this->assertSame(0, $this->unitDipinjamTanpaPeminjamanAktif());

        // Sudah dikembalikan -> unit tidak boleh tertinggal 'dipinjam'.
        $peminjaman->update(['status' => 'dikembalikan']);
        $this->assertSame(1, $this->unitDipinjamTanpaPeminjamanAktif());
    }

    public function test_detail_tanpa_unit_atau_unit_orphan_tidak_bisa_ada(): void
    {
        $alat = $this->alatDenganUnit(2);
        $units = $alat->alatUnit()->orderBy('serial_number')->get();

        [$peminjaman] = $this->peminjamanDipinjam($alat, 2);

        // Tidak ada detail tanpa unit, dan tidak ada unit 'dipinjam' yatim.
        $this->assertSame(0, DetailPinjam::whereNull('alat_unit_id')->count());
        $this->assertSame(0, $this->unitDipinjamTanpaPeminjamanAktif());

        // Menutup peminjaman: detail ikut hilang (CASCADE peminjaman_id).
        $peminjaman->delete();

        $this->assertSame(0, DetailPinjam::where('alat_unit_id', $units[0]->id)->count());
    }

    /*
    |--------------------------------------------------------------------------
    | Serial number unik global
    |--------------------------------------------------------------------------
    */

    public function test_serial_number_unik_global(): void
    {
        $alatA = Alat::factory()->create(['kode_alat' => 'ZZ']);
        $alatB = Alat::factory()->create(['kode_alat' => 'ZZ']);

        AlatUnit::create([
            'alat_id' => $alatA->id,
            'serial_number' => 'ZZ-001',
            'kondisi' => 'tersedia',
        ]);

        // Serial sama di alat berbeda harus ditolak oleh unique index.
        $this->expectException(QueryException::class);

        AlatUnit::create([
            'alat_id' => $alatB->id,
            'serial_number' => 'ZZ-001',
            'kondisi' => 'tersedia',
        ]);
    }

    public function test_serial_ikut_nomor_terbesar_bukan_jumlah_unit(): void
    {
        $alat = $this->alatDenganUnit(2);

        // Unit tengah dihapus, nomor berikutnya tetap melanjutkan terbesar.
        $alat->alatUnit()->orderBy('serial_number')->first()->delete();

        $berikutnya = AlatUnit::serialBerikutnya($alat->id, $alat->kode_alat);

        $this->assertSame($alat->kode_alat . '-003', $berikutnya);
    }

    /*
    |--------------------------------------------------------------------------
    | Hapus alat: yang belum pernah dipinjam dihapus, yang punya riwayat
    | diarsipkan
    |--------------------------------------------------------------------------
    | Aturannya TIDAK lagi "punya unit -> arsip". Unit itu stok inventaris
    | yang dibuat otomatis setiap kali admin menambah alat, jadi syarat
    | itu membuat setiap alat baru tidak bisa dihapus -- tidak ada
    | bedanya dengan alat yang benar-benar pernah dipakai. Yang menentukan
    | adalah riwayat peminjaman.
    */

    public function test_alat_yang_belum_dipinjam_bisa_dihapus_bersama_unitnya(): void
    {
        $admin = $this->admin();
        $alat = $this->alatDenganUnit(3);

        $this->actingAs($admin)
            ->delete(route('admin.alat.destroy', $alat->id))
            ->assertRedirect();

        // Alat hilang permanen, unit ikut hilang lewat FK CASCADE.
        $this->assertDatabaseMissing('alat', ['id' => $alat->id]);
        $this->assertSame(0, AlatUnit::where('alat_id', $alat->id)->count());
    }

    public function test_alat_berkali_dipakai_jadi_arsip_bukan_hapus(): void
    {
        $admin = $this->admin();
        $alat = $this->alatDenganUnit(2);

        [$peminjaman, $units] = $this->peminjamanDipinjam($alat, 2);

        $this->actingAs($admin)
            ->delete(route('admin.alat.destroy', $alat->id))
            ->assertRedirect();

        $this->assertDatabaseHas('alat', ['id' => $alat->id, 'is_arsip' => true]);

        // Riwayat detail_pinjam per unit serial tetap utuh.
        foreach ($units as $unit) {
            $this->assertDatabaseHas('detail_pinjam', [
                'peminjaman_id' => $peminjaman->id,
                'alat_unit_id' => $unit->id,
            ]);
        }
    }

    public function test_alat_tanpa_unit_tanpa_riwayat_bisa_dihapus(): void
    {
        $admin = $this->admin();
        $alat = Alat::factory()->create();

        $this->actingAs($admin)
            ->delete(route('admin.alat.destroy', $alat->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('alat', ['id' => $alat->id]);
    }

    public function test_alat_diarsip_tidak_muncul_di_katalog(): void
    {
        $admin = $this->admin();
        $alat = $this->alatDenganUnit(2);

        $alat->update(['is_arsip' => true]);

        Sanctum::actingAs($admin, ['*']);

        $response = $this->getJson('/api/katalog');
        $response->assertOk();
        $response->assertJsonMissing(['id' => $alat->id]);
    }

    public function test_restore_alat_kembali_ke_katalog(): void
    {
        $admin = $this->admin();
        $alat = $this->alatDenganUnit(2);
        $alat->update(['is_arsip' => true]);

        $this->actingAs($admin)
            ->post(route('admin.alat.restore', $alat->id))
            ->assertRedirect();

        $this->assertFalse($alat->refresh()->is_arsip);
    }

    /*
    |--------------------------------------------------------------------------
    | FK alat_unit -> detail_pinjam: unit tak boleh hilang diam-diam
    |--------------------------------------------------------------------------
    |
    | Di MySQL FK-nya RESTRICT (lihat 2026_09_30_145000). SQLite tidak
    | bisa ditambah FK lewat ALTER, jadi migrasi yang sama melewati SQLite
    | dan jaminan itu dipindah ke level aplikasi: alat dengan unit tidak
    | pernah dihapus permanen, jadi unit yang punya riwayat detail_pinjam
    | tidak bisa hilang. Diuji lewat jalur aplikasi, bukan DB langsung.
    |
    */

    public function test_unit_dengan_riwayat_detail_tidak_bisa_hilang(): void
    {
        $admin = $this->admin();
        $alat = $this->alatDenganUnit(2);

        [$peminjaman, $units] = $this->peminjamanDipinjam($alat, 1);

        // Hapus alat lewat aplikasi (tidak boleh langsung hilang).
        $this->actingAs($admin)
            ->delete(route('admin.alat.destroy', $alat->id))
            ->assertRedirect();

        // Unit berseri historinya masih ada dan masih terhubung.
        foreach ($units as $unit) {
            $this->assertDatabaseHas('alat_unit', [
                'id' => $unit->id,
                'alat_id' => $alat->id,
            ]);
        }

        $this->assertDatabaseHas('detail_pinjam', [
            'peminjaman_id' => $peminjaman->id,
            'alat_unit_id' => $units[0]->id,
        ]);

        // Tidak ada detail yang menunjuk unit hilang.
        $unitIds = AlatUnit::pluck('id');
        $orphan = DetailPinjam::whereNotNull('alat_unit_id')
            ->whereNotIn('alat_unit_id', $unitIds)
            ->count();

        $this->assertSame(0, $orphan);
    }

    public function test_fk_detail_pinjam_ke_alat_unit_restrict_di_mysql(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            $this->markTestSkipped(
                'SQLite tidak mendukung penambahan FK lewat ALTER; migrasi '
                . '145000 melewati SQLite. Jaminan RESTRICT diuji lewat jalur aplikasi.'
            );
        }

        $alat = $this->alatDenganUnit(2);
        $unit = $alat->alatUnit()->first();
        [$peminjaman] = $this->peminjamanDipinjam($alat, 1);

        $this->expectException(QueryException::class);
        $unit->delete();
    }

    /*
    |--------------------------------------------------------------------------
    | Kategori
    |--------------------------------------------------------------------------
    */

    public function test_kategori_tidak_bisa_dihapus_kalau_punya_alat(): void
    {
        $admin = $this->admin();
        $kategori = Kategori::factory()->create();
        Alat::factory()->create(['kategori_id' => $kategori->id]);

        $this->assertTrue($kategori->alat()->exists());

        $response = $this->actingAs($admin)
            ->delete(route('admin.kategori.destroy', $kategori->id));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('kategori', ['id' => $kategori->id]);
        $this->assertSame(1, Alat::where('kategori_id', $kategori->id)->count());
    }

    public function test_kategori_kosong_bisa_dihapus(): void
    {
        $admin = $this->admin();
        $kategori = Kategori::factory()->create();

        $this->actingAs($admin)
            ->delete(route('admin.kategori.destroy', $kategori->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('kategori', ['id' => $kategori->id]);
    }

    public function test_api_kategori_tidak_bisa_dihapus_kalau_punya_alat(): void
    {
        $admin = $this->admin();
        $kategori = Kategori::factory()->create();
        Alat::factory()->create(['kategori_id' => $kategori->id]);

        Sanctum::actingAs($admin, ['*']);

        $this->deleteJson(route('kategori.destroy', $kategori->id))
            ->assertStatus(422);

        $this->assertDatabaseHas('kategori', ['id' => $kategori->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | Hapus peminjaman: unit balik ke tersedia, tidak tertinggal 'dipinjam'
    |--------------------------------------------------------------------------
    */

    public function test_hapus_peminjaman_mengembalikan_unit_ke_tersedia(): void
    {
        $admin = $this->admin();
        $alat = $this->alatDenganUnit(3);

        [$peminjaman, $units] = $this->peminjamanDipinjam($alat, 2);

        $this->actingAs($admin)
            ->delete(route('admin.peminjaman.destroy', $peminjaman->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('peminjaman', ['id' => $peminjaman->id]);
        $this->assertDatabaseMissing('detail_pinjam', ['peminjaman_id' => $peminjaman->id]);

        foreach ($units as $unit) {
            $this->assertSame('tersedia', $unit->fresh()->kondisi);
        }

        // Tidak ada unit yang tertinggal sebagai 'dipinjam' tanpa pemilik.
        $this->assertSame(0, $this->unitDipinjamTanpaPeminjamanAktif());

        // Dua unit dari tiga kembali tersedia.
        $this->assertSame(3, $alat->fresh()->alatUnit()->tersedia()->count());
    }
}
