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

---
## [Pembuatan Seeder Full Modul Keuangan dan Pembayaran]
**Waktu**: 11:15 WIB
**Status**: Dieksekusi

### Latar Belakang
Pengguna meminta pembuatan data seeder penuh untuk modul Keuangan dan Pembayaran (SPP, Kegiatan Tengah Semester/KTS, Pembelian Buku, Sumbangan Sukarela, Uang Gedung/Pangkal, dan Formulir PPDB) untuk mendukung testing lokal aplikasi.

### Proposed Changes

#### [NEW] [2026_07_21_02_seed_spp_data.php](file:///C:/xampp/htdocs/SINTA-SaaS/database/migrations/2026_07_21_02_seed_spp_data.php)

Berikut adalah kode PHP lengkap seeder migrasi yang direncanakan:

```php
<?php
/**
 * Migration Seeder: Seed Data Keuangan dan Pembayaran (SPP)
 * Location: database/migrations/2026_07_21_02_seed_spp_data.php
 */

return [
    'up' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

        // 1. Seed Pengaturan Modul Keuangan (Transaksi SPP Pengaturan)
        $tenants = $pdo->query("SELECT id FROM tenants")->fetchAll(PDO::FETCH_COLUMN);
        
        $stmtSettings = $pdo->prepare("
            INSERT INTO `transaksi_spp_pengaturan` (`tenant_id`, `nama_modul`, `istilah_tagihan`, `istilah_tunggakan`, `visibilitas_siswa`)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE nama_modul=VALUES(nama_modul), istilah_tagihan=VALUES(istilah_tagihan), istilah_tunggakan=VALUES(istilah_tunggakan);
        ");

        foreach ($tenants as $tenantId) {
            $stmtSettings->execute([
                $tenantId,
                'Keuangan & SPP',
                'Tagihan',
                'Tunggakan',
                1
            ]);
        }

        // 2. Seed Komponen Biaya (Transaksi SPP Komponen)
        $komponenTemplates = [
            ['nama_komponen' => 'SPP Bulanan', 'tipe_periode' => 'Bulanan'],
            ['nama_komponen' => 'Kegiatan Tengah Semester (KTS)', 'tipe_periode' => 'Semester'],
            ['nama_komponen' => 'Pembelian Buku Paket', 'tipe_periode' => 'Bebas'],
            ['nama_komponen' => 'Sumbangan Sukarela', 'tipe_periode' => 'Bebas'],
            ['nama_komponen' => 'Uang Pangkal / Gedung', 'tipe_periode' => 'Tahunan'],
            ['nama_komponen' => 'Formulir Pendaftaran PPDB', 'tipe_periode' => 'Bebas'],
        ];

        $stmtKomponen = $pdo->prepare("
            INSERT INTO `transaksi_spp_komponen` (`tenant_id`, `nama_komponen`, `tipe_periode`, `is_active`)
            VALUES (?, ?, ?, 1)
        ");

        $pdo->exec("TRUNCATE TABLE `transaksi_spp_komponen`;");

        foreach ($tenants as $tenantId) {
            foreach ($komponenTemplates as $kt) {
                $stmtKomponen->execute([
                    $tenantId,
                    $kt['nama_komponen'],
                    $kt['tipe_periode']
                ]);
            }
        }

        // 3. Seed Tarif Default per Komponen (Transaksi SPP Tarif)
        $allKomponen = $pdo->query("SELECT id, tenant_id, nama_komponen FROM `transaksi_spp_komponen`")->fetchAll(PDO::FETCH_ASSOC);
        $allKelas = $pdo->query("SELECT id, tenant_id, id_jenjang FROM `kelas`")->fetchAll(PDO::FETCH_ASSOC);
        $allJenjang = $pdo->query("SELECT id FROM `jenjang`")->fetchAll(PDO::FETCH_COLUMN);
        $allTA = $pdo->query("SELECT id FROM `tahun_ajaran`")->fetchAll(PDO::FETCH_COLUMN);

        $stmtTarif = $pdo->prepare("
            INSERT INTO `transaksi_spp_tarif` (`tenant_id`, `komponen_id`, `kelas_id`, `jenjang_id`, `jalur_masuk`, `nominal`, `tahun_ajaran_id`)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $pdo->exec("TRUNCATE TABLE `transaksi_spp_tarif`;");

        $tarifMap = [
            'SPP Bulanan' => 200000.00,
            'Kegiatan Tengah Semester (KTS)' => 150000.00,
            'Pembelian Buku Paket' => 450000.00,
            'Sumbangan Sukarela' => 0.00,
            'Uang Pangkal / Gedung' => 2500000.00,
            'Formulir Pendaftaran PPDB' => 100000.00
        ];

        foreach ($allKomponen as $komp) {
            $tid = $komp['tenant_id'];
            $kid = $komp['id'];
            $namaK = $komp['nama_komponen'];
            $nominal = $tarifMap[$namaK] ?? 100000.00;

            foreach ($allTA as $taId) {
                if ($namaK === 'Formulir Pendaftaran PPDB') {
                    foreach ($allJenjang as $jId) {
                        $stmtTarif->execute([$tid, $kid, null, $jId, 'Reguler', $nominal, $taId]);
                        $stmtTarif->execute([$tid, $kid, null, $jId, 'Prestasi', $nominal - 25000.00, $taId]);
                    }
                } elseif ($namaK === 'Uang Pangkal / Gedung') {
                    foreach ($allJenjang as $jId) {
                        $stmtTarif->execute([$tid, $kid, null, $jId, null, $nominal, $taId]);
                    }
                } else {
                    foreach ($allKelas as $kls) {
                        $stmtTarif->execute([$tid, $kid, $kls['id'], null, null, $nominal, $taId]);
                    }
                }
            }
        }

        // 4. Seed Keringanan / Diskon (Transaksi SPP Keringanan)
        $pdo->exec("TRUNCATE TABLE `transaksi_spp_keringanan`;");
        
        $stmtKeringanan = $pdo->prepare("
            INSERT INTO `transaksi_spp_keringanan` (`tenant_id`, `siswa_id`, `komponen_id`, `tipe_keringanan`, `nilai`, `keterangan`)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $siswaContoh = $pdo->query("
            SELECT s.id, s.tenant_id, k.id as komponen_id 
            FROM siswa s
            JOIN `transaksi_spp_komponen` k ON s.tenant_id = k.tenant_id
            WHERE k.nama_komponen = 'SPP Bulanan'
            LIMIT 3
        ")->fetchAll(PDO::FETCH_ASSOC);

        if (isset($siswaContoh[0])) {
            $stmtKeringanan->execute([
                $siswaContoh[0]['tenant_id'],
                $siswaContoh[0]['id'],
                $siswaContoh[0]['komponen_id'],
                'Nominal',
                50000.00,
                'Beasiswa Prestasi Akademik (Potongan SPP)'
            ]);
        }
        if (isset($siswaContoh[1])) {
            $stmtKeringanan->execute([
                $siswaContoh[1]['tenant_id'],
                $siswaContoh[1]['id'],
                $siswaContoh[1]['komponen_id'],
                'Persentase',
                100.00,
                'Beasiswa Penuh Siswa Asuh / Yatim Piatu'
            ]);
        }

        // 5. Seed Tagihan Terbit (Transaksi SPP Tagihan)
        $pdo->exec("TRUNCATE TABLE `transaksi_spp_tagihan`;");
        
        $stmtTagihan = $pdo->prepare("
            INSERT INTO `transaksi_spp_tagihan` (`id`, `tenant_id`, `siswa_id`, `komponen_id`, `tarif_id`, `tahun_ajaran_id`, `bulan`, `nominal_tagihan`, `nominal_bayar`, `status_lunas`, `jatuh_tempo`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $allTarifs = $pdo->query("
            SELECT t.*, k.nama_komponen, k.tipe_periode 
            FROM `transaksi_spp_tarif` t
            JOIN `transaksi_spp_komponen` k ON t.komponen_id = k.id
        ")->fetchAll(PDO::FETCH_ASSOC);

        $siswaList = $pdo->query("SELECT id, tenant_id, id_kelas FROM siswa")->fetchAll(PDO::FETCH_ASSOC);

        foreach ($siswaList as $siswa) {
            $tid = $siswa['tenant_id'];
            $sid = $siswa['id'];
            $klsId = $siswa['id_kelas'];

            foreach ($allTarifs as $tarif) {
                if ($tarif['tenant_id'] !== $tid) continue;
                if ($tarif['kelas_id'] !== null && $tarif['kelas_id'] != $klsId) continue;

                $tagihanId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000,
                    mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );

                $nominalAsli = $tarif['nominal'];
                $nominalTagihan = $nominalAsli;

                $stmtCheckKeringanan = $pdo->prepare("SELECT tipe_keringanan, nilai FROM `transaksi_spp_keringanan` WHERE siswa_id = ? AND komponen_id = ? LIMIT 1");
                $stmtCheckKeringanan->execute([$sid, $tarif['komponen_id']]);
                $keringanan = $stmtCheckKeringanan->fetch(PDO::FETCH_ASSOC);
                
                if ($keringanan) {
                    if ($keringanan['tipe_keringanan'] === 'Nominal') {
                        $nominalTagihan = max(0.00, $nominalAsli - $keringanan['nilai']);
                    } else {
                        $nominalTagihan = max(0.00, $nominalAsli * (1 - ($keringanan['nilai'] / 100)));
                    }
                }

                if ($tarif['nama_komponen'] === 'SPP Bulanan') {
                    $bulanList = [7, 8, 9];
                    foreach ($bulanList as $b) {
                        $uuidBulan = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                            mt_rand(0, 0xffff),
                            mt_rand(0, 0x0fff) | 0x4000,
                            mt_rand(0, 0x3fff) | 0x8000,
                            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                        );
                        
                        $nomBayar = 0.00;
                        $statusLunas = 'Belum';
                        
                        if ($b == 7) {
                            $nomBayar = $nominalTagihan;
                            $statusLunas = 'Lunas';
                        } elseif ($b == 8) {
                            $nomBayar = round($nominalTagihan / 2, 2);
                            $statusLunas = 'Cicil';
                        }

                        $stmtTagihan->execute([
                            $uuidBulan, $tid, $sid, $tarif['komponen_id'], $tarif['id'], $tarif['tahun_ajaran_id'],
                            $b, $nominalTagihan, $nomBayar, $statusLunas, "2026-0" . ($b) . "-10"
                        ]);
                    }
                } elseif ($tarif['nama_komponen'] === 'Kegiatan Tengah Semester (KTS)') {
                    $stmtTagihan->execute([
                        $tagihanId, $tid, $sid, $tarif['komponen_id'], $tarif['id'], $tarif['tahun_ajaran_id'],
                        null, $nominalTagihan, $nominalTagihan, 'Lunas', '2026-10-15'
                    ]);
                } elseif ($tarif['nama_komponen'] === 'Pembelian Buku Paket') {
                    $stmtTagihan->execute([
                        $tagihanId, $tid, $sid, $tarif['komponen_id'], $tarif['id'], $tarif['tahun_ajaran_id'],
                        null, $nominalTagihan, 0.00, 'Belum', '2026-08-30'
                    ]);
                } elseif ($tarif['nama_komponen'] === 'Uang Pangkal / Gedung') {
                    $stmtTagihan->execute([
                        $tagihanId, $tid, $sid, $tarif['komponen_id'], $tarif['id'], $tarif['tahun_ajaran_id'],
                        null, $nominalTagihan, 1000000.00, 'Cicil', '2026-12-31'
                    ]);
                }
            }
        }

        // 6. Seed Ledger Pembayaran (Transaksi SPP Pembayaran)
        $pdo->exec("TRUNCATE TABLE `transaksi_spp_pembayaran`;");

        $stmtPembayaran = $pdo->prepare("
            INSERT INTO `transaksi_spp_pembayaran` (`id`, `tenant_id`, `tagihan_id`, `siswa_id`, `nominal_dibayar`, `metode_pembayaran`, `kasir_id`, `nomor_kwitansi`, `keterangan`, `status_transaksi`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Success')
        ");

        $tagihanBerbayar = $pdo->query("SELECT * FROM `transaksi_spp_tagihan` WHERE nominal_bayar > 0.00")->fetchAll(PDO::FETCH_ASSOC);

        $kasirList = $pdo->query("
            SELECT u.id, u.tenant_id 
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE r.nama_role IN ('operator_sekolah', 'super_admin')
        ")->fetchAll(PDO::FETCH_ASSOC);

        $kasirMap = [];
        foreach ($kasirList as $k) {
            $kasirMap[$k['tenant_id'] ?? 'global'] = $k['id'];
        }

        $kwitansiCounter = 1;

        foreach ($tagihanBerbayar as $tag) {
            $tid = $tag['tenant_id'];
            $sid = $tag['siswa_id'];
            $tagId = $tag['id'];
            $nominalBayar = $tag['nominal_bayar'];

            $kasirId = $kasirMap[$tid] ?? ($kasirMap['global'] ?? null);
            if (!$kasirId) continue;

            $pembayaranId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );

            $nomorKwitansi = "KW/SPP/" . date('Ymd') . "/" . str_pad($kwitansiCounter++, 4, '0', STR_PAD_LEFT);
            $metode = ($kwitansiCounter % 2 == 0) ? 'Tunai' : 'Transfer';

            $stmtPembayaran->execute([
                $pembayaranId,
                $tid,
                $tagId,
                $sid,
                $nominalBayar,
                $metode,
                $kasirId,
                $nomorKwitansi,
                'Pembayaran uji coba seeder otomatis',
            ]);
        }

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "- Seeder data keuangan SPP berhasil dieksekusi.\n";
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec("TRUNCATE TABLE `transaksi_spp_pembayaran`;");
        $pdo->exec("TRUNCATE TABLE `transaksi_spp_tagihan`;");
        $pdo->exec("TRUNCATE TABLE `transaksi_spp_keringanan`;");
        $pdo->exec("TRUNCATE TABLE `transaksi_spp_tarif`;");
        $pdo->exec("TRUNCATE TABLE `transaksi_spp_komponen`;");
        $pdo->exec("TRUNCATE TABLE `transaksi_spp_pengaturan`;");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "- Seeder data keuangan SPP berhasil di-rollback.\n";
    }
];
```

