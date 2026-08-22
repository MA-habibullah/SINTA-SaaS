<?php

/**
 * Migration PostgreSQL: Update akademik.pemetaan_mapel (Guru Pengampu, KKM, Jam) & akademik.detail_nilai_rapor (Deskripsi Capaian, Formatif/Sumatif)
 * Format: return ['up' => closure, 'down' => closure] (WAJIB AGENTS.md)
 */

return [
    'up' => function (PDO $pdo): void {
        echo "=== MENJALANKAN MIGRASI: AKADEMIK PEMETAAN GURU & SKEMA PENILAIAN ===\n";

        // 1. Tambahkan kolom guru_id, kkm, jam_pelajaran pada akademik.pemetaan_mapel
        $pdo->exec("
            ALTER TABLE akademik.pemetaan_mapel 
            ADD COLUMN IF NOT EXISTS guru_id UUID NULL,
            ADD COLUMN IF NOT EXISTS kkm NUMERIC(5,2) NULL DEFAULT 75,
            ADD COLUMN IF NOT EXISTS jam_pelajaran SMALLINT NULL DEFAULT 2;
        ");

        // 2. Tambahkan foreign key & indeks pada pemetaan_mapel jika belum ada
        $pdo->exec("
            CREATE INDEX IF NOT EXISTS idx_pemetaan_mapel_guru ON akademik.pemetaan_mapel(guru_id);
            CREATE INDEX IF NOT EXISTS idx_pemetaan_mapel_kelas_ta ON akademik.pemetaan_mapel(kelas_id, tahun_ajaran, semester);
        ");

        // 3. Tambahkan kolom deskripsi_capaian & nilai_tp/sts/sas pada akademik.detail_nilai_rapor jika belum ada
        $pdo->exec("
            ALTER TABLE akademik.detail_nilai_rapor 
            ADD COLUMN IF NOT EXISTS deskripsi_capaian TEXT NULL,
            ADD COLUMN IF NOT EXISTS deskripsi_keterampilan TEXT NULL,
            ADD COLUMN IF NOT EXISTS nilai_tp NUMERIC(5,2) NULL,
            ADD COLUMN IF NOT EXISTS nilai_sts NUMERIC(5,2) NULL,
            ADD COLUMN IF NOT EXISTS nilai_sas NUMERIC(5,2) NULL,
            ADD COLUMN IF NOT EXISTS bobot_penilaian JSONB NULL;
        ");

        echo "- Kolom guru_id, kkm, jam_pelajaran pada pemetaan_mapel berhasil ditambahkan.\n";
        echo "- Kolom deskripsi_capaian, nilai_tp/sts/sas pada detail_nilai_rapor berhasil ditambahkan.\n";
    },
    'down' => function (PDO $pdo): void {
        $pdo->exec("
            ALTER TABLE akademik.pemetaan_mapel 
            DROP COLUMN IF EXISTS guru_id,
            DROP COLUMN IF EXISTS kkm,
            DROP COLUMN IF EXISTS jam_pelajaran;

            ALTER TABLE akademik.detail_nilai_rapor 
            DROP COLUMN IF EXISTS deskripsi_capaian,
            DROP COLUMN IF EXISTS deskripsi_keterampilan,
            DROP COLUMN IF EXISTS nilai_tp,
            DROP COLUMN IF EXISTS nilai_sts,
            DROP COLUMN IF EXISTS nilai_sas,
            DROP COLUMN IF EXISTS bobot_penilaian;
        ");
        echo "- Kolom pada akademik.pemetaan_mapel & akademik.detail_nilai_rapor berhasil di-rollback.\n";
    }
];
