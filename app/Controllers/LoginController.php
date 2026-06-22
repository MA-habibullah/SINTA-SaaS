<?php

namespace App\Controllers;

use App\Models\User;
use App\Core\SessionManager;

class LoginController extends BaseController {

    /**
     * Handle Login Request (API Endpoint)
     * POST /api/v1/auth/login
     */
    public function login(): void {
        // Hanya izinkan request POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed'], 405);
        }

        $input = $this->getJsonInput();
        $email = isset($input['email']) ? trim($input['email']) : '';
        $password = isset($input['password']) ? $input['password'] : '';

        // Validasi input minimal
        if (empty($email) || empty($password)) {
            $this->jsonResponse(['error' => 'Email dan password wajib diisi'], 400);
        }

        // Cari user berdasarkan email dan konteks tenant (subdomain sekolah) saat ini
        // BaseController otomatis mendeteksi $this->tenantId
        $userModel = new User($this->tenantId);
        $user = $userModel->findByEmailAndTenant($email, $this->tenantId);

        // Secure by Design: Gunakan pesan error umum dan cegah kebocoran status username
        // Jangan beri tahu apakah email salah atau password salah secara spesifik (cegah User Enumeration)
        $genericErrorMessage = 'Email atau password yang Anda masukkan salah.';

        if (!$user) {
            $this->jsonResponse(['error' => $genericErrorMessage], 401);
        }

        // Verifikasi status keaktifan user
        if ($user['status'] !== 'active') {
            $this->jsonResponse(['error' => 'Akun Anda ditangguhkan. Silakan hubungi admin sekolah.'], 403);
        }

        // Verifikasi status keaktifan tenant sekolah
        if ($user['tenant_id'] !== null) {
            try {
                $db = \App\Config\Database::getConnection();
                $stmt = $db->prepare("SELECT status FROM tenants WHERE id = ? AND deleted_at IS NULL");
                $stmt->execute([$user['tenant_id']]);
                $tenantStatus = $stmt->fetchColumn();
                if ($tenantStatus !== 'active') {
                    $this->jsonResponse(['error' => 'Akses sekolah ditangguhkan atau dinonaktifkan. Hubungi Administrator.'], 403);
                }
            } catch (\Throwable $e) {
                // Fail-safe
            }
        }

        // Verifikasi password hash menggunakan password_verify
        if (!password_verify($password, $user['password'])) {
            $this->jsonResponse(['error' => $genericErrorMessage], 401);
        }

        // Password cocok! Inisialisasi session aman
        SessionManager::login($user);

        // Kirim respon sukses beserta data profil ringkas (tanpa password)
        $this->jsonResponse([
            'success' => true,
            'message' => 'Login berhasil',
            'user' => [
                'id' => $user['id'],
                'nama_lengkap' => $user['nama_lengkap'],
                'email' => $user['email'],
                'role' => $user['nama_role'],
                'tenant_id' => $user['tenant_id']
            ]
        ]);
    }

    /**
     * Handle Logout Request
     */
    public function logout(): void {
        SessionManager::logout();
        // Redirect langsung ke halaman login setelah menghancurkan session
        header('Location: /SINTA-SaaS/login');
        exit;
    }

    /**
     * Check if user is currently authenticated
     * GET /api/v1/auth/check
     */
    public function checkAuth(): void {
        if (SessionManager::isLoggedIn()) {
            // Sesi aktif, kembalikan data sesi dasar
            $this->jsonResponse([
                'authenticated' => true,
                'user' => [
                    'id' => $_SESSION['user_id'],
                    'nama_lengkap' => $_SESSION['nama_lengkap'],
                    'email' => $_SESSION['email'],
                    'role' => $_SESSION['role_name'],
                    'tenant_id' => $_SESSION['tenant_id']
                ]
            ]);
        } else {
            $this->jsonResponse(['authenticated' => false], 200);
        }
    }
}
