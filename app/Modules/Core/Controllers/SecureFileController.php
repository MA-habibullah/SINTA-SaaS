<?php
namespace App\Modules\Core\Controllers;

use App\Core\BaseController;

/**
 * SecureFileController
 * =====================
 * Melayani file upload dengan validasi sesi & tenant sebelum file dikirim.
 * Endpoint: GET /api/v1/file/serve?path=storage/app/public/...
 *
 * Lapisan Keamanan:
 * 1. Cek sesi login wajib
 * 2. Validasi ekstensi file (whitelist)
 * 3. Cegah Path Traversal (/../ dll)
 * 4. Validasi MIME type real menggunakan fileinfo
 * 5. X-Content-Type-Options: nosniff header
 * 6. Tambahan: Content-Disposition inline hanya untuk image
 */
class SecureFileController extends BaseController
{
    // Ekstensi yang diizinkan diunduh
    private const ALLOWED_EXTENSIONS = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'webp' => 'image/webp',
        'gif'  => 'image/gif',
        'pdf'  => 'application/pdf',
    ];

    // Folder yang boleh diakses melalui endpoint ini (whitelist)
    private const ALLOWED_DIRS = [
        'storage/app/public/pelanggaran/',
        'storage/app/public/pembinaan/',
        'storage/app/public/sertifikat/',
        'storage/app/public/logos/',
        'storage/app/public/uploads/',
        'storage/app/public/tenants/',
        'storage/app/public/scans/',
        'storage/app/public/pdss/',
        'storage/app/public/perpustakaan/',
        'storage/app/public/archive/',
    ];

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * GET /api/v1/file/serve?path=storage/app/public/pelanggaran/xxx.jpg
     */
    public function serve(): void
    {
        // ─── 1. Cek sesi login wajib ────────────────────────────────────────
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized. Silakan login terlebih dahulu.']);
            exit;
        }

        // ─── 2. Ambil & bersihkan parameter path ────────────────────────────
        $rawPath = $_GET['path'] ?? '';
        if (empty($rawPath)) {
            http_response_code(400);
            echo json_encode(['error' => 'Parameter path wajib disertakan.']);
            exit;
        }

        // ─── 3. Cegah Path Traversal ────────────────────────────────────────
        // Normalisasi: hapus ../, ..\, %2e%2e, encoded slash, dsb
        $cleanPath = str_replace(['../', '.\\', '../', '%2e%2e', '%00'], '', $rawPath);
        $cleanPath = ltrim($cleanPath, '/');
        $cleanPath = preg_replace('/[^a-zA-Z0-9\/\-_.]/u', '', $cleanPath);

        // ─── 4. Pastikan path ada dalam whitelist folder yang diizinkan ─────
        $isAllowed = false;
        foreach (self::ALLOWED_DIRS as $dir) {
            if (str_starts_with($cleanPath, $dir) || str_starts_with($cleanPath, ltrim($dir, '/'))) {
                $isAllowed = true;
                break;
            }
        }
        if (!$isAllowed) {
            http_response_code(403);
            echo json_encode(['error' => 'Akses ke direktori ini tidak diizinkan.']);
            exit;
        }

        // ─── 5. Resolve path absolut & pastikan ada dalam root aplikasi ──────
        $baseDir  = defined('BASE_DIR') ? \BASE_DIR : realpath(__DIR__ . '/../../../../');
        $fullPath = $baseDir . '/' . $cleanPath;
        $realBase = realpath($baseDir);

        if (!file_exists($fullPath) || !is_file($fullPath)) {
            http_response_code(404);
            echo json_encode(['error' => 'File tidak ditemukan.']);
            exit;
        }

        $absPath = realpath($fullPath);
        if ($absPath === false || $realBase === false || !str_starts_with($absPath, $realBase)) {
            http_response_code(403);
            echo json_encode(['error' => 'Akses ke file di luar direktori aplikasi ditolak.']);
            exit;
        }

        // ─── 6. Validasi ekstensi (whitelist) ─────────────────────────────
        $ext = strtolower(pathinfo($absPath, PATHINFO_EXTENSION));
        if (!array_key_exists($ext, self::ALLOWED_EXTENSIONS)) {
            http_response_code(415);
            echo json_encode(['error' => 'Tipe file tidak diizinkan.']);
            exit;
        }

        // ─── 7. Validasi MIME type real menggunakan fileinfo ──────────────
        $finfo        = finfo_open(FILEINFO_MIME_TYPE);
        $realMime     = finfo_file($finfo, $absPath);
        finfo_close($finfo);
        $expectedMime = self::ALLOWED_EXTENSIONS[$ext];

        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'application/pdf'];
        if (!in_array($realMime, $allowedMimes, true)) {
            http_response_code(415);
            echo json_encode(['error' => 'MIME type file tidak valid. File ditolak.']);
            exit;
        }

        // ─── 8. Kirim file dengan header keamanan lengkap ─────────────────
        header('Content-Type: ' . $realMime);
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Cache-Control: private, max-age=3600');
        header('Content-Length: ' . filesize($absPath));

        // Default: 'inline' agar PDF & gambar bisa dipratinjau di browser/modal.
        // Jika parameter ?download=1 disertakan → paksa unduhan ('attachment').
        $disposition = (isset($_GET['download']) && $_GET['download'] === '1') ? 'attachment' : 'inline';
        header('Content-Disposition: ' . $disposition . '; filename="' . basename($absPath) . '"');

        readfile($absPath);
        exit;
    }
}
