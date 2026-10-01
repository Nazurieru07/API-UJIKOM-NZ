<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\Kategori;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Katalog peminjam: kartu alat harus tetap pendek walau alat punya
 * puluhan unit serial. Sebelumnya setiap serial ditampilkan vertikal
 * sehingga katalog memanjang tak terbatas ke bawah.
 */
class KatalogSerialRingkasTest extends TestCase
{
    use RefreshDatabase;

    private function peminjam(): User
    {
        return User::factory()->create(['role' => 'peminjam']);
    }

    public function test_kartu_hanya_tampilkan_empat_serial_pertama(): void
    {
        $alat = Alat::factory()->denganUnit(10)->create();

        $response = $this->actingAs($this->peminjam())
            ->get(route('peminjam.katalog'))
            ->assertStatus(200);

        $body = $response->getContent();

        // Grid 2 kolom menggantikan list vertikal.
        $this->assertStringContainsString('grid grid-cols-2 gap-2', $body);

        // Tombol sisanya muncul: 10 unit - 4 tampil = 6. Marker id tombol
        // unik per alat, jadi tidak konflik dengan script handler.
        $this->assertStringContainsString('id="tombol-serial-' . $alat->id . '"', $body);
    }

    public function test_kartu_tidak_punya_tombol_lainnya_kalau_kurang_dari_empat(): void
    {
        $alat = Alat::factory()->denganUnit(3)->create();

        $response = $this->actingAs($this->peminjam())
            ->get(route('peminjam.katalog'))
            ->assertStatus(200);

        // Tombol load-more tidak dibuat (hanya 3 unit). Cek marker id
        // tombol -- class .tampil-semua-serial tidak bisa dipakai karena
        // script handler-nya ter-render dan ikut memuat class itu.
        $this->assertStringNotContainsString(
            'id="tombol-serial-' . $alat->id . '"',
            $response->getContent()
        );
    }

    public function test_endpoint_unit_melewati_serial_yang_sudah_tampil_di_kartu(): void
    {
        $alat = Alat::factory()->denganUnit(8)->create();

        $semua = $alat->alatUnit()
            ->tersedia()
            ->orderBy('serial_number')
            ->pluck('serial_number')
            ->all();

        $response = $this->actingAs($this->peminjam())
            ->getJson(route('peminjam.unit.alat', $alat->id))
            ->assertStatus(200)
            ->assertJsonPath('nama_alat', $alat->nama_alat)
            // Default skip = 4, sama dengan take(4) di kartu katalog.
            ->assertJsonPath('skip', 4);

        $units = $response->json('units');

        // 8 unit - 4 yang sudah tampil di kartu = 4 sisanya.
        $this->assertCount(4, $units);

        // Regression guard: endpoint sebelumnya mengirim SEMUA unit, jadi
        // tombol "+N unit lainnya" menampilkan ulang AH-001 yang sudah ada
        // di kartu.
        $this->assertSame($semua[4], $units[0]['serial_number']);
        $this->assertSame($semua[7], $units[3]['serial_number']);
    }

    public function test_endpoint_unit_tidak_kirim_yang_dipinjam_atau_rusak(): void
    {
        $alat = Alat::factory()->denganUnit(5)->create();
        $units = $alat->alatUnit()->orderBy('serial_number')->get();
        $units[3]->update(['kondisi' => 'dipinjam']);
        $units[4]->update(['kondisi' => 'rusak']);

        $response = $this->actingAs($this->peminjam())
            ->getJson(route('peminjam.unit.alat', ['id' => $alat->id, 'skip' => 0]))
            ->assertStatus(200);

        // Hanya unit tersedia yang boleh dipilih untuk pinjam baru.
        // Default skip=4 ada di endpoint lain; di sini ambil seluruh
        // daftar supaya kondisi tiap unit bisa diperiksa.
        $units = $response->json('units');

        $this->assertCount(3, $units);

        $kondisi = $alat->alatUnit()->pluck('kondisi', 'id')->all();

        foreach ($units as $unit) {
            $this->assertSame('tersedia', $kondisi[$unit['id']]);
        }
    }

    public function test_endpoint_alat_terarsip_tidak_bisa_diakses(): void
    {
        $alat = Alat::factory()->denganUnit(2)->create(['is_arsip' => true]);

        $this->actingAs($this->peminjam())
            ->getJson(route('peminjam.unit.alat', $alat->id))
            ->assertStatus(404);
    }

    public function test_halaman_katalog_memuat_script_load_more(): void
    {
        Alat::factory()->denganUnit(6)->create();

        $this->actingAs($this->peminjam())
            ->get(route('peminjam.katalog'))
            ->assertStatus(200)
            ->assertSee('tampil-semua-serial');
    }
}