### Verification Plan
- Menjalankan `php migrate.php` di CLI untuk mengeksekusi seeder.
- Menjalankan kueri SQL pengecekan isi tabel untuk memastikan records terisi penuh secara dinamis sesuai relasi sekolah, kelas, dan siswa masing-masing.

---
## [Redesain Layout Dashboard & Area Kerja Compact Full-Screen 30:70 Split]
**Waktu**: 12:06 WIB
**Status**: Draft

### Latar Belakang
Pada modul Keuangan dan Pembayaran aplikasi SINTA-SaaS, tata letak area konten utama (body content) saat ini menyisakan ruang putih kosong yang luas di bagian bawah layar. Pengguna membutuhkan redesain antarmuka agar elemen-elemen form input dan tabel tersaji secara terpadu, lebih padat (compact), responsif, dan membentang penuh mengisi tinggi layar kerja yang tersedia (full-screen layout), dengan rasio pembagian kolom 30% Form Input (kiri) dan 70% Tabel Data (kanan).

### Proposed Changes

#### [NEW] [test_compact_layout.php](file:///C:/xampp/htdocs/SINTA-SaaS/scratch/test_compact_layout.php)

Mockup halaman pengujian mandiri di folder `scratch/` untuk memvalidasi struktur HTML/CSS compact layout sebelum diterapkan ke view operasional aplikasi.

