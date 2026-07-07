<?php
/**
 * Migration 2026_07_06_01_alter_rincian_alamat_to_text
 * 
 * Mengubah tipe data kolom alamat_kk dan alamat_domisili pada tabel rincian_alamat
 * dari VARCHAR(100) menjadi TEXT untuk mencegah error 1406 (Data too long)
 * saat orang tua mendaftarkan alamat yang panjang.
 */

return [
    'up' => function (PDO $pdo): void {
        echo "Memulai migrasi: Mengubah tipe kolom alamat pada rincian_alamat...\n";

        // Ubah kolom alamat_kk dan alamat_domisili menjadi TEXT
        $sql = "ALTER TABLE rincian_alamat 
                MODIFY alamat_kk TEXT NOT NULL COMMENT 'Alamat Kartu Keluarga',
                MODIFY alamat_domisili TEXT NULL COMMENT 'Alamat Domisili'";
                
        $pdo->exec($sql);

        echo "Migrasi berhasil: Kolom alamat berhasil diperbesar menjadi TEXT!\n";
    },

    'down' => function (PDO $pdo): void {
        echo "Memulai rollback: Mengembalikan tipe kolom alamat menjadi VARCHAR(100)...\n";
        
        $sql = "ALTER TABLE rincian_alamat 
                MODIFY alamat_kk VARCHAR(100) NOT NULL COMMENT 'Alamat Kartu Keluarga',
                MODIFY alamat_domisili VARCHAR(100) NULL COMMENT 'Alamat Domisili'";
                
        $pdo->exec($sql);
        
        echo "Rollback berhasil: Kolom alamat dikembalikan ke VARCHAR(100)!\n";
    },
];
