<?php
return [
    'up' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

        // 1. Insert Dashboard menu under Perpustakaan (parent_id = 80)
        $pdo->exec("INSERT INTO `menus` (`nama_menu`, `url`, `icon`, `parent_id`, `urutan`) 
            VALUES ('Dashboard', '/SINTA-SaaS/perpustakaan', 'bi bi-grid-fill', 80, 0)");
        
        $newMenuId = $pdo->lastInsertId();

        // 2. Copy tenant menu access from Katalog menu (id = 81)
        $stmtTma = $pdo->prepare("INSERT INTO `tenant_menu_access` (`tenant_id`, `menu_id`)
            SELECT `tenant_id`, :new_menu_id FROM `tenant_menu_access` WHERE `menu_id` = 81");
        $stmtTma->execute(['new_menu_id' => $newMenuId]);

        // 3. Copy role menu access from Katalog menu (id = 81)
        $stmtRma = $pdo->prepare("INSERT INTO `role_menu_access` (`tenant_id`, `role_id`, `menu_id`)
            SELECT `tenant_id`, `role_id`, :new_menu_id FROM `role_menu_access` WHERE `menu_id` = 81");
        $stmtRma->execute(['new_menu_id' => $newMenuId]);

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "- Menu Dashboard Perpustakaan berhasil ditambahkan dan dikonfigurasi hak aksesnya.\n";
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

        // 1. Find and delete the Dashboard menu under Perpustakaan
        $stmtFind = $pdo->prepare("SELECT id FROM `menus` WHERE `nama_menu` = 'Dashboard' AND `parent_id` = 80 LIMIT 1");
        $stmtFind->execute();
        $menuId = $stmtFind->fetchColumn();

        if ($menuId) {
            $pdo->exec("DELETE FROM `role_menu_access` WHERE `menu_id` = " . (int)$menuId);
            $pdo->exec("DELETE FROM `tenant_menu_access` WHERE `menu_id` = " . (int)$menuId);
            $pdo->exec("DELETE FROM `menus` WHERE `id` = " . (int)$menuId);
        }

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "- Rollback menu Dashboard Perpustakaan selesai.\n";
    }
];
