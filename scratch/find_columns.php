<?php
require_once __DIR__ . '/../app/Config/Database.php';
use App\Config\Database;

try {
    $pdo = Database::getConnection();
    $sql = "SELECT TABLE_NAME, COLUMN_NAME 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE COLUMN_NAME IN ('id_angkatan', 'id_tahun_ajaran', 'id_pendidikan', 'id_jenjang', 'id_jurusan', 'id_kelas', 'id_tempat_lahir') 
            AND TABLE_SCHEMA = 'dapodik_spmb'";
    $rows = $pdo->query($sql)->fetchAll();
    echo "Found columns:\n";
    print_r($rows);
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
