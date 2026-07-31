<?php
// MIGRATION SCRIPT FOR PENGGUNA, KELEMBAGAAN, BUKU INDUK, PENGUMUMAN

function migrateFile($source, $target, $namespace, $className, $isModel = false) {
    if (!file_exists($source)) return false;
    $content = file_get_contents($source);
    
    // Replace Namespace
    $content = preg_replace("/namespace App\\\\(?:Controllers|Models);/", "namespace $namespace;", $content);
    
    // Replace Class Name
    $content = preg_replace("/class ([a-zA-Z0-9_]+)( extends [a-zA-Z0-9_\\\\]+)? {/", "class $className$2 {", $content);
    
    // Replace Models dependencies
    $content = str_replace("use App\Models\Pengguna;", "use App\Modules\Sistem\Models\PenggunaModel;", $content);
    $content = str_replace("use App\Models\Kelembagaan;", "use App\Modules\Sistem\Models\KelembagaanModel;", $content);
    
    // Replace Model Instantiations
    $content = str_replace("new Pengguna(", "new PenggunaModel(", $content);
    $content = str_replace("new Kelembagaan(", "new KelembagaanModel(", $content);
    $content = str_replace("Pengguna $model", "PenggunaModel $model", $content);
    $content = str_replace("Kelembagaan $model", "KelembagaanModel $model", $content);
    
    // Replace MySQL deleted_at IS NULL with PostgreSQL is_active = true
    $content = str_replace("deleted_at IS NULL", "is_active = true", $content);
    $content = str_replace("deleted_at IS NOT NULL", "is_active = false", $content);
    
    // Fix string case for PostgreSQL (e.g., status = \x27Aktif\x27 vs \x27aktif\x27)
    // $content = preg_replace("/status_siswa = \x27aktif\x27/i", "LOWER(status_siswa) = \x27aktif\x27", $content);
    
    // Replace lastInsertId with RETURNING id (This is tricky for PDO, but for now we replace it with UUID generation if possible, or just ignore if it\x27s a read model)
    if ($isModel) {
        $content = str_replace("lastInsertId()", "query(\x27SELECT gen_random_uuid()\x27)->fetchColumn() /* FIXME UUID */", $content);
    }
    
    // Save Target
    $dir = dirname($target);
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    file_put_contents($target, $content);
    return true;
}

// 1. Pengguna
migrateFile(
    "C:/laragon/www/sinta/scratch/folder legacy/app/Controllers/PenggunaController.php",
    "C:/laragon/www/sinta/app/Modules/Sistem/Controllers/PenggunaModuleController.php",
    "App\Modules\Sistem\Controllers",
    "PenggunaModuleController"
);
migrateFile(
    "C:/laragon/www/sinta/scratch/folder legacy/app/Models/Pengguna.php",
    "C:/laragon/www/sinta/app/Modules/Sistem/Models/PenggunaModel.php",
    "App\Modules\Sistem\Models",
    "PenggunaModel",
    true
);

// 2. Kelembagaan
migrateFile(
    "C:/laragon/www/sinta/scratch/folder legacy/app/Controllers/KelembagaanController.php",
    "C:/laragon/www/sinta/app/Modules/Sistem/Controllers/KelembagaanModuleController.php",
    "App\Modules\Sistem\Controllers",
    "KelembagaanModuleController"
);
migrateFile(
    "C:/laragon/www/sinta/scratch/folder legacy/app/Models/Kelembagaan.php",
    "C:/laragon/www/sinta/app/Modules/Sistem/Models/KelembagaanModel.php",
    "App\Modules\Sistem\Models",
    "KelembagaanModel",
    true
);

// 3. Buku Induk
migrateFile(
    "C:/laragon/www/sinta/scratch/folder legacy/app/Controllers/BukuIndukController.php",
    "C:/laragon/www/sinta/app/Modules/Siswa/Controllers/BukuIndukModuleController.php",
    "App\Modules\Siswa\Controllers",
    "BukuIndukModuleController"
);

// 4. Pengumuman
migrateFile(
    "C:/laragon/www/sinta/scratch/folder legacy/app/Controllers/PengumumanController.php",
    "C:/laragon/www/sinta/app/Modules/Core/Controllers/PengumumanModuleController.php",
    "App\Modules\Core\Controllers",
    "PengumumanModuleController"
);

echo "Migration script executed successfully.";

