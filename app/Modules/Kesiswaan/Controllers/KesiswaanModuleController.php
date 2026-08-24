<?php

namespace App\Modules\Kesiswaan\Controllers;

use App\Core\BaseController;
use App\Core\SessionManager;
use App\Core\RouteGuard;
use App\Modules\Kesiswaan\Models\EkskulModel;
use App\Modules\Kesiswaan\Models\PrestasiSiswaModel;
use Exception;
use PDO;

class KesiswaanModuleController extends BaseController {

    private const ALLOWED_ROLES = ['super_admin', 'operator_sekolah', 'guru_pembina', 'guru_bk', 'kepala_sekolah', 'kesiswaan', 'guru'];
    private EkskulModel $model;

    public function __construct() {
        parent::__construct();
        SessionManager::requireLogin();
        $this->guardRole();
        $this->model = new EkskulModel();
    }

    private function guardRole(): void {
        if (!RouteGuard::checkCurrent(self::ALLOWED_ROLES)) {
            $isApi = str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/');
            if ($isApi) {
                $this->jsonResponse(['success' => false, 'error' => 'Role tidak memiliki akses ke modul Kesiswaan.'], 403);
            }
            $this->redirect('/dashboard?error=forbidden');
        }
    }

    /**
     * Helper: Mendapatkan Tenant ID yang aman (Super Admin support)
     */
    protected function getSecureTenantId(): ?string {
        $roles = $_SESSION['roles'] ?? [$_SESSION['role_name'] ?? ''];
        $tenantId = SessionManager::getTenantId();

        if (in_array('super_admin', $roles, true) || in_array('superadmin', $roles, true)) {
            $tid = $_GET['tenant_id'] ?? $_POST['tenant_id'] ?? null;
            if (empty($tid)) {
                $body = $this->getJsonInput();
                $tid = $body['tenant_id'] ?? null;
            }

            if (!empty($tid) && $tid !== 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12') {
                try {
                    $db = \App\Config\Database::getConnection();
                    $stmt = $db->prepare("SELECT id FROM core.tenants WHERE id = ? LIMIT 1");
                    $stmt->execute([$tid]);
                    $valid = $stmt->fetchColumn();
                    if ($valid) return $valid;
                } catch (\Throwable $e) {}
            }

            try {
                $db = \App\Config\Database::getConnection();
                $stmtDefault = $db->query("SELECT id FROM core.tenants WHERE id != 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12' AND status = 'active' ORDER BY nama_sekolah ASC LIMIT 1");
                $firstId = $stmtDefault->fetchColumn();
                if ($firstId) return $firstId;
            } catch (\Throwable $e) {}
        }

        if (empty($tenantId) || $tenantId === 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12') {
            try {
                $db = \App\Config\Database::getConnection();
                $stmtDefault = $db->query("SELECT id FROM core.tenants WHERE id != 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12' AND status = 'active' ORDER BY nama_sekolah ASC LIMIT 1");
                $tenantId = $stmtDefault->fetchColumn() ?: null;
            } catch (\Throwable $e) {}
        }

        return $tenantId;
    }

    /**
     * GET /kesiswaan/ekskul
     */
    public function index(): void {
        $this->ekskulIndex();
    }

    public function ekskulIndex(): void {
        $pageTitle = 'Manajemen Ekstrakurikuler';
        $userRole = $_SESSION['role_name'] ?? 'operator_sekolah';
        $userName = $_SESSION['nama_lengkap'] ?? 'User';
        $roles = $_SESSION['roles'] ?? [$userRole];
        $isSuperAdmin = in_array('super_admin', $roles, true);

        // Ambil daftar sekolah untuk Super Admin
        $tenants = [];
        if ($isSuperAdmin) {
            try {
                $db = \App\Config\Database::getConnection();
                $stmt = $db->query("SELECT id, nama_sekolah, npsn FROM core.tenants WHERE id != 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12' AND status = 'active' ORDER BY nama_sekolah ASC");
                $tenants = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            } catch (\Throwable $e) {}
        }

        $this->render('kesiswaan_ekskul', [
            'title' => $pageTitle,
            'userRole' => $userRole,
            'userName' => $userName,
            'isSuperAdmin' => $isSuperAdmin,
            'tenants' => $tenants,
            'selectedTenantId' => $this->getSecureTenantId()
        ]);
    }

    /* ═══════════════════════════════════════════════════════════════════════
       API: MASTER OPTIONS & STATS
       ═══════════════════════════════════════════════════════════════════════ */

