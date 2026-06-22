<?php

namespace App\Controllers;

use App\Core\SessionManager;
use PDO;

class KurikulumController extends BaseController {

    public function __construct() {
        parent::__construct();
        SessionManager::requireLogin();
        
        // Ensure the role is allowed
        $roles = $_SESSION['roles'] ?? [$_SESSION['role_name'] ?? ''];
        $allowed = array_intersect($roles, ['super_admin', 'operator_sekolah', 'guru']);
        if (empty($allowed)) {
            http_response_code(403);
            die("<h1>403 Forbidden</h1><p>Anda tidak memiliki akses ke fitur Seting Kurikulum.</p>");
        }
    }

    /**
     * GET /api/v1/kurikulum
     * Fetch master options (Tahun Ajaran, Kelas, Bank Mapel) and existing mapped data if parameters are provided.
     */
    public function index(): void {
        $db = \App\Config\Database::getConnection();
        
        // Resolve tenant_id
        $tenantId = SessionManager::getTenantId();
        if (!$tenantId && !empty($_GET['tenant_id'])) {
            $tenantId = $_GET['tenant_id'];
        }

        if (!$tenantId) {
            $this->jsonResponse([
                'tahun_ajaran' => [],
                'kelas' => [],
                'bank_mapel' => [],
                'existing_mapping' => []
            ]);
            return;
        }

        // 1. Get list of Tahun Ajaran
        $qTahun = "SELECT id, tahun_ajaran FROM tahun_ajaran WHERE is_active = 1";
        if ($tenantId) {
            $qTahun .= " AND tenant_id = :tenant_id";
        }
        $qTahun .= " AND deleted_at IS NULL ORDER BY tahun_ajaran DESC";
        $stmtTahun = $db->prepare($qTahun);
        if ($tenantId) {
            $stmtTahun->execute(['tenant_id' => $tenantId]);
        } else {
            $stmtTahun->execute();
        }
        $tahunList = $stmtTahun->fetchAll(PDO::FETCH_ASSOC);

        // 2. Get list of Kelas
        $qKelas = "SELECT id, nama_kelas FROM kelas WHERE is_active = 1";
        if ($tenantId) {
            $qKelas .= " AND tenant_id = :tenant_id";
        }
        $qKelas .= " AND deleted_at IS NULL ORDER BY nama_kelas ASC";
        $stmtKelas = $db->prepare($qKelas);
        if ($tenantId) {
            $stmtKelas->execute(['tenant_id' => $tenantId]);
        } else {
            $stmtKelas->execute();
        }
        $kelasList = $stmtKelas->fetchAll(PDO::FETCH_ASSOC);

        // 3. Get list of Bank Mapel (Mata Pelajaran)
        $qMapel = "SELECT id, kode_mapel, nama_mapel FROM mata_pelajaran WHERE is_active = 1";
        if ($tenantId) {
            $qMapel .= " AND tenant_id = :tenant_id";
        }
        $qMapel .= " AND deleted_at IS NULL ORDER BY nama_mapel ASC";
        $stmtMapel = $db->prepare($qMapel);
        if ($tenantId) {
            $stmtMapel->execute(['tenant_id' => $tenantId]);
        } else {
            $stmtMapel->execute();
        }
        $mapelList = $stmtMapel->fetchAll(PDO::FETCH_ASSOC);

        // 4. Fetch existing mapping if filters are provided
        $existingMapping = [];
        $kelasId = $_GET['kelas_id'] ?? '';
        $tahunAjaran = $_GET['tahun_ajaran'] ?? '';
        $semester = $_GET['semester'] ?? '';

        if (!empty($kelasId) && !empty($tahunAjaran) && !empty($semester)) {
            $qExist = "SELECT kelompok_id, mapel_id FROM pemetaan_mapel WHERE kelas_id = :kelas_id AND tahun_ajaran = :tahun_ajaran AND semester = :semester";
            if ($tenantId) {
                $qExist .= " AND tenant_id = :tenant_id";
            }
            $qExist .= " AND deleted_at IS NULL";
            $stmtExist = $db->prepare($qExist);
            $params = [
                'kelas_id' => $kelasId,
                'tahun_ajaran' => $tahunAjaran,
                'semester' => $semester
            ];
            if ($tenantId) {
                $params['tenant_id'] = $tenantId;
            }
            $stmtExist->execute($params);
            $existingMapping = $stmtExist->fetchAll(PDO::FETCH_ASSOC);
        }

        $this->jsonResponse([
            'tahun_ajaran' => $tahunList,
            'kelas' => $kelasList,
            'bank_mapel' => $mapelList,
            'existing_mapping' => $existingMapping
        ]);
    }