#### Rincian Desain & Struktur CSS:
1. **Navigasi Sidebar**: Tetap menyertakan 8 menu utama modul keuangan (Dashboard Keuangan, Atur Tarif & Biaya, Keringanan & Beasiswa, Generate Tagihan, Loket Pembayaran, Laporan Keuangan, Pengaturan Keuangan, Tagihan Saya).
2. **Kepadatan Informasi (Compactness)**:
   - Margin dan padding elemen dikurangi (misal: padding panel 0.75rem, padding sel tabel 0.4rem).
   - Ukuran font teks 0.8rem dan label 0.75rem untuk efisiensi ruang pandang.
   - Form input berukuran ramping (input height dikurangi, border radius 6px).
   - Tabel rapat (`table-compact` dengan status `table-sm`).
3. **Grid System & Full-Height Layout**:
   - Kontainer induk menggunakan flex-direction vertical setinggi `calc(100vh - var(--header-height) - 3rem)`.
   - Scrollbar global disembunyikan (`overflow: hidden` pada container utama), dan scrollbar diaktifkan secara internal hanya pada kontainer bodi tabel (`overflow-y: auto`). Hal ini membuat header tabel bersifat melayang (`position: sticky`) saat data ditarik ke bawah.
4. **Rasio Kolom 30:70**:
   - Kolom Kiri: Panel form input (`width: 30%`).
   - Kolom Kanan: Panel tabel grid data (`width: 70%`).

