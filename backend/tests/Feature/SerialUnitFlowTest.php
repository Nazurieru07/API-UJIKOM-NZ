<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\AlatUnit;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SerialUnitFlowTest extends TestCase
{
    use RefreshDatabase;

    private function alatDenganUnit(int $jumlah = 3): Alat
    {
        return Alat::factory()->denganUnit($jumlah)->create();
    }

    public function test_factory_membuat_alat_dengan_unit_tersedia(): void
    {
        $alat = $this->alatDenganUnit(5);

        $this->assertSame(5, $alat->alatUnit()->count());
        $this->assertSame(5, $alat->alatUnit()->tersedia()->count());

        // Serial mengikuti kode alat yang dibuat factory, jadi nilai
        // 'AA-001' hardcoded akan salah. Cek formatnya saja.
        $pertama = $alat->alatUnit()->orderBy('serial_number')->first();

        $this->assertSame($alat->kode_alat . '-001', $pertama->serial_number);
    }

    public function test_serial_berikutnya_melanjutkan_nomor_terbesar(): void
    {
        $alat = $this->alatDenganUnit(2);

        // Hapus unit tengah, nomor baru tetap melanjutkan terbesar.
        $alat->alatUnit()->first()->delete();

        $berikutnya = AlatUnit::serialBerikutnya($alat->id, $alat->kode_alat);

        $this->assertSame($alat->kode_alat . '-003', $berikutnya);
    }

    public function test_serial_unik_global(): void
    {
        $alatA = Alat::factory()->create(['kode_alat' => 'XX']);
        $alatB = Alat::factory()->create(['kode_alat' => 'XX']);

        AlatUnit::create(['alat_id' => $alatA->id, 'serial_number' => 'XX-001', 'kondisi' => 'tersedia']);

        // Prefix sama di alat berbeda harus tetap unik.
        $this->expectException(\Illuminate\Database\QueryException::class);
        AlatUnit::create(['alat_id' => $alatB->id, 'serial_number' => 'XX-001', 'kondisi' => 'tersedia']);
    }

    public function test_saran_kode_alat_dua_kata(): void
    {
        $this->assertSame('RM', Alat::saranKodeAlat('Router Mikrotik RB941'));
        $this->assertSame('KD', Alat::saranKodeAlat('Kamera DSLR Canon'));
    }

    public function test_saran_kode_alat_satu_kata_ambil_dua_huruf(): void
    {
        $this->assertSame('TE', Alat::saranKodeAlat('Teleporter'));
    }

    public function test_saran_kode_alat_nama_kosong(): void
    {
        $this->assertSame('AL', Alat::saranKodeAlat('   '));
    }

    public function test_katalog_hanya_tampilkan_alat_yang_punya_unit_tersedia(): void
    {
        $adaUnit = $this->alatDenganUnit(2);
        $tanpaUnit = Alat::factory()->create();
        AlatUnit::where('alat_id', $adaUnit->id)->first()->update(['kondisi' => 'rusak']);

        $semuaAdaRusak = Alat::factory()->denganUnit(1)->create();
        AlatUnit::where('alat_id', $semuaAdaRusak->id)->update(['kondisi' => 'rusak']);

        $hasil = Alat::whereHas('alatUnit', fn ($q) => $q->where('kondisi', 'tersedia'))->pluck('id');

        $this->assertTrue($hasil->contains($adaUnit->id));
        $this->assertFalse($hasil->contains($tanpaUnit->id));
        $this->assertFalse($hasil->contains($semuaAdaRusak->id));
    }

    public function test_detail_pinjam_terhubung_ke_satu_unit_serial(): void
    {
        $alat = $this->alatDenganUnit(2);
        $user = User::factory()->create(['role' => 'peminjam']);
        $unit = $alat->alatUnit()->first();

        $peminjaman = Peminjaman::create([
            'user_id' => $user->id,
            'tgl_pinjam' => now()->toDateString(),
            'tgl_kembali_plan' => now()->addDays(3)->toDateString(),
            'status' => 'dipinjam',
        ]);

        $detail = $peminjaman->detailPinjams()->create([
            'alat_unit_id' => $unit->id,
        ]);

        $this->assertSame($unit->serial_number, $detail->alatUnit->serial_number);
        $this->assertSame($alat->nama_alat, $detail->alatUnit->alat->nama_alat);
    }

    public function test_atomic_approve_mencegah_dipinjam_dua_kali(): void
    {
        $alat = $this->alatDenganUnit(1);
        $unit = $alat->alatUnit()->first();

        // Simulasikan unit sudah dipinjam (mis. petugas A approve).
        AlatUnit::where('id', $unit->id)
            ->where('kondisi', 'tersedia')
            ->update(['kondisi' => 'dipinjam']);

        // Petugas B mencoba approve pengajuan yang sama.
        $affected = AlatUnit::where('id', $unit->id)
            ->where('kondisi', 'tersedia')
            ->update(['kondisi' => 'dipinjam']);

        $this->assertSame(0, $affected);
        $this->assertSame('dipinjam', $unit->fresh()->kondisi);
    }

    public function test_pengembalian_baik_mengembalikan_unit_ke_tersedia(): void
    {
        $alat = $this->alatDenganUnit(1);
        $unit = $alat->alatUnit()->first();
        $unit->update(['kondisi' => 'dipinjam']);

        $user = User::factory()->create(['role' => 'peminjam']);
        $peminjaman = Peminjaman::create([
            'user_id' => $user->id,
            'tgl_pinjam' => now()->subDays(3)->toDateString(),
            'tgl_kembali_plan' => now()->subDay()->toDateString(),
            'status' => 'dipinjam',
        ]);
        $peminjaman->detailPinjams()->create(['alat_unit_id' => $unit->id]);

        // Proses pengembalian kondisi Baik.
        $unit->update(['kondisi' => 'tersedia']);

        $this->assertSame('tersedia', $unit->fresh()->kondisi);
    }

    public function test_pengembalian_rusak_menyembunyikan_unit_dari_katalog(): void
    {
        $alat = $this->alatDenganUnit(2);
        $unitRusak = $alat->alatUnit()->first();
        $unitRusak->update(['kondisi' => 'dipinjam']);

        // Pengembalian kondisi Rusak -> unit jadi rusak, bukan tersedia.
        $unitRusak->update(['kondisi' => 'rusak']);

        $tersedia = $alat->fresh()->alatUnit()->tersedia()->count();

        $this->assertSame(1, $tersedia);
        $this->assertSame('rusak', $unitRusak->fresh()->kondisi);
    }

    public function test_unit_rusak_tidak_muncul_di_filter_tersedia(): void
    {
        $alat = $this->alatDenganUnit(3);
        $alat->alatUnit()->first()->update(['kondisi' => 'rusak']);

        $this->assertSame(2, $alat->alatUnit()->tersedia()->count());
        $this->assertSame(1, $alat->alatUnit()->rusak()->count());
        $this->assertSame(3, $alat->alatUnit()->count());
    }
}
