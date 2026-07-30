<?php

return [
    'up' => function (PDO $pdo): void {
        echo "Menjalankan migrasi: Create PDSS Tables (pdss_config_mapel, pdss_manual_eligible, pdss_lock)...\n";

        // 1. Create pdss_config_mapel
        $pdo->exec("CREATE TABLE IF NOT EXISTS `pdss_config_mapel` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `tenant_id` CHAR(36) NOT NULL,
            `mapel_id` INT UNSIGNED NOT NULL,
            `sem_1` TINYINT(1) DEFAULT 0,
            `sem_2` TINYINT(1) DEFAULT 0,
            `sem_3` TINYINT(1) DEFAULT 0,
            `sem_4` TINYINT(1) DEFAULT 0,
            `sem_5` TINYINT(1) DEFAULT 0,
            `sem_6` TINYINT(1) DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `uk_tenant_mapel` (`tenant_id`, `mapel_id`),
            CONSTRAINT `fk_pdss_mapel_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_pdss_mapel_id` FOREIGN KEY (`mapel_id`) REFERENCES `mata_pelajaran` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
        echo "- Tabel pdss_config_mapel berhasil dibuat.\n";

        // 2. Create pdss_manual_eligible
        $pdo->exec("CREATE TABLE IF NOT EXISTS `pdss_manual_eligible` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `tenant_id` CHAR(36) NOT NULL,
            `siswa_id` CHAR(36) NOT NULL,
            `status_eligible` ENUM('auto', 'eligible', 'tidak_eligible') DEFAULT 'auto',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `uk_tenant_siswa` (`tenant_id`, `siswa_id`),
            CONSTRAINT `fk_pdss_manual_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_pdss_manual_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
        echo "- Tabel pdss_manual_eligible berhasil dibuat.\n";

        // 3. Create pdss_lock
        $pdo->exec("CREATE TABLE IF NOT EXISTS `pdss_lock` (
            `tenant_id` CHAR(36) NOT NULL,
            `step` TINYINT(1) NOT NULL,
            `is_locked` TINYINT(1) DEFAULT 0,
            `locked_by` VARCHAR(255) DEFAULT NULL,
            `locked_at` TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY (`tenant_id`, `step`),
            CONSTRAINT `fk_pdss_lock_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
        echo "- Tabel pdss_lock berhasil dibuat.\n";

        echo "Migrasi pembuatan tabel PDSS selesai!\n";
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec("DROP TABLE IF EXISTS `pdss_lock`");
        $pdo->exec("DROP TABLE IF EXISTS `pdss_manual_eligible`");
        $pdo->exec("DROP TABLE IF EXISTS `pdss_config_mapel`");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "Rollback: Tabel PDSS dihapus.\n";
    },
];
