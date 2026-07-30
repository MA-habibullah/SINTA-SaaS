<?php

return [
    'up' => function (PDO $pdo): void {
        echo "Menjalankan migrasi: Tambah kode_prodi dan jenis_portofolio...\n";

        // Add kode_prodi
        try {
            $pdo->exec("ALTER TABLE `master_kampus_prodi` ADD COLUMN `kode_prodi` VARCHAR(50) DEFAULT NULL AFTER `kampus_id`");
            echo "- Kolom kode_prodi berhasil ditambahkan.\n";
        } catch (\PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "- Kolom kode_prodi sudah ada. Dilewati.\n";
            } else {
                throw $e;
            }
        }

        // Add jenis_portofolio
        try {
            $pdo->exec("ALTER TABLE `master_kampus_prodi` ADD COLUMN `jenis_portofolio` VARCHAR(100) DEFAULT 'Tidak Ada' AFTER `jenjang`");
            echo "- Kolom jenis_portofolio berhasil ditambahkan.\n";
        } catch (\PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "- Kolom jenis_portofolio sudah ada. Dilewati.\n";
            } else {
                throw $e;
            }
        }

        // Add indeks untuk kode_prodi supaya pencarian cepat
        try {
            $pdo->exec("ALTER TABLE `master_kampus_prodi` ADD INDEX `idx_mkp_kode` (`kode_prodi`)");
            echo "- Indeks idx_mkp_kode berhasil ditambahkan.\n";
        } catch (\PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "- Indeks idx_mkp_kode sudah ada. Dilewati.\n";
            } else {
                throw $e;
            }
        }

        echo "Migrasi berhasil diselesaikan!\n";
    },

    'down' => function (PDO $pdo): void {
        try {
            $pdo->exec("ALTER TABLE `master_kampus_prodi` DROP INDEX `idx_mkp_kode`");
        } catch (\PDOException $e) {}
        try {
            $pdo->exec("ALTER TABLE `master_kampus_prodi` DROP COLUMN `jenis_portofolio`");
        } catch (\PDOException $e) {}
        try {
            $pdo->exec("ALTER TABLE `master_kampus_prodi` DROP COLUMN `kode_prodi`");
        } catch (\PDOException $e) {}
        echo "Rollback: Kolom kode_prodi & jenis_portofolio dihapus.\n";
    },
];
