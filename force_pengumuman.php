<?php
require __DIR__ . '/app/Config/Database.php';
use App\Config\Database;

try {
    $pdo = Database::getConnection();
    echo "Mengecek dan memperbaiki tabel pengumuman...\n";

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `kategori_pengumuman` (
          `id` char(36) NOT NULL,
          `tenant_id` char(36) DEFAULT NULL,
          `nama_kategori` varchar(255) NOT NULL,
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
          PRIMARY KEY (`id`),
          KEY `fk_kategori_tenant` (`tenant_id`),
          CONSTRAINT `fk_kategori_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");
    echo "1. Tabel kategori_pengumuman siap.\n";

    $stmt = $pdo->query("SHOW COLUMNS FROM `pengumuman` LIKE 'kategori_id'");
    if ($stmt->rowCount() === 0) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec("
            ALTER TABLE `pengumuman` 
            ADD COLUMN `kategori_id` char(36) NULL AFTER `lampiran_file`,
            ADD CONSTRAINT `fk_pengumuman_kategori` FOREIGN KEY (`kategori_id`) REFERENCES `kategori_pengumuman` (`id`) ON DELETE SET NULL;
        ");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "2. Kolom kategori_id berhasil ditambahkan.\n";
    } else {
        echo "2. Kolom kategori_id sudah ada.\n";
    }

    echo "Selesai! Database siap digunakan.\n";

} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
