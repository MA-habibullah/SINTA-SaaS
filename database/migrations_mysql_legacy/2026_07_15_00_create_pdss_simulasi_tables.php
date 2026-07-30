<?php
/**
 * Migration: Tabel Simulasi Pemilihan Kampus & Prodi PDSS
 *
 * Membuat 2 tabel baru:
 * 1. pdss_simulasi       — Pilihan kampus+prodi siswa per tahun ajaran per nomor simulasi
 * 2. pdss_simulasi_setting — Kontrol buka/tutup/kunci tiap fase simulasi oleh BK
 */
return [
    'up' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

        // ============================================================
        // 1. Tabel pdss_simulasi
        //    Menyimpan pilihan aktif kampus+prodi siswa
        //    1 baris per siswa per tahun_ajaran per no_simulasi
        // ============================================================
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `pdss_simulasi` (
                `id`                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `tenant_id`         CHAR(36)        NOT NULL,
                `siswa_id`          CHAR(36)        NOT NULL,
                `tahun_ajaran_id`   INT UNSIGNED    NOT NULL,
                `no_simulasi`       TINYINT(1)      NOT NULL COMMENT '1, 2, atau 3',

                -- Pilihan 1
                `kampus_id_1`       CHAR(36)        DEFAULT NULL COMMENT 'FK ke master_kampus',
                `prodi_id_1`        CHAR(36)        DEFAULT NULL COMMENT 'FK ke master_kampus_prodi',
                `nama_kampus_1`     VARCHAR(255)    DEFAULT NULL COMMENT 'Snapshot nama kampus saat input',
                `nama_prodi_1`      VARCHAR(255)    DEFAULT NULL COMMENT 'Snapshot nama prodi saat input',

                -- Pilihan 2
                `kampus_id_2`       CHAR(36)        DEFAULT NULL,
                `prodi_id_2`        CHAR(36)        DEFAULT NULL,
                `nama_kampus_2`     VARCHAR(255)    DEFAULT NULL,
                `nama_prodi_2`      VARCHAR(255)    DEFAULT NULL,

                -- Bukti upload (khusus Simulasi 3)
                `bukti_file`        VARCHAR(512)    DEFAULT NULL COMMENT 'Path relatif file bukti',
                `bukti_filename`    VARCHAR(255)    DEFAULT NULL COMMENT 'Nama file asli',
                `bukti_uploaded_at` TIMESTAMP       NULL DEFAULT NULL,

                -- Metadata
                `catatan_siswa`     TEXT            DEFAULT NULL,
                `catatan_bk`        TEXT            DEFAULT NULL,
                `diisi_oleh`        ENUM('Siswa','Guru BK','Admin') NOT NULL DEFAULT 'Siswa',
                `status`            ENUM('draft','submitted','dikunci') NOT NULL DEFAULT 'draft',

                `created_at`        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at`        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `deleted_at`        TIMESTAMP       NULL DEFAULT NULL,

                UNIQUE KEY `uk_sim_siswa_ta_no` (`tenant_id`, `siswa_id`, `tahun_ajaran_id`, `no_simulasi`),
                INDEX `idx_sim_tenant_ta`       (`tenant_id`, `tahun_ajaran_id`),
                INDEX `idx_sim_siswa`           (`siswa_id`),
                INDEX `idx_sim_no`              (`no_simulasi`),
                INDEX `idx_sim_kampus1`         (`kampus_id_1`, `prodi_id_1`),
                INDEX `idx_sim_kampus2`         (`kampus_id_2`, `prodi_id_2`)

            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
              COMMENT='Simulasi Pilihan Kampus & Prodi Siswa per Tahun Ajaran';
        ");
        echo "  OK Tabel pdss_simulasi berhasil dibuat.\n";

        // ============================================================
        // 2. Tabel pdss_simulasi_setting
        //    Kontrol buka/tutup/kunci tiap fase simulasi (1, 2, 3)
        //    BK bisa membuka/menutup/mengunci secara independen
        // ============================================================
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `pdss_simulasi_setting` (
                `id`              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
                `tenant_id`       CHAR(36)        NOT NULL,
                `tahun_ajaran_id` INT UNSIGNED    NOT NULL,
                `no_simulasi`     TINYINT(1)      NOT NULL COMMENT '1, 2, atau 3',

                `is_open`         TINYINT(1)      NOT NULL DEFAULT 0 COMMENT '1 = pengisian terbuka',
                `is_locked`       TINYINT(1)      NOT NULL DEFAULT 0 COMMENT '1 = dikunci permanen',

                `dibuka_oleh`     VARCHAR(255)    DEFAULT NULL,
                `dibuka_at`       TIMESTAMP       NULL DEFAULT NULL,
                `ditutup_oleh`    VARCHAR(255)    DEFAULT NULL,
                `ditutup_at`      TIMESTAMP       NULL DEFAULT NULL,
                `dikunci_oleh`    VARCHAR(255)    DEFAULT NULL,
                `dikunci_at`      TIMESTAMP       NULL DEFAULT NULL,

                `created_at`      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at`      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                UNIQUE KEY `uk_sss_tenant_ta_no` (`tenant_id`, `tahun_ajaran_id`, `no_simulasi`),
                INDEX `idx_sss_tenant` (`tenant_id`)

            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
              COMMENT='Setting buka/tutup/kunci tiap fase simulasi PDSS per tahun ajaran';
        ");
        echo "  OK Tabel pdss_simulasi_setting berhasil dibuat.\n";

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "  OK Migration Simulasi PDSS selesai.\n";
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec("DROP TABLE IF EXISTS `pdss_simulasi`;");
        $pdo->exec("DROP TABLE IF EXISTS `pdss_simulasi_setting`;");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "  OK Rollback: Tabel pdss_simulasi & pdss_simulasi_setting dihapus.\n";
    }
];
