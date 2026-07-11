<?php
/**
 * Migration: Restrukturisasi Menu BK & PDSS
 */
return [
    'up' => function (PDO $pdo): void {
        $defaultTenant = '00000000-0000-0000-0000-000000000000';

        // 1. Create Parent Menu (ID 29)
        $pdo->exec("
            INSERT INTO menus (id, nama_menu, url, icon, parent_id, urutan)
            VALUES (29, 'BIMBINGAN KONSELING', '#', 'bi bi-heart-pulse-fill', NULL, 6)
            ON DUPLICATE KEY UPDATE 
                nama_menu = VALUES(nama_menu),
                url = VALUES(url),
                icon = VALUES(icon),
                parent_id = VALUES(parent_id)
        ");

        // 2. Update Menu BK (ID 30) -> Layanan & Kedisiplinan
        $pdo->exec("
            UPDATE menus 
            SET nama_menu = 'Layanan & Kedisiplinan', 
                url = '/SINTA-SaaS/bk/layanan', 
                icon = 'bi bi-person-badge',
                parent_id = 29,
                urutan = 1
            WHERE id = 30
        ");

        // 3. Update Menu PDSS (ID 40) -> Kesiapan Akademik & PDSS
        $pdo->exec("
            UPDATE menus 
            SET nama_menu = 'Kesiapan Akademik & PDSS', 
                url = '/SINTA-SaaS/bk/akademik', 
                icon = 'bi bi-journal-check',
                parent_id = 29,
                urutan = 2
            WHERE id = 40
        ");

        // 4. Create Menu Alumni (ID 32)
        $pdo->exec("
            INSERT INTO menus (id, nama_menu, url, icon, parent_id, urutan)
            VALUES (32, 'Alumni & Tracer Study', '/SINTA-SaaS/bk/alumni', 'bi bi-mortarboard', 29, 3)
            ON DUPLICATE KEY UPDATE 
                nama_menu = VALUES(nama_menu),
                url = VALUES(url),
                icon = VALUES(icon),
                parent_id = VALUES(parent_id)
        ");

        // 5. Grant access for ID 29 and ID 32 to Super Admin(1), Operator(2), Guru BK(20)
        // Also verify ID 40 is granted to Guru BK(20) if not already
        $pdo->exec("
            INSERT IGNORE INTO role_menu_access (tenant_id, role_id, menu_id) VALUES
                ('{$defaultTenant}', 1, 29),
                ('{$defaultTenant}', 2, 29),
                ('{$defaultTenant}', 20, 29),
                
                ('{$defaultTenant}', 1, 32),
                ('{$defaultTenant}', 2, 32),
                ('{$defaultTenant}', 20, 32),

                ('{$defaultTenant}', 1, 40),
                ('{$defaultTenant}', 2, 40),
                ('{$defaultTenant}', 20, 40)
        ");

        // 6. Grant tenant_menu_access for ID 29 and 32
        $tenants = $pdo->query("SELECT id FROM tenants WHERE deleted_at IS NULL")->fetchAll(PDO::FETCH_COLUMN);
        if (!empty($tenants)) {
            $rows = [];
            foreach ($tenants as $tid) {
                $rows[] = "('$tid', 29)";
                $rows[] = "('$tid', 32)";
                $rows[] = "('$tid', 40)"; // ensure PDSS is available
            }
            if (count($rows) > 0) {
                $pdo->exec("
                    INSERT IGNORE INTO tenant_menu_access (tenant_id, menu_id) VALUES
                    " . implode(',', $rows)
                );
            }
        }
        
        echo "  OK Restrukturisasi Menu BK & PDSS selesai.\n";
    },

    'down' => function (PDO $pdo): void {
        // Rollback
        // Revert 30 and 40 back to top level
        $pdo->exec("
            UPDATE menus 
            SET nama_menu = 'Bimbingan Konseling', url = '/SINTA-SaaS/bk', icon = 'bi bi-heart-pulse-fill', parent_id = NULL 
            WHERE id = 30
        ");
        $pdo->exec("
            UPDATE menus 
            SET nama_menu = 'PDSS & Alumni', url = '/SINTA-SaaS/pdss/kesiapan', icon = 'bi bi-clipboard-check', parent_id = NULL 
            WHERE id = 40
        ");

        // Delete 29 and 32
        $pdo->exec("DELETE FROM role_menu_access WHERE menu_id IN (29, 32)");
        $pdo->exec("DELETE FROM tenant_menu_access WHERE menu_id IN (29, 32)");
        $pdo->exec("DELETE FROM menus WHERE id IN (29, 32)");

        echo "  OK Rollback Restrukturisasi Menu BK & PDSS selesai.\n";
    }
];
