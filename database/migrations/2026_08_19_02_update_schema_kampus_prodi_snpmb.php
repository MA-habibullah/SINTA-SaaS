<?php

/**
 * Migration PostgreSQL Schema: Update Schema Master Kampus, Prodi & Riwayat SNPMB
 * Format: return ['up' => closure, 'down' => closure]
 */

return [
    'up' => function (PDO $pdo): void {
        echo "=== UPDATING POSTGRESQL SCHEMA FOR MASTER KAMPUS & PRODI SNPMB ===\n";

        // 1. Lengkapi pdss.master_kampus
        $pdo->exec("
            ALTER TABLE pdss.master_kampus 
                ADD COLUMN IF NOT EXISTS tenant_id UUID NULL,
                ADD COLUMN IF NOT EXISTS id_ptn VARCHAR(50) NULL,
                ADD COLUMN IF NOT EXISTS kode_ptn VARCHAR(50) NULL,
                ADD COLUMN IF NOT EXISTS web VARCHAR(255) NULL,
                ADD COLUMN IF NOT EXISTS jenis VARCHAR(100) DEFAULT 'PTN Akademik',
                ADD COLUMN IF NOT EXISTS jenis_kampus VARCHAR(100) DEFAULT 'Negeri',
                ADD COLUMN IF NOT EXISTS kota_kampus VARCHAR(255) NULL,
                ADD COLUMN IF NOT EXISTS kota VARCHAR(100) NULL,
                ADD COLUMN IF NOT EXISTS provinsi VARCHAR(100) NULL,
                ADD COLUMN IF NOT EXISTS alamat TEXT NULL,
                ADD COLUMN IF NOT EXISTS alamat_kampus TEXT NULL,
                ADD COLUMN IF NOT EXISTS akreditasi VARCHAR(50) NULL;
            CREATE INDEX IF NOT EXISTS idx_master_kampus_tenant_id ON pdss.master_kampus (tenant_id);
            CREATE INDEX IF NOT EXISTS idx_master_kampus_id_ptn ON pdss.master_kampus (id_ptn);
            CREATE INDEX IF NOT EXISTS idx_master_kampus_kode_ptn ON pdss.master_kampus (kode_ptn);
        ");

        // 2. Lengkapi pdss.master_kampus_prodi
        $pdo->exec("
            ALTER TABLE pdss.master_kampus_prodi 
                ADD COLUMN IF NOT EXISTS kampus_id UUID NULL,
                ADD COLUMN IF NOT EXISTS id_prodi VARCHAR(50) NULL,
                ADD COLUMN IF NOT EXISTS kode_prodi VARCHAR(50) NULL,
                ADD COLUMN IF NOT EXISTS id_ptn VARCHAR(50) NULL,
                ADD COLUMN IF NOT EXISTS program_studi VARCHAR(255) NULL,
                ADD COLUMN IF NOT EXISTS nama_prodi VARCHAR(255) NULL,
                ADD COLUMN IF NOT EXISTS fakultas VARCHAR(255) NULL,
                ADD COLUMN IF NOT EXISTS jenjang VARCHAR(50) DEFAULT 'Sarjana',
                ADD COLUMN IF NOT EXISTS daya_tampung_sekarang INT DEFAULT 0,
                ADD COLUMN IF NOT EXISTS jenis_portofolio VARCHAR(255) DEFAULT 'Tidak Ada';
            CREATE INDEX IF NOT EXISTS idx_master_prodi_kampus_id ON pdss.master_kampus_prodi (kampus_id);
            CREATE INDEX IF NOT EXISTS idx_master_prodi_kode_prodi ON pdss.master_kampus_prodi (kode_prodi);
            CREATE INDEX IF NOT EXISTS idx_master_prodi_id_prodi ON pdss.master_kampus_prodi (id_prodi);
            CREATE INDEX IF NOT EXISTS idx_master_prodi_id_ptn ON pdss.master_kampus_prodi (id_ptn);
        ");

        // 3. Buat/lengkapi pdss.kampus_prodi_riwayat
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS pdss.kampus_prodi_riwayat (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                prodi_id UUID NULL,
                id_prodi VARCHAR(50) NULL,
                kode_prodi VARCHAR(50) NULL,
                tahun INT NOT NULL,
                daya_tampung INT DEFAULT 0,
                jumlah_pendaftar INT DEFAULT 0,
                diterima INT DEFAULT 0,
                keketatan VARCHAR(50) NULL,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT uq_prodi_riwayat_tahun UNIQUE (prodi_id, tahun)
            );
            CREATE INDEX IF NOT EXISTS idx_kampus_riwayat_prodi_id ON pdss.kampus_prodi_riwayat (prodi_id);
            CREATE INDEX IF NOT EXISTS idx_kampus_riwayat_kode_prodi ON pdss.kampus_prodi_riwayat (kode_prodi);
            CREATE INDEX IF NOT EXISTS idx_kampus_riwayat_tahun ON pdss.kampus_prodi_riwayat (tahun);
        ");

        echo "- Skema pdss.master_kampus, master_kampus_prodi, dan kampus_prodi_riwayat berhasil diperbarui.\n";
    },
    'down' => function (PDO $pdo): void {
        $pdo->exec("DROP TABLE IF EXISTS pdss.kampus_prodi_riwayat CASCADE;");
    }
];
