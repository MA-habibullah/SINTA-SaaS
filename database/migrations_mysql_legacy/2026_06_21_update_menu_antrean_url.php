<?php
/**
 * Migration: Update Menu Antrean URL
 * 
 * Mengubah URL menu Antrean Sistem & Background Jobs (ID 17) agar mengarah
 * ke rute pengelola antrean (/SINTA-SaaS/utilitas/antrean).
 */
return [

    'up' => function(PDO $pdo): void {
        $stmt = $pdo->prepare("UPDATE `menus` SET `url` = :url WHERE `id` = 17");
        $stmt->execute(['url' => '/SINTA-SaaS/utilitas/antrean']);
        echo "  OK Menu Antrean Sistem & Background Jobs diarahkan ke URL /SINTA-SaaS/utilitas/antrean.\n";
    },

    'down' => function(PDO $pdo): void {
        $stmt = $pdo->prepare("UPDATE `menus` SET `url` = '#' WHERE `id` = 17");
        $stmt->execute();
        echo "  OK Menu Antrean Sistem & Background Jobs dikembalikan ke URL #.\n";
    }
];
