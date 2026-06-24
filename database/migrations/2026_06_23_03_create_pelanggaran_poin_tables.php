<?php
/**
 * Migration: Create Pelanggaran & Poin Tables
 * 
 * Membuat:
 * 1. Tabel `master_pelanggaran` - Master data tata tertib dan bobot poin pelanggaran (customizable per tenant).
 * 2. Tabel `catatan_pelanggaran_siswa` - Transaksi pencatatan kejadian pelanggaran siswa.
 * 3. Seeding data awal untuk seluruh tenant aktif.
 */

return [
    'up' => function(PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

        // 1. Tabel master_pelanggaran
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `master_pelanggaran` (
                `id` CHAR(36) NOT NULL,
                `tenant_id` CHAR(36) NOT NULL,
                `kategori` ENUM('Ringan', 'Sedang', 'Berat', 'Khusus') NOT NULL,
                `nama_pelanggaran` VARCHAR(255) NOT NULL,
                `bobot_poin` INT UNSIGNED NOT NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `deleted_at` TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                CONSTRAINT `fk_master_pelanggaran_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
                INDEX `idx_master_pelanggaran_tenant` (`tenant_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
        ");
        echo "  OK Tabel master_pelanggaran berhasil dibuat.\n";

        // 2. Tabel catatan_pelanggaran_siswa
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `catatan_pelanggaran_siswa` (
                `id` CHAR(36) NOT NULL,
                `tenant_id` CHAR(36) NOT NULL,
                `siswa_id` CHAR(36) NOT NULL,
                `tahun_ajaran_id` INT UNSIGNED NOT NULL,
                `pelanggaran_id` CHAR(36) NOT NULL,
                `tanggal_kejadian` DATE NOT NULL,
                `catatan_keterangan` TEXT NULL,
                `guru_pelapor_id` CHAR(36) NULL COMMENT 'UUID user pelapor',
                `foto_bukti` VARCHAR(255) NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `deleted_at` TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                CONSTRAINT `fk_catatan_pelanggaran_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_catatan_pelanggaran_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_catatan_pelanggaran_ta` FOREIGN KEY (`tahun_ajaran_id`) REFERENCES `tahun_ajaran` (`id`) ON DELETE RESTRICT,
                CONSTRAINT `fk_catatan_pelanggaran_rule` FOREIGN KEY (`pelanggaran_id`) REFERENCES `master_pelanggaran` (`id`) ON DELETE CASCADE,
                INDEX `idx_catatan_pelanggaran_tenant` (`tenant_id`),
                INDEX `idx_catatan_pelanggaran_siswa` (`siswa_id`),
                INDEX `idx_catatan_pelanggaran_ta` (`tahun_ajaran_id`),
                INDEX `idx_catatan_pelanggaran_rule` (`pelanggaran_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
        ");
        echo "  OK Tabel catatan_pelanggaran_siswa berhasil dibuat.\n";

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

        // 3. Seeding data awal template bagi semua tenant aktif
        $tenants = $pdo->query("SELECT id FROM tenants WHERE deleted_at IS NULL")->fetchAll(PDO::FETCH_COLUMN);
        
        $defaults = [
            ['Ringan', 'Datang terlambat ke sekolah', 5],
            ['Ringan', 'Atribut sekolah tidak lengkap (Topi/Dasi/Sabuk)', 5],
            ['Ringan', 'Rambut gondrong / tidak rapi (Siswa Laki-laki)', 10],
            ['Sedang', 'Menggunakan HP di luar aturan / jam pelajaran', 15],
            ['Sedang', 'Bolos / keluar lingkungan sekolah saat jam pelajaran', 20],
            ['Sedang', 'Seragam dicoret-coret / tidak sesuai harinya', 15],
            ['Berat', 'Merokok di dalam lingkungan / seragam sekolah', 50],
            ['Berat', 'Melakukan perundungan (Bullying) / Berkelahi', 75],
            ['Berat', 'Merusak fasilitas / sarana prasarana sekolah', 40],
            ['Khusus', 'Membawa / mengonsumsi miras / narkoba', 100]
        ];

        $insertedCount = 0;
        foreach ($tenants as $tid) {
            foreach ($defaults as $rule) {
                // Generate UUID v4
                $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000,
                    mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );
                
                $stmt = $pdo->prepare("
                    INSERT IGNORE INTO master_pelanggaran (id, tenant_id, kategori, nama_pelanggaran, bobot_poin)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $uuid,
                    $tid,
                    $rule[0],
                    $rule[1],
                    $rule[2]
                ]);
                $insertedCount++;
            }
        }
        echo "  OK Seeded {$insertedCount} default customizable violation rules.\n";
    },

    'down' => function(PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec("DROP TABLE IF EXISTS `catatan_pelanggaran_siswa`;");
        $pdo->exec("DROP TABLE IF EXISTS `master_pelanggaran`;");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "  OK Rollback tabel pelanggaran & poin selesai.\n";
    }
];
