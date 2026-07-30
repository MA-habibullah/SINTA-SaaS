<?php
/**
 * Migration: Add SYSTEM_TIMEOUT to ENUM values in activity_logs
 * 
 * Mengubah struktur ENUM kolom `action` untuk menambahkan 'SYSTEM_TIMEOUT' 
 * karena migrasi sebelumnya sudah dieksekusi di production.
 */
return [
    'up' => function (PDO $pdo): void {
        $pdo->exec("
            ALTER TABLE `activity_logs` 
            MODIFY COLUMN `action` ENUM('INSERT', 'UPDATE', 'DELETE', 'LOGIN', 'LOGOUT', 'SYSTEM_TIMEOUT') NOT NULL;
        ");
        echo "  OK Kolom action pada tabel activity_logs berhasil ditambahkan 'SYSTEM_TIMEOUT'.\n";
    },

    'down' => function (PDO $pdo): void {
        // PERINGATAN: Downgrade ENUM berisiko kehilangan data jika ada baris yang mengandung 'SYSTEM_TIMEOUT'.
        $pdo->exec("DELETE FROM `activity_logs` WHERE `action` IN ('SYSTEM_TIMEOUT');");
        
        $pdo->exec("
            ALTER TABLE `activity_logs` 
            MODIFY COLUMN `action` ENUM('INSERT', 'UPDATE', 'DELETE', 'LOGIN', 'LOGOUT') NOT NULL;
        ");
        echo "  OK Rollback ENUM action (hapus SYSTEM_TIMEOUT) selesai.\n";
    },
];
