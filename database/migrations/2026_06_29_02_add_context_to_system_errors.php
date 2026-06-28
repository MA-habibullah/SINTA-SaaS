<?php
// Migration: Add context column to system_errors
return [
    'up' => function (PDO $pdo): void {
        $pdo->exec("ALTER TABLE `system_errors` ADD COLUMN `context` JSON NULL AFTER `ip_address`");
        echo "  OK Kolom context berhasil ditambahkan pada tabel system_errors.\n";
    },
    'down' => function (PDO $pdo): void {
        $pdo->exec("ALTER TABLE `system_errors` DROP COLUMN `context`");
        echo "  OK Kolom context berhasil dihapus dari tabel system_errors.\n";
    }
];
