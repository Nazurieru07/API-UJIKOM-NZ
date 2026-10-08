<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\AlatUnit;
use App\Models\DetailPinjam;
use App\Models\Peminjaman;
use App\Models\Pengembalian;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Petugas memeriksa & menyetujui pengembalian yang diajukan peminjam.
 *
 * Alur: peminjam memilih "Diproses Oleh: Petugas" saat mengajukan,
 * jadi antrean ini milik petugas. Efek approval-nya sama dengan
 * admin: unit kembali ke katalog. Yang membedakan: petugas_id diisi
 * petugas yang menyetujui, sehingga pengembalian ini masuk ke
 * laporan petugas tersebut, bukan hanya laporan admin.
 */
class PetugasPengembalianTest extends TestCase
{
    use RefreshDatabase;

    private function petugas(): User
    {
        return User::factory()->create(['role' => 'petugas']);
    }

    private function peminjam(): User
    {
        return User::factory()->create(['role' => 'peminjam']);
    }

    /**
     * Peminjaman aktif dengan satu unit serial yang terpakai.
     */
    private function peminjamanDenganUnit(User $peminjam, string $serial): Peminjaman
    {
        $peminjaman = Peminjaman::factory()->create([
            'user_id' => $peminjam->id,
            'status' => 'dipinjam',
        ]);

        $alat = Alat::factory()->create();

        $unit = AlatUnit::create([
            'alat_id' => $alat->id,
            'serial_number' => $serial,
            'kondisi' => 'dipinjam',
        ]);

        DetailPinjam::create([
            'peminjaman_id' => $peminjaman->id,
            'alat_unit_id' => $unit->id,
        ]);

        return $peminjaman->fresh(['detailPinjams.alatUnit']);
    }

    private function pengembalianMenunggu(Peminjaman $peminjaman): Pengembalian
    {
        return Pengembalian::create([
            'peminjaman_id' => $peminjaman->id,
            'petugas_id' => null,
            'tgl_kembali' => now(),
            'kondisi_kembali' => null,
            'denda_kerusakan' => 0,
            'denda' => 0,
            'status_request' => 'menunggu',
            'catatan_peminjam' => 'Balikin ke petugas.',
            'diproses_oleh' => 'petugas',
        ]);
    }

    // --------------------------------------------------------------

    public function test_antrean_petugas_menampilkan_pengajuan_dari_peminjam(): void
    {
        $petugas = $this->petugas();
        $peminjam = $this->peminjam();

        $peminjaman = $this->peminjamanDenganUnit($peminjam, 'APX-ANTRE-01');
        $this->pengembalianMenunggu($peminjaman);

        $response = $this->actingAs($petugas)
            ->get(route('petugas.pengembalian.index'))
            ->assertOk();

        $response->assertSeeText('APX-ANTRE-01');
        // Badge pembeda supaya petugas tahu ini pengajuan peminjam.
        $response->assertSeeText('Dari Peminjam');
    }

    public function test_antrean_petugas_tidak_menampilkan_pengajuan_untuk_admin(): void
    {
        $petugas = $this->petugas();
        $peminjam = $this->peminjam();

        $peminjaman = $this->peminjamanDenganUnit($peminjam, 'APX-ANTRE-02');

        Pengembalian::create([
            'peminjaman_id' => $peminjaman->id,
            'petugas_id' => null,
            'tgl_kembali' => now(),
            'kondisi_kembali' => null,
            'denda_kerusakan' => 0,
            'denda' => 0,
            'status_request' => 'menunggu',
            'catatan_peminjam' => 'Untuk admin.',
            'diproses_oleh' => 'admin',
        ]);

        $response = $this->actingAs($petugas)
            ->get(route('petugas.pengembalian.index'))
            ->assertOk();

        // Khusus admin -> bukan antrean petugas.
        $response->assertDontSeeText('APX-ANTRE-02');
    }

    public function test_petugas_bisa_menyimpan_hasil_pemeriksaan(): void
    {
        $petugas = $this->petugas();
        $peminjam = $this->peminjam();

        $peminjaman = $this->peminjamanDenganUnit($peminjam, 'APX-ANTRE-03');
        $pengembalian = $this->pengembalianMenunggu($peminjaman);

        $this->actingAs($petugas)
            ->put(
                route('petugas.pengembalian.pemeriksaan', $pengembalian->id),
                [
                    'kondisi_kembali' => 'Baik',
                    'denda_kerusakan' => 0,
                ]
            )->assertRedirect();

        $this->assertDatabaseHas('pengembalian', [
            'id' => $pengembalian->id,
            'kondisi_kembali' => 'Baik',
            'denda_kerusakan' => 0,
            // status belum berubah: petugas belum approve.
            'status_request' => 'menunggu',
        ]);
    }

