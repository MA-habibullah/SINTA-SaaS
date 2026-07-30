<?php
/**
 * Migration: Tambahkan Menu Pemindai Dokumen (ID 60)
 * Di bawah menu induk Sistem & Utilitas (parent_id = 13)
 */
return [
    'up' => function (PDO $pdo): void {
        $defaultTenant = '00000000-0000-0000-0000-000000000000';

        // 1. Masukkan menu baru "Pemindai Dokumen" (id 60) ke tabel menus di bawah parent_id 13 (Sistem & Utilitas)
        $pdo->exec("
            INSERT INTO `menus` (`id`, `nama_menu`, `url`, `icon`, `parent_id`, `urutan`)
            VALUES (60, 'Pemindai Dokumen', '/SINTA-SaaS/utility/document-scanner', 'bi bi-camera-fill', 13, 12)
            ON DUPLICATE KEY UPDATE
                `nama_menu` = VALUES(`nama_menu`),
                `url`       = VALUES(`url`),
                `icon`      = VALUES(`icon`),
                `parent_id` = VALUES(`parent_id`),
                `urutan`    = VALUES(`urutan`);
        ");

        // 2. Berikan akses default ke seluruh role yang terdaftar di database untuk tenant default
        $roles = $pdo->query("SELECT id FROM roles")->fetchAll(PDO::FETCH_COLUMN);
        if (!empty($roles)) {
            $accessRows = array_map(fn($roleId) => "('{$defaultTenant}', {$roleId}, 60)", $roles);
            $pdo->exec("
                INSERT IGNORE INTO `role_menu_access` (`tenant_id`, `role_id`, `menu_id`)
                VALUES " . implode(',', $accessRows)
            );
        }

        // 3. Daftarkan menu baru ini ke semua tenant aktif agar RouteGuard & Sidebar meloloskannya
        $tenants = $pdo->query("SELECT id FROM tenants WHERE deleted_at IS NULL")->fetchAll(PDO::FETCH_COLUMN);
        if (!empty($tenants)) {
            $tenantRows = array_map(fn($tid) => "('$tid', 60)", $tenants);
            $pdo->exec("
                INSERT IGNORE INTO `tenant_menu_access` (`tenant_id`, `menu_id`) VALUES
                " . implode(',', $tenantRows)
            );
            echo "  OK tenant_menu_access untuk Pemindai Dokumen berhasil disinkron ke " . count($tenants) . " sekolah.\n";
        }

        echo "  OK Menu 'Pemindai Dokumen' (ID 60) berhasil didaftarkan.\n";
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("DELETE FROM `role_menu_access` WHERE `menu_id` = 60;");
        $pdo->exec("DELETE FROM `tenant_menu_access` WHERE `menu_id` = 60;");
        $pdo->exec("DELETE FROM `menus` WHERE `id` = 60;");
        echo "  OK Rollback menu Pemindai Dokumen selesai.\n";
    },
];
