<?php
/**
 * Migration: Alter ENUM values in activity_logs for Audit Trail
 * 
 * 1. Mengubah struktur ENUM kolom `action` untuk menambahkan 'LOGIN' dan 'LOGOUT'.
 */
return [
    'up' => function (PDO $pdo): void {
        $pdo->exec("
            ALTER TABLE `activity_logs` 
            MODIFY COLUMN `action` ENUM('INSERT', 'UPDATE', 'DELETE', 'LOGIN', 'LOGOUT') NOT NULL;
        ");
        echo "  OK Kolom action pada tabel activity_logs berhasil diperbarui.\n";
    },

    'down' => function (PDO $pdo): void {
        // PERINGATAN: Downgrade ENUM berisiko kehilangan data jika ada baris yang mengandung 'LOGIN'/'LOGOUT'.
        // Idealnya, kita harus membersihkan data 'LOGIN'/'LOGOUT' terlebih dahulu jika mau rollback.
        $pdo->exec("DELETE FROM `activity_logs` WHERE `action` IN ('LOGIN', 'LOGOUT');");
        
        $pdo->exec("
            ALTER TABLE `activity_logs` 
            MODIFY COLUMN `action` ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL;
        ");
        echo "  OK Rollback ENUM action tabel activity_logs selesai.\n";
    },
];
