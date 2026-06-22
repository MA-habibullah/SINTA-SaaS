<?php
require_once __DIR__ . '/../app/Config/Database.php';
use App\Config\Database;

try {
    $pdo = Database::getConnection();
    $tables = ['angkatan', 'tahun_ajaran', 'jenjang', 'jurusan', 'kelas', 'pendidikan'];
    foreach ($tables as $table) {
        echo "\n--- Table: $table ---\n";
        $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll();
        print_r($rows);
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
