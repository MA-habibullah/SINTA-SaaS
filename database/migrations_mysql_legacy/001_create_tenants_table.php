<?php
/**
 * Migration 001 - Create Tenants Table
 */

return [
    'up' => function(PDO $pdo) {
        $sql = "CREATE TABLE IF NOT EXISTS tenants (
            id CHAR(36) NOT NULL,
            nama_sekolah VARCHAR(255) NOT NULL,
            npsn CHAR(8) NOT NULL UNIQUE,
            subdomain VARCHAR(100) NOT NULL UNIQUE,
            domain VARCHAR(255) DEFAULT NULL UNIQUE,
            status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
            paket_aktif VARCHAR(50) NOT NULL DEFAULT 'Premium SaaS',
            status_sinkronisasi VARCHAR(50) NOT NULL DEFAULT 'Tersinkronisasi',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY (id),
            INDEX idx_tenant_status (status),
            INDEX idx_tenants_npsn (npsn)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
        
        $pdo->exec($sql);
    },
    
    'down' => function(PDO $pdo) {
        $pdo->exec("DROP TABLE IF EXISTS tenants;");
    }
];
