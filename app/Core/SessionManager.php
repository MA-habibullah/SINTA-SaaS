<?php

namespace App\Core;

class SessionManager {
    
    /**
     * Start a secure session
     */
    public static function start(): void {
        if (session_status() === PHP_SESSION_NONE) {
            // Secure by Design cookie configuration
            $cookieParams = [
                'lifetime' => 0,                     // Session expires when browser closes
                'path' => '/',
                'domain' => '',                      // Defaults to current host
                'secure' => isset($_SERVER['HTTPS']), // True only if HTTPS is used
                'httponly' => true,                  // Prevent XSS access to cookie
                'samesite' => 'Lax'                  // Protect against CSRF
            ];
            
            session_set_cookie_params($cookieParams);
            session_start();
        }
    }

    /**
     * Login user and regenerate session ID (prevents Session Fixation)
     */
    public static function login(array $user): void {
        self::start();
        
        // Secure by Design: Hancurkan session lama dan buat ID baru untuk cegah session hijacking/fixation
        session_regenerate_id(true);

        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['tenant_id'] = $user['tenant_id']; // NULL jika Super Admin
        $_SESSION['role_name'] = $user['nama_role'];
        $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['last_activity'] = time();

        // Fetch and store all roles for this user
        try {
            $db = \App\Config\Database::getConnection();
            $stmt = $db->prepare("
                SELECT r.nama_role 
                FROM user_roles ur
                JOIN roles r ON ur.role_id = r.id
                WHERE ur.user_id = ?
            ");
            $stmt->execute([$user['id']]);
            $roles = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        } catch (\Throwable $e) {
            $roles = [];
        }

        if (empty($roles)) {
            $roles = [$user['nama_role']];
        }

        $_SESSION['roles'] = $roles;
    }

    /**
     * Logout and destroy session completely
     */
    public static function logout(): void {
        self::start();
        
        // Unset all session variables
        $_SESSION = [];

        // Delete the session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), 
                '', 
                time() - 42000,
                $params["path"], 
                $params["domain"],
                $params["secure"], 
                $params["httponly"]
            );
        }

        // Destroy session file on server
        session_destroy();
    }

    /**
     * Get current Tenant ID
     */
    public static function getTenantId(): ?string {
        self::start();
        return $_SESSION['tenant_id'] ?? null;
    }

    /**
     * Check if user is logged in
     */
    public static function isLoggedIn(): bool {
        self::start();
        
        // Cek juga session timeout (misal: 30 menit tidak aktif)
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            $timeout = 1800; // 30 Menit
            if (time() - ($_SESSION['last_activity'] ?? 0) > $timeout) {
                self::logout();
                return false;
            }

            // Secure SaaS Gatekeeper: Check tenant status if not Super Admin
            if (isset($_SESSION['tenant_id']) && $_SESSION['tenant_id'] !== null) {
                try {
                    $db = \App\Config\Database::getConnection();
                    $stmt = $db->prepare("SELECT status FROM tenants WHERE id = ? AND deleted_at IS NULL");
                    $stmt->execute([$_SESSION['tenant_id']]);
                    $status = $stmt->fetchColumn();
                    if ($status !== 'active') {
                        self::logout();
                        return false;
                    }
                } catch (\Throwable $e) {
                    // Fail-safe: ignore DB connection errors during session checks
                }
            }
            
            $_SESSION['last_activity'] = time(); // Refresh aktivitas terakhir
            return true;
        }
        
        return false;
    }

    /**
     * Enforce authentication gate
     */
    public static function requireLogin(): void {
        if (!self::isLoggedIn()) {
            $requestUri = $_SERVER['REQUEST_URI'] ?? '';
            $isApi = str_contains($requestUri, '/api/') || 
                     (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) ||
                     (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
            
            if ($isApi) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized access. Please login.']);
                exit;
            } else {
                $redirectUrl = '/SINTA-SaaS/login';
                // Jika mengakses halaman khusus admin/staf/pengguna, arahkan ke login admin
                $path = parse_url($requestUri, PHP_URL_PATH);
                $adminPaths = ['/pengguna', '/super-admin', '/konfigurasi', '/master-data', '/siswa/tambah', '/siswa/edit', '/siswa/hapus'];
                foreach ($adminPaths as $adminPath) {
                    if (str_contains($path, $adminPath)) {
                        $redirectUrl = '/SINTA-SaaS/admin';
                        break;
                    }
                }
                header('Location: ' . $redirectUrl);
                exit;
            }
        }
    }
}
