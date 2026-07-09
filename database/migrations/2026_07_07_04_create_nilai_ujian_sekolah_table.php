<?php
/**
 * Migration: Create Nilai Ujian Sekolah Table
 */
return [
    'up' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        
        $sql = "CREATE TABLE IF NOT EXISTS `nilai_ujian_sekolah` (
            `id` CHAR(36) NOT NULL,
            `tenant_id` CHAR(36) NOT NULL,
            `id_siswa` CHAR(36) NOT NULL,
            `id_mata_pelajaran` CHAR(36) NOT NULL,
            `nilai_ujian` DECIMAL(5,2) DEFAULT NULL,
            `tahun_ajaran` VARCHAR(20) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `deleted_at` TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            CONSTRAINT `fk_nus_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_nus_siswa` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_nus_mapel` FOREIGN KEY (`id_mata_pelajaran`) REFERENCES `mata_pelajaran` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $pdo->exec($sql);
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        
        echo "  OK Tabel 'nilai_ujian_sekolah' berhasil dibuat.\n";
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec("DROP TABLE IF EXISTS `nilai_ujian_sekolah`;");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "  OK Rollback tabel 'nilai_ujian_sekolah' selesai.\n";
    }
];
