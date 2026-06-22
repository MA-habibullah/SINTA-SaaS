<?php
require_once __DIR__ . '/../app/Config/Database.php';
use App\Config\Database;

try {
    $pdo = Database::getConnection();
    $rows = $pdo->query("SELECT * FROM `siswa`")->fetchAll();
    echo "Siswa records:\n";
    print_r($rows);
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
