<?php
require_once __DIR__ . '/../app/Config/Database.php';
use App\Config\Database;

try {
    $pdo = Database::getConnection();
    $rows = $pdo->query("SELECT * FROM `menus` ORDER BY id")->fetchAll();
    echo "Menus in database:\n";
    foreach ($rows as $row) {
        printf("ID: %d | Parent: %s | Name: %s | URL: %s\n", $row['id'], $row['parent_id'] ?? 'NULL', $row['nama_menu'], $row['url'] ?? 'NULL');
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
