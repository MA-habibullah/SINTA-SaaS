<?php
require_once __DIR__ . '/../../app/Config/Database.php';

class AddTinggalKelasEnum {
    public function up() {
        $db = \App\Config\Database::getConnection();
        
        try {
            // Update enum riwayat_kenaikan_kelas
            $sql = "ALTER TABLE riwayat_kenaikan_kelas MODIFY COLUMN jenis_aksi enum('naik_kelas','lulus','tinggal_kelas','penempatan_awal') NOT NULL;";
            $db->exec($sql);
            echo "Migration successful: Added 'tinggal_kelas' to enum riwayat_kenaikan_kelas.jenis_aksi\n";
        } catch (PDOException $e) {
            echo "Migration failed: " . $e->getMessage() . "\n";
        }
    }

    public function down() {
        $db = \App\Config\Database::getConnection();
        
        try {
            // Rollback enum
            $sql = "ALTER TABLE riwayat_kenaikan_kelas MODIFY COLUMN jenis_aksi enum('naik_kelas','lulus','penempatan_awal') NOT NULL;";
            $db->exec($sql);
            echo "Rollback successful: Removed 'tinggal_kelas' from enum riwayat_kenaikan_kelas.jenis_aksi\n";
        } catch (PDOException $e) {
            echo "Rollback failed: " . $e->getMessage() . "\n";
        }
    }
}

// Execute migration if run directly
if (php_sapi_name() === 'cli') {
    $migration = new AddTinggalKelasEnum();
    $migration->up();
}
