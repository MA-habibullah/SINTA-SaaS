<?php
/**
 * Migration 014 - Alter Siswa Table Add Password and First Login fields
 */

return [
    'up' => function(PDO $pdo) {
        // Cek apakah kolom password sudah ada
        $stmt = $pdo->query("SHOW COLUMNS FROM siswa LIKE 'password'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE siswa ADD COLUMN password VARCHAR(255) DEFAULT NULL AFTER kontak_wali");
        }

        // Cek apakah kolom is_first_login sudah ada
        $stmt2 = $pdo->query("SHOW COLUMNS FROM siswa LIKE 'is_first_login'");
        if (!$stmt2->fetch()) {
            $pdo->exec("ALTER TABLE siswa ADD COLUMN is_first_login TINYINT(1) NOT NULL DEFAULT 1 AFTER password");
        }

        // Opsional: Untuk data siswa yang sudah ada, set password default dari tanggal_lahir jika terisi
        $stmtSiswa = $pdo->query("SELECT id, tanggal_lahir FROM siswa WHERE password IS NULL AND tanggal_lahir IS NOT NULL");
        $siswas = $stmtSiswa->fetchAll(PDO::FETCH_ASSOC);
        if ($siswas) {
            $stmtUpdate = $pdo->prepare("UPDATE siswa SET password = :password WHERE id = :id");
            foreach ($siswas as $s) {
                $hashedPassword = password_hash($s['tanggal_lahir'], PASSWORD_BCRYPT);
                $stmtUpdate->execute([
                    'password' => $hashedPassword,
                    'id' => $s['id']
                ]);
            }
        }
    },

    'down' => function(PDO $pdo) {
        $pdo->exec("ALTER TABLE siswa DROP COLUMN password, DROP COLUMN is_first_login");
    }
];
