# Resume Progress — 01.API-UJIKOM (Sistem Peminjaman Alat)

Tanggal: 22 September 2026
Sesi: Audit ulang + penyempurnaan fitur + perbaikan bug

---

## Yang Ditambahkan

### 2. Edit Kondisi Alat per pcs (Role Admin)

Menu alat sekarang punya tombol **"Ubah Kondisi"** (dropdown, warna amber).
Admin bisa memindahkan jumlah pcs tertentu dari satu kondisi ke kondisi lain:

- Pilih kondisi asal (Baik / Rusak Ringan / Rusak Parah)
- Pilih kondisi tujuan
- Masukkan jumlah pcs
- Stok kondisi langsung diperbarui, `status_kondisi` utama ikut
  disesuaikan berdasarkan kondisi mayoritas
- Tercatat di log aktivitas

Contoh: 1 pcs dari Baik -> Rusak Ringan maka badge di index berubah.

File yang diubah:

- `backend/app/Http/Controllers/AdminController.php` — method baru `ubahKondisiAlat`
- `backend/routes/web.php` — route `admin.alat.ubahKondisi`
- `backend/resources/views/admin/alat/index.blade.php` — tombol & form

Validasi: kondisi asal & tujuan harus berbeda (`different`), jumlah harus
cukup, semua enum terbatas pada 3 nilai resmi.

---

### 1. Filter pada menu Pemantauan Pengembalian (Role Petugas)

Sebelumnya menu ini hanya memiliki pencarian nama peminjam. Sekarang ditambahkan:

- **Filter Kategori Alat** (dropdown, dari tabel `kategori`)
- **Filter Tanggal Dari** (input date)
- **Filter Tanggal Sampai** (input date)
- Tombol **Cari** dan **Reset** (reset muncul hanya jika ada filter aktif)

File yang diubah:

- `backend/app/Http/Controllers/PetugasController.php` — method `indexPengembalian`
- `backend/resources/views/petugas/pengembalian/index.blade.php`

Filter kategori bekerja lewat `whereHas('detailPinjams.alat')`, jadi peminjaman
yang salah satu alatnya masuk kategori terpilih akan tampil. Filter tanggal
memfilter berdasarkan `tgl_pinjam`.

Kolom Alat pada tabel juga sekarang menampilkan badge nama kategori tiap alat.

---

## Yang Diperbaiki (Hasil Audit)

### Bug Laporan: "Petugas Dihapus" padahal yang proses Admin

- Lokasi: `resources/views/petugas/laporan/index.blade.php` &
  `resources/views/petugas/laporan/pdf.blade.php`
- Masalah: pengembalian yang dibuat lewat alur Admin punya `petugas_id`
  NULL (memang sengaja, kolom di-nullable). View menulis
  `$pengembalian->petugas->name ?? 'Petugas Dihapus'` jadi yang tampil
  teks menyesatkan.
- Perbaikan: kalau `petugas_id` NULL, tampilkan **"Admin"** karena yang
  mempros memang role admin.

### Kritis (Korupsi Data)

1. **Double Approve Peminjaman**
   - Lokasi: `PetugasController::setujuiPeminjaman`
   - Masalah: tidak ada pengecekan status `diajukan`. Klik 2x atau request
     bersamaan menyebabkan stok & `stok_baik` dikurangi 2x.
   - Perbaikan: tambah guard `if ($peminjaman->status !== 'diajukan')` +
     `lockForUpdate()`.

2. **Validasi Password saat Update User**
   - Lokasi: `AdminController::updateUser`
   - Masalah: password tidak divalidasi sama sekali (bisa kosong / 1 karakter).
   - Perbaikan: tambah rule `nullable|string|min:8`.

3. **Hapus User dengan Peminjaman Aktif**
   - Lokasi: `AdminController::destroyUser`
   - Masalah: FK `cascadeOnDelete` menghapus peminjaman & detail tanpa
     mengembalikan stok alat → stok hilang diam-diam.
   - Perbaikan: tolak hapus jika user masih punya peminjaman
     `diajukan`/`dipinjam`/`telat`.

4. **Duplikasi Data Pengembalian**
   - Masalah: `pengembalian.peminjaman_id` tidak ada constraint unique.
     Race condition bisa membuat 2 record pengembalian untuk 1 peminjaman.
   - Perbaikan: migration baru menambah unique constraint.
   - File: `backend/database/migrations/2026_09_22_090000_add_unique_peminjaman_id_to_pengembalian_table.php`

