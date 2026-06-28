<?php
/**
 * Migration: Add Missing Roles
 * 
 * Memasukkan role Kesiswaan (22) dan memastikan Guru Pembina (21)
 * serta Guru BK (20) ada dengan ID yang tepat agar tidak terjadi
 * error Integrity Constraint Violation saat assign role.
 */
return [
    ''up'' => function (PDO $pdo): void {
        $pdo->exec("
            INSERT IGNORE INTO roles (id, nama_role, deskripsi) VALUES
            (20, ''guru_bk'', ''Guru Bimbingan Konseling - akses modul BK penuh''),
            (21, ''guru_pembina'', ''Extracurricular Coach with access to member management and grading''),
            (22, ''kesiswaan'', ''Staf Kesiswaan / Pengelola Ekstrakurikuler'')
            ON DUPLICATE KEY UPDATE deskripsi=VALUES(deskripsi);
        ");
        echo "  OK Missing roles (20, 21, 22) berhasil ditambahkan.\n";
    },

    ''down'' => function (PDO $pdo): void {
        $pdo->exec("DELETE FROM roles WHERE id IN (20, 21, 22)");
        echo "  OK Missing roles (20, 21, 22) berhasil dihapus.\n";
    }
];
