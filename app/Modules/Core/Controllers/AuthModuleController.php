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

        try {
            $userModel = new UserModel();
            $user = $userModel->findByEmailAndTenant($email, $this->tenantId);

            if (!$user) {
                $this->jsonResponse(false, null, $genericError, 200);
            }

            if (($user['status'] ?? 'active') !== 'active') {
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
                $this->jsonResponse(false, null, $genericError, 200);
            }

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

            $this->jsonResponse(true, [
                'message' => 'Login berhasil. Mengalihkan ke dashboard...',
                'redirect' => $this->getBaseUrl() . '/dashboard'
            ]);

        } catch (\Throwable $e) {
            $this->jsonResponse(false, null, 'Gagal terhubung ke database: ' . $e->getMessage(), 400);
        }
    }
}
