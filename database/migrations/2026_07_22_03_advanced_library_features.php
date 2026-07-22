<?php
/**
 * Migration: Advanced Library Features (E-Perpus, WA/Email Toggles, Rating/Ulasan, Notifikasi Log)
 */
return [
    'up' => function (PDO $pdo): void {
        // 1. Alter perpus_bibliografi for E-Book support
        try {
            $pdo->query("SELECT file_ebook FROM perpus_bibliografi LIMIT 1");
        } catch (\PDOException $e) {
            $pdo->exec("ALTER TABLE `perpus_bibliografi`
                ADD COLUMN `file_ebook`  VARCHAR(255) DEFAULT NULL AFTER `cover`,
                ADD COLUMN `is_ebook`    TINYINT(1) NOT NULL DEFAULT 0 AFTER `file_ebook`,
                ADD COLUMN `total_views` INT UNSIGNED DEFAULT 0 AFTER `is_ebook`");
        }

        // 2. Alter perpus_pengaturan for WhatsApp & Email Gateway + Toggle Switches
        try {
            $pdo->query("SELECT wa_gateway_url FROM perpus_pengaturan LIMIT 1");
        } catch (\PDOException $e) {
            $pdo->exec("ALTER TABLE `perpus_pengaturan`
                ADD COLUMN `wa_gateway_url`       VARCHAR(255) DEFAULT NULL AFTER `jam_operasional`,
                ADD COLUMN `wa_gateway_api_key`   VARCHAR(255) DEFAULT NULL AFTER `wa_gateway_url`,
                ADD COLUMN `auto_notif_wa_aktif`   TINYINT(1) NOT NULL DEFAULT 1 AFTER `wa_gateway_api_key`,
                ADD COLUMN `auto_notif_email_aktif` TINYINT(1) NOT NULL DEFAULT 0 AFTER `auto_notif_wa_aktif`");
        }

        // 3. perpus_ulasan
        $pdo->exec("CREATE TABLE IF NOT EXISTS `perpus_ulasan` (
            `id`             CHAR(36) NOT NULL DEFAULT (UUID()),
            `tenant_id`      CHAR(36) NOT NULL,
            `bibliografi_id` CHAR(36) NOT NULL,
            `anggota_id`     CHAR(36) NOT NULL,
            `rating`         TINYINT UNSIGNED NOT NULL DEFAULT 5,
            `ulasan`         TEXT NOT NULL,
            `status`         ENUM('Disetujui','Pending','Ditolak') DEFAULT 'Disetujui',
            `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`bibliografi_id`) REFERENCES `perpus_bibliografi`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`anggota_id`) REFERENCES `perpus_anggota`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

        // 4. perpus_notifikasi_log
        $pdo->exec("CREATE TABLE IF NOT EXISTS `perpus_notifikasi_log` (
            `id`           CHAR(36) NOT NULL DEFAULT (UUID()),
            `tenant_id`    CHAR(36) NOT NULL,
            `anggota_id`   CHAR(36) NOT NULL,
            `media`        ENUM('WhatsApp','Email','InApp') DEFAULT 'WhatsApp',
            `tujuan`       VARCHAR(100) NOT NULL,
            `pesan`        TEXT NOT NULL,
            `status`       ENUM('Terkirim','Gagal','Pending') DEFAULT 'Pending',
            `response_api` TEXT DEFAULT NULL,
            `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

        echo "- Migrasi fitur lanjutan e-Perpus & Notifikasi Toggle berhasil diterapkan.\n";
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec("DROP TABLE IF EXISTS `perpus_notifikasi_log`;");
        $pdo->exec("DROP TABLE IF EXISTS `perpus_ulasan`;");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "- Rollback fitur lanjutan e-Perpus selesai.\n";
    }
];
