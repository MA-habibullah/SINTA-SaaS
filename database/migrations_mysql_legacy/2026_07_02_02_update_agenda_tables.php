<?php
/**
 * Migration: Create Kategori Agenda & Modify Agenda Sekolah Table
 */
return [
    'up' => function (PDO $pdo): void {
        // 1. Create kategori_agenda table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `kategori_agenda` (
                `id` CHAR(36) NOT NULL,
                `tenant_id` CHAR(36) NULL,
                `nama_kategori` VARCHAR(100) NOT NULL,
                `kode_warna` VARCHAR(20) DEFAULT '#0b5ed7',
                `is_active` TINYINT(1) DEFAULT 1,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ");
        echo "  OK Tabel kategori_agenda berhasil dibuat.\n";

        // 2. Alter agenda_sekolah to add new columns
        // Check if kategori_id exists first
        $stmt = $pdo->query("SHOW COLUMNS FROM `agenda_sekolah` LIKE 'kategori_id'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("
                ALTER TABLE `agenda_sekolah` 
                ADD COLUMN `kategori_id` CHAR(36) NULL AFTER `tenant_id`,
                ADD COLUMN `lokasi` VARCHAR(255) NULL AFTER `deskripsi`,
                ADD COLUMN `pic_id` CHAR(36) NULL AFTER `lokasi`,
                ADD COLUMN `target_audiens` ENUM('Semua', 'Siswa', 'Guru', 'Wali Murid', 'Internal Manajemen') DEFAULT 'Semua' AFTER `visibilitas`,
                ADD COLUMN `waktu_mulai` DATETIME NULL AFTER `deskripsi`,
                ADD COLUMN `waktu_selesai` DATETIME NULL AFTER `waktu_mulai`;
            ");
            
            // Migrate existing dates to the new datetime format
            $pdo->exec("
                UPDATE `agenda_sekolah` 
                SET `waktu_mulai` = CONCAT(`tanggal_mulai`, ' ', IFNULL(`waktu`, '00:00:00')),
                    `waktu_selesai` = CONCAT(IFNULL(`tanggal_selesai`, `tanggal_mulai`), ' ', IFNULL(`waktu`, '23:59:59'))
                WHERE `waktu_mulai` IS NULL
            ");
            
            echo "  OK Tabel agenda_sekolah berhasil di-alter dan data di-migrate.\n";

            // Add Indexes for performance
            $pdo->exec("
                ALTER TABLE `agenda_sekolah` 
                ADD INDEX `idx_waktu_mulai` (`waktu_mulai`),
                ADD INDEX `idx_waktu_selesai` (`waktu_selesai`),
                ADD INDEX `idx_status_kegiatan` (`status_kegiatan`);
            ");
            echo "  OK Index berhasil ditambahkan ke agenda_sekolah.\n";
        } else {
            echo "  INFO Tabel agenda_sekolah sudah memiliki kolom-kolom baru.\n";
        }
    },

    'down' => function (PDO $pdo): void {
        // Drop indexes first
        try {
            $pdo->exec("ALTER TABLE `agenda_sekolah` DROP INDEX `idx_waktu_mulai`");
            $pdo->exec("ALTER TABLE `agenda_sekolah` DROP INDEX `idx_waktu_selesai`");
            $pdo->exec("ALTER TABLE `agenda_sekolah` DROP INDEX `idx_status_kegiatan`");
        } catch (\PDOException $e) {
            // Ignore
        }

        // Drop columns
        try {
            $pdo->exec("
                ALTER TABLE `agenda_sekolah` 
                DROP COLUMN `kategori_id`,
                DROP COLUMN `lokasi`,
                DROP COLUMN `pic_id`,
                DROP COLUMN `target_audiens`,
                DROP COLUMN `waktu_mulai`,
                DROP COLUMN `waktu_selesai`;
            ");
            echo "  OK Kolom-kolom baru pada agenda_sekolah berhasil dihapus.\n";
        } catch (\PDOException $e) {
            // Ignore
        }

        // Drop table
        $pdo->exec("DROP TABLE IF EXISTS `kategori_agenda`");
        echo "  OK Tabel kategori_agenda berhasil dihapus.\n";
    }
];
