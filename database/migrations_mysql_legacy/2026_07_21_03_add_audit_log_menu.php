<?php
/**
 * Migration: Registrasi Menu Audit Trail & Log Security (ID 79) Khusus Super Admin
 */
return [
    'up' => function (PDO $pdo): void {
        // 1. Tambahkan Menu 79 ke tabel menus
        $stmtCheckMenu = $pdo->prepare("SELECT COUNT(*) FROM menus WHERE id = 79");
        $stmtCheckMenu->execute();
        if ($stmtCheckMenu->fetchColumn() == 0) {
            $stmtInsertMenu = $pdo->prepare("
                INSERT INTO menus (id, nama_menu, url, parent_id, icon, urutan) 
                VALUES (79, 'Audit Trail & Log Security', '/SINTA-SaaS/keuangan/audit-log', 70, 'bi bi-shield-check', 9)
            ");
            $stmtInsertMenu->execute();
            echo "- Menu 79 (Audit Trail & Log Security) berhasil ditambahkan.\n";
        }

        // 2. Berikan Hak Akses Menu 79 HANYA untuk Super Admin (role_id = 1) di seluruh tenant
        $tenants = $pdo->query("SELECT id FROM tenants")->fetchAll(PDO::FETCH_COLUMN);
        $tenants[] = '00000000-0000-0000-0000-000000000000';
        $tenants = array_unique($tenants);

        $stmtTMA = $pdo->prepare("INSERT INTO tenant_menu_access (tenant_id, menu_id) VALUES (?, ?)");
        $stmtRMA = $pdo->prepare("INSERT INTO role_menu_access (tenant_id, role_id, menu_id) VALUES (?, 1, ?)");

        foreach ($tenants as $tid) {
            try {
                $stmtTMA->execute([$tid, 79]);
            } catch (\PDOException $e) {}

            try {
                $stmtRMA->execute([$tid, 79]);
            } catch (\PDOException $e) {}
        }
        echo "- Hak akses menu 79 berhasil diberikan secara eksklusif untuk Super Admin.\n";
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("DELETE FROM role_menu_access WHERE menu_id = 79");
        $pdo->exec("DELETE FROM tenant_menu_access WHERE menu_id = 79");
        $pdo->exec("DELETE FROM menus WHERE id = 79");
        echo "- Rollback menu 79 selesai.\n";
    },
];
