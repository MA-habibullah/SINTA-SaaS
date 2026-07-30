<?php
return [
    'up' => function (PDO $pdo): void {
        // Disable foreign key checks temporarily
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

        // 1. Create table perpus_serial_berkala
        $pdo->exec("CREATE TABLE IF NOT EXISTS `perpus_serial_berkala` (
            `id`                   CHAR(36) NOT NULL DEFAULT (UUID()),
            `tenant_id`            CHAR(36) NOT NULL,
            `nama_media`           VARCHAR(255) NOT NULL,
            `jenis`                ENUM('Surat Kabar', 'Majalah', 'Jurnal', 'Lainnya') DEFAULT 'Surat Kabar',
            `frekuensi`            VARCHAR(100) DEFAULT 'Harian',
            `issn`                 VARCHAR(50) DEFAULT NULL,
            `tanggal_berlangganan` DATE NOT NULL,
            `status_aktif`         TINYINT(1) DEFAULT 1,
            `created_at`           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at`           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

        // 2. Create table perpus_staf_kompetensi
        $pdo->exec("CREATE TABLE IF NOT EXISTS `perpus_staf_kompetensi` (
            `id`               CHAR(36) NOT NULL DEFAULT (UUID()),
            `tenant_id`        CHAR(36) NOT NULL,
            `nama_staf`        VARCHAR(255) NOT NULL,
            `jabatan`          VARCHAR(100) NOT NULL,
            `nama_kegiatan`    VARCHAR(255) NOT NULL,
            `penyelenggara`    VARCHAR(255) NOT NULL,
            `tanggal_kegiatan` DATE NOT NULL,
            `sertifikat_no`    VARCHAR(100) DEFAULT NULL,
            `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        echo "- Tabel perpus_serial_berkala & perpus_staf_kompetensi berhasil dibuat.\n";
    },
    'down' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        $pdo->exec("DROP TABLE IF EXISTS `perpus_serial_berkala`");
        $pdo->exec("DROP TABLE IF EXISTS `perpus_staf_kompetensi`");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        echo "- Rollback tabel perpus_serial_berkala & perpus_staf_kompetensi berhasil.\n";
    },
];
