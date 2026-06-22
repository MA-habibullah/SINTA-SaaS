<?php
// Start clean session
session_start();
session_destroy();
session_start();

// Mock request body
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/json';

// Simulated php://input content helper
$requestData = [
    'nisn' => '0081234567', // Ahmad Dani
    'password' => 'siswa123'
];

// We write to a temporary file and mock file_get_contents('php://input')? 
// No, in BaseController, it reads php://input. So we can't easily mock php://input without stream wrapper.
// But we can test by calling Database connection and password_verify directly, or we can just mock AuthSiswaController.
// Actually, let's just inspect the database record of Ahmad Dani and try to see what password is in there.

require_once __DIR__ . '/../app/Config/Database.php';
use App\Config\Database;

try {
    $db = Database::getConnection();
    $nisn = '0081234567';
    $stmt = $db->prepare("SELECT * FROM siswa WHERE nisn = :nisn AND deleted_at IS NULL LIMIT 1");
    $stmt->execute(['nisn' => $nisn]);
    $siswa = $stmt->fetch();
    
    if (!$siswa) {
        echo "Siswa not found!\n";
    } else {
        echo "Siswa found: " . $siswa['nama_lengkap'] . "\n";
        echo "Password hash: " . $siswa['password'] . "\n";
        echo "Is first login: " . $siswa['is_first_login'] . "\n";
        
        // Test password verify
        $passwordsToTest = ['siswa123', '2008-04-12'];
        foreach ($passwordsToTest as $pass) {
            echo "Testing password '$pass': " . (password_verify($pass, $siswa['password']) ? "MATCH" : "NO MATCH") . "\n";
        }
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
