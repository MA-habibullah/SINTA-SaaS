<?php

namespace App\Modules\Kesiswaan\Controllers;

use App\Core\BaseController;
use App\Core\SessionManager;
use App\Core\RouteGuard;
use App\Modules\Kesiswaan\Models\EkskulModel;
use App\Modules\Kesiswaan\Models\PrestasiSiswaModel;

class KesiswaanModuleController extends BaseController {

    private const ALLOWED_ROLES = ['super_admin', 'operator_sekolah', 'guru_pembina', 'guru_bk', 'kepala_sekolah', 'kesiswaan'];

    public function __construct() {
        parent::__construct();
        SessionManager::requireLogin();
        $this->guardRole();
    }

    private function guardRole(): void {
        if (!RouteGuard::checkCurrent(self::ALLOWED_ROLES)) {
            $isApi = str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/');
            if ($isApi) {
                $this->jsonResponse(false, null, 'Role tidak memiliki akses ke modul Kesiswaan.', 403);
            }
            $this->redirect('/dashboard?error=forbidden');
        }
    }

    /**
     * GET /kesiswaan/ekskul
     */
    public function index(): void {
        $this->ekskulIndex();
    }

    public function ekskulIndex(): void {
        require_once __DIR__ . '/../../../../views/kesiswaan/kesiswaan_ekskul.php';
    }

    /**
     * API: Ambil daftar ekskul aktif
     * GET /api/v1/kesiswaan/ekskul
     */
    public function getEkskulApi(): void {
        if (!$this->tenantId) {
            $this->jsonResponse(false, null, 'Tenant ID tidak terdeteksi', 400);
            return;
        }

        $ekskulList = EkskulModel::getActiveEkskul($this->tenantId);
        $this->jsonResponse(true, $ekskulList);
    }

    /**
     * API: Ambil prestasi siswa
     * GET /api/v1/kesiswaan/prestasi
     */
    public function getPrestasiApi(): void {
        if (!$this->tenantId) {
            $this->jsonResponse(false, null, 'Tenant ID tidak terdeteksi', 400);
            return;
        }

        $siswaId = $_GET['siswa_id'] ?? '';
        $prestasiList = PrestasiSiswaModel::getPrestasiBySiswa($this->tenantId, $siswaId);
        $this->jsonResponse(true, $prestasiList);
    }
}
