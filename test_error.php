<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
$_SESSION['role_name'] = 'super_admin';

require 'app/Core/SessionManager.php';
require 'app/Config/Database.php';
require 'app/Controllers/BaseController.php';
require 'app/Controllers/ErrorMonitorController.php';

ob_start();
$c = new \App\Controllers\ErrorMonitorController();
try {
    $c->fetchApi();
} catch (Exception $e) {
    echo $e->getMessage();
}
$out = ob_get_clean();
file_put_contents('test_error_output.txt', "OUTPUT IS: " . ($out === '' ? 'EMPTY STRING' : $out));
