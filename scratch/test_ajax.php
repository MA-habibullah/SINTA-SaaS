<?php
$_GET['ajax'] = '1';
$_GET['action'] = 'get_all_kota';
$_SESSION['tenant_id'] = '11111111-1111-1111-1111-111111111111'; // mock session

// Include config
require_once __DIR__ . '/../app/Config/Database.php';

try {
    $pdo = App\Config\Database::getConnection();
    $stmt = $pdo->query("SELECT id_kota, nama_kota FROM kota ORDER BY nama_kota ASC");
    $result = $stmt->fetchAll();
    echo "Cities found: " . count($result) . "\n";
    if (count($result) > 0) {
        echo "First city: " . json_encode($result[0]) . "\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
