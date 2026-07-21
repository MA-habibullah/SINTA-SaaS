# Log Rencana Implementasi Harian: 2026-07-21

---
## [Nama Fitur atau Rencana] Modul Keuangan, SPP Dinamis, & Integrasi PPDB (SaaS Multi-Tenant)
**Waktu**: 07:15 WIB
**Status**: Draft

# Rencana Implementasi: Modul Keuangan, SPP Dinamis, & Integrasi PPDB (SaaS Multi-Tenant)

Rencana implementasi ini memperluas fungsionalitas modul pembayaran agar sepenuhnya fleksibel mendukung pembayaran **non-statis** (Uang Gedung, KTS, LKS Buku, Sumbangan Sukarela) dan **integrasi pendaftaran PPDB (Penerimaan Peserta Didik Baru)** secara terpadu.

Sesuai aturan penamaan, seluruh tabel keuangan ini menggunakan awalan **`transaksi_spp_`**.

---

## 1. Dukungan Pembayaran Dinamis & Fleksibilitas

Sistem didesain untuk tidak mengunci jenis pembayaran. Segala kewajiban didefinisikan sebagai **Komponen Biaya** (`transaksi_spp_komponen`) dengan pengaturan sifat pembayaran:

| Tipe Pembayaran | Karakteristik | Metode Penanganan di Database |
| --- | --- | --- |
| **SPP Bulanan** | Periodik (Bulanan) | Kolom `bulan` (1-12) diisi. Tagihan di-generate per bulan berdasarkan tarif default kelas/jenjang. |
| **Uang Gedung / Pangkal** | Skala Besar (Sekali di Awal) | `tipe_periode = 'Tahunan'` atau `'Bebas'`. Mendukung pembayaran parsial (cicilan) berulang kali di Loket Kasir hingga lunas. |
| **Buku LKS & KTS** | Insidental (Per Kejadian) | `tipe_periode = 'Bebas'` atau `'Semester'`. Diterbitkan massal hanya saat ada pembagian buku atau agenda KTS. |
| **Sumbangan Sukarela** | Fleksibel (Nominal Bebas) | `tipe_periode = 'Bebas'`. Nominal tagihan awal di-set `0.00` atau sesuai kerelaan wali murid, dan kasir dapat menginput jumlah uang masuk secara langsung di Loket Pembayaran. |

---

## 2. Integrasi Pembayaran Formulir PPDB

### Mekanisme Alur Integrasi:
1.  **Perekaman Calon Siswa**:
    - Calon siswa yang mendaftar PPDB tercatat di tabel `pendaftaran_spmb` yang sudah memiliki relasi `siswa_id` (merujuk ke tabel `siswa`).
2.  **Penerbitan Tagihan Pendaftaran**:
    - Saat calon siswa mengirim formulir PPDB (status `draft` / `diajukan`), sistem secara otomatis (via event / hook di Controller PPDB) menerbitkan entri tagihan baru di tabel `transaksi_spp_tagihan` dengan kategori komponen **"Biaya Formulir Pendaftaran PPDB"** sesuai tarif pendaftaran sekolah tersebut.
3.  **Proses Validasi Kelunasan**:
    - Calon siswa melakukan pembayaran (tunai di loket pendaftaran sekolah atau transfer).
    - Loket Kasir memproses pembayaran tersebut. Setelah `status_lunas` berubah menjadi `'Lunas'`, sistem PPDB secara otomatis mendeteksi kelayakan dokumen berkas pendaftaran untuk dapat diverifikasi lebih lanjut oleh panitia sekolah.

---

## 3. Skema Database (`transaksi_spp_` Prefix)

Kami merancang skema relasional terpusat berikut pada migrasi `database/migrations/2026_07_21_01_create_spp_billing_system.php`:

