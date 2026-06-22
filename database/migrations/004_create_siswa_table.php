<?php
/**
 * Migration 004 - Create Siswa Table
 */

return [
    'up' => function(PDO $pdo) {
        $sql = "CREATE TABLE IF NOT EXISTS siswa (
            id CHAR(36) NOT NULL,
            tenant_id CHAR(36) NOT NULL,
            user_id CHAR(36) DEFAULT NULL,
            nisn CHAR(10) DEFAULT NULL,
            nis VARCHAR(20) DEFAULT NULL,
            nama_lengkap VARCHAR(255) NOT NULL,
            tempat_lahir VARCHAR(100) DEFAULT NULL,
            tanggal_lahir DATE DEFAULT NULL,
            jenis_kelamin ENUM('L', 'P') NOT NULL,
            alamat TEXT DEFAULT NULL,
            nama_wali VARCHAR(255) DEFAULT NULL,
            kontak_wali VARCHAR(20) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY (id),
            CONSTRAINT fk_siswa_tenant_id FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE RESTRICT ON UPDATE CASCADE,
            CONSTRAINT fk_siswa_user_id FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
            UNIQUE KEY uq_siswa_tenant_nis (tenant_id, nis),
            UNIQUE KEY uq_siswa_nisn (nisn),
            INDEX idx_siswa_tenant_name (tenant_id, nama_lengkap)
        ) ENGINE=InnoDB;";
        
        $pdo->exec($sql);
    },
    
    'down' => function(PDO $pdo) {
        $pdo->exec("DROP TABLE IF EXISTS siswa;");
    }
];
