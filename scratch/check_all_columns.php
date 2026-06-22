<?php
require_once __DIR__ . '/../app/Config/Database.php';
use App\Config\Database;

try {
    $pdo = Database::getConnection();
    $tables = ['rincian_pelajar', 'rincian_alamat', 'orang_tua', 'kontak', 'kip', 'registrasi', 'dokumen'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("DESCRIBE `$table`");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "\n=== Columns of table `$table` ===\n";
        foreach ($columns as $col) {
            printf("- Field: %s | Type: %s | Null: %s | Key: %s | Default: %s\n", 
                $col['Field'], $col['Type'], $col['Null'], $col['Key'], $col['Default'] ?? 'NULL');
        }
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
