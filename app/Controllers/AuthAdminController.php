<?php

namespace App\Controllers;

use App\Config\Database;
use App\Models\User;
use App\Core\SessionManager;
use PDO;

class AuthAdminController extends BaseController {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Render the admin login page
     * GET /admin
     */
    public function loginView(): void {
        // Jika sudah login, langsung lempar ke dashboard
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            if (($_SESSION['role_name'] ?? '') !== 'siswa') {
                header('Location: /SINTA-SaaS/dashboard');
                exit;
            }
        }

        // Ambil daftar sekolah secara dinamis untuk selector login lokal (simulasi subdomain)
        $db = Database::getConnection();
        $tenants = $db->query("SELECT subdomain, nama_sekolah, npsn FROM tenants WHERE status = 'active' AND deleted_at IS NULL ORDER BY nama_sekolah ASC")->fetchAll(PDO::FETCH_ASSOC);

        require_once __DIR__ . '/../../views/login_view.php';
    }

    /**
     * API: Autentikasi Admin / Staf / Super Admin
     * POST /api/v1/auth/login
     */
    public function login(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed.'], 405);
        }

        // Rate Limiter Check (Brute force protection)
        $this->checkRateLimit();

        $input = $this->getJsonInput();
        $email = isset($input['email']) ? trim($input['email']) : '';
        $password = isset($input['password']) ? $input['password'] : '';

        if (empty($email) || empty($password)) {
            $this->jsonResponse(['error' => 'Email dan password wajib diisi.'], 400);
        }

        // Cari user berdasarkan email dan konteks tenant (subdomain sekolah) saat ini
        $userModel = new User($this->tenantId);
        $user = $userModel->findByEmailAndTenant($email, $this->tenantId);
        $genericError = 'Email atau password salah.';

        if (!$user) {
            $this->incrementFailures();
            $this->jsonResponse(['error' => $genericError], 401);
        }

        // Pastikan akun active
        if (($user['status'] ?? '') !== 'active') {
            $this->jsonResponse(['error' => 'Akun ditangguhkan. Silakan hubungi administrator.'], 403);
        }

        // Deteksi Multi-Tenant: Admin Sekolah (memiliki tenant_id) vs Super Admin (tenant_id IS NULL)
        if (!empty($user['tenant_id'])) {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT status FROM tenants WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([$user['tenant_id']]);
            $tenantStatus = $stmt->fetchColumn();

            if ($tenantStatus !== 'active') {
                $this->jsonResponse(['error' => 'Akses sekolah ditangguhkan atau dinonaktifkan.'], 403);
            }
        }

        // Verifikasi password
        if (!password_verify($password, $user['password'])) {
            $this->incrementFailures();
            $this->jsonResponse(['error' => $genericError], 401);
        }

        // Clear failures on success
        $this->resetFailures();

        // Inisialisasi Sesi Admin (Safe from collision with Student session keys)
        SessionManager::start();
        session_regenerate_id(true);

        // Fetch user roles
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT r.nama_role 
                FROM user_roles ur
                JOIN roles r ON ur.role_id = r.id
                WHERE ur.user_id = ?
            ");
            $stmt->execute([$user['id']]);
            $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (\Throwable $e) {
            $roles = [];
        }

        if (empty($roles)) {
            $roles = [$user['nama_role']];
        }

        $_SESSION['admin'] = [
            'logged_in' => true,
            'id' => $user['id'],
            'tenant_id' => $user['tenant_id'], // NULL = Super Admin
            'role_name' => $user['nama_role'],
            'roles' => $roles,
            'nama_lengkap' => $user['nama_lengkap'],
            'email' => $user['email'],
            'last_activity' => time()
        ];

        // Fallback session keys for backwards compatibility with existing views/controllers
        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['tenant_id'] = $user['tenant_id'];
        $_SESSION['role_name'] = $user['nama_role'];
        $_SESSION['roles'] = $roles;
        $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['last_activity'] = time();

        $this->jsonResponse([
            'success' => true,
            'message' => 'Login berhasil.'
        ]);
    }

    /**
     * Logout Admin / Staf / Super Admin
     * GET /api/v1/auth/logout
     */
    public function logout(): void {
        // Audit Trail: Record LOGOUT before session is destroyed
        try {
            $userId = $_SESSION['admin']['id'] ?? $_SESSION['user_id'] ?? 'unknown';
            if ($userId !== 'unknown') {
                \App\Helpers\ActivityLogger::record('LOGOUT', 'users', $userId);
            }
        } catch (\Throwable $e) {}

        SessionManager::logout();
        header('Location: /SINTA-SaaS/admin');
        exit;
    }

    /**
     * Check brute force attempts based on client IP
     */
    private function checkRateLimit(): void {
        SessionManager::start();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $limitKey = 'login_fail_' . md5($ip);
        
        if (isset($_SESSION[$limitKey])) {
            $lockoutTime = $_SESSION[$limitKey]['lockout_until'] ?? 0;
            if (time() < $lockoutTime) {
                $diff = $lockoutTime - time();
                $this->jsonResponse(['error' => "Terlalu banyak kegagalan login. Coba lagi dalam {$diff} detik."], 429);
            }
        }
    }

    /**
     * Record a failed login attempt
     */
    private function incrementFailures(): void {
        SessionManager::start();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $limitKey = 'login_fail_' . md5($ip);
        
        if (!isset($_SESSION[$limitKey])) {
            $_SESSION[$limitKey] = ['attempts' => 1, 'lockout_until' => 0];
        } else {
            $_SESSION[$limitKey]['attempts']++;
        }

        if ($_SESSION[$limitKey]['attempts'] >= 5) {
            $_SESSION[$limitKey]['lockout_until'] = time() + 60; // 60 seconds lockout
            $_SESSION[$limitKey]['attempts'] = 0; // Reset counter for next block
        }
    }

    /**
     * Reset failure counter on successful login
     */
    private function resetFailures(): void {
        SessionManager::start();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $limitKey = 'login_fail_' . md5($ip);
        unset($_SESSION[$limitKey]);
    }
}
