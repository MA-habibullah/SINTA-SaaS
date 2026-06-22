<?php
require_once __DIR__ . '/../app/Config/Database.php';
use App\Config\Database;

try {
    $pdo = Database::getConnection();
    
    // Check if columns already exist
    $stmt = $pdo->query("DESCRIBE `siswa`");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $colsToAdd = [];
    if (!in_array('id_angkatan', $columns)) $colsToAdd[] = "ADD COLUMN id_angkatan INT UNSIGNED NULL";
    if (!in_array('id_tahun_ajaran', $columns)) $colsToAdd[] = "ADD COLUMN id_tahun_ajaran INT UNSIGNED NULL";
    if (!in_array('id_pendidikan', $columns)) $colsToAdd[] = "ADD COLUMN id_pendidikan INT UNSIGNED NULL";
    if (!in_array('id_jenjang', $columns)) $colsToAdd[] = "ADD COLUMN id_jenjang INT UNSIGNED NULL";
    if (!in_array('id_jurusan', $columns)) $colsToAdd[] = "ADD COLUMN id_jurusan INT UNSIGNED NULL";
    if (!in_array('id_kelas', $columns)) $colsToAdd[] = "ADD COLUMN id_kelas INT UNSIGNED NULL";
    
    if (!empty($colsToAdd)) {
        $sql = "ALTER TABLE `siswa` " . implode(", ", $colsToAdd);
        $pdo->exec($sql);
        echo "Successfully added missing academic columns to siswa table.\n";
    } else {
        echo "Academic columns already exist in siswa table.\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