    /**
     * POST /api/v1/kurikulum
     * Save/Sync curriculum mapping for a selected class, year, and semester.
     */
    public function store(): void {
        $input = $this->getJsonInput();
        
        $kelasId = $input['kelas_id'] ?? '';
        $tahunAjaran = $input['tahun_ajaran'] ?? '';
        $semester = $input['semester'] ?? '';
        $mappings = $input['mappings'] ?? []; // Expected format: [ ['kelompok_id' => 'Group A', 'mapel_ids' => [1, 2]], ... ]

        if (empty($kelasId) || empty($tahunAjaran) || empty($semester)) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Parameter kelas_id, tahun_ajaran, dan semester wajib diisi.'], 400);
            return;
        }

        // Resolve tenant_id
        $db = \App\Config\Database::getConnection();
        $tenantId = SessionManager::getTenantId();
        if (!$tenantId && !empty($input['tenant_id'])) {
            $tenantId = $input['tenant_id'];
        }
        if (!$tenantId && !empty($kelasId)) {
            $stmtKelasTenant = $db->prepare("SELECT tenant_id FROM kelas WHERE id = :kelas_id LIMIT 1");
            $stmtKelasTenant->execute(['kelas_id' => $kelasId]);
            $tenantId = $stmtKelasTenant->fetchColumn() ?: null;
        }

