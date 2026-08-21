<?php

/**
 * Migration PostgreSQL Schema: Pembaruan Struktur Tabel Ekstrakurikuler Kesiswaan
 * Format: return ['up' => closure, 'down' => closure]
 */

return [
    'up' => function (PDO $pdo): void {
        // 1. Pembaruan tabel master_ekskul
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS kesiswaan.master_ekskul (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NOT NULL,
                nama_ekskul VARCHAR(255) NOT NULL,
                kategori VARCHAR(100) DEFAULT 'Pilihan',
                deskripsi TEXT NULL,
                pembina_id UUID NULL,
                nama_pembina VARCHAR(255) NULL,
                hari_latihan VARCHAR(50) NULL,
                jam_mulai VARCHAR(10) NULL,
                jam_selesai VARCHAR(10) NULL,
                tempat_latihan VARCHAR(255) NULL,
                kuota_maksimal INT DEFAULT 0,
                is_active BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                deleted_at TIMESTAMP WITH TIME ZONE NULL
            );

            ALTER TABLE kesiswaan.master_ekskul ADD COLUMN IF NOT EXISTS nama_ekskul VARCHAR(255) NULL;
            ALTER TABLE kesiswaan.master_ekskul ADD COLUMN IF NOT EXISTS pembina_id UUID NULL;
            ALTER TABLE kesiswaan.master_ekskul ADD COLUMN IF NOT EXISTS nama_pembina VARCHAR(255) NULL;
            ALTER TABLE kesiswaan.master_ekskul ADD COLUMN IF NOT EXISTS hari_latihan VARCHAR(50) NULL;
            ALTER TABLE kesiswaan.master_ekskul ADD COLUMN IF NOT EXISTS jam_mulai VARCHAR(10) NULL;
            ALTER TABLE kesiswaan.master_ekskul ADD COLUMN IF NOT EXISTS jam_selesai VARCHAR(10) NULL;
            ALTER TABLE kesiswaan.master_ekskul ADD COLUMN IF NOT EXISTS tempat_latihan VARCHAR(255) NULL;
            ALTER TABLE kesiswaan.master_ekskul ADD COLUMN IF NOT EXISTS kuota_maksimal INT DEFAULT 0;
            ALTER TABLE kesiswaan.master_ekskul ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP WITH TIME ZONE NULL;
        ");

        // 2. Pembaruan tabel data_pembina
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS kesiswaan.data_pembina (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NOT NULL,
                nama_pembina VARCHAR(255) NOT NULL,
                guru_id UUID NULL,
                nip VARCHAR(50) NULL,
                jenis_kelamin VARCHAR(20) NULL,
                no_hp VARCHAR(50) NULL,
                email VARCHAR(100) NULL,
                kategori_pembina VARCHAR(50) DEFAULT 'Guru Internal',
                is_active BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );

            ALTER TABLE kesiswaan.data_pembina ADD COLUMN IF NOT EXISTS nama_pembina VARCHAR(255) NULL;
            ALTER TABLE kesiswaan.data_pembina ADD COLUMN IF NOT EXISTS guru_id UUID NULL;
            ALTER TABLE kesiswaan.data_pembina ADD COLUMN IF NOT EXISTS nip VARCHAR(50) NULL;
            ALTER TABLE kesiswaan.data_pembina ADD COLUMN IF NOT EXISTS jenis_kelamin VARCHAR(20) NULL;
            ALTER TABLE kesiswaan.data_pembina ADD COLUMN IF NOT EXISTS no_hp VARCHAR(50) NULL;
            ALTER TABLE kesiswaan.data_pembina ADD COLUMN IF NOT EXISTS email VARCHAR(100) NULL;
            ALTER TABLE kesiswaan.data_pembina ADD COLUMN IF NOT EXISTS kategori_pembina VARCHAR(50) DEFAULT 'Guru Internal';
        ");

        // 3. Pembaruan tabel anggota_ekskul
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS kesiswaan.anggota_ekskul (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NOT NULL,
                ekskul_id UUID NOT NULL,
                siswa_id UUID NOT NULL,
                tahun_ajaran_id VARCHAR(100) NOT NULL,
                semester VARCHAR(20) NOT NULL DEFAULT 'Ganjil',
                jabatan VARCHAR(50) DEFAULT 'Anggota',
                nomor_anggota VARCHAR(50) NULL,
                tanggal_bergabung DATE DEFAULT CURRENT_DATE,
                status_keanggotaan VARCHAR(20) DEFAULT 'Aktif',
                catatan TEXT NULL,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );

            ALTER TABLE kesiswaan.anggota_ekskul ADD COLUMN IF NOT EXISTS ekskul_id UUID NULL;
            ALTER TABLE kesiswaan.anggota_ekskul ADD COLUMN IF NOT EXISTS siswa_id UUID NULL;
            ALTER TABLE kesiswaan.anggota_ekskul ADD COLUMN IF NOT EXISTS tahun_ajaran_id VARCHAR(100) NULL;
            ALTER TABLE kesiswaan.anggota_ekskul ADD COLUMN IF NOT EXISTS semester VARCHAR(20) DEFAULT 'Ganjil';
            ALTER TABLE kesiswaan.anggota_ekskul ADD COLUMN IF NOT EXISTS jabatan VARCHAR(50) DEFAULT 'Anggota';
            ALTER TABLE kesiswaan.anggota_ekskul ADD COLUMN IF NOT EXISTS nomor_anggota VARCHAR(50) NULL;
            ALTER TABLE kesiswaan.anggota_ekskul ADD COLUMN IF NOT EXISTS tanggal_bergabung DATE DEFAULT CURRENT_DATE;
            ALTER TABLE kesiswaan.anggota_ekskul ADD COLUMN IF NOT EXISTS status_keanggotaan VARCHAR(20) DEFAULT 'Aktif';
            ALTER TABLE kesiswaan.anggota_ekskul ADD COLUMN IF NOT EXISTS catatan TEXT NULL;
        ");

        // 4. Pembaruan tabel jurnal_ekskul
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS kesiswaan.jurnal_ekskul (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NOT NULL,
                ekskul_id UUID NOT NULL,
                pembina_id UUID NULL,
                tahun_ajaran_id VARCHAR(100) NOT NULL,
                semester VARCHAR(20) NOT NULL DEFAULT 'Ganjil',
                tanggal_kegiatan DATE NOT NULL,
                jam_mulai VARCHAR(10) NULL,
                jam_selesai VARCHAR(10) NULL,
                materi_kegiatan TEXT NOT NULL,
                lokasi VARCHAR(255) NULL,
                jumlah_hadir INT DEFAULT 0,
                jumlah_absen INT DEFAULT 0,
                foto_kegiatan TEXT NULL,
                catatan_evaluasi TEXT NULL,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );

            ALTER TABLE kesiswaan.jurnal_ekskul ADD COLUMN IF NOT EXISTS ekskul_id UUID NULL;
            ALTER TABLE kesiswaan.jurnal_ekskul ADD COLUMN IF NOT EXISTS pembina_id UUID NULL;
            ALTER TABLE kesiswaan.jurnal_ekskul ADD COLUMN IF NOT EXISTS tahun_ajaran_id VARCHAR(100) NULL;
            ALTER TABLE kesiswaan.jurnal_ekskul ADD COLUMN IF NOT EXISTS semester VARCHAR(20) DEFAULT 'Ganjil';
            ALTER TABLE kesiswaan.jurnal_ekskul ADD COLUMN IF NOT EXISTS tanggal_kegiatan DATE NULL;
            ALTER TABLE kesiswaan.jurnal_ekskul ADD COLUMN IF NOT EXISTS jam_mulai VARCHAR(10) NULL;
            ALTER TABLE kesiswaan.jurnal_ekskul ADD COLUMN IF NOT EXISTS jam_selesai VARCHAR(10) NULL;
            ALTER TABLE kesiswaan.jurnal_ekskul ADD COLUMN IF NOT EXISTS materi_kegiatan TEXT NULL;
            ALTER TABLE kesiswaan.jurnal_ekskul ADD COLUMN IF NOT EXISTS lokasi VARCHAR(255) NULL;
            ALTER TABLE kesiswaan.jurnal_ekskul ADD COLUMN IF NOT EXISTS jumlah_hadir INT DEFAULT 0;
            ALTER TABLE kesiswaan.jurnal_ekskul ADD COLUMN IF NOT EXISTS jumlah_absen INT DEFAULT 0;
            ALTER TABLE kesiswaan.jurnal_ekskul ADD COLUMN IF NOT EXISTS foto_kegiatan TEXT NULL;
            ALTER TABLE kesiswaan.jurnal_ekskul ADD COLUMN IF NOT EXISTS catatan_evaluasi TEXT NULL;
        ");

        // 5. Pembaruan tabel nilai_ekskul
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS kesiswaan.nilai_ekskul (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NOT NULL,
                ekskul_id UUID NOT NULL,
                siswa_id UUID NOT NULL,
                tahun_ajaran_id VARCHAR(100) NOT NULL,
                semester VARCHAR(20) NOT NULL DEFAULT 'Ganjil',
                predikat VARCHAR(10) NOT NULL DEFAULT 'A',
                nilai_angka NUMERIC(5,2) NULL,
                keterangan TEXT NULL,
                is_locked BOOLEAN DEFAULT false,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );

            ALTER TABLE kesiswaan.nilai_ekskul ADD COLUMN IF NOT EXISTS ekskul_id UUID NULL;
            ALTER TABLE kesiswaan.nilai_ekskul ADD COLUMN IF NOT EXISTS siswa_id UUID NULL;
            ALTER TABLE kesiswaan.nilai_ekskul ADD COLUMN IF NOT EXISTS tahun_ajaran_id VARCHAR(100) NULL;
            ALTER TABLE kesiswaan.nilai_ekskul ADD COLUMN IF NOT EXISTS semester VARCHAR(20) DEFAULT 'Ganjil';
            ALTER TABLE kesiswaan.nilai_ekskul ADD COLUMN IF NOT EXISTS predikat VARCHAR(10) DEFAULT 'A';
            ALTER TABLE kesiswaan.nilai_ekskul ADD COLUMN IF NOT EXISTS nilai_angka NUMERIC(5,2) NULL;
            ALTER TABLE kesiswaan.nilai_ekskul ADD COLUMN IF NOT EXISTS keterangan TEXT NULL;
            ALTER TABLE kesiswaan.nilai_ekskul ADD COLUMN IF NOT EXISTS is_locked BOOLEAN DEFAULT false;
        ");

        // 6. Pembaruan tabel kunci_ekskul
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS kesiswaan.kunci_ekskul (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NOT NULL,
                ekskul_id UUID NOT NULL,
                tahun_ajaran_id VARCHAR(100) NOT NULL,
                semester VARCHAR(20) NOT NULL DEFAULT 'Ganjil',
                lock_anggota BOOLEAN DEFAULT false,
                lock_nilai BOOLEAN DEFAULT false,
                locked_by VARCHAR(255) NULL,
                locked_at TIMESTAMP WITH TIME ZONE NULL,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );

            ALTER TABLE kesiswaan.kunci_ekskul ADD COLUMN IF NOT EXISTS ekskul_id UUID NULL;
            ALTER TABLE kesiswaan.kunci_ekskul ADD COLUMN IF NOT EXISTS tahun_ajaran_id VARCHAR(100) NULL;
            ALTER TABLE kesiswaan.kunci_ekskul ADD COLUMN IF NOT EXISTS semester VARCHAR(20) DEFAULT 'Ganjil';
            ALTER TABLE kesiswaan.kunci_ekskul ADD COLUMN IF NOT EXISTS lock_anggota BOOLEAN DEFAULT false;
            ALTER TABLE kesiswaan.kunci_ekskul ADD COLUMN IF NOT EXISTS lock_nilai BOOLEAN DEFAULT false;
            ALTER TABLE kesiswaan.kunci_ekskul ADD COLUMN IF NOT EXISTS locked_by VARCHAR(255) NULL;
            ALTER TABLE kesiswaan.kunci_ekskul ADD COLUMN IF NOT EXISTS locked_at TIMESTAMP WITH TIME ZONE NULL;
        ");

        echo "- Schema KESISWAAN (Ekstrakurikuler) berhasil diperbarui dan diselaraskan.\n";
    },

    'down' => function (PDO $pdo): void {
        // Rollback columns if needed
    }
];
