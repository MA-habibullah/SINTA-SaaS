<?php
/**
 * Migration 017 - Alter document columns size to VARCHAR(255) to prevent truncation of new longer relative paths.
 */

return [
    'up' => function(PDO $pdo) {
        // Alter foto_profil in rincian_pelajar
        $pdo->exec("ALTER TABLE `rincian_pelajar` MODIFY COLUMN `foto_profil` VARCHAR(255) DEFAULT NULL");
        
        // Alter columns in dokumen
        $pdo->exec("ALTER TABLE `dokumen` 
            MODIFY COLUMN `berkas_kk` VARCHAR(255) DEFAULT NULL,
            MODIFY COLUMN `berkas_akta` VARCHAR(255) DEFAULT NULL,
            MODIFY COLUMN `berkas_ijazah_sd` VARCHAR(255) DEFAULT NULL,
            MODIFY COLUMN `berkas_ijazah_smp` VARCHAR(255) DEFAULT NULL,
            MODIFY COLUMN `berkas_ijazah_sma` VARCHAR(255) DEFAULT NULL,
            MODIFY COLUMN `berkas_mutasi_masuk` VARCHAR(255) DEFAULT NULL,
            MODIFY COLUMN `berkas_mutasi_keluar` VARCHAR(255) DEFAULT NULL,
            MODIFY COLUMN `berkas_kip` VARCHAR(255) DEFAULT NULL
        ");
    },
    'down' => function(PDO $pdo) {
        $pdo->exec("ALTER TABLE `rincian_pelajar` MODIFY COLUMN `foto_profil` VARCHAR(100) DEFAULT NULL");
        
        $pdo->exec("ALTER TABLE `dokumen` 
            MODIFY COLUMN `berkas_kk` VARCHAR(100) DEFAULT NULL,
            MODIFY COLUMN `berkas_akta` VARCHAR(100) DEFAULT NULL,
            MODIFY COLUMN `berkas_ijazah_sd` VARCHAR(100) DEFAULT NULL,
            MODIFY COLUMN `berkas_ijazah_smp` VARCHAR(100) DEFAULT NULL,
            MODIFY COLUMN `berkas_ijazah_sma` VARCHAR(100) DEFAULT NULL,
            MODIFY COLUMN `berkas_mutasi_masuk` VARCHAR(100) DEFAULT NULL,
            MODIFY COLUMN `berkas_mutasi_keluar` VARCHAR(100) DEFAULT NULL,
            MODIFY COLUMN `berkas_kip` VARCHAR(100) DEFAULT NULL
        ");
    }
];