### Verification Plan
- Membuat berkas visualisasi layout di `scratch/test_compact_layout.php`.
- Membuka halaman visualisasi di browser lokal untuk memvalidasi pemakaian sisa ruang vertikal layar, keterbacaan font kecil, kelancaran scroll internal tabel, dan responsivitas grid saat ukuran jendela diperkecil.

---
## [Penyelarasan Desain Compact, Premium, & Responsive di Seluruh Halaman Keuangan]
**Waktu**: 16:00 WIB
**Status**: Draft

# Rencana Implementasi: Penyelarasan Desain Compact, Premium, & Responsive di Seluruh Halaman Keuangan

Rencana ini bertujuan menyelaraskan seluruh tampilan antarmuka (UI/UX) pada halaman-halaman modul Keuangan & Pembayaran di aplikasi SINTA-SaaS agar memiliki bahasa desain yang konsisten dengan halaman **Atur Tarif & Biaya** (`master.php`). Setiap halaman akan menggunakan layout compact setinggi layar (`full-height`/`zero-scroll-global`) pada desktop, dan beralih ke layout stacked kolom natural yang responsif di smartphone.

---

## Deskripsi Rencana & Target Halaman

Kami akan mendesain ulang 7 berkas tampilan keuangan berikut:
1.  **Dashboard Keuangan** (`views/keuangan/dashboard.php`)
2.  **Keringanan & Beasiswa** (`views/keuangan/keringanan.php`)
3.  **Generate Tagihan Massal** (`views/keuangan/generate.php`)
4.  **Loket Pembayaran Kasir** (`views/keuangan/kasir.php`)
5.  **Laporan Keuangan** (`views/keuangan/laporan.php`)
6.  **Pengaturan Istilah & Visibilitas** (`views/keuangan/pengaturan.php`)
7.  **Tagihan Saya (Dashboard Siswa)** (`views/keuangan/tagihan_saya.php`)

