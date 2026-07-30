<?php
/**
 * Migration: Alter Tenants Add Akreditasi Column
 * 
 * Menambahkan kolom akreditasi ke tabel tenants agar sekolah dapat mengisi dan memperbarui
 * status akreditasi mereka sendiri secara dinamis.
 */
return [

    'up' => function (PDO $pdo): void {
        // Cek dulu apakah kolom akreditasi sudah ada
        $stmt = $pdo->query("SHOW COLUMNS FROM `tenants` LIKE 'akreditasi'");
        if ($stmt->fetch()) {
            echo "  OK Kolom akreditasi sudah ada, skip.\n";
            return;
        }

        // Tentukan posisi AFTER secara dinamis
        $afterCol = 'status_sinkronisasi'; // fallback kolom yang pasti ada
        $check = $pdo->query("SHOW COLUMNS FROM `tenants` LIKE 'sertifikat_akreditasi'");
        if ($check->fetch()) {
            $afterCol = 'sertifikat_akreditasi';
        }

        $pdo->exec("
            ALTER TABLE `tenants`
            ADD COLUMN `akreditasi` VARCHAR(50) DEFAULT 'A (Unggul)' AFTER `{$afterCol}`;
        ");
        echo "  OK Kolom akreditasi berhasil ditambahkan pada tabel tenants (AFTER {$afterCol}).\n";
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("
            ALTER TABLE `tenants`
            DROP COLUMN `akreditasi`;
        ");
        echo "  OK Kolom akreditasi berhasil dihapus dari tabel tenants.\n";
    },
];
