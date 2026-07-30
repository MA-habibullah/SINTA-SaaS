<?php
/**
 * Migration: Master Kampus, Prodi, Jalur Masuk, & Modifikasi Tracer Study
 */
return [
    'up' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

        // 1. Modifikasi tabel riwayat_pekerjaan
        $pdo->exec("
            ALTER TABLE `riwayat_pekerjaan`
            MODIFY COLUMN `id_siswa` VARCHAR(36) DEFAULT NULL COMMENT 'Boleh null untuk alumni manual',
            ADD COLUMN IF NOT EXISTS `nama_alumni` VARCHAR(255) DEFAULT NULL AFTER `tenant_id`;
        ");

        // 2. Modifikasi tabel riwayat_kuliah
        $pdo->exec("
            ALTER TABLE `riwayat_kuliah`
            MODIFY COLUMN `id_siswa` VARCHAR(36) DEFAULT NULL COMMENT 'Boleh null untuk alumni manual',
            ADD COLUMN IF NOT EXISTS `kampus_prodi_id` CHAR(36) DEFAULT NULL AFTER `tenant_id`,
            ADD COLUMN IF NOT EXISTS `jalur_masuk_id` BIGINT UNSIGNED DEFAULT NULL AFTER `kampus_prodi_id`;
        ");

        // 3. Buat tabel master_kampus
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `master_kampus` (
                `id` CHAR(36) NOT NULL PRIMARY KEY,
                `tenant_id` CHAR(36) NOT NULL,
                `nama_kampus` VARCHAR(255) NOT NULL,
                `kota_kampus` VARCHAR(255) DEFAULT NULL,
                `alamat_kampus` TEXT DEFAULT NULL,
                `jenis_kampus` ENUM('Negeri', 'Swasta', 'Kedinasan', 'Lainnya') NOT NULL DEFAULT 'Negeri',
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_mk_tenant` (`tenant_id`)
            ) ENGINE=InnoDB COMMENT='Pangkalan Data Kampus';
        ");

        // 4. Buat tabel master_kampus_prodi
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `master_kampus_prodi` (
                `id` CHAR(36) NOT NULL PRIMARY KEY,
                `kampus_id` CHAR(36) NOT NULL,
                `fakultas` VARCHAR(255) DEFAULT NULL,
                `program_studi` VARCHAR(255) NOT NULL,
                `jenjang` ENUM('D1','D2','D3','D4','S1','S2','S3','Profesi') NOT NULL DEFAULT 'S1',
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_mkp_kampus` (`kampus_id`),
                CONSTRAINT `fk_mkp_kampus` FOREIGN KEY (`kampus_id`) REFERENCES `master_kampus`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB COMMENT='Daftar Program Studi per Kampus';
        ");

        // 5. Buat tabel kampus_prodi_riwayat (Daya Tampung vs Pendaftar)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `kampus_prodi_riwayat` (
                `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `prodi_id` CHAR(36) NOT NULL,
                `tahun` SMALLINT NOT NULL,
                `daya_tampung` INT DEFAULT 0,
                `jumlah_pendaftar` INT DEFAULT 0,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uk_prodi_tahun` (`prodi_id`, `tahun`),
                CONSTRAINT `fk_kpr_prodi` FOREIGN KEY (`prodi_id`) REFERENCES `master_kampus_prodi`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB COMMENT='Riwayat keketatan tiap prodi pertahun';
        ");

        // 6. Buat tabel master_jalur_masuk
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `master_jalur_masuk` (
                `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `tenant_id` CHAR(36) NOT NULL,
                `nama_jalur` VARCHAR(255) NOT NULL,
                `kategori` ENUM('SNBP', 'SNBT', 'Mandiri', 'Kedinasan', 'Lainnya') DEFAULT 'Lainnya',
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_mjm_tenant` (`tenant_id`)
            ) ENGINE=InnoDB COMMENT='Master Jalur Masuk Dinamis';
        ");

        // Seed data jalur masuk dasar untuk tenant default (dan semua tenant)
        $defaultTenant = '00000000-0000-0000-0000-000000000000';
        $tenants = $pdo->query("SELECT id FROM tenants WHERE deleted_at IS NULL")->fetchAll(PDO::FETCH_COLUMN);
        if (empty($tenants)) {
            $tenants = [$defaultTenant];
        }

        foreach ($tenants as $tid) {
            $pdo->exec("
                INSERT IGNORE INTO master_jalur_masuk (tenant_id, nama_jalur, kategori) VALUES
                ('{$tid}', 'SNBP (Prestasi)', 'SNBP'),
                ('{$tid}', 'SNBT (Tes)', 'SNBT'),
                ('{$tid}', 'Umum Bidik Misi / PIP', 'Lainnya'),
                ('{$tid}', 'Ujian Mandiri', 'Mandiri'),
                ('{$tid}', 'Kedinasan', 'Kedinasan')
            ");
        }

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "  OK Database schema untuk Master Kampus & Alumni Manual berhasil diterapkan.\n";
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        
        $pdo->exec("DROP TABLE IF EXISTS `master_jalur_masuk`;");
        $pdo->exec("DROP TABLE IF EXISTS `kampus_prodi_riwayat`;");
        $pdo->exec("DROP TABLE IF EXISTS `master_kampus_prodi`;");
        $pdo->exec("DROP TABLE IF EXISTS `master_kampus`;");

        // Rollback alter table riwayat_kuliah (tidak menghapus kolom, tapi idealnya harus dibalik)
        // Rollback riwayat_pekerjaan (tidak bisa rollback ke NOT NULL jika ada record NULL)
        
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "  OK Rollback: Master Kampus & Jalur Masuk dihapus.\n";
    }
];
