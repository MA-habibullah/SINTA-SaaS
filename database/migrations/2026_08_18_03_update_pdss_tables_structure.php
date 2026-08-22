<?php

/**
 * Migration PostgreSQL: Update PDSS Tables Structure (Multi-Schema Compatible)
 * Format: return ['up' => closure, 'down' => closure]
 */

return [
    'up' => function (PDO $pdo): void {
        echo "=== UPDATING PDSS TABLES STRUCTURE (POSTGRESQL MULTI-SCHEMA) ===\n";

        // 1. pdss.pdss_config_mapel
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS pdss.pdss_config_mapel (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NOT NULL,
                tahun_ajaran_id UUID NULL,
                kelas_id UUID NULL,
                jurusan_id UUID NULL,
                mapel_id UUID NOT NULL,
                sem_1 BOOLEAN DEFAULT FALSE,
                sem_2 BOOLEAN DEFAULT FALSE,
                sem_3 BOOLEAN DEFAULT FALSE,
                sem_4 BOOLEAN DEFAULT FALSE,
                sem_5 BOOLEAN DEFAULT FALSE,
                sem_6 BOOLEAN DEFAULT FALSE,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );
            ALTER TABLE pdss.pdss_config_mapel ADD COLUMN IF NOT EXISTS tahun_ajaran_id UUID NULL;
            ALTER TABLE pdss.pdss_config_mapel ADD COLUMN IF NOT EXISTS kelas_id UUID NULL;
            ALTER TABLE pdss.pdss_config_mapel ADD COLUMN IF NOT EXISTS jurusan_id UUID NULL;
            ALTER TABLE pdss.pdss_config_mapel ADD COLUMN IF NOT EXISTS mapel_id UUID NULL;
            ALTER TABLE pdss.pdss_config_mapel ADD COLUMN IF NOT EXISTS sem_1 BOOLEAN DEFAULT FALSE;
            ALTER TABLE pdss.pdss_config_mapel ADD COLUMN IF NOT EXISTS sem_2 BOOLEAN DEFAULT FALSE;
            ALTER TABLE pdss.pdss_config_mapel ADD COLUMN IF NOT EXISTS sem_3 BOOLEAN DEFAULT FALSE;
            ALTER TABLE pdss.pdss_config_mapel ADD COLUMN IF NOT EXISTS sem_4 BOOLEAN DEFAULT FALSE;
            ALTER TABLE pdss.pdss_config_mapel ADD COLUMN IF NOT EXISTS sem_5 BOOLEAN DEFAULT FALSE;
            ALTER TABLE pdss.pdss_config_mapel ADD COLUMN IF NOT EXISTS sem_6 BOOLEAN DEFAULT FALSE;
            
            DROP INDEX IF EXISTS pdss.idx_pdss_config_mapel_unique;
            CREATE UNIQUE INDEX IF NOT EXISTS idx_pdss_config_mapel_unique 
            ON pdss.pdss_config_mapel (tenant_id, COALESCE(tahun_ajaran_id, 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12'), COALESCE(kelas_id, 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12'), mapel_id);
        ");

        // 2. pdss.pdss_lock
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS pdss.pdss_lock (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NOT NULL,
                step SMALLINT NOT NULL DEFAULT 1,
                tahun_ajaran_id UUID NULL,
                is_locked BOOLEAN DEFAULT FALSE,
                locked_by VARCHAR(255) NULL,
                locked_at TIMESTAMP WITH TIME ZONE NULL,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );
            ALTER TABLE pdss.pdss_lock ADD COLUMN IF NOT EXISTS step SMALLINT NOT NULL DEFAULT 1;
            ALTER TABLE pdss.pdss_lock ADD COLUMN IF NOT EXISTS tahun_ajaran_id UUID NULL;
            ALTER TABLE pdss.pdss_lock ADD COLUMN IF NOT EXISTS is_locked BOOLEAN DEFAULT FALSE;
            ALTER TABLE pdss.pdss_lock ADD COLUMN IF NOT EXISTS locked_by VARCHAR(255) NULL;
            ALTER TABLE pdss.pdss_lock ADD COLUMN IF NOT EXISTS locked_at TIMESTAMP WITH TIME ZONE NULL;

            DROP INDEX IF EXISTS pdss.idx_pdss_lock_unique;
            CREATE UNIQUE INDEX IF NOT EXISTS idx_pdss_lock_unique 
            ON pdss.pdss_lock (tenant_id, step, COALESCE(tahun_ajaran_id, 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12'));
        ");

        // 3. pdss.pdss_manual_eligible
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS pdss.pdss_manual_eligible (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NOT NULL,
                siswa_id UUID NOT NULL,
                status_eligible VARCHAR(50) DEFAULT 'auto',
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );
            ALTER TABLE pdss.pdss_manual_eligible ADD COLUMN IF NOT EXISTS siswa_id UUID NULL;
            ALTER TABLE pdss.pdss_manual_eligible ADD COLUMN IF NOT EXISTS status_eligible VARCHAR(50) DEFAULT 'auto';

            DROP INDEX IF EXISTS pdss.idx_pdss_manual_eligible_unique;
            CREATE UNIQUE INDEX IF NOT EXISTS idx_pdss_manual_eligible_unique 
            ON pdss.pdss_manual_eligible (tenant_id, siswa_id);
        ");

        // 4. pdss.target_kampus
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS pdss.target_kampus (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NOT NULL,
                nama_kampus VARCHAR(255) NULL,
                jenis_kampus VARCHAR(100) NULL,
                kuota_target INT DEFAULT 0,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );
            ALTER TABLE pdss.target_kampus ADD COLUMN IF NOT EXISTS nama_kampus VARCHAR(255) NULL;
            ALTER TABLE pdss.target_kampus ADD COLUMN IF NOT EXISTS jenis_kampus VARCHAR(100) NULL;
            ALTER TABLE pdss.target_kampus ADD COLUMN IF NOT EXISTS kuota_target INT DEFAULT 0;
        ");

        echo "- Tabel PDSS PostgreSQL berhasil diperbarui.\n";
    },

    'down' => function (PDO $pdo): void {
        // Rollback columns if necessary
    }
];
