<?php
/**
 * Migration - Add SaaS Subscription and Capacity Limits to Tenants Table
 */

return [
    'up' => function(PDO $pdo) {
        // Cek apakah kolom-kolom ini sudah ada sebelum menambahkan
        $checkCols = [
            'storage_limit_mb' => "ALTER TABLE tenants ADD COLUMN storage_limit_mb INT NOT NULL DEFAULT 100 AFTER paket_aktif",
            'max_siswa_limit'  => "ALTER TABLE tenants ADD COLUMN max_siswa_limit INT NOT NULL DEFAULT 500 AFTER storage_limit_mb",
            'max_staff_limit'  => "ALTER TABLE tenants ADD COLUMN max_staff_limit INT NOT NULL DEFAULT 50 AFTER max_siswa_limit",
            'enable_bk'        => "ALTER TABLE tenants ADD COLUMN enable_bk TINYINT(1) NOT NULL DEFAULT 1 AFTER max_staff_limit",
            'enable_tracer'    => "ALTER TABLE tenants ADD COLUMN enable_tracer TINYINT(1) NOT NULL DEFAULT 1 AFTER enable_bk"
        ];

        // Jalankan query ALTER TABLE untuk masing-masing kolom jika belum ada
        foreach ($checkCols as $col => $sql) {
            try {
                $pdo->query("SELECT {$col} FROM tenants LIMIT 1");
            } catch (\PDOException $e) {
                // Kolom belum ada, jalankan query alter
                $pdo->exec($sql);
            }
        }
    },

    'down' => function(PDO $pdo) {
        $cols = ['enable_tracer', 'enable_bk', 'max_staff_limit', 'max_siswa_limit', 'storage_limit_mb'];
        foreach ($cols as $col) {
            try {
                $pdo->exec("ALTER TABLE tenants DROP COLUMN {$col}");
            } catch (\PDOException $e) {
                // Abaikan jika kolom tidak ada
            }
        }
    }
];
