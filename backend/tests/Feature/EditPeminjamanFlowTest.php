<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\AlatUnit;
use App\Models\Kategori;
use App\Models\Peminjaman;
use App\Models\PermintaanEditPeminjaman;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Alur pengajuan edit peminjaman per unit serial.
 *
 * Peminjam tidak lagi menambah/mengurangi "jumlah alat", tapi menyebut
 * unit serial tertentu: aksi 'tambah' untuk minta unit baru, 'hapus'
 * untuk melepas unit yang sedang dipinjam. Kondisi unit baru berubah
 * saat petugas menyetujui, bukan saat pengajuan dibuat.
 */
class EditPeminjamanFlowTest extends TestCase
{
    use RefreshDatabase;

    private $peminjam;
    private $petugas;
    private $peminjaman;
    private $alat;
    private $unitDipinjam;

    protected function setUp(): void
    {
        parent::setUp();

        $this->peminjam = User::create([
            'name' => 'Peminjam Test',
            'email' => 'peminjam.test@mail.com',
            'password' => bcrypt('password'),
            'role' => 'peminjam',
        ]);

        $this->petugas = User::create([
            'name' => 'Petugas Test',
            'email' => 'petugas.test@mail.com',
            'password' => bcrypt('password'),
            'role' => 'petugas',
        ]);

        $kategori = Kategori::create(['nama_kategori' => 'Kategori Test']);

        $this->alat = Alat::create([
            'nama_alat' => 'Alat Test',
            'kategori_id' => $kategori->id,
            'kode_alat' => 'AT',
        ]);

        // Tiga unit: satu sedang dipinjam, dua masih tersedia.
        foreach ([1, 2, 3] as $i) {
            AlatUnit::create([
                'alat_id' => $this->alat->id,
                'serial_number' => 'AT-' . str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'kondisi' => 'tersedia',
            ]);
        }

        $units = $this->alat->alatUnit()->orderBy('serial_number')->get();
        $this->unitDipinjam = $units[0];

        $this->peminjaman = Peminjaman::create([
            'user_id' => $this->peminjam->id,
            'tgl_pinjam' => now()->toDateString(),
            'tgl_kembali_plan' => now()->addDays(3)->toDateString(),
            'status' => 'dipinjam',
            'keterangan' => null,
        ]);

        $this->peminjaman->detailPinjams()->create([
            'alat_unit_id' => $this->unitDipinjam->id,
        ]);

        $this->unitDipinjam->update(['kondisi' => 'dipinjam']);
    }

    private function buatPermintaan(string $alasan = 'Test'): PermintaanEditPeminjaman
    {
        return PermintaanEditPeminjaman::create([
            'peminjaman_id' => $this->peminjaman->id,
            'user_id' => $this->peminjam->id,
            'tgl_kembali_plan_baru' => now()->addDays(7)->toDateString(),
            'alasan' => $alasan,
            'status' => 'menunggu',
        ]);
    }

    private function unitTersedia(int $ke = 1): ?AlatUnit
    {
        // Re-query tiap pemanggilan: kondisi unit berubah saat permintaan
        // edit disetujui, jadi instance alat yang di-cache di setUp() tidak
        // lagi mencerminkan unit yang masih tersedia.
        return $this->alat->fresh()->alatUnit()
            ->tersedia()
            ->orderBy('serial_number')
            ->get()
            ->get($ke - 1);
    }

    /**
     * Unit berdasarkan serial, tanpa mempedulikan kondisinya. Dipakai
     * untuk memverifikasi unit yang TIDAK disebut permintaan tetap utuh.
     */
    private function unit(string $serial): AlatUnit
    {
        return $this->alat->alatUnit()
            ->where('serial_number', $serial)
            ->firstOrFail();
    }

    /*
    |--------------------------------------------------------------------------
    | Peminjam
    |--------------------------------------------------------------------------
    */

    public function test_peminjam_bisa_melihat_form_edit()
    {
        $this->actingAs($this->peminjam);

        $response = $this->get(route('peminjam.edit.form', $this->peminjaman->id));

        $response->assertStatus(200);
        $response->assertSee('Ajukan Edit Peminjaman');
        // Serial unit yang sedang dipinjam tampil di form.
        $response->assertSee($this->unitDipinjam->serial_number);
    }

