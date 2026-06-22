<?php
/**
 * Migration: Bimbingan Konseling (BK) Module
 *
 * Membuat:
 * 1. Tabel `catatan_bk`     — jurnal/catatan konseling siswa (terenkripsi via AES di app layer)
 * 2. Role `guru_bk`         — role khusus BK (role_id = 20, nilai aman di luar range seed lama)
 * 3. Menu `Bimbingan Konseling` di tabel menus (id = 30)
 * 4. role_menu_access untuk super_admin (1), operator_sekolah (2), dan guru_bk (20)
 * 5. tenant_menu_access untuk default tenant agar RouteGuard lolos
 */
return [

    'up' => function (PDO $pdo): void {

        // ============================================================
        // 1. Tabel catatan_bk
        // ============================================================
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `catatan_bk` (
                `id`              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `id_siswa`        VARCHAR(36)  NOT NULL COMMENT 'UUID referensi ke tabel siswa',
                `tenant_id`       VARCHAR(36)  NOT NULL COMMENT 'UUID referensi ke tabel tenants',
                `id_guru_bk`      VARCHAR(36)  DEFAULT NULL COMMENT 'UUID user guru BK yang mencatat',
                `tanggal_konseling` DATE       NOT NULL,
                `jenis_kasus`     ENUM(
                    'Akademik','Perilaku','Keluarga','Karir','Kesehatan Mental','Lainnya'
                ) NOT NULL DEFAULT 'Lainnya',
                `catatan`         TEXT         NOT NULL COMMENT 'Konten catatan (dapat dienkripsi di app layer)',
                `tindak_lanjut`   TEXT         DEFAULT NULL,
                `status_kasus`    ENUM('Terbuka','Proses','Selesai') NOT NULL DEFAULT 'Terbuka',
                `is_rahasia`      TINYINT(1)   NOT NULL DEFAULT 1 COMMENT '1 = hanya guru BK yg bisa lihat',
                `created_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `deleted_at`      TIMESTAMP    NULL DEFAULT NULL,

                INDEX `idx_catatan_siswa`  (`id_siswa`),
                INDEX `idx_catatan_tenant` (`tenant_id`),
                INDEX `idx_catatan_guru`   (`id_guru_bk`),
                INDEX `idx_catatan_status` (`status_kasus`)

            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
              COMMENT='Jurnal & Rekam Kasus Bimbingan Konseling';
        ");
        echo "  OK Tabel catatan_bk berhasil dibuat.\n";

        // ============================================================
        // 2. Role guru_bk (role_id = 20, pakai INSERT IGNORE)
        // ============================================================
        $pdo->exec("
            INSERT IGNORE INTO roles (id, nama_role, deskripsi)
            VALUES (20, 'guru_bk', 'Guru Bimbingan Konseling — akses modul BK penuh')
        ");
        echo "  OK Role guru_bk berhasil ditambahkan.\n";

        // ============================================================
        // 3. Menu Bimbingan Konseling (id = 30, parent menu baru)
        // ============================================================
        $pdo->exec("
            INSERT INTO menus (id, nama_menu, url, icon, parent_id, urutan)
            VALUES
                (30, 'Bimbingan Konseling', '/SINTA-SaaS/bk', 'bi bi-heart-pulse-fill', NULL, 6)
            ON DUPLICATE KEY UPDATE
                nama_menu = VALUES(nama_menu),
                url       = VALUES(url),
                icon      = VALUES(icon),
                urutan    = VALUES(urutan)
        ");
        echo "  OK Menu Bimbingan Konseling (id=30) berhasil ditambahkan.\n";

        // ============================================================
        // 4. role_menu_access: Super Admin (1), Operator (2), Guru BK (20)
        //    Menggunakan defaultTenant = 00000000-...
        // ============================================================
        $defaultTenant = '00000000-0000-0000-0000-000000000000';
        $pdo->exec("
            INSERT IGNORE INTO role_menu_access (tenant_id, role_id, menu_id) VALUES
                ('{$defaultTenant}', 1,  30),
                ('{$defaultTenant}', 2,  30),
                ('{$defaultTenant}', 20, 30)
        ");
        echo "  OK role_menu_access untuk BK berhasil ditambahkan.\n";

        // ============================================================
        // 5. tenant_menu_access untuk SEMUA tenant yang sudah ada
        //    agar RouteGuard::check() tidak memblokir menu BK
        // ============================================================
        $tenants = $pdo->query("SELECT id FROM tenants WHERE deleted_at IS NULL")->fetchAll(\PDO::FETCH_COLUMN);
        if (!empty($tenants)) {
            $rows = array_map(fn($tid) => "('$tid', 30)", $tenants);
            $pdo->exec("
                INSERT IGNORE INTO tenant_menu_access (tenant_id, menu_id) VALUES
                " . implode(',', $rows)
            );
            echo "  OK tenant_menu_access untuk BK berhasil disinkron ke " . count($tenants) . " sekolah.\n";
        }
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec("DROP TABLE IF EXISTS `catatan_bk`;");
        $pdo->exec("DELETE FROM role_menu_access WHERE menu_id = 30;");
        $pdo->exec("DELETE FROM tenant_menu_access WHERE menu_id = 30;");
        $pdo->exec("DELETE FROM menus WHERE id = 30;");
        $pdo->exec("DELETE FROM roles WHERE id = 20;");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "  OK Rollback modul Bimbingan Konseling selesai.\n";
    },
];
