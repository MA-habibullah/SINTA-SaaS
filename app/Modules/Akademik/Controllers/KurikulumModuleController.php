<?php

namespace App\Modules\Akademik\Controllers;

use App\Core\BaseController;
use App\Core\SessionManager;
use App\Config\Database;
use PDO;

class KurikulumModuleController extends BaseController {

    public function __construct() {
        parent::__construct();
        SessionManager::requireLogin();
        
        $roles = $_SESSION['roles'] ?? [$_SESSION['role_name'] ?? ''];
        $allowed = array_intersect($roles, ['super_admin', 'operator_sekolah', 'guru']);
        if (empty($allowed)) {
            $this->jsonResponse(false, null, 'Anda tidak memiliki akses ke fitur ini.', 403);
        }
    }

    /**
     * GET /api/v1/kurikulum
     */
    public function index(): void {
        $db = Database::getConnection();
        
        $tenantId = SessionManager::getTenantId();
        if ((!$tenantId || $tenantId === '00000000-0000-0000-0000-000000000000') && !empty($_GET['tenant_id'])) {
            $tenantId = $_GET['tenant_id'];
        }
        if ($tenantId === '00000000-0000-0000-0000-000000000000') {
            $tenantId = null;
        }

        $sqlTahun = "SELECT id, nama_tahun_ajaran AS tahun_ajaran, kategori AS status FROM akademik.tahun_ajaran WHERE is_active = true";
        if ($tenantId) {
            $sqlTahun .= " AND tenant_id = :tenant_id";
        }
        $sqlTahun .= " ORDER BY nama_tahun_ajaran DESC";
        $stmtTahun = $db->prepare($sqlTahun);
        if ($tenantId) { $stmtTahun->execute(['tenant_id' => $tenantId]); } else { $stmtTahun->execute(); }
        $tahunList = $stmtTahun->fetchAll(PDO::FETCH_ASSOC);

        $sqlKelas = "SELECT id, nama_kelas, id_jenjang, id_jurusan, kode_kelas FROM akademik.kelas WHERE is_active = true";
        if ($tenantId) {
            $sqlKelas .= " AND tenant_id = :tenant_id";
        }
        $sqlKelas .= " ORDER BY nama_kelas ASC";
        $stmtKelas = $db->prepare($sqlKelas);
        if ($tenantId) { $stmtKelas->execute(['tenant_id' => $tenantId]); } else { $stmtKelas->execute(); }
        $kelasList = $stmtKelas->fetchAll(PDO::FETCH_ASSOC);

        $sqlJenjang = "SELECT id, nama_jenjang FROM core.jenjang WHERE is_active = true";
        if ($tenantId) {
            $sqlJenjang .= " AND (tenant_id = :tenant_id OR tenant_id IS NULL)";
        }
        $sqlJenjang .= " ORDER BY nama_jenjang ASC";
        $stmtJenjang = $db->prepare($sqlJenjang);
        if ($tenantId) { $stmtJenjang->execute(['tenant_id' => $tenantId]); } else { $stmtJenjang->execute(); }
        $jenjangList = $stmtJenjang->fetchAll(PDO::FETCH_ASSOC);

        $sqlMapel = "SELECT id, id::text AS kode_mapel, nama_mata_pelajaran AS nama_mapel FROM akademik.mata_pelajaran WHERE is_active = true";
        if ($tenantId) {
            $sqlMapel .= " AND tenant_id = :tenant_id";
        }
        $sqlMapel .= " ORDER BY nama_mata_pelajaran ASC";
        $stmtMapel = $db->prepare($sqlMapel);
        if ($tenantId) { $stmtMapel->execute(['tenant_id' => $tenantId]); } else { $stmtMapel->execute(); }
        $mapelList = $stmtMapel->fetchAll(PDO::FETCH_ASSOC);

        $existingMapping = [];
        $kelasId = $_GET['kelas_id'] ?? '';
        $tahunAjaran = $_GET['tahun_ajaran'] ?? '';
        $semester = $_GET['semester'] ?? '';
        $activeKurikulumId = '';

        if (!empty($kelasId) && !empty($tahunAjaran) && !empty($semester)) {
            $stmtExist = $db->prepare("SELECT kelompok_id, mapel_id FROM akademik.pemetaan_mapel WHERE kelas_id = :kelas_id AND tahun_ajaran = :tahun_ajaran AND semester = :semester AND tenant_id = :tenant_id AND is_active = true");
            $stmtExist->execute([
                'kelas_id' => $kelasId,
                'tahun_ajaran' => $tahunAjaran,
                'semester' => $semester,
                'tenant_id' => $tenantId
            ]);
            $existingMapping = $stmtExist->fetchAll(PDO::FETCH_ASSOC);

            $stmtActive = $db->prepare("SELECT kurikulum_id FROM akademik.kelas_kurikulum WHERE kelas_id = :kelas_id AND tahun_ajaran = :tahun_ajaran AND tenant_id = :tenant_id LIMIT 1");
            $stmtActive->execute([
                'kelas_id' => $kelasId,
                'tahun_ajaran' => $tahunAjaran,
                'tenant_id' => $tenantId
            ]);
            $activeKurikulumId = $stmtActive->fetchColumn() ?: '';
        }

        $stmtRef = $db->prepare("SELECT id, nama_ref_kurikulum AS nama_kurikulum, kategori AS tipe_penilaian FROM akademik.ref_kurikulum WHERE is_active = true AND (tenant_id = :tenant_id OR tenant_id IS NULL) ORDER BY nama_ref_kurikulum ASC");
        $stmtRef->execute(['tenant_id' => $tenantId]);
        $kurikulumList = $stmtRef->fetchAll(PDO::FETCH_ASSOC);

        $this->jsonResponse(true, [
            'tahun_ajaran' => $tahunList,
            'kelas' => $kelasList,
            'jenjang' => $jenjangList,
            'bank_mapel' => $mapelList,
            'existing_mapping' => $existingMapping,
            'kurikulum_list' => $kurikulumList,
            'active_kurikulum_id' => $activeKurikulumId
        ]);
    }