    public function test_peminjam_bisa_ajukan_edit_tanggal_saja()
    {
        $this->actingAs($this->peminjam);

        $this->post(route('peminjam.edit.ajukan', $this->peminjaman->id), [
            'tgl_kembali_plan_baru' => now()->addDays(7)->format('Y-m-d'),
            'alasan' => 'Butuh lebih lama',
        ])->assertRedirect();

        $this->assertDatabaseHas('permintaan_edit_peminjaman', [
            'peminjaman_id' => $this->peminjaman->id,
            'status' => 'menunggu',
            'tgl_kembali_plan_baru' => now()->addDays(7)->format('Y-m-d'),
        ]);

        // Tidak ada baris detail kalau tidak ada unit yang disebut.
        $this->assertSame(0, \App\Models\DetailPermintaanEdit::count());
    }

    public function test_peminjam_bisa_ajukan_tambah_unit_serial()
    {
        $unitBaru = $this->unitTersedia(1);

        $this->actingAs($this->peminjam);

        $this->post(route('peminjam.edit.ajukan', $this->peminjaman->id), [
            'tgl_kembali_plan_baru' => now()->addDays(7)->format('Y-m-d'),
            'alasan' => 'Tambah unit',
            'alat_unit_id' => [$unitBaru->id],
            'aksi' => ['tambah'],
        ])->assertRedirect();

        $this->assertDatabaseHas('detail_permintaan_edit', [
            'permintaan_edit_id' => PermintaanEditPeminjaman::latest('id')->first()->id,
            'alat_unit_id' => $unitBaru->id,
            'aksi' => 'tambah',
        ]);

        // Pengajuan belum mengubah apa pun: unit masih 'tersedia'.
        $this->assertSame('tersedia', $unitBaru->fresh()->kondisi);
        $this->assertSame(1, $this->peminjaman->detailPinjams()->count());
    }

    public function test_peminjam_bisa_ajukan_hapus_unit_yang_dipinjam()
    {
        $this->actingAs($this->peminjam);

        $this->post(route('peminjam.edit.ajukan', $this->peminjaman->id), [
            'tgl_kembali_plan_baru' => now()->addDays(7)->format('Y-m-d'),
            'alasan' => 'Kembalikan satu unit',
            'alat_unit_id' => [$this->unitDipinjam->id],
            'aksi' => ['hapus'],
        ])->assertRedirect();

        $this->assertDatabaseHas('detail_permintaan_edit', [
            'alat_unit_id' => $this->unitDipinjam->id,
            'aksi' => 'hapus',
        ]);

        // Unit masih dipinjam sampai petugas menyetujui.
        $this->assertSame('dipinjam', $this->unitDipinjam->fresh()->kondisi);
        $this->assertSame(1, $this->peminjaman->detailPinjams()->count());
    }

    public function test_tidak_bisa_ajukan_edit_duplikat()
    {
        $this->actingAs($this->peminjam);

        $this->post(route('peminjam.edit.ajukan', $this->peminjaman->id), [
            'tgl_kembali_plan_baru' => now()->addDays(7)->format('Y-m-d'),
        ]);

        $this->post(route('peminjam.edit.ajukan', $this->peminjaman->id), [
            'tgl_kembali_plan_baru' => now()->addDays(10)->format('Y-m-d'),
        ])->assertSessionHas('error');

        $this->assertSame(1, PermintaanEditPeminjaman::count());
    }

    /*
    |--------------------------------------------------------------------------
    | Petugas
    |--------------------------------------------------------------------------
    */

    public function test_petugas_bisa_melihat_daftar_permintaan()
    {
        $this->buatPermintaan('Perlu unit tambahan');

        $response = $this->actingAs($this->petugas)
            ->get(route('petugas.edit-peminjaman.index'));

        $response->assertStatus(200);
        $response->assertSee('Perlu unit tambahan');
    }

    public function test_petugas_bisa_menolak_permintaan()
    {
        $unitBaru = $this->unitTersedia(1);
        $permintaan = $this->buatPermintaan();
        $permintaan->detailEdits()->create([
            'alat_unit_id' => $unitBaru->id,
            'aksi' => 'tambah',
        ]);

        $this->actingAs($this->petugas);

        $this->post(route('petugas.edit-peminjaman.tolak', $permintaan->id), [
            'catatan_penolakan' => 'Tidak sesuai prosedur',
        ])->assertRedirect();

        $this->assertDatabaseHas('permintaan_edit_peminjaman', [
            'id' => $permintaan->id,
            'status' => 'ditolak',
        ]);

        // Ditolak: tidak ada perubahan pada unit maupun peminjaman.
        $this->assertSame('tersedia', $unitBaru->fresh()->kondisi);
        $this->assertSame(1, $this->peminjaman->detailPinjams()->count());
        $this->assertSame(
            now()->addDays(3)->format('Y-m-d'),
            $this->peminjaman->fresh()->tgl_kembali_plan->format('Y-m-d')
        );
    }

