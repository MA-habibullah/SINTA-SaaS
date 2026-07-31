<?php

return [
    'up' => function (PDO $pdo): void {
        echo "- Membuat tabel sistem.kategori_pengumuman...\n";
        
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS sistem.kategori_pengumuman (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL,
                nama_kategori VARCHAR(100) NOT NULL,
                created_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP
            );
        ");
        
        echo "- Tabel sistem.kategori_pengumuman berhasil dibuat.\n";
    },
    'down' => function (PDO $pdo): void {
        $pdo->exec("DROP TABLE IF EXISTS sistem.kategori_pengumuman;");
    },
];
