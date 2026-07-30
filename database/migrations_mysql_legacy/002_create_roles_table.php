<?php
/**
 * Migration 002 - Create Roles Table
 */

return [
    'up' => function(PDO $pdo) {
        $sql = "CREATE TABLE IF NOT EXISTS roles (
            id INT UNSIGNED AUTO_INCREMENT NOT NULL,
            nama_role VARCHAR(50) NOT NULL UNIQUE,
            deskripsi TEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB;";
        
        $pdo->exec($sql);
    },
    
    'down' => function(PDO $pdo) {
        $pdo->exec("DROP TABLE IF EXISTS roles;");
    }
];