### A. Tabel `transaksi_spp_pengaturan`
Menampung kustomisasi istilah per tenant sekolah.
```sql
CREATE TABLE IF NOT EXISTS `transaksi_spp_pengaturan` (
    `tenant_id` CHAR(36) PRIMARY KEY,
    `nama_modul` VARCHAR(100) NOT NULL DEFAULT 'Keuangan & SPP',
    `istilah_tagihan` VARCHAR(100) NOT NULL DEFAULT 'Tagihan',
    `istilah_tunggakan` VARCHAR(100) NOT NULL DEFAULT 'Tunggakan',
    `visibilitas_siswa` TINYINT(1) DEFAULT 1, -- 1: Tampil di dashboard siswa/pendaftar, 0: Sembunyikan
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_spp_settings_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### B. Tabel `transaksi_spp_komponen`
Menyimpan komponen biaya (e.g. 'SPP Bulanan', 'Uang Gedung', 'Biaya Formulir PPDB').
```sql
CREATE TABLE IF NOT EXISTS `transaksi_spp_komponen` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` CHAR(36) NOT NULL,
    `nama_komponen` VARCHAR(100) NOT NULL,
    `tipe_periode` ENUM('Bulanan', 'Semester', 'Tahunan', 'Bebas') NOT NULL DEFAULT 'Bulanan',
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_spp_komponen_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### C. Tabel `transaksi_spp_tarif`
Menyimpan tarif acuan biaya default per kelas, jenjang, atau jalur masuk khusus (misal: jalur PPDB prestasi vs reguler).
```sql
CREATE TABLE IF NOT EXISTS `transaksi_spp_tarif` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` CHAR(36) NOT NULL,
    `komponen_id` INT UNSIGNED NOT NULL,
    `kelas_id` INT UNSIGNED DEFAULT NULL,
    `jenjang_id` INT UNSIGNED DEFAULT NULL,
    `jalur_masuk` VARCHAR(50) DEFAULT NULL, -- Menghubungkan tarif PPDB dengan jalur masuk (reguler/prestasi)
    `nominal` DECIMAL(12, 2) NOT NULL,
    `tahun_ajaran_id` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_spp_tarif_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_spp_tarif_komponen` FOREIGN KEY (`komponen_id`) REFERENCES `transaksi_spp_komponen` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_spp_tarif_kelas` FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_spp_tarif_jenjang` FOREIGN KEY (`jenjang_id`) REFERENCES `jenjang` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_spp_tarif_ta` FOREIGN KEY (`tahun_ajaran_id`) REFERENCES `tahun_ajaran` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### D. Tabel `transaksi_spp_keringanan`
Mengatur diskon/beasiswa nominal/persentase secara personal per siswa/pendaftar PPDB.
```sql
CREATE TABLE IF NOT EXISTS `transaksi_spp_keringanan` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` CHAR(36) NOT NULL,
    `siswa_id` CHAR(36) NOT NULL,
    `komponen_id` INT UNSIGNED NOT NULL,
    `tipe_keringanan` ENUM('Nominal', 'Persentase') NOT NULL DEFAULT 'Nominal',
    `nilai` DECIMAL(12, 2) NOT NULL,
    `keterangan` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_spp_keringanan_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_spp_keringanan_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_spp_keringanan_komponen` FOREIGN KEY (`komponen_id`) REFERENCES `transaksi_spp_komponen` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### E. Tabel `transaksi_spp_tagihan`