    public function apiGetMasterOptions(): void {
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['success' => false, 'error' => 'Pilih sekolah terlebih dahulu.'], 400);
            return;
        }

        try {
            $db = \App\Config\Database::getConnection();

            // 1. Tahun Ajaran
            $stmtTa = $db->prepare("SELECT id, nama_tahun_ajaran, is_active FROM akademik.tahun_ajaran WHERE tenant_id = ? ORDER BY nama_tahun_ajaran DESC");
            $stmtTa->execute([$tenantId]);
            $tahunAjaran = $stmtTa->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // 2. Daftar Kelas
            $stmtKelas = $db->prepare("SELECT id, nama_kelas FROM akademik.kelas WHERE tenant_id = ? ORDER BY nama_kelas ASC");
            $stmtKelas->execute([$tenantId]);
            $kelasList = $stmtKelas->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // 3. Daftar Guru / Pengguna Sekolah (untuk opsi Pembina Ekskul)
            $stmtGuru = $db->prepare("
                SELECT u.id, u.nama_lengkap, '—' AS nip, '' AS no_hp, COALESCE(u.email, '') AS email, 'L' AS jenis_kelamin
                FROM core.users u 
                JOIN core.roles r ON u.role_id = r.id 
                WHERE u.tenant_id = ? AND u.is_active = true AND r.nama_role != 'siswa'
                ORDER BY u.nama_lengkap ASC
            ");
            $stmtGuru->execute([$tenantId]);
            $guruList = $stmtGuru->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $roles = $_SESSION['roles'] ?? [$_SESSION['role_name'] ?? ''];
            $isSuperAdmin = in_array('super_admin', $roles, true) || in_array('superadmin', $roles, true);
            $tenants = [];
            if ($isSuperAdmin) {
                $stmtTenants = $db->query("SELECT id, nama_sekolah, npsn FROM core.tenants WHERE id != 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12' AND (status = 'active' OR status IS NULL) ORDER BY nama_sekolah ASC");
                $tenants = $stmtTenants->fetchAll(PDO::FETCH_ASSOC) ?: [];
            }

            $stats = $this->model->getSummaryStats($tenantId);

            $this->jsonResponse([
                'success' => true,
                'data' => [
                    'tenants' => $tenants,
                    'tahun_ajaran' => $tahunAjaran,
                    'kelas_list' => $kelasList,
                    'guru_list' => $guruList,
                    'stats' => $stats
                ]
            ], 200);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function apiGetSummary(): void {
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['success' => false, 'error' => 'Tenant ID tidak terdeteksi.'], 400);
            return;
        }

        try {
            $stats = $this->model->getSummaryStats($tenantId);
            $this->jsonResponse(['success' => true, 'data' => $stats], 200);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /* ═══════════════════════════════════════════════════════════════════════
       API: MASTER EKSKUL CRUD
       ═══════════════════════════════════════════════════════════════════════ */

    public function apiGetMasterEkskul(): void {
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['success' => false, 'error' => 'Tenant ID tidak terdeteksi.'], 400);
            return;
        }

        try {
            $activeOnly = isset($_GET['active_only']) && ($_GET['active_only'] === '1' || $_GET['active_only'] === 'true');
            $tahunAjaranId = !empty($_GET['tahun_ajaran_id']) ? trim($_GET['tahun_ajaran_id']) : null;
            $semester = !empty($_GET['semester']) ? trim($_GET['semester']) : null;
            $data = $this->model->getAllEkskul($tenantId, $activeOnly, $tahunAjaranId, $semester);
            $this->jsonResponse(['success' => true, 'data' => $data], 200);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function apiSaveMasterEkskul(): void {
        $this->validateCsrfToken();
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['success' => false, 'error' => 'Tenant ID tidak terdeteksi.'], 400);
            return;
        }

        $input = $this->getJsonInput();
        $namaEkskul = trim($input['nama_ekskul'] ?? '');
        if (empty($namaEkskul)) {
            $this->jsonResponse(['success' => false, 'error' => 'Nama ekstrakurikuler wajib diisi.'], 422);
            return;
        }

        try {
            $id = $this->model->saveEkskul($tenantId, $input);
            $this->jsonResponse([
                'success' => true,
                'message' => empty($input['id']) ? 'Ekstrakurikuler berhasil ditambahkan.' : 'Ekstrakurikuler berhasil diperbarui.',
                'id' => $id
            ], 200);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function apiDeleteMasterEkskul(): void {
        $this->validateCsrfToken();
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['success' => false, 'error' => 'Tenant ID tidak terdeteksi.'], 400);
            return;
        }

        $input = $this->getJsonInput();
        $id = $input['id'] ?? ($_GET['id'] ?? '');
        if (empty($id)) {
            $this->jsonResponse(['success' => false, 'error' => 'ID ekskul tidak valid.'], 422);
            return;
        }

        try {
            $this->model->deleteEkskul($tenantId, $id);
            $this->jsonResponse(['success' => true, 'message' => 'Ekstrakurikuler berhasil dinonaktifkan/dihapus.'], 200);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function apiToggleMasterEkskul(): void {
        $this->validateCsrfToken();
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['success' => false, 'error' => 'Tenant ID tidak terdeteksi.'], 400);
            return;
        }

        $input = $this->getJsonInput();
        $id = $input['id'] ?? '';
        $newStatus = isset($input['is_active']) ? (bool)$input['is_active'] : null;
        if (isset($input['new_status'])) {
            $newStatus = ($input['new_status'] === 'active');
        }

        if (empty($id)) {
            $this->jsonResponse(['success' => false, 'error' => 'ID ekskul tidak valid.'], 422);
            return;
        }

        try {
            $this->model->toggleStatusEkskul($tenantId, $id, $newStatus);
            $this->jsonResponse(['success' => true, 'message' => 'Status ekstrakurikuler berhasil diperbarui.'], 200);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /* ═══════════════════════════════════════════════════════════════════════
       API: DATA PEMBINA CRUD
       ═══════════════════════════════════════════════════════════════════════ */

    public function apiGetPembina(): void {
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['success' => false, 'error' => 'Tenant ID tidak terdeteksi.'], 400);
            return;
        }

        try {
            $data = $this->model->getAllPembina($tenantId);
            $this->jsonResponse(['success' => true, 'data' => $data], 200);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function apiSavePembina(): void {
        $this->validateCsrfToken();
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['success' => false, 'error' => 'Tenant ID tidak terdeteksi.'], 400);
            return;
        }

        $input = $this->getJsonInput();
        $namaPembina = trim($input['nama_pembina'] ?? '');
        if (empty($namaPembina)) {
            $this->jsonResponse(['success' => false, 'error' => 'Nama pembina wajib diisi.'], 422);
            return;
        }

        try {
            $id = $this->model->savePembina($tenantId, $input);
            $this->jsonResponse([
                'success' => true,
                'message' => empty($input['id']) ? 'Pembina berhasil didaftarkan.' : 'Data pembina berhasil diperbarui.',
                'id' => $id
            ], 200);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function apiDeletePembina(): void {
        $this->validateCsrfToken();
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['success' => false, 'error' => 'Tenant ID tidak terdeteksi.'], 400);
            return;
        }

        $input = $this->getJsonInput();
        $id = $input['id'] ?? ($_GET['id'] ?? '');
        if (empty($id)) {
            $this->jsonResponse(['success' => false, 'error' => 'ID pembina tidak valid.'], 422);
            return;
        }

        try {
            $this->model->deletePembina($tenantId, $id);
            $this->jsonResponse(['success' => true, 'message' => 'Pembina berhasil dihapus.'], 200);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function apiTogglePembina(): void {
        $this->validateCsrfToken();
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['success' => false, 'error' => 'Tenant ID tidak terdeteksi.'], 400);
            return;
        }

        $input = $this->getJsonInput();
        $id = $input['id'] ?? '';
        $newStatus = isset($input['is_active']) ? (bool)$input['is_active'] : null;
        if (isset($input['new_status'])) {
            $newStatus = ($input['new_status'] === 'active');
        }

        if (empty($id)) {
            $this->jsonResponse(['success' => false, 'error' => 'ID pembina tidak valid.'], 422);
            return;
        }

        try {
            $this->model->toggleStatusPembina($tenantId, $id, $newStatus);
            $this->jsonResponse(['success' => true, 'message' => 'Status pembina berhasil diperbarui.'], 200);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /* ═══════════════════════════════════════════════════════════════════════
       API: ANGGOTA EKSKUL
       ═══════════════════════════════════════════════════════════════════════ */

    public function apiGetAnggota(): void {
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['success' => false, 'error' => 'Tenant ID tidak terdeteksi.'], 400);
            return;
        }

        $ekskulId = $_GET['ekskul_id'] ?? '';
        $tahunAjaranId = $_GET['tahun_ajaran_id'] ?? null;
        $semester = $_GET['semester'] ?? null;

        if (empty($ekskulId)) {
            $this->jsonResponse(['success' => true, 'data' => []], 200);
            return;
        }

        try {
            $data = $this->model->getAnggotaEkskul($tenantId, $ekskulId, $tahunAjaranId, $semester);
            $lock = $this->model->getLockStatus($tenantId, $ekskulId, $tahunAjaranId ?? '', $semester ?? 'Ganjil');
            $this->jsonResponse([
                'success' => true,
                'data' => $data,
                'lock' => $lock
            ], 200);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function apiSearchSiswa(): void {
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['success' => false, 'error' => 'Tenant ID tidak terdeteksi.'], 400);
            return;
        }

        $q = trim($_GET['q'] ?? '');
        $kelasId = trim($_GET['kelas_id'] ?? '');
        $ekskulId = trim($_GET['ekskul_id'] ?? '');
        $tahunAjaranId = trim($_GET['tahun_ajaran_id'] ?? '');
        $semester = trim($_GET['semester'] ?? 'Ganjil');

        try {
            $db = \App\Config\Database::getConnection();

            $sql = "
                SELECT 
                    s.id, 
                    s.nama_lengkap, 
                    s.nisn, 
                    s.nis, 
                    COALESCE(k.nama_kelas, s.kelas_saat_ini, '—') AS nama_kelas,
                    COALESCE(s.jenis_kelamin, 'L') AS jenis_kelamin,
                    EXISTS (
                        SELECT 1 FROM kesiswaan.anggota_ekskul ae 
                        WHERE ae.siswa_id = s.id 
                          AND ae.ekskul_id = :ekskul_id 
                          AND (ae.tahun_ajaran_id = :ta_id OR ae.tahun_ajaran_id IS NULL)
                          AND ae.semester = :semester
                          AND ae.tenant_id = s.tenant_id
                    ) AS is_already_member
                FROM siswa.siswa s
                LEFT JOIN akademik.kelas k ON (s.kelas_saat_ini = k.nama_kelas OR s.kelas_saat_ini = k.id::varchar) AND k.tenant_id = s.tenant_id
                WHERE s.tenant_id = :tenant_id 
                  AND s.status_siswa = 'Aktif'
            ";

            $params = [
                ':tenant_id' => $tenantId,
                ':ekskul_id' => $ekskulId ?: 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12',
                ':ta_id' => $tahunAjaranId ?: 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12',
                ':semester' => $semester
            ];

            if (!empty($kelasId)) {
                $sql .= " AND (s.kelas_saat_ini = :kelas_id OR k.id::varchar = :kelas_id)";
                $params[':kelas_id'] = $kelasId;
            }

            if (!empty($q)) {
                $sql .= " AND (s.nama_lengkap ILIKE :q OR s.nisn ILIKE :q OR s.nis ILIKE :q)";
                $params[':q'] = "%{$q}%";
            }

            $sql .= " ORDER BY s.nama_lengkap ASC LIMIT 50";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $this->jsonResponse(['success' => true, 'data' => $rows], 200);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function apiAddAnggota(): void {
        $this->validateCsrfToken();
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['success' => false, 'error' => 'Tenant ID tidak terdeteksi.'], 400);
            return;
        }

        $input = $this->getJsonInput();
        $ekskulId = $input['ekskul_id'] ?? '';
        $tahunAjaranId = $input['tahun_ajaran_id'] ?? '';
        $semester = $input['semester'] ?? 'Ganjil';
        $siswaIds = $input['siswa_ids'] ?? [];
        $jabatan = $input['jabatan'] ?? 'Anggota';

        if (empty($ekskulId) || empty($tahunAjaranId) || empty($siswaIds)) {
            $this->jsonResponse(['success' => false, 'error' => 'Pilih ekskul, tahun ajaran, dan minimal 1 siswa.'], 422);
            return;
        }

        try {
            $count = $this->model->addAnggotaBulk($tenantId, $ekskulId, (array)$siswaIds, $tahunAjaranId, $semester, $jabatan);
            $this->jsonResponse(['success' => true, 'message' => "Berhasil mendaftarkan {$count} anggota baru."], 200);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function apiRemoveAnggota(): void {
        $this->validateCsrfToken();
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['success' => false, 'error' => 'Tenant ID tidak terdeteksi.'], 400);
            return;
        }

        $input = $this->getJsonInput();
        $id = $input['id'] ?? ($_GET['id'] ?? '');
        if (empty($id)) {
            $this->jsonResponse(['success' => false, 'error' => 'ID anggota tidak valid.'], 422);
            return;
        }

        try {
            $this->model->removeAnggota($tenantId, $id);
            $this->jsonResponse(['success' => true, 'message' => 'Anggota berhasil dikeluarkan dari ekskul.'], 200);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function apiExportAnggota(): void {
        $tenantId = $this->getSecureTenantId();
        $ekskulId = $_GET['ekskul_id'] ?? '';
        $tahunAjaranId = $_GET['tahun_ajaran_id'] ?? null;
        $semester = $_GET['semester'] ?? 'Ganjil';

        if (!$tenantId || empty($ekskulId)) {
            http_response_code(400);
            echo "Parameter tidak lengkap.";
            exit;
        }

        $ekskul = $this->model->getEkskulById($tenantId, $ekskulId);
        $namaEkskul = $ekskul['nama_ekskul'] ?? 'Ekstrakurikuler';
        $members = $this->model->getAnggotaEkskul($tenantId, $ekskulId, $tahunAjaranId, $semester);

        $filename = "Daftar_Anggota_" . preg_replace('/[^a-zA-Z0-9_-]/', '_', $namaEkskul) . "_" . date('Ymd_His') . ".xlsx";

        $rows = [];
        $rows[] = [
            '<style bgcolor="#0f172a" color="#ffffff"><b>NO</b></style>',
            '<style bgcolor="#0f172a" color="#ffffff"><b>NAMA LENGKAP</b></style>',
            '<style bgcolor="#0f172a" color="#ffffff"><b>NISN</b></style>',
            '<style bgcolor="#0f172a" color="#ffffff"><b>NIS</b></style>',
            '<style bgcolor="#0f172a" color="#ffffff"><b>KELAS</b></style>',
            '<style bgcolor="#0f172a" color="#ffffff"><b>JENIS KELAMIN</b></style>',
            '<style bgcolor="#0f172a" color="#ffffff"><b>JABATAN</b></style>',
            '<style bgcolor="#0f172a" color="#ffffff"><b>STATUS</b></style>'
        ];

        $no = 1;
        foreach ($members as $m) {
            $nisn = !empty($m['nisn']) ? (string)$m['nisn'] : '-';
            $nis = !empty($m['nis']) ? (string)$m['nis'] : '-';
            $rows[] = [
                $no++,
                (string)$m['nama_lengkap'],
                $nisn !== '-' ? \Shuchkin\SimpleXLSXGen::raw($nisn) : '-',
                $nis !== '-' ? \Shuchkin\SimpleXLSXGen::raw($nis) : '-',
                (string)($m['nama_kelas'] ?: '-'),
                $m['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan',
                (string)($m['jabatan'] ?: 'Anggota'),
                (string)($m['status_keanggotaan'] ?: 'Aktif')
            ];
        }

        if (ob_get_level()) {
            ob_end_clean();
        }

        $xlsx = \Shuchkin\SimpleXLSXGen::fromArray($rows, 'Anggota Ekskul');
        $xlsx->downloadAs($filename);
        exit;
    }

    /* ═══════════════════════════════════════════════════════════════════════
       API: JURNAL KEGIATAN EKSKUL
       ═══════════════════════════════════════════════════════════════════════ */

    public function apiGetJurnal(): void {
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['success' => false, 'error' => 'Tenant ID tidak terdeteksi.'], 400);
            return;
        }

        $ekskulId = $_GET['ekskul_id'] ?? '';
        $tahunAjaranId = $_GET['tahun_ajaran_id'] ?? null;
        $semester = $_GET['semester'] ?? null;

        if (empty($ekskulId)) {
            $this->jsonResponse(['success' => true, 'data' => []], 200);
            return;
        }

        try {
            $data = $this->model->getJurnalEkskul($tenantId, $ekskulId, $tahunAjaranId, $semester);
            $this->jsonResponse(['success' => true, 'data' => $data], 200);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function apiSaveJurnal(): void {
        $this->validateCsrfToken();
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['success' => false, 'error' => 'Tenant ID tidak terdeteksi.'], 400);
            return;
        }

        $input = $this->getJsonInput();
        $ekskulId = $input['ekskul_id'] ?? '';
        $materi = trim($input['materi_kegiatan'] ?? '');
        $tanggal = trim($input['tanggal_kegiatan'] ?? '');

        if (empty($ekskulId) || empty($materi) || empty($tanggal)) {
            $this->jsonResponse(['success' => false, 'error' => 'Materi dan tanggal kegiatan wajib diisi.'], 422);
            return;
        }

        try {
            $id = $this->model->saveJurnal($tenantId, $input);
            $this->jsonResponse([
                'success' => true,
                'message' => empty($input['id']) ? 'Jurnal kegiatan berhasil dicatat.' : 'Jurnal kegiatan berhasil diperbarui.',
                'id' => $id
            ], 200);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function apiDeleteJurnal(): void {
        $this->validateCsrfToken();
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['success' => false, 'error' => 'Tenant ID tidak terdeteksi.'], 400);
            return;
        }

        $input = $this->getJsonInput();
        $id = $input['id'] ?? ($_GET['id'] ?? '');
        if (empty($id)) {
            $this->jsonResponse(['success' => false, 'error' => 'ID jurnal tidak valid.'], 422);
            return;
        }

        try {
            $this->model->deleteJurnal($tenantId, $id);
            $this->jsonResponse(['success' => true, 'message' => 'Catatan jurnal kegiatan berhasil dihapus.'], 200);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /* ═══════════════════════════════════════════════════════════════════════
       API: PENILAIAN EKSKUL & E-RAPOR
       ═══════════════════════════════════════════════════════════════════════ */

    public function apiGetNilai(): void {
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['success' => false, 'error' => 'Tenant ID tidak terdeteksi.'], 400);
            return;
        }

        $ekskulId = $_GET['ekskul_id'] ?? '';
        $tahunAjaranId = $_GET['tahun_ajaran_id'] ?? '';
        $semester = $_GET['semester'] ?? 'Ganjil';

        if (empty($ekskulId) || empty($tahunAjaranId)) {
            $this->jsonResponse(['success' => true, 'data' => []], 200);
            return;
        }

        try {
            $data = $this->model->getNilaiEkskul($tenantId, $ekskulId, $tahunAjaranId, $semester);
            $lock = $this->model->getLockStatus($tenantId, $ekskulId, $tahunAjaranId, $semester);
            $this->jsonResponse([
                'success' => true,
                'data' => $data,
                'lock' => $lock
            ], 200);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function apiSaveNilai(): void {
        $this->validateCsrfToken();
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['success' => false, 'error' => 'Tenant ID tidak terdeteksi.'], 400);
            return;
        }

        $input = $this->getJsonInput();
        $ekskulId = $input['ekskul_id'] ?? '';
        $tahunAjaranId = $input['tahun_ajaran_id'] ?? '';
        $semester = $input['semester'] ?? 'Ganjil';
        $grades = $input['grades'] ?? [];

        if (empty($ekskulId) || empty($tahunAjaranId)) {
            $this->jsonResponse(['success' => false, 'error' => 'Pilih ekskul dan tahun ajaran.'], 422);
            return;
        }

        // Cek apakah nilai terkunci
        $lock = $this->model->getLockStatus($tenantId, $ekskulId, $tahunAjaranId, $semester);
        if ($lock['lock_nilai']) {
            $this->jsonResponse(['success' => false, 'error' => 'Penilaian ekskul untuk semester ini telah dikunci.'], 403);
            return;
        }

        try {
            $saved = $this->model->saveNilaiBatch($tenantId, $ekskulId, $tahunAjaranId, $semester, (array)$grades);
            $this->jsonResponse(['success' => true, 'message' => "Berhasil menyimpan penilaian untuk {$saved} siswa."], 200);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function apiExportNilai(): void {
        $tenantId = $this->getSecureTenantId();
        $ekskulId = $_GET['ekskul_id'] ?? '';
        $tahunAjaranId = $_GET['tahun_ajaran_id'] ?? '';
        $semester = $_GET['semester'] ?? 'Ganjil';

        if (!$tenantId || empty($ekskulId) || empty($tahunAjaranId)) {
            http_response_code(400);
            echo "Parameter tidak lengkap.";
            exit;
        }

        $ekskul = $this->model->getEkskulById($tenantId, $ekskulId);
        $namaEkskul = $ekskul['nama_ekskul'] ?? 'Ekstrakurikuler';
        $nilaiList = $this->model->getNilaiEkskul($tenantId, $ekskulId, $tahunAjaranId, $semester);

        $filename = "Format_Nilai_Ekskul_" . preg_replace('/[^a-zA-Z0-9_-]/', '_', $namaEkskul) . "_" . date('Ymd_His') . ".xlsx";

        $rows = [];
        $rows[] = [
            '<style bgcolor="#0f172a" color="#ffffff"><b>SISWA_ID</b></style>',
            '<style bgcolor="#0f172a" color="#ffffff"><b>NO</b></style>',
            '<style bgcolor="#0f172a" color="#ffffff"><b>NAMA LENGKAP</b></style>',
            '<style bgcolor="#0f172a" color="#ffffff"><b>NISN</b></style>',
            '<style bgcolor="#0f172a" color="#ffffff"><b>NIS</b></style>',
            '<style bgcolor="#0f172a" color="#ffffff"><b>KELAS</b></style>',
            '<style bgcolor="#0f172a" color="#ffffff"><b>PREDIKAT (A/B/C/D)</b></style>',
            '<style bgcolor="#0f172a" color="#ffffff"><b>NILAI ANGKA</b></style>',
            '<style bgcolor="#0f172a" color="#ffffff"><b>KETERANGAN</b></style>'
        ];

        $no = 1;
        foreach ($nilaiList as $n) {
            $nisn = !empty($n['nisn']) ? (string)$n['nisn'] : '-';
            $nis = !empty($n['nis']) ? (string)$n['nis'] : '-';
            $rows[] = [
                \Shuchkin\SimpleXLSXGen::raw((string)$n['siswa_id']),
                $no++,
                (string)$n['nama_lengkap'],
                $nisn !== '-' ? \Shuchkin\SimpleXLSXGen::raw($nisn) : '-',
                $nis !== '-' ? \Shuchkin\SimpleXLSXGen::raw($nis) : '-',
                (string)($n['nama_kelas'] ?: '-'),
                (string)($n['predikat'] ?: 'A'),
                $n['nilai_angka'] !== null ? (float)$n['nilai_angka'] : '',
                (string)($n['keterangan'] ?: '')
            ];
        }

        if (ob_get_level()) {
            ob_end_clean();
        }

        $xlsx = \Shuchkin\SimpleXLSXGen::fromArray($rows, 'Format Nilai');
        $xlsx->downloadAs($filename);
        exit;
    }

    public function apiImportNilai(): void {
        $this->validateCsrfToken();
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['success' => false, 'error' => 'Tenant ID tidak terdeteksi.'], 400);
            return;
        }

        $ekskulId = $_POST['ekskul_id'] ?? '';
        $tahunAjaranId = $_POST['tahun_ajaran_id'] ?? '';
        $semester = $_POST['semester'] ?? 'Ganjil';

        // finfo_file Magic Bytes & MIME validated via SecurityUploadHelper
        $val = \App\Helpers\SecurityUploadHelper::validateFile($_FILES['file_nilai'] ?? [], ['xlsx', 'csv', 'xls'], 10 * 1024 * 1024);
        if (empty($ekskulId) || empty($tahunAjaranId) || !$val['valid']) {
            $this->jsonResponse(['success' => false, 'error' => !$val['valid'] ? 'Berkas nilai tidak valid: ' . $val['error'] : 'Ekskul dan tahun ajaran wajib dipilih.'], 422);
            return;
        }

        // finfo_file validated
        $file = $_FILES['file_nilai']['tmp_name'];
        $fileName = strtolower($_FILES['file_nilai']['name'] ?? '');
        $grades = [];

        // Check if file is XLSX
        if ($val['extension'] === 'xlsx' || str_ends_with($fileName, '.xlsx')) {
            if ($xlsx = \Shuchkin\SimpleXLSX::parseFile($file)) {
                $rows = $xlsx->rows();
                // Skip header (row 0)
                for ($i = 1; $i < count($rows); $i++) {
                    $row = $rows[$i];
                    if (empty($row[0])) continue;
                    $siswaId = trim((string)$row[0]);
                    $predikat = !empty($row[6]) ? strtoupper(trim((string)$row[6])) : 'A';
                    $nilaiAngka = isset($row[7]) && is_numeric(trim((string)$row[7])) ? (float)trim((string)$row[7]) : null;
                    $keterangan = !empty($row[8]) ? trim((string)$row[8]) : '';

                    $grades[] = [
                        'siswa_id' => $siswaId,
                        'predikat' => in_array($predikat, ['A', 'B', 'C', 'D'], true) ? $predikat : 'A',
                        'nilai_angka' => $nilaiAngka,
                        'keterangan' => $keterangan
                    ];
                }
            } else {
                $this->jsonResponse(['success' => false, 'error' => 'Gagal membaca berkas XLSX: ' . \Shuchkin\SimpleXLSX::parseError()], 400);
                return;
            }
        } else {
            // Fallback CSV parser
            $handle = fopen($file, 'r');
            if (!$handle) {
                $this->jsonResponse(['success' => false, 'error' => 'Gagal membaca file upload.'], 400);
                return;
            }

            $header = fgetcsv($handle, 1000, ',');
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                if (empty($row[0])) continue;
                $siswaId = trim($row[0]);
                $predikat = !empty($row[6]) ? strtoupper(trim($row[6])) : 'A';
                $nilaiAngka = isset($row[7]) && is_numeric(trim($row[7])) ? (float)trim($row[7]) : null;
                $keterangan = !empty($row[8]) ? trim($row[8]) : '';

                $grades[] = [
                    'siswa_id' => $siswaId,
                    'predikat' => in_array($predikat, ['A', 'B', 'C', 'D'], true) ? $predikat : 'A',
                    'nilai_angka' => $nilaiAngka,
                    'keterangan' => $keterangan
                ];
            }
            fclose($handle);
        }

        if (empty($grades)) {
            $this->jsonResponse(['success' => false, 'error' => 'Tidak ada baris data nilai siswa yang valid dalam file.'], 422);
            return;
        }

        try {
            $saved = $this->model->saveNilaiBatch($tenantId, $ekskulId, $tahunAjaranId, $semester, $grades);
            $this->jsonResponse(['success' => true, 'message' => "Berhasil mengimpor {$saved} nilai ekskul siswa."], 200);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /* ═══════════════════════════════════════════════════════════════════════
       API: PENGUNCIAN STATUS (LOCK)
       ═══════════════════════════════════════════════════════════════════════ */

    public function apiToggleLock(): void {
        $this->validateCsrfToken();
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['success' => false, 'error' => 'Tenant ID tidak terdeteksi.'], 400);
            return;
        }

        $input = $this->getJsonInput();
        $ekskulId = $input['ekskul_id'] ?? '';
        $tahunAjaranId = $input['tahun_ajaran_id'] ?? '';
        $semester = $input['semester'] ?? 'Ganjil';
        $type = $input['type'] ?? 'anggota'; // 'anggota' or 'nilai'
        $userName = $_SESSION['nama_lengkap'] ?? 'Administrator';

        if (empty($ekskulId) || empty($tahunAjaranId)) {
            $this->jsonResponse(['success' => false, 'error' => 'Pilih ekskul dan tahun ajaran.'], 422);
            return;
        }

        try {
            $newLock = $this->model->toggleLock($tenantId, $ekskulId, $tahunAjaranId, $semester, $type, $userName);
            $this->jsonResponse([
                'success' => true,
                'message' => 'Status penguncian berhasil diubah.',
                'lock' => $newLock
            ], 200);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /* ═══════════════════════════════════════════════════════════════════════
       BACKWARD COMPATIBILITY CONTROLLER METHODS FOR LEGACY ROUTES
       ═══════════════════════════════════════════════════════════════════════ */

    public function store(): void { $this->validateCsrfToken(); $this->apiSaveMasterEkskul(); }
    public function update(): void { $this->validateCsrfToken(); $this->apiSaveMasterEkskul(); }
    public function toggleStatus(): void { $this->validateCsrfToken(); $this->apiToggleMasterEkskul(); }
    public function storePembina(): void { $this->validateCsrfToken(); $this->apiSavePembina(); }
    public function updatePembina(): void { $this->validateCsrfToken(); $this->apiSavePembina(); }
    public function togglePembinaStatus(): void { $this->validateCsrfToken(); $this->apiTogglePembina(); }
    public function addMembers(): void { $this->validateCsrfToken(); $this->apiAddAnggota(); }
    public function removeMember(): void { $this->validateCsrfToken(); $this->apiRemoveAnggota(); }
    public function saveGrades(): void { $this->validateCsrfToken(); $this->apiSaveNilai(); }
    public function toggleLockAnggota(): void { 
        $this->validateCsrfToken();
        $_POST['type'] = 'anggota';
        $this->apiToggleLock(); 
    }
    public function toggleLockNilai(): void { 
        $this->validateCsrfToken();
        $_POST['type'] = 'nilai';
        $this->apiToggleLock(); 
    }
    public function exportGrades(): void { $this->apiExportNilai(); }
    public function importGrades(): void { $this->apiImportNilai(); }
    public function exportMembers(): void { $this->apiExportAnggota(); }

    public function getEkskulApi(): void { $this->apiGetMasterEkskul(); }
    public function getPrestasiApi(): void {
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['success' => false, 'error' => 'Tenant ID tidak terdeteksi'], 400);
            return;
        }
        $siswaId = $_GET['siswa_id'] ?? '';
        $prestasiList = PrestasiSiswaModel::getPrestasiBySiswa($tenantId, $siswaId);
        $this->jsonResponse(['success' => true, 'data' => $prestasiList], 200);
    }
}
