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

---
## [Penyempurnaan Multi-Tenant, Filter, Toggle Status, dan Pagination Modul Keuangan SaaS]
**Waktu**: 16:55 WIB
**Status**: Draft

### Latar Belakang & Analisis Masalah
Pengguna meminta penambahan fitur SaaS profesional untuk peran `super_admin` agar dapat memilih sekolah (tenant) sasaran, menyaring jenis komponen biaya di laporan transaksi, menggunakan pagination pada seluruh tabel utama, dan melakukan kontrol status keaktifan komponen biaya melalui sakelar (toggle ON/OFF) dinamis.

### Rencana Perubahan Kode (Proposed Changes)

#### [MODIFY] [index.php](file:///C:/xampp/htdocs/SINTA-SaaS/index.php)
- Menambahkan route API `/api/v1/keuangan/tenants` dan `/api/v1/keuangan/komponen/toggle`.

#### [MODIFY] [SppController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/SppController.php)
- Menambahkan endpoints `apiTenants()` dan `apiToggleKomponen()`.
- Memperbarui `resolveTenantId()` dan endpoints terkait agar fleksibel terhadap input parameter `tenant_id` dari client.

#### [MODIFY] [master.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/master.php)
- Menambahkan Tenant Selector di atas tab.
- Menambahkan sakelar Toggle ON/OFF keaktifan komponen biaya (`is_active`).
- Menambahkan pagination client-side pada daftar komponen dan tarif.

#### [MODIFY] [keringanan.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/keringanan.php)
- Menambahkan Tenant Selector dan pagination pada daftar penerima beasiswa.

#### [MODIFY] [generate.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/generate.php)
- Menambahkan Tenant Selector dan sinkronisasi otomatis komponen, kelas, dan tahun ajaran per sekolah.

#### [MODIFY] [kasir.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/kasir.php)
- Menambahkan Tenant Selector dan sinkronisasi autocomplete pencarian siswa berdasarkan sekolah terpilih.

#### [MODIFY] [laporan.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/laporan.php)
- Menambahkan Tenant Selector dan filter jenis komponen biaya.
- Menambahkan pagination pada tabel rekap pemasukan dan tunggakan.

#### [MODIFY] [pengaturan.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/pengaturan.php)
- Menambahkan Tenant Selector agar super_admin dapat menyesuaikan istilah finansial per sekolah secara terisolasi.

#### [MODIFY] [dashboard.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/dashboard.php)
- Menambahkan Tenant Selector untuk memperbarui metrik rekapitulasi pelunasan per sekolah.

#### [MODIFY] [tagihan_saya.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/tagihan_saya.php)
- Menambahkan pagination pada tabel rincian tagihan personal siswa.

### Rencana Verifikasi & Pengujian
- Menjalankan syntax checking `php -l`.
- Menguji login super_admin untuk melihat sinkronisasi persistensi dropdown tenant menggunakan `localStorage` di lintas halaman keuangan.
- Memastikan pagination berjalan mulus dan status sakelar komponen biaya ter-update langsung ke database.

---
## [Perbaikan Tampilan Pagination (Sliding Window / Ellipsis)]
**Waktu**: 17:23 WIB
**Status**: Dieksekusi

### Latar Belakang & Analisis Masalah
Saat jumlah halaman pada tabel data keuangan (Komponen Biaya, Tarif Acuan, Keringanan, Laporan, Tagihan Saya) bertambah sangat banyak (misal mencapai ratusan halaman), tombol pagination numerik yang dicetak menggunakan list per halaman (`v-for="p in totalPages"`) melebar melampaui lebar layar container (overflow horizontal), merusak desain antarmuka, dan mengganggu responsive layout.

### Proposed Changes

#### [MODIFY] [master.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/master.php)
- Mengganti loop pencetakan tombol pagination langsung dari `totalKompPages` dan `totalTarifPages` ke `visibleKompPages` dan `visibleTarifPages`.
- Menambahkan fungsi helper `getVisiblePages` pada Vue setup untuk menghitung kisaran tombol halaman (sliding window) di sekitar halaman saat ini dengan penambahan tombol ellipsis `...` apabila jarak halaman melebihi batas.

#### [MODIFY] [keringanan.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/keringanan.php)
- Mengimplementasikan `getVisiblePages` helper dan computed property `visibleKeringananPages` untuk batasan jumlah tombol halaman.

#### [MODIFY] [laporan.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/laporan.php)
- Mengimplementasikan `getVisiblePages` helper dan computed property `visiblePages` pada tabel transaksi/tunggakan keuangan.

#### [MODIFY] [tagihan_saya.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/tagihan_saya.php)
- Mengimplementasikan `getVisiblePages` helper dan computed property `visiblePages` pada tabel rincian tagihan personal siswa.

### Rencana Verifikasi & Pengujian
- Menjalankan syntax checking `php -l` di terminal untuk menjamin integritas kode.
- Menguji kelancaran interaksi tombol halaman numerik, tombol "Sebelumnya", "Berikutnya", dan status tombol `...` yang dinonaktifkan (`disabled`).

---
## [Penambahan Filter Sekolah, Tahun Ajaran, Komponen, dan Pengurutan Tahun Ajaran Terbaru di Master Keuangan untuk Super Admin]
**Waktu**: 17:31 WIB
**Status**: Dieksekusi

# Rencana Implementasi: Penambahan Filter Sekolah, Tahun Ajaran, Komponen, dan Pengurutan Tahun Ajaran Terbaru di Master Keuangan

Menyediakan fungsionalitas manajemen multi-tenant yang lebih kuat bagi Super Admin dengan menampilkan data sekolah, memberikan filter Sekolah, Tahun Ajaran, dan Komponen pada tabel Tarif Default, serta memastikan Tahun Ajaran terbaru selalu berada di posisi paling atas.

## User Review Required
> [!IMPORTANT]
> Fitur penyaringan ini bersifat lokal (client-side dynamic filter) sehingga respons antarmuka sangat cepat (instantaneous).
> Super Admin juga dapat memilih "-- Semua Sekolah --" pada dropdown filter global untuk melihat kompilasi seluruh komponen & tarif dari semua sekolah di satu halaman tunggal.

## Open Questions
Tidak ada. Seluruh spesifikasi kebutuhan telah dianalisis dan dirumuskan.

## Proposed Changes

### 1. Backend Controller (`app/Controllers/SppController.php`)
- **SQL Parameter Binding / SQLi Prevention (Anti-SQLi compliance)**: Memperbaiki string query di `keringanan()` dan `generate()` agar menggunakan prepared statements dengan binding parameter, menggantikan string interpolation.
- **Dukungan Tampilan Semua Sekolah untuk Super Admin**: Di `apiKomponen()` dan `apiTarif()` (metode `GET`), jika pengguna adalah `super_admin` dan parameter `tenant_id` tidak ditentukan atau kosong (`""`), API akan mengembalikan daftar komponen/tarif dari seluruh tenant/sekolah beserta relasi `tenants.nama_sekolah`.
- **Pengurutan Tahun Ajaran Terbaru di Bagian Atas**: Mengubah urutan kueri data tarif default (`apiTarif()`) agar diurutkan berdasarkan `tahun_ajaran DESC` terlebih dahulu, kemudian `t.id DESC`.

#### [MODIFY] [SppController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/SppController.php)

---

