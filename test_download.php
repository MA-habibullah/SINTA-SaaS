<?php
$_SERVER['REQUEST_URI'] = '/SINTA-SaaS/download.php?file=uploads/11111111-1111-1111-1111-111111111111/de5e7f15-9e0c-4823-9fab-401e055dd9a7/61675974a9de5f8247a09c1d5011d7e7c54b31cd.pdf';
require 'app/Core/SessionManager.php';
\App\Core\SessionManager::start();
$_SESSION['logged_in'] = true;
$_SESSION['role_name'] = 'super_admin';
$_GET['file'] = 'uploads/11111111-1111-1111-1111-111111111111/de5e7f15-9e0c-4823-9fab-401e055dd9a7/61675974a9de5f8247a09c1d5011d7e7c54b31cd.pdf';

ob_start();
require 'download.php';
$out = ob_get_clean();
echo "Length: " . strlen($out) . "\n";
file_put_contents('test_download_out.txt', $out);
