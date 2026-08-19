<?php
/**
 * Migration: 2026_08_18_05_update_pdss_5step_and_pengunduran_diri.php
 * Standar: PostgreSQL Multi-Schema
 * Deskripsi: Menambahkan tabel pdss.pengunduran_diri dan kolom penunjang alur 5-Step PDSS & pelimpahan kuota siswa eligible.
 */
return [
    'up' => function (PDO $pdo): void {
        // 1. Buat tabel pdss.pengunduran_diri jika belum ada
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS pdss.pengunduran_diri (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NOT NULL,
                siswa_id UUID NOT NULL,
                tahun_ajaran_id UUID,
                nomor_surat VARCHAR(100),
                tanggal_surat DATE DEFAULT CURRENT_DATE,
                alasan TEXT,
                nama_file VARCHAR(255),
                path_file TEXT NOT NULL,
                ukuran_file INT DEFAULT 0,
                mime_type VARCHAR(100) DEFAULT 'application/pdf',
                status_verifikasi VARCHAR(50) DEFAULT 'Disetujui',
                created_by UUID,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );
        ");

        // 2. Indeks untuk performa query pengunduran diri
        $pdo->exec("
            CREATE INDEX IF NOT EXISTS idx_pengunduran_diri_tenant_siswa 
            ON pdss.pengunduran_diri(tenant_id, siswa_id);
            
            CREATE INDEX IF NOT EXISTS idx_pengunduran_diri_tenant_ta 
            ON pdss.pengunduran_diri(tenant_id, tahun_ajaran_id);
        ");

        // 3. Tambahkan kolom status_pengunduran_diri dan relasinya pada pdss.kesiapan_siswa
        $pdo->exec("
            ALTER TABLE pdss.kesiapan_siswa 
            ADD COLUMN IF NOT EXISTS status_pengunduran_diri BOOLEAN DEFAULT FALSE,
            ADD COLUMN IF NOT EXISTS pengunduran_diri_id UUID REFERENCES pdss.pengunduran_diri(id) ON DELETE SET NULL,
            ADD COLUMN IF NOT EXISTS pengganti_siswa_id UUID;
        ");

        // 4. Pastikan tabel pdss.pdss_lock mendukung step 1, 2, 3, 4
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS pdss.pdss_lock (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NOT NULL,
                tahun_ajaran_id UUID NOT NULL,
                step INT NOT NULL,
                is_locked INT DEFAULT 0,
                locked_by UUID,
                locked_at TIMESTAMP WITH TIME ZONE,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                UNIQUE (tenant_id, tahun_ajaran_id, step)
            );
        ");

        echo "- Migrasi tabel pdss.pengunduran_diri, kolom kesiapan_siswa, dan pdss_lock berhasil diterapkan.\n";
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("
            ALTER TABLE pdss.kesiapan_siswa 
            DROP COLUMN IF EXISTS status_pengunduran_diri,
            DROP COLUMN IF EXISTS pengunduran_diri_id,
            DROP COLUMN IF EXISTS pengganti_siswa_id;
        ");
        $pdo->exec("DROP TABLE IF EXISTS pdss.pengunduran_diri CASCADE;");
    },
];
