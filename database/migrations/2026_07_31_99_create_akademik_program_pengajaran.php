<?php
return [
    'up' => function (PDO $pdo): void {
        $pdo->exec("CREATE TABLE IF NOT EXISTS akademik.program_pengajaran (
            id VARCHAR(36) PRIMARY KEY,
            tenant_id VARCHAR(36),
            kode_program VARCHAR(50),
            nama_program VARCHAR(100),
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        echo "- Table akademik.program_pengajaran created/verified successfully.\n";
    },
    'down' => function (PDO $pdo): void {
        $pdo->exec("DROP TABLE IF EXISTS akademik.program_pengajaran");
    },
];
