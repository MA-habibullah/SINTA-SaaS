<?php
require_once __DIR__ . '/../app/Config/Database.php';
use App\Config\Database;

try {
    $pdo = Database::getConnection();
    $tables = ['provinsi', 'kota', 'kecamatan', 'kelurahan', 'angkatan', 'tahun_ajaran', 'jenjang', 'jurusan', 'kelas', 'pendidikan'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM `$table`");
        $count = $stmt->fetchColumn();
        echo "Table $table: $count rows\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
