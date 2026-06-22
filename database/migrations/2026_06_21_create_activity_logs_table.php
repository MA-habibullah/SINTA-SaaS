<?php
/**
 * Migration: Create Activity Logs (Audit Trail) Table and Sidebar Menu
 * 
 * 1. Membuat tabel `activity_logs` (membiarkan charset mewarisi database default).
 * 2. Mendaftarkan menu "Log Aktivitas" (ID 20) di bawah "Sistem & Utilitas" (ID 13).
 * 3. Menambahkan akses menu ke `role_menu_access` untuk Super Admin (1) dan Operator Sekolah (2).
 */
return [

    'up' => function (PDO $pdo): void {
        // 1. Buat tabel activity_logs
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `activity_logs` (
                `id` CHAR(36) NOT NULL,
                `tenant_id` CHAR(36) DEFAULT NULL,
                `user_id` CHAR(36) DEFAULT NULL,
                `user_role` VARCHAR(50) NOT NULL,
                `action` ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL,
                `table_name` VARCHAR(100) NOT NULL,
                `record_id` VARCHAR(36) NOT NULL,
                `old_data` JSON DEFAULT NULL,
                `new_data` JSON DEFAULT NULL,
                `ip_address` VARCHAR(45) NOT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                CONSTRAINT `fk_logs_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
                INDEX `idx_logs_tenant_role` (`tenant_id`, `user_role`),
                INDEX `idx_logs_table_record` (`table_name`, `record_id`),
                INDEX `idx_logs_created` (`created_at`)
            ) ENGINE=InnoDB;
        ");

        // 2. Tambah menu Log Aktivitas di bawah Sistem & Utilitas (parent_id = 13, urutan = 7)
        $pdo->exec("
            INSERT INTO `menus` (`id`, `nama_menu`, `url`, `icon`, `parent_id`, `urutan`)
            VALUES (20, 'Log Aktivitas', '/SINTA-SaaS/utilitas/log-aktivitas', 'bi bi-journal-text', 13, 7)
            ON DUPLICATE KEY UPDATE `nama_menu`=VALUES(`nama_menu`), `url`=VALUES(`url`), `icon`=VALUES(`icon`), `parent_id`=VALUES(`parent_id`), `urutan`=VALUES(`urutan`);
        ");

        // 3. Tambah relasi role_menu_access untuk default tenant
        $defaultTenant = '00000000-0000-0000-0000-000000000000';
        $pdo->exec("
            INSERT IGNORE INTO `role_menu_access` (`tenant_id`, `role_id`, `menu_id`)
            VALUES 
                ('{$defaultTenant}', 1, 20), -- Super Admin
                ('{$defaultTenant}', 2, 20)  -- Operator Sekolah
        ");

        echo "  OK Tabel activity_logs dan menu Log Aktivitas berhasil dibuat.\n";
    },

    'down' => function (PDO $pdo): void {
        // Drop foreign key check temporary
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

        // 1. Hapus akses menu
        $pdo->exec("DELETE FROM `role_menu_access` WHERE `menu_id` = 20;");

        // 2. Hapus menu
        $pdo->exec("DELETE FROM `menus` WHERE `id` = 20;");

        // 3. Drop tabel
        $pdo->exec("DROP TABLE IF EXISTS `activity_logs`;");

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

        echo "  OK Rollback tabel activity_logs dan menu Log Aktivitas selesai.\n";
    },
];
