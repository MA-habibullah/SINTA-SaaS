<?php
/**
 * Migration: Create Alumni Archiving Tables (arsip_dokumen_alumni & log_akses_arsip)
 */
return [
    'up' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

        // 1. Create table arsip_dokumen_alumni
        $pdo->exec("CREATE TABLE IF NOT EXISTS `arsip_dokumen_alumni` (
            `id` CHAR(36) NOT NULL PRIMARY KEY,
            `siswa_id` CHAR(36) NOT NULL,
            `tenant_id` CHAR(36) NOT NULL,
            `jenis_dokumen` ENUM('Buku Induk', 'Ijazah', 'SKHUN', 'Sertifikat/SKL', 'Lainnya') NOT NULL,
            `file_path` VARCHAR(255) NOT NULL,
            `file_size` INT UNSIGNED DEFAULT 0,
            `keterangan` TEXT DEFAULT NULL,
            `uploaded_by` CHAR(36) DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT `fk_arsip_dokumen_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_arsip_dokumen_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

        // 2. Create table log_akses_arsip
        $pdo->exec("CREATE TABLE IF NOT EXISTS `log_akses_arsip` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` CHAR(36) NOT NULL,
            `tenant_id` CHAR(36) NOT NULL,
            `siswa_id` CHAR(36) NOT NULL,
            `aktivitas` VARCHAR(100) NOT NULL,
            `ip_address` VARCHAR(45) NOT NULL,
            `user_agent` VARCHAR(255) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT `fk_log_akses_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_log_akses_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    },
    'down' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec("DROP TABLE IF EXISTS `log_akses_arsip`;");
        $pdo->exec("DROP TABLE IF EXISTS `arsip_dokumen_alumni`;");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }
];