5. **Validasi `status_kondisi` Bebas**
   - Lokasi: `AdminController::storeAlat` & `updateAlat`
   - Masalah: hanya `required|string|max:100`. Nilai aneh membuat semua
     stok kondisi jadi 0 karena switch hanya mengenali 3 nilai.
   - Perbaikan: ganti jadi `required|in:Baik,Rusak,Rusak Parah` + whitelist
     field yang masuk ke `Alat::create` (sebelumnya `$request->all()`).

6. **Stok Kondisi Negatif saat Hapus Pengembalian**
   - Lokasi: `AdminController::destroyPengembalian`
   - Masalah: hanya cek `stok` total. Jika alat sudah diperbaiki via
     `perbaikiAlat`, `stok_rusak`/`stok_rusak_parah` bisa minus.
   - Perbaikan: cek per-kolom kondisi sebelum decrement.

### Sedang

7. **Notifikasi Tidak Queue**
   - Masalah: 6 class notifikasi semuanya synchronous, padahal
     `QUEUE_CONNECTION=database`. Setiap approval memblokir HTTP request.
   - Perbaikan: semua class sekarang `implements ShouldQueue`.

8. **Login Tanpa Throttle**
   - Lokasi: `AuthController::login`
   - Masalah: brute force terbuka tanpa batas.
   - Perbaikan: `RateLimiter` 5 percobaan per menit per (email + IP),
     dibersihkan saat login berhasil.

9. **Debug Route Terbuka**
   - Lokasi: `routes/web.php`
   - Masalah: route `/debug-db` (bongkar skema tabel + nama database) dan
     `/who-am-i` (bongkar versi PHP + hostname) bisa diakses siapa saja.
   - Perbaikan: kedua route dihapus.

---

## Verifikasi

Dijalankan di container Docker yang sedang running (`laravel-api`):

- `php -l` bersih untuk semua file PHP yang diubah.
- Migration `2026_09_22_090000` berjalan sukses.
- Login sukses untuk 3 role (admin / petugas / peminjam).
- Halaman pemantauan pengembalian petugas tampil dengan filter baru.
- Filter kategori: kategori ID 6 menampilkan 1 data, kategori lain & ID
  tidak ditemukan menampilkan 0 (sesuai data).
- Filter tanggal dari / sampai / kombinasi + kategori: semua 200 OK.
- Route `/debug-db` & `/who-am-i` sekarang 404.
- Rate limiter memblokir percobaan login ke-5 (diverifikasi langsung
  via `RateLimiter` API).
- Guard double-approve: simulasi approve ke-2 ditolak karena status
  sudah `dipinjam` → stok aman, data test di-rollback.
- Semua 6 class notifikasi terbukti `implements ShouldQueue`.
- Stok alat & data peminjaman test kembali bersih setelah verifikasi.

### Verifikasi Fitur Baru (Sesi 2)

- Tombol "Ubah Kondisi" tampil di semua baris alat di index admin.
- Submit 1 pcs Baik -> Rusak Ringan: `stok_baik` 5->4, `stok_rusak`
  3->4, flash message muncul. Sesuai harapan.
- Submit 999 pcs (melebihi stok): ditolak, stok tidak berubah, error
  jelas.
- Kondisi asal == tujuan: ditolak validasi `different`.
- Kondisi asal kosong: ditolak validasi `required`.
- `status_kondisi` utama ikut berubah sesuai kondisi mayoritas
  (terbukti: setelah pindah 3 pcs ke Rusak Parah, status jadi `Rusak`).
- Log aktivitas tercatat: "Mengubah 3 pcs alat 'Teleporter' dari
  kondisi Baik menjadi Rusak Parah."
- Stok dikembalikan ke kondisi semula setelah test.
- Laporan petugas: 3 baris yang `petugas_id` NULL sekarang menampilkan
  "Admin" (sebelumnya "Petugas Dihapus"). PDF juga sudah diperbaiki.

---

## Yang Masih Belum Dikerjakan

- `indexPeminjaman` masih melakukan mass update `dipinjam` → `telat`
  lewat query builder (skip observer, log tidak konsisten).
- Folder `tests/` belum ada (klaim "28/28 passed" di commit sebelumnya
  tidak terbukti ada test-nya di repo maupun git history).
- `APP_DEBUG=true` di `.env` — pastikan di-set `false` untuk production.
- Queue worker belum dijalankan otomatis. Notifikasi masuk ke table
  `jobs` tapi butuh `php artisan queue:work` (atau supervisor) untuk
  memprosesnya.
