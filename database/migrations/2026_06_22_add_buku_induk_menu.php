<?php
/**
 * Migration: Tambahkan Sub Menu Buku Induk (ID 35)
 * Di bawah menu induk Data Pokok / Core Dapodik (parent_id = 5)
 */
return [
    'up' => function (PDO $pdo): void {
        // 1. Masukkan menu baru "Buku Induk" (id 35) ke tabel menus di bawah parent_id 5 (Data Pokok / Core Dapodik)
        $pdo->exec("
            INSERT INTO `menus` (`id`, `nama_menu`, `url`, `icon`, `parent_id`, `urutan`)
            VALUES (35, 'Buku Induk', '/SINTA-SaaS/buku-induk', 'bi bi-book-half', 5, 3)
            ON DUPLICATE KEY UPDATE
                `nama_menu` = VALUES(`nama_menu`),
                `url`       = VALUES(`url`),
                `icon`      = VALUES(`icon`),
                `parent_id` = VALUES(`parent_id`),
                `urutan`    = VALUES(`urutan`);
        ");

        // 2. Daftarkan menu baru ini ke hak akses default tenant ('00000000-0000-0000-0000-000000000000') untuk:
        //    - Super Admin (role_id = 1)
        //    - Operator Sekolah (role_id = 2)
        //    - Guru (role_id = 3)
        $defaultTenant = '00000000-0000-0000-0000-000000000000';
        $pdo->exec("
            INSERT IGNORE INTO `role_menu_access` (`tenant_id`, `role_id`, `menu_id`)
            VALUES 
                ('{$defaultTenant}', 1, 35),
                ('{$defaultTenant}', 2, 35),
                ('{$defaultTenant}', 3, 35);
        ");

        // 3. Daftarkan menu baru ini ke semua tenant aktif agar RouteGuard & Sidebar meloloskannya
        $tenants = $pdo->query("SELECT id FROM tenants WHERE deleted_at IS NULL")->fetchAll(\PDO::FETCH_COLUMN);
        if (!empty($tenants)) {
            $rows = array_map(fn($tid) => "('$tid', 35)", $tenants);
            $pdo->exec("
                INSERT IGNORE INTO `tenant_menu_access` (`tenant_id`, `menu_id`) VALUES
                " . implode(',', $rows)
            );
            echo "  OK tenant_menu_access untuk Buku Induk berhasil disinkron ke " . count($tenants) . " sekolah.\n";
        }

        echo "  OK Menu 'Buku Induk' (ID 35) berhasil didaftarkan.\n";
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("DELETE FROM `role_menu_access` WHERE `menu_id` = 35;");
        $pdo->exec("DELETE FROM `tenant_menu_access` WHERE `menu_id` = 35;");
        $pdo->exec("DELETE FROM `menus` WHERE `id` = 35;");
        echo "  OK Rollback menu Buku Induk selesai.\n";
    },
];
