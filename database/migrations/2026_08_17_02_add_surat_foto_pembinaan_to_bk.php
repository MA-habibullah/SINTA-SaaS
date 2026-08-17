<?php

/**
 * Migration PostgreSQL Schema: Add surat_panggilan_pdf and foto_bukti to bk.pembinaan_monitoring
 * Format: return ['up' => closure, 'down' => closure]
 */

return [
    'up' => function (PDO $pdo): void {
        echo "=== ADDING SURAT PANGGILAN & FOTO BUKTI TO bk.pembinaan_monitoring ===\n";
        $pdo->exec("
            ALTER TABLE bk.pembinaan_monitoring ADD COLUMN IF NOT EXISTS surat_panggilan_pdf VARCHAR(512) NULL;
            ALTER TABLE bk.pembinaan_monitoring ADD COLUMN IF NOT EXISTS foto_bukti VARCHAR(512) NULL;
            ALTER TABLE bk.pembinaan_monitoring ADD COLUMN IF NOT EXISTS id_user UUID NULL;
            ALTER TABLE bk.pembinaan_monitoring ADD COLUMN IF NOT EXISTS nama_guru VARCHAR(255) NULL;
            ALTER TABLE bk.catatan_bk ADD COLUMN IF NOT EXISTS surat_panggilan_pdf VARCHAR(512) NULL;
            ALTER TABLE bk.catatan_bk ADD COLUMN IF NOT EXISTS foto_panggilan VARCHAR(512) NULL;
        ");
        echo "- Kolom surat_panggilan_pdf, foto_bukti, id_user & nama_guru berhasil ditambahkan pada bk.pembinaan_monitoring.\n";
    },
    'down' => function (PDO $pdo): void {
        $pdo->exec("
            ALTER TABLE bk.pembinaan_monitoring DROP COLUMN IF EXISTS surat_panggilan_pdf;
            ALTER TABLE bk.pembinaan_monitoring DROP COLUMN IF EXISTS foto_bukti;
            ALTER TABLE bk.pembinaan_monitoring DROP COLUMN IF EXISTS id_user;
            ALTER TABLE bk.pembinaan_monitoring DROP COLUMN IF EXISTS nama_guru;
            ALTER TABLE bk.catatan_bk DROP COLUMN IF EXISTS surat_panggilan_pdf;
            ALTER TABLE bk.catatan_bk DROP COLUMN IF EXISTS foto_panggilan;
        ");
    },
];
