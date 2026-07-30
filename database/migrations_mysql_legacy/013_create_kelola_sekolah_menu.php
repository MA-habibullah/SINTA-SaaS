<?php
/**
 * Migration 013 - Create Kelola Sekolah Menu & Access Map for Super Admin
 */

return [
    'up' => function(PDO $pdo) {
        // 1. Masukkan menu baru "Kelola Sekolah" (id 19) ke tabel menus di bawah parent_id 13 (Sistem & Utilitas)
        $pdo->exec("INSERT INTO menus (id, nama_menu, url, icon, parent_id, urutan) VALUES
            (19, 'Kelola Sekolah', '/SINTA-SaaS/super-admin/tenants', 'bi bi-building-gear', 13, 6)
        ON DUPLICATE KEY UPDATE nama_menu=VALUES(nama_menu), url=VALUES(url), icon=VALUES(icon), parent_id=VALUES(parent_id), urutan=VALUES(urutan);");

        // 2. Daftarkan menu baru ini ke hak akses Super Admin (role_id = 1) untuk default tenant
        $pdo->exec("INSERT INTO role_menu_access (tenant_id, role_id, menu_id) VALUES 
            ('00000000-0000-0000-0000-000000000000', 1, 19)
        ON DUPLICATE KEY UPDATE tenant_id=VALUES(tenant_id);");
    },

    'down' => function(PDO $pdo) {
        $pdo->exec("DELETE FROM role_menu_access WHERE menu_id = 19;");
        $pdo->exec("DELETE FROM menus WHERE id = 19;");
    }
];
