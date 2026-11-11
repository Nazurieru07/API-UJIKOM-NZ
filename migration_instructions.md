# 📋 Instruksi Migrasi Database

## File Migrasi yang Perlu Dijalankan

### 1. **File Migrasi Baru yang Dibuat**:
- `backend/database/migrations/2026_10_29_000001_create_detail_pengembalian_table.php`
- `backend/database/migrations/2026_10_29_000002_modify_pengembalian_for_partial_return.php`

### 2. **File Migrasi yang Dihapus**:
- `backend/database/migrations/2026_10_08_110000_add_diproses_oleh_to_pengembalian.php` (sudah dihapus)

## 🛠️ **Cara Menjalankan Migrasi**

### **Jika PHP terinstall**:
```bash
cd backend
php artisan migrate
```

### **Jika ingin rollback**:
```bash
cd backend
php artisan migrate:rollback
```

## 🔄 **Perubahan Struktur Database**

### **Tabel Baru: `detail_pengembalian`**
```sql
CREATE TABLE detail_pengembalian (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pengembalian_id BIGINT UNSIGNED NOT NULL,
    detail_pinjam_id BIGINT UNSIGNED NOT NULL,
    kondisi_kembali ENUM('Baik', 'Rusak') DEFAULT 'Baik',
    denda_kerusakan INT DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY detail_pengembalian_detail_pinjam_id_unique (detail_pinjam_id),
    FOREIGN KEY (pengembalian_id) REFERENCES pengembalian(id) ON DELETE CASCADE,
    FOREIGN KEY (detail_pinjam_id) REFERENCES detail_pinjam(id) ON DELETE CASCADE
);
```

### **Modifikasi Tabel `pengembalian`**:
```sql
ALTER TABLE pengembalian 
MODIFY kondisi_kembali VARCHAR(255) NULL,
MODIFY denda_kerusakan INT NULL,
ADD COLUMN is_parsial BOOLEAN DEFAULT FALSE AFTER denda_kerusakan,
ADD COLUMN jumlah_unit_dikembalikan INT DEFAULT 0 AFTER is_parsial;
```

## ⚠️ **Penting! Data Lama**

### **Kolom `diproses_oleh`**:
- Kolom ini sudah dihapus dari migrasi
- Jika tabel sudah memiliki kolom ini, perlu di-drop manual:
```sql
ALTER TABLE pengembalian DROP COLUMN diproses_oleh;
```

### **Data Kompatibilitas**:
- Data lama tetap bisa diakses karena perubahan nullable
- Pengembalian lama akan otomatis memiliki `is_parsial = false`

## 🧪 **Testing Setelah Migrasi**

### **1. Test Seeder**:
```bash
php artisan db:seed --class=UserSeeder
```

### **2. Test Fitur Baru**:
1. Login sebagai petugas
2. Coba fitur "Kembalikan Sebagian"
3. Cek laporan "Barang Dipinjam"

### **3. Verifikasi Data**:
```sql
-- Cek struktur tabel
DESCRIBE pengembalian;
DESCRIBE detail_pengembalian;

-- Cek data sample
SELECT * FROM pengembalian WHERE is_parsial = true LIMIT 5;
```

## 🔧 **Troubleshooting**

### **Jika ada error foreign key**:
```sql
-- Nonaktifkan foreign key check sementara
SET FOREIGN_KEY_CHECKS = 0;

-- Jalankan migrasi/alter table

-- Aktifkan kembali
SET FOREIGN_KEY_CHECKS = 1;
```

### **Jika kolom `diproses_oleh` masih ada**:
```sql
-- Cek dulu apakah kolom ada
SHOW COLUMNS FROM pengembalian LIKE 'diproses_oleh';

-- Jika ada, hapus
ALTER TABLE pengembalian DROP COLUMN diproses_oleh;
```

## 📞 **Dukungan**

Jika ada masalah dengan migrasi:
1. Backup database terlebih dahulu
2. Cek log error di `storage/logs/laravel.log`
3. Verifikasi versi Laravel dan PHP (minimal PHP 8.2)