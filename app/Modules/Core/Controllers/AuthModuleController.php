<?php

namespace App\Modules\Core\Controllers;

use App\Core\BaseController;
use App\Config\Database;
use App\Modules\Core\Models\UserModel;
use App\Core\SessionManager;
use PDO;

class AuthModuleController extends BaseController {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Tampilkan Halaman Login khusus Admin/Staff/SuperAdmin
     * GET /admin
     */
    public function adminLoginView(): void {
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            if (($_SESSION['role_name'] ?? '') !== 'siswa') {
                $this->redirect('/dashboard');
            }
        }

        try {
            $db = Database::getConnection();
            $tenants = $db->query("SELECT subdomain, nama_sekolah, npsn FROM core.tenants WHERE status = 'active' ORDER BY nama_sekolah ASC")->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $tenants = [];
        }

        require_once __DIR__ . '/../../../../views/login_view.php';
    }

    /**
     * API: Autentikasi Admin / Staf / Super Admin
     * POST /api/v1/auth/login
     */
    public function loginAdminApi(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(false, null, 'Method not allowed.', 405);
        }

        $input = $this->getJsonInput();
        $email = isset($input['email']) ? trim($input['email']) : '';
        $password = isset($input['password']) ? $input['password'] : '';

        if (empty($email) || empty($password)) {
            $this->jsonResponse(false, null, 'Email dan password wajib diisi.', 400);
        }

        $genericError = 'Email atau password salah.';

        // --- Rate Limiting: Max 5 login attempts per IP per 15 menit ---
        $throttleKey   = 'login_attempts_' . md5($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $throttleTime  = 'login_attempts_time_' . md5($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $maxAttempts   = 5;
        $windowSeconds = 900; // 15 menit

        \App\Core\SessionManager::start();
        $attempts  = (int)($_SESSION[$throttleKey]  ?? 0);
        $firstTime = (int)($_SESSION[$throttleTime] ?? 0);

        // Reset window jika sudah lewat 15 menit
        if ($firstTime > 0 && (time() - $firstTime) > $windowSeconds) {
            $_SESSION[$throttleKey]  = 0;
            $_SESSION[$throttleTime] = 0;
            $attempts = 0;
        }

        // Blokir jika sudah melebihi batas
        if ($attempts >= $maxAttempts) {
            $remaining = $windowSeconds - (time() - $firstTime);
            $this->jsonResponse(false, null, "Terlalu banyak percobaan login. Coba lagi dalam " . ceil($remaining / 60) . " menit.", 200);
        }

        // Catat percobaan pertama
        if ($attempts === 0) {
            $_SESSION[$throttleTime] = time();
        }
        // --- End Rate Limiting ---



        try {
            $userModel = new UserModel();
            $user = $userModel->findByEmailAndTenant($email, $this->tenantId);

            // Jika tidak ditemukan via tenantId, coba fallback cari by email saja (Super Admin global)
            if (!$user) {
                $db = Database::getConnection();
                $stmt = $db->prepare("
                    SELECT u.id, u.tenant_id, u.role_id, u.nama_lengkap, u.username, u.email,
                           u.password_hash as password, u.is_active, r.nama_role
                    FROM core.users u
                    JOIN core.roles r ON u.role_id = r.id
                    WHERE u.email = ?
                    LIMIT 1
                ");
                $stmt->execute([$email]);
                $user = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
            }

            if (!$user) {
                $_SESSION[$throttleKey] = ($attempts + 1);
                $this->jsonResponse(false, null, $genericError, 200);
            }

            // Cek is_active (boolean PostgreSQL)
            if (isset($user['is_active']) && $user['is_active'] == false) {
                $this->jsonResponse(false, null, 'Akun ditangguhkan. Silakan hubungi administrator.', 200);
            }

            if (!empty($user['tenant_id'])) {
                $db = Database::getConnection();
                $stmt = $db->prepare("SELECT status FROM core.tenants WHERE id = ?");
                $stmt->execute([$user['tenant_id']]);
                $tenantStatus = $stmt->fetchColumn();

                if ($tenantStatus && $tenantStatus !== 'active') {
                    $this->jsonResponse(false, null, 'Akses sekolah ditangguhkan atau dinonaktifkan.', 200);
                }
            }

            if (!password_verify($password, $user['password'])) {
                $_SESSION[$throttleKey] = ($attempts + 1);
                $this->jsonResponse(false, null, $genericError, 200);
            }

            // Login berhasil — reset throttle counter
            $_SESSION[$throttleKey]  = 0;
            $_SESSION[$throttleTime] = 0;

            SessionManager::start();
            session_regenerate_id(true);

            try {
                $db = Database::getConnection();
                $stmt = $db->prepare("
                    SELECT r.nama_role 
                    FROM core.user_roles ur
                    JOIN core.roles r ON ur.role_id = r.id
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
                'tenant_id' => $user['tenant_id'],
                'role_name' => $user['nama_role'],
                'roles' => $roles,
                'nama_lengkap' => $user['nama_lengkap'],
                'email' => $user['email'],
                'last_activity' => time()
            ];

            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['tenant_id'] = $user['tenant_id'];
            $_SESSION['role_name'] = $user['nama_role'];
            $_SESSION['roles'] = $roles;
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['last_activity'] = time();

            \App\Helpers\ActivityLogger::log(
                'LOGIN', 
                'core.users', 
                null, 
                ['email' => $user['email'], 'nama_lengkap' => $user['nama_lengkap'], 'role' => $user['nama_role']],
                $user['tenant_id'],
                $user['id']
            );

            $this->jsonResponse(true, [
                'message' => 'Login berhasil. Mengalihkan ke dashboard...',
                'redirect' => $this->getBaseUrl() . '/dashboard'
            ]);

        } catch (\Throwable $e) {
            $this->jsonResponse(false, null, 'Gagal terhubung ke database: ' . $e->getMessage(), 400);
        }
    }
    /**
     * Logout pengguna dari sesi saat ini
     * POST /api/v1/auth/logout
     */
    public function logout(): void {
        \App\Core\SessionManager::start();

        if (!empty($_SESSION['logged_in'])) {
            \App\Helpers\ActivityLogger::log(
                'LOGOUT', 
                'core.users', 
                ['email' => $_SESSION['email'] ?? '', 'nama_lengkap' => $_SESSION['nama_lengkap'] ?? ''],
                null,
                $_SESSION['tenant_id'] ?? null,
                $_SESSION['user_id'] ?? null
            );
        }

        session_unset();
        session_destroy();
        
        // Turbo.js / standard form expecting a redirect
        $this->redirect('/admin');
    }
}
