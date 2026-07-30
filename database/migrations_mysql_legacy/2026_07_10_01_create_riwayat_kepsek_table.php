<?php
/**
 * Migration - Create Riwayat Kepala Sekolah Table
 */

return [
    'up' => function(PDO $pdo) {
        $sql = "CREATE TABLE IF NOT EXISTS `riwayat_kepala_sekolah` (
            `id` CHAR(36) NOT NULL,
            `tenant_id` CHAR(36) NOT NULL,
            `nama_kepsek` VARCHAR(255) NOT NULL,
            `nip_kepsek` VARCHAR(50) DEFAULT NULL,
            `tanggal_mulai` DATE NOT NULL,
            `tanggal_selesai` DATE DEFAULT NULL,
            `status_plt` TINYINT(1) DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            CONSTRAINT `fk_riwayat_kepsek_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
        
        $pdo->exec($sql);
    },
    'down' => function(PDO $pdo) {
        $pdo->exec("DROP TABLE IF EXISTS `riwayat_kepala_sekolah`;");
    }
];
