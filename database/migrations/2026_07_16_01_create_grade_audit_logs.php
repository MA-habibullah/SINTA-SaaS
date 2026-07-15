<?php
/**
 * Migration: Create Grade Audit Logs & Add is_locked Column to kelas_kurikulum
 */
return [
    'up' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

        // 1. Tambah kolom is_locked ke kelas_kurikulum
        try {
            $pdo->exec("ALTER TABLE `kelas_kurikulum` ADD COLUMN `is_locked` TINYINT(1) DEFAULT 0;");
        } catch (\Throwable $e) {
            // Kolom mungkin sudah ada
        }

        // 2. Create log_nilai_rapor
        $pdo->exec("CREATE TABLE IF NOT EXISTS `log_nilai_rapor` (
            `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
            `tenant_id` CHAR(36) NOT NULL,
            `user_id` CHAR(36) NOT NULL,
            `siswa_id` INT UNSIGNED NOT NULL,
            `mapel_id` INT UNSIGNED NOT NULL,
            `semester` VARCHAR(50) NOT NULL,
            `tahun_ajaran` VARCHAR(50) NOT NULL,
            `nilai_lama_json` TEXT DEFAULT NULL,
            `nilai_baru_json` TEXT DEFAULT NULL,
            `action` VARCHAR(20) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            CONSTRAINT `fk_log_nilai_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
            INDEX `idx_log_nilai_lookup` (`tenant_id`, `siswa_id`, `mapel_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    },
    'down' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec("DROP TABLE IF EXISTS `log_nilai_rapor`;");
        try {
            $pdo->exec("ALTER TABLE `kelas_kurikulum` DROP COLUMN `is_locked`;");
        } catch (\Throwable $e) {}
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }
];
