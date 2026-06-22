<?php
/**
 * Migration: Create System Errors Table (Error Tracker & Log Monitor)
 *
 * 1. Membuat tabel `system_errors` untuk menampung semua error PHP/SQL otomatis.
 * 2. Mendaftarkan menu "Error Monitor" (ID 22) di Sistem & Utilitas (parent_id = 13).
 * 3. Menambahkan akses hanya untuk Super Admin (role_id = 1).
 */
return [

    'up' => function (PDO $pdo): void {
        // 1. Buat tabel system_errors
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `system_errors` (
                `id`             CHAR(36)      NOT NULL,
                `tenant_id`      CHAR(36)      DEFAULT NULL COMMENT 'Null jika error terjadi di luar konteks login',
                `error_level`    VARCHAR(50)   NOT NULL COMMENT 'Exception | Fatal | E_WARNING | E_NOTICE dll',
                `message`        TEXT          NOT NULL COMMENT 'Pesan error dari PHP/PDO',
                `file`           VARCHAR(500)  DEFAULT NULL COMMENT 'Path absolut file tempat error terjadi',
                `line`           INT UNSIGNED  DEFAULT NULL COMMENT 'Nomor baris tempat error terjadi',
                `trace`          LONGTEXT      DEFAULT NULL COMMENT 'Stack trace lengkap (format JSON array)',
                `request_url`    VARCHAR(1000) DEFAULT NULL COMMENT 'Full URL request saat error terjadi',
                `request_method` VARCHAR(10)   DEFAULT NULL COMMENT 'GET, POST, PUT, DELETE dll',
                `user_agent`     VARCHAR(500)  DEFAULT NULL,
                `ip_address`     VARCHAR(45)   DEFAULT NULL,
                `created_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                INDEX `idx_se_error_level` (`error_level`),
                INDEX `idx_se_tenant`      (`tenant_id`),
                INDEX `idx_se_created`     (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 2. Tambah menu Error Monitor di Sistem & Utilitas (parent_id = 13, urutan = 10)
        $pdo->exec("
            INSERT INTO `menus` (`id`, `nama_menu`, `url`, `icon`, `parent_id`, `urutan`)
            VALUES (22, 'Error Monitor', '/dapodik-spmb/super-admin/error-monitor', 'bi bi-bug-fill', 13, 10)
            ON DUPLICATE KEY UPDATE
                `nama_menu` = VALUES(`nama_menu`),
                `url`       = VALUES(`url`),
                `icon`      = VALUES(`icon`),
                `parent_id` = VALUES(`parent_id`),
                `urutan`    = VALUES(`urutan`);
        ");

        // 3. Akses menu HANYA untuk Super Admin (role_id = 1) — DEVELOPER ONLY
        $defaultTenant = '00000000-0000-0000-0000-000000000000';
        $pdo->exec("
            INSERT IGNORE INTO `role_menu_access` (`tenant_id`, `role_id`, `menu_id`)
            VALUES ('{$defaultTenant}', 1, 22);
        ");

        echo "  OK Tabel system_errors dan menu Error Monitor berhasil dibuat.\n";
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec("DELETE FROM `role_menu_access` WHERE `menu_id` = 22;");
        $pdo->exec("DELETE FROM `menus` WHERE `id` = 22;");
        $pdo->exec("DROP TABLE IF EXISTS `system_errors`;");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "  OK Rollback system_errors dan menu Error Monitor selesai.\n";
    },
];
