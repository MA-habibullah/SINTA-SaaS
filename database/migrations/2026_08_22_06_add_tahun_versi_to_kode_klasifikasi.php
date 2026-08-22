<?php

/**
 * Migration PostgreSQL: Tambah Kolom Tahun Berlaku & Versi Regulasi pada Tabel Kode Klasifikasi Surat
 * Format Standar SINTA: return ['up' => closure, 'down' => closure]
 */

return [
    'up' => function (PDO $pdo): void {
        echo "=== Menambahkan Kolom Tahun Berlaku & Versi Regulasi pada persuratan.kode_klasifikasi_surat ===\n";

        $pdo->exec("
            ALTER TABLE persuratan.kode_klasifikasi_surat 
            ADD COLUMN IF NOT EXISTS tahun_berlaku_mulai INT DEFAULT 2025;

            ALTER TABLE persuratan.kode_klasifikasi_surat 
            ADD COLUMN IF NOT EXISTS tahun_berlaku_selesai INT DEFAULT NULL;

            ALTER TABLE persuratan.kode_klasifikasi_surat 
            ADD COLUMN IF NOT EXISTS versi_regulasi VARCHAR(100) DEFAULT 'Permendagri/Disdik 2025';

            -- Update semua rekaman klasifikasi yang ada agar memiliki tahun berlaku 2025
            UPDATE persuratan.kode_klasifikasi_surat 
            SET tahun_berlaku_mulai = 2025,
                versi_regulasi = COALESCE(versi_regulasi, 'Permendagri/Disdik 2025')
            WHERE tahun_berlaku_mulai IS NULL;
        ");

        echo "- Kolom tahun_berlaku_mulai, tahun_berlaku_selesai, dan versi_regulasi berhasil ditambahkan.\n";
    },
    'down' => function (PDO $pdo): void {
        $pdo->exec("
            ALTER TABLE persuratan.kode_klasifikasi_surat DROP COLUMN IF EXISTS tahun_berlaku_mulai;
            ALTER TABLE persuratan.kode_klasifikasi_surat DROP COLUMN IF EXISTS tahun_berlaku_selesai;
            ALTER TABLE persuratan.kode_klasifikasi_surat DROP COLUMN IF EXISTS versi_regulasi;
        ");
    }
];
