<?php

/**
 * Migration PostgreSQL: Update PDSS Kesiapan & Simulasi Tables
 * Format: return ['up' => closure, 'down' => closure]
 */

return [
    'up' => function (\PDO $pdo): void {
        echo "=== UPDATING PDSS KESIAPAN & SIMULASI TABLES ===\n";

        // 1. Tambah kolom ke pdss.kesiapan_siswa
        $pdo->exec("
            ALTER TABLE pdss.kesiapan_siswa 
            ADD COLUMN IF NOT EXISTS ranking_jurusan INT DEFAULT 0,
            ADD COLUMN IF NOT EXISTS is_eligible_final BOOLEAN DEFAULT FALSE,
            ADD COLUMN IF NOT EXISTS catatan_override TEXT NULL,
            ADD COLUMN IF NOT EXISTS overridden_by VARCHAR(255) NULL,
            ADD COLUMN IF NOT EXISTS overridden_at TIMESTAMP WITH TIME ZONE NULL;
        ");

        // 2. Tambah kolom ke pdss.simulasi_setting
        $pdo->exec("
            ALTER TABLE pdss.simulasi_setting
            ADD COLUMN IF NOT EXISTS is_locked BOOLEAN DEFAULT FALSE,
            ADD COLUMN IF NOT EXISTS locked_by VARCHAR(255) NULL,
            ADD COLUMN IF NOT EXISTS locked_at TIMESTAMP WITH TIME ZONE NULL;
        ");

        // 3. Buat tabel pdss.pdss_bukti_simulasi
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS pdss.pdss_bukti_simulasi (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NOT NULL,
                siswa_id UUID NOT NULL,
                tahun_ajaran_id UUID NULL,
                no_simulasi SMALLINT NOT NULL DEFAULT 1,
                nama_file VARCHAR(255) NOT NULL,
                path_file VARCHAR(512) NOT NULL,
                ukuran_file BIGINT NULL,
                mime_type VARCHAR(100) NULL,
                uploaded_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );
            CREATE INDEX IF NOT EXISTS idx_pdss_bukti_tenant_siswa 
            ON pdss.pdss_bukti_simulasi (tenant_id, siswa_id, no_simulasi);
        ");

        // 4. Buat tabel cache ranking pdss.pdss_ranking
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS pdss.pdss_ranking (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NOT NULL,
                siswa_id UUID NOT NULL,
                tahun_ajaran_id UUID NULL,
                nama_jurusan VARCHAR(100) NULL,
                nilai_rata_rata NUMERIC(5,2) DEFAULT 0.00,
                ranking_jurusan INT DEFAULT 0,
                ranking_sekolah INT DEFAULT 0,
                is_eligible BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );
            CREATE UNIQUE INDEX IF NOT EXISTS idx_pdss_ranking_unique 
            ON pdss.pdss_ranking (tenant_id, siswa_id, COALESCE(tahun_ajaran_id, '00000000-0000-0000-0000-000000000000'));
        ");

        echo "- PDSS tables (kesiapan, simulasi_setting, bukti_simulasi, ranking) berhasil diperbarui.\n";
    },

    'down' => function (\PDO $pdo): void {
        $pdo->exec("DROP TABLE IF EXISTS pdss.pdss_ranking;");
        $pdo->exec("DROP TABLE IF EXISTS pdss.pdss_bukti_simulasi;");
    }
];
