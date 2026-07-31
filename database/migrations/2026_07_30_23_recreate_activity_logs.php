<?php
return [
    'up' => function (PDO $pdo): void {
        $pdo->exec("DROP TABLE IF EXISTS sistem.activity_logs CASCADE");
        
        $sql = "
            CREATE TABLE sistem.activity_logs (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL REFERENCES core.tenants(id) ON DELETE CASCADE,
                user_id UUID NULL REFERENCES core.users(id) ON DELETE SET NULL,
                user_role VARCHAR(100) NULL,
                table_name VARCHAR(100) NOT NULL,
                action VARCHAR(50) NOT NULL,
                old_data JSONB NULL,
                new_data JSONB NULL,
                ip_address VARCHAR(45) NULL,
                created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP
            )
        ";
        $pdo->exec($sql);
        echo "- Tabel sistem.activity_logs berhasil direkonstruksi untuk keperluan Audit Trail.\n";
    },
    'down' => function (PDO $pdo): void {
        $pdo->exec("DROP TABLE IF EXISTS sistem.activity_logs CASCADE");
    },
];
