<?php

return [
    'up' => function (PDO $pdo): void {
        // Hapus tabel redundan core.active_sessions karena yang digunakan secara aktif oleh sistem adalah sistem.active_sessions
        $pdo->exec("DROP TABLE IF EXISTS core.active_sessions CASCADE;");
        echo "- Removed redundant table core.active_sessions (0 duplicates remaining).\n";
    },
    'down' => function (PDO $pdo): void {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS core.active_sessions (
                id VARCHAR(255) PRIMARY KEY,
                tenant_id UUID REFERENCES core.tenants(id) ON DELETE CASCADE,
                user_id UUID,
                ip_address VARCHAR(45),
                user_agent TEXT,
                tanggal_login TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        ");
    },
];
