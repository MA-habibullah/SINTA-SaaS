<?php

return [
    'up' => function (PDO $pdo): void {
        // 1. Update kepegawaian.riwayat_kepala_sekolah
        $pdo->exec("
            ALTER TABLE kepegawaian.riwayat_kepala_sekolah 
            ADD COLUMN IF NOT EXISTS nama_kepsek VARCHAR(255) NULL,
            ADD COLUMN IF NOT EXISTS nip_kepsek VARCHAR(50) NULL,
            ADD COLUMN IF NOT EXISTS tanggal_mulai DATE NULL,
            ADD COLUMN IF NOT EXISTS tanggal_selesai DATE NULL,
            ADD COLUMN IF NOT EXISTS status_plt SMALLINT DEFAULT 0
        ");
        echo "- Kolom kepegawaian.riwayat_kepala_sekolah berhasil diperbarui.\n";

        // 2. Update tracer.arsip_dokumen_alumni
        $pdo->exec("
            ALTER TABLE tracer.arsip_dokumen_alumni 
            ADD COLUMN IF NOT EXISTS siswa_id UUID NULL,
            ADD COLUMN IF NOT EXISTS jenis_dokumen VARCHAR(100) NULL,
            ADD COLUMN IF NOT EXISTS file_path TEXT NULL,
            ADD COLUMN IF NOT EXISTS file_size BIGINT DEFAULT 0,
            ADD COLUMN IF NOT EXISTS keterangan TEXT NULL,
            ADD COLUMN IF NOT EXISTS uploaded_by VARCHAR(100) NULL
        ");
        echo "- Kolom tracer.arsip_dokumen_alumni berhasil diperbarui.\n";
    },
    'down' => function (PDO $pdo): void {
        $pdo->exec("
            ALTER TABLE kepegawaian.riwayat_kepala_sekolah 
            DROP COLUMN IF EXISTS nama_kepsek,
            DROP COLUMN IF EXISTS nip_kepsek,
            DROP COLUMN IF EXISTS tanggal_mulai,
            DROP COLUMN IF EXISTS tanggal_selesai,
            DROP COLUMN IF EXISTS status_plt
        ");
        $pdo->exec("
            ALTER TABLE tracer.arsip_dokumen_alumni 
            DROP COLUMN IF EXISTS siswa_id,
            DROP COLUMN IF EXISTS jenis_dokumen,
            DROP COLUMN IF EXISTS file_path,
            DROP COLUMN IF EXISTS file_size,
            DROP COLUMN IF EXISTS keterangan,
            DROP COLUMN IF EXISTS uploaded_by
        ");
    }
];
