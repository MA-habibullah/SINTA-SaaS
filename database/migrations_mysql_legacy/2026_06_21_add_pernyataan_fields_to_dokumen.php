<?php
/**
 * Migration: Add Pernyataan Fields To Dokumen Table
 * 
 * Menambahkan kolom untuk berkas surat pernyataan baru & orang tua serta surat pernyataan TKA.
 */
return [

    'up' => function (PDO $pdo): void {
        $pdo->exec("
            ALTER TABLE `dokumen`
            ADD COLUMN `berkas_pernyataan_baru` VARCHAR(255) DEFAULT NULL AFTER `berkas_kip`,
            ADD COLUMN `berkas_pernyataan_tka` VARCHAR(255) DEFAULT NULL AFTER `berkas_pernyataan_baru`;
        ");
        echo "  OK Kolom berkas_pernyataan_baru dan berkas_pernyataan_tka berhasil ditambahkan pada tabel dokumen.\n";
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("
            ALTER TABLE `dokumen`
            DROP COLUMN `berkas_pernyataan_baru`,
            DROP COLUMN `berkas_pernyataan_tka`;
        ");
        echo "  OK Kolom berkas_pernyataan_baru dan berkas_pernyataan_tka berhasil dihapus dari tabel dokumen.\n";
    },
];
