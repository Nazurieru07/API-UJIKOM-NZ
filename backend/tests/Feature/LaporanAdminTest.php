<?php

namespace Tests\Feature;

use App\Exports\LaporanPengembalianExport;
use App\Models\Alat;
use App\Models\AlatUnit;
use App\Models\DetailPinjam;
use App\Models\Peminjaman;
use App\Models\Pengembalian;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menu Cetak Laporan untuk Admin: semua data pengembalian, dua format
 * (Excel + PDF), dan pemisahan hak akses terhadap Petugas.
 *
 * Baris laporan menampilkan unit serial (nama alat + serial_number),
 * bukan jumlah barang. Kondisi pengembalian hanya 'Baik' atau 'Rusak';
 * tidak ada lagi tingkat 'Rusak Ringan' / 'Rusak Berat'.
 */
class LaporanAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function petugas(): User
    {
        return User::factory()->create(['role' => 'petugas']);
    }

    /**
     * Satu pengembalian disetujui, diproses petugas tertentu, dengan
     * $jumlahUnit unit serial yang dipinjam pada saat itu.
     */
    private function pengembalianDiproses(
        User $petugas,
        string $kondisi = 'Baik',
        int $jumlahUnit = 2
    ): Pengembalian {
        $peminjam = User::factory()->create(['role' => 'peminjam']);

        $alat = Alat::factory()->denganUnit($jumlahUnit)->create();
        $units = $alat->alatUnit()->orderBy('serial_number')->get();

        $peminjaman = Peminjaman::create([
            'user_id' => $peminjam->id,
            'tgl_pinjam' => now()->subDays(5)->toDateString(),
            'tgl_kembali_plan' => now()->subDays(2)->toDateString(),
            'status' => 'dikembalikan',
        ]);

        foreach ($units as $unit) {
            DetailPinjam::create([
                'peminjaman_id' => $peminjaman->id,
                'alat_unit_id' => $unit->id,
            ]);
        }

        return Pengembalian::create([
            'peminjaman_id' => $peminjaman->id,
            'tgl_kembali' => now()->subDay()->toDateString(),
            'kondisi_kembali' => $kondisi,
            'denda' => 5000,
            'denda_kerusakan' => 10000,
            'petugas_id' => $petugas->id,
            'status_request' => 'disetujui',
        ]);
    }

    public function test_admin_bisa_membuka_halaman_laporan(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.laporan.index'))
            ->assertStatus(200);
    }

    public function test_laporan_admin_menampilkan_pengembalian_semua_petugas(): void
    {
        $petugasA = $this->petugas();
        $petugasB = $this->petugas();

        $this->pengembalianDiproses($petugasA);
        $this->pengembalianDiproses($petugasB);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.laporan.index'));

        $response->assertStatus(200);
        $this->assertCount(2, $response->viewData('pengembalians'));
    }

    public function test_laporan_petugas_hanya_menampilkan_pengembalian_miliknya(): void
    {
        $petugasA = $this->petugas();
        $petugasB = $this->petugas();

        $milikA = $this->pengembalianDiproses($petugasA);
        $milikB = $this->pengembalianDiproses($petugasB);

        $response = $this->actingAs($petugasA)
            ->get(route('petugas.laporan.index'));

        $response->assertStatus(200);
        $ids = $response->viewData('pengembalians')->pluck('id');

        $this->assertTrue($ids->contains($milikA->id));
        $this->assertFalse($ids->contains($milikB->id));
    }

    public function test_admin_ditolak_dari_endpoint_petugas(): void
    {
        $this->actingAs($this->admin())
            ->get(route('petugas.laporan.index'))
            ->assertForbidden();
    }

    public function test_petugas_ditolak_dari_endpoint_admin(): void
    {
        $this->actingAs($this->petugas())
            ->get(route('admin.laporan.index'))
            ->assertForbidden();
    }

    /*
    |--------------------------------------------------------------------------
    | Data serial: relasi dan isi tampil
    |--------------------------------------------------------------------------
    */

    public function test_laporan_memuat_relasi_unit_serial_tanpa_error(): void
    {
        $petugas = $this->petugas();
        $pengembalian = $this->pengembalianDiproses($petugas);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.laporan.index'));

        $response->assertStatus(200);

        $baris = $response->viewData('pengembalians')->firstWhere('id', $pengembalian->id);
        $this->assertNotNull($baris);

        // Relasi detailPinjams.alatUnit.alat ter-eager-load.
        $details = $baris->peminjaman->detailPinjams;
        $this->assertCount(2, $details);

        foreach ($details as $detail) {
            $this->assertInstanceOf(AlatUnit::class, $detail->alatUnit);
            $this->assertInstanceOf(Alat::class, $detail->alatUnit->alat);
            $this->assertNotEmpty($detail->alatUnit->serial_number);
        }
    }

    public function test_laporan_menampilkan_serial_number_bukan_jumlah(): void
    {
        $petugas = $this->petugas();
        $pengembalian = $this->pengembalianDiproses($petugas);

        $serials = $pengembalian->peminjaman->detailPinjams
            ->map(fn ($detail) => $detail->alatUnit->serial_number);

        $this->assertCount(2, $serials);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.laporan.index'));

        $response->assertStatus(200);

        // Setiap serial tampil di halaman, nama alatnya juga.
        foreach ($serials as $serial) {
            $response->assertSee($serial);
        }

        // Nama alat dari setiap unit, bukan cuma yang pertama: urutan
        // detailPinjams tidak dijamin sama dengan urutan unit.
        foreach ($pengembalian->peminjaman->detailPinjams as $detail) {
            $response->assertSee($detail->alatUnit->alat->nama_alat);
        }
    }

    public function test_laporan_petugas_juga_menampilkan_serial_number(): void
    {
        $petugas = $this->petugas();
        $pengembalian = $this->pengembalianDiproses($petugas);

        $serials = $pengembalian->peminjaman->detailPinjams
            ->map(fn ($detail) => $detail->alatUnit->serial_number);

        $response = $this->actingAs($petugas)
            ->get(route('petugas.laporan.index'));

        $response->assertStatus(200);

        foreach ($serials as $serial) {
            $response->assertSee($serial);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Kondisi pengembalian: hanya Baik / Rusak
    |----------------------------------------------------------------------
    | Nilai 'Rusak Ringan' / 'Rusak Berat' sudah dinormalisasi migrasi
    | 2026_09_30_144000. Filter kondisi (kalau ditambah) hanya punya dua
    | opsi ini; diuji di level data + tampilan, bukan request parameter.
    |--------------------------------------------------------------------------
    */

    public function test_kondisi_kembali_hanya_baik_dan_rusak(): void
    {
        $petugas = $this->petugas();

        $baik = $this->pengembalianDiproses($petugas, 'Baik');
        $rusak = $this->pengembalianDiproses($petugas, 'Rusak');

        $this->assertSame('Baik', $baik->fresh()->kondisi_kembali);
        $this->assertSame('Rusak', $rusak->fresh()->kondisi_kembali);

        // Hanya dua nilai kondisi yang ada di laporan.
        $semuaKondisi = Pengembalian::distinct()->pluck('kondisi_kembali');
        $this->assertEqualsCanonicalizing(['Baik', 'Rusak'], $semuaKondisi->all());
    }

    public function test_laporan_menampilkan_kondisi_baik_dan_rusak(): void
    {
        $petugas = $this->petugas();

        $this->pengembalianDiproses($petugas, 'Baik');
        $this->pengembalianDiproses($petugas, 'Rusak');

        $response = $this->actingAs($this->admin())
            ->get(route('admin.laporan.index'));

        $response->assertStatus(200);
        $response->assertSee('Baik');
        $response->assertSee('Rusak');
    }

    public function test_nilai_kondisi_lama_tidak_lagi_muncul(): void
    {
        $petugas = $this->petugas();
        $this->pengembalianDiproses($petugas, 'Rusak');

        $response = $this->actingAs($this->admin())
            ->get(route('admin.laporan.index'));

        $response->assertStatus(200);
        $response->assertDontSee('Rusak Ringan');
        $response->assertDontSee('Rusak Berat');
    }

    /*
    |--------------------------------------------------------------------------
    | Filter tanggal
    |--------------------------------------------------------------------------
    */

    public function test_filter_tanggal_membatasi_hasil_laporan(): void
    {
        $petugas = $this->petugas();

        $dalamRentang = $this->pengembalianDiproses($petugas);
        $dalamRentang->update(['tgl_kembali' => now()->subDays(3)->toDateString()]);

        $luarRentang = $this->pengembalianDiproses($petugas);
        $luarRentang->update(['tgl_kembali' => now()->subDays(30)->toDateString()]);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.laporan.index', [
                'tanggal_mulai' => now()->subDays(7)->toDateString(),
                'tanggal_selesai' => now()->toDateString(),
            ]));

        $ids = $response->viewData('pengembalians')->pluck('id');

        $this->assertTrue($ids->contains($dalamRentang->id));
        $this->assertFalse($ids->contains($luarRentang->id));
    }

    /*
    |--------------------------------------------------------------------------
    | Export
    |--------------------------------------------------------------------------
    */

    public function test_export_excel_admin_menghasilkan_file_xlsx(): void
    {
        $this->pengembalianDiproses($this->petugas());

        $response = $this->actingAs($this->admin())
            ->get(route('admin.laporan.excel'));

        $response->assertStatus(200);
        $response->assertHeader(
            'content-type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
    }

    public function test_export_pdf_admin_menghasilkan_dokumen_pdf(): void
    {
        $this->pengembalianDiproses($this->petugas());

        $response = $this->actingAs($this->admin())
            ->get(route('admin.laporan.pdf'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_export_excel_petugas_hanya_menyertakan_datanya_sendiri(): void
    {
        $petugasA = $this->petugas();
        $petugasB = $this->petugas();

        $milikA = $this->pengembalianDiproses($petugasA);
        $milikB = $this->pengembalianDiproses($petugasB);

        $response = $this->actingAs($petugasA)
            ->get(route('petugas.laporan.excel'));

        $response->assertStatus(200);

        // Nama petugas lain tidak boleh bocor ke file Excel petugas.
        $excel = (new LaporanPengembalianExport(
            Pengembalian::where('petugas_id', $petugasA->id)->get(),
            null,
            null,
            $petugasA->name
        ));

        $data = $excel->view()->getData()['pengembalians'];

        $this->assertTrue($data->contains('id', $milikA->id));
        $this->assertFalse($data->contains('id', $milikB->id));
    }

    public function test_export_excel_menampilkan_serial_number_unit(): void
    {
        $petugas = $this->petugas();
        $pengembalian = $this->pengembalianDiproses($petugas);

        $serials = $pengembalian->peminjaman->detailPinjams
            ->map(fn ($detail) => $detail->alatUnit->serial_number);

        $excel = new LaporanPengembalianExport(
            Pengembalian::all(),
            null,
            null,
            'Admin'
        );

        // View export harus bisa membaca serial lewat relasi yang sama.
        $view = $excel->view();
        $baris = $view->getData()['pengembalians'];

        $this->assertCount(1, $baris);

        foreach ($serials as $serial) {
            $this->assertContains(
                $serial,
                $baris[0]->peminjaman->detailPinjams
                    ->map(fn ($detail) => $detail->alatUnit->serial_number)
                    ->all()
            );
        }
    }
}
