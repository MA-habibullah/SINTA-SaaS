<?php
/**
 * Migration: Create user_menu_access table
 * 
 * Tabel untuk memetakan hak akses menu khusus secara langsung ke user (user-level override).
 */
return [
    'up' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `user_menu_access` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `user_id` VARCHAR(36) NOT NULL,
                `menu_id` INT UNSIGNED NOT NULL,
                `tenant_id` VARCHAR(36) NOT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY `uq_user_menu_tenant` (`tenant_id`, `user_id`, `menu_id`),
                CONSTRAINT `fk_user_menu_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_user_menu_menu_id` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_user_menu_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "- Tabel user_menu_access berhasil dibuat.\n";
    },
    'down' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec("DROP TABLE IF EXISTS `user_menu_access`;");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "- Tabel user_menu_access berhasil dihapus.\n";
    }
];
