<?php
/**
 * Migration: Konsolidasi Menu Keuangan & SPP ke Master Keuangan Berbasis Navtabs
 */
return [
    'up' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

        // 1. Rename menu ID 72 menjadi Master Keuangan
        $stmtUpdate = $pdo->prepare("UPDATE `menus` SET `nama_menu` = 'Master Keuangan', `icon` = 'bi bi-tags' WHERE `id` = 72");
        $stmtUpdate->execute();

        // 2. Hapus data akses menu lama (73, 74, 77) agar tidak muncul di sidebar
        $pdo->exec("DELETE FROM `role_menu_access` WHERE `menu_id` IN (73, 74, 77)");
        $pdo->exec("DELETE FROM `tenant_menu_access` WHERE `menu_id` IN (73, 74, 77)");
        $pdo->exec("DELETE FROM `menus` WHERE `id` IN (73, 74, 77)");

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "- Menu keuangan berhasil dikonsolidasikan ke Master Keuangan (ID 72).\n";
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

        // Kembalikan nama menu 72
        $pdo->exec("UPDATE `menus` SET `nama_menu` = 'Atur Tarif & Biaya', `icon` = 'bi bi-tags' WHERE `id` = 72");

        // Daftarkan ulang menu 73, 74, 77 jika belum ada
        $menus = [
            [73, 'Keringanan & Beasiswa', '/SINTA-SaaS/keuangan/keringanan', 70, 'bi bi-award', 3],
            [74, 'Generate Tagihan', '/SINTA-SaaS/keuangan/generate', 70, 'bi bi-file-earmark-plus', 4],
            [77, 'Pengaturan Keuangan', '/SINTA-SaaS/keuangan/pengaturan', 70, 'bi bi-gear', 7]
        ];

        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM `menus` WHERE `id` = ?");
        $stmtMenu = $pdo->prepare("INSERT INTO `menus` (id, nama_menu, url, parent_id, icon, urutan) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($menus as $m) {
            $stmtCheck->execute([$m[0]]);
            if ($stmtCheck->fetchColumn() == 0) {
                try {
                    $stmtMenu->execute($m);
                } catch (\PDOException $e) {}
            }
        }

        // Kembalikan akses default untuk role admin, operator, kepala sekolah (1, 2, 26)
        $tenants = $pdo->query("SELECT id FROM tenants")->fetchAll(PDO::FETCH_COLUMN);
        $tenants[] = '00000000-0000-0000-0000-000000000000';
        $tenants = array_unique($tenants);

        $stmtTMA = $pdo->prepare("INSERT INTO tenant_menu_access (tenant_id, menu_id) VALUES (?, ?)");
        $stmtRMA = $pdo->prepare("INSERT INTO role_menu_access (tenant_id, role_id, menu_id) VALUES (?, ?, ?)");

        foreach ($tenants as $tid) {
            foreach ([73, 74, 77] as $mid) {
                try { $stmtTMA->execute([$tid, $mid]); } catch (\PDOException $e) {}
                foreach ([1, 2, 26] as $rid) {
                    try { $stmtRMA->execute([$tid, $rid, $mid]); } catch (\PDOException $e) {}
                }
            }
        }

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "- Rollback konsolidasi menu selesai.\n";
    },
];
