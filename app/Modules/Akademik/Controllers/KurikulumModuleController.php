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
        
        $requestedTenant = $_GET['tenant_id'] ?? ($_GET['filter_tenant_id'] ?? null);
        $sessionTenant = SessionManager::getTenantId();
        $tenantId = (!empty($requestedTenant)) ? $requestedTenant : $sessionTenant;
        if ($tenantId === 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12') {
            $tenantId = null;
        }

        $sqlTahun = "SELECT id, nama_tahun_ajaran AS tahun_ajaran, nama_tahun_ajaran, kategori AS status FROM akademik.tahun_ajaran WHERE is_active = true";
        if ($tenantId) {
            $sqlTahun .= " AND (tenant_id = :tenant_id OR tenant_id IS NULL)";
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

        // Ambil daftar Guru aktif untuk opsi Guru Pengampu
        $sqlGuru = "SELECT u.id, u.nama_lengkap, u.nip, u.nuptk 
                    FROM core.users u 
                    JOIN core.roles r ON u.role_id = r.id 
                    WHERE 1=1";
        if ($tenantId) {
            $sqlGuru .= " AND (u.tenant_id = :tenant_id OR u.tenant_id IS NULL)";
        }
        $sqlGuru .= " AND (u.is_active = true OR u.is_active IS NULL)
                      AND (r.nama_role IN ('guru', 'guru_bk', 'admin', 'operator_sekolah') 
                           OR EXISTS (
                               SELECT 1 FROM core.user_roles ur 
                               JOIN core.roles r2 ON ur.role_id = r2.id 
                               WHERE ur.user_id = u.id AND r2.nama_role IN ('guru', 'guru_bk')
                           ))
                    ORDER BY u.nama_lengkap ASC";
        $stmtGuru = $db->prepare($sqlGuru);
        if ($tenantId) {
            $stmtGuru->execute(['tenant_id' => $tenantId]);
        } else {
            $stmtGuru->execute();
        }
        $guruList = $stmtGuru->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $existingMapping = [];
        $kelasId = $_GET['kelas_id'] ?? '';
        $tahunAjaran = $_GET['tahun_ajaran'] ?? '';
        $semester = $_GET['semester'] ?? '';
        $activeKurikulumId = '';

        if (!empty($kelasId) && !empty($tahunAjaran) && !empty($semester)) {
            $stmtExist = $db->prepare("SELECT kelompok_id, mapel_id, guru_id, COALESCE(kkm, 75) as kkm, COALESCE(jam_pelajaran, 2) as jam_pelajaran FROM akademik.pemetaan_mapel WHERE (kelas_id = :kelas_id OR kelas_id::text = :kelas_id) AND tahun_ajaran = :tahun_ajaran AND semester = :semester AND (tenant_id::text = :tenant_id OR tenant_id IS NULL) AND (is_active = true OR is_active IS NULL)");
            $paramsExist = [
                'kelas_id' => $kelasId,
                'tahun_ajaran' => $tahunAjaran,
                'semester' => $semester,
                'tenant_id' => $tenantId
            ];
            $stmtExist->execute($paramsExist);
            $existingMapping = $stmtExist->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $sqlActive = "SELECT kurikulum_id FROM akademik.kelas_kurikulum WHERE (kelas_id = :kelas_id OR kelas_id::text = :kelas_id) AND tahun_ajaran = :tahun_ajaran";
            $paramsActive = [
                'kelas_id' => $kelasId,
                'tahun_ajaran' => $tahunAjaran
            ];
            if ($tenantId) {
                $sqlActive .= " AND (tenant_id::text = :tenant_id OR tenant_id IS NULL)";
                $paramsActive['tenant_id'] = $tenantId;
            }
            $sqlActive .= " LIMIT 1";
            $stmtActive = $db->prepare($sqlActive);
            $stmtActive->execute($paramsActive);
            $activeKurikulumId = $stmtActive->fetchColumn() ?: '';
        }

        if ($tenantId) {
            $stmtRef = $db->prepare("SELECT id, nama_ref_kurikulum AS nama_kurikulum, kategori AS tipe_penilaian FROM akademik.ref_kurikulum WHERE is_active = true AND (tenant_id::text = :tenant_id OR tenant_id IS NULL) ORDER BY nama_ref_kurikulum ASC");
            $stmtRef->execute(['tenant_id' => $tenantId]);
        } else {
            $stmtRef = $db->prepare("SELECT id, nama_ref_kurikulum AS nama_kurikulum, kategori AS tipe_penilaian FROM akademik.ref_kurikulum WHERE is_active = true AND (tenant_id IS NULL OR tenant_id::text = 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12') ORDER BY nama_ref_kurikulum ASC");
            $stmtRef->execute();
        }
        $kurikulumList = $stmtRef->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $this->jsonResponse(true, [
            'tahun_ajaran' => $tahunList,
            'kelas' => $kelasList,
            'jenjang' => $jenjangList,
            'bank_mapel' => $mapelList,
            'guru_list' => $guruList,
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
        if ((!$tenantId || $tenantId === 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12') && !empty($input['tenant_id'])) {
            $tenantId = $input['tenant_id'];
        }
        if (!$tenantId && $kelasId) {
            $stmtKelasTenant = $db->prepare("SELECT tenant_id::text FROM akademik.kelas WHERE id::text = :kelas_id LIMIT 1");
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

        $stmtLock = $db->prepare("SELECT is_locked_kurikulum FROM akademik.kunci_akademik WHERE (tenant_id::text = :tenant_id OR tenant_id IS NULL) AND tahun_ajaran = :ta AND semester = :sem AND is_active = true LIMIT 1");
        $stmtLock->execute(['tenant_id' => $tenantId, 'ta' => $tahunAjaran, 'sem' => $semester]);
        if ($stmtLock->fetchColumn()) {
            $this->jsonResponse(false, null, 'Gagal menyimpan. Seting Kurikulum pada Tahun Ajaran & Semester ini telah dikunci oleh administrator.', 403);
            return;
        }

        $db->beginTransaction();
        try {
            $stmtDelete = $db->prepare("DELETE FROM akademik.pemetaan_mapel WHERE (kelas_id = :kelas_id OR kelas_id::text = :kelas_id) AND semester = :semester AND tahun_ajaran = :tahun_ajaran AND (tenant_id::text = :tenant_id OR tenant_id IS NULL)");
            $stmtDelete->execute([
                'kelas_id' => $kelasId,
                'semester' => $semester,
                'tahun_ajaran' => $tahunAjaran,
                'tenant_id' => $tenantId
            ]);

            $stmtInsert = $db->prepare("INSERT INTO akademik.pemetaan_mapel (tenant_id, tahun_ajaran, semester, kelas_id, kelompok_id, mapel_id, guru_id, kkm, jam_pelajaran) VALUES (:tenant_id, :tahun_ajaran, :semester, :kelas_id, :kelompok_id, :mapel_id, :guru_id, :kkm, :jam_pelajaran)");
            foreach ($mappings as $group) {
                $kelompokId = trim($group['kelompok_id'] ?? '');
                $mapelItems = $group['mapel_ids'] ?? [];
                if (empty($kelompokId) || empty($mapelItems)) {
                    continue;
                }
                foreach ($mapelItems as $item) {
                    $mId = is_array($item) ? ($item['mapel_id'] ?? '') : $item;
                    $gId = is_array($item) && !empty($item['guru_id']) ? $item['guru_id'] : null;
                    $kkm = is_array($item) && isset($item['kkm']) ? (float)$item['kkm'] : 75;
                    $jam = is_array($item) && isset($item['jam_pelajaran']) ? (int)$item['jam_pelajaran'] : 2;

                    if (empty($mId)) continue;

                    $stmtInsert->execute([
                        'tenant_id' => $tenantId,
                        'tahun_ajaran' => $tahunAjaran,
                        'semester' => $semester,
                        'kelas_id' => $kelasId,
                        'kelompok_id' => $kelompokId,
                        'mapel_id' => $mId,
                        'guru_id' => $gId,
                        'kkm' => $kkm,
                        'jam_pelajaran' => $jam
                    ]);
                }
            }

            if (!empty($kurikulumId)) {
                $stmtDelKur = $db->prepare("DELETE FROM akademik.kelas_kurikulum WHERE (kelas_id = :kelas_id OR kelas_id::text = :kelas_id) AND tahun_ajaran = :tahun_ajaran AND (tenant_id::text = :tenant_id OR tenant_id IS NULL)");
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
     * Mendukung salin lintas semester & tahun ajaran.
     * Params:
     *   source_kelas_id, target_kelas_id, tahun_ajaran, semester   (sumber)
     *   target_tahun_ajaran (opsional, default = tahun_ajaran)
     *   target_semester     (opsional, default = semester)
     */
    public function copyCurriculum(): void {
        $input = $this->getJsonInput();

        $sourceKelasId      = $input['source_kelas_id']       ?? '';
        $targetKelasId      = $input['target_kelas_id']       ?? '';
        $tahunAjaran        = $input['tahun_ajaran']          ?? '';
        $semester           = $input['semester']              ?? '';
        // Target bisa berbeda semester/TA dari sumber
        $targetTahunAjaran  = !empty($input['target_tahun_ajaran']) ? $input['target_tahun_ajaran'] : $tahunAjaran;
        $targetSemester     = !empty($input['target_semester'])     ? $input['target_semester']     : $semester;

        $db = Database::getConnection();
        $tenantId = SessionManager::getTenantId();
        if ((!$tenantId || $tenantId === 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12') && !empty($input['tenant_id'])) {
            $tenantId = $input['tenant_id'];
        }
        if (!$tenantId && $targetKelasId) {
            $stmtKelasTenant = $db->prepare("SELECT tenant_id::text FROM akademik.kelas WHERE id::text = :kelas_id LIMIT 1");
            $stmtKelasTenant->execute(['kelas_id' => $targetKelasId]);
            $tenantId = $stmtKelasTenant->fetchColumn() ?: null;
        }

        if (empty($sourceKelasId) || empty($targetKelasId) || empty($tahunAjaran) || empty($semester)) {
            $this->jsonResponse(false, null, 'Parameter source_kelas_id, target_kelas_id, tahun_ajaran, dan semester wajib diisi.', 400);
            return;
        }

        // Boleh sama kelas asal beda semester/TA
        if ($sourceKelasId === $targetKelasId && $semester === $targetSemester && $tahunAjaran === $targetTahunAjaran) {
            $this->jsonResponse(false, null, 'Kelas sumber & tujuan, semester, dan tahun ajaran tidak boleh semua sama persis.', 400);
            return;
        }

        if (!$tenantId) {
            $this->jsonResponse(false, null, 'Tenant ID tidak terdeteksi.', 400);
            return;
        }

        // Cek kunci pada semester/TA TUJUAN
        $stmtLock = $db->prepare("SELECT is_locked_kurikulum FROM akademik.kunci_akademik WHERE (tenant_id::text = :tenant_id OR tenant_id IS NULL) AND tahun_ajaran = :ta AND semester = :sem AND is_active = true LIMIT 1");
        $stmtLock->execute(['tenant_id' => $tenantId, 'ta' => $targetTahunAjaran, 'sem' => $targetSemester]);
        if ((bool)$stmtLock->fetchColumn()) {
            $this->jsonResponse(false, null, 'Gagal menyalin. Seting Kurikulum Semester tujuan sedang dikunci. Silakan buka kunci rombel/kurikulum terlebih dahulu.', 403);
            return;
        }

        $db->beginTransaction();
        try {
            // Ambil mapping dari sumber (semester & TA sumber)
            $stmtSource = $db->prepare("SELECT kelompok_id, mapel_id, guru_id, COALESCE(kkm, 75) as kkm, COALESCE(jam_pelajaran, 2) as jam_pelajaran FROM akademik.pemetaan_mapel WHERE (kelas_id = :source_kelas_id OR kelas_id::text = :source_kelas_id) AND semester = :semester AND tahun_ajaran = :tahun_ajaran AND (tenant_id::text = :tenant_id OR tenant_id IS NULL) AND (is_active = true OR is_active IS NULL)");
            $stmtSource->execute([
                'source_kelas_id' => $sourceKelasId,
                'semester'        => $semester,
                'tahun_ajaran'    => $tahunAjaran,
                'tenant_id'       => $tenantId
            ]);
            $sourceMappings = $stmtSource->fetchAll(PDO::FETCH_ASSOC);

            if (empty($sourceMappings)) {
                $db->rollBack();
                $this->jsonResponse(false, null, 'Kelas sumber tidak memiliki pemetaan kurikulum pada semester & tahun ajaran ini. Pastikan kelas sumber sudah disetting kurikulumnya terlebih dahulu.', 422);
                return;
            }

            // Hapus data lama di tujuan (semester & TA tujuan)
            $stmtDelete = $db->prepare("DELETE FROM akademik.pemetaan_mapel WHERE (kelas_id = :target_kelas_id OR kelas_id::text = :target_kelas_id) AND semester = :semester AND tahun_ajaran = :tahun_ajaran AND (tenant_id::text = :tenant_id OR tenant_id IS NULL)");
            $stmtDelete->execute([
                'target_kelas_id' => $targetKelasId,
                'semester'        => $targetSemester,
                'tahun_ajaran'    => $targetTahunAjaran,
                'tenant_id'       => $tenantId
            ]);

            // Insert ke tujuan dengan semester & TA tujuan
            $stmtInsert = $db->prepare("INSERT INTO akademik.pemetaan_mapel (tenant_id, tahun_ajaran, semester, kelas_id, kelompok_id, mapel_id, guru_id, kkm, jam_pelajaran) VALUES (:tenant_id, :tahun_ajaran, :semester, :kelas_id, :kelompok_id, :mapel_id, :guru_id, :kkm, :jam_pelajaran)");
            foreach ($sourceMappings as $row) {
                $stmtInsert->execute([
                    'tenant_id'     => $tenantId,
                    'tahun_ajaran'  => $targetTahunAjaran,
                    'semester'      => $targetSemester,
                    'kelas_id'      => $targetKelasId,
                    'kelompok_id'   => $row['kelompok_id'],
                    'mapel_id'      => $row['mapel_id'],
                    'guru_id'       => $row['guru_id'] ?? null,
                    'kkm'           => $row['kkm'] ?? 75,
                    'jam_pelajaran' => $row['jam_pelajaran'] ?? 2
                ]);
            }

            $db->commit();
            $this->jsonResponse(true, [
                'message' => 'Kurikulum berhasil disalin.',
                'total'   => count($sourceMappings)
            ]);
        } catch (\Throwable $e) {
            $db->rollBack();
            $this->jsonResponse(false, null, 'Gagal menyalin kurikulum: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/v1/kunci-akademik/status
     */
    public function getStatus(): void {
        $this->getLockStatus();
    }

    public function toggle(): void {
        $this->toggleLock();
    }

    /**
     * GET /api/v1/kunci-akademik/status
     */
    public function getLockStatus(): void {
        $requestedTenant = $_GET['filter_tenant_id'] ?? $_GET['tenant_id'] ?? null;
        $sessionTenant = SessionManager::getTenantId();
        $tenantId = (!empty($requestedTenant)) ? $requestedTenant : $sessionTenant;
        if ($tenantId === 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12') {
            $tenantId = null;
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
        $requestedTenant = $data['filter_tenant_id'] ?? $data['tenant_id'] ?? null;
        $sessionTenant = SessionManager::getTenantId();
        $tenantId = (!empty($requestedTenant)) ? $requestedTenant : $sessionTenant;
        if ($tenantId === 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12') {
            $tenantId = null;
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
            $update = $db->prepare("UPDATE akademik.kunci_akademik SET {$col} = :status WHERE id = :id AND tenant_id = :tenant_id");
            $update->execute([
                'status'    => $status,
                'id'        => $row['id'],
                'tenant_id' => $tenantId
            ]);
        } else {
            $col = $type === 'kurikulum' ? 'is_locked_kurikulum' : 'is_locked_nilai';
            $insert = $db->prepare("INSERT INTO akademik.kunci_akademik (tenant_id, tahun_ajaran, semester, {$col}) VALUES (:tenant_id, :tahun_ajaran, :semester, :status)");
            $insert->execute([
                'tenant_id'    => $tenantId,
                'tahun_ajaran' => $tahunAjaran,
                'semester'     => $semester,
                'status'       => $status
            ]);
        }

        $this->jsonResponse(true, ['message' => 'Status kunci berhasil diperbarui.']);
    }

    /**
     * POST /api/v1/kurikulum/mapel
     * Tambah mata pelajaran baru ke bank mapel sekolah
     */
    public function storeMapel(): void {
        $input = $this->getJsonInput();

        $namaMapel = trim($input['nama_mata_pelajaran'] ?? '');
        $kategori  = trim($input['kategori'] ?? '');
        $deskripsi = trim($input['deskripsi'] ?? '');

        if (empty($namaMapel)) {
            $this->jsonResponse(false, null, 'Nama mata pelajaran tidak boleh kosong.', 422);
            return;
        }

        $db = Database::getConnection();
        $tenantId = SessionManager::getTenantId();
        if ((!$tenantId || $tenantId === 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12') && !empty($input['tenant_id'])) {
            $tenantId = $input['tenant_id'];
        }
        if (!$tenantId) {
            $this->jsonResponse(false, null, 'Tenant ID tidak terdeteksi.', 400);
            return;
        }

        // Cek duplikat nama (case-insensitive) untuk sekolah ini
        $stmtCheck = $db->prepare("SELECT id FROM akademik.mata_pelajaran WHERE tenant_id = :tenant_id AND LOWER(nama_mata_pelajaran) = LOWER(:nama) AND is_active = TRUE");
        $stmtCheck->execute(['tenant_id' => $tenantId, 'nama' => $namaMapel]);
        if ($stmtCheck->fetchColumn()) {
            $this->jsonResponse(false, null, "Mata pelajaran \"{$namaMapel}\" sudah ada di bank mapel sekolah ini.", 409);
            return;
        }

        try {
            $stmt = $db->prepare(
                "INSERT INTO akademik.mata_pelajaran (tenant_id, nama_mata_pelajaran, kategori, deskripsi, is_active)
                 VALUES (:tenant_id, :nama, :kategori, :deskripsi, TRUE)
                 RETURNING id::text AS id, nama_mata_pelajaran AS nama_mapel, id::text AS kode_mapel"
            );
            $stmt->execute([
                'tenant_id' => $tenantId,
                'nama'      => $namaMapel,
                'kategori'  => $kategori ?: null,
                'deskripsi' => $deskripsi ?: null,
            ]);
            $newMapel = $stmt->fetch(PDO::FETCH_ASSOC);

            $this->jsonResponse(true, [
                'mapel'   => $newMapel,
                'message' => "Mata pelajaran \"{$namaMapel}\" berhasil ditambahkan ke bank mapel sekolah."
            ], null, 201);
        } catch (\Throwable $e) {
            $this->jsonResponse(false, null, 'Gagal menambahkan mata pelajaran: ' . $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /api/v1/kurikulum/mapel
     * Hapus (soft-delete) mata pelajaran dari bank mapel sekolah
     */
    public function deleteMapel(): void {
        $input = $this->getJsonInput();

        $mapelId = trim($input['mapel_id'] ?? '');
        if (empty($mapelId)) {
            $this->jsonResponse(false, null, 'mapel_id tidak boleh kosong.', 422);
            return;
        }

        $db = Database::getConnection();
        $tenantId = SessionManager::getTenantId();
        if ((!$tenantId || $tenantId === 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12') && !empty($input['tenant_id'])) {
            $tenantId = $input['tenant_id'];
        }
        if (!$tenantId) {
            $this->jsonResponse(false, null, 'Tenant ID tidak terdeteksi.', 400);
            return;
        }

        // Cek apakah mapel sedang digunakan di pemetaan aktif
        $stmtUsed = $db->prepare("SELECT COUNT(*) FROM akademik.pemetaan_mapel WHERE mapel_id = :mapel_id AND tenant_id = :tenant_id AND is_active = TRUE");
        $stmtUsed->execute(['mapel_id' => $mapelId, 'tenant_id' => $tenantId]);
        $usedCount = (int)$stmtUsed->fetchColumn();
        if ($usedCount > 0) {
            $this->jsonResponse(false, null, "Tidak dapat menghapus. Mata pelajaran ini masih digunakan di {$usedCount} pemetaan kurikulum aktif.", 409);
            return;
        }

        try {
            $stmt = $db->prepare(
                "UPDATE akademik.mata_pelajaran SET is_active = FALSE, updated_at = CURRENT_TIMESTAMP
                 WHERE id = :mapel_id AND tenant_id = :tenant_id"
            );
            $stmt->execute(['mapel_id' => $mapelId, 'tenant_id' => $tenantId]);

            if ($stmt->rowCount() === 0) {
                $this->jsonResponse(false, null, 'Mata pelajaran tidak ditemukan atau tidak memiliki akses.', 404);
                return;
            }

            $this->jsonResponse(true, ['message' => 'Mata pelajaran berhasil dihapus dari bank mapel sekolah.']);
        } catch (\Throwable $e) {
            $this->jsonResponse(false, null, 'Gagal menghapus mata pelajaran: ' . $e->getMessage(), 500);
        }
    }
}
