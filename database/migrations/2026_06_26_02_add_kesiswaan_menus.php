<?php
/**
 * Migration: Add Kesiswaan & Ekskul Menus
 */

return [
    'up' => function(PDO $pdo) {
        // 1. Insert Menus
        // Cek ID terakhir untuk keamanan, atau gunakan ID yang cukup besar
        // ID 50: Kesiswaan (Parent)
        // ID 51: Ekstrakurikuler
        
        $pdo->exec("INSERT INTO menus (id, nama_menu, url, icon, parent_id, urutan) VALUES
            (50, 'Kesiswaan', '#', 'bi bi-person-badge', NULL, 6),
            (51, 'Ekstrakurikuler', '/SINTA-SaaS/kesiswaan/ekskul', 'bi bi-dribbble', 50, 1)
        ON DUPLICATE KEY UPDATE nama_menu=VALUES(nama_menu), url=VALUES(url), icon=VALUES(icon), parent_id=VALUES(parent_id), urutan=VALUES(urutan);");

        // 2. Akses Role (1: super_admin, 2: operator_sekolah, dll)
        $pdo->exec("INSERT IGNORE INTO role_menu_access (role_id, menu_id, tenant_id) VALUES 
            (1, 50, '00000000-0000-0000-0000-000000000000'), (1, 51, '00000000-0000-0000-0000-000000000000'),
            (2, 50, '00000000-0000-0000-0000-000000000000'), (2, 51, '00000000-0000-0000-0000-000000000000')
        ");

        // 3. Akses Tenant
        // Ambil semua tenant_id untuk didaftarkan menunya
        $stmt = $pdo->query("SELECT id FROM tenants");
        $tenants = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($tenants)) {
            $insertTenantMenu = [];
            foreach ($tenants as $t) {
                $insertTenantMenu[] = "('$t', 50)";
                $insertTenantMenu[] = "('$t', 51)";
            }
            $pdo->exec("INSERT IGNORE INTO tenant_menu_access (tenant_id, menu_id) VALUES " . implode(", ", $insertTenantMenu));
        }

        echo "  OK Menu Kesiswaan & Ekstrakurikuler berhasil ditambahkan.\n";
    },

    'down' => function(PDO $pdo) {
        $pdo->exec("DELETE FROM menus WHERE id IN (50, 51)");
        echo "  OK Rollback Menu Kesiswaan selesai.\n";
    }
];
