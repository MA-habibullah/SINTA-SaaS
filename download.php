<?php
// download.php - Gatekeeper Akses Berkas Sensitif (Secure by Design)
require_once __DIR__ . '/app/Core/SessionManager.php';
require_once __DIR__ . '/app/Config/Database.php';

use App\Core\SessionManager;
use App\Config\Database;

SessionManager::start();

// 1. Validasi Login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    error_log("DOWNLOAD ERROR 401: Belum login.");
    die("401 Unauthorized: Silakan login terlebih dahulu.");
}

$roleName = $_SESSION['role_name'] ?? '';
$userId   = $_SESSION['user_id'] ?? '';

// 2. Ambil Parameter
$file = $_GET['file'] ?? '';
if (empty($file)) {
    http_response_code(400);
    die("400 Bad Request: Parameter berkas tidak ditentukan.");
}

// Pencegahan Path Traversal Attack
$file = str_replace(['../', '..\\'], '', $file);

$db = Database::getConnection();

// Cek apakah format path baru (mengandung slash) atau legacy (tidak mengandung slash)
$isLegacy = (strpos($file, '/') === false);

if (!$isLegacy) {
    // FORMAT BARU: uploads/{tenant_id}/{siswa_id}/{filename}
    $pathParts = explode('/', trim($file, '/'));
    if (count($pathParts) < 4 || $pathParts[0] !== 'uploads') {
        http_response_code(400);
        error_log("DOWNLOAD ERROR 400: Format path berkas tidak valid: " . $file);
        die("400 Bad Request: Format path berkas tidak valid.");
    }

    $tenantId = $pathParts[1];
    $siswaId  = $pathParts[2];
    $fileName = $pathParts[3];

    // Check RBAC & Tenant
    if ($roleName === 'siswa') {
        $stmt = $db->prepare("SELECT id FROM siswa WHERE user_id = :user_id LIMIT 1");
        $stmt->execute(['user_id' => $userId]);
        $siswa = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$siswa || $siswa['id'] !== $siswaId) {
            http_response_code(403);
            error_log("DOWNLOAD ERROR 403: Siswa mismatch. currentSiswa=" . ($siswa['id'] ?? 'null') . " requestedSiswa=" . $siswaId);
            die("403 Forbidden: Anda tidak memiliki wewenang untuk mengakses berkas ini.");
        }
    } elseif ($roleName !== 'super_admin') {
        // Operator Sekolah, Guru, and Guru BK are allowed but restricted to their own tenant/school
        $currentTenantId = SessionManager::getTenantId();
        if (empty($currentTenantId) || $currentTenantId !== $tenantId) {
            http_response_code(403);
            error_log("DOWNLOAD ERROR 403: Tenant mismatch. currentTenant=" . $currentTenantId . " requestedTenant=" . $tenantId);
            die("403 Forbidden: Anda tidak diizinkan mengakses berkas dari sekolah/tenant lain.");
        }
    }

    $filePath = __DIR__ . '/storage/app/public/uploads/' . $tenantId . '/' . $siswaId . '/' . $fileName;
} else {
    // FORMAT LEGACY: filename.ext (membutuhkan parameter tenant & field)
    $tenantId = $_GET['tenant'] ?? '';
    $fieldName = $_GET['field'] ?? '';

    if (empty($tenantId) || empty($fieldName)) {
        http_response_code(400);
        die("400 Bad Request: Parameter pendukung legacy tidak lengkap.");
    }

    // Check RBAC & Tenant
    if ($roleName === 'siswa') {
        $stmt = $db->prepare("
            SELECT s.id, s.tenant_id 
            FROM siswa s 
            LEFT JOIN rincian_pelajar rp ON s.id = rp.id_siswa
            LEFT JOIN dokumen d ON s.id = d.id_siswa
            WHERE s.user_id = :user_id 
              AND (rp.foto_profil = :file OR d.berkas_kk = :file OR d.berkas_akta = :file OR d.berkas_ijazah_sd = :file OR d.berkas_ijazah_smp = :file OR d.berkas_ijazah_sma = :file OR d.berkas_mutasi_masuk = :file OR d.berkas_mutasi_keluar = :file OR d.berkas_kip = :file)
            LIMIT 1
        ");
        $stmt->execute(['user_id' => $userId, 'file' => $file]);
        $siswa = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$siswa || $siswa['tenant_id'] !== $tenantId) {
            http_response_code(403);
            die("403 Forbidden: Anda tidak memiliki wewenang untuk mengakses berkas ini.");
        }
    } elseif ($roleName !== 'super_admin') {
        // Operator Sekolah, Guru, and Guru BK are allowed but restricted to their own tenant/school
        $currentTenantId = SessionManager::getTenantId();
        if (empty($currentTenantId) || $currentTenantId !== $tenantId) {
            http_response_code(403);
            die("403 Forbidden: Anda tidak diizinkan mengakses berkas dari sekolah/tenant lain.");
        }
    }

    $filePath = __DIR__ . '/storage/uploads/' . $tenantId . '/' . $fieldName . '/' . $file;
}

// 4. Periksa Keberadaan File Fisik
if (!file_exists($filePath)) {
    http_response_code(404);
    error_log("DOWNLOAD ERROR 404: File tidak ditemukan di server. filePath=" . $filePath);
    die("404 Not Found: Berkas fisik tidak ditemukan di server.");
}

// 5. Kirim Berkas Secara Aman
if (function_exists('finfo_open')) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $filePath);
    finfo_close($finfo);
} elseif (function_exists('mime_content_type')) {
    $mimeType = mime_content_type($filePath);
} else {
    $mimeType = 'application/octet-stream';
}

header('Content-Description: File Transfer');
header('Content-Type: ' . $mimeType);
header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath));

if (ob_get_level()) {
    ob_clean();
}
flush();
readfile($filePath);
exit;
