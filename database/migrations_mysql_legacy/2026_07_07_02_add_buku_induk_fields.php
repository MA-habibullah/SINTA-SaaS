<?php
/**
 * Migration: Add missing fields for Buku Induk
 */
return [
    'up' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        
        // 1. Tambah kolom di rincian_pelajar
        $pdo->exec("ALTER TABLE `rincian_pelajar`
            ADD COLUMN `saudara_tiri` INT DEFAULT 0 AFTER `jumlah_saudara`,
            ADD COLUMN `saudara_angkat` INT DEFAULT 0 AFTER `saudara_tiri`;
        ");
        
        // 2. Tambah kolom di registrasi
        $pdo->exec("ALTER TABLE `registrasi`
            ADD COLUMN `sekolah_asal_mutasi` VARCHAR(255) NULL AFTER `alasan_keluar`,
            ADD COLUMN `pindah_dari_tingkat` VARCHAR(50) NULL AFTER `sekolah_asal_mutasi`,
            ADD COLUMN `pindah_no_surat` VARCHAR(100) NULL AFTER `pindah_dari_tingkat`,
            ADD COLUMN `tingkat_ditinggalkan` VARCHAR(50) NULL AFTER `pindah_no_surat`,
            ADD COLUMN `diterima_di_tingkat` VARCHAR(50) NULL AFTER `tingkat_ditinggalkan`;
        ");

        // 3. Buat tabel kesehatan_siswa
        $pdo->exec("CREATE TABLE IF NOT EXISTS `kesehatan_siswa` (
            `id` CHAR(36) NOT NULL,
            `tenant_id` CHAR(36) NOT NULL,
            `siswa_id` CHAR(36) NOT NULL,
            `semester` TINYINT(1) NOT NULL COMMENT '1 to 6',
            `tahun_ajaran` VARCHAR(20) NULL,
            `tinggi_badan` INT NULL,
            `berat_badan` INT NULL,
            `pendengaran` VARCHAR(100) NULL,
            `pengelihatan` VARCHAR(100) NULL,
            `gigi` VARCHAR(100) NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            CONSTRAINT `fk_ks_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_ks_siswa_id` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE,
            UNIQUE KEY `uq_kesehatan_semester` (`siswa_id`, `semester`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
        
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "  OK Field baru & tabel kesehatan_siswa berhasil dibuat.\n";
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        
        $pdo->exec("ALTER TABLE `rincian_pelajar`
            DROP COLUMN `saudara_tiri`,
            DROP COLUMN `saudara_angkat`;
        ");
        
        $pdo->exec("ALTER TABLE `registrasi`
            DROP COLUMN `sekolah_asal_mutasi`,
            DROP COLUMN `pindah_dari_tingkat`,
            DROP COLUMN `pindah_no_surat`,
            DROP COLUMN `tingkat_ditinggalkan`,
            DROP COLUMN `diterima_di_tingkat`;
        ");

        $pdo->exec("DROP TABLE IF EXISTS `kesehatan_siswa`;");

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "  OK Rollback field baru & tabel kesehatan_siswa selesai.\n";
    },
];
