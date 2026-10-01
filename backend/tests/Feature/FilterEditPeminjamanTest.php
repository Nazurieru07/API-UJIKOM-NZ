<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\DetailPermintaanEdit;
use App\Models\Peminjaman;
use App\Models\PermintaanEditPeminjaman;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilterEditPeminjamanTest extends TestCase
{
    use RefreshDatabase;

    private $petugas;
    private $peminjamA;
    private $peminjamB;
    private $alat;
    private $units;

    protected function setUp(): void
    {
        parent::setUp();

        $this->petugas = User::create([
            'name' => 'Petugas Test', 'email' => 'petugas.test@mail.com',
            'password' => bcrypt('password'), 'role' => 'petugas',
        ]);
        $this->peminjamA = User::create([
            'name' => 'Andi Wijaya', 'email' => 'andi@mail.com',
            'password' => bcrypt('password'), 'role' => 'peminjam',
        ]);
        $this->peminjamB = User::create([
            'name' => 'Budi Santoso', 'email' => 'budi@mail.com',
            'password' => bcrypt('password'), 'role' => 'peminjam',
        ]);

        $this->alat = Alat::factory()->denganUnit(2)->create();
        $this->units = $this->alat->alatUnit()->orderBy('serial_number')->get();

        foreach ([$this->peminjamA, $this->peminjamB] as $i => $p) {
            $peminjaman = Peminjaman::create([
                'user_id' => $p->id,
                'petugas_id' => $this->petugas->id,
                'tgl_pinjam' => now(),
                'tgl_kembali_plan' => now()->addDays(3),
                'status' => 'dipinjam', 'keterangan' => null,
            ]);
            $peminjaman->detailPinjams()->create([
                'alat_unit_id' => $this->units[$i]->id,
            ]);
        }
    }

    /** Buat satu permintaan edit untuk peminjam tertentu. */
    private function buatPermintaan($userId, string $alasan, $daysAgo = 0): PermintaanEditPeminjaman
    {
        $peminjaman = Peminjaman::where('user_id', $userId)->first();

        $req = PermintaanEditPeminjaman::create([
            'peminjaman_id' => $peminjaman->id,
            'user_id' => $userId,
            'alasan' => $alasan,
            'status' => 'menunggu',
        ]);

        // created_at is not mass-assignable; set it directly so the date
        // filters have something other than "today" to discriminate on.
        $req->created_at = now()->subDays($daysAgo);
        $req->save();

        return $req->refresh();
    }

    public function test_petugas_bisa_melihat_halaman_daftar_edit()
    {
        $this->actingAs($this->petugas);
        $this->buatPermintaan($this->peminjamA->id, 'Tanggal berubah');

        $response = $this->get(route('petugas.edit-peminjaman.index'));
        $response->assertStatus(200);
        $response->assertSee('Permintaan Edit Menunggu Persetujuan');
        $response->assertSee('Andi Wijaya');
    }

    public function test_filter_pencarian_nama_memfilter_hasil()
    {
        $this->actingAs($this->petugas);
        $this->buatPermintaan($this->peminjamA->id, 'Alasan xAndi');
        $this->buatPermintaan($this->peminjamB->id, 'Alasan xBudi');

        $response = $this->get(route('petugas.edit-peminjaman.index', ['search' => 'Andi']));
        $response->assertStatus(200);
        $response->assertSee('Alasan xAndi');
        $response->assertDontSee('Alasan xBudi');
    }

    public function test_filter_pencarian_alasan_memfilter_hasil()
    {
        $this->actingAs($this->petugas);
        $this->buatPermintaan($this->peminjamA->id, 'Tanggal ujian mundur');
        $this->buatPermintaan($this->peminjamB->id, 'Alat rusak parah');

        $response = $this->get(route('petugas.edit-peminjaman.index', ['search' => 'ujian']));
        $response->assertStatus(200);
        $response->assertSee('Tanggal ujian mundur');
        $response->assertDontSee('Alat rusak parah');
    }

    public function test_dropdown_peminjam_memfilter_hasil()
    {
        $this->actingAs($this->petugas);
        $this->buatPermintaan($this->peminjamA->id, 'Alasan xAndi');
        $this->buatPermintaan($this->peminjamB->id, 'Alasan xBudi');

        $response = $this->get(route('petugas.edit-peminjaman.index', ['peminjam_id' => $this->peminjamB->id]));
        $response->assertStatus(200);
        $response->assertSee('Alasan xBudi');
        $response->assertDontSee('Alasan xAndi');
    }

    public function test_dropdown_hanya_berisi_peminjam_yang_mengajukan()
    {
        $this->actingAs($this->petugas);
        $this->buatPermintaan($this->peminjamA->id, 'Hanya Andi mengajukan');

        $response = $this->get(route('petugas.edit-peminjaman.index'));
        $response->assertStatus(200);
        $response->assertSee($this->peminjamA->email);
        $response->assertDontSee($this->peminjamB->email);
    }

    public function test_filter_tanggal_dari_memfilter_hasil()
    {
        $this->actingAs($this->petugas);
        $this->buatPermintaan($this->peminjamA->id, 'Alasan xLama', 10);
        $this->buatPermintaan($this->peminjamB->id, 'Alasan xBaru', 1);

        $response = $this->get(route('petugas.edit-peminjaman.index', [
            'tanggal_dari' => now()->subDays(5)->format('Y-m-d'),
        ]));
        $response->assertStatus(200);
        $response->assertSee('Alasan xBaru');
        $response->assertDontSee('Alasan xLama');
    }

    public function test_filter_tanggal_sampai_memfilter_hasil()
    {
        $this->actingAs($this->petugas);
        $this->buatPermintaan($this->peminjamA->id, 'Alasan xLama', 10);
        $this->buatPermintaan($this->peminjamB->id, 'Alasan xBaru', 1);

        $response = $this->get(route('petugas.edit-peminjaman.index', [
            'tanggal_sampai' => now()->subDays(5)->format('Y-m-d'),
        ]));
        $response->assertStatus(200);
        $response->assertSee('Alasan xLama');
        $response->assertDontSee('Alasan xBaru');
    }

    public function test_kombinasi_filter_peminjam_dan_tanggal()
    {
        $this->actingAs($this->petugas);
        $this->buatPermintaan($this->peminjamA->id, 'Alasan xAbaru', 1);
        $this->buatPermintaan($this->peminjamB->id, 'Alasan xBbaru', 1);

        $response = $this->get(route('petugas.edit-peminjaman.index', [
            'peminjam_id' => $this->peminjamA->id,
            'tanggal_dari' => now()->subDays(5)->format('Y-m-d'),
        ]));
        $response->assertStatus(200);
        $response->assertSee('Alasan xAbaru');
        $response->assertDontSee('Alasan xBbaru');
    }

    public function test_filter_tidak_menampilkan_permintaan_sudah_diproses()
    {
        $this->actingAs($this->petugas);
        $req = $this->buatPermintaan($this->peminjamA->id, 'Akan disetujui');
        $req->update(['status' => 'disetujui', 'processed_by' => $this->petugas->id, 'processed_at' => now()]);

        $response = $this->get(route('petugas.edit-peminjaman.index', ['search' => 'Akan']));
        $response->assertStatus(200);
        $response->assertDontSee('Akan disetujui');
    }

    public function test_permintaan_edit_terhubung_ke_unit_serial()
    {
        $this->actingAs($this->petugas);
        $unitTersisa = $this->alat->alatUnit()->orderBy('serial_number')->first();
        $req = $this->buatPermintaan($this->peminjamA->id, 'Tambah unit');

        DetailPermintaanEdit::create([
            'permintaan_edit_id' => $req->id,
            'alat_unit_id' => $unitTersisa->id,
            'aksi' => 'tambah',
        ]);

        $response = $this->get(route('petugas.edit-peminjaman.index'));
        $response->assertStatus(200);
        $response->assertSee($unitTersisa->serial_number);
    }

    public function test_admin_ditolak_dari_menu_edit_peminjaman_petugas()
    {
        $admin = User::create([
            'name' => 'Admin', 'email' => 'admin@mail.com',
            'password' => bcrypt('password'), 'role' => 'admin',
        ]);
        $this->actingAs($admin);

        $response = $this->get(route('petugas.edit-peminjaman.index'));
        $response->assertStatus(403);
    }
}
