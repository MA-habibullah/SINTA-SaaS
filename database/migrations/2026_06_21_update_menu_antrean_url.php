<?php
/**
 * Migration: Update Menu Antrean URL
 * 
 * Mengubah URL menu Antrean Sistem & Background Jobs (ID 17) agar mengarah
 * ke rute pengelola antrean (/dapodik-spmb/utilitas/antrean).
 */
return [

    'up' => function(PDO $pdo): void {
        $stmt = $pdo->prepare("UPDATE `menus` SET `url` = :url WHERE `id` = 17");
        $stmt->execute(['url' => '/dapodik-spmb/utilitas/antrean']);
        echo "  OK Menu Antrean Sistem & Background Jobs diarahkan ke URL /dapodik-spmb/utilitas/antrean.\n";
    },

    'down' => function(PDO $pdo): void {
        $stmt = $pdo->prepare("UPDATE `menus` SET `url` = '#' WHERE `id` = 17");
        $stmt->execute();
        echo "  OK Menu Antrean Sistem & Background Jobs dikembalikan ke URL #.\n";
    }
];
