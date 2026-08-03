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

        require_once __DIR__ . '/../../../../views/siswa/siswa_login_view.php';
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

            $query = "
                SELECT s.id, s.tenant_id, s.nisn, s.nis, s.nama_lengkap,
                       s.password, s.is_first_login, s.is_active, s.status_siswa,
                       t.nama_sekolah, t.status AS tenant_status
                FROM siswa.siswa s
                JOIN core.tenants t ON t.id = s.tenant_id
                WHERE s.nisn = :nisn
            ";

            $params = ['nisn' => $nisn];

            if ($this->tenantId !== null) {
                $query .= " AND s.tenant_id = :tenant_id";
                $params['tenant_id'] = $this->tenantId;
            }

            $query .= " LIMIT 1";

            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $siswa = $stmt->fetch(PDO::FETCH_ASSOC);

            $genericError = 'NISN atau Password yang Anda masukkan salah.';

            if (!$siswa) {
                $this->jsonResponse(false, null, $genericError, 200);
            }

            if (!$siswa['is_active'] || $siswa['status_siswa'] !== 'aktif') {
                $this->jsonResponse(false, null, 'Akun siswa tidak aktif atau telah lulus/pindah.', 200);
            }

            if ($siswa['tenant_status'] !== 'active') {
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
            $_SESSION['nama_sekolah'] = $siswa['nama_sekolah'];
            $_SESSION['role_name'] = 'siswa';
            $_SESSION['roles'] = ['siswa'];
            $_SESSION['nama_lengkap'] = $siswa['nama_lengkap'];
            $_SESSION['nisn'] = $siswa['nisn'];
            $_SESSION['is_first_login'] = (bool)($siswa['is_first_login'] ?? false);

            \App\Helpers\ActivityLogger::log(
                'LOGIN', 
                'akademik.siswa', 
                null, 
                ['nisn' => $siswa['nisn'], 'nama_lengkap' => $siswa['nama_lengkap'], 'role' => 'siswa'],
                $siswa['tenant_id'],
                $siswa['id']
            );

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

    /**
     * Tampilkan halaman wajib ubah password (First Login)
     * GET /siswa/ubah-password
     */
    public function changePasswordView(): void {
        SessionManager::start();

        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || ($_SESSION['role_name'] ?? '') !== 'siswa') {
            $this->redirect('/login');
            return;
        }

        if (!($_SESSION['is_first_login'] ?? false)) {
            $this->redirect('/dashboard');
            return;
        }

        require_once __DIR__ . '/../../../../views/siswa/siswa_change_password_view.php';
    }

    /**
     * API: Ubah Password Wajib Siswa (First Login)
     * POST /api/v1/siswa/ubah-password
     */
    public function changePasswordApi(): void {
        SessionManager::start();

        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || ($_SESSION['role_name'] ?? '') !== 'siswa') {
            $this->jsonResponse(false, null, 'Unauthorized access.', 401);
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(false, null, 'Method not allowed.', 405);
        }

        $input = $this->getJsonInput();
        $passwordBaru = isset($input['password_baru']) ? $input['password_baru'] : '';
        $konfirmasiPassword = isset($input['konfirmasi_password']) ? $input['konfirmasi_password'] : '';

        if (empty($passwordBaru) || empty($konfirmasiPassword)) {
            $this->jsonResponse(false, null, 'Password Baru dan Konfirmasi Password wajib diisi.', 400);
        }

        if (strlen($passwordBaru) < 8) {
            $this->jsonResponse(false, null, 'Password baru minimal harus sepanjang 8 karakter.', 422);
        }

        if ($passwordBaru !== $konfirmasiPassword) {
            $this->jsonResponse(false, null, 'Konfirmasi password tidak cocok.', 422);
        }

        try {
            $db = Database::getConnection();
            $siswaId = $_SESSION['user_id'];

            $hashedPassword = password_hash($passwordBaru, PASSWORD_BCRYPT);

            $stmt = $db->prepare("UPDATE siswa.siswa SET password = :password, is_first_login = false, updated_at = NOW() WHERE id = :id");
            $stmt->execute([
                'password' => $hashedPassword,
                'id' => $siswaId
            ]);

            $_SESSION['is_first_login'] = false;

            $this->jsonResponse(true, [
                'message' => 'Password berhasil diperbarui. Silakan masuk ke Dashboard.'
            ]);

        } catch (\Throwable $e) {
            error_log("Failed to change student password: " . $e->getMessage());
            $this->jsonResponse(false, null, 'Terjadi kesalahan sistem saat memperbarui password.', 500);
        }
    }

    /**
     * Logout Siswa
     * GET /siswa/logout
     */
    public function logout(): void {
        try {
            $userId = $_SESSION['user_id'] ?? 'unknown';
            if ($userId !== 'unknown') {
                \App\Helpers\ActivityLogger::record('LOGOUT', 'siswa', $userId);
            }
        } catch (\Throwable $e) {}

        SessionManager::logout();
        $this->redirect('/login');
    }
}