Menyimpan tagihan diterbitkan untuk siswa maupun pendaftar PPDB.
```sql
CREATE TABLE IF NOT EXISTS `transaksi_spp_tagihan` (
    `id` CHAR(36) PRIMARY KEY,
    `tenant_id` CHAR(36) NOT NULL,
    `siswa_id` CHAR(36) NOT NULL, -- Pendaftar PPDB sudah memiliki record di tabel siswa
    `komponen_id` INT UNSIGNED NOT NULL,
    `tarif_id` INT UNSIGNED NOT NULL,
    `tahun_ajaran_id` INT UNSIGNED NOT NULL,
    `bulan` TINYINT DEFAULT NULL, -- 1 s/d 12 untuk bulanan
    `nominal_tagihan` DECIMAL(12, 2) NOT NULL,
    `nominal_bayar` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `status_lunas` ENUM('Belum', 'Cicil', 'Lunas') DEFAULT 'Belum',
    `jatuh_tempo` DATE DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_spp_tagihan_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_spp_tagihan_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_spp_tagihan_komponen` FOREIGN KEY (`komponen_id`) REFERENCES `transaksi_spp_komponen` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_spp_tagihan_ta` FOREIGN KEY (`tahun_ajaran_id`) REFERENCES `tahun_ajaran` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### F. Tabel `transaksi_spp_pembayaran`
Buku besar (*ledger*) pencatatan mutasi transaksi kasir masuk per tagihan.
```sql
CREATE TABLE IF NOT EXISTS `transaksi_spp_pembayaran` (
    `id` CHAR(36) PRIMARY KEY,
    `tenant_id` CHAR(36) NOT NULL,
    `tagihan_id` CHAR(36) NOT NULL,
    `siswa_id` CHAR(36) NOT NULL,
    `nominal_dibayar` DECIMAL(12, 2) NOT NULL,
    `metode_pembayaran` ENUM('Tunai', 'Transfer', 'Payment Gateway') DEFAULT 'Tunai',
    `tanggal_bayar` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `kasir_id` CHAR(36) NOT NULL,
    `nomor_kwitansi` VARCHAR(50) NOT NULL UNIQUE,
    `keterangan` VARCHAR(255) DEFAULT NULL,
    `gateway_reference_id` VARCHAR(100) DEFAULT NULL, -- Persiapan Payment Gateway masa depan
    `status_transaksi` ENUM('Pending', 'Success', 'Failed') DEFAULT 'Success', -- Persiapan Payment Gateway masa depan
    CONSTRAINT `fk_spp_bayar_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_spp_bayar_tagihan` FOREIGN KEY (`tagihan_id`) REFERENCES `transaksi_spp_tagihan` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_spp_bayar_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_spp_bayar_kasir` FOREIGN KEY (`kasir_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### G. Tabel `transaksi_spp_audit_log`
Log audit aktivitas keuangan.
```sql
CREATE TABLE IF NOT EXISTS `transaksi_spp_audit_log` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` CHAR(36) NOT NULL,
    `user_id` CHAR(36) NOT NULL,
    `aksi` VARCHAR(50) NOT NULL, -- e.g. 'EDIT_NOMINAL', 'VOID_TRANSACTION', 'APPLY_DISCOUNT'
    `tabel_target` VARCHAR(50) NOT NULL,
    `target_id` VARCHAR(36) NOT NULL,
    `data_sebelum` TEXT DEFAULT NULL, -- JSON format data lama
    `data_sesudah` TEXT DEFAULT NULL, -- JSON format data baru
    `keterangan` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_spp_audit_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_spp_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 4. Proposed Changes (Langkah Perubahan Modul)

### A. Rute Utama & API (`index.php`)
[NEW] Mendaftarkan rute keuangan baru `/keuangan/*` dan `/api/v1/keuangan/*` untuk kasir, tagihan, dan laporan.

### B. Controller Baru (`app/Controllers/SppController.php`)
- [NEW] Loket Kasir, bulk generator tagihan menggunakan DB transactions.
- [NEW] Integrasi Pendaftaran: Menyediakan hook API internal `/api/v1/keuangan/buat-tagihan-ppdb` yang dipanggil otomatis oleh Modul PPDB saat calon siswa mendaftar.

### C. Antarmuka Client (Vue 3)
- [NEW] `views/keuangan/kasir.php`: Keranjang pembayaran multi-komponen dan cicilan.
- [NEW] `views/keuangan/pengaturan.php`: Form kustomisasi istilah ("SPP", "Uang Pendaftaran", "Uang Gedung") dan toggle visibilitas.

---

## Verification Plan

### Automated Tests
1. **Migration test**:
   Menjalankan `php migrate.php` untuk memastikan seluruh tabel `transaksi_spp_` terbuat sukses.
2. **PPDB Integration Script (`scratch/test_ppmb_billing_integration.php`)**:
   - Mendaftarkan siswa baru di tabel `siswa` (sebagai calon peserta didik).
   - Menstimulasi pemanggilan endpoint pendaftaran untuk membuat tagihan "Formulir PPDB" otomatis.
   - Melakukan checkout lunas pada tagihan tersebut dan memvalidasi status kelayakan data pendaftaran calon siswa berubah menjadi siap diverifikasi.

### Manual Verification
1. Login sebagai **Admin Sekolah**, atur tarif formulir pendaftaran PPDB.
2. Simulasikan pendaftaran calon siswa baru dari dashboard depan PPDB.
3. Pastikan pada dashboard admin kasir muncul tagihan pendaftaran atas nama calon siswa baru tersebut.
4. Lakukan pelunasan di loket, pastikan struk tercetak dan status pendaftaran calon siswa ter-update otomatis di menu PPDB.
