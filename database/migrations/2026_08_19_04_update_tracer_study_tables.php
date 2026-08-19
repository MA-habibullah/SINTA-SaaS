<?php
/**
 * Migration: Update Schema Tracer Tables (Riwayat Kuliah & Riwayat Pekerjaan)
 * 
 * Menambahkan kolom-kolom pendukung untuk:
 * - Alumni luar sistem / lawas (nama_alumni, nisn, is_manual)
 * - Kampus swasta / manual (nama_kampus, prodi_id, kampus_id, is_kampus_swasta, fakultas, tahun_lulus)
 * - Riwayat pekerjaan (siswa_id, nama_alumni, pendapatan_bulanan, status_kerja, is_manual)
 */
return [
    'up' => function (PDO $pdo): void {
        // 1. Pastikan tabel tracer.alumni mendukung data manual
        $pdo->exec("
            ALTER TABLE tracer.alumni
            ADD COLUMN IF NOT EXISTS nis VARCHAR(50),
            ADD COLUMN IF NOT EXISTS jurusan_asal VARCHAR(100),
            ADD COLUMN IF NOT EXISTS is_manual BOOLEAN DEFAULT FALSE;
        ");

        // 2. Perbarui tabel tracer.riwayat_kuliah
        $pdo->exec("
            ALTER TABLE tracer.riwayat_kuliah
            ADD COLUMN IF NOT EXISTS siswa_id UUID,
            ADD COLUMN IF NOT EXISTS nama_alumni VARCHAR(255),
            ADD COLUMN IF NOT EXISTS nisn VARCHAR(50),
            ADD COLUMN IF NOT EXISTS fakultas VARCHAR(255),
            ADD COLUMN IF NOT EXISTS tahun_lulus INTEGER,
            ADD COLUMN IF NOT EXISTS kampus_id UUID,
            ADD COLUMN IF NOT EXISTS prodi_id UUID,
            ADD COLUMN IF NOT EXISTS jalur_masuk_id INTEGER,
            ADD COLUMN IF NOT EXISTS jenjang VARCHAR(50) DEFAULT 'S1',
            ADD COLUMN IF NOT EXISTS is_manual BOOLEAN DEFAULT FALSE,
            ADD COLUMN IF NOT EXISTS is_kampus_swasta BOOLEAN DEFAULT FALSE;
        ");

        // 3. Perbarui tabel tracer.riwayat_pekerjaan
        $pdo->exec("
            ALTER TABLE tracer.riwayat_pekerjaan
            ADD COLUMN IF NOT EXISTS siswa_id UUID,
            ADD COLUMN IF NOT EXISTS nama_alumni VARCHAR(255),
            ADD COLUMN IF NOT EXISTS nisn VARCHAR(50),
            ADD COLUMN IF NOT EXISTS posisi_jabatan VARCHAR(255),
            ADD COLUMN IF NOT EXISTS pendapatan_bulanan VARCHAR(100),
            ADD COLUMN IF NOT EXISTS status_kerja VARCHAR(50) DEFAULT 'Kontrak',
            ADD COLUMN IF NOT EXISTS is_manual BOOLEAN DEFAULT FALSE;
        ");

        echo "- Kolom tabel tracer.alumni, tracer.riwayat_kuliah, dan tracer.riwayat_pekerjaan berhasil diperbarui.\n";
    },
    'down' => function (PDO $pdo): void {
        $pdo->exec("
            ALTER TABLE tracer.riwayat_kuliah
            DROP COLUMN IF EXISTS siswa_id,
            DROP COLUMN IF EXISTS nama_alumni,
            DROP COLUMN IF EXISTS nisn,
            DROP COLUMN IF EXISTS fakultas,
            DROP COLUMN IF EXISTS tahun_lulus,
            DROP COLUMN IF EXISTS kampus_id,
            DROP COLUMN IF EXISTS prodi_id,
            DROP COLUMN IF EXISTS jalur_masuk_id,
            DROP COLUMN IF EXISTS jenjang,
            DROP COLUMN IF EXISTS is_manual,
            DROP COLUMN IF EXISTS is_kampus_swasta;

            ALTER TABLE tracer.riwayat_pekerjaan
            DROP COLUMN IF EXISTS siswa_id,
            DROP COLUMN IF EXISTS nama_alumni,
            DROP COLUMN IF EXISTS nisn,
            DROP COLUMN IF EXISTS posisi_jabatan,
            DROP COLUMN IF EXISTS pendapatan_bulanan,
            DROP COLUMN IF EXISTS status_kerja,
            DROP COLUMN IF EXISTS is_manual;
        ");
    }
];
