<?php
/**
 * Migration: Create Detail Nilai Rapor Table
 */
return [
    'up' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS `detail_nilai_rapor` (
            `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
            `tenant_id` CHAR(36) NOT NULL,
            `siswa_id` CHAR(36) NOT NULL,
            `kelas_id` INT UNSIGNED NOT NULL,
            `tahun_ajaran` VARCHAR(50) NOT NULL,
            `semester` VARCHAR(20) NOT NULL,
            `mapel_id` INT UNSIGNED NOT NULL,
            `nilai_akhir` DECIMAL(5,2) DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `deleted_at` TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            CONSTRAINT `fk_detail_nilai_rapor_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_detail_nilai_rapor_siswa_id` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_detail_nilai_rapor_kelas_id` FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_detail_nilai_rapor_mapel_id` FOREIGN KEY (`mapel_id`) REFERENCES `mata_pelajaran` (`id`) ON DELETE CASCADE,
            UNIQUE KEY `uq_detail_nilai_rapor_sync` (`tenant_id`, `siswa_id`, `tahun_ajaran`, `semester`, `mapel_id`),
            INDEX `idx_detail_nilai_rapor_tenant` (`tenant_id`),
            INDEX `idx_detail_nilai_rapor_siswa` (`siswa_id`),
            INDEX `idx_detail_nilai_rapor_kelas` (`kelas_id`),
            INDEX `idx_detail_nilai_rapor_mapel` (`mapel_id`)
        ) ENGINE=InnoDB;");
        
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "  OK Tabel 'detail_nilai_rapor' berhasil dibuat.\n";
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec("DROP TABLE IF EXISTS `detail_nilai_rapor`;");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "  OK Rollback tabel 'detail_nilai_rapor' selesai.\n";
    },
];
