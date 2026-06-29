<?php

$replacements = [
    // Header & Titles
    'Dapodik & SPMB' => 'SINTA-SaaS',
    'Dapodik &amp; SPMB' => 'SINTA-SaaS',
    'DAPODIK <span class="text-primary">SAAS</span>' => 'SINTA <span class="text-primary">SAAS</span>',
    'DAPODIK PLATFORM' => 'SINTA PLATFORM',
    
    // Domain
    'dapodikspmb.id' => 'sinta-saas.id',
    
    // Index comment
    'aplikasi Dapodik & SPMB' => 'aplikasi SINTA-SaaS',
    
    // Auth descriptions
    'Dapodik & Penerimaan Siswa Baru' => 'SINTA-SaaS & Penerimaan Siswa Baru',
    
    // Form descriptions
    'database Dapodik' => 'database SINTA-SaaS'
];

$files = [
    'views/layout/header.php',
    'views/layout/master.php',
    'views/layout/footer.php',
    'views/layout/sidebar.php',
    'views/login_view.php',
    'views/siswa_login_view.php',
    'views/siswa_change_password_view.php',
    'views/dashboard_view.php',
    'views/identitas_sekolah.php',
    'views/tenants_index.php',
    'views/tambah_siswa.php',
    'app/Controllers/BaseController.php',
    'index.php'
];

foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        $content = file_get_contents($path);
        $newContent = str_replace(array_keys($replacements), array_values($replacements), $content);
        if ($content !== $newContent) {
            file_put_contents($path, $newContent);
            echo "Updated: $file\n";
        }
    } else {
        echo "Not found: $file\n";
    }
}
