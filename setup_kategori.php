<?php
require 'app/Config/Database.php';

$db = App\Config\Database::getConnection();

try {
    $db->exec("CREATE TABLE IF NOT EXISTS kategori_pengumuman (
        id CHAR(36) PRIMARY KEY,
        tenant_id CHAR(36) DEFAULT NULL,
        nama_kategori VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_kategori_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    
    // Add kategori_id to pengumuman
    $stmt = $db->query("SHOW COLUMNS FROM pengumuman LIKE 'kategori_id'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE pengumuman ADD COLUMN kategori_id CHAR(36) DEFAULT NULL AFTER tenant_id");
        $db->exec("ALTER TABLE pengumuman ADD CONSTRAINT fk_pengumuman_kategori FOREIGN KEY (kategori_id) REFERENCES kategori_pengumuman(id) ON DELETE SET NULL");
    }
    
    echo "DB setup success.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
