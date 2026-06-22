<?php
/**
 * Migration 007 - Create Menus and Access Tables
 */

return [
    'up' => function(PDO $pdo) {
        // 1. Buat tabel menus
        $pdo->exec("CREATE TABLE IF NOT EXISTS menus (
            id INT UNSIGNED AUTO_INCREMENT NOT NULL,
            nama_menu VARCHAR(100) NOT NULL,
            url VARCHAR(255) DEFAULT NULL,
            icon VARCHAR(100) DEFAULT NULL,
            parent_id INT UNSIGNED DEFAULT NULL,
            urutan INT UNSIGNED NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            CONSTRAINT fk_menus_parent_id FOREIGN KEY (parent_id) REFERENCES menus (id) ON DELETE CASCADE
        ) ENGINE=InnoDB;");

        // 2. Buat tabel role_menu_access (Pivot Access)
        $pdo->exec("CREATE TABLE IF NOT EXISTS role_menu_access (
            role_id INT UNSIGNED NOT NULL,
            menu_id INT UNSIGNED NOT NULL,
            PRIMARY KEY (role_id, menu_id),
            CONSTRAINT fk_access_role_id FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE CASCADE,
            CONSTRAINT fk_access_menu_id FOREIGN KEY (menu_id) REFERENCES menus (id) ON DELETE CASCADE
        ) ENGINE=InnoDB;");

        // 3. Seed master data menus (Parent & Child structure)
        $pdo->exec("INSERT INTO menus (id, nama_menu, url, icon, parent_id, urutan) VALUES
            (1, 'Dashboard', '/SINTA-SaaS/dashboard', 'bi bi-grid-fill', NULL, 1),
            
            (2, 'PPDB / Penerimaan Siswa Baru', '#', 'bi bi-clipboard-check', NULL, 2),
            (3, 'Verifikasi Pendaftaran', '#', 'bi bi-check2-square', 2, 1),
            (4, 'Kelola Calon Siswa', '#', 'bi bi-person-plus', 2, 2),
            
            (5, 'Data Pokok / Core Dapodik', '#', 'bi bi-folder2-open', NULL, 3),
            (6, 'Pengguna', '/SINTA-SaaS/pengguna', 'bi bi-people', 5, 1),
            (7, 'Master Data', '/SINTA-SaaS/master-data', 'bi bi-diagram-3', 5, 2),
            
            (9, 'Registrasi & Mutasi', '#', 'bi bi-arrow-left-right', NULL, 4),
            (10, 'Riwayat Jalur PPDB', '#', 'bi bi-clock-history', 9, 1),
            (11, 'Kelola Kelulusan & Alumni', '#', 'bi bi-mortarboard', 9, 2),
            (12, 'Log Mutasi & Putus Sekolah', '#', 'bi bi-person-x', 9, 3),
            
            (13, 'Sistem & Utilitas', '#', 'bi bi-shield-lock-fill', NULL, 5),
            (14, 'Identitas Sekolah', '#', 'bi bi-info-circle', 13, 1),
            (15, 'Manajemen User & Hak Akses', '/SINTA-SaaS/konfigurasi/akses', 'bi bi-shield-lock-fill', 13, 2),
            (16, 'Monitoring Sesi Aktif', '#', 'bi bi-clock', 13, 3),
            (17, 'Antrean Sistem & Background Jobs', '#', 'bi bi-cpu', 13, 4)
        ON DUPLICATE KEY UPDATE nama_menu=VALUES(nama_menu), url=VALUES(url), icon=VALUES(icon), parent_id=VALUES(parent_id), urutan=VALUES(urutan);");

        // 4. Seed default access mappings (Roles seeded in Migration 006: 1:super_admin, 2:operator_sekolah, 3:guru, 4:siswa)
        
        // A. Super Admin (role_id = 1) mendapat akses penuh ke menu 1 s.d 17 (kecuali menu id 8 yang telah dihapus)
        $superAdminAccess = [];
        for ($i = 1; $i <= 17; $i++) {
            if ($i === 8) continue;
            $superAdminAccess[] = "(1, $i)";
        }
        $pdo->exec("INSERT INTO role_menu_access (role_id, menu_id) VALUES " . implode(", ", $superAdminAccess) . " ON DUPLICATE KEY UPDATE role_id=VALUES(role_id);");

        // B. Operator Sekolah (role_id = 2) mendapat akses ke seluruh menu kecuali Manajemen User & Hak Akses (id 15) dan Mata Pelajaran (id 8)
        $operatorAccess = [];
        for ($i = 1; $i <= 17; $i++) {
            if ($i === 15 || $i === 8) continue;
            $operatorAccess[] = "(2, $i)";
        }
        $pdo->exec("INSERT INTO role_menu_access (role_id, menu_id) VALUES " . implode(", ", $operatorAccess) . " ON DUPLICATE KEY UPDATE role_id=VALUES(role_id);");

        // C. Guru (role_id = 3) mendapat akses ke Dashboard, PPDB & childs, Data Pokok & childs (tanpa mapel), Registrasi & Mutasi & childs
        $guruAccess = ["(3, 1)", "(3, 2)", "(3, 3)", "(3, 4)", "(3, 5)", "(3, 6)", "(3, 9)", "(3, 11)"];
        $pdo->exec("INSERT INTO role_menu_access (role_id, menu_id) VALUES " . implode(", ", $guruAccess) . " ON DUPLICATE KEY UPDATE role_id=VALUES(role_id);");

        // D. Siswa (role_id = 4) mendapat akses ke Dashboard, Data Pokok (parent), Data Siswa
        $siswaAccess = ["(4, 1)", "(4, 5)", "(4, 6)"];
        $pdo->exec("INSERT INTO role_menu_access (role_id, menu_id) VALUES " . implode(", ", $siswaAccess) . " ON DUPLICATE KEY UPDATE role_id=VALUES(role_id);");
    },

    'down' => function(PDO $pdo) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec("DROP TABLE IF EXISTS role_menu_access;");
        $pdo->exec("DROP TABLE IF EXISTS menus;");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }
];
