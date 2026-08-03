<?php

/**
 * Migration PostgreSQL Schema 12: PDSS (Konsolidasi Skema & Tabel PDSS)
 * Format: return ['up' => closure, 'down' => closure]
 */

return [
    'up' => function (PDO $pdo): void {
        echo "=== INSTALLING POSTGRESQL SCHEMA 12: PDSS (KONSOLIDASI TABEL) ===\n";
        $pdo->exec("CREATE SCHEMA IF NOT EXISTS pdss;");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS pdss.master_kampus (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL,
                nama_kampus VARCHAR(255) NOT NULL,
                kota_kampus VARCHAR(255) NULL,
                alamat_kampus TEXT NULL,
                jenis_kampus VARCHAR(100) DEFAULT 'Negeri',
                jenis VARCHAR(50) DEFAULT 'PTN',
                akreditasi VARCHAR(50) NULL,
                kota VARCHAR(100) NULL,
                provinsi VARCHAR(100) NULL,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );
            ALTER TABLE pdss.master_kampus ADD COLUMN IF NOT EXISTS jenis VARCHAR(50) DEFAULT 'PTN';
            ALTER TABLE pdss.master_kampus ADD COLUMN IF NOT EXISTS akreditasi VARCHAR(50) NULL;
            ALTER TABLE pdss.master_kampus ADD COLUMN IF NOT EXISTS kota VARCHAR(100) NULL;
            ALTER TABLE pdss.master_kampus ADD COLUMN IF NOT EXISTS provinsi VARCHAR(100) NULL;

            CREATE TABLE IF NOT EXISTS pdss.master_kampus_prodi (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                kampus_id UUID NULL,
                tenant_id UUID NULL,
                kode_prodi VARCHAR(50) NULL,
                fakultas VARCHAR(255) NULL,
                program_studi VARCHAR(255) NULL,
                jenjang VARCHAR(50) NULL,
                jenis_portofolio VARCHAR(255) NULL,
                nama_master_kampus_prodi VARCHAR(255) NULL,
                kategori VARCHAR(100) NULL,
                deskripsi TEXT NULL,
                is_active BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS pdss.kampus_prodi_riwayat (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                prodi_id UUID NULL,
                tahun INT NULL,
                daya_tampung INT DEFAULT 0,
                jumlah_pendaftar INT DEFAULT 0,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS pdss.pdss_config_mapel (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL,
                nama_pdss_config_mapel VARCHAR(255) NULL,
                kategori VARCHAR(100) NULL,
                deskripsi TEXT NULL,
                is_active BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS pdss.pdss_lock (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL,
                nama_pdss_lock VARCHAR(255) NULL,
                kategori VARCHAR(100) NULL,
                deskripsi TEXT NULL,
                is_active BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS pdss.pdss_manual_eligible (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL,
                nama_pdss_manual_eligible VARCHAR(255) NULL,
                kategori VARCHAR(100) NULL,
                deskripsi TEXT NULL,
                is_active BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS pdss.pdss_simulasi (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL,
                nama_pdss_simulasi VARCHAR(255) NULL,
                kategori VARCHAR(100) NULL,
                deskripsi TEXT NULL,
                is_active BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS pdss.pdss_simulasi_setting (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL,
                nama_pdss_simulasi_setting VARCHAR(255) NULL,
                kategori VARCHAR(100) NULL,
                deskripsi TEXT NULL,
                is_active BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS pdss.target_kampus (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL,
                nama_target_kampus VARCHAR(255) NULL,
                kategori VARCHAR(100) NULL,
                deskripsi TEXT NULL,
                is_active BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS pdss.kesiapan_siswa (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NOT NULL,
                siswa_id UUID NOT NULL,
                tahun_ajaran_id UUID NULL,
                is_eligible BOOLEAN DEFAULT FALSE,
                nilai_rata_rata NUMERIC(5,2) DEFAULT 0,
                ranking_sekolah INT DEFAULT 0,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );
            CREATE UNIQUE INDEX IF NOT EXISTS idx_kesiapan_tenant_siswa_ta 
            ON pdss.kesiapan_siswa (tenant_id, siswa_id, COALESCE(tahun_ajaran_id, '00000000-0000-0000-0000-000000000000'));

            CREATE TABLE IF NOT EXISTS pdss.simulasi_setting (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NOT NULL,
                tahun_ajaran_id UUID NULL,
                no_simulasi INT NOT NULL DEFAULT 1,
                is_open BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );
            CREATE UNIQUE INDEX IF NOT EXISTS idx_simulasi_setting_tenant_ta_sim 
            ON pdss.simulasi_setting (tenant_id, COALESCE(tahun_ajaran_id, '00000000-0000-0000-0000-000000000000'), no_simulasi);

            CREATE TABLE IF NOT EXISTS pdss.pilihan_kampus (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NOT NULL,
                siswa_id UUID NOT NULL,
                tahun_ajaran_id UUID NULL,
                no_simulasi INT DEFAULT 1,
                no_pilihan INT DEFAULT 1,
                kampus_id UUID NULL,
                prodi_id UUID NULL,
                status VARCHAR(50) DEFAULT 'draft',
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS pdss.master_prodi (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                kampus_id UUID NULL,
                nama_prodi VARCHAR(255) NOT NULL,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS pdss.master_jalur_masuk (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                nama_jalur VARCHAR(255) NOT NULL,
                deskripsi TEXT NULL,
                persyaratan TEXT NULL,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );
        ");

        // Seed default Jalur Masuk if empty
        $pdo->exec("
            INSERT INTO pdss.master_jalur_masuk (id, nama_jalur, deskripsi, persyaratan)
            SELECT gen_random_uuid(), 'SNBP', 'Seleksi Nasional Berdasarkan Prestasi (Prestasi Rapor & Sertifikat)', 'Siswa Eligible Kelas 12'
            WHERE NOT EXISTS (SELECT 1 FROM pdss.master_jalur_masuk WHERE nama_jalur = 'SNBP');

            INSERT INTO pdss.master_jalur_masuk (id, nama_jalur, deskripsi, persyaratan)
            SELECT gen_random_uuid(), 'SNBT', 'Seleksi Nasional Berdasarkan Tes (UTBK)', 'Lulusan SMA/SMK/MA Kelas 12 & Alumni max 3 thn'
            WHERE NOT EXISTS (SELECT 1 FROM pdss.master_jalur_masuk WHERE nama_jalur = 'SNBT');

            INSERT INTO pdss.master_jalur_masuk (id, nama_jalur, deskripsi, persyaratan)
            SELECT gen_random_uuid(), 'Mandiri', 'Seleksi Mandiri Perguruan Tinggi Negeri/Swasta', 'Tes Mandiri / Nilai UTBK'
            WHERE NOT EXISTS (SELECT 1 FROM pdss.master_jalur_masuk WHERE nama_jalur = 'Mandiri');
        ");

        echo "- Schema PDSS (Konsolidasi Skema & Tabel) Berhasil Dibuat.\n";
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("DROP SCHEMA IF EXISTS pdss CASCADE;");
        echo "- Schema PDSS Berhasil Di-drop.\n";
    }
];
