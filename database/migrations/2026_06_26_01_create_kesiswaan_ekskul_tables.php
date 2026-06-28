<?php
/**
 * Migration: Create Kesiswaan Ekskul Tables
 * 
 * Membuat:
 * 1. Tabel `master_ekskul` - Master data ekstrakurikuler.
 * 2. Tabel `anggota_ekskul` - Pivot table siswa yang mengikuti ekskul.
 * 3. Tabel `jurnal_ekskul` - Catatan kegiatan ekskul.
 * 4. Tabel `nilai_ekskul` - Nilai ekskul per siswa.
 */

return [
    'up' => function(PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

        // 1. Tabel master_ekskul
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `master_ekskul` (
                `id` CHAR(36) NOT NULL,
                `tenant_id` CHAR(36) NOT NULL,
                `nama_ekskul` VARCHAR(255) NOT NULL,
                `kategori` ENUM('Wajib', 'Pilihan') NOT NULL DEFAULT 'Pilihan',
                `pembina_id` CHAR(36) NULL COMMENT 'UUID user pelatih/pembina',
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `deleted_at` TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                CONSTRAINT `fk_master_ekskul_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_master_ekskul_pembina` FOREIGN KEY (`pembina_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
                INDEX `idx_master_ekskul_tenant` (`tenant_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ");
        echo "  OK Tabel master_ekskul berhasil dibuat.\n";

        // 2. Tabel anggota_ekskul
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `anggota_ekskul` (
                `id` CHAR(36) NOT NULL,
                `tenant_id` CHAR(36) NOT NULL,
                `ekskul_id` CHAR(36) NOT NULL,
                `siswa_id` CHAR(36) NOT NULL,
                `tahun_ajaran_id` INT UNSIGNED NOT NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                CONSTRAINT `fk_anggota_ekskul_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_anggota_ekskul_master` FOREIGN KEY (`ekskul_id`) REFERENCES `master_ekskul` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_anggota_ekskul_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_anggota_ekskul_ta` FOREIGN KEY (`tahun_ajaran_id`) REFERENCES `tahun_ajaran` (`id`) ON DELETE RESTRICT,
                INDEX `idx_anggota_ekskul_tenant` (`tenant_id`),
                INDEX `idx_anggota_ekskul_ekskul` (`ekskul_id`),
                INDEX `idx_anggota_ekskul_siswa` (`siswa_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ");
        echo "  OK Tabel anggota_ekskul berhasil dibuat.\n";

        // 3. Tabel jurnal_ekskul
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `jurnal_ekskul` (
                `id` CHAR(36) NOT NULL,
                `tenant_id` CHAR(36) NOT NULL,
                `ekskul_id` CHAR(36) NOT NULL,
                `tanggal_kegiatan` DATE NOT NULL,
                `materi` TEXT NOT NULL,
                `foto_kegiatan` VARCHAR(255) NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `deleted_at` TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                CONSTRAINT `fk_jurnal_ekskul_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_jurnal_ekskul_master` FOREIGN KEY (`ekskul_id`) REFERENCES `master_ekskul` (`id`) ON DELETE CASCADE,
                INDEX `idx_jurnal_ekskul_tenant` (`tenant_id`),
                INDEX `idx_jurnal_ekskul_ekskul` (`ekskul_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ");
        echo "  OK Tabel jurnal_ekskul berhasil dibuat.\n";

        // 4. Tabel nilai_ekskul
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `nilai_ekskul` (
                `id` CHAR(36) NOT NULL,
                `tenant_id` CHAR(36) NOT NULL,
                `ekskul_id` CHAR(36) NOT NULL,
                `siswa_id` CHAR(36) NOT NULL,
                `tahun_ajaran_id` INT UNSIGNED NOT NULL,
                `nilai` ENUM('A', 'B', 'C', 'D') NOT NULL DEFAULT 'B',
                `deskripsi` TEXT NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                CONSTRAINT `fk_nilai_ekskul_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_nilai_ekskul_master` FOREIGN KEY (`ekskul_id`) REFERENCES `master_ekskul` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_nilai_ekskul_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_nilai_ekskul_ta` FOREIGN KEY (`tahun_ajaran_id`) REFERENCES `tahun_ajaran` (`id`) ON DELETE RESTRICT,
                INDEX `idx_nilai_ekskul_tenant` (`tenant_id`),
                INDEX `idx_nilai_ekskul_ekskul` (`ekskul_id`),
                INDEX `idx_nilai_ekskul_siswa` (`siswa_id`),
                UNIQUE KEY `uk_nilai_ekskul_siswa_ta` (`ekskul_id`, `siswa_id`, `tahun_ajaran_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ");
        echo "  OK Tabel nilai_ekskul berhasil dibuat.\n";

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    },

    'down' => function(PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec("DROP TABLE IF EXISTS `nilai_ekskul`;");
        $pdo->exec("DROP TABLE IF EXISTS `jurnal_ekskul`;");
        $pdo->exec("DROP TABLE IF EXISTS `anggota_ekskul`;");
        $pdo->exec("DROP TABLE IF EXISTS `master_ekskul`;");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "  OK Rollback tabel kesiswaan ekskul selesai.\n";
    }
];
