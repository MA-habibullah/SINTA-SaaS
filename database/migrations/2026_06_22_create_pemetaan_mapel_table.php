<?php
/**
 * Migration: Create Pemetaan Mapel Table
 */
return [
    'up' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS `pemetaan_mapel` (
            `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
            `tenant_id` CHAR(36) NOT NULL,
            `tahun_ajaran` VARCHAR(50) NOT NULL,
            `semester` VARCHAR(20) NOT NULL,
            `kelas_id` INT UNSIGNED NOT NULL,
            `kelompok_id` VARCHAR(100) NOT NULL,
            `mapel_id` INT UNSIGNED NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `deleted_at` TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            CONSTRAINT `fk_pemetaan_mapel_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_pemetaan_mapel_kelas_id` FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_pemetaan_mapel_mapel_id` FOREIGN KEY (`mapel_id`) REFERENCES `mata_pelajaran` (`id`) ON DELETE CASCADE,
            UNIQUE KEY `uq_pemetaan_mapel_sync` (`tenant_id`, `tahun_ajaran`, `semester`, `kelas_id`, `kelompok_id`, `mapel_id`),
            INDEX `idx_pemetaan_mapel_tenant` (`tenant_id`),
            INDEX `idx_pemetaan_mapel_kelas` (`kelas_id`),
            INDEX `idx_pemetaan_mapel_mapel` (`mapel_id`)
        ) ENGINE=InnoDB;");
        
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "  OK Tabel 'pemetaan_mapel' berhasil dibuat.\n";
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec("DROP TABLE IF EXISTS `pemetaan_mapel`;");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "  OK Rollback tabel 'pemetaan_mapel' selesai.\n";
    },
];
