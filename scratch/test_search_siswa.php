<?php
require_once __DIR__ . '/../app/Config/Database.php';
require_once __DIR__ . '/../app/Core/SessionManager.php';
require_once __DIR__ . '/../app/Controllers/BaseController.php';
require_once __DIR__ . '/../app/Controllers/BKController.php';

use App\Core\SessionManager;
use App\Controllers\BKController;

SessionManager::start();

// Mock session - Maulana (Operator) at tenant a447e90e-9edd-4e64-8eb7-20f4f3e89c49
$_SESSION['logged_in'] = true;
$_SESSION['role_name'] = 'operator_sekolah';
$_SESSION['user_id'] = '3e78ca99-b4c5-4d1b-9601-364985786ebe';
$_SESSION['tenant_id'] = 'a447e90e-9edd-4e64-8eb7-20f4f3e89c49';
$_SESSION['nama_lengkap'] = 'maulana';
$_SESSION['last_activity'] = time();

$_SERVER['REQUEST_URI'] = '/dapodik-spmb/api/v1/bk/siswa';

// Set GET parameters
$_GET['q'] = 'coba';

echo "Testing Search Siswa API for 'coba':\n";
try {
    $controller = new BKController();
    $controller->apiSiswaSearch();
} catch (\Exception $e) {
    echo "Caught Exception: " . $e->getMessage() . "\n";
}

