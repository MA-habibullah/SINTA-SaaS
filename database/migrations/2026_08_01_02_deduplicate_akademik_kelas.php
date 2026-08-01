<?php

return [
    'up' => function (PDO $pdo): void {
        // 1. Deduplicate akademik.kelas: delete duplicate rows, keeping earliest created_at row per (tenant_id, LOWER(TRIM(nama_kelas)))
        $pdo->exec("
            DELETE FROM akademik.kelas
            WHERE id IN (
                SELECT id FROM (
                    SELECT id, ROW_NUMBER() OVER (
                        PARTITION BY tenant_id, LOWER(TRIM(nama_kelas)) 
                        ORDER BY created_at ASC, id ASC
                    ) as rnum
                    FROM akademik.kelas
                ) t
                WHERE t.rnum > 1
            )
        ");
        echo "- Data duplikat di akademik.kelas berhasil dibersihkan.\n";

        // 2. Add unique index if not exists to prevent future duplicates
        $pdo->exec("
            CREATE UNIQUE INDEX IF NOT EXISTS idx_akademik_kelas_tenant_nama_unique 
            ON akademik.kelas (tenant_id, LOWER(TRIM(nama_kelas)))
        ");
        echo "- Unique index idx_akademik_kelas_tenant_nama_unique berhasil dibuat.\n";
    },
    'down' => function (PDO $pdo): void {
        $pdo->exec("DROP INDEX IF EXISTS akademik.idx_akademik_kelas_tenant_nama_unique");
    },
];