### 2. Frontend View (`views/keuangan/master.php`)
- **Global Dropdown Selector**: Menambahkan pilihan `-- Semua Sekolah (Global) --` (nilai kosong `""`) pada pemilih sekolah global Super Admin.
- **Input Filter Lokal pada Card "Daftar Tarif Default"**:
  - Pilihan Dropdown **Sekolah/Tenant** (Hanya tampil untuk Super Admin).
  - Pilihan Dropdown **Tahun Ajaran**.
  - Pilihan Dropdown **Komponen Biaya** (Dibuat dinamis menggunakan `computed` set unik nama komponen yang tersedia).
- **Kolom Sekolah pada Tabel**:
  - Menampilkan kolom `Sekolah` di tabel komponen biaya (Tab 1) dan tabel tarif default (Tab 2) hanya jika pengguna masuk sebagai `super_admin`.
- **Modifikasi Form Tambah/Edit Data**:
  - Pada form komponen, jika filter global terpilih "Semua Sekolah", Super Admin wajib memilih Sekolah tujuan di dropdown form sebelum menyimpan.
  - Pada form tarif, jika filter global terpilih "Semua Sekolah", tampilkan pesan informatif untuk mengarahkan pengguna agar memilih salah satu sekolah terlebih dahulu pada filter global sebelum menautkan tarif baru.
- **Logika Vue.js**:
  - Menambahkan reactive variables `filterTarifTenant`, `filterTarifTa`, `filterTarifKomp`.
  - Membuat computed property `uniqueKomponenNames` untuk opsi select filter komponen.
  - Memperbarui computed property `filteredTarif` agar menyaring tarif berdasarkan ketiga input filter tersebut secara dinamis.
  - Menambahkan watcher untuk mereset `tarifPage.value = 1` ketika nilai filter berubah.

#### [MODIFY] [master.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/master.php)

---

## Verification Plan

### Manual Verification
1. Masuk sebagai `super_admin` dan akses halaman `/SINTA-SaaS/keuangan/master`.
2. Verifikasi pemilih sekolah di pojok kiri atas/header memiliki opsi `-- Semua Sekolah (Global) --`.
3. Verifikasi opsi Tahun Ajaran pada form tambah tarif menampilkan tahun ajaran terbaru di bagian atas.
4. Pilih salah satu tenant, verifikasi data ter-filter sesuai tenant.
5. Pilih kembali `-- Semua Sekolah (Global) --`, lalu uji filter lokal (Sekolah, Tahun Ajaran, Komponen) di atas tabel "Daftar Tarif Default".
6. Verifikasi kolom `Sekolah` muncul pada tabel komponen dan tabel tarif default untuk Super Admin.
7. Coba tambah komponen baru saat mode global aktif, dan verifikasi pilihan Sekolah muncul dan berhasil disimpan.

---
## [Fitur Preview Penerbitan Tagihan & Manajemen Daftar Tagihan Siswa]
**Waktu**: 17:38 WIB
**Status**: Dieksekusi

# Rencana Implementasi: Fitur Preview Penerbitan Tagihan & Manajemen Daftar Tagihan Siswa

Menambahkan navigasi navtab di halaman Generate Tagihan untuk memisahkan fitur penerbitan masal (Generate) dengan manajemen daftar tagihan aktif. Menyediakan pratinjau daftar siswa dan nominal sebelum tagihan diterbitkan, serta manajemen daftar tagihan lengkap dengan pencarian, penyaringan, dan pengeditan nominal secara asinkronus (AJAX Fetch).

## User Review Required
> [!IMPORTANT]
> Fitur pencarian dan filter di "Daftar Tagihan Siswa" menggunakan server-side pagination agar performa tetap cepat dan responsif meskipun database memiliki puluhan ribu data tagihan siswa.
> Edit nominal tagihan diijinkan asalkan tagihan tersebut belum lunas atau nominal bayar lebih kecil dari nominal tagihan baru yang diset. Penghapusan tagihan ditolak secara aman jika tagihan sudah memiliki riwayat pembayaran (`nominal_bayar > 0`).

## Open Questions
Tidak ada.

## Proposed Changes

### 1. Backend Routes (`index.php`)
Menambahkan rute-rute endpoint API keuangan baru:
- `GET /api/v1/keuangan/preview-generate` -> Pratinjau target penerima tagihan berdasarkan form filter.
- `GET /api/v1/keuangan/daftar-tagihan` -> Mengambil data tagihan siswa terdaftar (paginated & filtered).
- `POST /api/v1/keuangan/edit-tagihan-nominal` -> Mengedit nominal tagihan aktif dan merekalibrasi status kelunasan.
- `DELETE /api/v1/keuangan/hapus-tagihan` -> Menghapus tagihan yang belum dibayar secara aman.

#### [MODIFY] [index.php](file:///C:/xampp/htdocs/SINTA-SaaS/index.php)

---

### 2. Backend Controller (`app/Controllers/SppController.php`)
- **`apiPreviewGenerate()`**: Menyaring siswa aktif berdasarkan kriteria filter, mencocokkan dengan prioritas tarif default, menghitung diskon keringanan/beasiswa personal, memeriksa duplikasi tagihan, dan mengembalikan data pratinjau.
- **`apiDaftarTagihan()`**: Menerima parameter query pencarian `q`, filter `kelas_id`, `tahun_ajaran_id`, `komponen_id`, `status_lunas`, `tenant_id`, serta parameter pagination `page` & `page_size`. Mengembalikan data tagihan dengan query teroptimasi.
- **`apiEditTagihanNominal()`**: Memperbarui kolom `nominal_tagihan` pada tagihan yang dipilih, menghitung ulang status lunas (Lunas/Cicil/Belum), serta mencatat perubahan di audit log keuangan.
- **`apiHapusTagihan()`**: Menghapus tagihan terpilih dari database jika `nominal_bayar` sama dengan 0. Menolak penghapusan dan melempar error jika tagihan sudah dicicil/dibayar.

#### [MODIFY] [SppController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/SppController.php)

---

### 3. Frontend View (`views/keuangan/generate.php`)
- **Nav Tabs Layout**: Menempatkan Bootstrap Nav Tabs di bagian atas halaman:
  1. **Generate Tagihan**: Layout 2 kolom (Kiri: Form input filter; Kanan: Tabel preview siswa sebelum generate lengkap dengan nominal asli, diskon, dan nominal bersih).
  2. **Daftar Tagihan Siswa**: Tabel pencarian, filter, edit nominal, dan pagination numerik dengan elipsis `...`.
- **Edit Nominal Modal**: Menyediakan modal Bootstrap `#editNominalModal` untuk mengubah nominal tagihan aktif.
- **Logika Vue.js**:
  - Watcher otomatis untuk menembak API pratinjau (`preview-generate`) saat form generate dilengkapi.
  - Implementasi pemanggilan API `apiDaftarTagihan()`, `apiEditTagihanNominal()`, dan `apiHapusTagihan()`.
  - Sinkronisasi visual untuk memelihara input filter.

#### [MODIFY] [generate.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/generate.php)

---

## Verification Plan

