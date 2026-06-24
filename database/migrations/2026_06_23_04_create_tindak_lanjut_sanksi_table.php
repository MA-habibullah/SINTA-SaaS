<?php
/**
 * Migration: Create Tindak Lanjut Sanksi Table
 * 
 * Membuat:
 * 1. Tabel `tindak_lanjut_sanksi` - Menyimpan log pembinaan/tindak lanjut/konseling 
 *    yang dilakukan oleh BK/Wali kelas untuk mengatasi sanksi poin siswa.
 */

return [
    'up' => function(PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `tindak_lanjut_sanksi` (
                `id` CHAR(36) NOT NULL,
                `tenant_id` CHAR(36) NOT NULL,
                `siswa_id` CHAR(36) NOT NULL,
                `tahun_ajaran_id` INT UNSIGNED NOT NULL,
                `tanggal_tindakan` DATE NOT NULL,
                `jenis_tindakan` VARCHAR(100) NOT NULL COMMENT 'e.g. Pembinaan Wali Kelas, Konseling BK, Pemanggilan Ortu, Skorsing, Sidang Pleno',
                `keterangan_tindakan` TEXT NOT NULL,
                `guru_id` CHAR(36) NOT NULL COMMENT 'UUID user yang menginput',
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `deleted_at` TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                CONSTRAINT `fk_tl_sanksi_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_tl_sanksi_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_tl_sanksi_ta` FOREIGN KEY (`tahun_ajaran_id`) REFERENCES `tahun_ajaran` (`id`) ON DELETE RESTRICT,
                INDEX `idx_tl_sanksi_tenant` (`tenant_id`),
                INDEX `idx_tl_sanksi_siswa` (`siswa_id`),
                INDEX `idx_tl_sanksi_ta` (`tahun_ajaran_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
        ");
        echo "  OK Tabel tindak_lanjut_sanksi berhasil dibuat.\n";

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    },

    'down' => function(PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec("DROP TABLE IF EXISTS `tindak_lanjut_sanksi`;");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "  OK Rollback tabel tindak_lanjut_sanksi selesai.\n";
    }
];
