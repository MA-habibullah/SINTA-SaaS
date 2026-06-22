<?php
require_once __DIR__ . '/../app/Config/Database.php';
use App\Config\Database;

try {
    $pdo = Database::getConnection();
    echo "=== KIP table records ===\n";
    $rows = $pdo->query("SELECT * FROM `kip`")->fetchAll(PDO::FETCH_ASSOC);
    print_r($rows);
    
    echo "\n=== Orang Tua table records ===\n";
    $rows = $pdo->query("SELECT * FROM `orang_tua`")->fetchAll(PDO::FETCH_ASSOC);
    print_r($rows);
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