### Manual Verification
1. Akses halaman `/SINTA-SaaS/keuangan/generate`.
2. Verifikasi tab navigasi "Generate Tagihan" dan "Daftar Tagihan Siswa" tampil dengan baik.
3. Pada tab "Generate Tagihan", pilih Komponen Biaya & Tahun Ajaran. Verifikasi panel di sebelah kanan menampilkan tabel pratinjau daftar siswa calon penerima beserta perhitungan diskon dan nominal akhir secara otomatis.
4. Klik tombol "Terbitkan Tagihan Sekarang" dan pastikan tagihan berhasil terbit.
5. Pindah ke tab "Daftar Tagihan Siswa". Verifikasi list tagihan terbit muncul lengkap dengan pagination and filter.
6. Ketikkan nama siswa pada input pencarian, verifikasi pencarian secara server-side berjalan lancar.
7. Klik tombol "Edit" pada salah satu baris tagihan, ubah nominalnya, klik Simpan, dan pastikan nominal baru ter-update dengan benar.
8. Klik tombol "Hapus" pada tagihan kosong (belum dibayar), pastikan terhapus. Coba hapus tagihan yang sudah memiliki cicilan/pembayaran, dan pastikan ditolak dengan pesan yang ramah.

---
## [Penyaringan Tahun Ajaran Berdasarkan Tenant / Sekolah]
**Waktu**: 17:43 WIB
**Status**: Dieksekusi

# Rencana Implementasi: Penyaringan Tahun Ajaran Berdasarkan Tenant / Sekolah

Mengatur pemuatan Tahun Ajaran secara dinamis melalui endpoint API AJAX baru, disaring berdasarkan sekolah/tenant yang sedang terpilih secara global. Hal ini memastikan pilihan Tahun Ajaran pada halaman Master Keuangan dan Generate Tagihan terisolasi dengan benar per sekolah.

## User Review Required
> [!IMPORTANT]
> Tahun ajaran tidak lagi dimuat secara statis dari PHP controller page loader saat rendering halaman pertama kali. Seluruh pemuatan dialihkan melalui dynamic fetch AJAX (`/api/v1/keuangan/tahun-ajaran`) saat tenant berubah, sesuai dengan arsitektur Zero Data Leakage.

## Open Questions
Tidak ada.

## Proposed Changes

### 1. Backend Routes (`index.php`)
Menambahkan rute endpoint API baru:
- `GET /api/v1/keuangan/tahun-ajaran` -> Mengambil daftar tahun ajaran milik tenant aktif (atau unik global untuk super_admin).

#### [MODIFY] [index.php](file:///C:/xampp/htdocs/SINTA-SaaS/index.php)

---

### 2. Backend Controller (`app/Controllers/SppController.php`)
- **`apiTahunAjaran()`**: Mengambil data `tahun_ajaran` dari database. Jika pengguna adalah `super_admin` dalam mode global (tanpa param `tenant_id`), list tahun ajaran dikelompokkan secara unik menggunakan `GROUP BY tahun_ajaran`. Jika tenant tertentu dipilih, query disaring berdasarkan `tenant_id` sekolah tersebut.

#### [MODIFY] [SppController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/SppController.php)

---

### 3. Frontend Views (`views/keuangan/master.php` & `views/keuangan/generate.php`)
- Mengubah deklarasi variabel `listTa` dari *static injection* ke dynamic ref `Vue.ref([])`.
- Menambahkan method pembantu `fetchTahunAjaran()` untuk mengambil data tahun ajaran dari API baru.
- Memanggil `fetchTahunAjaran()` saat halaman di-mount dan saat tenant berubah (`onTenantChange()`).

#### [MODIFY] [master.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/master.php)
#### [MODIFY] [generate.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/generate.php)

---

## Verification Plan

### Manual Verification
1. Masuk sebagai `super_admin` dan akses halaman `/SINTA-SaaS/keuangan/generate`.
2. Pilih salah satu tenant (misal: "SMA Negeri 1 Jakarta"). Verifikasi opsi dropdown "Tahun Ajaran" disaring dan hanya menampilkan tahun ajaran milik SMA Negeri 1 Jakarta.
3. Ubah sekolah di dropdown global, pastikan pilihan Tahun Ajaran berubah menyesuaikan sekolah baru.
4. Akses halaman `/SINTA-SaaS/keuangan/master`, ganti tenant sekolah, dan pastikan dropdown Tahun Ajaran di form penautan tarif baru ter-update dinamis.

---
## [Perbaikan Query Lookup Jenjang Kelas pada Penerbitan Tagihan]
**Waktu**: 17:54 WIB
**Status**: Dieksekusi

# Rencana Implementasi: Perbaikan Query Lookup Jenjang Kelas pada Penerbitan Tagihan

Memperbaiki kesalahan referensi kolom `jenjang_id` menjadi `id_jenjang` pada tabel `kelas` di kueri database pada method `apiGenerateTagihan` dan `apiPreviewGenerate`. Ini akan memperbaiki masalah daftar nama siswa yang tidak muncul (kosong) saat penerbitan tagihan.

## User Review Required
> [!IMPORTANT]
> Kolom jenjang pada tabel `kelas` didefinisikan sebagai `id_jenjang` sesuai skema database DAPODIK, sedangkan di tabel `transaksi_spp_tarif` tetap dinamakan `jenjang_id`. Query pencocokan tarif default berdasarkan jenjang perlu diselaraskan agar tidak memicu error database.

## Open Questions
Tidak ada.

## Proposed Changes

### 1. Backend Controller (`app/Controllers/SppController.php`)
- Memperbarui query lookup jenjang di `apiGenerateTagihan()` (baris 511) dari `jenjang_id = ?` ke `id_jenjang = ?`.
- Memperbarui query lookup jenjang di `apiPreviewGenerate()` (baris 667) dari `jenjang_id = ?` ke `id_jenjang = ?`.
- Memperbarui filter target jenjang pada query siswa aktif di `apiPreviewGenerate()` (baris 627) dari `jenjang_id = ?` ke `id_jenjang = ?`.

#### [MODIFY] [SppController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/SppController.php)

---

## Verification Plan

### Manual Verification
1. Akses halaman `/SINTA-SaaS/keuangan/generate`.
2. Pilih tenant "SMA Negeri 1 Jakarta", Komponen "Formulir Pendaftaran PPDB", dan Tahun Ajaran "2026/2027".
3. Pilih Target Distribusi "Semua Kelas".
4. Verifikasi bahwa daftar nama siswa sasaran kini muncul secara otomatis pada panel pratinjau di sebelah kanan (tidak lagi kosong).

---
## [Penyaringan Kelas dan Jenjang Berdasarkan Tenant / Sekolah]
**Waktu**: 17:59 WIB
**Status**: Dieksekusi

# Rencana Implementasi: Penyaringan Kelas dan Jenjang Berdasarkan Tenant / Sekolah

Mengatur pemuatan Kelas dan Jenjang secara dinamis melalui endpoint API AJAX baru, disaring berdasarkan sekolah/tenant terpilih secara global. Ini membatasi pilihan Kelas dan Jenjang sasaran pada antarmuka Master Keuangan dan Generate Tagihan agar terisolasi per sekolah.

## User Review Required
> [!IMPORTANT]
> Opsi Kelas dan Jenjang tidak lagi dimuat secara statis dari PHP controller page loader saat rendering halaman pertama kali. Seluruh pemuatan dialihkan melalui dynamic fetch AJAX (`/api/v1/keuangan/kelas` dan `/api/v1/keuangan/jenjang`) saat tenant berubah, sesuai dengan arsitektur Zero Data Leakage.

## Open Questions
Tidak ada.

## Proposed Changes

