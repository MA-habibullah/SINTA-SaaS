<?php
require_once __DIR__ . '/../app/Config/Database.php';
use App\Config\Database;

try {
    $pdo = Database::getConnection();
    echo "USERS:\n";
    $users = $pdo->query("SELECT id, email, nama_lengkap, role_id, tenant_id FROM users")->fetchAll(PDO::FETCH_ASSOC);
    print_r($users);

    echo "\nROLES:\n";
    $roles = $pdo->query("SELECT id, name, display_name FROM roles")->fetchAll(PDO::FETCH_ASSOC);
    print_r($roles);

    echo "\nTENANTS:\n";
    $tenants = $pdo->query("SELECT id, nama_sekolah, subdomain FROM tenants")->fetchAll(PDO::FETCH_ASSOC);
    print_r($tenants);
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
