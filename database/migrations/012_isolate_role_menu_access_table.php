<?php
/**
 * Migration 012 - Isolate Role Menu Access Table by Tenant
 */

return [
    'up' => function(PDO $pdo) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec("DROP TABLE IF EXISTS role_menu_access;");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

        // 1. Buat kembali tabel role_menu_access dengan kolom tenant_id untuk isolasi
        $pdo->exec("CREATE TABLE IF NOT EXISTS role_menu_access (
            tenant_id CHAR(36) NOT NULL DEFAULT '00000000-0000-0000-0000-000000000000',
            role_id INT UNSIGNED NOT NULL,
            menu_id INT UNSIGNED NOT NULL,
            PRIMARY KEY (tenant_id, role_id, menu_id),
            CONSTRAINT fk_access_role_id FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE CASCADE,
            CONSTRAINT fk_access_menu_id FOREIGN KEY (menu_id) REFERENCES menus (id) ON DELETE CASCADE
        ) ENGINE=InnoDB;");

        // 2. Re-seed default access map untuk default tenant ('00000000-0000-0000-0000-000000000000')
        $defaultTenant = '00000000-0000-0000-0000-000000000000';
        $records = [];

        // A. Super Admin (role_id = 1) mendapat akses penuh ke menu 1 s.d 18 (kecuali menu 8)
        for ($i = 1; $i <= 18; $i++) {
            if ($i === 8) continue;
            $records[] = "('{$defaultTenant}', 1, $i)";
        }

        // B. Operator Sekolah (role_id = 2) mendapat akses ke seluruh menu kecuali Manajemen User & Hak Akses (id 15) dan menu 8
        for ($i = 1; $i <= 18; $i++) {
            if ($i === 8 || $i === 15) continue;
            $records[] = "('{$defaultTenant}', 2, $i)";
        }

        // C. Guru (role_id = 3) mendapat akses ke Dashboard (1), PPDB & childs (2,3,4), Data Pokok & child (5,6), Registrasi & Mutasi & child (9,11)
        $guruAccess = [1, 2, 3, 4, 5, 6, 9, 11];
        foreach ($guruAccess as $mId) {
            $records[] = "('{$defaultTenant}', 3, $mId)";
        }

        // D. Siswa (role_id = 4) mendapat akses ke Dashboard (1), Data Pokok (5), Pengguna (6)
        $siswaAccess = [1, 5, 6];
        foreach ($siswaAccess as $mId) {
            $records[] = "('{$defaultTenant}', 4, $mId)";
        }

        // E. Pendaftar (role_id = 5) mendapat akses ke Dashboard (1)
        $records[] = "('{$defaultTenant}', 5, 1)";

        // F. Karyawan (role_id = 6) mendapat akses ke Dashboard (1), Data Pokok (5)
        $records[] = "('{$defaultTenant}', 6, 1)";
        $records[] = "('{$defaultTenant}', 6, 5)";

        if (!empty($records)) {
            $sql = "INSERT INTO role_menu_access (tenant_id, role_id, menu_id) VALUES " . implode(", ", $records);
            $pdo->exec($sql);
        }
    },

    'down' => function(PDO $pdo) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec("DROP TABLE IF EXISTS role_menu_access;");
        // Kembalikan ke bentuk asal non-tenant-isolated
        $pdo->exec("CREATE TABLE IF NOT EXISTS role_menu_access (
            role_id INT UNSIGNED NOT NULL,
            menu_id INT UNSIGNED NOT NULL,
            PRIMARY KEY (role_id, menu_id),
            CONSTRAINT fk_access_role_id FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE CASCADE,
            CONSTRAINT fk_access_menu_id FOREIGN KEY (menu_id) REFERENCES menus (id) ON DELETE CASCADE
        ) ENGINE=InnoDB;");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }
];