### 1. Backend Routes (`index.php`)
Menambahkan rute-rute endpoint API baru:
- `GET /api/v1/keuangan/kelas` -> Mengambil daftar kelas milik tenant aktif (atau semua kelas untuk super_admin global).
- `GET /api/v1/keuangan/jenjang` -> Mengambil daftar jenjang milik tenant aktif (atau semua jenjang untuk super_admin global).

#### [MODIFY] [index.php](file:///C:/xampp/htdocs/SINTA-SaaS/index.php)

---

### 2. Backend Controller (`app/Controllers/SppController.php`)
- **`apiKelas()`**: Mengambil data `kelas` dari database disaring berdasarkan `tenant_id` aktif.
- **`apiJenjang()`**: Mengambil data `jenjang` dari database disaring berdasarkan `tenant_id` aktif.

#### [MODIFY] [SppController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/SppController.php)

---

### 3. Frontend Views (`views/keuangan/master.php` & `views/keuangan/generate.php`)
- Mengubah deklarasi variabel `listKelas` dan `listJenjang` ke dynamic ref `Vue.ref([])`.
- Menambahkan method pembantu `fetchKelas()` and `fetchJenjang()`.
- Memanggil pembaruan `fetchKelas()` dan `fetchJenjang()` saat halaman di-mount dan saat tenant berubah (`onTenantChange()`).

#### [MODIFY] [master.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/master.php)
#### [MODIFY] [generate.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/generate.php)

---

## Verification Plan

### Manual Verification
1. Akses halaman `/SINTA-SaaS/keuangan/generate`.
2. Pilih tenant "SMA Negeri 1 Jakarta".
3. Pilih Target Distribusi "Per Jenjang".
4. Klik dropdown "Pilih Jenjang Sasaran" dan pastikan hanya jenjang milik SMA Negeri 1 Jakarta yang tampil (seperti: "Sekolah Menengah Atas"), sementara jenjang milik SMK ("Sekolah Menengah Kejuruan") atau sekolah lain disembunyikan.
5. Lakukan hal yang sama untuk dropdown "Pilih Kelas Sasaran" (Target Distribusi: Per Kelas), pastikan hanya kelas milik SMA Negeri 1 Jakarta yang terdaftar.

---
## [Pemilihan Siswa Sasaran (Checklist) Sebelum Menerbitkan Tagihan]
**Waktu**: 18:16 WIB
**Status**: Dieksekusi

# Rencana Implementasi: Pemilihan Siswa Sasaran (Checklist) Sebelum Menerbitkan Tagihan

Menambahkan fitur pemilihan siswa sasaran menggunakan checkbox pada panel pratinjau di halaman Generate Tagihan. Pengguna dapat memilih semua siswa secara cepat (select all) atau mencentang beberapa siswa saja secara mandiri sebelum menekan tombol "Terbitkan Tagihan".

## User Review Required
> [!IMPORTANT]
> Secara default, seluruh siswa yang belum mendapatkan tagihan pada tahun/komponen terpilih akan otomatis tercentang (checked) agar mempercepat proses penerbitan jika memang ditujukan ke semua siswa. Pengguna dapat menghapus centang untuk mengecualikan siswa tertentu dari daftar penerbitan tagihan.

## Open Questions
Tidak ada.

## Proposed Changes

### 1. Backend Controller (`app/Controllers/SppController.php`)
- **`apiGenerateTagihan()`**: Menerima parameter array `siswa_ids` dalam body POST. Jika array ini diisi (tidak kosong), query pencarian siswa aktif disaring menggunakan `AND s.id IN (...)` dengan parameter placeholder/prepared statement yang dinamis guna membatasi penerbitan tagihan hanya untuk siswa-siswa terpilih.

#### [MODIFY] [SppController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/SppController.php)

---

### 2. Frontend View (`views/keuangan/generate.php`)
- **Tabel Preview**: Menambahkan kolom checkbox di paling kiri tabel pratinjau.
  - Header: Checkbox untuk "Pilih Semua" (Select All).
  - Row: Checkbox individual per siswa (dinonaktifkan jika tagihan `sudah_ada` bernilai true).
- **Logika Vue.js**:
  - State baru: `selectedSiswaIds` (array referensi siswa terpilih).
  - Computed property `isAllSelected` dan helper `toggleSelectAll()` untuk mengelola status checkbox induk.
  - Watcher pada `previewList` untuk mencentang seluruh siswa sasaran yang eligible secara default saat pratinjau dimuat.
  - Menyesuaikan payload AJAX POST pada tombol terbitkan tagihan agar mengirimkan parameter `siswa_ids`.

#### [MODIFY] [generate.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/generate.php)

---

## Verification Plan

### Manual Verification
1. Akses halaman `/SINTA-SaaS/keuangan/generate`.
2. Pilih tenant "SMA Negeri 1 Jakarta", Komponen "Formulir Pendaftaran PPDB", dan Tahun Ajaran "2026/2027".
3. Pada panel pratinjau kanan, verifikasi seluruh siswa calon penerima otomatis tercentang.
4. Hilangkan centang pada 1-2 siswa, pastikan label tombol terbitkan berubah menjadi `Terbitkan Tagihan (X Siswa)` (jumlah berkurang).
5. Klik tombol header checkbox untuk menghapus semua centangan siswa. Centang secara manual beberapa siswa saja.
6. Klik tombol "Terbitkan Tagihan" dan verifikasi tagihan hanya terbit untuk siswa-siswa yang tercentang.

---
## [Optimalisasi Kueri & Indeks Database Tagihan SPP Massal]
**Waktu**: 18:25 WIB
**Status**: Dieksekusi

# Rencana Implementasi: Optimalisasi Kueri & Indeks Database Tagihan SPP Massal

Memperbaiki bug perbedaan data pada hitungan pagination (COUNT vs SELECT) serta menambahkan indeks performa pada database untuk mempercepat operasi sort dan load pada saat mengelola jutaan baris data tagihan SPP.

## User Review Required
> [!IMPORTANT]
> Mengubah `INNER JOIN` menjadi `LEFT JOIN` pada tabel lookup `kelas`, `komponen`, `tahun_ajaran`, dan `tenants` di kueri data terpaginasi. Ini menjamin data yang dihitung di fungsi COUNT selalu memiliki jumlah baris yang sama persis dengan yang ditampilkan di fungsi SELECT (meskipun ada kelas/jenjang yang kosong/terhapus).
> Menambahkan indeks B-TREE baru pada database untuk kolom `created_at`, `(tenant_id, created_at)`, dan `status_lunas`.

## Open Questions
Tidak ada.

## Proposed Changes

### 1. Database Migration (`database/migrations/2026_07_21_02_add_indexes_to_spp_tagihan.php`)
- **`up()`**: Menambahkan indeks performa pencarian `idx_spp_tagihan_created`, `idx_spp_tagihan_tenant_created`, dan `idx_spp_tagihan_status` pada tabel `transaksi_spp_tagihan`.
- **`down()`**: Menghapus indeks performa tersebut saat rollback.

#### [NEW] [2026_07_21_02_add_indexes_to_spp_tagihan.php](file:///C:/xampp/htdocs/SINTA-SaaS/database/migrations/2026_07_21_02_add_indexes_to_spp_tagihan.php)

---

### 2. Backend Controller (`app/Controllers/SppController.php`)
- **`apiDaftarTagihan()`**:
  - Mengubah `INNER JOIN` ke `LEFT JOIN` pada query select.
  - Mengoptimasi klausa pengurutan dari `ORDER BY t.created_at DESC, s.nama_lengkap ASC` menjadi `ORDER BY t.created_at DESC` agar MySQL dapat menggunakan composite index secara langsung (menghindari filesort).