Setiap halaman di atas akan diubah untuk mengadopsi standar CSS kustom yang compact dan responsif.

---

## Rincian Perubahan Per Komponen

### 1. File CSS & Layout Master Global (`views/layout/master.php`)
Kami akan menaruh utility CSS kustom secara langsung di masing-masing file view agar bersifat modular dan independen, atau mendefinisikannya di bagian atas/bawah tag `<style>` masing-masing halaman.
Setiap halaman akan mengimplementasikan model:
*   `.workspace-container`: Membatasi tinggi halaman `height: calc(100vh - var(--header-height) - 1.5rem)` dan mematikan scroll global `overflow: hidden`.
*   `.workspace-body`: Mengatur arah flex `display: flex; gap: 0.75rem; flex-grow: 1; overflow: hidden;`.
*   `@media (max-width: 767.98px)`: Mengubah seluruh layout menjadi `height: auto`, `overflow: visible`, `flex-direction: column`, dan mengembalikan scroll manual secara vertikal agar terlihat cantik di smartphone.

---

## Rencana Berkas yang Diubah

### [MODIFY] [dashboard.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/dashboard.php)
*   **Perubahan**:
    - Membungkus kontainer utama dengan kelas `.workspace-container`.
    - Mengurangi padding stats card dari `p-4` menjadi `p-3` yang lebih hemat ruang.
    - Memindahkan tabel rekapitulasi pelunasan kelas ke dalam kontainer `.table-compact-container` dengan `.table-compact` dan header kolom sticky agar data dapat di-scroll secara internal.
    - Menambahkan query media HP untuk menata ulang grid statistik menjadi 1 kolom penuh di layar HP.

### [MODIFY] [keringanan.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/keringanan.php)
*   **Perubahan**:
    - Mengubah total tata letak baris/kolom Bootstrap menjadi pembagian kolom flex 30% (`panel-form`) dan 70% (`panel-table`) seperti di halaman `master.php`.
    - Menerapkan input `.form-compact` (padding kecil, tinggi 32px) dan tabel `.table-compact` dengan scrollbar internal.
    - Menambahkan query media HP agar form dan tabel bertumpuk secara vertikal di smartphone.

