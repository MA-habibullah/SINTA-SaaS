<?php
require_once __DIR__ . '/../../app/Config/Database.php';

use App\Config\Database;

try {
    $pdo = Database::getConnection();
    echo "Menjalankan migrasi: Alter PDSS Tables for Multiyear...\n";

    // 1. Ambil ID Tahun Ajaran aktif sebagai fallback
    $qTa = $pdo->query("SELECT id FROM tahun_ajaran ORDER BY is_active DESC, id DESC LIMIT 1");
    $activeTaId = $qTa->fetchColumn();
    if (!$activeTaId) {
        throw new Exception("Tidak ada data tahun_ajaran di database!");
    }

    // 2. ALTER TABLE pdss_config_mapel
    // Cek apakah kolom tahun_ajaran_id sudah ada
    $checkColConfig = $pdo->query("SHOW COLUMNS FROM `pdss_config_mapel` LIKE 'tahun_ajaran_id'");
    if (!$checkColConfig->fetch()) {
        echo "- Menambahkan kolom tahun_ajaran_id ke pdss_config_mapel...\n";
        $pdo->exec("ALTER TABLE `pdss_config_mapel` ADD COLUMN `tahun_ajaran_id` INT UNSIGNED DEFAULT NULL AFTER `tenant_id`");
        
        // Update records lama ke tahun ajaran aktif
        $pdo->exec("UPDATE `pdss_config_mapel` SET `tahun_ajaran_id` = $activeTaId WHERE `tahun_ajaran_id` IS NULL");
        
        // Buat foreign key
        try {
            $pdo->exec("ALTER TABLE `pdss_config_mapel` ADD CONSTRAINT `fk_pcm_tahun_ajaran` FOREIGN KEY (`tahun_ajaran_id`) REFERENCES `tahun_ajaran` (`id`) ON DELETE SET NULL");
        } catch (\PDOException $e) {}
    }

    // Update Unique Key di pdss_config_mapel
    $checkUk = $pdo->query("SHOW INDEX FROM `pdss_config_mapel` WHERE Key_name = 'uk_tenant_mapel'");
    if ($checkUk->fetch()) {
        echo "- Mengubah unique key constraint di pdss_config_mapel...\n";
        try {
            $pdo->exec("ALTER TABLE `pdss_config_mapel` DROP FOREIGN KEY `fk_pdss_mapel_tenant`");
        } catch (\PDOException $e) {}
        try {
            $pdo->exec("ALTER TABLE `pdss_config_mapel` DROP INDEX `uk_tenant_mapel`");
        } catch (\PDOException $e) {}
        
        $pdo->exec("ALTER TABLE `pdss_config_mapel` ADD UNIQUE KEY `uk_tenant_ta_mapel` (`tenant_id`, `tahun_ajaran_id`, `mapel_id`)");
        
        try {
            $pdo->exec("ALTER TABLE `pdss_config_mapel` ADD CONSTRAINT `fk_pdss_mapel_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE");
        } catch (\PDOException $e) {}
    }

    // 3. ALTER TABLE pdss_lock
    $checkColLock = $pdo->query("SHOW COLUMNS FROM `pdss_lock` LIKE 'tahun_ajaran_id'");
    if (!$checkColLock->fetch()) {
        echo "- Mengubah struktur tabel pdss_lock untuk multiyear...\n";
        
        try {
            $pdo->exec("ALTER TABLE `pdss_lock` DROP FOREIGN KEY `fk_pdss_lock_tenant`");
        } catch (\PDOException $e) {}
        try {
            $pdo->exec("ALTER TABLE `pdss_lock` DROP PRIMARY KEY");
        } catch (\PDOException $e) {}

        // Pastikan kolom step ada
        $checkColStep = $pdo->query("SHOW COLUMNS FROM `pdss_lock` LIKE 'step'");
        if (!$checkColStep->fetch()) {
            $pdo->exec("ALTER TABLE `pdss_lock` ADD COLUMN `step` TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER `tenant_id`");
        }

        // Tambah kolom tahun_ajaran_id
        $pdo->exec("ALTER TABLE `pdss_lock` ADD COLUMN `tahun_ajaran_id` INT UNSIGNED NOT NULL AFTER `step`");
        
        // Update data lama
        $pdo->exec("UPDATE `pdss_lock` SET `tahun_ajaran_id` = $activeTaId");

        // Set primary key komposit
        $pdo->exec("ALTER TABLE `pdss_lock` ADD PRIMARY KEY (`tenant_id`, `step`, `tahun_ajaran_id`)");

        // Hubungkan kembali foreign keys
        try {
            $pdo->exec("ALTER TABLE `pdss_lock` ADD CONSTRAINT `fk_pdss_lock_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE");
        } catch (\PDOException $e) {}
        try {
            $pdo->exec("ALTER TABLE `pdss_lock` ADD CONSTRAINT `fk_pdss_lock_ta` FOREIGN KEY (`tahun_ajaran_id`) REFERENCES `tahun_ajaran` (`id`) ON DELETE CASCADE");
        } catch (\PDOException $e) {}
    }

    echo "Migrasi PDSS multiyear berhasil diselesaikan!\n";
} catch (\Exception $e) {
    echo "Terjadi kesalahan saat migrasi: " . $e->getMessage() . "\n";
    exit(1);
}
