<?php

/**
 * Migration PostgreSQL Schema 11: BK (8 Tabel)
 * Format: return ['up' => closure, 'down' => closure]
 */

return [
    'up' => function (PDO $pdo): void {
        echo "=== INSTALLING POSTGRESQL SCHEMA 11: BK (8 TABEL) ===\n";
        $pdo->exec("CREATE SCHEMA IF NOT EXISTS bk;");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS bk.catatan_bk (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL,
                id_siswa UUID NULL,
                snapshot_nama_siswa VARCHAR(255) NULL,
                snapshot_nisn VARCHAR(50) NULL,
                snapshot_nis VARCHAR(50) NULL,
                snapshot_nama_kelas VARCHAR(100) NULL,
                id_kelas_snapshot UUID NULL,
                id_guru_bk UUID NULL,
                tanggal_konseling DATE NULL,
                jenis_kasus VARCHAR(100) NULL,
                catatan TEXT NULL,
                tindak_lanjut TEXT NULL,
                status_kasus VARCHAR(50) DEFAULT 'Terbuka',
                is_rahasia SMALLINT DEFAULT 1,
                nama_catatan_bk VARCHAR(255) NULL,
                kategori VARCHAR(100) NULL,
                deskripsi TEXT NULL,
                is_active BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP
            );
            ALTER TABLE bk.catatan_bk ADD COLUMN IF NOT EXISTS id_siswa UUID NULL;
            ALTER TABLE bk.catatan_bk ADD COLUMN IF NOT EXISTS snapshot_nama_siswa VARCHAR(255) NULL;
            ALTER TABLE bk.catatan_bk ADD COLUMN IF NOT EXISTS snapshot_nisn VARCHAR(50) NULL;
            ALTER TABLE bk.catatan_bk ADD COLUMN IF NOT EXISTS snapshot_nis VARCHAR(50) NULL;
            ALTER TABLE bk.catatan_bk ADD COLUMN IF NOT EXISTS snapshot_nama_kelas VARCHAR(100) NULL;
            ALTER TABLE bk.catatan_bk ADD COLUMN IF NOT EXISTS id_kelas_snapshot UUID NULL;
            ALTER TABLE bk.catatan_bk ADD COLUMN IF NOT EXISTS id_guru_bk UUID NULL;
            ALTER TABLE bk.catatan_bk ADD COLUMN IF NOT EXISTS tanggal_konseling DATE NULL;
            ALTER TABLE bk.catatan_bk ADD COLUMN IF NOT EXISTS jenis_kasus VARCHAR(100) NULL;
            ALTER TABLE bk.catatan_bk ADD COLUMN IF NOT EXISTS catatan TEXT NULL;
            ALTER TABLE bk.catatan_bk ADD COLUMN IF NOT EXISTS tindak_lanjut TEXT NULL;
            ALTER TABLE bk.catatan_bk ADD COLUMN IF NOT EXISTS status_kasus VARCHAR(50) DEFAULT 'Terbuka';
            ALTER TABLE bk.catatan_bk ADD COLUMN IF NOT EXISTS is_rahasia SMALLINT DEFAULT 1;

            CREATE TABLE IF NOT EXISTS bk.catatan_bk_log (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                id_catatan_bk UUID NOT NULL,
                tenant_id UUID NULL,
                status_lama VARCHAR(50) NULL,
                status_baru VARCHAR(50) NULL,
                id_user UUID NULL,
                nama_user VARCHAR(255) NULL,
                peran_user VARCHAR(100) NULL,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS bk.master_jalur_masuk (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL,
                nama_jalur VARCHAR(255) NULL,
                nama_master_jalur_masuk VARCHAR(255) NULL,
                kategori VARCHAR(100) DEFAULT 'Lainnya',
                deskripsi TEXT NULL,
                is_active BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS bk.master_pelanggaran (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL,
                nama_master_pelanggaran VARCHAR(255) NULL,
                kategori VARCHAR(100) NULL,
                deskripsi TEXT NULL,
                is_active BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS bk.pelanggaran_siswa (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL,
                nama_pelanggaran_siswa VARCHAR(255) NULL,
                kategori VARCHAR(100) NULL,
                deskripsi TEXT NULL,
                is_active BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS bk.pembinaan_monitoring (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL,
                nama_pembinaan_monitoring VARCHAR(255) NULL,
                kategori VARCHAR(100) NULL,
                deskripsi TEXT NULL,
                is_active BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS bk.pilihan_penjurusan (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL,
                nama_pilihan_penjurusan VARCHAR(255) NULL,
                kategori VARCHAR(100) NULL,
                deskripsi TEXT NULL,
                is_active BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS bk.sesi_mentoring (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL,
                nama_sesi_mentoring VARCHAR(255) NULL,
                kategori VARCHAR(100) NULL,
                deskripsi TEXT NULL,
                is_active BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS bk.tindak_lanjut_sanksi (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL,
                nama_tindak_lanjut_sanksi VARCHAR(255) NULL,
                kategori VARCHAR(100) NULL,
                deskripsi TEXT NULL,
                is_active BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP
            );

        ");
        echo "- Schema BK (8 Tabel) Berhasil Dibuat.\n";
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("DROP SCHEMA IF EXISTS bk CASCADE;");
        echo "- Schema BK Berhasil Di-drop.\n";
    }
];
