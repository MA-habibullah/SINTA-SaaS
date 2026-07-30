<?php
/**
 * Migration: Add id_kelas column to siswa table
 * 
 * Kolom ini dibutuhkan oleh banyak query JOIN ke tabel kelas.
 * Sebelumnya tidak ada di definisi awal tabel siswa (migration 004).
 */
return [
    'up' => function (PDO $pdo): void {
        // Cek apakah kolom sudah ada
        $stmt = $pdo->query("SHOW COLUMNS FROM `siswa` LIKE 'id_kelas'");
        if ($stmt->fetch()) {
            echo "  OK Kolom id_kelas sudah ada di tabel siswa, skip.\n";
            return;
        }

        // Tentukan posisi AFTER secara dinamis
        $afterCol = 'nis'; // fallback: kolom yang pasti ada
        $candidates = ['status', 'sekolah_asal', 'nisn'];
        foreach ($candidates as $c) {
            if ($pdo->query("SHOW COLUMNS FROM `siswa` LIKE '$c'")->fetch()) {
                $afterCol = $c;
                break;
            }
        }

        $pdo->exec("
            ALTER TABLE `siswa`
            ADD COLUMN `id_kelas` INT UNSIGNED DEFAULT NULL
                COMMENT 'FK ke tabel kelas' AFTER `{$afterCol}`;
        ");

        echo "  OK Kolom id_kelas berhasil ditambahkan ke tabel siswa (AFTER {$afterCol}).\n";
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("ALTER TABLE `siswa` DROP COLUMN IF EXISTS `id_kelas`;");
        echo "  OK Kolom id_kelas berhasil dihapus dari tabel siswa.\n";
    },
];
