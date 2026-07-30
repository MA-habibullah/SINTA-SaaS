<?php
/**
 * Migration: Create Dynamic SPP Billing and Financial Tables
 * Location: database/migrations/2026_07_21_01_create_spp_billing_system.php
 */

return [
    'up' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

        // 1. Tabel Pengaturan Modul Keuangan (Tenant Settings)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `transaksi_spp_pengaturan` (
            `tenant_id` CHAR(36) PRIMARY KEY,
            `nama_modul` VARCHAR(100) NOT NULL DEFAULT 'Keuangan & SPP',
            `istilah_tagihan` VARCHAR(100) NOT NULL DEFAULT 'Tagihan',
            `istilah_tunggakan` VARCHAR(100) NOT NULL DEFAULT 'Tunggakan',
            `visibilitas_siswa` TINYINT(1) DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT `fk_spp_settings_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

        // 2. Tabel Komponen Biaya (Komponen Tagihan)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `transaksi_spp_komponen` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `tenant_id` CHAR(36) NOT NULL,
            `nama_komponen` VARCHAR(100) NOT NULL,
            `tipe_periode` ENUM('Bulanan', 'Semester', 'Tahunan', 'Bebas') NOT NULL DEFAULT 'Bulanan',
            `is_active` TINYINT(1) DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT `fk_spp_komponen_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

        // 3. Tabel Tarif Default per Komponen (Kelas, Jenjang, Jalur Masuk)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `transaksi_spp_tarif` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `tenant_id` CHAR(36) NOT NULL,
            `komponen_id` INT UNSIGNED NOT NULL,
            `kelas_id` INT UNSIGNED DEFAULT NULL,
            `jenjang_id` INT UNSIGNED DEFAULT NULL,
            `jalur_masuk` VARCHAR(50) DEFAULT NULL,
            `nominal` DECIMAL(12, 2) NOT NULL,
            `tahun_ajaran_id` INT UNSIGNED NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT `fk_spp_tarif_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_spp_tarif_komponen` FOREIGN KEY (`komponen_id`) REFERENCES `transaksi_spp_komponen` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_spp_tarif_kelas` FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`id`) ON DELETE SET NULL,
            CONSTRAINT `fk_spp_tarif_jenjang` FOREIGN KEY (`jenjang_id`) REFERENCES `jenjang` (`id`) ON DELETE SET NULL,
            CONSTRAINT `fk_spp_tarif_ta` FOREIGN KEY (`tahun_ajaran_id`) REFERENCES `tahun_ajaran` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

        // 4. Tabel Keringanan / Potongan Biaya (Diskon/Beasiswa)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `transaksi_spp_keringanan` (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

        // 5. Tabel Tagihan Terbit
        $pdo->exec("CREATE TABLE IF NOT EXISTS `transaksi_spp_tagihan` (
            `id` CHAR(36) PRIMARY KEY,
            `tenant_id` CHAR(36) NOT NULL,
            `siswa_id` CHAR(36) NOT NULL,
            `komponen_id` INT UNSIGNED NOT NULL,
            `tarif_id` INT UNSIGNED NOT NULL,
            `tahun_ajaran_id` INT UNSIGNED NOT NULL,
            `bulan` TINYINT DEFAULT NULL,
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

        // 6. Tabel Ledger Pembayaran (Kasir)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `transaksi_spp_pembayaran` (
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
            `gateway_reference_id` VARCHAR(100) DEFAULT NULL,
            `status_transaksi` ENUM('Pending', 'Success', 'Failed') DEFAULT 'Success',
            CONSTRAINT `fk_spp_bayar_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_spp_bayar_tagihan` FOREIGN KEY (`tagihan_id`) REFERENCES `transaksi_spp_tagihan` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_spp_bayar_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_spp_bayar_kasir` FOREIGN KEY (`kasir_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

        // 7. Tabel Audit Log Keuangan
        $pdo->exec("CREATE TABLE IF NOT EXISTS `transaksi_spp_audit_log` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `tenant_id` CHAR(36) NOT NULL,
            `user_id` CHAR(36) NOT NULL,
            `aksi` VARCHAR(50) NOT NULL,
            `tabel_target` VARCHAR(50) NOT NULL,
            `target_id` VARCHAR(36) NOT NULL,
            `data_sebelum` TEXT DEFAULT NULL,
            `data_sesudah` TEXT DEFAULT NULL,
            `keterangan` TEXT DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT `fk_spp_audit_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_spp_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

        // 8. Registrasi Menu-Menu Baru (ID: 70 - 78)
        $menus = [
            [70, 'Keuangan & Pembayaran', '#', null, 'bi bi-wallet2', 110],
            [71, 'Dashboard Keuangan', '/SINTA-SaaS/keuangan/dashboard', 70, 'bi bi-speedometer2', 1],
            [72, 'Atur Tarif & Biaya', '/SINTA-SaaS/keuangan/master', 70, 'bi bi-tags', 2],
            [73, 'Keringanan & Beasiswa', '/SINTA-SaaS/keuangan/keringanan', 70, 'bi bi-award', 3],
            [74, 'Generate Tagihan', '/SINTA-SaaS/keuangan/generate', 70, 'bi bi-file-earmark-plus', 4],
            [75, 'Loket Pembayaran', '/SINTA-SaaS/keuangan/kasir', 70, 'bi bi-cash-stack', 5],
            [76, 'Laporan Keuangan', '/SINTA-SaaS/keuangan/laporan', 70, 'bi bi-graph-up-arrow', 6],
            [77, 'Pengaturan Keuangan', '/SINTA-SaaS/keuangan/pengaturan', 70, 'bi bi-gear', 7],
            [78, 'Tagihan Saya', '/SINTA-SaaS/keuangan/tagihan-saya', 70, 'bi bi-file-earmark-text', 8]
        ];

        $stmtMenu = $pdo->prepare("INSERT INTO `menus` (id, nama_menu, url, parent_id, icon, urutan) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($menus as $m) {
            try {
                $stmtMenu->execute($m);
            } catch (\PDOException $e) {}
        }

        // 9. Berikan Akses Menu default (super_admin dan operator_sekolah)
        // super_admin (role_id = 1), operator_sekolah (role_id = 2)
        // Ambil semua tenant untuk memasangkan hak akses menu
        $tenants = $pdo->query("SELECT id FROM tenants")->fetchAll(PDO::FETCH_COLUMN);
        // Default tenant fallback UUID
        $tenants[] = '00000000-0000-0000-0000-000000000000';
        $tenants = array_unique($tenants);

        $stmtTMA = $pdo->prepare("INSERT INTO tenant_menu_access (tenant_id, menu_id) VALUES (?, ?)");
        $stmtRMA = $pdo->prepare("INSERT INTO role_menu_access (tenant_id, role_id, menu_id) VALUES (?, ?, ?)");

        // 70 - 77 diakses oleh super_admin (1) & operator_sekolah (2) & kepala_sekolah (26)
        // 78 diakses oleh siswa (4)
        foreach ($tenants as $tid) {
            for ($mid = 70; $mid <= 78; $mid++) {
                try {
                    $stmtTMA->execute([$tid, $mid]);
                } catch (\PDOException $e) {}
            }

            // Daftarkan role access
            // Admin/Kasir roles (1, 2, 26)
            foreach ([1, 2, 26] as $rid) {
                for ($mid = 70; $mid <= 77; $mid++) {
                    try {
                        $stmtRMA->execute([$tid, $rid, $mid]);
                    } catch (\PDOException $e) {}
                }
                // Admin/Kasir also can see "Tagihan Saya" (ID 78) as fallback/test
                try {
                    $stmtRMA->execute([$tid, $rid, 78]);
                } catch (\PDOException $e) {}
            }

            // Siswa role (4) can only see the parent menu (70) and "Tagihan Saya" (78)
            try {
                $stmtRMA->execute([$tid, 4, 70]);
            } catch (\PDOException $e) {}
            try {
                $stmtRMA->execute([$tid, 4, 78]);
            } catch (\PDOException $e) {}
        }

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

        // Hapus tabel
        $pdo->exec("DROP TABLE IF EXISTS `transaksi_spp_audit_log`;");
        $pdo->exec("DROP TABLE IF EXISTS `transaksi_spp_pembayaran`;");
        $pdo->exec("DROP TABLE IF EXISTS `transaksi_spp_tagihan`;");
        $pdo->exec("DROP TABLE IF EXISTS `transaksi_spp_keringanan`;");
        $pdo->exec("DROP TABLE IF EXISTS `transaksi_spp_tarif`;");
        $pdo->exec("DROP TABLE IF EXISTS `transaksi_spp_komponen`;");
        $pdo->exec("DROP TABLE IF EXISTS `transaksi_spp_pengaturan`;");

        // Hapus menu dari 70 s/d 78
        $pdo->exec("DELETE FROM role_menu_access WHERE menu_id BETWEEN 70 AND 78;");
        $pdo->exec("DELETE FROM tenant_menu_access WHERE menu_id BETWEEN 70 AND 78;");
        $pdo->exec("DELETE FROM menus WHERE id BETWEEN 70 AND 78;");

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }
];
