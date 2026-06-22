<?php
require_once __DIR__ . '/../app/Config/Database.php';
use App\Config\Database;

try {
    $pdo = Database::getConnection();
    $tables = ['siswa', 'rincian_pelajar', 'rincian_alamat', 'orang_tua', 'kontak', 'kip', 'registrasi', 'dokumen'];
    foreach ($tables as $t) {
        echo "=== TABLE: $t ===\n";
        $stmt = $pdo->query("DESCRIBE `$t`");
        $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols as $col) {
            printf("  %-25s %-30s %-5s %-4s %-10s\n", 
                $col['Field'], 
                $col['Type'], 
                $col['Null'], 
                $col['Key'], 
                var_export($col['Default'], true)
            );
        }
        echo "\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
