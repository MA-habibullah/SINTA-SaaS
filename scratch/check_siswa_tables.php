<?php
require_once __DIR__ . '/../app/Config/Database.php';
use App\Config\Database;

try {
    $pdo = Database::getConnection();
    $tables = ['siswa', 'rincian_pelajar', 'rincian_alamat', 'kontak', 'orang_tua', 'kip', 'registrasi', 'dokumen'];
    foreach ($tables as $t) {
        $stmt = $pdo->query("DESCRIBE `$t`");
        echo "\nTable: $t\n";
        while($row = $stmt->fetch()) {
            echo "  {$row['Field']} - {$row['Type']}\n";
        }
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
