<?php

namespace App\Core;

class BaseController {
    protected ?string $tenantId = null;

    public function __construct() {
        $this->detectTenant();
    }

    protected function detectTenant(): void {
        $subdomain = null;

        if (isset($_SERVER['HTTP_X_TENANT_ID'])) {
            $subdomain = $_SERVER['HTTP_X_TENANT_ID'];
        } else {
            $host = $_SERVER['HTTP_HOST'] ?? '';
            $parts = explode('.', $host);
            if (count($parts) >= 3 && $parts[0] !== 'www') {
                $subdomain = $parts[0];
            }
        }

        if ($subdomain !== null && $subdomain !== '') {
            $this->tenantId = $this->getTenantIdBySubdomain($subdomain);
        }
    }

    private function getTenantIdBySubdomain(string $subdomain): ?string {
        try {
            $db = \App\Config\Database::getConnection();
            $stmt = $db->prepare("SELECT id FROM core.tenants WHERE subdomain = :subdomain AND status = 'active' LIMIT 1");
            $stmt->execute(['subdomain' => $subdomain]);
            $row = $stmt->fetch();
            return $row ? $row['id'] : null;
        } catch (\PDOException $e) {
            error_log("Failed to detect tenant by subdomain: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Backward-compatible render method for legacy MVC controllers (loads master layout)
     */
    protected function render(string $view, array $data = []): void {
        extract($data);
        $contentView = __DIR__ . '/../../views/' . $view . '.php';

        // Smart Fallback View Resolver: jika view tidak ditemukan di root views/, cari di subfolder modul
        if (!file_exists($contentView)) {
            $possibleFolders = ['sistem', 'core', 'humas', 'siswa', 'rapor', 'kesiswaan', 'bk', 'alumni', 'pdss', 'perpustakaan', 'keuangan', 'utility'];
            foreach ($possibleFolders as $folder) {
                $fallbackPath = __DIR__ . '/../../views/' . $folder . '/' . $view . '.php';
                if (file_exists($fallbackPath)) {
                    $contentView = $fallbackPath;
                    break;
                }
            }
        }

        if (!file_exists($contentView)) {
            error_log("View not found: " . $contentView);
        }
        require_once __DIR__ . '/../../views/layout/master.php';
    }

    /**
     * Dapatkan Base URL dinamis dari lingkungan server
     */
    protected function getBaseUrl(): string {
        $appUrl = getenv('APP_URL') ?: ($_ENV['APP_URL'] ?? '');
        if (!empty($appUrl)) {
            $parsed = parse_url($appUrl, PHP_URL_PATH);
            return $parsed ? rtrim($parsed, '/') : '';
        }
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        return rtrim(dirname($scriptName), '/\\');
    }

    /**
     * Redirect HTTP dinamis bebas hardcode folder
     */
    protected function redirect(string $path): void {
        $cleanPath = '/' . ltrim($path, '/');
        $baseUrl = $this->getBaseUrl();
        header('Location: ' . $baseUrl . $cleanPath);
        exit;
    }

    protected function jsonResponse(mixed $success, mixed $data = null, ?string $error = null, int $statusCode = 200): void {
        
        // Handle array payload (backward compatibility for controllers passing arrays directly)
        if (is_array($success)) {
            $payload = $success;
            $statusCode = is_int($data) ? $data : 200;
            
            if (isset($payload['error']) || isset($payload['errors'])) {
                $success = false;
                $error = $payload['error'] ?? json_encode($payload['errors']);
                $data = null;
            } else if (array_key_exists('success', $payload) && array_key_exists('data', $payload)) {
                $success = (bool)$payload['success'];
                $error = $payload['error'] ?? null;
                // If payload has extra top-level metadata (e.g. is_locked, total, stats, etc.)
                $extraKeys = array_diff(array_keys($payload), ['success', 'data', 'error']);
                if (!empty($extraKeys)) {
                    $responseArr = $payload;
                    $responseArr['success'] = $success;
                    $responseArr['error'] = $error;
                    http_response_code($statusCode);
                    header('Content-Type: application/json; charset=utf-8');
                    $responseArr = $this->sanitizeDataArray($responseArr);
                    echo json_encode($responseArr, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
                    exit;
                }
                $data = $payload['data'];
            } else if (array_key_exists('data', $payload) && count($payload) === 1) {
                // Just ['data' => ...]
                $success = true;
                $data = $payload['data'];
            } else {
                // Raw data array like ['data' => [], 'total' => 100]
                $success = true;
                $data = $payload;
            }
        } else {
            $success = (bool)$success;
        }

        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');

        if (is_array($data)) {
            $data = $this->sanitizeDataArray($data);
        }

        if ($statusCode >= 500 && !empty($error)) {
            try {
                \App\Helpers\ErrorTracker::logToDatabase(
                    level: 'API_SERVER_ERROR',
                    message: is_string($error) ? $error : json_encode($error),
                    file: null,
                    line: $statusCode,
                    trace: debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 8)
                );
            } catch (\Throwable $ignored) {}
        }

        echo json_encode([
            'success' => $success,
            'data'    => $data,
            'error'   => $error
        ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        exit;
    }

    private function sanitizeDataArray(array $arr): array {
        foreach ($arr as $key => &$val) {
            if (in_array(strtolower((string)$key), ['password', 'password_hash', 'token', 'remember_token', 'api_key'], true)) {
                unset($arr[$key]);
                continue;
            }
            if (is_array($val)) {
                $val = $this->sanitizeDataArray($val);
            }
        }
        return $arr;
    }

    protected function getJsonInput(): array {
        $raw = file_get_contents('php://input');
        if (empty($raw)) {
            return $_POST;
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : $_POST;
    }

    /**
     * Sanitasi input string untuk mencegah XSS.
     * Digunakan oleh seluruh controller turunan untuk membersihkan
     * nilai dari $_GET, $_POST, atau array input sebelum diproses.
     *
     * @param mixed $value Nilai input yang akan disanitasi
     * @return string      Nilai yang sudah bersih dari tag HTML dan karakter berbahaya
     */
    protected function sanitize(mixed $value): string
    {
        if ($value === null || $value === false) {
            return '';
        }
        $str = (string) $value;
        // Hapus tag HTML/script terlebih dahulu
        $str = strip_tags($str);
        // Encode karakter spesial HTML untuk mencegah XSS residual
        $str = htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return trim($str);
    }

    /**
     * Catat Throwable (Exception/Error) yang tertangkap oleh catch-block ke DB error-monitor.
     *
     * PENTING: `catch (Throwable $e)` di controller "menelan" error sebelum ErrorTracker
     * sempat menangkapnya via set_exception_handler. Method ini menjadi jembatan agar
     * error tetap tercatat di tabel sistem.system_errors dan tampil di halaman
     * /super-admin/error-monitor.
     *
     * Cara pakai di controller:
     *   } catch (Throwable $e) {
     *       $this->logApiException($e);
     *       $this->jsonResponse(false, null, 'Pesan user-friendly.', 500);
     *   }
     *
     * @param \Throwable $e         Exception atau Error yang tertangkap
     * @param string     $context   Nama method/fitur untuk memudahkan pencarian di monitor
     */
    protected function logApiException(\Throwable $e, string $context = ''): void
    {
        try {
            \App\Helpers\ErrorTracker::logToDatabase(
                level:   ($context ? "[{$context}] " : '') . get_class($e),
                message: $e->getMessage(),
                file:    $e->getFile(),
                line:    $e->getLine(),
                trace:   $e->getTrace()
            );
        } catch (\Throwable $logErr) {
            // Fallback agar logging tidak menyebabkan error tambahan
            error_log('[BaseController::logApiException] Gagal catat ke DB: ' . $logErr->getMessage());
            error_log('[BaseController::logApiException] Error Asli: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        }
    }

    /**
     * Cek apakah request ini adalah JSON/API request.
     * Digunakan untuk menentukan format response (JSON vs HTML).
     */
    protected function isJsonRequest(): bool
    {
        return (
            str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') ||
            str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') ||
            str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/') ||
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
        );
    }

    /**
     * Dapatkan Tenant ID yang aman dari session (dengan fallback).
     */
    protected function getSecureTenantId(): ?string
    {
        $tenantId = $this->tenantId ?: ($_SESSION['tenant_id'] ?? null);
        return $tenantId ? (string)$tenantId : null;
    }

    /**
     * Dapatkan token CSRF aktif untuk sesi saat ini
     */
    protected function getCsrfToken(): string
    {
        return SessionManager::getCsrfToken();
    }

    /**
     * Validasi token CSRF pada request mutasi data (POST/PUT/DELETE)
     *
     * @param ?string $token Token opsional jika dikirim manual
     * @param bool $autoExit Jika true, langsung kirim response 403 jika tidak valid
     * @return bool True jika valid
     */
    protected function validateCsrfToken(?string $token = null, bool $autoExit = true): bool
    {
        if ($token === null) {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
            if (!$token) {
                $rawInput = $this->getJsonInput();
                $token = $rawInput['_csrf_token'] ?? ($rawInput['csrf_token'] ?? ($_POST['_csrf_token'] ?? ($_POST['csrf_token'] ?? null)));
            }
        }

        $valid = SessionManager::validateCsrfToken($token);

        if (!$valid && $autoExit) {
            if ($this->isJsonRequest()) {
                $this->jsonResponse(false, null, 'Sesi keamanan tidak valid atau telah kedaluwarsa (CSRF token mismatch). Silakan segarkan halaman.', 403);
            } else {
                http_response_code(403);
                header('Content-Type: text/plain; charset=utf-8');
                echo "403 Forbidden: Sesi keamanan tidak valid (CSRF token mismatch).";
                exit;
            }
        }

        return $valid;
    }

    /**
     * Cek apakah pengguna saat ini memiliki role super_admin / admin platform
     */
    protected function isUserSuperAdmin(): bool
    {
        $role = $_SESSION['user']['role'] ?? $_SESSION['role_name'] ?? '';
        if (in_array(strtolower((string)$role), ['super_admin', 'super admin', 'superadmin', 'admin platform'], true)) {
            return true;
        }
        $roles = $_SESSION['roles'] ?? [];
        if (is_array($roles)) {
            foreach ($roles as $r) {
                if (in_array(strtolower((string)$r), ['super_admin', 'super admin', 'superadmin', 'admin platform'], true)) {
                    return true;
                }
            }
        }
        return false;
    }
}