#### [MODIFY] [SppController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/SppController.php)

---

## Verification Plan

### Manual Verification
1. Akses tab "Daftar Tagihan Siswa".
2. Navigasi ke halaman-halaman akhir (misal halaman 4175).
3. Pastikan data tagihan tetap muncul konsisten di tabel sesuai dengan jumlah pagination-nya.
4. Rasakan perbedaan kecepatan pemuatan halaman yang jauh lebih instan.

---
## [Ekspor Excel Dinamis (Pivot SPP) & Filter Mandatori Halaman Daftar Tagihan]
**Waktu**: 18:36 WIB
**Status**: Dieksekusi

# Rencana Implementasi: Ekspor Excel Dinamis (Pivot SPP) & Filter Mandatori Halaman Daftar Tagihan

Mengubah fungsionalitas pencarian halaman Daftar Tagihan agar mewajibkan filter (tidak me-load data di awal secara langsung) guna meringankan beban server pada database dengan jutaan data. Serta menambahkan menu download Excel dalam format pivot dinamis (cross-tab) menggunakan pustaka `SimpleXLSXGen`.

## User Review Required
> [!IMPORTANT]
> - **Filter Mandatori**: Saat pertama kali membuka tab "Daftar Tagihan Siswa", tabel akan kosong dan menampilkan pesan petunjuk untuk memilih filter terlebih dahulu.
> - **Ekspor Excel Pivot Dinamis**: Struktur Excel dibuat cross-tab dengan sumbu baris: `nama`, `nisn`, `kelas`, dan sumbu kolom dikelompokkan bertingkat: **Tahun Ajaran > Komponen Biaya > [nominal, telah di bayar, Kurang bayar, status]**.

## Open Questions
Tidak ada.

## Proposed Changes

### 1. Backend Central Router (`index.php`)
Mendaftarkan rute baru:
- `GET /api/v1/keuangan/export-tagihan-excel` -> Menjalankan method ekspor Excel pivot SPP di controller.

#### [MODIFY] [index.php](file:///C:/xampp/htdocs/SINTA-SaaS/index.php)

---

### 2. Backend Controller (`app/Controllers/SppController.php`)
- **`apiExportTagihanExcel()`**:
  - Mengambil parameter query (`tenant_id`, `q`, `kelas_id`, `tahun_ajaran_id`, `komponen_id`, `status_lunas`).
  - Memvalidasi bahwa minimal satu filter (di luar tenant) telah diset. Jika tidak ada, kembalikan pesan galat.
  - Menarik data tagihanSPP aktif yang sesuai dari database.
  - Memetakan data secara dinamis ke bentuk baris/kolom Pivot (cross-tab).
  - Memanfaatkan pustaka `SimpleXLSXGen` untuk merancang sheet Excel lengkap dengan merge cell (A1:A3 untuk nama, dst.), background warna cell, dan memicu download otomatis ke browser.

#### [MODIFY] [SppController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/SppController.php)

---

### 3. Frontend View (`views/keuangan/generate.php`)
- **Computed Property `hasFilterApplied`**: Mengidentifikasi apakah ada filter aktif selain tenant.
- **`fetchDaftarTagihan()`**: Mencegah request AJAX ke server jika `hasFilterApplied` bernilai false.
- **Pembersihan onMounted**: Menghapus pemanggilan otomatis `fetchDaftarTagihan()` saat mounted pertama kali.
- **UI Grid Filter**:
  - Mengubah layout grid filter agar muat ditambahkan tombol "Excel" (.xlsx) berwarna hijau.
  - Menonaktifkan tombol "Excel" jika belum ada filter yang diterapkan.
  - Menambahkan method `downloadExcel()`.
- **Placeholder Filter Mandatori**:
  - Menampilkan layout pemberitahuan yang menawan saat belum ada filter yang diisi.

#### [MODIFY] [generate.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/generate.php)

---

## Verification Plan

### Manual Verification
1. Akses halaman `/SINTA-SaaS/keuangan/generate` dan klik tab **Daftar Tagihan Siswa**.
2. Verifikasi tabel kosong dan menampilkan ikon filter bertuliskan *"Silakan tentukan minimal satu filter di atas terlebih dahulu..."*.
3. Verifikasi tombol **Excel** berwarna hijau dalam posisi nonaktif (`disabled`).
4. Pilih salah satu filter (misal Kelas atau Komponen Biaya). Pastikan kueri terpanggil otomatis dan menampilkan daftar tagihan terbit.
5. Verifikasi tombol **Excel** kini aktif. Klik tombol **Excel** tersebut.
6. Periksa file `.xlsx` yang terunduh dan pastikan strukturnya berupa pivot bertingkat sesuai dengan mockup (Tahun Ajaran > Komponen > [nominal, telah di bayar, Kurang bayar, status] dengan merge cell vertikal pada kolom nama, nisn, kelas).

---
## [Filter Kelas, Riwayat Kelas, & Status Tagihan Loket Kasir]
**Waktu**: 18:48 WIB
**Status**: Dieksekusi

# Rencana Implementasi: Filter Kelas, Riwayat Kelas, & Status Tagihan Loket Kasir

Memperbaiki Loket Kasir Pembayaran untuk mendukung pencarian siswa menggunakan filter kelas tambahan (mengantisipasi nama ganda), menampilkan riwayat kelas siswa pada baris tagihan lama sesuai dengan tahun ajaran terkait, serta menampilkan kolom **Status Tagihan** (Belum Lunas, Cicil) sebelum input jumlah pembayaran.

## User Review Required
> [!IMPORTANT]
> - **Filter Kelas di Kasir**: Pada kolom pencarian siswa loket pembayaran kasir, ditambahkan dropdown kelas baru. Data opsi kelas ini dimuat secara dinamis mengikuti tenant sekolah terpilih.
> - **Kelas Lama Tagihan**: Pada tabel Daftar Kewajiban Pembayaran kasir, ditambahkan kolom "Kelas" tepat setelah Tahun Ajaran yang menampilkan kelas siswa pada periode tagihan tersebut dibuat (diambil secara dinamis dari riwayat kenaikan kelas atau fallback ke kelas aktif).
> - **Kolom Status (Rekomendasi Terbaik)**: Ditambahkan kolom "Status" tepat sebelum kolom input "Bayar (Rp)" yang menyajikan status lunas berjalan dalam bentuk badge berwarna (merah untuk Belum Lunas, kuning untuk Cicil) agar kasir mengetahui riwayat pembayaran tagihan tersebut sebelumnya.

## Open Questions
Tidak ada.

## Proposed Changes

### 1. Backend Controller (`app/Controllers/SppController.php`)
- **`apiCariSiswa()`**:
  - Menerima parameter `kelas_id` opsional.
  - Jika `kelas_id` diisi, filter pencarian siswa ditambahkan `AND s.id_kelas = ?` untuk mempersempit daftar saran pencarian.
- **`apiGetTagihanSiswa()`**:
  - Merefaktor query select agar mengambil nama kelas historis siswa (`nama_kelas_history`) menggunakan subquery `COALESCE` yang memeriksa tabel `riwayat_kenaikan_kelas` untuk `tahun_ajaran` tagihan, atau fallback ke kelas siswa saat ini.

#### [MODIFY] [SppController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/SppController.php)

