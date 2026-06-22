<?php
/**
 * Migration: Tracer Study — Riwayat Kuliah & Riwayat Pekerjaan
 * Jalankan via CLI: php migrate.php up
 */
return [

    'up' => function (PDO $pdo): void {

        // ======================================================
        // TABEL 1: riwayat_kuliah (Riwayat Perguruan Tinggi)
        // ======================================================
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `riwayat_kuliah` (
                `id`            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `id_siswa`      VARCHAR(36)  NOT NULL COMMENT 'UUID referensi ke tabel siswa',
                `tenant_id`     VARCHAR(36)  NOT NULL COMMENT 'UUID referensi ke tabel tenants',
                `nama_kampus`   VARCHAR(255) NOT NULL,
                `fakultas`      VARCHAR(255) DEFAULT NULL,
                `jurusan`       VARCHAR(255) DEFAULT NULL,
                `tahun_masuk`   SMALLINT     NOT NULL,
                `tahun_lulus`   SMALLINT     DEFAULT NULL,
                `status_kuliah` ENUM('Aktif','Lulus','Drop') NOT NULL DEFAULT 'Aktif',
                `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                INDEX `idx_rk_id_siswa`  (`id_siswa`),
                INDEX `idx_rk_tenant_id` (`tenant_id`)

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
                `tenant_id`          VARCHAR(36)    NOT NULL COMMENT 'UUID referensi ke tabel tenants',
                `nama_perusahaan`    VARCHAR(255)   NOT NULL,
                `posisi_jabatan`     VARCHAR(255)   NOT NULL,
                `pendapatan_bulanan` VARCHAR(100)   DEFAULT NULL,
                `tahun_mulai`        SMALLINT       NOT NULL,
                `tahun_selesai`      SMALLINT       DEFAULT NULL,
                `status_kerja`       ENUM('Kontrak','Tetap','Magang') NOT NULL DEFAULT 'Kontrak',
                `created_at`         TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at`         TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                INDEX `idx_rp_id_siswa`  (`id_siswa`),
                INDEX `idx_rp_tenant_id` (`tenant_id`)

            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
              COMMENT='Tracer Study: Riwayat Karir/Pekerjaan Alumni';
        ");

        echo "  OK Tabel riwayat_kuliah berhasil dibuat.\n";
        echo "  OK Tabel riwayat_pekerjaan berhasil dibuat.\n";
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec("DROP TABLE IF EXISTS `riwayat_pekerjaan`;");
        $pdo->exec("DROP TABLE IF EXISTS `riwayat_kuliah`;");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

        echo "  ✓ Rollback: Tabel riwayat_kuliah & riwayat_pekerjaan dihapus.\n";
    },
];
