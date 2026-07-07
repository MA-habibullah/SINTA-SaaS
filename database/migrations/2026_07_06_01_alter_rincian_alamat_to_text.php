<?php
/**
 * Migration 2026_07_06_01_alter_rincian_alamat_to_text
 * 
 * Mengubah tipe data kolom alamat_kk dan alamat_domisili pada tabel rincian_alamat
 * dari VARCHAR(100) menjadi TEXT untuk mencegah error 1406 (Data too long)
 * saat orang tua mendaftarkan alamat yang panjang.
 */

try {
    $pdo = \App\Config\Database::getConnection();
    
    echo "Memulai migrasi: Mengubah tipe kolom alamat pada rincian_alamat...\n";

    // Ubah kolom alamat_kk dan alamat_domisili menjadi TEXT
    $sql = "ALTER TABLE rincian_alamat 
            MODIFY alamat_kk TEXT NOT NULL COMMENT 'Alamat Kartu Keluarga',
            MODIFY alamat_domisili TEXT NULL COMMENT 'Alamat Domisili'";
            
    $pdo->exec($sql);

    echo "Migrasi berhasil: Kolom alamat berhasil diperbesar menjadi TEXT!\n";
    
} catch (PDOException $e) {
    echo "Migrasi gagal: " . $e->getMessage() . "\n";
}