### [MODIFY] [generate.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/generate.php)
*   **Perubahan**:
    - Mengubah kontainer formulir generate menjadi `.panel-form` berukuran compact (misalnya dengan pembatas lebar maksimal `col-12 col-md-8 mx-auto` untuk desktop).
    - Memadatkan ukuran label, input, select, dan alert info agar tidak memakan ruang kosong yang berlebih.
    - Menyesuaikan tombol submit generator ke gaya compact.

### [MODIFY] [kasir.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/kasir.php)
*   **Perubahan**:
    - Menyusun area transaksi pembayaran menjadi layout modular 65% Daftar Tagihan Siswa (`panel-table`) dan 35% Panel Pembayaran & Cetak Kuitansi (`panel-form`).
    - Membuat panel daftar tagihan dapat di-scroll secara internal dengan header tabel sticky, sehingga kasir dapat membandingkan banyak tagihan dengan mudah.
    - Mengintegrasikan panel pencarian siswa sebagai bagian dari header layout yang tersemat rapi.
    - Menerapkan query media HP agar form checkout pembayaran bergeser ke bawah tabel tagihan secara otomatis di smartphone.

### [MODIFY] [laporan.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/laporan.php)
*   **Perubahan**:
    - Memposisikan filter laporan (pilihan tanggal, cari nama, tombol ekspor) ke dalam baris toolbar compact yang diletakkan di dalam `.panel-header`.
    - Memposisikan tabel list laporan pemasukan kasir dan tunggakan siswa ke dalam `.table-compact-container` 100% lebar layar.
    - Mengatur agar tinggi area tabel laporan memanfaatkan sisa tinggi viewport secara dinamis pada desktop.

### [MODIFY] [pengaturan.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/pengaturan.php)
*   **Perubahan**:
    - Mengemas panel konfigurasi nama modul, istilah tagihan/tunggakan, dan visibilitas siswa ke dalam `.panel-form` compact di tengah halaman (`col-12 col-md-7 mx-auto`).
    - Mengaplikasikan desain input form 32px yang konsisten dengan halaman pengaturan lainnya.

### [MODIFY] [tagihan_saya.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/tagihan_saya.php)
*   **Perubahan**:
    - Memadatkan visualisasi profil data siswa dan tagihan aktif.
    - Menampilkan daftar tunggakan siswa dalam format `.table-compact` dengan tinggi dinamis.
    - Membuat tata letak kartu tagihan ringkas yang sangat nyaman dibaca oleh siswa dari layar smartphone.

---

## Rencana Pengujian & Verifikasi

### Manual Verification
1.  **Pengujian Desktop (Resolusi Lebar)**:
    - Buka setiap menu dari sidebar (Dashboard Keuangan, Atur Tarif & Biaya, Keringanan, Generate Tagihan, Loket Pembayaran, Laporan Keuangan, Pengaturan).
    - Pastikan tinggi halaman terkunci (tidak ada scrollbar vertikal global di window browser luar) dan scrollbar internal tabel aktif.
    - Pastikan semua tab navigasi dan tombol beroperasi secara interaktif tanpa galat konsol JS.
2.  **Pengujian Smartphone (Resolusi Mobile)**:
    - Gunakan mode "Inspect Element -> Device Toggle (HP)" atau akses lewat HP.
    - Pastikan seluruh halaman otomatis bertumpuk (stacked) secara vertikal dengan lebar 100%.
    - Pastikan data tabel dapat digeser secara horizontal jika melebihi lebar layar, dan halaman browser luar dapat di-scroll vertikal secara natural.

---
## [Penyelarasan Gaya Navtabs & Tabel Modul Keuangan SINTA-SaaS]
**Waktu**: 16:10 WIB
**Status**: Draft

# Rencana Implementasi: Penyelarasan Gaya Navtabs & Tabel Modul Keuangan SINTA-SaaS

