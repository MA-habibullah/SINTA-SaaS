<?php
require_once dirname(__DIR__) . '/app/Config/Database.php';

$db = App\Config\Database::getConnection();

// View tenants and active_sessions table structures
echo "=== TENANTS ===\n";
$r1 = $db->query("SHOW CREATE TABLE tenants");
echo $r1->fetchColumn(1) . "\n\n";

echo "=== ACTIVE_SESSIONS ===\n";
$r2 = $db->query("SHOW CREATE TABLE active_sessions");
echo $r2->fetchColumn(1) . "\n\n";

echo "=== USERS ===\n";
$r3 = $db->query("SHOW CREATE TABLE users");
echo $r3->fetchColumn(1) . "\n\n";
