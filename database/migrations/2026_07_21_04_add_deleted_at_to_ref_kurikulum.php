<?php
/**
 * Migration: Add missing deleted_at column to ref_kurikulum table for soft delete support
 */
return [
    'up' => function (PDO $pdo): void {
        // Check if deleted_at column already exists in ref_kurikulum
        $stmt = $pdo->query("SHOW COLUMNS FROM `ref_kurikulum` LIKE 'deleted_at'");
        $exists = $stmt->fetch();

        if (!$exists) {
            $pdo->exec("ALTER TABLE `ref_kurikulum` ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL AFTER `is_active`;");
            echo "- Kolom 'deleted_at' berhasil ditambahkan ke tabel 'ref_kurikulum'.\n";
        } else {
            echo "- Kolom 'deleted_at' sudah ada pada tabel 'ref_kurikulum'.\n";
        }
    },
    'down' => function (PDO $pdo): void {
        $stmt = $pdo->query("SHOW COLUMNS FROM `ref_kurikulum` LIKE 'deleted_at'");
        if ($stmt->fetch()) {
            $pdo->exec("ALTER TABLE `ref_kurikulum` DROP COLUMN `deleted_at`;");
            echo "- Kolom 'deleted_at' berhasil dihapus dari tabel 'ref_kurikulum'.\n";
        }
    },
];
