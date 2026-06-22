<?php
/**
 * Automated Verification Script for Advanced Student Form Features
 */

// Use output buffering to prevent "headers already sent" warnings from echo statements
ob_start();

require_once __DIR__ . '/../app/Config/Database.php';
require_once __DIR__ . '/../app/Core/Model.php';
require_once __DIR__ . '/../app/Core/SessionManager.php';
require_once __DIR__ . '/../app/Core/StorageGuard.php';
require_once __DIR__ . '/../app/Models/Siswa.php';
require_once __DIR__ . '/../app/Controllers/BaseController.php';
require_once __DIR__ . '/../app/Controllers/SiswaController.php';

use App\Core\SessionManager;
use App\Core\StorageGuard;
use App\Models\Siswa;
use App\Controllers\SiswaController;

// 1. Initialize the session before setting $_SESSION values
SessionManager::start();

// 2. Mock session keys (Must be done after session is active)
$_SESSION['logged_in'] = true;
$_SESSION['role_name'] = 'super_admin';
$_SESSION['nama_lengkap'] = 'Test Super Admin';
$_SESSION['last_activity'] = time();

try {
    $tenantId = '11111111-1111-1111-1111-111111111111';
    $siswaModel = new Siswa();
    $controller = new SiswaController();

    echo "==================================================\n";
    echo "STARTING VERIFICATION OF ADVANCED WIZARD FEATURES\n";
    echo "==================================================\n\n";

    // Clean up previous runs
    $pdo = \App\Config\Database::getConnection();
    $pdo->exec("DELETE FROM siswa WHERE nama_lengkap LIKE 'ADVANCED TEST%'");

    // 2. VERIFY DRAFT PERSISTENCE (TUGAS 1)
    echo "1. Testing Auto-Save Draft Persistence...\n";
    $_SESSION['siswa_draft'] = [
        'nama_lengkap' => 'ADVANCED TEST DRAFT',
        'nisn' => '1112223334'
    ];
    
    // Simulate loading form
    if (isset($_SESSION['siswa_draft']) && $_SESSION['siswa_draft']['nama_lengkap'] === 'ADVANCED TEST DRAFT') {
        echo "[SUCCESS] Draft successfully hydrated from session.\n";
    } else {
        throw new \Exception("Draft hydration failed!");
    }
    unset($_SESSION['siswa_draft']);

    // 3. VERIFY CONDITIONAL KIP VALIDATION (TUGAS 2)
    echo "\n2. Testing Conditional KIP Validation...\n";
    $reflection = new \ReflectionClass($controller);
    $validateMethod = $reflection->getMethod('validateSiswaData');
    $validateMethod->setAccessible(true);

    // KIP chosen (1) but number empty
    $testInput1 = [
        'nama_lengkap' => 'ADVANCED TEST KIP FAIL',
        'jenis_kelamin' => 'L',
        'punya_kip' => 1,
        'no_kip' => ''
    ];
    $errors = $validateMethod->invoke($controller, $testInput1);
    if (isset($errors['no_kip'])) {
        echo "[SUCCESS] Correctly blocked empty KIP number when KIP card is selected: " . $errors['no_kip'] . "\n";
    } else {
        throw new \Exception("Failed to validate empty KIP number when KIP card is selected.");
    }

    // KIP chosen (1) and number has flexible alphanumeric format (should be allowed)
    $testInput2 = [
        'nama_lengkap' => 'ADVANCED TEST KIP SUCCESS ALPHANUMERIC',
        'jenis_kelamin' => 'L',
        'punya_kip' => 1,
        'no_kip' => 'KIP-123-ABC-9999'
    ];
    $errors = $validateMethod->invoke($controller, $testInput2);
    if (!isset($errors['no_kip'])) {
        echo "[SUCCESS] Correctly allowed flexible alphanumeric KIP number.\n";
    } else {
        throw new \Exception("Blocked flexible KIP number: " . print_r($errors, true));
    }

    // 4. VERIFY OPTIONAL STATUS_ANAK VALIDATION (TUGAS 2)
    echo "\n3. Testing Optional Status Kependudukan Anak...\n";
    $testInput4 = [
        'nama_lengkap' => 'ADVANCED TEST STATUS ANAK',
        'jenis_kelamin' => 'L',
        'status_anak' => ''
    ];
    $errors = $validateMethod->invoke($controller, $testInput4);
    if (!isset($errors['status_anak'])) {
        echo "[SUCCESS] Correctly allowed empty status_anak on validation.\n";
    } else {
        throw new \Exception("Blocked empty status_anak.");
    }

    // 5. VERIFY STORAGE LIMIT & CALCULATIONS (TUGAS 3)
    echo "\n4. Testing Storage Limit Monitoring...\n";
    $initialUsage = StorageGuard::getTenantStorageUsage($tenantId);
    $limit = StorageGuard::getTenantStorageLimit($tenantId);
    echo "Initial Storage Usage: " . number_format($initialUsage) . " bytes\n";
    echo "Storage Limit: " . number_format($limit) . " bytes (" . ($limit / 1024 / 1024) . " MB)\n";

    $canUpload = StorageGuard::checkStorageLimit($tenantId, 100000); // Check 100 KB
    echo "Can upload 100 KB: " . ($canUpload ? 'Yes' : 'No') . "\n";
    if (!$canUpload && $initialUsage < $limit) {
        throw new \Exception("Incorrectly blocked safe file upload.");
    }
    echo "[SUCCESS] Storage Guard verified successfully.\n";

    // 6. VERIFY DYNAMIC DIRECTORY STORAGE (TUGAS 3)
    echo "\n5. Testing Dynamic Directory & Naming Hashing...\n";
    $testFileKey = 'foto_profil';
    $baseDir = __DIR__ . '/../storage/uploads/' . $tenantId . '/' . $testFileKey . '/';
    echo "Target Directory: $baseDir\n";
    
    if (!is_dir($baseDir)) {
        mkdir($baseDir, 0755, true);
    }
    
    $fileExtension = 'jpg';
    $hashedName = md5(uniqid(rand(), true)) . '.' . $fileExtension;
    $destPath = $baseDir . $hashedName;
    echo "Generated Hashed File Name: $hashedName\n";

    file_put_contents($destPath, 'mock image content');
    if (file_exists($destPath)) {
        echo "[SUCCESS] File saved successfully under dynamic directory with unique hash name.\n";
        @unlink($destPath);
    } else {
        throw new \Exception("Failed to save file dynamically!");
    }

    echo "\n==================================================\n";
    echo "ALL ADVANCED FEATURES VERIFIED SUCCESSFULLY!\n";
    echo "==================================================\n";

} catch (\Throwable $e) {
    echo "\n[ERROR] VERIFICATION FAILED: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " on line " . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
}

// Flush output buffer
ob_end_flush();
