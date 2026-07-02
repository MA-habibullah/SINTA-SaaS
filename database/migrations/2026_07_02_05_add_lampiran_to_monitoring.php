<?php
/**
 * Migration: Add lampiran_bukti to guru_monitoring
 */
return [
    'up' => function (PDO $pdo): void {
        // Check if column exists first
        $stmt = $pdo->query("SHOW COLUMNS FROM `guru_monitoring` LIKE 'lampiran_bukti'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("
                ALTER TABLE `guru_monitoring` 
                ADD COLUMN `lampiran_bukti` VARCHAR(255) NULL AFTER `deskripsi_kasus`
            ");
            echo "  OK Kolom lampiran_bukti berhasil ditambahkan ke guru_monitoring.\n";
        } else {
            echo "  INFO Kolom lampiran_bukti sudah ada di guru_monitoring.\n";
        }
    },

    'down' => function (PDO $pdo): void {
        try {
            $pdo->exec("ALTER TABLE `guru_monitoring` DROP COLUMN `lampiran_bukti`");
            echo "  OK Kolom lampiran_bukti berhasil dihapus.\n";
        } catch (\PDOException $e) {
            // Ignore
        }
    }
];
