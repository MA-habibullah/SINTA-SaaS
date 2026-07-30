<?php

return [
    'up' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

        // 1. Modifikasi ENUM sumber_buku untuk mendukung Dana BPOPP, Hibah Pemda, Sumbangan Perorangan, Bantuan Lainnya
        $pdo->exec("ALTER TABLE `perpus_eksemplar` MODIFY COLUMN `sumber_buku` ENUM(
            'Dana BOS',
            'Dana BPOPP',
            'Sumbangan Siswa',
            'Sumbangan Alumni',
            'Hibah Pemerintah',
            'Hibah Pemda',
            'Pembelian Mandiri',
            'Sumbangan Perorangan',
            'Bantuan Lainnya',
            'Lainnya'
        ) DEFAULT 'Dana BOS'");

        // 2. Modifikasi ENUM kondisi untuk mendukung Afkir/Dihapuskan
        $pdo->exec("ALTER TABLE `perpus_eksemplar` MODIFY COLUMN `kondisi` ENUM(
            'Baik',
            'Rusak Ringan',
            'Rusak Berat',
            'Afkir/Dihapuskan'
        ) DEFAULT 'Baik'");

        // 3. Modifikasi ENUM status untuk mendukung Dihapuskan/Afkir & Di Gudang
        $pdo->exec("ALTER TABLE `perpus_eksemplar` MODIFY COLUMN `status` ENUM(
            'Tersedia',
            'Dipinjam Reguler',
            'Dipinjam Paket',
            'Dipinjam Event',
            'Dipesan',
            'Rusak',
            'Hilang',
            'Diperbaiki',
            'Di Gudang',
            'Dihapuskan/Afkir'
        ) DEFAULT 'Tersedia'");

        // 4. Tambah kolom sumber_pemberi jika belum ada
        $checkCol = $pdo->query("SHOW COLUMNS FROM `perpus_eksemplar` LIKE 'sumber_pemberi'");
        if ($checkCol->rowCount() === 0) {
            $pdo->exec("ALTER TABLE `perpus_eksemplar` ADD COLUMN `sumber_pemberi` VARCHAR(255) NULL AFTER `sumber_buku`");
            echo "- Kolom sumber_pemberi berhasil ditambahkan ke perpus_eksemplar.\n";
        }

        // 5. Tambah kolom tanggal_penghapusan jika belum ada
        $checkColTgl = $pdo->query("SHOW COLUMNS FROM `perpus_eksemplar` LIKE 'tanggal_penghapusan'");
        if ($checkColTgl->rowCount() === 0) {
            $pdo->exec("ALTER TABLE `perpus_eksemplar` ADD COLUMN `tanggal_penghapusan` DATE NULL AFTER `harga_perolehan`");
            echo "- Kolom tanggal_penghapusan berhasil ditambahkan ke perpus_eksemplar.\n";
        }

        // 6. Tambah kolom alasan_penghapusan jika belum ada
        $checkColAlasan = $pdo->query("SHOW COLUMNS FROM `perpus_eksemplar` LIKE 'alasan_penghapusan'");
        if ($checkColAlasan->rowCount() === 0) {
            $pdo->exec("ALTER TABLE `perpus_eksemplar` ADD COLUMN `alasan_penghapusan` TEXT NULL AFTER `tanggal_penghapusan`");
            echo "- Kolom alasan_penghapusan berhasil ditambahkan ke perpus_eksemplar.\n";
        }

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "- Migrasi audit & perolehan eksemplar perpustakaan berhasil dijalankan.\n";
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

        $checkCol = $pdo->query("SHOW COLUMNS FROM `perpus_eksemplar` LIKE 'sumber_pemberi'");
        if ($checkCol->rowCount() > 0) {
            $pdo->exec("ALTER TABLE `perpus_eksemplar` DROP COLUMN `sumber_pemberi`");
        }

        $checkColTgl = $pdo->query("SHOW COLUMNS FROM `perpus_eksemplar` LIKE 'tanggal_penghapusan'");
        if ($checkColTgl->rowCount() > 0) {
            $pdo->exec("ALTER TABLE `perpus_eksemplar` DROP COLUMN `tanggal_penghapusan`");
        }

        $checkColAlasan = $pdo->query("SHOW COLUMNS FROM `perpus_eksemplar` LIKE 'alasan_penghapusan'");
        if ($checkColAlasan->rowCount() > 0) {
            $pdo->exec("ALTER TABLE `perpus_eksemplar` DROP COLUMN `alasan_penghapusan`");
        }

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "- Rollback migrasi audit eksemplar selesai.\n";
    }
];
