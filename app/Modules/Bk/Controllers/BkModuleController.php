<?php

namespace App\Modules\Bk\Controllers;

use App\Core\BaseController;
use App\Core\SessionManager;
use App\Core\RouteGuard;

class BkModuleController extends BaseController {

    private const ALLOWED_ROLES = ['super_admin', 'operator_sekolah', 'guru_bk'];

    public function __construct() {
        parent::__construct();
        SessionManager::requireLogin();
        $this->guardRole();
    }

    private function guardRole(): void {
        if (!RouteGuard::checkCurrent(self::ALLOWED_ROLES)) {
            $isApi = str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/');
            if ($isApi) {
                $this->jsonResponse(false, null, 'Akses ditolak. Modul ini hanya untuk Guru BK, Admin Sekolah, dan Super Admin.', 403);
            }
            http_response_code(403);
            echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>403 Akses Ditolak</title>'
               . '<link href="assets/css/bootstrap.min.css" rel="stylesheet">'
               . '<link href="assets/css/bootstrap-icons.css" rel="stylesheet">'
               . '</head><body class="bg-light d-flex align-items-center justify-content-center" style="min-height:100vh;">'
               . '<div class="card shadow-sm p-5 text-center" style="max-width:480px;">'
               . '<i class="bi bi-shield-x text-danger fs-1 mb-3 d-block"></i>'
               . '<h4 class="fw-bold mb-2">403 — Akses Ditolak</h4>'
               . '<p class="text-muted">Modul Bimbingan Konseling hanya dapat diakses oleh <strong>Guru BK</strong>, <strong>Admin Sekolah</strong>, atau <strong>Super Admin</strong>.</p>'
               . '<a href="dashboard" class="btn btn-primary mt-2 rounded-3">Kembali ke Dashboard</a>'
               . '</div></body></html>';
            exit;
        }
    }

    /**
     * GET /bk
     */
    public function indexView(): void {
        require_once __DIR__ . '/../../../../views/bk/pdss_simulasi_ui.php';
    }
}
