<?php
/**
 * Migration 016 - Add missing fields to siswa table
 */

return [
    'up' => function(PDO $pdo) {
        // Cek dan tambahkan no_kk
        $stmt = $pdo->query("SHOW COLUMNS FROM siswa LIKE 'no_kk'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE siswa ADD COLUMN no_kk CHAR(16) DEFAULT NULL AFTER nis");
        }

        // Cek dan tambahkan nik
        $stmt = $pdo->query("SHOW COLUMNS FROM siswa LIKE 'nik'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE siswa ADD COLUMN nik CHAR(16) DEFAULT NULL AFTER no_kk");
        }

        // Cek dan tambahkan nama_panggilan
        $stmt = $pdo->query("SHOW COLUMNS FROM siswa LIKE 'nama_panggilan'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE siswa ADD COLUMN nama_panggilan VARCHAR(30) DEFAULT NULL AFTER nama_lengkap");
        }

        // Cek dan tambahkan agama
        $stmt = $pdo->query("SHOW COLUMNS FROM siswa LIKE 'agama'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE siswa ADD COLUMN agama ENUM('Buddha','Hindu','Islam','Katolik','Khonghucu','Kristen') DEFAULT NULL AFTER jenis_kelamin");
        }

        // Cek dan tambahkan ukuran_seragam_sekolah
        $stmt = $pdo->query("SHOW COLUMNS FROM siswa LIKE 'ukuran_seragam_sekolah'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE siswa ADD COLUMN ukuran_seragam_sekolah VARCHAR(3) DEFAULT NULL AFTER agama");
        }

        // Cek dan tambahkan ukuran_seragam_olahraga
        $stmt = $pdo->query("SHOW COLUMNS FROM siswa LIKE 'ukuran_seragam_olahraga'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE siswa ADD COLUMN ukuran_seragam_olahraga VARCHAR(3) DEFAULT NULL AFTER ukuran_seragam_sekolah");
        }

        // Cek dan tambahkan sekolah_asal
        $stmt = $pdo->query("SHOW COLUMNS FROM siswa LIKE 'sekolah_asal'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE siswa ADD COLUMN sekolah_asal VARCHAR(100) DEFAULT NULL AFTER ukuran_seragam_olahraga");
        }

        // Cek dan tambahkan status
        $stmt = $pdo->query("SHOW COLUMNS FROM siswa LIKE 'status'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE siswa ADD COLUMN status ENUM('Aktif','Lulus','Pindah') NOT NULL DEFAULT 'Aktif' AFTER sekolah_asal");
        }
    },

    'down' => function(PDO $pdo) {
        $pdo->exec("ALTER TABLE siswa DROP COLUMN nik, DROP COLUMN no_kk, DROP COLUMN nama_panggilan, DROP COLUMN agama, DROP COLUMN ukuran_seragam_sekolah, DROP COLUMN ukuran_seragam_olahraga, DROP COLUMN sekolah_asal, DROP COLUMN status");
    }
];
