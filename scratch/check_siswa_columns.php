<?php
require_once __DIR__ . '/../app/Config/Database.php';
use App\Config\Database;

try {
    $pdo = Database::getConnection();
    $stmt = $pdo->query("DESCRIBE `siswa`");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Columns of table `siswa`:\n";
    foreach ($columns as $col) {
        printf("- Field: %s | Type: %s | Null: %s | Key: %s | Default: %s\n", 
            $col['Field'], $col['Type'], $col['Null'], $col['Key'], $col['Default'] ?? 'NULL');
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
