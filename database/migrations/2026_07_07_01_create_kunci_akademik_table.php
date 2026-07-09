<?php
/**
 * Migration: Create Kunci Akademik Table
 */
return [
    'up' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS `kunci_akademik` (
            `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
            `tenant_id` CHAR(36) NOT NULL,
            `tahun_ajaran` VARCHAR(50) NOT NULL,
            `semester` VARCHAR(20) NOT NULL,
            `is_locked_kurikulum` TINYINT(1) DEFAULT 0,
            `is_locked_nilai` TINYINT(1) DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            CONSTRAINT `fk_kunci_akademik_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
            UNIQUE KEY `uq_kunci_akademik` (`tenant_id`, `tahun_ajaran`, `semester`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
        
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "  OK Tabel 'kunci_akademik' berhasil dibuat.\n";
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec("DROP TABLE IF EXISTS `kunci_akademik`;");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "  OK Rollback tabel 'kunci_akademik' selesai.\n";
    },
];