    public function test_petugas_menyetujui_mengembalikan_unit_dan_isi_petugas_id(): void
    {
        $petugas = $this->petugas();
        $peminjam = $this->peminjam();

        $peminjaman = $this->peminjamanDenganUnit($peminjam, 'APX-SETUJU-01');
        $pengembalian = $this->pengembalianMenunggu($peminjaman);

        // Approve tanpa kondisi: harus ditolak (null terbaca rusak).
        $this->actingAs($petugas)
            ->post(route('petugas.pengembalian.setujui', $pengembalian->id))
            ->assertRedirect();

        $this->assertSame('menunggu', $pengembalian->fresh()->status_request);

        // Lalu isi kondisi.
        $pengembalian->update(['kondisi_kembali' => 'Baik']);

        Notification::fake();

        $this->actingAs($petugas)
            ->post(route('petugas.pengembalian.setujui', $pengembalian->id))
            ->assertRedirect(route('petugas.pengembalian.index'));

        // Status peminjaman & unit.
        $this->assertSame('dikembalikan', $peminjaman->fresh()->status);
        $unit = $peminjaman->fresh('detailPinjams.alatUnit')
            ->detailPinjams->first()->alatUnit;
        $this->assertSame('tersedia', $unit->fresh()->kondisi);

        // Penentu laporan: petugas_id diisi petugas yang approve.
        $this->assertSame($petugas->id, $pengembalian->fresh()->petugas_id);
        $this->assertSame('disetujui', $pengembalian->fresh()->status_request);

        // Peminjam diberi tahu pengembalian selesai.
        Notification::assertSentTo(
            $peminjam,
            \App\Notifications\PengembalianSelesaiNotification::class
        );

        // Log aktivitas petugas tercatat.
        $this->assertDatabaseHas('log_aktivitas', [
            'user_id' => $petugas->id,
        ]);
    }

    public function test_kondisi_rusak_menandai_unit_rusak(): void
    {
        $petugas = $this->petugas();
        $peminjam = $this->peminjam();

        $peminjaman = $this->peminjamanDenganUnit($peminjam, 'APX-SETUJU-02');
        $pengembalian = $this->pengembalianMenunggu($peminjaman);

        $pengembalian->update([
            'kondisi_kembali' => 'Rusak',
            'denda_kerusakan' => 50000,
        ]);

        $this->actingAs($petugas)
            ->post(route('petugas.pengembalian.setujui', $pengembalian->id));

        $unit = $peminjaman->fresh('detailPinjams.alatUnit')
            ->detailPinjams->first()->alatUnit;
        $this->assertSame('rusak', $unit->fresh()->kondisi);
        $this->assertSame('dikembalikan', $peminjaman->fresh()->status);
    }

    public function test_petugas_menolak_pengajuan_tidak_mengubah_unit(): void
    {
        $petugas = $this->petugas();
        $peminjam = $this->peminjam();

        $peminjaman = $this->peminjamanDenganUnit($peminjam, 'APX-TOLAK-01');
        $pengembalian = $this->pengembalianMenunggu($peminjaman);

        $this->actingAs($petugas)
            ->post(route('petugas.pengembalian.tolak', $pengembalian->id))
            ->assertRedirect(route('petugas.pengembalian.index'));

        $this->assertSame('ditolak', $pengembalian->fresh()->status_request);
        $this->assertSame('dipinjam', $peminjaman->fresh()->status);
        $unit = $peminjaman->fresh('detailPinjams.alatUnit')
            ->detailPinjams->first()->alatUnit;
        $this->assertSame('dipinjam', $unit->fresh()->kondisi);
    }

    public function test_pengajuan_yang_sudah_diproses_tidak_bisa_diproses_lagi(): void
    {
        $petugas = $this->petugas();
        $peminjam = $this->peminjam();

        $peminjaman = $this->peminjamanDenganUnit($peminjam, 'APX-DUA-01');
        $pengembalian = $this->pengembalianMenunggu($peminjaman);
        $pengembalian->update(['status_request' => 'disetujui']);

        // Approve ulang: harus redirect dengan error, tidak ada perubahan.
        $this->actingAs($petugas)
            ->post(route('petugas.pengembalian.setujui', $pengembalian->id))
            ->assertRedirect();

        // Tolak ulang juga.
        $this->actingAs($petugas)
            ->post(route('petugas.pengembalian.tolak', $pengembalian->id))
            ->assertRedirect();

        // Tidak ada double approve/tolak.
        $this->assertSame('disetujui', $pengembalian->fresh()->status_request);
    }

    public function test_laporan_petugas_memuat_pengembalian_yang_disetujui_petugas(): void
    {
        $petugas = $this->petugas();
        $peminjam = $this->peminjam();

        $peminjaman = $this->peminjamanDenganUnit($peminjam, 'APX-LAP-01');
        $pengembalian = $this->pengembalianMenunggu($peminjaman);
        $pengembalian->update([
            'status_request' => 'disetujui',
            'kondisi_kembali' => 'Baik',
            'petugas_id' => $petugas->id,
        ]);

        $response = $this->actingAs($petugas)
            ->get(route('petugas.laporan.index'))
            ->assertOk();

        $response->assertSeeText('APX-LAP-01');
    }

    public function test_laporan_petugas_tidak_memuat_pengembalian_disetujui_oleh_lain(): void
    {
        $petugas = $this->petugas();
        $petugasLain = $this->petugas();
        $peminjam = $this->peminjam();

        $peminjaman = $this->peminjamanDenganUnit($peminjam, 'APX-LAP-02');
        $pengembalian = $this->pengembalianMenunggu($peminjaman);
        $pengembalian->update([
            'status_request' => 'disetujui',
            'kondisi_kembali' => 'Baik',
            'petugas_id' => $petugasLain->id,
        ]);

        $response = $this->actingAs($petugas)
            ->get(route('petugas.laporan.index'))
            ->assertOk();

        // Milik petugas lain -> tidak muncul di laporan saya.
        $response->assertDontSeeText('APX-LAP-02');
    }
}
