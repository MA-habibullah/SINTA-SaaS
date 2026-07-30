<?php
/**
 * Migration: Restrukturisasi Menu Sidebar
 * 
 * 1. Memindahkan submenu 'Riwayat Jalur PPDB' (ID 10) ke menu 'PPDB / Penerimaan Siswa Baru' (ID 2).
 * 2. Menghapus kategori menu induk 'Registrasi & Mutasi' (ID 9) beserta sub-menunya ('Kelola Kelulusan & Alumni' dan 'Log Mutasi & Putus Sekolah') dari sidebar.
 */
return [

    'up' => function (PDO $pdo): void {
        // 1. Pindahkan Riwayat Jalur PPDB ke menu induk PPDB (parent_id = 2, urutan = 3)
        $pdo->exec("
            UPDATE `menus` 
            SET `parent_id` = 2, `urutan` = 3 
            WHERE `id` = 10;
        ");
        echo "  OK Riwayat Jalur PPDB berhasil dipindahkan ke menu PPDB.\n";

        // 2. Hapus menu Registrasi & Mutasi (menghapus menu anaknya: ID 11 & 12 via cascade delete)
        $pdo->exec("
            DELETE FROM `menus` 
            WHERE `id` = 9;
        ");
        echo "  OK Menu Registrasi & Mutasi berhasil dihapus dari sidebar.\n";
    },

    'down' => function (PDO $pdo): void {
        // 1. Buat kembali menu induk Registrasi & Mutasi (ID 9)
        $pdo->exec("
            INSERT INTO `menus` (`id`, `nama_menu`, `url`, `icon`, `parent_id`, `urutan`)
            VALUES (9, 'Registrasi & Mutasi', '#', 'bi bi-arrow-left-right', NULL, 4)
            ON DUPLICATE KEY UPDATE `nama_menu`=VALUES(`nama_menu`), `url`=VALUES(`url`), `icon`=VALUES(`icon`), `parent_id`=VALUES(`parent_id`), `urutan`=VALUES(`urutan`);
        ");

        // 2. Buat kembali submenu Kelola Kelulusan & Alumni (ID 11) dan Log Mutasi & Putus Sekolah (ID 12)
        $pdo->exec("
            INSERT INTO `menus` (`id`, `nama_menu`, `url`, `icon`, `parent_id`, `urutan`)
            VALUES 
                (11, 'Kelola Kelulusan & Alumni', '#', 'bi bi-mortarboard', 9, 2),
                (12, 'Log Mutasi & Putus Sekolah', '#', 'bi bi-person-x', 9, 3)
            ON DUPLICATE KEY UPDATE `nama_menu`=VALUES(`nama_menu`), `url`=VALUES(`url`), `icon`=VALUES(`icon`), `parent_id`=VALUES(`parent_id`), `urutan`=VALUES(`urutan`);
        ");

        // 3. Kembalikan Riwayat Jalur PPDB ke parent Registrasi & Mutasi (parent_id = 9, urutan = 1)
        $pdo->exec("
            UPDATE `menus` 
            SET `parent_id` = 9, `urutan` = 1 
            WHERE `id` = 10;
        ");

        // 4. Pastikan mapping role_menu_access untuk menu 9, 11, 12 dibuat kembali untuk Super Admin (1), Operator (2), Guru (3)
        $pdo->exec("
            INSERT IGNORE INTO `role_menu_access` (`role_id`, `menu_id`)
            VALUES 
                (1, 9), (1, 11), (1, 12),
                (2, 9), (2, 11), (2, 12),
                (3, 9), (3, 11);
        ");

        echo "  OK Rollback menu Registrasi & Mutasi selesai.\n";
    },
];
