<?php
// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = dirname(__DIR__) . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

try {
    $db = App\Config\Database::getConnection();
    $errors = $db->query("SELECT * FROM system_errors ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    echo "LATEST SYSTEM ERRORS:\n";
    print_r($errors);
} catch (Exception $e) {
    echo "DB Error: " . $e->getMessage() . "\n";
}