---

### 2. Frontend View (`views/keuangan/kasir.php`)
- **Pencarian & Filter Kelas**:
  - Menata ulang grid pencarian siswa: menyisipkan dropdown **Filter Kelas** sebelum baris pencarian nama/NISN.
  - Menambahkan ref `listKelas` dan `selectedKelasId`.
  - Membuat method `fetchKelas()` untuk memuat kelas aktif sekolah secara dinamis.
  - Memanggil `fetchKelas()` saat mounted dan saat `onTenantChange()` dipicu.
  - Mengirim parameter `kelas_id` ke API `/api/v1/keuangan/cari-siswa`.
- **Tabel Daftar Kewajiban**:
  - Menyisipkan kolom header `Kelas` setelah Tahun Ajaran.
  - Menampilkan variabel `t.nama_kelas_history` di dalam badge agar tampak premium.
  - Menyisipkan kolom header `Status` sebelum `Bayar (Rp)`.
  - Menampilkan `t.status_lunas` dalam badge berwarna menggunakan fungsi `getStatusBadgeClass()`.

#### [MODIFY] [kasir.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/kasir.php)

---

## Verification Plan

### Manual Verification
1. Akses halaman `/SINTA-SaaS/keuangan/kasir`.
2. Pilih tenant "SMA Negeri 1 Jakarta".
3. Verifikasi dropdown **Filter Kelas** terisi daftar kelas SMA Negeri 1 Jakarta secara dinamis.
4. Pilih kelas tertentu (misal "Kelas X IPS 1") dan cari nama siswa (misal "foni"). Pastikan daftar yang muncul tersaring tepat hanya untuk kelas tersebut.
5. Klik nama siswa tersebut untuk memuat kewajibannya.
6. Periksa tabel **Daftar Kewajiban Pembayaran**, pastikan ada kolom **Kelas** di sebelah kanan Tahun Ajaran yang menampilkan kelas historis siswa pada tahun ajaran tersebut (misal "Kelas X IPS 1").
7. Periksa kolom **Status** di sebelah kanan Sisa Kekurangan, pastikan menampilkan status cicilan berjalan (misal "Cicil" atau "Belum Lunas").

---
## [Filter Cascading, Daftar Siswa Dinamis, Riwayat Kelas Akurat, & Grouping Tagihan Kasir]
**Waktu**: 19:03 WIB
**Status**: Dieksekusi

# Rencana Implementasi: Filter Cascading, Daftar Siswa Dinamis, Riwayat Kelas Akurat per Tahun Ajaran, & Pengelompokan Tagihan Kasir

Memperbarui antarmuka Loket Kasir Pembayaran secara komprehensif dengan menyematkan **Filter Jenjang** bertingkat (cascading) ke **Filter Kelas**, menampilkan seluruh daftar siswa dalam kelas terpilih secara interaktif, menyajikan **riwayat kelas historis yang akurat sesuai tahun ajaran tagihan**, serta merestrukturisasi tabel **Daftar Kewajiban Pembayaran** (grouping per Tahun Ajaran & Komponen, urutan bulan Januari-Desember, kunci tagihan Lunas, dan penyesuaian lebar kolom agar tidak terpotong).

## User Review Required
> [!IMPORTANT]
> - **Akurasi Riwayat Kelas per Tahun Ajaran**:
>   - Kelas yang tampil pada setiap baris tagihan **bukan sekadar kelas siswa saat ini**, melainkan **kelas riwayat siswa pada tahun ajaran tagihan tersebut terbit**.
>   - Sistem akan memeriksa tabel `riwayat_kenaikan_kelas`:
>     1. Mengambil `nama_kelas_tujuan` pada record kenaikan/penempatan di tahun ajaran tersebut.
>     2. Jika tagihan berasal dari tahun sebelumnya, mengambil `nama_kelas_asal` dari record kenaikan kelas setelahnya.
>     3. Fallback ke kelas aktif siswa jika tidak terdapat histori promo.
> - **Filter Jenjang & Kelas Cascading**:
>   - Ditambahkan dropdown **Filter Jenjang** di loket kasir.
>   - Ketika Jenjang dipilih, opsi pada dropdown **Filter Kelas** otomatis tersaring sesuai Jenjang tersebut.
>   - Ketika Kelas dipilih, sistem akan menampilkan **seluruh daftar siswa** dalam kelas tersebut tanpa kasir harus mengetik nama/NISN terlebih dahulu.
> - **Interaksi Klik Nama Siswa**:
>   - Kasir cukup mengklik salah satu nama siswa dari daftar/saran untuk memuat dan menampilkan **Daftar Kewajiban Pembayaran** siswa tersebut.
> - **Pengelompokan (Grouping) & Urutan Bulan**:
>   - Tagihan dikelompokkan secara bertingkat berdasarkan **Tahun Ajaran** -> **Komponen Tagihan**.
>   - Untuk komponen bulanan, item diurutkan kronologis dari **Januari (1) hingga Desember (12)**.
> - **Proteksi Tagihan Lunas & Responsivitas Tabel**:
>   - Tagihan berstatus `Lunas` tetap ditampilkan sebagai informasi riwayat, namun checkbox dan input nominalnya dikunci (`disabled`).
>   - Tabel dilengkapi dengan `text-nowrap` dan `min-width: 950px` (dengan scroll horizontal jika layar sempit) agar kolom `Bayar (Rp)` dan `Status` tampil utuh.

## Open Questions
Tidak ada.

## Proposed Changes

### 1. Backend Controller (`app/Controllers/SppController.php`)
- **`apiKelas()`**:
  - Menerima parameter `jenjang_id` opsional untuk menyaring kelas berdasarkan `id_jenjang`.
- **`apiCariSiswa()`**:
  - Menerima `jenjang_id` dan `kelas_id`.
  - Jika `kelas_id` atau `jenjang_id` ditentukan tetapi query pencarian `q` kosong, tetap kembalikan seluruh daftar siswa aktif dalam kelas/jenjang tersebut (hingga 50 siswa) diurutkan berdasarkan `nama_lengkap ASC`.
- **`apiGetTagihanSiswa()`**:
  - Menghapus filter `AND t.status_lunas != 'Lunas'` agar seluruh riwayat tagihan (Belum, Cicil, Lunas) dapat dimuat.
  - Memperbarui pencarian `nama_kelas_history` dengan logika resolusi 2-lapis (pemeriksaan `nama_kelas_tujuan` pada tahun ajaran tagihan, atau `nama_kelas_asal` pada record kenaikan kelas tahun berikutnya) agar kelas lama di tahun sebelumnya tampil tepat (misal 2025/2026 menampilkan Kelas X, sedangkan 2026/2027 menampilkan Kelas XI).
  - Mengurutkan data SQL awal secara kronologis.

#### [MODIFY] [SppController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/SppController.php)

---

### 2. Frontend View (`views/keuangan/kasir.php`)
- **Grid Filter Jenjang & Kelas**:
  - Menambahkan dropdown **Filter Jenjang** (`listJenjang` & `selectedJenjangId`).
  - Menghubungkan perubahan Jenjang dengan pemanggilan `fetchKelas()` cascading.
  - Menambahkan watcher/event handler pada perubahan Kelas untuk memanggil `searchSiswa()` secara otomatis sehingga seluruh siswa di kelas tersebut langsung muncul dalam opsi pilihan.
