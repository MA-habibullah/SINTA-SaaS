<?php

namespace App\Controllers;

use App\Config\Database;
use App\Core\SessionManager;
use PDO;

class AuthSiswaController extends BaseController {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Tampilkan halaman login khusus siswa
     * GET /siswa/login
     */
    public function loginView(): void {
        // Jika sudah login, langsung lempar ke dashboard
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            if (($_SESSION['role_name'] ?? '') === 'siswa') {
                header('Location: /SINTA-SaaS/dashboard');
                exit;
            }
        }

        // Ambil daftar sekolah secara dinamis untuk selector login lokal (simulasi subdomain)
        $db = Database::getConnection();
        $tenants = $db->query("SELECT subdomain, nama_sekolah, npsn FROM tenants WHERE status = 'active' AND deleted_at IS NULL ORDER BY nama_sekolah ASC")->fetchAll(PDO::FETCH_ASSOC);

        require_once __DIR__ . '/../../views/siswa_login_view.php';
    }

    /**
     * API: Autentikasi Siswa
     * POST /api/v1/siswa/login
     */
    public function loginApi(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed.'], 405);
        }

        $input = $this->getJsonInput();
        $nisn = isset($input['nisn']) ? trim($input['nisn']) : '';
        $password = isset($input['password']) ? $input['password'] : '';

        if (empty($nisn) || empty($password)) {
            $this->jsonResponse(['error' => 'NISN dan Password wajib diisi.'], 400);
        }

        try {
            $db = Database::getConnection();

            // 1. Cari siswa berdasarkan NISN (non-deleted) dan tenant_id jika terdeteksi
            if ($this->tenantId !== null) {
                $stmt = $db->prepare("SELECT * FROM siswa WHERE nisn = :nisn AND tenant_id = :tenant_id AND deleted_at IS NULL LIMIT 1");
                $stmt->execute([
                    'nisn' => $nisn,
                    'tenant_id' => $this->tenantId
                ]);
            } else {
                $stmt = $db->prepare("SELECT * FROM siswa WHERE nisn = :nisn AND deleted_at IS NULL LIMIT 1");
                $stmt->execute(['nisn' => $nisn]);
            }
            $siswa = $stmt->fetch(PDO::FETCH_ASSOC);

            $genericError = 'NISN atau Password yang Anda masukkan salah.';

            if (!$siswa) {
                $this->jsonResponse(['error' => $genericError], 401);
            }

            // 2. Keamanan Multi-Tenant: Cek status keaktifan tenant sekolah siswa
            $stmtTenant = $db->prepare("SELECT status, nama_sekolah FROM tenants WHERE id = :tenant_id AND deleted_at IS NULL LIMIT 1");
            $stmtTenant->execute(['tenant_id' => $siswa['tenant_id']]);
            $tenant = $stmtTenant->fetch(PDO::FETCH_ASSOC);

            if (!$tenant || $tenant['status'] !== 'active') {
                $this->jsonResponse(['error' => 'Akses sekolah Anda sedang ditangguhkan atau dinonaktifkan. Silakan hubungi operator sekolah.'], 403);
            }

            // 3. Verifikasi Password
            if (empty($siswa['password']) || !password_verify($password, (string)$siswa['password'])) {
                $this->jsonResponse(['error' => $genericError], 401);
            }

            // 4. Inisialisasi Sesi Siswa
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
            $_SESSION['is_first_login'] = (bool)$siswa['is_first_login'];
            $_SESSION['last_activity'] = time();

            // Audit Trail: Record LOGIN
            try {
                \App\Helpers\ActivityLogger::record('LOGIN', 'siswa', $siswa['id']);
            } catch (\Throwable $e) {}

            $this->jsonResponse([
                'success' => true,
                'message' => 'Login berhasil.',
                'is_first_login' => (bool)$siswa['is_first_login']
            ]);

        } catch (\Throwable $e) {
            error_log("Student authentication failed: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Terjadi kesalahan sistem saat memproses login.'], 500);
        }
    }

    /**
     * Tampilkan halaman wajib ubah password (First Login)
     * GET /siswa/ubah-password
     */
    public function changePasswordView(): void {
        SessionManager::start();

        // Pastikan yang mengakses adalah siswa yang terautentikasi
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || ($_SESSION['role_name'] ?? '') !== 'siswa') {
            header('Location: /SINTA-SaaS/login');
            exit;
        }

        // Jika dia bukan first login lagi, kembalikan ke dashboard
        if (!($_SESSION['is_first_login'] ?? false)) {
            header('Location: /SINTA-SaaS/dashboard');
            exit;
        }

        require_once __DIR__ . '/../../views/siswa_change_password_view.php';
    }

    /**
     * API: Ubah Password Wajib Siswa (First Login)
     * POST /api/v1/siswa/ubah-password
     */
    public function changePasswordApi(): void {
        SessionManager::start();

        // Pastikan terautentikasi sebagai siswa
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || ($_SESSION['role_name'] ?? '') !== 'siswa') {
            $this->jsonResponse(['error' => 'Unauthorized access.'], 401);
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed.'], 405);
        }

        $input = $this->getJsonInput();
        $passwordBaru = isset($input['password_baru']) ? $input['password_baru'] : '';
        $konfirmasiPassword = isset($input['konfirmasi_password']) ? $input['konfirmasi_password'] : '';

        // Validasi
        if (empty($passwordBaru) || empty($konfirmasiPassword)) {
            $this->jsonResponse(['error' => 'Password Baru dan Konfirmasi Password wajib diisi.'], 400);
        }

        if (strlen($passwordBaru) < 8) {
            $this->jsonResponse(['error' => 'Password baru minimal harus sepanjang 8 karakter.'], 422);
        }

        if ($passwordBaru !== $konfirmasiPassword) {
            $this->jsonResponse(['error' => 'Konfirmasi password tidak cocok.'], 422);
        }

        try {
            $db = Database::getConnection();
            $siswaId = $_SESSION['user_id'];

            // Hash password baru
            $hashedPassword = password_hash($passwordBaru, PASSWORD_BCRYPT);

            // Update password dan is_first_login di DB
            $stmt = $db->prepare("UPDATE siswa SET password = :password, is_first_login = 0, updated_at = NOW() WHERE id = :id");
            $stmt->execute([
                'password' => $hashedPassword,
                'id' => $siswaId
            ]);

            // Update status di session
            $_SESSION['is_first_login'] = false;

            $this->jsonResponse([
                'success' => true,
                'message' => 'Password berhasil diperbarui. Silakan masuk ke Dashboard.'
            ]);

        } catch (\Throwable $e) {
            error_log("Failed to change student password: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Terjadi kesalahan sistem saat memperbarui password.'], 500);
        }
    }

    /**
     * Logout Siswa
     * GET /siswa/logout
     */
    public function logout(): void {
        // Audit Trail: Record LOGOUT before session is destroyed
        try {
            $userId = $_SESSION['user_id'] ?? 'unknown';
            if ($userId !== 'unknown') {
                \App\Helpers\ActivityLogger::record('LOGOUT', 'siswa', $userId);
            }
        } catch (\Throwable $e) {}

        SessionManager::logout();
        header('Location: /SINTA-SaaS/login');
        exit;
    }
}
