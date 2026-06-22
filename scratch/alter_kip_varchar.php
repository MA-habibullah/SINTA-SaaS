<?php
require_once __DIR__ . '/../app/Config/Database.php';
use App\Config\Database;

try {
    $pdo = Database::getConnection();
    
    // Alter kip table to make no_kip VARCHAR(100) to support flexible length and characters
    $sql = "ALTER TABLE `kip` MODIFY COLUMN `no_kip` VARCHAR(100) NULL DEFAULT NULL";
            
    $pdo->exec($sql);
    echo "Successfully altered no_kip column to VARCHAR(100).\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
