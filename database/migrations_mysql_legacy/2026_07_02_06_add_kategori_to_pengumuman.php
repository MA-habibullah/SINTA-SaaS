<?php
/**
 * Migration: Add kategori_id to pengumuman table
 */
return [
    'up' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

        // Periksa apakah kolom kategori_id sudah ada (untuk idempotency)
        $stmt = $pdo->query("SHOW COLUMNS FROM `pengumuman` LIKE 'kategori_id'");
        if ($stmt->rowCount() === 0) {
            $pdo->exec("
                ALTER TABLE `pengumuman` 
                ADD COLUMN `kategori_id` char(36) NULL AFTER `lampiran_file`,
                ADD CONSTRAINT `fk_pengumuman_kategori` FOREIGN KEY (`kategori_id`) REFERENCES `kategori_pengumuman` (`id`) ON DELETE SET NULL;
            ");
            echo "  OK Kolom kategori_id berhasil ditambahkan ke tabel pengumuman.\n";
        } else {
            echo "  INFO Kolom kategori_id sudah ada di tabel pengumuman.\n";
        }

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        
        $stmt = $pdo->query("SHOW COLUMNS FROM `pengumuman` LIKE 'kategori_id'");
        if ($stmt->rowCount() > 0) {
            $pdo->exec("
                ALTER TABLE `pengumuman` 
                DROP FOREIGN KEY `fk_pengumuman_kategori`,
                DROP COLUMN `kategori_id`;
            ");
            echo "  OK Kolom kategori_id dihapus dari tabel pengumuman.\n";
        }
        
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }
];
