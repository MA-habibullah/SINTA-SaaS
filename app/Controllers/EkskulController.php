<?php

namespace App\Controllers;

use App\Core\SessionManager;
use PDO;

/**
 * EkskulController
 * 
 * Menangani Modul Kesiswaan - Ekstrakurikuler:
 * - Master Data Ekskul
 * - Kelola Anggota Ekskul
 * - Jurnal & Kegiatan
 * - Presensi & Penilaian
 */
class EkskulController extends BaseController {

    private const ALLOWED_ROLES = ['super_admin', 'operator_sekolah', 'guru_pembina', 'guru_bk', 'kepala_sekolah'];

    public function __construct() {
        parent::__construct();
        SessionManager::requireLogin();
        $this->guardRole();
    }

    private function guardRole(): void {
        $roles = $_SESSION['roles'] ?? [$_SESSION['role_name'] ?? ''];
        $hasAllowedRole = false;
        foreach ($roles as $r) {
            if (in_array($r, self::ALLOWED_ROLES, true)) {
                $hasAllowedRole = true;
                break;
            }
        }
        if (!$hasAllowedRole) {
            $isApi = str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/SINTA-SaaS/api/');
            if ($isApi) {
                http_response_code(403);
                echo json_encode(['status' => 'error', 'message' => 'Role tidak memiliki akses ke modul Ekskul.']);
                exit;
            } else {
                header("Location: /SINTA-SaaS/dashboard?error=forbidden");
                exit;
            }
        }
    }

    /**
     * Menampilkan halaman utama modul Ekskul
     */
    public function index() {
        $tenant_id = $_SESSION['tenant_id'];

        // Get Master Ekskul
        $stmt = $this->db->prepare("
            SELECT e.*, u.nama_lengkap as nama_pembina 
            FROM master_ekskul e
            LEFT JOIN users u ON e.pembina_id = u.id
            WHERE e.tenant_id = ? AND e.deleted_at IS NULL
        ");
        $stmt->execute([$tenant_id]);
        $master_ekskul = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Render View
        $this->view('kesiswaan_ekskul', [
            'master_ekskul' => $master_ekskul
        ]);
    }
}
