<?php
/**
 * Migration: Add Waka Roles
 */
return [
    'up' => function (PDO $pdo): void {
        $pdo->exec("
            INSERT IGNORE INTO roles (id, nama_role, deskripsi) VALUES
            (23, 'humas', 'Waka Hubungan Masyarakat'),
            (24, 'kurikulum', 'Waka Kurikulum'),
            (25, 'sarpras', 'Waka Sarana dan Prasarana')
        ");
        echo "  OK Waka roles (humas, kurikulum, sarpras) berhasil ditambahkan.\n";
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("DELETE FROM roles WHERE id IN (23, 24, 25)");
        echo "  OK Waka roles berhasil dihapus.\n";
    }
];
