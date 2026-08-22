<?php
/**
 * Migration: Pastikan kolom catatan di persuratan.disposisi_surat dan tabel persuratan.nomor_terakhir tersedia
 */
return [
    'up' => function (PDO $pdo): void {
        // 1. Kolom catatan di disposisi_surat
        $pdo->exec("
            ALTER TABLE persuratan.disposisi_surat ADD COLUMN IF NOT EXISTS catatan TEXT NULL;
        ");

        // 2. Tabel nomor_terakhir
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS persuratan.nomor_terakhir (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL,
                tahun INT NOT NULL,
                jenis VARCHAR(50) NOT NULL,
                nomor_terakhir INT NOT NULL DEFAULT 1,
                is_active BOOLEAN NOT NULL DEFAULT TRUE,
                created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
            );
        ");

        echo "- Schema persuratan updated successfully.\n";
    },
    'down' => function (PDO $pdo): void {
        $pdo->exec("DROP TABLE IF EXISTS persuratan.nomor_terakhir CASCADE;");
    },
];
