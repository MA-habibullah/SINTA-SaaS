<?php

return [
    'up' => function (PDO $pdo): void {
        echo "- Memperbaiki skema tabel sistem.system_errors...\n";
        
        // Drop the old wrongly-created table
        $pdo->exec("DROP TABLE IF EXISTS sistem.system_errors;");
        
        // Recreate with correct schema matching the ErrorTracker
        $pdo->exec("
            CREATE TABLE sistem.system_errors (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL,
                error_level VARCHAR(50) NOT NULL,
                message TEXT NOT NULL,
                file VARCHAR(500) NULL,
                line INTEGER NULL,
                trace JSONB NULL,
                request_url VARCHAR(1000) NULL,
                request_method VARCHAR(10) NULL,
                user_agent TEXT NULL,
                ip_address VARCHAR(45) NULL,
                context JSONB NULL,
                created_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP
            );
        ");
        
        echo "- Tabel sistem.system_errors berhasil diperbaiki.\n";
    },
    'down' => function (PDO $pdo): void {
        $pdo->exec("DROP TABLE IF EXISTS sistem.system_errors;");
        // We won't rollback to the broken schema intentionally, 
        // as the old schema was completely non-functional for the error tracker.
    },
];
