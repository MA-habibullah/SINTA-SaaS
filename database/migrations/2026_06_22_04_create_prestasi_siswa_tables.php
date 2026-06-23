<?php
/**
 * Migration: Create Prestasi Siswa and Anggota Tables
 */
return [
    'up' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

        // 1. Tabel prestasi_siswa
        $pdo->exec("CREATE TABLE IF NOT EXISTS `prestasi_siswa` (
            `id` CHAR(36) NOT NULL,
            `tenant_id` CHAR(36) NOT NULL,
            `tahun_ajaran_id` INT UNSIGNED NOT NULL,
            `semester` ENUM('Ganjil', 'Genap') NOT NULL,
            `bidang_lomba` ENUM('Sains/Riset', 'Seni/Budaya', 'Olahraga', 'Keagamaan', 'Lainnya') NOT NULL,
            `nama_lomba` VARCHAR(255) NOT NULL,
            `nomor_sertifikat` VARCHAR(100) NOT NULL,
            `juara` VARCHAR(50) NOT NULL,
            `kategori` ENUM('Personal', 'Regu') NOT NULL DEFAULT 'Personal',
            `tingkat_kejuaraan` ENUM('Kota/Kabupaten', 'Provinsi', 'Nasional', 'Internasional') NOT NULL,
            `jenis_lomba` ENUM('Offline', 'Online') NOT NULL,
            `tempat_lomba` VARCHAR(255) NOT NULL,
            `tanggal_lomba` DATE NOT NULL,
            `penyelenggara` VARCHAR(255) NOT NULL,
            `guru_pendamping` VARCHAR(255) DEFAULT NULL,
            `poin_prestasi` INT NOT NULL DEFAULT 0,
            `foto_bukti_prestasi` VARCHAR(255) DEFAULT NULL,
            `foto_siswa_prestasi` VARCHAR(255) DEFAULT NULL,
            `foto_kegiatan_lomba` VARCHAR(255) DEFAULT NULL,
            `surat_tugas_pdf` VARCHAR(255) DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `deleted_at` TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            CONSTRAINT `fk_prestasi_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_prestasi_tahun_id` FOREIGN KEY (`tahun_ajaran_id`) REFERENCES `tahun_ajaran` (`id`) ON DELETE CASCADE,
            INDEX `idx_prestasi_tenant` (`tenant_id`),
            INDEX `idx_prestasi_tahun` (`tahun_ajaran_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin COMMENT='Daftar Prestasi Lomba Siswa';");

        // 2. Tabel pivot prestasi_siswa_anggota
        $pdo->exec("CREATE TABLE IF NOT EXISTS `prestasi_siswa_anggota` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `id_prestasi` CHAR(36) NOT NULL,
            `id_siswa` CHAR(36) NOT NULL,
            CONSTRAINT `fk_psa_prestasi` FOREIGN KEY (`id_prestasi`) REFERENCES `prestasi_siswa` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_psa_siswa` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id`) ON DELETE CASCADE,
            INDEX `idx_psa_prestasi` (`id_prestasi`),
            INDEX `idx_psa_siswa` (`id_siswa`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin COMMENT='Relasi Anggota Lomba dengan Siswa';");

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "  OK Tabel 'prestasi_siswa' dan 'prestasi_siswa_anggota' berhasil dibuat.\n";
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec("DROP TABLE IF EXISTS `prestasi_siswa_anggota`;");
        $pdo->exec("DROP TABLE IF EXISTS `prestasi_siswa`;");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "  OK Rollback tabel prestasi siswa selesai.\n";
    },
];
