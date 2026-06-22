<?php
require_once __DIR__ . '/../app/Config/Database.php';
use App\Config\Database;

try {
    $pdo = Database::getConnection();
    
    // Alter kip table to make fields nullable or have default values
    $sql = "ALTER TABLE `kip` 
            MODIFY COLUMN `no_kip` CHAR(15) NULL DEFAULT NULL,
            MODIFY COLUMN `status_anak` ENUM('Piatu','Yatim','Yatim Piatu') NULL DEFAULT NULL,
            MODIFY COLUMN `alasan_layak` ENUM('Daerah Konflik','Dampak Bencana Alam','Kelainan Fisik','Keluarga Terpidana / Berada di LAPAS','Pemegang PKH / KPS / KKS','Pernah Drop Out','Siswa Miskin','Tidak Ada') NULL DEFAULT 'Tidak Ada'";
            
    $pdo->exec($sql);
    echo "Successfully altered kip table fields to be nullable/defaulted.\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