        if (!$tenantId) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Tenant ID tidak terdeteksi.'], 400);
            return;
        }
        $db->beginTransaction();
        try {
            // 1. Delete existing mapping for the target scope
            $stmtDelete = $db->prepare("DELETE FROM pemetaan_mapel WHERE kelas_id = :kelas_id AND semester = :semester AND tahun_ajaran = :tahun_ajaran AND tenant_id = :tenant_id");
            $stmtDelete->execute([
                'kelas_id' => $kelasId,
                'semester' => $semester,
                'tahun_ajaran' => $tahunAjaran,
                'tenant_id' => $tenantId
            ]);

            // 2. Insert new mappings
            $stmtInsert = $db->prepare("INSERT INTO pemetaan_mapel (tenant_id, tahun_ajaran, semester, kelas_id, kelompok_id, mapel_id) VALUES (:tenant_id, :tahun_ajaran, :semester, :kelas_id, :kelompok_id, :mapel_id)");
            foreach ($mappings as $group) {
                $kelompokId = trim($group['kelompok_id'] ?? '');
                $mapelIds = $group['mapel_ids'] ?? [];
                if (empty($kelompokId) || empty($mapelIds)) {
                    continue;
                }
                foreach ($mapelIds as $mapelId) {
                    $stmtInsert->execute([
                        'tenant_id' => $tenantId,
                        'tahun_ajaran' => $tahunAjaran,
                        'semester' => $semester,
                        'kelas_id' => $kelasId,
                        'kelompok_id' => $kelompokId,
                        'mapel_id' => $mapelId
                    ]);
                }
            }

            $db->commit();
            $this->jsonResponse(['status' => 'success', 'message' => 'Kurikulum kelas berhasil disimpan.']);
        } catch (\Throwable $e) {
            $db->rollBack();
            $this->jsonResponse(['status' => 'error', 'message' => 'Gagal menyimpan kurikulum: ' . $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/v1/kurikulum/copy
     * Copy curriculum mapping configuration from a source class to a target class.
     */
    public function copyCurriculum(): void {
        $input = $this->getJsonInput();

        $sourceKelasId = $input['source_kelas_id'] ?? '';
        $targetKelasId = $input['target_kelas_id'] ?? '';
        $tahunAjaran = $input['tahun_ajaran'] ?? '';
        $semester = $input['semester'] ?? '';

        if (empty($sourceKelasId) || empty($targetKelasId) || empty($tahunAjaran) || empty($semester)) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Parameter source_kelas_id, target_kelas_id, tahun_ajaran, dan semester wajib diisi.'], 400);
            return;
        }

        if ($sourceKelasId == $targetKelasId) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Kelas sumber dan kelas tujuan tidak boleh sama.'], 400);
            return;
        }

        // Resolve tenant_id
        $db = \App\Config\Database::getConnection();
        $tenantId = SessionManager::getTenantId();
        if (!$tenantId && !empty($input['tenant_id'])) {
            $tenantId = $input['tenant_id'];
        }
        if (!$tenantId && !empty($targetKelasId)) {
            $stmtKelasTenant = $db->prepare("SELECT tenant_id FROM kelas WHERE id = :kelas_id LIMIT 1");
            $stmtKelasTenant->execute(['kelas_id' => $targetKelasId]);
            $tenantId = $stmtKelasTenant->fetchColumn() ?: null;
        }

        if (!$tenantId) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Tenant ID tidak terdeteksi.'], 400);
            return;
        }
        $db->beginTransaction();
        try {
            // 1. Fetch source mappings
            $stmtSource = $db->prepare("SELECT kelompok_id, mapel_id FROM pemetaan_mapel WHERE kelas_id = :source_kelas_id AND semester = :semester AND tahun_ajaran = :tahun_ajaran AND tenant_id = :tenant_id AND deleted_at IS NULL");
            $stmtSource->execute([
                'source_kelas_id' => $sourceKelasId,
                'semester' => $semester,
                'tahun_ajaran' => $tahunAjaran,
                'tenant_id' => $tenantId
            ]);
            $sourceMappings = $stmtSource->fetchAll(PDO::FETCH_ASSOC);

            if (empty($sourceMappings)) {
                $db->rollBack();
                $this->jsonResponse(['status' => 'error', 'message' => 'Kelas sumber tidak memiliki pemetaan kurikulum pada semester & tahun ajaran ini.'], 404);
                return;
            }

            // 2. Delete existing target mappings
            $stmtDelete = $db->prepare("DELETE FROM pemetaan_mapel WHERE kelas_id = :target_kelas_id AND semester = :semester AND tahun_ajaran = :tahun_ajaran AND tenant_id = :tenant_id");
            $stmtDelete->execute([
                'target_kelas_id' => $targetKelasId,
                'semester' => $semester,
                'tahun_ajaran' => $tahunAjaran,
                'tenant_id' => $tenantId
            ]);

            // 3. Insert copied mappings
            $stmtInsert = $db->prepare("INSERT INTO pemetaan_mapel (tenant_id, tahun_ajaran, semester, kelas_id, kelompok_id, mapel_id) VALUES (:tenant_id, :tahun_ajaran, :semester, :kelas_id, :kelompok_id, :mapel_id)");
            foreach ($sourceMappings as $row) {
                $stmtInsert->execute([
                    'tenant_id' => $tenantId,
                    'tahun_ajaran' => $tahunAjaran,
                    'semester' => $semester,
                    'kelas_id' => $targetKelasId,
                    'kelompok_id' => $row['kelompok_id'],
                    'mapel_id' => $row['mapel_id']
                ]);
            }

            $db->commit();
            $this->jsonResponse(['status' => 'success', 'message' => 'Kurikulum berhasil disalin ke kelas tujuan.']);
        } catch (\Throwable $e) {
            $db->rollBack();
            $this->jsonResponse(['status' => 'error', 'message' => 'Gagal menyalin kurikulum: ' . $e->getMessage()], 500);
        }
    }
}
