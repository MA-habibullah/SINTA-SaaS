<?php

/**
 * Migration PostgreSQL Schema 13: TRACER (Konsolidasi Skema & Tabel Tracer Study)
 * Format: return ['up' => closure, 'down' => closure]
 */

return [
    'up' => function (PDO $pdo): void {
        echo "=== INSTALLING POSTGRESQL SCHEMA 13: TRACER (KONSOLIDASI TABEL) ===\n";
        $pdo->exec("CREATE SCHEMA IF NOT EXISTS tracer;");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS tracer.alumni (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NOT NULL,
                siswa_id UUID NULL,
                nama_alumni VARCHAR(255) NULL,
                nisn VARCHAR(50) NULL,
                tahun_lulus INT NULL,
                status_tracer VARCHAR(50) DEFAULT 'tidak_diketahui',
                keterangan TEXT NULL,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS tracer.arsip_dokumen_alumni (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL,
                nama_arsip_dokumen_alumni VARCHAR(255) NULL,
                kategori VARCHAR(100) NULL,
                deskripsi TEXT NULL,
                is_active BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS tracer.log_akses_arsip (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL,
                nama_log_akses_arsip VARCHAR(255) NULL,
                kategori VARCHAR(100) NULL,
                deskripsi TEXT NULL,
                is_active BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS tracer.riwayat_kuliah (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NOT NULL,
                alumni_id UUID NULL,
                nama_kampus VARCHAR(255) NULL,
                nama_prodi VARCHAR(255) NULL,
                jalur_masuk VARCHAR(100) NULL,
                tahun_masuk INT NULL,
                status_kuliah VARCHAR(50) DEFAULT 'aktif',
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );
            ALTER TABLE tracer.riwayat_kuliah ADD COLUMN IF NOT EXISTS alumni_id UUID NULL;
            ALTER TABLE tracer.riwayat_kuliah ADD COLUMN IF NOT EXISTS nama_kampus VARCHAR(255) NULL;
            ALTER TABLE tracer.riwayat_kuliah ADD COLUMN IF NOT EXISTS nama_prodi VARCHAR(255) NULL;
            ALTER TABLE tracer.riwayat_kuliah ADD COLUMN IF NOT EXISTS jalur_masuk VARCHAR(100) NULL;
            ALTER TABLE tracer.riwayat_kuliah ADD COLUMN IF NOT EXISTS tahun_masuk INT NULL;
            ALTER TABLE tracer.riwayat_kuliah ADD COLUMN IF NOT EXISTS status_kuliah VARCHAR(50) DEFAULT 'aktif';

            CREATE TABLE IF NOT EXISTS tracer.riwayat_pekerjaan (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NOT NULL,
                alumni_id UUID NULL,
                nama_perusahaan VARCHAR(255) NULL,
                posisi VARCHAR(255) NULL,
                jenis_instansi VARCHAR(100) NULL,
                tahun_mulai INT NULL,
                tahun_selesai INT NULL,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );
            ALTER TABLE tracer.riwayat_pekerjaan ADD COLUMN IF NOT EXISTS alumni_id UUID NULL;
            ALTER TABLE tracer.riwayat_pekerjaan ADD COLUMN IF NOT EXISTS nama_perusahaan VARCHAR(255) NULL;
            ALTER TABLE tracer.riwayat_pekerjaan ADD COLUMN IF NOT EXISTS posisi VARCHAR(255) NULL;
            ALTER TABLE tracer.riwayat_pekerjaan ADD COLUMN IF NOT EXISTS jenis_instansi VARCHAR(100) NULL;
            ALTER TABLE tracer.riwayat_pekerjaan ADD COLUMN IF NOT EXISTS tahun_mulai INT NULL;
            ALTER TABLE tracer.riwayat_pekerjaan ADD COLUMN IF NOT EXISTS tahun_selesai INT NULL;

            CREATE TABLE IF NOT EXISTS tracer.tracer_study_alumni (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL,
                nama_tracer_study_alumni VARCHAR(255) NULL,
                kategori VARCHAR(100) NULL,
                deskripsi TEXT NULL,
                is_active BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP
            );
        ");

        echo "- Schema TRACER (Konsolidasi Skema & Tabel) Berhasil Dibuat.\n";
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("DROP SCHEMA IF EXISTS tracer CASCADE;");
        echo "- Schema TRACER Berhasil Di-drop.\n";
    }
];
