<?php
/**
 * Migration: Create user_roles table forcefully
 * 
 * Memastikan tabel user_roles benar-benar dibuat dan data roles
 * dari tabel users disinkronisasikan.
 */
return [
    'up' => function (PDO $pdo): void {
        // 1. Pastikan tabel dibuat dengan collation yang benar
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `user_roles` (
                `user_id` CHAR(36) NOT NULL,
                `role_id` INT UNSIGNED NOT NULL,
                PRIMARY KEY (`user_id`, `role_id`),
                CONSTRAINT `fk_ur_user_id_force` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk_ur_role_id_force` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ");
        echo "  OK Tabel user_roles (force) berhasil dipastikan ada.\n";

        // 2. Sinkronisasi data user lama ke tabel user_roles
        $pdo->exec("
            INSERT IGNORE INTO `user_roles` (`user_id`, `role_id`)
            SELECT `id`, `role_id` FROM `users` WHERE `role_id` IS NOT NULL;
        ");
        echo "  OK Data role berhasil disinkronisasi.\n";
    },

    'down' => function (PDO $pdo): void {
        echo "  OK Tidak ada rollback untuk patch ini.\n";
    }
];
