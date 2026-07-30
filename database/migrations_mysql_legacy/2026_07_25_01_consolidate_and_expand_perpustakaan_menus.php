<?php
/**
 * Migration: Konsolidasi Menu Perpustakaan & Pembuatan Tabel Usulan Buku
 */
return [
    'up' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

        // 1. Rename & Re-icon menu utama perpustakaan
        $pdo->exec("UPDATE `menus` SET `nama_menu` = 'Katalog & Inventori', `icon` = 'bi bi-journal-album', `urutan` = 1 WHERE `id` = 81");
        $pdo->exec("UPDATE `menus` SET `nama_menu` = 'Sirkulasi & Layanan', `icon` = 'bi bi-arrow-repeat', `urutan` = 2 WHERE `id` = 82");
        $pdo->exec("UPDATE `menus` SET `nama_menu` = 'Administrasi & Keanggotaan', `icon` = 'bi bi-people-fill', `urutan` = 3 WHERE `id` = 85");

        // 2. Hapus menu redundan
        $pdo->exec("DELETE FROM `role_menu_access` WHERE `menu_id` IN (83, 84, 86, 87, 88, 89)");
        $pdo->exec("DELETE FROM `tenant_menu_access` WHERE `menu_id` IN (83, 84, 86, 87, 88, 89)");
        $pdo->exec("DELETE FROM `menus` WHERE `id` IN (83, 84, 86, 87, 88, 89)");

        // 3. Buat tabel perpus_usulan_buku
        $pdo->exec("CREATE TABLE IF NOT EXISTS `perpus_usulan_buku` (
            `id`             CHAR(36) NOT NULL DEFAULT (UUID()),
            `tenant_id`      CHAR(36) NOT NULL,
            `judul`          VARCHAR(255) NOT NULL,
            `pengarang`      VARCHAR(255) DEFAULT NULL,
            `penerbit`       VARCHAR(255) DEFAULT NULL,
            `pengusul_nama`  VARCHAR(255) NOT NULL,
            `tanggal_usulan` DATE NOT NULL,
            `status`         ENUM('Diajukan','Disetujui','Ditolak','Sudah Dibeli') DEFAULT 'Diajukan',
            `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "- Konsolidasi menu perpustakaan & tabel usulan buku berhasil dibuat.\n";
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

        // Hapus tabel usulan buku
        $pdo->exec("DROP TABLE IF EXISTS `perpus_usulan_buku`;");

        // Kembalikan nama menu
        $pdo->exec("UPDATE `menus` SET `nama_menu` = 'Katalog & Koleksi', `icon` = 'bi bi-book', `urutan` = 1 WHERE `id` = 81");
        $pdo->exec("UPDATE `menus` SET `nama_menu` = 'Sirkulasi Reguler', `icon` = 'bi bi-arrow-repeat', `urutan` = 2 WHERE `id` = 82");
        $pdo->exec("UPDATE `menus` SET `nama_menu` = 'Keanggotaan & Bebas Pustaka', `icon` = 'bi bi-people', `urutan` = 5 WHERE `id` = 85");

        // Daftar ulang menu redundan
        $menus = [
            [83, 'Buku Paket', '/SINTA-SaaS/perpustakaan/buku-paket', 'bi bi-box-seam', 80, 3],
            [84, 'Event Khusus', '/SINTA-SaaS/perpustakaan/event', 'bi bi-trophy', 80, 4],
            [86, 'Denda & Billing', '/SINTA-SaaS/perpustakaan/denda', 'bi bi-cash-coin', 80, 6],
            [87, 'Stock Opname', '/SINTA-SaaS/perpustakaan/opname', 'bi bi-qr-code-scan', 80, 7],
            [88, 'Laporan Perpustakaan', '/SINTA-SaaS/perpustakaan/laporan', 'bi bi-file-earmark-bar-graph', 80, 8],
            [89, 'Pengaturan Perpustakaan', '/SINTA-SaaS/perpustakaan/pengaturan', 'bi bi-gear', 80, 9]
        ];

        $stmtMenu = $pdo->prepare("INSERT INTO `menus` (id, nama_menu, url, icon, parent_id, urutan) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($menus as $m) {
            try {
                $stmtMenu->execute($m);
            } catch (\PDOException $e) {}
        }

        // Kembalikan hak akses
        $tenants = $pdo->query("SELECT id FROM tenants")->fetchAll(PDO::FETCH_COLUMN);
        $tenants[] = '00000000-0000-0000-0000-000000000000';
        $tenants = array_unique($tenants);
        $roles = $pdo->query("SELECT id, nama_role FROM roles")->fetchAll(PDO::FETCH_KEY_PAIR);

        $stmtTMA = $pdo->prepare("INSERT INTO tenant_menu_access (tenant_id, menu_id) VALUES (?, ?)");
        $stmtRMA = $pdo->prepare("INSERT INTO role_menu_access (tenant_id, role_id, menu_id) VALUES (?, ?, ?)");

        foreach ($tenants as $tid) {
            foreach ([83, 84, 86, 87, 88, 89] as $mid) {
                try { $stmtTMA->execute([$tid, $mid]); } catch (\PDOException $e) {}
                foreach ($roles as $rid => $rname) {
                    if (in_array(strtolower((string)$rname), ['super_admin', 'superadmin', 'admin', 'operator_sekolah', 'pustakawan'], true)) {
                        try { $stmtRMA->execute([$tid, $rid, $mid]); } catch (\PDOException $e) {}
                    }
                }
            }
        }

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "- Rollback konsolidasi menu perpustakaan selesai.\n";
    }
];
