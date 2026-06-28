<?php
/**
 * Migration: Fix All Collations
 * 
 * Menyeragamkan semua collation tabel dan kolom di database menjadi utf8mb4_general_ci.
 * Ini untuk memperbaiki error "Illegal mix of collations" akibat sisa-sisa
 * migration lama yang terlanjur dibuat di VPS sebelum diperbaiki.
 */
return [
    'up' => function (PDO $pdo): void {
        // Ambil semua nama tabel di database saat ini
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

        $count = 0;
        foreach ($tables as $table) {
            try {
                // Konversi charset dan collation tabel beserta semua kolom di dalamnya
                $pdo->exec("ALTER TABLE `{$table}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;");
                $count++;
            } catch (\Throwable $e) {
                echo "  WARN: Gagal mengkonversi tabel {$table}: " . $e->getMessage() . "\n";
            }
        }

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

        echo "  OK Sebanyak {$count} tabel berhasil diseragamkan ke utf8mb4_general_ci.\n";
    },

    'down' => function (PDO $pdo): void {
        echo "  OK Tidak ada aksi rollback untuk perubahan collation.\n";
    },
];
