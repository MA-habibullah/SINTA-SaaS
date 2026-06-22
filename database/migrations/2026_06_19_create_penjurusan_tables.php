<?php
/**
 * Migration: Tabel Pilihan Penjurusan Mandiri (BK Module - Tab 2)
 *
 * Menyimpan pilihan jurusan yang diajukan siswa (mandiri).
 * Admin BK / Guru BK dapat memverifikasi, menolak, atau meng-override pilihan.
 * Setiap perubahan di-audit di tabel pilihan_penjurusan_log.
 */
return [

    'up' => function (PDO $pdo): void {

        // ============================================================
        // 1. Tabel pilihan_penjurusan
        //    Menyimpan pilihan AKTIF (1 baris per siswa per periode)
        // ============================================================
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `pilihan_penjurusan` (
                `id`              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `id_siswa`        VARCHAR(36)     NOT NULL,
                `tenant_id`       VARCHAR(36)     NOT NULL,
                `id_jurusan`      INT UNSIGNED    NOT NULL,
                `id_tahun_ajaran` INT UNSIGNED    DEFAULT NULL COMMENT 'FK ke tahun_ajaran (opsional)',
                `status`          ENUM(
                    'Diajukan','Diverifikasi','Ditolak','Override_BK'
                ) NOT NULL DEFAULT 'Diajukan',
                `catatan_bk`      TEXT            DEFAULT NULL COMMENT 'Catatan Guru BK saat verifikasi/override',
                `dikunci`         TINYINT(1)      NOT NULL DEFAULT 0 COMMENT '1 = terkunci, tidak bisa diubah siswa',
                `diajukan_oleh`   ENUM('Siswa','Guru BK','Admin') NOT NULL DEFAULT 'Siswa',
                `diproses_oleh`   VARCHAR(36)     DEFAULT NULL COMMENT 'user_id Guru BK/Admin yang memproses',
                `diproses_at`     TIMESTAMP       NULL DEFAULT NULL,
                `created_at`      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at`      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `deleted_at`      TIMESTAMP       NULL DEFAULT NULL,

                UNIQUE KEY  `uq_siswa_tahun` (`id_siswa`, `id_tahun_ajaran`),
                INDEX       `idx_pj_siswa`   (`id_siswa`),
                INDEX       `idx_pj_tenant`  (`tenant_id`),
                INDEX       `idx_pj_jurusan` (`id_jurusan`),
                INDEX       `idx_pj_status`  (`status`)

            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
              COMMENT='Pilihan Penjurusan Mandiri Siswa — dikelola Guru BK';
        ");
        echo "  OK Tabel pilihan_penjurusan berhasil dibuat.\n";

        // ============================================================
        // 2. Tabel pilihan_penjurusan_log (Audit Trail ACID)
        //    Setiap perubahan status dicatat permanen di sini.
        // ============================================================
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `pilihan_penjurusan_log` (
                `id`              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `id_pilihan`      BIGINT UNSIGNED NOT NULL COMMENT 'FK ke pilihan_penjurusan.id',
                `id_siswa`        VARCHAR(36)     NOT NULL,
                `tenant_id`       VARCHAR(36)     NOT NULL,
                `id_jurusan_lama` INT UNSIGNED    DEFAULT NULL,
                `id_jurusan_baru` INT UNSIGNED    NOT NULL,
                `status_lama`     VARCHAR(30)     DEFAULT NULL,
                `status_baru`     VARCHAR(30)     NOT NULL,
                `aksi`            ENUM('Buat','Verifikasi','Tolak','Override','Buka Kunci') NOT NULL,
                `dilakukan_oleh`  VARCHAR(36)     DEFAULT NULL COMMENT 'user_id pelaku',
                `nama_pelaku`     VARCHAR(255)    DEFAULT NULL,
                `catatan`         TEXT            DEFAULT NULL,
                `created_at`      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

                INDEX `idx_log_pilihan`  (`id_pilihan`),
                INDEX `idx_log_siswa`    (`id_siswa`),
                INDEX `idx_log_tenant`   (`tenant_id`)

            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
              COMMENT='Audit Trail — Riwayat Perubahan Pilihan Penjurusan Siswa';
        ");
        echo "  OK Tabel pilihan_penjurusan_log berhasil dibuat.\n";
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec("DROP TABLE IF EXISTS `pilihan_penjurusan_log`;");
        $pdo->exec("DROP TABLE IF EXISTS `pilihan_penjurusan`;");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "  OK Rollback pilihan_penjurusan selesai.\n";
    },
];
