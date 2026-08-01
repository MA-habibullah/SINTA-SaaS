<?php

namespace App\Modules\Sistem\Controllers;

use App\Core\BaseController;
use App\Core\SessionManager;

class DocumentScannerModuleController extends BaseController {

    public function __construct() {
        parent::__construct();
        SessionManager::requireLogin();
    }

    public function index(): void {
        $data = [
            'title'     => 'Pemindai & Pengenalan Teks Berkas (OCR Document Scanner)',
            'user_nama' => $_SESSION['nama_lengkap'] ?? 'User',
            'user_role' => $_SESSION['role_name'] ?? '',
        ];
        
        $this->render('utility/document_scanner', $data);
    }
}
