<?php
/**
 * Migration: Add 'tinggal_kelas' to enum riwayat_kenaikan_kelas
 */
return [
    'up' => function (PDO $pdo): void {
        try {
            $sql = "ALTER TABLE riwayat_kenaikan_kelas MODIFY COLUMN jenis_aksi enum('naik_kelas','lulus','tinggal_kelas','penempatan_awal') NOT NULL;";
            $pdo->exec($sql);
            echo "  OK Enum 'tinggal_kelas' berhasil ditambahkan.\n";
        } catch (PDOException $e) {
            echo "  Gagal: " . $e->getMessage() . "\n";
        }
    },
    'down' => function (PDO $pdo): void {
        try {
            $sql = "ALTER TABLE riwayat_kenaikan_kelas MODIFY COLUMN jenis_aksi enum('naik_kelas','lulus','penempatan_awal') NOT NULL;";
            $pdo->exec($sql);
            echo "  OK Enum 'tinggal_kelas' berhasil dihapus.\n";
        } catch (PDOException $e) {
            echo "  Gagal: " . $e->getMessage() . "\n";
        }
    }
];
