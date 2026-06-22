<?php
/**
 * Migration: Create Active Sessions Table
 * 
 * Membuat tabel active_sessions untuk melacak login harian dan pengguna online,
 * serta memperbarui URL menu Monitoring Sesi Aktif (ID 16).
 */
return [

    'up' => function (PDO $pdo): void {
        // 1. Buat tabel active_sessions
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `active_sessions` (
                `id` CHAR(36) NOT NULL,
                `user_id` CHAR(36) NOT NULL,
                `tenant_id` CHAR(36) DEFAULT NULL,
                `ip_address` VARCHAR(45) NOT NULL,
                `user_agent` VARCHAR(255) NOT NULL,
                `tanggal_login` DATE NOT NULL,
                `last_activity` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_user_login` (`user_id`, `tanggal_login`),
                KEY `idx_sessions_tenant_id` (`tenant_id`),
                KEY `idx_sessions_last_activity` (`last_activity`),
                KEY `idx_sessions_tanggal_login` (`tanggal_login`),
                CONSTRAINT `fk_sessions_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB;
        ");
        echo "  OK Tabel active_sessions berhasil dibuat.\n";

        // 2. Update URL menu Monitoring Sesi Aktif (ID 16)
        $pdo->exec("
            UPDATE `menus` 
            SET `url` = '/dapodik-spmb/utilitas/sesi-aktif' 
            WHERE `id` = 16;
        ");
        echo "  OK Menu Monitoring Sesi Aktif berhasil diupdate ke /dapodik-spmb/utilitas/sesi-aktif.\n";
    },

    'down' => function (PDO $pdo): void {
        // 1. Revert URL menu Monitoring Sesi Aktif (ID 16)
        $pdo->exec("
            UPDATE `menus` 
            SET `url` = '#' 
            WHERE `id` = 16;
        ");
        echo "  OK Menu Monitoring Sesi Aktif berhasil di-rollback ke #.\n";

        // 2. Hapus tabel active_sessions
        $pdo->exec("DROP TABLE IF EXISTS `active_sessions`;");
        echo "  OK Tabel active_sessions berhasil dihapus.\n";
    },
];
