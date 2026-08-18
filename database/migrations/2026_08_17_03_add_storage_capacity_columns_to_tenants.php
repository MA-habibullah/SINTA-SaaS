<?php

return [
    'up' => function (PDO $pdo): void {
        $pdo->exec("
            ALTER TABLE core.tenants 
            ADD COLUMN IF NOT EXISTS storage_limit_mb INTEGER DEFAULT 1024,
            ADD COLUMN IF NOT EXISTS max_siswa_limit INTEGER DEFAULT 1000,
            ADD COLUMN IF NOT EXISTS max_staff_limit INTEGER DEFAULT 100,
            ADD COLUMN IF NOT EXISTS enable_bk SMALLINT DEFAULT 1,
            ADD COLUMN IF NOT EXISTS enable_tracer SMALLINT DEFAULT 1;
        ");
        echo "- Kolom kapasitas kustom (storage_limit_mb, max_siswa_limit, max_staff_limit, enable_bk, enable_tracer) berhasil ditambahkan ke core.tenants.\n";
    },
    'down' => function (PDO $pdo): void {
        $pdo->exec("
            ALTER TABLE core.tenants 
            DROP COLUMN IF EXISTS storage_limit_mb,
            DROP COLUMN IF EXISTS max_siswa_limit,
            DROP COLUMN IF EXISTS max_staff_limit,
            DROP COLUMN IF EXISTS enable_bk,
            DROP COLUMN IF EXISTS enable_tracer;
        ");
    },
];
