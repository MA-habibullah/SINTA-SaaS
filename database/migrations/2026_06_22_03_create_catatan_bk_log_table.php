<?php
/**
 * Migration: Create Catatan BK Log Table
 *
 * Membuat tabel catatan_bk_log untuk mencatat audit trail aktivitas guru BK/Admin
 * dalam menangani kasus siswa (termasuk pembuatan awal dan perubahan status).
 */
return [
    'up' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS `catatan_bk_log` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,
            `id_catatan_bk` BIGINT UNSIGNED NOT NULL,
            `tenant_id` VARCHAR(36) NOT NULL,
            `status_lama` ENUM('Terbuka', 'Proses', 'Selesai') DEFAULT NULL COMMENT 'NULL jika pembuatan awal',
            `status_baru` ENUM('Terbuka', 'Proses', 'Selesai') NOT NULL,
            `id_user` VARCHAR(36) NOT NULL COMMENT 'user_id pelaku tindakan (guru_bk/admin)',
            `nama_user` VARCHAR(255) NOT NULL COMMENT 'Nama lengkap pelaku',
            `peran_user` VARCHAR(50) NOT NULL COMMENT 'Role pelaku saat bertindak',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            CONSTRAINT `fk_catatan_bk_log_catatan` FOREIGN KEY (`id_catatan_bk`) REFERENCES `catatan_bk` (`id`) ON DELETE CASCADE,
            INDEX `idx_cb_log_catatan` (`id_catatan_bk`),
            INDEX `idx_cb_log_tenant` (`tenant_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Audit Trail — Log Riwayat Aktivitas & Penanganan Kasus BK';");
        
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "  OK Tabel 'catatan_bk_log' berhasil dibuat.\n";
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec("DROP TABLE IF EXISTS `catatan_bk_log`;");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "  OK Rollback tabel 'catatan_bk_log' selesai.\n";
    },
];
