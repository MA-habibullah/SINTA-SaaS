<?php
/**
 * Migration: Create System Jobs Table
 * 
 * Membuat tabel system_jobs untuk menyimpan tugas-tugas (jobs) yang akan
 * dijalankan di latar belakang (background queue).
 */
return [

    'up' => function(PDO $pdo): void {
        $sql = "CREATE TABLE IF NOT EXISTS `system_jobs` (
            `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
            `tenant_id` CHAR(36) NULL DEFAULT NULL,
            `job_type` VARCHAR(100) NOT NULL,
            `payload` JSON NOT NULL,
            `status` ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
            `attempts` INT UNSIGNED DEFAULT 0,
            `error_message` TEXT NULL DEFAULT NULL,
            `reserved_at` DATETIME NULL DEFAULT NULL,
            `completed_at` DATETIME NULL DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX `idx_jobs_status` (`status`),
            INDEX `idx_jobs_tenant` (`tenant_id`),
            CONSTRAINT `fk_jobs_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB;";
        
        $pdo->exec($sql);
        echo "  OK Tabel system_jobs berhasil dibuat.\n";
    },

    'down' => function(PDO $pdo): void {
        $pdo->exec("DROP TABLE IF EXISTS `system_jobs`;");
        echo "  OK Tabel system_jobs berhasil dihapus.\n";
    }
];
