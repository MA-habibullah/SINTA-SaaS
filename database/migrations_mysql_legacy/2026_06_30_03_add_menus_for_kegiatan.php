<?php
/**
 * Migration: Add Menus for Kegiatan & Pengumuman
 */
return [
    'up' => function (PDO $pdo): void {
        // 1. Tambah Menu Induk "Informasi & Kegiatan" (id = 45)
        $pdo->exec("
            INSERT IGNORE INTO menus (id, nama_menu, url, icon, parent_id, urutan)
            VALUES (45, 'Informasi & Kegiatan', '#', 'bi bi-calendar-event', NULL, 6)
        ");

        // 2. Tambah Submenu
        $pdo->exec("
            INSERT IGNORE INTO menus (id, nama_menu, url, icon, parent_id, urutan)
            VALUES 
            (46, 'Pengumuman', '/SINTA-SaaS/informasi/pengumuman', 'bi bi-megaphone', 45, 1),
            (47, 'Agenda & Timeline', '/SINTA-SaaS/informasi/agenda', 'bi bi-kanban', 45, 2)
        ");

        // 3. Set Akses untuk Pengumuman (super_admin, admin/operator_sekolah, humas)
        // super_admin = 1, admin = 2, humas = 23
        $rolesPengumuman = [1, 2, 23];
        foreach ($rolesPengumuman as $roleId) {
            $pdo->exec("INSERT IGNORE INTO role_menu_access (role_id, menu_id) VALUES ($roleId, 46)");
            $pdo->exec("INSERT IGNORE INTO role_menu_access (role_id, menu_id) VALUES ($roleId, 45)"); // Akses parent
        }

        // 4. Set Akses untuk Agenda & Timeline (super_admin, admin, kesiswaan, humas, kurikulum, sarpras)
        // super_admin = 1, admin = 2, kesiswaan = 22, humas = 23, kurikulum = 24, sarpras = 25
        $rolesAgenda = [1, 2, 22, 23, 24, 25];
        foreach ($rolesAgenda as $roleId) {
            $pdo->exec("INSERT IGNORE INTO role_menu_access (role_id, menu_id) VALUES ($roleId, 47)");
            $pdo->exec("INSERT IGNORE INTO role_menu_access (role_id, menu_id) VALUES ($roleId, 45)"); // Akses parent
        }

        echo "  OK Menu Informasi & Kegiatan berhasil ditambahkan beserta hak aksesnya.\n";
    },

    'down' => function (PDO $pdo): void {
        // Hapus akses
        $pdo->exec("DELETE FROM role_menu_access WHERE menu_id IN (45, 46, 47)");
        // Hapus menu
        $pdo->exec("DELETE FROM menus WHERE id IN (45, 46, 47)");
        echo "  OK Menu Informasi & Kegiatan berhasil dihapus.\n";
    }
];
