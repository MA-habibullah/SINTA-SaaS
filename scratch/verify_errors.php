<?php
require_once __DIR__ . '/app/Config/Database.php';

$db = App\Config\Database::getConnection();

// Verify table structure
echo "=== SYSTEM_ERRORS TABLE ===\n";
$r = $db->query('DESCRIBE system_errors');
foreach ($r->fetchAll() as $col) {
    echo $col['Field'] . ' | ' . $col['Type'] . ' | Null: ' . $col['Null'] . "\n";
}

// Verify menu
echo "\n=== MENU ERROR MONITOR ===\n";
$m = $db->query('SELECT id, nama_menu, url FROM menus WHERE id = 22');
$menu = $m->fetch();
if ($menu) {
    echo 'ID: ' . $menu['id'] . ' | ' . $menu['nama_menu'] . ' -> ' . $menu['url'] . "\n";
} else {
    echo "Menu ID 22 NOT FOUND\n";
}

echo "\nDone.\n";
