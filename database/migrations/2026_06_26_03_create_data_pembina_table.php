<?php
/**
 * Migration: Create Data Pembina Table & Role
 * 
 * Membuat:
 * 1. Role `guru_pembina` di tabel roles.
 * 2. Tabel `data_pembina` untuk menyimpan profil detail pembina ekskul.
 */

return [
    'up' => function(PDO $pdo): void {
        // 1. Insert Role `guru_pembina` jika belum ada (ID akan auto increment)
        // Kita gunakan ID 7 jika memungkinkan (karena 1-6 sudah terpakai di seed awal), 
        // tapi lebih aman menggunakan nama_role sebagai reference nanti atau biarkan AI.
        $pdo->exec("INSERT IGNORE INTO roles (nama_role, deskripsi) VALUES 
            ('guru_pembina', 'Extracurricular Coach with access to member management and grading')
        ");
        echo "  OK Role guru_pembina berhasil ditambahkan.\n";

        // 2. Buat Tabel data_pembina
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `data_pembina` (
                `id` CHAR(36) NOT NULL,
                `tenant_id` CHAR(36) NOT NULL,
                `user_id` CHAR(36) NOT NULL,
                `no_hp` VARCHAR(20) NULL,
                `alamat` TEXT NULL,
                `instansi_asal` VARCHAR(100) NULL,
                `keahlian_khusus` VARCHAR(100) NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                CONSTRAINT `fk_data_pembina_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_data_pembina_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
                INDEX `idx_data_pembina_tenant` (`tenant_id`),
                INDEX `idx_data_pembina_user` (`user_id`),
                UNIQUE KEY `uk_data_pembina_user` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
        ");
        echo "  OK Tabel data_pembina berhasil dibuat.\n";
    },

    'down' => function(PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec("DROP TABLE IF EXISTS `data_pembina`;");
        $pdo->exec("DELETE FROM roles WHERE nama_role = 'guru_pembina';");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "  OK Rollback data_pembina selesai.\n";
    }
];
