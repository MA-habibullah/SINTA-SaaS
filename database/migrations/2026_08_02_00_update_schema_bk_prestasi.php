<?php

/**
 * Migration PostgreSQL: Update Schema BK, Kesiswaan Prestasi & Absensi Semester
 * Format: return ['up' => closure, 'down' => closure]
 */

return [
    'up' => function (PDO $pdo): void {
        echo "=== UPDATING POSTGRESQL SCHEMA FOR BK, PRESTASI SISWA & ABSENSI SEMESTER ===\n";

        // 1. Skema bk.catatan_bk
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
        ");
        echo "- Tabel bk.catatan_bk & bk.catatan_bk_log berhasil ditambahkan/diperbarui.\n";

        // 2. Skema kesiswaan.prestasi_siswa & kesiswaan.prestasi_siswa_anggota
        $pdo->exec("CREATE SCHEMA IF NOT EXISTS kesiswaan;");
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS kesiswaan.prestasi_siswa (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL,
                nama_prestasi_siswa VARCHAR(255) NULL,
                kategori VARCHAR(100) NULL,
                deskripsi TEXT NULL,
                is_active BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP
            );

            ALTER TABLE kesiswaan.prestasi_siswa ADD COLUMN IF NOT EXISTS tahun_ajaran_id VARCHAR(100) NULL;
            ALTER TABLE kesiswaan.prestasi_siswa ADD COLUMN IF NOT EXISTS semester VARCHAR(50) NULL;
            ALTER TABLE kesiswaan.prestasi_siswa ADD COLUMN IF NOT EXISTS bidang_lomba VARCHAR(255) NULL;
            ALTER TABLE kesiswaan.prestasi_siswa ADD COLUMN IF NOT EXISTS nama_lomba VARCHAR(255) NULL;
            ALTER TABLE kesiswaan.prestasi_siswa ADD COLUMN IF NOT EXISTS nomor_sertifikat VARCHAR(255) NULL;
            ALTER TABLE kesiswaan.prestasi_siswa ADD COLUMN IF NOT EXISTS juara VARCHAR(100) NULL;
            ALTER TABLE kesiswaan.prestasi_siswa ADD COLUMN IF NOT EXISTS tingkat_kejuaraan VARCHAR(100) NULL;
            ALTER TABLE kesiswaan.prestasi_siswa ADD COLUMN IF NOT EXISTS jenis_lomba VARCHAR(50) DEFAULT 'Offline';
            ALTER TABLE kesiswaan.prestasi_siswa ADD COLUMN IF NOT EXISTS tempat_lomba VARCHAR(255) NULL;
            ALTER TABLE kesiswaan.prestasi_siswa ADD COLUMN IF NOT EXISTS tanggal_lomba DATE NULL;
            ALTER TABLE kesiswaan.prestasi_siswa ADD COLUMN IF NOT EXISTS penyelenggara VARCHAR(255) NULL;
            ALTER TABLE kesiswaan.prestasi_siswa ADD COLUMN IF NOT EXISTS guru_pendamping VARCHAR(255) NULL;
            ALTER TABLE kesiswaan.prestasi_siswa ADD COLUMN IF NOT EXISTS poin_prestasi INT DEFAULT 0;
            ALTER TABLE kesiswaan.prestasi_siswa ADD COLUMN IF NOT EXISTS foto_bukti_prestasi VARCHAR(512) NULL;
            ALTER TABLE kesiswaan.prestasi_siswa ADD COLUMN IF NOT EXISTS foto_siswa_prestasi VARCHAR(512) NULL;
            ALTER TABLE kesiswaan.prestasi_siswa ADD COLUMN IF NOT EXISTS foto_kegiatan_lomba VARCHAR(512) NULL;
            ALTER TABLE kesiswaan.prestasi_siswa ADD COLUMN IF NOT EXISTS surat_tugas_pdf VARCHAR(512) NULL;

            CREATE TABLE IF NOT EXISTS kesiswaan.prestasi_siswa_anggota (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                id_prestasi UUID NOT NULL,
                id_siswa UUID NOT NULL,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );
        ");
        echo "- Tabel kesiswaan.prestasi_siswa & kesiswaan.prestasi_siswa_anggota berhasil diperbarui.\n";

        // 3. Skema siswa.absensi_semester
        $pdo->exec("
            ALTER TABLE siswa.absensi_semester ADD COLUMN IF NOT EXISTS tahun_ajaran_id VARCHAR(100) NULL;
            ALTER TABLE siswa.absensi_semester ADD COLUMN IF NOT EXISTS alfa SMALLINT DEFAULT 0;
        ");
        echo "- Tabel siswa.absensi_semester berhasil diperbarui.\n";
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("DROP TABLE IF EXISTS kesiswaan.prestasi_siswa_anggota CASCADE;");
        $pdo->exec("DROP TABLE IF EXISTS bk.catatan_bk_log CASCADE;");
    }
];
