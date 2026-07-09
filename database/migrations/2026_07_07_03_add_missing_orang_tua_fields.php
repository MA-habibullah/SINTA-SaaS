<?php
return [
    'up' => function(PDO $pdo) {
        // Add missing fields to orang_tua table
        $sql = "ALTER TABLE orang_tua
            ADD COLUMN tanggal_lahir_ayah DATE DEFAULT NULL AFTER tahun_lahir_ayah,
            ADD COLUMN kewarganegaraan_ayah ENUM('WNI', 'WNA') DEFAULT 'WNI' AFTER agama_ayah,
            ADD COLUMN status_hidup_ayah ENUM('Hidup', 'Meninggal', 'Tidak Diketahui') DEFAULT 'Hidup' AFTER kewarganegaraan_ayah,

            ADD COLUMN tanggal_lahir_ibu DATE DEFAULT NULL AFTER tahun_lahir_ibu,
            ADD COLUMN kewarganegaraan_ibu ENUM('WNI', 'WNA') DEFAULT 'WNI' AFTER agama_ibu,
            ADD COLUMN status_hidup_ibu ENUM('Hidup', 'Meninggal', 'Tidak Diketahui') DEFAULT 'Hidup' AFTER kewarganegaraan_ibu,

            ADD COLUMN tanggal_lahir_wali DATE DEFAULT NULL AFTER tahun_lahir_wali,
            ADD COLUMN kewarganegaraan_wali ENUM('WNI', 'WNA') DEFAULT NULL AFTER agama_wali,
            ADD COLUMN hubungan_wali VARCHAR(50) DEFAULT NULL AFTER kewarganegaraan_wali;
        ";
        
        try {
            $pdo->exec($sql);
        } catch (\PDOException $e) {
            // Ignore if columns already exist
            if (strpos($e->getMessage(), 'Duplicate column name') === false) {
                throw $e;
            }
        }
    },
    'down' => function(PDO $pdo) {
        $sql = "ALTER TABLE orang_tua
            DROP COLUMN tanggal_lahir_ayah,
            DROP COLUMN kewarganegaraan_ayah,
            DROP COLUMN status_hidup_ayah,
            DROP COLUMN tanggal_lahir_ibu,
            DROP COLUMN kewarganegaraan_ibu,
            DROP COLUMN status_hidup_ibu,
            DROP COLUMN tanggal_lahir_wali,
            DROP COLUMN kewarganegaraan_wali,
            DROP COLUMN hubungan_wali;
        ";
        try {
            $pdo->exec($sql);
        } catch (\PDOException $e) {
            // Ignore if columns don't exist
            if (strpos($e->getMessage(), 'check that column/key exists') === false) {
                throw $e;
            }
        }
    }
];
