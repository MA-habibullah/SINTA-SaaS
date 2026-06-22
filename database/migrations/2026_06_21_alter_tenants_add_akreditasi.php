<?php
/**
 * Migration: Alter Tenants Add Akreditasi Column
 * 
 * Menambahkan kolom akreditasi ke tabel tenants agar sekolah dapat mengisi dan memperbarui
 * status akreditasi mereka sendiri secara dinamis.
 */
return [

    'up' => function (PDO $pdo): void {
        $pdo->exec("
            ALTER TABLE `tenants`
            ADD COLUMN `akreditasi` VARCHAR(50) DEFAULT 'A (Unggul)' AFTER `sertifikat_akreditasi`;
        ");
        echo "  OK Kolom akreditasi berhasil ditambahkan pada tabel tenants.\n";
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("
            ALTER TABLE `tenants`
            DROP COLUMN `akreditasi`;
        ");
        echo "  OK Kolom akreditasi berhasil dihapus dari tabel tenants.\n";
    },
];
