<?php

return [
    'up' => function (PDO $pdo): void {
        // 1. akademik.pemetaan_mapel
        $pdo->exec("ALTER TABLE akademik.pemetaan_mapel ADD COLUMN IF NOT EXISTS tahun_ajaran VARCHAR(50) NULL");
        $pdo->exec("ALTER TABLE akademik.pemetaan_mapel ADD COLUMN IF NOT EXISTS semester VARCHAR(20) NULL");
        $pdo->exec("ALTER TABLE akademik.pemetaan_mapel ADD COLUMN IF NOT EXISTS kelas_id VARCHAR(100) NULL");
        $pdo->exec("ALTER TABLE akademik.pemetaan_mapel ADD COLUMN IF NOT EXISTS kelompok_id VARCHAR(50) NULL");
        $pdo->exec("ALTER TABLE akademik.pemetaan_mapel ADD COLUMN IF NOT EXISTS mapel_id VARCHAR(100) NULL");

        // 2. akademik.kelas_kurikulum
        $pdo->exec("ALTER TABLE akademik.kelas_kurikulum ADD COLUMN IF NOT EXISTS tahun_ajaran VARCHAR(50) NULL");
        $pdo->exec("ALTER TABLE akademik.kelas_kurikulum ADD COLUMN IF NOT EXISTS kelas_id VARCHAR(100) NULL");
        $pdo->exec("ALTER TABLE akademik.kelas_kurikulum ADD COLUMN IF NOT EXISTS kurikulum_id VARCHAR(100) NULL");

        // 3. akademik.detail_nilai_rapor
        $pdo->exec("ALTER TABLE akademik.detail_nilai_rapor ADD COLUMN IF NOT EXISTS siswa_id VARCHAR(100) NULL");
        $pdo->exec("ALTER TABLE akademik.detail_nilai_rapor ADD COLUMN IF NOT EXISTS kelas_id VARCHAR(100) NULL");
        $pdo->exec("ALTER TABLE akademik.detail_nilai_rapor ADD COLUMN IF NOT EXISTS mapel_id VARCHAR(100) NULL");
        $pdo->exec("ALTER TABLE akademik.detail_nilai_rapor ADD COLUMN IF NOT EXISTS tahun_ajaran VARCHAR(50) NULL");
        $pdo->exec("ALTER TABLE akademik.detail_nilai_rapor ADD COLUMN IF NOT EXISTS semester VARCHAR(20) NULL");
        $pdo->exec("ALTER TABLE akademik.detail_nilai_rapor ADD COLUMN IF NOT EXISTS nilai_akhir NUMERIC(5,2) NULL");
        $pdo->exec("ALTER TABLE akademik.detail_nilai_rapor ADD COLUMN IF NOT EXISTS capaian_kompetensi TEXT NULL");

        // 4. akademik.kunci_akademik
        $pdo->exec("ALTER TABLE akademik.kunci_akademik ADD COLUMN IF NOT EXISTS tahun_ajaran VARCHAR(50) NULL");
        $pdo->exec("ALTER TABLE akademik.kunci_akademik ADD COLUMN IF NOT EXISTS semester VARCHAR(20) NULL");
        $pdo->exec("ALTER TABLE akademik.kunci_akademik ADD COLUMN IF NOT EXISTS is_locked_kurikulum BOOLEAN DEFAULT false");
        $pdo->exec("ALTER TABLE akademik.kunci_akademik ADD COLUMN IF NOT EXISTS is_locked_nilai BOOLEAN DEFAULT false");

        echo "- Migrasi kolom akademik.pemetaan_mapel & detail_nilai_rapor berhasil.\n";
    },
    'down' => function (PDO $pdo): void {
        // Rollback if needed
    }
];
