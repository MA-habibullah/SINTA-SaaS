<?php
/**
 * Migration: Create Kegiatan & Pengumuman Tables
 */
return [
    'up' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

        // 1. Jadwal Ekskul
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `jadwal_ekskul` (
                `id` CHAR(36) NOT NULL,
                `tenant_id` CHAR(36) NOT NULL,
                `ekskul_id` CHAR(36) NOT NULL,
                `hari` ENUM('Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu') NOT NULL,
                `waktu_mulai` TIME NOT NULL,
                `waktu_selesai` TIME NOT NULL,
                `ruangan` VARCHAR(100) NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                CONSTRAINT `fk_jadwal_ekskul_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_jadwal_ekskul_master` FOREIGN KEY (`ekskul_id`) REFERENCES `master_ekskul` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ");
        echo "  OK Tabel jadwal_ekskul berhasil dibuat.\n";

        // 2. Agenda Sekolah (Menyatukan Timeline & Agenda)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `agenda_sekolah` (
                `id` CHAR(36) NOT NULL,
                `tenant_id` CHAR(36) NOT NULL,
                `created_by` CHAR(36) NOT NULL,
                `judul` VARCHAR(255) NOT NULL,
                `deskripsi` TEXT NULL,
                `tanggal_mulai` DATE NOT NULL,
                `tanggal_selesai` DATE NOT NULL,
                `waktu` TIME NULL,
                `tipe` ENUM('Agenda Umum', 'Timeline Internal') NOT NULL DEFAULT 'Agenda Umum',
                `status_kegiatan` ENUM('Rencana', 'Sedang Berjalan', 'Selesai', 'Batal') NOT NULL DEFAULT 'Rencana',
                `visibilitas` ENUM('public', 'guru', 'private') NOT NULL DEFAULT 'public',
                `target_roles` JSON NULL COMMENT 'List role IDs jika visibilitas private',
                `lampiran_file` VARCHAR(255) NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                CONSTRAINT `fk_agenda_sekolah_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_agenda_sekolah_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ");
        echo "  OK Tabel agenda_sekolah berhasil dibuat.\n";

        // 3. Pengumuman
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `pengumuman` (
                `id` CHAR(36) NOT NULL,
                `tenant_id` CHAR(36) NOT NULL,
                `created_by` CHAR(36) NOT NULL,
                `judul` VARCHAR(255) NOT NULL,
                `isi_pengumuman` LONGTEXT NOT NULL,
                `lampiran_file` VARCHAR(255) NULL,
                `visibilitas` ENUM('public', 'guru', 'siswa', 'private') NOT NULL DEFAULT 'public',
                `target_roles` JSON NULL,
                `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                CONSTRAINT `fk_pengumuman_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_pengumuman_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ");
        echo "  OK Tabel pengumuman berhasil dibuat.\n";

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec("DROP TABLE IF EXISTS `pengumuman`");
        $pdo->exec("DROP TABLE IF EXISTS `agenda_sekolah`");
        $pdo->exec("DROP TABLE IF EXISTS `jadwal_ekskul`");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "  OK Tabel kegiatan dan pengumuman berhasil dihapus.\n";
    }
];
