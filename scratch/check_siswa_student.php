<?php
require_once __DIR__ . '/../app/Config/Database.php';
use App\Config\Database;

try {
    $pdo = Database::getConnection();
    $id = '145875fc-5804-44fa-9f2b-8c29a51f1f2d';
    
    $siswa = $pdo->query("SELECT id, tenant_id, nama_lengkap FROM siswa WHERE id = '$id'")->fetch(PDO::FETCH_ASSOC);
    $rincian = $pdo->query("SELECT id_siswa, foto_profil FROM rincian_pelajar WHERE id_siswa = '$id'")->fetch(PDO::FETCH_ASSOC);
    $dokumen = $pdo->query("SELECT * FROM dokumen WHERE id_siswa = '$id'")->fetch(PDO::FETCH_ASSOC);
    
    echo "=== SISWA DATA ===\n";
    print_r($siswa);
    
    echo "\n=== RINCIAN PELAJAR ===\n";
    print_r($rincian);
    
    echo "\n=== DOKUMEN ===\n";
    print_r($dokumen);
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
