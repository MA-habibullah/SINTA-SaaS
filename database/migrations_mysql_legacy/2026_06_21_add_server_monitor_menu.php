<?php
/**
 * Migration: Server Monitor Menu (ID 23)
 * Mendaftarkan menu "Server Monitor" di bawah "Sistem & Utilitas" (parent_id = 13)
 * Akses eksklusif untuk Super Admin (role_id = 1) saja.
 */
return [

    'up' => function (PDO $pdo): void {
        $pdo->exec("
            INSERT INTO `menus` (`id`, `nama_menu`, `url`, `icon`, `parent_id`, `urutan`)
            VALUES (23, 'Server Monitor', '/SINTA-SaaS/super-admin/server-monitor', 'bi bi-hdd-network-fill', 13, 11)
            ON DUPLICATE KEY UPDATE
                `nama_menu` = VALUES(`nama_menu`),
                `url`       = VALUES(`url`),
                `icon`      = VALUES(`icon`),
                `parent_id` = VALUES(`parent_id`),
                `urutan`    = VALUES(`urutan`);
        ");

        $defaultTenant = '00000000-0000-0000-0000-000000000000';
        $pdo->exec("
            INSERT IGNORE INTO `role_menu_access` (`tenant_id`, `role_id`, `menu_id`)
            VALUES ('{$defaultTenant}', 1, 23);
        ");

        echo "  OK Menu 'Server Monitor' (ID 23) berhasil didaftarkan.\n";
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("DELETE FROM `role_menu_access` WHERE `menu_id` = 23;");
        $pdo->exec("DELETE FROM `menus` WHERE `id` = 23;");
        echo "  OK Rollback menu Server Monitor selesai.\n";
    },
];
