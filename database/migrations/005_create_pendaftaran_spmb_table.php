<?php
/**
 * Migration 005 - Create Pendaftaran SPMB Table
 */

return [
    'up' => function(PDO $pdo) {
        $sql = "CREATE TABLE IF NOT EXISTS pendaftaran_spmb (
            id CHAR(36) NOT NULL,
            tenant_id CHAR(36) NOT NULL,
            siswa_id CHAR(36) NOT NULL,
            nomor_pendaftaran VARCHAR(50) NOT NULL,
            jalur_pendaftaran VARCHAR(50) NOT NULL,
            status_pendaftaran ENUM('draft', 'diajukan', 'diverifikasi', 'diterima', 'ditolak') DEFAULT 'draft',
            nilai_seleksi DECIMAL(5,2) DEFAULT 0.00,
            berkas_dokumen JSON DEFAULT NULL,
            tanggal_daftar DATETIME DEFAULT NULL,
            diverifikasi_oleh CHAR(36) DEFAULT NULL,
            catatan_verifikasi TEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            CONSTRAINT fk_spmb_tenant_id FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE RESTRICT ON UPDATE CASCADE,
            CONSTRAINT fk_spmb_siswa_id FOREIGN KEY (siswa_id) REFERENCES siswa (id) ON DELETE RESTRICT ON UPDATE CASCADE,
            CONSTRAINT fk_spmb_verifier FOREIGN KEY (diverifikasi_oleh) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
            UNIQUE KEY uq_spmb_tenant_nomor (tenant_id, nomor_pendaftaran),
            INDEX idx_spmb_tenant_status (tenant_id, status_pendaftaran),
            INDEX idx_spmb_tenant_siswa (tenant_id, siswa_id)
        ) ENGINE=InnoDB;";
        
        $pdo->exec($sql);
    },
    
    'down' => function(PDO $pdo) {
        $pdo->exec("DROP TABLE IF EXISTS pendaftaran_spmb;");
    }
];
