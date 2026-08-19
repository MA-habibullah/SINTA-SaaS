<?php
/**
 * Migration: Add parent birthplace and student parent phone number
 * Target Schema: siswa
 * Target Tables: siswa.siswa, siswa.orang_tua
 */

return [
    'up' => function (PDO $pdo): void {
        // 1. Tambah kolom no_telepon_orang_tua pada tabel siswa.siswa
        $pdo->exec("
            ALTER TABLE siswa.siswa 
            ADD COLUMN IF NOT EXISTS no_telepon_orang_tua VARCHAR(20);
        ");

        // 2. Tambah kolom tempat_lahir dan id_tempat_lahir pada tabel siswa.orang_tua
        $pdo->exec("
            ALTER TABLE siswa.orang_tua 
            ADD COLUMN IF NOT EXISTS tempat_lahir VARCHAR(100),
            ADD COLUMN IF NOT EXISTS id_tempat_lahir INTEGER;
        ");

        echo " - Kolom no_telepon_orang_tua berhasil ditambahkan ke siswa.siswa.\n";
        echo " - Kolom tempat_lahir dan id_tempat_lahir berhasil ditambahkan ke siswa.orang_tua.\n";
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("
            ALTER TABLE siswa.siswa 
            DROP COLUMN IF EXISTS no_telepon_orang_tua;
        ");

        $pdo->exec("
            ALTER TABLE siswa.orang_tua 
            DROP COLUMN IF EXISTS tempat_lahir,
            DROP COLUMN IF EXISTS id_tempat_lahir;
        ");
    }
];
