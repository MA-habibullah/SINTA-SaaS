<?php
/**
 * Migration: Create Dynamic Curriculum Engine Tables & Add Detail Nilai Rapor JSON Columns
 */
return [
    'up' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

        // 1. Create ref_kurikulum
        $pdo->exec("CREATE TABLE IF NOT EXISTS `ref_kurikulum` (
            `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
            `tenant_id` CHAR(36) DEFAULT NULL,
            `nama_kurikulum` VARCHAR(100) NOT NULL,
            `tipe_penilaian` ENUM('klasik', 'kompleks', 'sederhana', 'custom') NOT NULL DEFAULT 'sederhana',
            `is_active` TINYINT(1) DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            CONSTRAINT `fk_ref_kur_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
            UNIQUE KEY `uq_ref_kur_name` (`tenant_id`, `nama_kurikulum`),
            INDEX `idx_ref_kur_tenant` (`tenant_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

        // Seed default national curriculum data if empty
        $stmtCheck = $pdo->query("SELECT COUNT(*) FROM `ref_kurikulum` WHERE `tenant_id` IS NULL");
        if ($stmtCheck->fetchColumn() == 0) {
            $pdo->exec("INSERT INTO `ref_kurikulum` (`tenant_id`, `nama_kurikulum`, `tipe_penilaian`) VALUES
                (NULL, 'KBK (Kurikulum Berbasis Kompetensi)', 'klasik'),
                (NULL, 'KTSP (Kurikulum Tingkat Satuan Pendidikan)', 'klasik'),
                (NULL, 'Kurikulum 2013 (K-13)', 'kompleks'),
                (NULL, 'Kurikulum Merdeka', 'sederhana')
            ");
        }

        // 2. Create kelas_kurikulum mapping
        $pdo->exec("CREATE TABLE IF NOT EXISTS `kelas_kurikulum` (
            `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
            `tenant_id` CHAR(36) NOT NULL,
            `kelas_id` INT UNSIGNED NOT NULL,
            `tahun_ajaran` VARCHAR(50) NOT NULL,
            `kurikulum_id` INT UNSIGNED NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            CONSTRAINT `fk_kelas_kur_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_kelas_kur_kelas` FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_kelas_kur_ref` FOREIGN KEY (`kurikulum_id`) REFERENCES `ref_kurikulum` (`id`) ON DELETE CASCADE,
            UNIQUE KEY `uq_kelas_kur_sync` (`tenant_id`, `kelas_id`, `tahun_ajaran`),
            INDEX `idx_kelas_kur_tenant` (`tenant_id`),
            INDEX `idx_kelas_kur_kelas` (`kelas_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

        // 3. Add columns to detail_nilai_rapor
        // Check first to prevent errors
        $stmtCols = $pdo->query("SHOW COLUMNS FROM `detail_nilai_rapor` LIKE 'nilai_detail_json'");
        if (!$stmtCols->fetch()) {
            $pdo->exec("ALTER TABLE `detail_nilai_rapor` ADD COLUMN `nilai_detail_json` JSON DEFAULT NULL AFTER `deskripsi`;");
        }
        $stmtColsKKM = $pdo->query("SHOW COLUMNS FROM `detail_nilai_rapor` LIKE 'kkm'");
        if (!$stmtColsKKM->fetch()) {
            $pdo->exec("ALTER TABLE `detail_nilai_rapor` ADD COLUMN `kkm` DECIMAL(5,2) DEFAULT NULL AFTER `nilai_detail_json`;");
        }

        // 4. Create nilai_sikap_k13
        $pdo->exec("CREATE TABLE IF NOT EXISTS `nilai_sikap_k13` (
            `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
            `tenant_id` CHAR(36) NOT NULL,
            `siswa_id` CHAR(36) NOT NULL,
            `tahun_ajaran` VARCHAR(50) NOT NULL,
            `semester` VARCHAR(20) NOT NULL,
            `predikat_spiritual` VARCHAR(10) DEFAULT NULL,
            `deskripsi_spiritual` TEXT DEFAULT NULL,
            `predikat_sosial` VARCHAR(10) DEFAULT NULL,
            `deskripsi_sosial` TEXT DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            CONSTRAINT `fk_sikap_k13_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_sikap_k13_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE,
            UNIQUE KEY `uq_sikap_k13_sync` (`tenant_id`, `siswa_id`, `tahun_ajaran`, `semester`),
            INDEX `idx_sikap_k13_tenant` (`tenant_id`),
            INDEX `idx_sikap_k13_siswa` (`siswa_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "  OK Migrasi Dynamic Curriculum Engine selesai.\n";
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec("DROP TABLE IF EXISTS `nilai_sikap_k13`;");
        $pdo->exec("DROP TABLE IF EXISTS `kelas_kurikulum`;");
        $pdo->exec("DROP TABLE IF EXISTS `ref_kurikulum`;");
        $pdo->exec("ALTER TABLE `detail_nilai_rapor` DROP COLUMN `nilai_detail_json`, DROP COLUMN `kkm`;");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "  OK Rollback Dynamic Curriculum Engine selesai.\n";
    }
];
