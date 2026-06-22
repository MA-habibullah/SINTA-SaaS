<?php
/**
 * Migration 011 - Create Tenant Menu Access Table & Seeder
 */

return [
    'up' => function(PDO $pdo) {
        // 1. Buat tabel tenant_menu_access
        $pdo->exec("CREATE TABLE IF NOT EXISTS tenant_menu_access (
            id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,
            tenant_id CHAR(36) NOT NULL,
            menu_id INT UNSIGNED NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_tenant_menu (tenant_id, menu_id),
            CONSTRAINT fk_tma_tenant_id FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE,
            CONSTRAINT fk_tma_menu_id FOREIGN KEY (menu_id) REFERENCES menus (id) ON DELETE CASCADE
        ) ENGINE=InnoDB;");

        // 2. Masukkan menu baru "Akses Fitur Sekolah" (id 18) ke tabel menus di bawah parent_id 13 (Sistem & Utilitas)
        $pdo->exec("INSERT INTO menus (id, nama_menu, url, icon, parent_id, urutan) VALUES
            (18, 'Akses Fitur Sekolah', '/dapodik-spmb/super-admin/tenant-menus', 'bi bi-building-lock', 13, 5)
        ON DUPLICATE KEY UPDATE nama_menu=VALUES(nama_menu), url=VALUES(url), icon=VALUES(icon), parent_id=VALUES(parent_id), urutan=VALUES(urutan);");

        // 3. Daftarkan menu baru ini ke hak akses Super Admin (role_id = 1)
        $pdo->exec("INSERT INTO role_menu_access (role_id, menu_id) VALUES (1, 18)
        ON DUPLICATE KEY UPDATE role_id=VALUES(role_id);");

        // 4. Lakukan seeding menu default (semua menu 1 s.d 18) untuk tenant yang sudah ada
        // SMA Negeri 1 Jakarta: '11111111-1111-1111-1111-111111111111'
        // SMK Negeri 2 Bandung: '22222222-2222-2222-2222-222222222222'
        $tenants = [
            '11111111-1111-1111-1111-111111111111',
            '22222222-2222-2222-2222-222222222222'
        ];

        $insertValues = [];
        foreach ($tenants as $tenantId) {
            for ($menuId = 1; $menuId <= 18; $menuId++) {
                if ($menuId === 8) continue; // Menu id 8 dilewati karena tidak ada
                $insertValues[] = "('{$tenantId}', {$menuId})";
            }
        }

        if (!empty($insertValues)) {
            $sql = "INSERT INTO tenant_menu_access (tenant_id, menu_id) VALUES " . implode(", ", $insertValues) . "
                    ON DUPLICATE KEY UPDATE tenant_id=VALUES(tenant_id);";
            $pdo->exec($sql);
        }
    },

    'down' => function(PDO $pdo) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec("DELETE FROM role_menu_access WHERE menu_id = 18;");
        $pdo->exec("DELETE FROM menus WHERE id = 18;");
        $pdo->exec("DROP TABLE IF EXISTS tenant_menu_access;");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }
];
