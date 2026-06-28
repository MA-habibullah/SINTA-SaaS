<?php
/**
 * Migration: Tabel User Roles (Many-to-Many Junction Table)
 * 
 * Menghubungkan pengguna (users) ke banyak peran (roles) secara bersamaan.
 * Secara default, setiap user akan mendapatkan entry untuk role_id lamanya.
 */
return [

    'up' => function (PDO $pdo): void {
        // 1. Buat tabel user_roles
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `user_roles` (
                `user_id` CHAR(36)  NOT NULL,
                `role_id` INT UNSIGNED NOT NULL,
                PRIMARY KEY (`user_id`, `role_id`),
                CONSTRAINT `fk_ur_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk_ur_role_id` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
              COMMENT='Tabel Junction untuk Multi-Role Pengguna';
        ");
        echo "  OK Tabel user_roles berhasil dibuat.\n";

        // 2. Migrasikan data role yang sudah ada dari tabel users ke user_roles
        $pdo->exec("
            INSERT IGNORE INTO `user_roles` (`user_id`, `role_id`)
            SELECT `id`, `role_id` FROM `users`;
        ");
        echo "  OK Data role awal berhasil disinkronisasi ke user_roles.\n";
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec("DROP TABLE IF EXISTS `user_roles`;");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "  OK Rollback user_roles selesai.\n";
    },
];
