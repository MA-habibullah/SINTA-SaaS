<?php

namespace App\Controllers;

use App\Core\BaseController;

class DashboardController extends BaseController
{
    public function index()
    {
        // Require session mapping or just simple logic to load the view
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Cek login untuk keamanan halaman dashboard
        if (!isset($_SESSION['user_id'])) {
            header('Location: /sinta/login');
            exit;
        }

        // Pastikan variabel user untuk template header.php dll ada jika diperlukan
        $data = [
            'title' => 'Dashboard SINTA-SaaS',
            'user' => [
                'nama' => $_SESSION['user_name'] ?? 'Pengguna',
                'role' => $_SESSION['role_name'] ?? 'GUEST'
            ]
        ];

        require_once __DIR__ . '/../../views/dashboard_view.php';
    }
}
