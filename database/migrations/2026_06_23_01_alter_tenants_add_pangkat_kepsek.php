<?php
/**
 * Migration: Alter Tenants Add Pangkat Kepsek Column
 * 
 * Menambahkan kolom pangkat_kepsek ke tabel tenants agar pangkat kepala sekolah
 * dapat diatur dan ditampilkan pada halaman cetak rapot.
 */
return [

    'up' => function (PDO $pdo): void {
        $pdo->exec("
            ALTER TABLE `tenants`
            ADD COLUMN `pangkat_kepsek` VARCHAR(100) DEFAULT 'Pembina' AFTER `nip_kepsek`;
        ");
        echo "  OK Kolom pangkat_kepsek berhasil ditambahkan pada tabel tenants.\n";
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("
            ALTER TABLE `tenants`
            DROP COLUMN `pangkat_kepsek`;
        ");
        echo "  OK Kolom pangkat_kepsek berhasil dihapus dari tabel tenants.\n";
    },
];
