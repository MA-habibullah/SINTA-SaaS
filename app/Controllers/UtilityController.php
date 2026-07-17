<?php

namespace App\Controllers;

use App\Core\SessionManager;

class UtilityController extends BaseController {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Render Halaman Pemindai Dokumen (AeroScan)
     */
    public function documentScanner(): void {
        SessionManager::start();
        if (!isset($_SESSION['user_id'])) {
            header('Location: /SINTA-SaaS/login');
            exit;
        }

        $data = [
            'title' => 'Pemindai & Kompresor Dokumen (AeroScan)',
            'user_nama' => $_SESSION['nama_lengkap'] ?? '',
            'user_role' => $_SESSION['role_name'] ?? ''
        ];

        $this->render('utility/document_scanner', $data);
    }
}
