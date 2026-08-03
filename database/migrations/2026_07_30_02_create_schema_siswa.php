<?php

/**
 * Migration PostgreSQL Schema 02: SISWA (9 Tabel)
 * SSOT Reference: docs/portal_schema/siswa.html
 * Format: return ['up' => closure, 'down' => closure] (WAJIB AGENTS.md)
 */

return [
    'up' => function (PDO $pdo): void {
        echo "=== INSTALLING POSTGRESQL SCHEMA 02: SISWA (9 TABEL) ===\n";
        $pdo->exec("CREATE SCHEMA IF NOT EXISTS siswa;");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS siswa.siswa (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NOT NULL REFERENCES core.tenants(id) ON DELETE CASCADE,
                nisn CHAR(10) NOT NULL,
                nis VARCHAR(20) NULL,
                nama_lengkap VARCHAR(255) NOT NULL,
                jenis_kelamin CHAR(1) NULL CHECK (jenis_kelamin IN ('L','P')),
                tempat_lahir VARCHAR(100) NULL,
                tanggal_lahir DATE NULL,
                agama VARCHAR(30) NULL,
                foto_url VARCHAR(512) NULL,
                email VARCHAR(255) NULL,
                no_hp VARCHAR(20) NULL,
                alamat TEXT NULL,
                kelas_saat_ini VARCHAR(50) NULL,
                jurusan VARCHAR(100) NULL,
                angkatan SMALLINT NULL,
                tahun_masuk SMALLINT NULL,
                tahun_lulus SMALLINT NULL,
                status_siswa VARCHAR(20) NOT NULL DEFAULT 'aktif',
                password VARCHAR(255) NULL,
                is_first_login BOOLEAN NOT NULL DEFAULT true,
                is_active BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT uq_siswa_tenant_nisn UNIQUE (tenant_id, nisn)
            );

            CREATE TABLE IF NOT EXISTS siswa.fisik_kesehatan_siswa (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                siswa_id UUID NOT NULL REFERENCES siswa.siswa(id) ON DELETE CASCADE,
                tenant_id UUID NOT NULL REFERENCES core.tenants(id) ON DELETE CASCADE,
                tinggi_badan SMALLINT NULL,
                berat_badan SMALLINT NULL,
                golongan_darah VARCHAR(5) NULL,
                riwayat_penyakit TEXT NULL,
                alergi TEXT NULL,
                disabilitas VARCHAR(100) NULL,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS siswa.orang_tua (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                siswa_id UUID NOT NULL REFERENCES siswa.siswa(id) ON DELETE CASCADE,
                tenant_id UUID NOT NULL REFERENCES core.tenants(id) ON DELETE CASCADE,
                hubungan VARCHAR(20) NOT NULL DEFAULT 'ayah',
                nama_lengkap VARCHAR(255) NULL,
                nik CHAR(16) NULL,
                pekerjaan VARCHAR(100) NULL,
                pendidikan VARCHAR(50) NULL,
                penghasilan BIGINT NULL,
                no_hp VARCHAR(20) NULL,
                alamat TEXT NULL,
                is_aktif BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS siswa.registrasi (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                siswa_id UUID NOT NULL REFERENCES siswa.siswa(id) ON DELETE CASCADE,
                tenant_id UUID NOT NULL REFERENCES core.tenants(id) ON DELETE CASCADE,
                jenis_pendaftaran VARCHAR(50) NULL,
                asal_sekolah VARCHAR(255) NULL,
                npsn_asal CHAR(8) NULL,
                tahun_daftar SMALLINT NULL,
                no_pendaftaran VARCHAR(50) NULL,
                status_ppdb VARCHAR(30) NULL DEFAULT 'diterima',
                catatan TEXT NULL,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS siswa.absensi_semester (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                siswa_id UUID NOT NULL REFERENCES siswa.siswa(id) ON DELETE CASCADE,
                tenant_id UUID NOT NULL REFERENCES core.tenants(id) ON DELETE CASCADE,
                tahun_ajaran VARCHAR(50) NULL,
                tahun_ajaran_id UUID NULL,
                semester VARCHAR(50) NULL,
                hadir SMALLINT NOT NULL DEFAULT 0,
                sakit SMALLINT NOT NULL DEFAULT 0,
                izin SMALLINT NOT NULL DEFAULT 0,
                alpha SMALLINT NOT NULL DEFAULT 0,
                alfa SMALLINT NOT NULL DEFAULT 0,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );
            ALTER TABLE siswa.absensi_semester ADD COLUMN IF NOT EXISTS tahun_ajaran_id UUID NULL;
            ALTER TABLE siswa.absensi_semester ADD COLUMN IF NOT EXISTS alfa SMALLINT DEFAULT 0;

            CREATE TABLE IF NOT EXISTS siswa.riwayat_kenaikan_kelas (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                siswa_id UUID NOT NULL REFERENCES siswa.siswa(id) ON DELETE CASCADE,
                tenant_id UUID NOT NULL REFERENCES core.tenants(id) ON DELETE CASCADE,
                tahun_ajaran VARCHAR(20) NOT NULL,
                dari_kelas VARCHAR(50) NULL,
                ke_kelas VARCHAR(50) NULL,
                status VARCHAR(30) NULL DEFAULT 'naik',
                catatan TEXT NULL,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS siswa.riwayat_beasiswa (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                siswa_id UUID NOT NULL REFERENCES siswa.siswa(id) ON DELETE CASCADE,
                tenant_id UUID NOT NULL REFERENCES core.tenants(id) ON DELETE CASCADE,
                nama_beasiswa VARCHAR(255) NOT NULL,
                penyelenggara VARCHAR(255) NULL,
                tahun_mulai SMALLINT NULL,
                tahun_selesai SMALLINT NULL,
                nominal BIGINT NULL,
                keterangan TEXT NULL,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS siswa.anggota_kelas (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                siswa_id UUID NOT NULL REFERENCES siswa.siswa(id) ON DELETE CASCADE,
                tenant_id UUID NOT NULL REFERENCES core.tenants(id) ON DELETE CASCADE,
                kelas_id UUID NULL,
                tahun_ajaran VARCHAR(20) NOT NULL,
                nomor_absen SMALLINT NULL,
                is_aktif BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS siswa.dokumen (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                siswa_id UUID NOT NULL REFERENCES siswa.siswa(id) ON DELETE CASCADE,
                tenant_id UUID NOT NULL REFERENCES core.tenants(id) ON DELETE CASCADE,
                jenis_dokumen VARCHAR(100) NOT NULL,
                nama_file VARCHAR(255) NOT NULL,
                url_file VARCHAR(512) NOT NULL,
                keterangan TEXT NULL,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );

        ");
        echo "- Schema SISWA (9 Tabel) Berhasil Dibuat.\n";
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("DROP SCHEMA IF EXISTS siswa CASCADE;");
        echo "- Schema SISWA Berhasil Di-drop.\n";
    }
];
