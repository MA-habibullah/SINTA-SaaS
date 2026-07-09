<?php

return [
    'up' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

        // Periksa apakah kolom id_jenjang_asal belum ada, lalu tambahkan
        $stmt = $pdo->query("SHOW COLUMNS FROM `riwayat_kenaikan_kelas` LIKE 'id_jenjang_asal'");
        if ($stmt->rowCount() === 0) {
            $pdo->exec("ALTER TABLE `riwayat_kenaikan_kelas` ADD COLUMN `id_jenjang_asal` INT(11) NULL AFTER `id_kelas_asal`;");
            echo "  OK Kolom id_jenjang_asal ditambahkan ke riwayat_kenaikan_kelas.\n";
        }

        // Periksa apakah kolom id_jenjang_tujuan belum ada, lalu tambahkan
        $stmt = $pdo->query("SHOW COLUMNS FROM `riwayat_kenaikan_kelas` LIKE 'id_jenjang_tujuan'");
        if ($stmt->rowCount() === 0) {
            $pdo->exec("ALTER TABLE `riwayat_kenaikan_kelas` ADD COLUMN `id_jenjang_tujuan` INT(11) NULL AFTER `id_kelas_tujuan`;");
            echo "  OK Kolom id_jenjang_tujuan ditambahkan ke riwayat_kenaikan_kelas.\n";
        }

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        
        $stmt = $pdo->query("SHOW COLUMNS FROM `riwayat_kenaikan_kelas` LIKE 'id_jenjang_asal'");
        if ($stmt->rowCount() > 0) {
            $pdo->exec("ALTER TABLE `riwayat_kenaikan_kelas` DROP COLUMN `id_jenjang_asal`;");
        }

        $stmt = $pdo->query("SHOW COLUMNS FROM `riwayat_kenaikan_kelas` LIKE 'id_jenjang_tujuan'");
        if ($stmt->rowCount() > 0) {
            $pdo->exec("ALTER TABLE `riwayat_kenaikan_kelas` DROP COLUMN `id_jenjang_tujuan`;");
        }

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }
];