- **Pengelompokan (Grouping) & Urutan Bulan pada Tabel Tagihan**:
  - Membuat computed property `groupedTagihan` yang mengelompokkan `tagihanList` menurut `tahun_ajaran` -> `nama_komponen`, dan mengurutkan item bulanan dari bulan 1 (Januari) hingga 12 (Desember).
  - Menyusun baris header kelompok (Group Header Rows) untuk Tahun Ajaran dan Komponen Tagihan.
- **Proteksi Status & Layout Responsif**:
  - Memasang `:disabled="t.status_lunas === 'Lunas' || (t.nominal_tagihan - t.nominal_bayar) <= 0"` pada checkbox dan input bayar.
  - Menerapkan `text-nowrap`, `min-width: 950px`, dan `min-width: 120px` pada kolom input bayar.

#### [MODIFY] [kasir.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/kasir.php)

---

## Verification Plan

### Manual Verification
1. Akses halaman `/SINTA-SaaS/keuangan/kasir`.
2. Pilih tenant "SMA Negeri 1 Jakarta".
3. Pilih **Jenjang** (misal SMA). Pastikan opsi **Kelas** otomatis tersaring hanya untuk kelas SMA.
4. Pilih **Kelas** (misal "Kelas X IPS 1"). Verifikasi bahwa seluruh daftar siswa di kelas tersebut langsung muncul secara otomatis.
5. Klik salah satu nama siswa. Verifikasi bahwa **Daftar Kewajiban Pembayaran** muncul dengan pengelompokan Tahun Ajaran -> Komponen Tagihan.
6. Periksa kolom **Kelas** pada tiap baris tagihan: pastikan tagihan tahun ajaran 2025/2026 menampilkan kelas riwayat lama siswa pada tahun 2025/2026 (misal Kelas X IPS 1), sedangkan tagihan tahun ajaran 2026/2027 menampilkan kelas baru (misal Kelas XI IPS 1).
7. Periksa komponen bulanan, pastikan urutan bulan berjalan dari Januari sampai Desember.
8. Periksa tagihan berstatus `Lunas`, pastikan badge berwarna hijau dan input/checkbox dalam posisi terkunci (`disabled`).
9. Periksa kolom `Bayar (Rp)` dan `Status`, pastikan tidak ada teks/kolom yang terpotong.

---
## [Fitur Cetak Ulang Kuitansi Pembayaran Tagihan Lunas/Cicil di Loket Kasir]
**Waktu**: 19:14 WIB
**Status**: Dieksekusi

# Rencana Implementasi: Fitur Cetak Ulang Kuitansi Bukti Pembayaran Lunas/Cicil

Menambahkan kemampuan cetak ulang kuitansi pembayaran resmi pada halaman Loket Kasir Pembayaran untuk setiap tagihan yang telah memiliki riwayat pembayaran (status Lunas maupun Cicil).

## User Review Required
> [!IMPORTANT]
> - **Tombol Cetak Ulang Kuitansi**: Pada setiap baris tagihan yang sudah memiliki pembayaran (`nominal_bayar > 0`), ditambahkan tombol **"Cetak Ulang"** berikon printer di samping badge status.
> - **Integritas Data Kuitansi**: Menarik `nomor_kwitansi` terakhir, tanggal transaksi, serta metode pembayaran langsung dari tabel `transaksi_spp_pembayaran` untuk mengisi pratinjau kuitansi secara akurat saat tombol diklik.

## Open Questions
Tidak ada.

## Proposed Changes

### 1. Backend Controller (`app/Controllers/SppController.php`)
- **`apiGetTagihanSiswa()`**:
  - Menyisipkan subquery pada SQL select untuk mengambil detail transaksi pembayaran terbaru per tagihan: `latest_nomor_kwitansi`, `latest_tgl_bayar`, dan `latest_metode`.

#### [MODIFY] [SppController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/SppController.php)

---

### 2. Frontend View (`views/keuangan/kasir.php`)
- **Tombol & Method `reprintKwitansi(t)`**:
  - Menyajikan tombol **"Cetak Ulang"** (`btn-outline-primary`) pada kolom Status jika `t.nominal_bayar > 0`.
  - Menambahkan fungsi `reprintKwitansi(t)` yang mengisi objek `printData` dengan informasi pembayaran historis tagihan tersebut, lalu memicu pembukaan modal pratinjau `#modalKwitansi`.

#### [MODIFY] [kasir.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/kasir.php)

---

## Verification Plan

### Manual Verification
1. Akses halaman `/SINTA-SaaS/keuangan/kasir`.
2. Cari dan pilih siswa yang telah memiliki tagihan berstatus `Lunas` atau `Cicil`.
3. Periksa kolom **Status**: verifikasi adanya tombol **"Cetak Ulang"** berikon printer di samping badge status.
4. Klik tombol **"Cetak Ulang"**.
5. Verifikasi bahwa modal pratinjau kuitansi terbuka dengan menampilkan Nomor Kuitansi asli, Tanggal Transaksi, Nama Siswa, Kelas Historis, dan Nominal yang telah dibayar.
6. Klik tombol **Cetak Sekarang** untuk memicu jendela pencetakan kuitansi.

---
## [Fitur Pembatalan (Void) Transaksi Pembayaran Kasir]
**Waktu**: 19:40 WIB
**Status**: Disetujui / Dieksekusi

# Rencana Implementasi: Fitur Pembatalan (Void) Transaksi Pembayaran Kasir

Menyediakan opsi pembatalan (*void / reversal*) transaksi pembayaran resmi di halaman Loket Kasir untuk memulihkan sisa kekurangan tagihan siswa jika admin/kasir salah mengklik pembayaran.

## User Review Required
> [!IMPORTANT]
> - **Mekanisme Reversal Saldo Tagihan**:
>   - Ketika pembayaran dibatalkan, `nominal_dibayar` pada transaksi pembayaran terkait akan dikurangkan dari `transaksi_spp_tagihan.nominal_bayar`.
>   - Status pelunasan tagihan (`status_lunas`) akan dihitung ulang secara otomatis (`Belum` jika `nominal_bayar = 0`, atau `Cicil` jika `nominal_bayar < nominal_tagihan`).
> - **Modal Konfirmasi & Alasan Pembatalan**:
>   - Kasir diwajibkan memasukkan **Alasan Pembatalan** (misal: *"Salah klik nominal"* / *"Salah pilih metode"*) sebagai syarat pembatalan transaksi.
> - **Audit Trail & Keamanan**:
>   - Seluruh aktivitas pembatalan dicatat secara permanen di `transaksi_spp_audit_log` dengan rincian identitas kasir, waktu pembatalan, data sebelum & sesudah, serta alasan pembatalan.

## Open Questions
Tidak ada.

## Proposed Changes

### 1. Routing System (`index.php`)
- Mendaftarkan rute API baru:
  - `POST /api/v1/keuangan/batal-pembayaran` -> `SppController::apiBatalPembayaran()`

#### [MODIFY] [index.php](file:///C:/xampp/htdocs/SINTA-SaaS/index.php)

---

### 2. Backend Controller (`app/Controllers/SppController.php`)
- Menambahkan method `apiBatalPembayaran()`:
  - Memverifikasi `tagihan_id` dan `alasan_batal`.
  - Mengambil data pembayaran terbaru dari `transaksi_spp_pembayaran`.
  - Mengunci baris tagihan dengan `FOR UPDATE` di dalam transaksi database.
  - Membalikkan nilai `nominal_bayar` dan memperbarui `status_lunas` tagihan.
  - Menghapus record pembayaran terkait dan menyuntikkan log audit `VOID_PAYMENT` ke `transaksi_spp_audit_log`.

