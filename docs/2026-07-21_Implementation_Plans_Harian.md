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