    /**
     * POST /api/v1/kurikulum
     */
    public function store(): void {
        $input = $this->getJsonInput();
        
        $kelasId = $input['kelas_id'] ?? '';
        $tahunAjaran = $input['tahun_ajaran'] ?? '';
        $semester = $input['semester'] ?? '';
        $kurikulumId = $input['kurikulum_id'] ?? '';
        $mappings = $input['mappings'] ?? [];

        $db = Database::getConnection();
        $tenantId = SessionManager::getTenantId();
        if ((!$tenantId || $tenantId === '00000000-0000-0000-0000-000000000000') && !empty($input['tenant_id'])) {
            $tenantId = $input['tenant_id'];
        }
        if (!$tenantId && $kelasId) {
            $stmtKelasTenant = $db->prepare("SELECT tenant_id FROM akademik.kelas WHERE id = :kelas_id LIMIT 1");
            $stmtKelasTenant->execute(['kelas_id' => $kelasId]);
            $tenantId = $stmtKelasTenant->fetchColumn() ?: null;
        }

        if (empty($kelasId) || empty($tahunAjaran) || empty($semester)) {
            $this->jsonResponse(false, null, 'Parameter kelas_id, tahun_ajaran, dan semester wajib diisi.', 400);
            return;
        }

        if (!$tenantId) {
            $this->jsonResponse(false, null, 'Tenant ID tidak terdeteksi.', 400);
            return;
        }

        $stmtLock = $db->prepare("SELECT is_locked_kurikulum FROM akademik.kunci_akademik WHERE tenant_id = ? AND tahun_ajaran = ? AND semester = ?");
        $stmtLock->execute([$tenantId, $tahunAjaran, $semester]);
        if ($stmtLock->fetchColumn()) {
            $this->jsonResponse(false, null, 'Gagal menyimpan. Seting Kurikulum pada Tahun Ajaran & Semester ini telah dikunci oleh administrator.', 403);
            return;
        }

        $db->beginTransaction();
        try {
            $stmtDelete = $db->prepare("DELETE FROM akademik.pemetaan_mapel WHERE kelas_id = :kelas_id AND semester = :semester AND tahun_ajaran = :tahun_ajaran AND tenant_id = :tenant_id");
            $stmtDelete->execute([
                'kelas_id' => $kelasId,
                'semester' => $semester,
                'tahun_ajaran' => $tahunAjaran,
                'tenant_id' => $tenantId
            ]);

            $stmtInsert = $db->prepare("INSERT INTO akademik.pemetaan_mapel (tenant_id, tahun_ajaran, semester, kelas_id, kelompok_id, mapel_id) VALUES (:tenant_id, :tahun_ajaran, :semester, :kelas_id, :kelompok_id, :mapel_id)");
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

            if (!empty($kurikulumId)) {
                $stmtDelKur = $db->prepare("DELETE FROM akademik.kelas_kurikulum WHERE kelas_id = :kelas_id AND tahun_ajaran = :tahun_ajaran AND tenant_id = :tenant_id");
                $stmtDelKur->execute([
                    'kelas_id' => $kelasId,
                    'tahun_ajaran' => $tahunAjaran,
                    'tenant_id' => $tenantId
                ]);
                
                $stmtInsKur = $db->prepare("INSERT INTO akademik.kelas_kurikulum (tenant_id, kelas_id, tahun_ajaran, kurikulum_id) VALUES (:tenant_id, :kelas_id, :tahun_ajaran, :kurikulum_id)");
                $stmtInsKur->execute([
                    'tenant_id' => $tenantId,
                    'kelas_id' => $kelasId,
                    'tahun_ajaran' => $tahunAjaran,
                    'kurikulum_id' => $kurikulumId
                ]);
            }

            $db->commit();
            $this->jsonResponse(true, ['message' => 'Kurikulum kelas berhasil disimpan.']);
        } catch (\Throwable $e) {
            $db->rollBack();
            $this->jsonResponse(false, null, 'Gagal menyimpan kurikulum: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/v1/kurikulum/copy
     */
    public function copyCurriculum(): void {
        $input = $this->getJsonInput();

        $sourceKelasId = $input['source_kelas_id'] ?? '';
        $targetKelasId = $input['target_kelas_id'] ?? '';
        $tahunAjaran = $input['tahun_ajaran'] ?? '';
        $semester = $input['semester'] ?? '';

        $db = Database::getConnection();
        $tenantId = SessionManager::getTenantId();
        if ((!$tenantId || $tenantId === '00000000-0000-0000-0000-000000000000') && !empty($input['tenant_id'])) {
            $tenantId = $input['tenant_id'];
        }
        if (!$tenantId && $targetKelasId) {
            $stmtKelasTenant = $db->prepare("SELECT tenant_id FROM akademik.kelas WHERE id = :kelas_id LIMIT 1");
            $stmtKelasTenant->execute(['kelas_id' => $targetKelasId]);
            $tenantId = $stmtKelasTenant->fetchColumn() ?: null;
        }

        if (empty($sourceKelasId) || empty($targetKelasId) || empty($tahunAjaran) || empty($semester)) {
            $this->jsonResponse(false, null, 'Parameter source_kelas_id, target_kelas_id, tahun_ajaran, dan semester wajib diisi.', 400);
            return;
        }

        if ($sourceKelasId == $targetKelasId) {
            $this->jsonResponse(false, null, 'Kelas sumber dan kelas tujuan tidak boleh sama.', 400);
            return;
        }

        if (!$tenantId) {
            $this->jsonResponse(false, null, 'Tenant ID tidak terdeteksi.', 400);
            return;
        }

        $stmtLock = $db->prepare("SELECT is_locked_kurikulum FROM akademik.kunci_akademik WHERE tenant_id = ? AND tahun_ajaran = ? AND semester = ?");
        $stmtLock->execute([$tenantId, $tahunAjaran, $semester]);
        if ($stmtLock->fetchColumn()) {
            $this->jsonResponse(false, null, 'Gagal menyalin. Seting Kurikulum pada Tahun Ajaran & Semester ini telah dikunci oleh administrator.', 403);
            return;
        }

        $db->beginTransaction();
        try {
            $stmtSource = $db->prepare("SELECT kelompok_id, mapel_id FROM akademik.pemetaan_mapel WHERE kelas_id = :source_kelas_id AND semester = :semester AND tahun_ajaran = :tahun_ajaran AND tenant_id = :tenant_id AND is_active = true");
            $stmtSource->execute([
                'source_kelas_id' => $sourceKelasId,
                'semester' => $semester,
                'tahun_ajaran' => $tahunAjaran,
                'tenant_id' => $tenantId
            ]);
            $sourceMappings = $stmtSource->fetchAll(PDO::FETCH_ASSOC);

            if (empty($sourceMappings)) {
                $db->rollBack();
                $this->jsonResponse(false, null, 'Kelas sumber tidak memiliki pemetaan kurikulum pada semester & tahun ajaran ini.', 404);
                return;
            }

            $stmtDelete = $db->prepare("DELETE FROM akademik.pemetaan_mapel WHERE kelas_id = :target_kelas_id AND semester = :semester AND tahun_ajaran = :tahun_ajaran AND tenant_id = :tenant_id");
            $stmtDelete->execute([
                'target_kelas_id' => $targetKelasId,
                'semester' => $semester,
                'tahun_ajaran' => $tahunAjaran,
                'tenant_id' => $tenantId
            ]);

            $stmtInsert = $db->prepare("INSERT INTO akademik.pemetaan_mapel (tenant_id, tahun_ajaran, semester, kelas_id, kelompok_id, mapel_id) VALUES (:tenant_id, :tahun_ajaran, :semester, :kelas_id, :kelompok_id, :mapel_id)");
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
            $this->jsonResponse(true, ['message' => 'Kurikulum berhasil disalin ke kelas tujuan.']);
        } catch (\Throwable $e) {
            $db->rollBack();
            $this->jsonResponse(false, null, 'Gagal menyalin kurikulum: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/v1/kunci-akademik/status
     */
    public function getLockStatus(): void {
        $tenantId = SessionManager::getTenantId();
        $filterTenant = $_GET['filter_tenant_id'] ?? '';
        
        if (!$tenantId && $filterTenant) {
            $tenantId = $filterTenant;
        }

        if (!$tenantId) {
            $this->jsonResponse(true, ['is_locked_kurikulum' => 0, 'is_locked_nilai' => 0]);
            return;
        }

        $tahunAjaran = $_GET['tahun_ajaran'] ?? '';
        $semester = $_GET['semester'] ?? '';

        if (!$tahunAjaran || !$semester) {
            $this->jsonResponse(true, ['is_locked_kurikulum' => 0, 'is_locked_nilai' => 0]);
            return;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT is_locked_kurikulum, is_locked_nilai FROM akademik.kunci_akademik WHERE tenant_id = ? AND tahun_ajaran = ? AND semester = ?");
        $stmt->execute([$tenantId, $tahunAjaran, $semester]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->jsonResponse(true, [
                'is_locked_kurikulum' => (int)$row['is_locked_kurikulum'],
                'is_locked_nilai' => (int)$row['is_locked_nilai']
            ]);
        } else {
            $this->jsonResponse(true, ['is_locked_kurikulum' => 0, 'is_locked_nilai' => 0]);
        }
    }

    /**
     * POST /api/v1/kunci-akademik/toggle
     */
    public function toggleLock(): void {
        $roles = $_SESSION['roles'] ?? [$_SESSION['role_name'] ?? ''];
        $allowed = array_intersect($roles, ['super_admin', 'operator_sekolah']);
        if (empty($allowed)) {
            $this->jsonResponse(false, null, 'Anda tidak memiliki otoritas untuk mengunci atau membuka kunci.', 403);
            return;
        }

        $data = $this->getJsonInput();
        $tenantId = SessionManager::getTenantId();
        
        if (!$tenantId && !empty($data['filter_tenant_id'])) {
            $tenantId = $data['filter_tenant_id'];
        }

        if (!$tenantId) {
            $this->jsonResponse(false, null, 'Tenant/Sekolah tidak valid', 400);
            return;
        }

        $tahunAjaran = $data['tahun_ajaran'] ?? '';
        $semester = $data['semester'] ?? '';
        $type = $data['type'] ?? '';
        $status = isset($data['status']) ? (int)$data['status'] : null;

        if (!$tahunAjaran || !$semester || !in_array($type, ['kurikulum', 'nilai']) || $status === null) {
            $this->jsonResponse(false, null, 'Data parameter tidak lengkap.', 400);
            return;
        }

        $db = Database::getConnection();
        
        $stmt = $db->prepare("SELECT id FROM akademik.kunci_akademik WHERE tenant_id = ? AND tahun_ajaran = ? AND semester = ?");
        $stmt->execute([$tenantId, $tahunAjaran, $semester]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $col = $type === 'kurikulum' ? 'is_locked_kurikulum' : 'is_locked_nilai';
            $update = $db->prepare("UPDATE akademik.kunci_akademik SET {$col} = ? WHERE id = ?");
            $update->execute([$status, $row['id']]);
        } else {
            $col = $type === 'kurikulum' ? 'is_locked_kurikulum' : 'is_locked_nilai';
            $insert = $db->prepare("INSERT INTO akademik.kunci_akademik (tenant_id, tahun_ajaran, semester, {$col}) VALUES (?, ?, ?, ?)");
            $insert->execute([$tenantId, $tahunAjaran, $semester, $status]);
        }

        $this->jsonResponse(true, ['message' => 'Status kunci berhasil diperbarui.']);
    }
}
