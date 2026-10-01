# SPEC MIGRASI SERIAL NUMBER — baca dulu sebelum edit

## Konteks
Aplikasi peminjaman alat Laravel. Transisi dari **stok agregat** ke **serial number per unit**.
Semua perubahan harusцию menghasilkan kode yang jalan + test hijau.

## Skema DB (SUDAH migrated, JANGAN ubah)
Tabel `alat` (kolom stok SUDAH/JANJI di-drop di migrasi terakhir, TAPI masih ada sekarang):
- id, kategori_id, nama_alat, **kode_alat** (prefix serial, misal "RM"), is_arsip, deskripsi, gambar

Tabel `alat_unit` (BARU):
- id, alat_id (FK), **serial_number** (UNIQUE global), **kondisi** ENUM('tersedia','dipinjam','rusak'), timestamps

Tabel `detail_pinjam`:
- peminjaman_id, **alat_unit_id** (nullable, sedang diisi)
- kolom LAMA `alat_id` dan `jumlah` MASIH ADA tapi akan di-drop. JANGAN pakai lagi.

## Model (SUDAH diubah, JANGAN diubah lagi)
`App\Models\Alat`:
- fillable: kategori_id, nama_alat, kode_alat, is_arsip, deskripsi, gambar
- casts: is_aktif... (tidak), hanya `is_arsip => boolean`
- `static function saranKodeAlat(string $nama): string` — prefix 2 huruf dari nama
- relasi: `kategori()` BelongsTo, `alatUnit()` HasMany, `detailPinjam()` HasMany
- `unitTersedia()`, `unitDipinjam()`, `unitRusak()` — HasMany DENGAN filter kondisi
- scope: `scopeTidakTerarsip()`, `scopeTerarsip()`
- **TIDAK LAGI ADA**: kolom stok*, scopeTersedia(), kondisiMayoritas()

`App\Models\AlatUnit`:
- fillable: alat_id, serial_number, kondisi
- relasi: `alat()` BelongsTo
- scope: `scopeTersedia()`, `scopeRusak()`, `scopeDipinjam()`
- method: `isTersedia()`, `isRusak()`, `isDipinjam()`
- static: `AlatUnit::serialBerikutnya(int $alatId, string $kodeAlat): string`

`App\Models\DetailPinjam`:
- fillable: **hanya** `peminjaman_id`, `alat_unit_id`
- relasi: `peminjaman()`, `alatUnit()`
- **TIDAK LAGI ADA**: `jumlah`, `alat()`
- Cara dapat alat: `$detail->alatUnit->alat`

## Aturan emas
1. **Jumlah unit tersedia** = `$alat->alatUnit()->tersedia()->count()` atau `withCount(['alatUnit' => fn($q) => $q->where('kondisi','tersedia')])`
2. **Cek ketersediaan WAJIB atomic** saat approve/pinjam: `AlatUnit::where('id',$id)->where('kondisi','tersedia')->update(['kondisi'=>'dipinjam'])` lalu cek `rowCount() === 1`. Ini mencegah dua petugas memorials serial yang sama.
3. **`$detail->jumlah` atau `$detail->alat` = FATAL ERROR** — kolom tidak ada lagi.
4. Lazy loading relation = N+1. Pakai `with()` / `withCount()`.
5. Jangan tinggalkan `dd()`, `dump()`, `logger()` debug.
6. Jangan ubah signature route yang sudah dipakai view lain kecuali instructed.

## Cara edit file di environment ini
- Baca file: `wsl.exe -u heinz-nazuryy -- bash -lc 'cat ~/01.API-UJIKOM/backend/path/file.php'`
- Tulis file: pakai tool `write_file` ke `C:/Users/Hype/AppData/Local/hermes/cache/scratch/NAMA.php`, lalu salin:
  `wsl.exe -u heinz-nazuryy -- bash -lc 'cp /mnt/c/Users/Hype/AppData/Local/hermes/cache/scratch/NAMA.php ~/01.API-UJIKOM/backend/path/file.php'`
- **JANGAN** pakai heredoc (`cat <<EOF`) di bash — karakter `$` dan quote selalu hilang.
- Verifikasi syntax: `wsl.exe -u heinz-nazuryy -- bash -lc 'cd ~/01.API-UJIKOM/backend && docker exec laravel-api php -l path/file.php'`

## Test
`wsl.exe -u heinz-nazuryy -- bash -lc 'cd ~/01.API-UJIKOM/backend && docker exec -e HOME=/tmp -e XDG_CONFIG_HOME=/tmp laravel-api php artisan test --filter=NamaTest'`

Test lama yang memakai stok/jumlah AKAN gagal dan itu_program yang sedang dikerjakan subprocess lain. Fokus: kode kamu sendiri harus syntax-valid dan logis benar.