    public function test_setujui_tambah_unit_membuat_detail_baru_dan_claim_unit()
    {
        $unitBaru = $this->unitTersedia(1);
        $permintaan = $this->buatPermintaan();
        $permintaan->detailEdits()->create([
            'alat_unit_id' => $unitBaru->id,
            'aksi' => 'tambah',
        ]);

        $this->actingAs($this->petugas);

        $this->post(route('petugas.edit-peminjaman.setujui', $permintaan->id))
            ->assertRedirect();

        $this->assertDatabaseHas('permintaan_edit_peminjaman', [
            'id' => $permintaan->id,
            'status' => 'disetujui',
        ]);

        // Unit baru jadi 'dipinjam' dan masuk peminjaman ini.
        $this->assertSame('dipinjam', $unitBaru->fresh()->kondisi);

        $this->assertDatabaseHas('detail_pinjam', [
            'peminjaman_id' => $this->peminjaman->id,
            'alat_unit_id' => $unitBaru->id,
        ]);

        $this->assertSame(2, $this->peminjaman->detailPinjams()->count());

        // Tanggal rencana ikut diperbarui.
        $this->assertSame(
            now()->addDays(7)->format('Y-m-d'),
            $this->peminjaman->fresh()->tgl_kembali_plan->format('Y-m-d')
        );

        // Unit ketiga (AT-003) tidak ikut berubah: masih 'tersedia'.
        $this->assertSame('tersedia', $this->unit('AT-003')->fresh()->kondisi);
    }

    public function test_setujui_hapus_unit_melepas_detail_dan_kembalikan_unit()
    {
        $permintaan = $this->buatPermintaan();
        $permintaan->detailEdits()->create([
            'alat_unit_id' => $this->unitDipinjam->id,
            'aksi' => 'hapus',
        ]);

        $this->actingAs($this->petugas);

        $this->post(route('petugas.edit-peminjaman.setujui', $permintaan->id))
            ->assertRedirect();

        $this->assertDatabaseHas('permintaan_edit_peminjaman', [
            'id' => $permintaan->id,
            'status' => 'disetujui',
        ]);

        // Baris detail_pinjam unit itu hilang.
        $this->assertDatabaseMissing('detail_pinjam', [
            'peminjaman_id' => $this->peminjaman->id,
            'alat_unit_id' => $this->unitDipinjam->id,
        ]);

        // Unit kembali bisa dipinjam.
        $this->assertSame('tersedia', $this->unitDipinjam->fresh()->kondisi);
        $this->assertSame(0, $this->peminjaman->detailPinjams()->count());
    }

    public function test_setujui_gagal_kalau_unit_tidak_lagi_tersedia()
    {
        $unitBaru = $this->unitTersedia(1);
        $permintaan = $this->buatPermintaan();
        $permintaan->detailEdits()->create([
            'alat_unit_id' => $unitBaru->id,
            'aksi' => 'tambah',
        ]);

        // Setelah pengajuan, unit dipakai petugas lain / jadi rusak.
        $unitBaru->update(['kondisi' => 'rusak']);

        $this->actingAs($this->petugas);

        $this->post(route('petugas.edit-peminjaman.setujui', $permintaan->id))
            ->assertSessionHas('error');

        // Tidak ada detail baru, unit tetap rusak, permintaan belum diproses.
        $this->assertDatabaseMissing('detail_pinjam', [
            'peminjaman_id' => $this->peminjaman->id,
            'alat_unit_id' => $unitBaru->id,
        ]);

        $this->assertSame('rusak', $unitBaru->fresh()->kondisi);
        $this->assertDatabaseHas('permintaan_edit_peminjaman', [
            'id' => $permintaan->id,
            'status' => 'menunggu',
        ]);
    }

    public function test_percaya_edit_hanya_satu_kali()
    {
        $permintaan = $this->buatPermintaan();
        $permintaan->detailEdits()->create([
            'alat_unit_id' => $this->unitDipinjam->id,
            'aksi' => 'hapus',
        ]);

        $this->actingAs($this->petugas);

        $this->post(route('petugas.edit-peminjaman.setujui', $permintaan->id))
            ->assertRedirect();

        // Percobaan kedua: permintaan sudah diproses, unit tidak changed lagi.
        $this->post(route('petugas.edit-peminjaman.setujui', $permintaan->id))
            ->assertSessionHas('error');

        $this->assertSame(0, $this->peminjaman->detailPinjams()->count());
        $this->assertSame('tersedia', $this->unitDipinjam->fresh()->kondisi);
    }
}