#### [MODIFY] [SppController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/SppController.php)

---

### 3. Frontend View (`views/keuangan/kasir.php`)
- **Tombol "Batal" pada Baris Tagihan**:
  - Pada baris tagihan yang memiliki `nominal_bayar > 0`, ditambahkan tombol **"Batal"** (`btn-outline-danger`) di samping tombol **"Cetak Ulang"**.
- **Modal Konfirmasi Pembatalan**:
  - Menambahkan modal dialog `#modalBatalPembayaran` yang menampilkan detail kwitansi yang akan dibatalkan beserta input teks wajib untuk **Alasan Pembatalan**.
  - Mengaitkan method `confirmBatalPembayaran(t)` dan `submitBatalPembayaran()` pada Vue setup untuk mengeksekusi pembatalan dan merefresh daftar tagihan secara otomatis.

#### [MODIFY] [kasir.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/kasir.php)

---

## Verification Plan

### Manual Verification
1. Akses halaman `/SINTA-SaaS/keuangan/kasir`.
2. Cari dan pilih siswa yang telah memiliki tagihan berstatus `Lunas` atau `Cicil`.
3. Periksa kolom **Status**: verifikasi adanya tombol **"Batal"** berwarna merah di samping tombol "Cetak Ulang".
4. Klik tombol **"Batal"**.
5. Verifikasi bahwa modal **"Konfirmasi Pembatalan Pembayaran"** muncul dengan rincian nomor kuitansi dan input alasan pembatalan.
6. Masukkan alasan pembatalan (misal: *"Salah input nominal"*) dan klik tombol **"Ya, Batalkan Transaksi"**.
7. Verifikasi bahwa tagihan kembali berstatus `Belum Lunas` / `Cicil`, sisa tagihan bertambah sesuai nominal yang dibatalkan, dan notifikasi sukses muncul di layar.

---
## [Audit Trail & Keamanan Aktivitas Pengguna/Petugas (Khusus Super Admin)]
**Waktu**: 19:46 WIB
**Status**: Disetujui / Dieksekusi

# Rencana Implementasi: Audit Trail & Keamanan Aktivitas Pengguna/Petugas (Khusus Super Admin)

Membangun modul pencatatan jejak audit (*Audit Trail & Security System*) yang komprehensif untuk merekam seluruh aktivitas petugas/pengguna (pembayaran, pembatalan/void, pembuatan/penghapusan tagihan, edit tarif/komponen, dsb.) dengan hak akses eksklusif hanya untuk **Super Admin**.

## User Review Required
> [!IMPORTANT]
> - **Proteksi Akses Eksklusif Super Admin**:
>   - Halaman `/keuangan/audit-log` dan API `/api/v1/keuangan/audit-log` diproteksi secara ketat. Jika diakses oleh selain `super_admin` (seperti Admin Sekolah, Operator, Kasir, atau Siswa), sistem akan menolak request dengan HTTP 403 Forbidden.
> - **Migrasi Menu Khusus Super Admin**:
>   - Dibuat file migrasi `2026_07_21_03_add_audit_log_menu.php` dengan format `return [...]` array untuk mendaftarkan menu `Audit Trail & Log Security` di bawah parent menu `Keuangan & Pembayaran` hanya untuk role `super_admin` (Role ID 1).
> - **Fitur Visual JSON Diff Modal**:
>   - Menyediakan modal detail pratinjau perubahan data (*Data Sebelum* vs *Data Sesudah*) dalam format JSON yang bersih dan mudah dibaca untuk audit investigasi.

## Open Questions
Tidak ada.

## Proposed Changes

### 1. Database Migration (`database/migrations/2026_07_21_03_add_audit_log_menu.php`)
#### [NEW] [2026_07_21_03_add_audit_log_menu.php](file:///C:/xampp/htdocs/SINTA-SaaS/database/migrations/2026_07_21_03_add_audit_log_menu.php)
- Membuat file migrasi terstandarisasi untuk menambahkan menu 79 `Audit Trail & Log Security` khusus `super_admin`.

---

### 2. Logger Helper (`app/Helpers/AuditLogger.php`)
#### [NEW] [AuditLogger.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Helpers/AuditLogger.php)
- Membuat helper terpusat `App\Helpers\AuditLogger::log()` untuk memudahkan perekaman aktivitas pengguna secara konsisten di seluruh modul aplikasi.

---

### 3. Routing System (`index.php`)
#### [MODIFY] [index.php](file:///C:/xampp/htdocs/SINTA-SaaS/index.php)
- Mendaftarkan rute halaman dan API:
  - `GET /keuangan/audit-log` -> `SppController::auditLog()`
  - `GET /api/v1/keuangan/audit-log` -> `SppController::apiAuditLog()`

---

### 4. Controller Backend & Security (`app/Controllers/SppController.php`)
#### [MODIFY] [SppController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/SppController.php)
- Menambahkan method `auditLog()` untuk merender halaman view log audit (dengan pengecekan hak akses `super_admin`).
- Menambahkan method `apiAuditLog()` untuk mengembalikan data log audit (dengan filter tenant, jenis aksi, rentang tanggal, search query, dan pagination).
- Mengintegrasikan pemanggilan `AuditLogger::log()` / `transaksi_spp_audit_log` pada seluruh fungsi CRUD (Komponen, Tarif, Keringanan, Generate Tagihan, Edit Nominal, Hapus Tagihan, Pembayaran Kasir, Void Pembayaran, & Pengaturan).

---

### 5. Frontend View (`views/keuangan/audit_log.php`)
#### [NEW] [audit_log.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/audit_log.php)
- Membangun antarmuka modern Vue 3 untuk Super Admin:
  - Header & Badge Keamanan Super Admin Platform.
  - 4 Kartu Metrik Ringkasan Audit (Total Log, Pembayaran, Pembatalan/Void, Modifikasi/Hapus).
  - Baris Filter Canggih (Sekolah/Tenant, Jenis Aksi, Tanggal Mulai - Akhir, & Search Bar).
  - Tabel Audit Log interaktif dengan badge status aksi berwarna-warni.
  - Modal **"Detail Perubahan Data (Audit JSON Diff)"** untuk memeriksa `Data Sebelum` vs `Data Sesudah`.

---

## Verification Plan

### Automated Tests
- Eksekusi migrasi database: `php migrate.php`
- Linting sintaksis PHP: `php -l app/Controllers/SppController.php`, `php -l views/keuangan/audit_log.php`

### Manual Verification
1. Akses `/SINTA-SaaS/keuangan/audit-log` menggunakan akun **Super Admin**.
2. Verifikasi bahwa halaman **Audit Trail & Log Security** terbuka dengan sempurna.
3. Lakukan pengujian filter: pilih Tenant tertentu, filter Jenis Aksi `VOID_PAYMENT`, dan masukkan kata kunci pencarian.
4. Klik tombol **"Lihat Perubahan"** pada salah satu baris log audit untuk memverifikasi modal JSON Diff.
5. Coba akses `/SINTA-SaaS/keuangan/audit-log` menggunakan akun selain Super Admin (misal akun Operator Sekolah). Verifikasi bahwa sistem menolak akses secara ketat dengan status 403 Forbidden.


