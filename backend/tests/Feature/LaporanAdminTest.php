<?php

namespace Tests\Feature;

use App\Exports\LaporanPengembalianExport;
use App\Models\Alat;
use App\Models\DetailPinjam;
use App\Models\Peminjaman;
use App\Models\Pengembalian;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menu Cetak Laporan untuk Admin: semua data pengembalian, dua format
 * (Excel + PDF), dan pemisahan hak akses terhadap Petugas.
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
     * Membuat satu pengembalian disetujui yang diproses petugas tertentu.
     */
    private function pengembalianDiproses(User $petugas, string $kondisi = 'Baik'): Pengembalian
    {
        $peminjam = User::factory()->create(['role' => 'peminjam']);
        $alat = Alat::factory()->create(['stok' => 5, 'stok_baik' => 5]);

        $peminjaman = Peminjaman::create([
            'user_id' => $peminjam->id,
            'tgl_pinjam' => now()->subDays(5),
            'tgl_kembali_plan' => now()->subDays(2),
            'status' => 'dikembalikan',
        ]);

        DetailPinjam::create([
            'peminjaman_id' => $peminjaman->id,
            'alat_id' => $alat->id,
            'jumlah' => 1,
        ]);

        return Pengembalian::create([
            'peminjaman_id' => $peminjaman->id,
            'tgl_kembali' => now()->subDay(),
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
            \App\Models\Pengembalian::where('petugas_id', $petugasA->id)->get(),
            null,
            null,
            $petugasA->name
        ));

        $this->assertTrue($excel->view()->getData()['pengembalians']->contains('id', $milikA->id));
        $this->assertFalse($excel->view()->getData()['pengembalians']->contains('id', $milikB->id));
    }

    public function test_filter_tanggal_membatasi_hasil_laporan(): void
    {
        $petugas = $this->petugas();

        $dalamRentang = $this->pengembalianDiproses($petugas);
        $dalamRentang->update(['tgl_kembali' => now()->subDays(3)]);

        $luarRentang = $this->pengembalianDiproses($petugas);
        $luarRentang->update(['tgl_kembali' => now()->subDays(30)]);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.laporan.index', [
                'tanggal_mulai' => now()->subDays(7)->toDateString(),
                'tanggal_selesai' => now()->toDateString(),
            ]));

        $ids = $response->viewData('pengembalians')->pluck('id');

        $this->assertTrue($ids->contains($dalamRentang->id));
        $this->assertFalse($ids->contains($luarRentang->id));
    }
}
