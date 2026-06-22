<?php
/**
 * Migration 015 - Add file_sizes to dokumen table
 */

return [
    'up' => function(PDO $pdo) {
        $pdo->exec("ALTER TABLE `dokumen` ADD COLUMN `file_sizes` JSON DEFAULT NULL");
    },
    'down' => function(PDO $pdo) {
        $pdo->exec("ALTER TABLE `dokumen` DROP COLUMN `file_sizes`");
    }
];
