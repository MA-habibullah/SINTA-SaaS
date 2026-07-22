<?php
/**
 * Migration: Seed Library Menus & Access (IDs 80-91)
 */
return [
    'up' => function (PDO $pdo): void {
        $menus = [
            [80, 'Perpustakaan', '/SINTA-SaaS/perpustakaan', 'bi bi-journal-bookmark-fill', NULL, 8],
            [81, 'Katalog & Koleksi', '/SINTA-SaaS/perpustakaan/katalog', 'bi bi-book', 80, 1],
            [82, 'Sirkulasi Reguler', '/SINTA-SaaS/perpustakaan/sirkulasi', 'bi bi-arrow-repeat', 80, 2],
            [83, 'Buku Paket', '/SINTA-SaaS/perpustakaan/buku-paket', 'bi bi-box-seam', 80, 3],
            [84, 'Event Khusus', '/SINTA-SaaS/perpustakaan/event', 'bi bi-trophy', 80, 4],
            [85, 'Keanggotaan & Bebas Pustaka', '/SINTA-SaaS/perpustakaan/anggota', 'bi bi-people', 80, 5],
            [86, 'Denda & Billing', '/SINTA-SaaS/perpustakaan/denda', 'bi bi-cash-coin', 80, 6],
            [87, 'Stock Opname', '/SINTA-SaaS/perpustakaan/opname', 'bi bi-qr-code-scan', 80, 7],
            [88, 'Laporan Perpustakaan', '/SINTA-SaaS/perpustakaan/laporan', 'bi bi-file-earmark-bar-graph', 80, 8],
            [89, 'Pengaturan Perpustakaan', '/SINTA-SaaS/perpustakaan/pengaturan', 'bi bi-gear', 80, 9],
            [90, 'OPAC Publik', '/SINTA-SaaS/perpustakaan/opac', 'bi bi-globe', 80, 10],
            [91, 'Perpustakaan Saya', '/SINTA-SaaS/perpustakaan/riwayat-saya', 'bi bi-book-half', NULL, 9]
        ];

        $stmtMenu = $pdo->prepare("INSERT INTO menus (id, nama_menu, url, icon, parent_id, urutan) 
            VALUES (?, ?, ?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE nama_menu=VALUES(nama_menu), url=VALUES(url), icon=VALUES(icon), parent_id=VALUES(parent_id), urutan=VALUES(urutan)");

        foreach ($menus as $m) {
            $stmtMenu->execute($m);
        }

        // Get tenants list
        $tenants = $pdo->query("SELECT id FROM tenants")->fetchAll(PDO::FETCH_COLUMN);
        $tenants[] = '00000000-0000-0000-0000-000000000000';
        $tenants = array_unique($tenants);

        // Get roles list (column is nama_role)
        $roles = $pdo->query("SELECT id, nama_role FROM roles")->fetchAll(PDO::FETCH_KEY_PAIR);

        $stmtTMA = $pdo->prepare("INSERT INTO tenant_menu_access (tenant_id, menu_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE menu_id=VALUES(menu_id)");
        $stmtRMA = $pdo->prepare("INSERT INTO role_menu_access (tenant_id, role_id, menu_id) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE menu_id=VALUES(menu_id)");

        $adminMenuIds = range(80, 90);
        $userMenuIds = [90, 91];

        foreach ($tenants as $tid) {
            // Tenant Menu Access
            for ($mid = 80; $mid <= 91; $mid++) {
                try {
                    $stmtTMA->execute([$tid, $mid]);
                } catch (\PDOException $e) {}
            }

            // Role Menu Access
            foreach ($roles as $rid => $rname) {
                $targetMenus = (in_array(strtolower((string)$rname), ['super_admin', 'superadmin', 'admin', 'operator_sekolah', 'pustakawan'], true))
                    ? $adminMenuIds
                    : $userMenuIds;

                foreach ($targetMenus as $mid) {
                    try {
                        $stmtRMA->execute([$tid, $rid, $mid]);
                    } catch (\PDOException $e) {}
                }
            }
        }

        echo "- Menus 80-91 perpustakaan berhasil di-seed beserta hak aksesnya.\n";
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        for ($mid = 80; $mid <= 91; $mid++) {
            $pdo->exec("DELETE FROM role_menu_access WHERE menu_id = {$mid}");
            $pdo->exec("DELETE FROM tenant_menu_access WHERE menu_id = {$mid}");
            $pdo->exec("DELETE FROM menus WHERE id = {$mid}");
        }
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "- Rollback menu perpustakaan 80-91 selesai.\n";
    }
];
