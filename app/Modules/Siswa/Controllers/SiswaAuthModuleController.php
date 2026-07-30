<?php

namespace App\Modules\Siswa\Controllers;

use App\Core\BaseController;
use App\Config\Database;
use App\Core\SessionManager;
use PDO;

class SiswaAuthModuleController extends BaseController {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Tampilkan halaman login khusus siswa
     * GET /login
     */
    public function loginView(): void {
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            if (($_SESSION['role_name'] ?? '') === 'siswa') {
                $this->redirect('/dashboard');
            }
        }

        try {
            $db = Database::getConnection();
            $tenants = $db->query("SELECT subdomain, nama_sekolah, npsn FROM core.tenants WHERE status = 'active' ORDER BY nama_sekolah ASC")->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $tenants = [];
        }

        require_once __DIR__ . '/../../../../views/siswa_login_view.php';
    }

    /**
     * API: Autentikasi Siswa
     * POST /api/v1/siswa/login
     */
    public function loginApi(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(false, null, 'Method not allowed.', 405);
        }

        $input = $this->getJsonInput();
        $nisn = isset($input['nisn']) ? trim($input['nisn']) : '';
        $password = isset($input['password']) ? $input['password'] : '';

        if (empty($nisn) || empty($password)) {
            $this->jsonResponse(false, null, 'NISN dan Password wajib diisi.', 400);
        }

        try {
            $db = Database::getConnection();

            if ($this->tenantId !== null) {
                $stmt = $db->prepare("SELECT * FROM siswa.siswa WHERE nisn = :nisn AND tenant_id = :tenant_id LIMIT 1");
                $stmt->execute([
                    'nisn' => $nisn,
                    'tenant_id' => $this->tenantId
                ]);
            } else {
                $stmt = $db->prepare("SELECT * FROM siswa.siswa WHERE nisn = :nisn LIMIT 1");
                $stmt->execute(['nisn' => $nisn]);
            }
            $siswa = $stmt->fetch(PDO::FETCH_ASSOC);

            $genericError = 'NISN atau Password yang Anda masukkan salah.';

            if (!$siswa) {
                $this->jsonResponse(false, null, $genericError, 200);
            }

            $stmtTenant = $db->prepare("SELECT status, nama_sekolah FROM core.tenants WHERE id = :tenant_id LIMIT 1");
            $stmtTenant->execute(['tenant_id' => $siswa['tenant_id']]);
            $tenant = $stmtTenant->fetch(PDO::FETCH_ASSOC);

            if (!$tenant || $tenant['status'] !== 'active') {
                $this->jsonResponse(false, null, 'Akses sekolah Anda sedang ditangguhkan atau dinonaktifkan. Silakan hubungi operator sekolah.', 200);
            }

            if (empty($siswa['password']) || !password_verify($password, (string)$siswa['password'])) {
                $this->jsonResponse(false, null, $genericError, 200);
            }

            SessionManager::start();
            session_regenerate_id(true);

            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = $siswa['id'];
            $_SESSION['tenant_id'] = $siswa['tenant_id'];
            $_SESSION['nama_sekolah'] = $tenant['nama_sekolah'];
            $_SESSION['role_name'] = 'siswa';
            $_SESSION['roles'] = ['siswa'];
            $_SESSION['nama_lengkap'] = $siswa['nama_lengkap'];
            $_SESSION['nisn'] = $siswa['nisn'];
            $_SESSION['is_first_login'] = (bool)($siswa['is_first_login'] ?? false);

            $redirectUrl = $this->getBaseUrl() . ($_SESSION['is_first_login'] ? '/siswa/ubah-password' : '/dashboard');

            $this->jsonResponse(true, [
                'message' => 'Login siswa berhasil.',
                'is_first_login' => $_SESSION['is_first_login'],
                'redirect' => $redirectUrl
            ]);

        } catch (\Throwable $e) {
            $this->jsonResponse(false, null, 'Gagal autentikasi siswa: ' . $e->getMessage(), 400);
        }
    }
}
