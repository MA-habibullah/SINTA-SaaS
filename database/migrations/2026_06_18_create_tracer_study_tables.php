<?php
/**
 * Migration: Tracer Study — Riwayat Kuliah, Riwayat Pekerjaan, Target Kampus & Menu PDSS
 * Jalankan via CLI: php migrate.php up
 */
return [

    'up' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

        // ======================================================
        // TABEL 1: riwayat_kuliah (Riwayat Perguruan Tinggi)
        // ======================================================
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `riwayat_kuliah` (
                `id`            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `id_siswa`      VARCHAR(36)  DEFAULT NULL COMMENT 'UUID referensi ke tabel siswa, NULL jika manual input BK',
                `tenant_id`     VARCHAR(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'UUID referensi ke tabel tenants',
                `nama_alumni`   VARCHAR(255) DEFAULT NULL COMMENT 'Diisi jika id_siswa NULL (input manual alumni)',
                `nama_kampus`   VARCHAR(255) NOT NULL,
                `fakultas`      VARCHAR(255) DEFAULT NULL,
                `jurusan`       VARCHAR(255) DEFAULT NULL,
                `tahun_masuk`   SMALLINT     NOT NULL,
                `tahun_lulus`   SMALLINT     DEFAULT NULL,
                `jenis_kampus`  ENUM('Negeri', 'Swasta', 'Kedinasan') NOT NULL DEFAULT 'Negeri',
                `jalur_masuk`   ENUM('SNBP', 'SNBT', 'Mandiri', 'Beasiswa', 'Jalur Swasta', 'Kedinasan', 'Lainnya') NOT NULL DEFAULT 'Lainnya',
                `status_kuliah` ENUM('Aktif','Lulus','Drop') NOT NULL DEFAULT 'Aktif',
                `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                INDEX `idx_rk_id_siswa`  (`id_siswa`),
                INDEX `idx_rk_tenant_id` (`tenant_id`),
                CONSTRAINT `fk_rk_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
              COMMENT='Tracer Study: Riwayat Pendidikan Tinggi Alumni';
        ");

        // ======================================================
        // TABEL 2: riwayat_pekerjaan (Riwayat Karir Alumni)
        // ======================================================
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `riwayat_pekerjaan` (
                `id`                 BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `id_siswa`           VARCHAR(36)    NOT NULL COMMENT 'UUID referensi ke tabel siswa',
                `tenant_id`          VARCHAR(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'UUID referensi ke tabel tenants',
                `nama_perusahaan`    VARCHAR(255)   NOT NULL,
                `posisi_jabatan`     VARCHAR(255)   NOT NULL,
                `pendapatan_bulanan` VARCHAR(100)   DEFAULT NULL,
                `tahun_mulai`        SMALLINT       NOT NULL,
                `tahun_selesai`      SMALLINT       DEFAULT NULL,
                `status_kerja`       ENUM('Kontrak','Tetap','Magang') NOT NULL DEFAULT 'Kontrak',
                `created_at`         TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at`         TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                INDEX `idx_rp_id_siswa`  (`id_siswa`),
                INDEX `idx_rp_tenant_id` (`tenant_id`),
                CONSTRAINT `fk_rp_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
              COMMENT='Tracer Study: Riwayat Karir/Pekerjaan Alumni';
        ");

        // ======================================================
        // TABEL 3: target_kampus (Konfigurasi Target Kampus & Quotas)
        // ======================================================
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `target_kampus` (
                `id`           CHAR(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL PRIMARY KEY,
                `tenant_id`    CHAR(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'UUID referensi ke tabel tenants',
                `nama_kampus`  VARCHAR(255) NOT NULL,
                `jenis_kampus` ENUM('Negeri', 'Swasta', 'Kedinasan') NOT NULL,
                `kuota_target` INT          NOT NULL DEFAULT 0,
                `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                INDEX `idx_tk_tenant_id` (`tenant_id`),
                CONSTRAINT `fk_tk_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Master & Target Kuota Kampus per Sekolah';
        ");

        // Seed Menu PDSS & Alumni (id = 40)
        $pdo->exec("
            INSERT INTO menus (id, nama_menu, url, icon, parent_id, urutan)
            VALUES (40, 'PDSS & Alumni', '/SINTA-SaaS/pdss/kesiapan', 'bi bi-database-fill', NULL, 7)
            ON DUPLICATE KEY UPDATE
                nama_menu = VALUES(nama_menu),
                url       = VALUES(url),
                icon      = VALUES(icon),
                urutan    = VALUES(urutan)
        ");

        // Seed default access mapping for default tenant
        $defaultTenant = '00000000-0000-0000-0000-000000000000';
        $pdo->exec("
            INSERT IGNORE INTO role_menu_access (tenant_id, role_id, menu_id) VALUES
                ('{$defaultTenant}', 1,  40), -- super_admin
                ('{$defaultTenant}', 2,  40), -- operator_sekolah
                ('{$defaultTenant}', 3,  40), -- guru
                ('{$defaultTenant}', 4,  40), -- siswa
                ('{$defaultTenant}', 20, 40)  -- guru_bk
        ");

        // Seed tenant_menu_access for ALL active tenants in DB
        $tenants = $pdo->query("SELECT id FROM tenants WHERE deleted_at IS NULL")->fetchAll(PDO::FETCH_COLUMN);
        if (!empty($tenants)) {
            $rows = array_map(fn($tid) => "('$tid', 40)", $tenants);
            $pdo->exec("
                INSERT IGNORE INTO tenant_menu_access (tenant_id, menu_id) VALUES
                " . implode(',', $rows)
            );
        }

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

        echo "  OK Tabel riwayat_kuliah berhasil disesuaikan.\n";
        echo "  OK Tabel riwayat_pekerjaan berhasil disesuaikan.\n";
        echo "  OK Tabel target_kampus berhasil dibuat.\n";
        echo "  OK Menu PDSS & Alumni berhasil ditambahkan.\n";
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec("DROP TABLE IF EXISTS `target_kampus`;");
        $pdo->exec("DROP TABLE IF EXISTS `riwayat_pekerjaan`;");
        $pdo->exec("DROP TABLE IF EXISTS `riwayat_kuliah`;");
        $pdo->exec("DELETE FROM role_menu_access WHERE menu_id = 40;");
        $pdo->exec("DELETE FROM tenant_menu_access WHERE menu_id = 40;");
        $pdo->exec("DELETE FROM menus WHERE id = 40;");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

        echo "  ✓ Rollback: Tabel riwayat_kuliah, riwayat_pekerjaan, target_kampus & Menu PDSS & Alumni dihapus.\n";
    },
];