Rencana ini bertujuan menyelaraskan gaya **Navigasi Tabs (Navtabs)** dan **Tabel Data (Tables)** di seluruh halaman modul Keuangan & Pembayaran agar serupa dengan visual pada halaman Manajemen Pengguna (flat minimalis modern):
1.  **Navtabs**: Menghapus outline tab folder default Bootstrap. Menggunakan gaya underline datar (flat underline) di mana tab yang aktif hanya diberi garis bawah biru (`2px solid #2563eb`) dan teks biru, sedangkan tab tidak aktif memiliki border bawah transparan dan teks slate-gray.
2.  **Tables**: Menghapus garis kisi vertikal, menggunakan latar header `#f8fafc` dengan teks uppercase slate-gray, memberikan garis pembatas bawah baris yang sangat tipis (`1px solid #f1f5f9`), serta menambahkan efek hover row yang halus.

---

## Target Berkas yang Diubah

Kami akan memperbarui bagian CSS `<style>` pada berkas-berkas berikut:
1.  **Atur Tarif & Biaya** (`views/keuangan/master.php`)
2.  **Keringanan & Beasiswa** (`views/keuangan/keringanan.php`)
3.  **Laporan Keuangan** (`views/keuangan/laporan.php`)
4.  **Dashboard Keuangan** (`views/keuangan/dashboard.php`)
5.  **Loket Pembayaran Kasir** (`views/keuangan/kasir.php`)
6.  **Tagihan Saya (Dashboard Siswa)** (`views/keuangan/tagihan_saya.php`)

---

## Detail Perubahan CSS untuk Penyelarasan Desain

Kami akan menambahkan/memperbarui CSS berikut ke dalam blok `<style>` masing-masing berkas view di atas:

### A. Penyelarasan Navtabs
```css
/* Penyelarasan Navtabs Minimalis Modern (Flat Underline) */
.nav-tabs {
    border-bottom: 1px solid #e2e8f0 !important;
}
.nav-tabs .nav-item {
    margin-bottom: -1px;
}
.nav-tabs .nav-link {
    border: none !important;
    border-bottom: 2px solid transparent !important;
    color: #64748b !important;
    font-weight: 600 !important;
    background: transparent !important;
    padding: 0.6rem 1rem !important;
    transition: all 0.15s ease-in-out;
}
.nav-tabs .nav-link:hover {
    color: #1e293b !important;
    border-bottom-color: #cbd5e1 !important;
}
.nav-tabs .nav-link.active {
    color: #2563eb !important;
    border-bottom-color: #2563eb !important;
    background: transparent !important;
}
```

### B. Penyelarasan Tabel (`.table-compact`)
```css
/* Penyelarasan Tabel Minimalis Tanpa Garis Vertikal */
.table-compact-container {
    border: 1px solid #e2e8f0 !important;
    border-radius: 8px !important;
    overflow: hidden;
    background: #ffffff;
}
.table-compact {
    border-collapse: collapse !important;
}
.table-compact th {
    background-color: #f8fafc !important;
    color: #475569 !important;
    font-weight: 700 !important;
    font-size: 0.72rem !important;
    text-transform: uppercase !important;
    letter-spacing: 0.05em !important;
    border-bottom: 2px solid #e2e8f0 !important;
    border-top: none !important;
    border-left: none !important;
    border-right: none !important;
    padding: 0.6rem 0.75rem !important;
}
.table-compact td {
    border-bottom: 1px solid #f1f5f9 !important;
    border-top: none !important;
    border-left: none !important;
    border-right: none !important;
    padding: 0.52rem 0.75rem !important;
    font-size: 0.78rem !important;
    color: #334155 !important;
    background-color: transparent !important;
}
.table-compact tbody tr {
    transition: background-color 0.15s ease;
}
.table-compact tbody tr:hover {
    background-color: #f8fafc !important;
}
```

---

## Rencana Pengujian & Verifikasi

### Manual Verification
1.  **Verifikasi Tab**:
    - Klik tab di halaman Atur Tarif & Biaya (`master.php`) dan Laporan Keuangan (`laporan.php`).
    - Pastikan perpindahan tab mulus dengan indikator garis bawah biru tanpa border kotak folder bawaan Bootstrap.
2.  **Verifikasi Tabel**:
    - Buka Loket Kasir, Keringanan, Laporan, Dashboard, dan Tagihan Saya.
    - Pastikan semua tabel data tidak memiliki border tegak lurus (vertikal), memiliki warna latar header abu-abu tipis dengan teks kapital (uppercase), dan border bawah sel sangat halus.




