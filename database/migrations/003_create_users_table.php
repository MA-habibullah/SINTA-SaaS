<?php
/**
 * Migration 003 - Create Users Table
 */

return [
    'up' => function(PDO $pdo) {
        $sql = "CREATE TABLE IF NOT EXISTS users (
            id CHAR(36) NOT NULL,
            tenant_id CHAR(36) DEFAULT NULL,
            role_id INT UNSIGNED NOT NULL,
            nama_lengkap VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            password VARCHAR(255) NOT NULL,
            status ENUM('active', 'inactive', 'blocked') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_user_tenant_email (tenant_id, email),
            CONSTRAINT fk_users_tenant_id FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE RESTRICT ON UPDATE CASCADE,
            CONSTRAINT fk_users_role_id FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE RESTRICT ON UPDATE CASCADE,
            INDEX idx_users_tenant (tenant_id),
            INDEX idx_users_status (status)
        ) ENGINE=InnoDB;";
        
        $pdo->exec($sql);
    },
    
    'down' => function(PDO $pdo) {
        $pdo->exec("DROP TABLE IF EXISTS users;");
    }
];
