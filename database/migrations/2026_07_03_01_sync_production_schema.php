<?php

return [
    'up' => function (PDO $pdo) {
        $queries = [
            // 1. users table
            "ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `no_telp` varchar(20) DEFAULT NULL AFTER `email`",

            // 2. anggota_ekskul table
            "ALTER TABLE `anggota_ekskul` ADD COLUMN IF NOT EXISTS `semester` enum('Ganjil','Genap') NOT NULL DEFAULT 'Ganjil' AFTER `tahun_ajaran_id`",

            // 3. master_ekskul table
            "ALTER TABLE `master_ekskul` ADD COLUMN IF NOT EXISTS `status` enum('active','inactive') DEFAULT 'active' AFTER `kategori`",
            
            // 4. kip table
            "ALTER TABLE `kip` MODIFY COLUMN `alasan_layak` enum('Daerah Konflik','Dampak Bencana Alam','Kelainan Fisik','Keluarga Terpidana / Berada di LAPAS','Pemegang PKH / KPS / KKS','Pernah Drop Out','Siswa Miskin','Tidak Ada') DEFAULT 'Tidak Ada'",
            "ALTER TABLE `kip` MODIFY COLUMN `no_kip` varchar(100) DEFAULT NULL",

            // 5. nilai_ekskul table
            "ALTER TABLE `nilai_ekskul` ADD COLUMN IF NOT EXISTS `semester` enum('Ganjil','Genap') NOT NULL DEFAULT 'Ganjil' AFTER `tahun_ajaran_id`",
            "ALTER TABLE `nilai_ekskul` ADD COLUMN IF NOT EXISTS `poin` int(10) UNSIGNED DEFAULT NULL AFTER `semester`",
            "ALTER TABLE `nilai_ekskul` ADD COLUMN IF NOT EXISTS `sakit` int(10) UNSIGNED DEFAULT 0 AFTER `deskripsi`",
            "ALTER TABLE `nilai_ekskul` ADD COLUMN IF NOT EXISTS `izin` int(10) UNSIGNED DEFAULT 0 AFTER `sakit`",
            "ALTER TABLE `nilai_ekskul` ADD COLUMN IF NOT EXISTS `alfa` int(10) UNSIGNED DEFAULT 0 AFTER `izin`"
        ];

        foreach ($queries as $sql) {
            try {
                $pdo->exec($sql);
            } catch (\PDOException $e) {
                // Ignore errors if columns already exist or if modification fails safely
                error_log("Migration sync warning (safe to ignore): " . $e->getMessage());
            }
        }

        // Handle unique key in nilai_ekskul separately
        try {
            // Try to drop the old unique key if it exists
            $pdo->exec("ALTER TABLE `nilai_ekskul` DROP INDEX `uk_nilai_ekskul_siswa_ta`");
        } catch (\PDOException $e) {
            // Ignore if index doesn't exist
        }

        try {
            // Create the new unique key
            $pdo->exec("ALTER TABLE `nilai_ekskul` ADD UNIQUE INDEX `uk_nilai_ekskul_siswa_ta_sem` (`ekskul_id`, `siswa_id`, `tahun_ajaran_id`, `semester`)");
        } catch (\PDOException $e) {
            // Ignore if index already exists
        }

        // Handle missing foreign keys safely
        $fks = [
            "ALTER TABLE `riwayat_kuliah` ADD CONSTRAINT `fk_rk_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE",
            "ALTER TABLE `riwayat_pekerjaan` ADD CONSTRAINT `fk_rp_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE",
            "ALTER TABLE `target_kampus` ADD CONSTRAINT `fk_tk_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE"
        ];

        foreach ($fks as $fk_sql) {
            try {
                $pdo->exec($fk_sql);
            } catch (\PDOException $e) {
                // Ignore if constraint already exists
            }
        }
    },
    
    'down' => function (PDO $pdo) {
        // Not necessary to rollback structural additions in production securely
    }
];
