<?php

namespace App\Modules\Alumni\Controllers;

use App\Core\BaseController;
use App\Core\SessionManager;
use App\Config\Database;
use PDO;

/**
 * TracerModuleController
 *
 * Menangani modul Tracer Study: Riwayat Kuliah & Riwayat Pekerjaan Alumni.
 * 
 * Standar Arsitektur:
 * - PostgreSQL Multi-Schema: Skema `tracer`, `siswa`, `pdss`, `core`.
 * - Full CRUD: Tambah (Store), Ubah (Update), Hapus (Delete), dan Tampil (List/Get).
 * - Dukungan Ganda: Siswa terdaftar di sistem (buku induk) & Alumni lawas di luar database (manual input).
 * - Dukungan Kampus: Kampus terdaftar di PDSS & Kampus Swasta / Luar Negeri manual.
 */
class TracerModuleController extends BaseController {

    public function __construct() {
        parent::__construct();
        SessionManager::requireLogin();
    }

    private function getTenantContext(): ?string {
        $tenantId = SessionManager::getTenantId();
        if (empty($tenantId) || $tenantId === '00000000-0000-0000-0000-000000000000') {
            if (!empty($_GET['tenant_id']) && $_GET['tenant_id'] !== '00000000-0000-0000-0000-000000000000') {
                $tenantId = trim($_GET['tenant_id']);
            } elseif (!empty($_POST['tenant_id']) && $_POST['tenant_id'] !== '00000000-0000-0000-0000-000000000000') {
                $tenantId = trim($_POST['tenant_id']);
            } else {
                $body = $this->getJsonInput();
                if (!empty($body['tenant_id']) && $body['tenant_id'] !== '00000000-0000-0000-0000-000000000000') {
                    $tenantId = trim($body['tenant_id']);
                }
            }
        }

        if (empty($tenantId) || $tenantId === '00000000-0000-0000-0000-000000000000') {
            try {
                $db = Database::getConnection();
                $stmtDefault = $db->query("SELECT id FROM core.tenants WHERE status = 'active' ORDER BY created_at ASC LIMIT 1");
                $tenantId = $stmtDefault->fetchColumn() ?: null;
            } catch (\Throwable $e) {
                $tenantId = null;
            }
        }

        return $tenantId;
    }

    // =========================================================================
    // PAGE: Halaman Tracer Study (View)
    // GET /tracer-study
    // =========================================================================
    public function index(): void {
        $roleName = $_SESSION['role_name'] ?? '';
        $siswaId  = $_SESSION['user_id']   ?? '';

        if ($roleName === 'siswa') {
            $status = $this->getSiswaStatus($siswaId);
            if ($status !== 'Lulus') {
                http_response_code(403);
                $this->render('error_403', [
                    'title'   => '403 Akses Ditolak',
                    'message' => 'Halaman Tracer Study hanya tersedia bagi siswa yang telah dinyatakan Lulus.'
                ]);
                return;
            }
        }

        $tenantId = $this->getTenantContext();

        $this->render('alumni/tracer_study', [
            'title'             => 'Tracer Study / Portofolio Alumni',
            'user_role'         => $roleName,
            'user_nama'         => $_SESSION['nama_lengkap'] ?? 'Alumni',
            'tenant_id'         => $tenantId,
            'riwayat_kuliah'    => [],
            'riwayat_pekerjaan' => []
        ]);
    }

    // =========================================================================
    // API: Get Riwayat Kuliah (GET)
    // GET /api/v1/tracer/kuliah
    // =========================================================================
    public function apiGetKuliah(): void {
        $roleName = $_SESSION['role_name'] ?? '';
        $siswaId  = $_SESSION['user_id']   ?? '';
        $tenantId = $this->getTenantContext();

        try {
            $db = Database::getConnection();

            if ($roleName === 'siswa') {
                $stmt = $db->prepare("
                    SELECT 
                        rk.id, rk.tenant_id, rk.siswa_id, rk.nama_alumni, rk.nisn, rk.kampus_id, rk.prodi_id, rk.jalur_masuk_id,
                        rk.nama_kampus, rk.nama_prodi, rk.fakultas, rk.jenjang, rk.tahun_masuk, rk.tahun_lulus, rk.status_kuliah,
                        rk.is_manual, rk.is_kampus_swasta, rk.created_at,
                        COALESCE(s.nama_lengkap, rk.nama_alumni, 'Alumni') AS nama_lengkap,
                        COALESCE(s.nisn, rk.nisn, '') AS nisn_display,
                        COALESCE(s.jurusan, '') AS jurusan_sekolah,
                        COALESCE(jm.nama_jalur, rk.jalur_masuk, '') AS nama_jalur,
                        COALESCE(mk.nama_kampus, rk.nama_kampus) AS nama_kampus_display,
                        COALESCE(mp.program_studi, mp.nama_prodi, rk.nama_prodi) AS nama_prodi_display
                    FROM tracer.riwayat_kuliah rk
                    LEFT JOIN siswa.siswa s ON rk.siswa_id = s.id
                    LEFT JOIN pdss.master_kampus mk ON rk.kampus_id = mk.id
                    LEFT JOIN pdss.master_kampus_prodi mp ON rk.prodi_id = mp.id
                    LEFT JOIN bk.master_jalur_masuk jm ON rk.jalur_masuk_id::text = jm.id::text
                    WHERE rk.siswa_id = ?
                    ORDER BY rk.tahun_masuk DESC, rk.created_at DESC
                ");
                $stmt->execute([$siswaId]);
            } elseif (!empty($tenantId)) {
                $targetId = $_GET['siswa_id'] ?? '';
                if ($targetId) {
                    $stmt = $db->prepare("
                        SELECT 
                            rk.id, rk.tenant_id, rk.siswa_id, rk.nama_alumni, rk.nisn, rk.kampus_id, rk.prodi_id, rk.jalur_masuk_id,
                            rk.nama_kampus, rk.nama_prodi, rk.fakultas, rk.jenjang, rk.tahun_masuk, rk.tahun_lulus, rk.status_kuliah,
                            rk.is_manual, rk.is_kampus_swasta, rk.created_at,
                            COALESCE(s.nama_lengkap, rk.nama_alumni, 'Alumni') AS nama_lengkap,
                            COALESCE(s.nisn, rk.nisn, '') AS nisn_display,
                            COALESCE(s.jurusan, '') AS jurusan_sekolah,
                            COALESCE(jm.nama_jalur, rk.jalur_masuk, '') AS nama_jalur,
                            COALESCE(mk.nama_kampus, rk.nama_kampus) AS nama_kampus_display,
                            COALESCE(mp.program_studi, mp.nama_prodi, rk.nama_prodi) AS nama_prodi_display
                        FROM tracer.riwayat_kuliah rk
                        LEFT JOIN siswa.siswa s ON rk.siswa_id = s.id
                        LEFT JOIN pdss.master_kampus mk ON rk.kampus_id = mk.id
                        LEFT JOIN pdss.master_kampus_prodi mp ON rk.prodi_id = mp.id
                        LEFT JOIN bk.master_jalur_masuk jm ON rk.jalur_masuk_id::text = jm.id::text
                        WHERE rk.tenant_id = ? AND rk.siswa_id = ?
                        ORDER BY rk.tahun_masuk DESC, rk.created_at DESC
                    ");
                    $stmt->execute([$tenantId, $targetId]);
                } else {
                    $stmt = $db->prepare("
                        SELECT 
                            rk.id, rk.tenant_id, rk.siswa_id, rk.nama_alumni, rk.nisn, rk.kampus_id, rk.prodi_id, rk.jalur_masuk_id,
                            rk.nama_kampus, rk.nama_prodi, rk.fakultas, rk.jenjang, rk.tahun_masuk, rk.tahun_lulus, rk.status_kuliah,
                            rk.is_manual, rk.is_kampus_swasta, rk.created_at,
                            COALESCE(s.nama_lengkap, rk.nama_alumni, 'Alumni') AS nama_lengkap,
                            COALESCE(s.nisn, rk.nisn, '') AS nisn_display,
                            COALESCE(s.jurusan, '') AS jurusan_sekolah,
                            COALESCE(jm.nama_jalur, rk.jalur_masuk, '') AS nama_jalur,
                            COALESCE(mk.nama_kampus, rk.nama_kampus) AS nama_kampus_display,
                            COALESCE(mp.program_studi, mp.nama_prodi, rk.nama_prodi) AS nama_prodi_display
                        FROM tracer.riwayat_kuliah rk
                        LEFT JOIN siswa.siswa s ON rk.siswa_id = s.id
                        LEFT JOIN pdss.master_kampus mk ON rk.kampus_id = mk.id
                        LEFT JOIN pdss.master_kampus_prodi mp ON rk.prodi_id = mp.id
                        LEFT JOIN bk.master_jalur_masuk jm ON rk.jalur_masuk_id::text = jm.id::text
                        WHERE rk.tenant_id = ?
                        ORDER BY rk.created_at DESC, rk.tahun_masuk DESC
                    ");
                    $stmt->execute([$tenantId]);
                }
            } else {
                $stmt = $db->query("
                    SELECT 
                        rk.id, rk.tenant_id, rk.siswa_id, rk.nama_alumni, rk.nisn, rk.kampus_id, rk.prodi_id, rk.jalur_masuk_id,
                        rk.nama_kampus, rk.nama_prodi, rk.fakultas, rk.jenjang, rk.tahun_masuk, rk.tahun_lulus, rk.status_kuliah,
                        rk.is_manual, rk.is_kampus_swasta, rk.created_at,
                        COALESCE(s.nama_lengkap, rk.nama_alumni, 'Alumni') AS nama_lengkap,
                        COALESCE(s.nisn, rk.nisn, '') AS nisn_display,
                        COALESCE(s.jurusan, '') AS jurusan_sekolah,
                        COALESCE(jm.nama_jalur, rk.jalur_masuk, '') AS nama_jalur,
                        COALESCE(mk.nama_kampus, rk.nama_kampus) AS nama_kampus_display,
                        COALESCE(mp.program_studi, mp.nama_prodi, rk.nama_prodi) AS nama_prodi_display,
                        t.nama_sekolah
                    FROM tracer.riwayat_kuliah rk
                    LEFT JOIN siswa.siswa s ON rk.siswa_id = s.id
                    LEFT JOIN pdss.master_kampus mk ON rk.kampus_id = mk.id
                    LEFT JOIN pdss.master_kampus_prodi mp ON rk.prodi_id = mp.id
                    LEFT JOIN bk.master_jalur_masuk jm ON rk.jalur_masuk_id::text = jm.id::text
                    LEFT JOIN core.tenants t ON rk.tenant_id = t.id
                    ORDER BY rk.created_at DESC
                    LIMIT 200
                ");
            }

            $this->jsonResponse([
                'success' => true,
                'data'    => $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []
            ]);
        } catch (\Throwable $e) {
            error_log('[TracerModuleController::apiGetKuliah] ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'error' => 'Gagal memuat riwayat kuliah: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // API: Simpan Riwayat Kuliah Baru (CREATE)
    // POST /api/v1/tracer/kuliah
    // =========================================================================
    public function storeKuliah(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed.'], 405);
            return;
        }

        $roleName = $_SESSION['role_name'] ?? '';
        $siswaId  = $_SESSION['user_id']   ?? '';
        $tenantId = $this->getTenantContext();
        $body     = $this->getJsonInput();

        $isManual = !empty($body['is_manual']);
        if ($roleName === 'siswa') {
            if (empty($siswaId)) {
                $this->jsonResponse(['error' => 'Session tidak valid.'], 401);
                return;
            }
            $isManual = false;
        } else {
            // Role Admin / BK
            $selectedSiswaId = $this->sanitizeStr($body['siswa_id'] ?? '');
            $manualNamaAlumni = $this->sanitizeStr($body['nama_alumni'] ?? '');
            if (!$isManual && empty($selectedSiswaId)) {
                $this->jsonResponse(['error' => 'Silakan pilih siswa dari sistem atau aktifkan "Input Alumni Luar Sistem".'], 422);
                return;
            }
            if ($isManual && empty($manualNamaAlumni)) {
                $this->jsonResponse(['error' => 'Nama lengkap alumni wajib diisi.'], 422);
                return;
            }
            $siswaId = $isManual ? null : $selectedSiswaId;
        }

        $namaAlumni      = $this->sanitizeStr($body['nama_alumni'] ?? '');
        $nisn            = $this->sanitizeStr($body['nisn'] ?? '');
        $namaKampus      = $this->sanitizeStr($body['nama_kampus'] ?? '');
        $namaProdi       = $this->sanitizeStr($body['jurusan'] ?? $body['nama_prodi'] ?? '');
        $fakultas        = $this->sanitizeStr($body['fakultas'] ?? '');
        $jenjang         = $this->sanitizeStr($body['jenjang'] ?? 'S1');
        $kampusId        = !empty($body['kampus_id']) ? $this->sanitizeStr($body['kampus_id']) : null;
        $prodiId         = !empty($body['prodi_id']) ? $this->sanitizeStr($body['prodi_id']) : (!empty($body['kampus_prodi_id']) ? $this->sanitizeStr($body['kampus_prodi_id']) : null);
        $jalurMasukId    = !empty($body['jalur_masuk_id']) ? $this->sanitizeStr($body['jalur_masuk_id']) : null;
        $tahunMasuk      = (int)($body['tahun_masuk'] ?? date('Y'));
        $tahunLulus      = !empty($body['tahun_lulus']) ? (int)$body['tahun_lulus'] : null;
        $statusKuliah    = $this->sanitizeStr($body['status_kuliah'] ?? 'Aktif');
        $isKampusSwasta  = empty($kampusId);

        if (empty($namaKampus)) {
            $this->jsonResponse(['error' => 'Nama perguruan tinggi / kampus wajib diisi.'], 422);
            return;
        }

        try {
            $db = Database::getConnection();
            $db->beginTransaction();

            $stmt = $db->prepare("
                INSERT INTO tracer.riwayat_kuliah
                    (id, tenant_id, siswa_id, nama_alumni, nisn, kampus_id, prodi_id, jalur_masuk_id, nama_kampus, nama_prodi, fakultas, jenjang, tahun_masuk, tahun_lulus, status_kuliah, is_manual, is_kampus_swasta, created_at, updated_at)
                VALUES
                    (gen_random_uuid(), :tenant_id, :siswa_id, :nama_alumni, :nisn, :kampus_id, :prodi_id, :jalur_masuk_id, :nama_kampus, :nama_prodi, :fakultas, :jenjang, :tahun_masuk, :tahun_lulus, :status_kuliah, :is_manual, :is_kampus_swasta, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                RETURNING id
            ");

            $stmt->execute([
                'tenant_id'        => $tenantId,
                'siswa_id'         => $siswaId ?: null,
                'nama_alumni'      => $namaAlumni ?: null,
                'nisn'             => $nisn ?: null,
                'kampus_id'        => $kampusId ?: null,
                'prodi_id'         => $prodiId ?: null,
                'jalur_masuk_id'   => $jalurMasukId ?: null,
                'nama_kampus'      => $namaKampus,
                'nama_prodi'       => $namaProdi ?: null,
                'fakultas'         => $fakultas ?: null,
                'jenjang'          => $jenjang,
                'tahun_masuk'      => $tahunMasuk,
                'tahun_lulus'      => $tahunLulus,
                'status_kuliah'    => $statusKuliah,
                'is_manual'        => $isManual ? 'TRUE' : 'FALSE',
                'is_kampus_swasta' => $isKampusSwasta ? 'TRUE' : 'FALSE'
            ]);

            $newId = $stmt->fetchColumn();
            $db->commit();

            $this->jsonResponse([
                'success' => true,
                'id'      => $newId,
                'message' => 'Riwayat kuliah berhasil disimpan.'
            ]);
        } catch (\Throwable $e) {
            if (isset($db) && $db->inTransaction()) $db->rollBack();
            error_log('[TracerModuleController::storeKuliah] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal menyimpan riwayat kuliah: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // API: Update Riwayat Kuliah (UPDATE)
    // POST /api/v1/tracer/kuliah/update
    // =========================================================================
    public function updateKuliah(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed.'], 405);
            return;
        }

        $roleName = $_SESSION['role_name'] ?? '';
        $tenantId = $this->getTenantContext();
        $body     = $this->getJsonInput();

        $id = $this->sanitizeStr($body['id'] ?? '');
        if (empty($id)) {
            $this->jsonResponse(['error' => 'ID data riwayat kuliah tidak valid.'], 422);
            return;
        }

        $namaAlumni      = $this->sanitizeStr($body['nama_alumni'] ?? '');
        $nisn            = $this->sanitizeStr($body['nisn'] ?? '');
        $namaKampus      = $this->sanitizeStr($body['nama_kampus'] ?? '');
        $namaProdi       = $this->sanitizeStr($body['jurusan'] ?? $body['nama_prodi'] ?? '');
        $fakultas        = $this->sanitizeStr($body['fakultas'] ?? '');
        $jenjang         = $this->sanitizeStr($body['jenjang'] ?? 'S1');
        $kampusId        = !empty($body['kampus_id']) ? $this->sanitizeStr($body['kampus_id']) : null;
        $prodiId         = !empty($body['prodi_id']) ? $this->sanitizeStr($body['prodi_id']) : (!empty($body['kampus_prodi_id']) ? $this->sanitizeStr($body['kampus_prodi_id']) : null);
        $jalurMasukId    = !empty($body['jalur_masuk_id']) ? $this->sanitizeStr($body['jalur_masuk_id']) : null;
        $tahunMasuk      = (int)($body['tahun_masuk'] ?? date('Y'));
        $tahunLulus      = !empty($body['tahun_lulus']) ? (int)$body['tahun_lulus'] : null;
        $statusKuliah    = $this->sanitizeStr($body['status_kuliah'] ?? 'Aktif');
        $isKampusSwasta  = empty($kampusId);

        if (empty($namaKampus)) {
            $this->jsonResponse(['error' => 'Nama perguruan tinggi / kampus wajib diisi.'], 422);
            return;
        }

        try {
            $db = Database::getConnection();

            // Verifikasi hak akses jika bukan super_admin
            if ($roleName !== 'super_admin') {
                $checkStmt = $db->prepare("SELECT id FROM tracer.riwayat_kuliah WHERE id = ? AND tenant_id = ?");
                $checkStmt->execute([$id, $tenantId]);
                if (!$checkStmt->fetch()) {
                    $this->jsonResponse(['error' => 'Data tidak ditemukan atau akses ditolak.'], 404);
                    return;
                }
            }

            $stmt = $db->prepare("
                UPDATE tracer.riwayat_kuliah
                SET nama_alumni = :nama_alumni,
                    nisn = :nisn,
                    kampus_id = :kampus_id,
                    prodi_id = :prodi_id,
                    jalur_masuk_id = :jalur_masuk_id,
                    nama_kampus = :nama_kampus,
                    nama_prodi = :nama_prodi,
                    fakultas = :fakultas,
                    jenjang = :jenjang,
                    tahun_masuk = :tahun_masuk,
                    tahun_lulus = :tahun_lulus,
                    status_kuliah = :status_kuliah,
                    is_kampus_swasta = :is_kampus_swasta,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");

            $stmt->execute([
                'id'               => $id,
                'nama_alumni'      => $namaAlumni ?: null,
                'nisn'             => $nisn ?: null,
                'kampus_id'        => $kampusId ?: null,
                'prodi_id'         => $prodiId ?: null,
                'jalur_masuk_id'   => $jalurMasukId,
                'nama_kampus'      => $namaKampus,
                'nama_prodi'       => $namaProdi ?: null,
                'fakultas'         => $fakultas ?: null,
                'jenjang'          => $jenjang,
                'tahun_masuk'      => $tahunMasuk,
                'tahun_lulus'      => $tahunLulus,
                'status_kuliah'    => $statusKuliah,
                'is_kampus_swasta' => $isKampusSwasta ? 'TRUE' : 'FALSE'
            ]);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Data riwayat kuliah berhasil diperbarui.'
            ]);
        } catch (\Throwable $e) {
            error_log('[TracerModuleController::updateKuliah] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal memperbarui riwayat kuliah: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // API: Hapus Riwayat Kuliah (DELETE)
    // POST /api/v1/tracer/kuliah/delete
    // =========================================================================
    public function deleteKuliah(): void {
        $roleName = $_SESSION['role_name'] ?? '';
        $tenantId = $this->getTenantContext();

        if (!in_array($roleName, ['super_admin', 'admin', 'operator', 'operator_sekolah', 'guru_bk'], true)) {
            $this->jsonResponse(['error' => 'Akses ditolak.'], 403);
            return;
        }

        $id = $this->sanitizeStr($_POST['id'] ?? $_GET['id'] ?? '');
        if (empty($id)) {
            $body = $this->getJsonInput();
            $id = $this->sanitizeStr($body['id'] ?? '');
        }

        if (empty($id)) {
            $this->jsonResponse(['error' => 'ID data riwayat kuliah tidak valid.'], 400);
            return;
        }

        try {
            $db = Database::getConnection();

            if ($roleName !== 'super_admin') {
                $stmt = $db->prepare("SELECT id FROM tracer.riwayat_kuliah WHERE id = ? AND tenant_id = ?");
                $stmt->execute([$id, $tenantId]);
                if (!$stmt->fetch()) {
                    $this->jsonResponse(['error' => 'Data tidak ditemukan atau akses ditolak.'], 404);
                    return;
                }
            }

            $stmt = $db->prepare("DELETE FROM tracer.riwayat_kuliah WHERE id = ?");
            $stmt->execute([$id]);

            $this->jsonResponse(['success' => true, 'message' => 'Riwayat kuliah berhasil dihapus.']);
        } catch (\Throwable $e) {
            error_log('[TracerModuleController::deleteKuliah] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal menghapus data: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // API: Get Riwayat Pekerjaan (GET)
    // GET /api/v1/tracer/pekerjaan
    // =========================================================================
    public function apiGetPekerjaan(): void {
        $roleName = $_SESSION['role_name'] ?? '';
        $siswaId  = $_SESSION['user_id']   ?? '';
        $tenantId = $this->getTenantContext();

        try {
            $db = Database::getConnection();

            if ($roleName === 'siswa') {
                $stmt = $db->prepare("
                    SELECT 
                        rp.id, rp.tenant_id, rp.siswa_id, rp.nama_alumni, rp.nisn,
                        rp.nama_perusahaan, COALESCE(rp.posisi_jabatan, rp.posisi, '') AS posisi_jabatan,
                        rp.jenis_instansi, rp.tahun_mulai, rp.tahun_selesai, rp.pendapatan_bulanan,
                        COALESCE(rp.status_kerja, 'Kontrak') AS status_kerja,
                        rp.is_manual, rp.created_at,
                        COALESCE(s.nama_lengkap, rp.nama_alumni, 'Alumni') AS nama_lengkap,
                        COALESCE(s.nisn, rp.nisn, '') AS nisn_display,
                        COALESCE(s.jurusan, '') AS jurusan_sekolah
                    FROM tracer.riwayat_pekerjaan rp
                    LEFT JOIN siswa.siswa s ON rp.siswa_id = s.id
                    WHERE rp.siswa_id = ?
                    ORDER BY rp.tahun_mulai DESC, rp.created_at DESC
                ");
                $stmt->execute([$siswaId]);
            } elseif (!empty($tenantId)) {
                $targetId = $_GET['siswa_id'] ?? '';
                if ($targetId) {
                    $stmt = $db->prepare("
                        SELECT 
                            rp.id, rp.tenant_id, rp.siswa_id, rp.nama_alumni, rp.nisn,
                            rp.nama_perusahaan, COALESCE(rp.posisi_jabatan, rp.posisi, '') AS posisi_jabatan,
                            rp.jenis_instansi, rp.tahun_mulai, rp.tahun_selesai, rp.pendapatan_bulanan,
                            COALESCE(rp.status_kerja, 'Kontrak') AS status_kerja,
                            rp.is_manual, rp.created_at,
                            COALESCE(s.nama_lengkap, rp.nama_alumni, 'Alumni') AS nama_lengkap,
                            COALESCE(s.nisn, rp.nisn, '') AS nisn_display,
                            COALESCE(s.jurusan, '') AS jurusan_sekolah
                        FROM tracer.riwayat_pekerjaan rp
                        LEFT JOIN siswa.siswa s ON rp.siswa_id = s.id
                        WHERE rp.tenant_id = ? AND rp.siswa_id = ?
                        ORDER BY rp.tahun_mulai DESC, rp.created_at DESC
                    ");
                    $stmt->execute([$tenantId, $targetId]);
                } else {
                    $stmt = $db->prepare("
                        SELECT 
                            rp.id, rp.tenant_id, rp.siswa_id, rp.nama_alumni, rp.nisn,
                            rp.nama_perusahaan, COALESCE(rp.posisi_jabatan, rp.posisi, '') AS posisi_jabatan,
                            rp.jenis_instansi, rp.tahun_mulai, rp.tahun_selesai, rp.pendapatan_bulanan,
                            COALESCE(rp.status_kerja, 'Kontrak') AS status_kerja,
                            rp.is_manual, rp.created_at,
                            COALESCE(s.nama_lengkap, rp.nama_alumni, 'Alumni') AS nama_lengkap,
                            COALESCE(s.nisn, rp.nisn, '') AS nisn_display,
                            COALESCE(s.jurusan, '') AS jurusan_sekolah
                        FROM tracer.riwayat_pekerjaan rp
                        LEFT JOIN siswa.siswa s ON rp.siswa_id = s.id
                        WHERE rp.tenant_id = ?
                        ORDER BY rp.created_at DESC, rp.tahun_mulai DESC
                    ");
                    $stmt->execute([$tenantId]);
                }
            } else {
                $stmt = $db->query("
                    SELECT 
                        rp.id, rp.tenant_id, rp.siswa_id, rp.nama_alumni, rp.nisn,
                        rp.nama_perusahaan, COALESCE(rp.posisi_jabatan, rp.posisi, '') AS posisi_jabatan,
                        rp.jenis_instansi, rp.tahun_mulai, rp.tahun_selesai, rp.pendapatan_bulanan,
                        COALESCE(rp.status_kerja, 'Kontrak') AS status_kerja,
                        rp.is_manual, rp.created_at,
                        COALESCE(s.nama_lengkap, rp.nama_alumni, 'Alumni') AS nama_lengkap,
                        COALESCE(s.nisn, rp.nisn, '') AS nisn_display,
                        COALESCE(s.jurusan, '') AS jurusan_sekolah,
                        t.nama_sekolah
                    FROM tracer.riwayat_pekerjaan rp
                    LEFT JOIN siswa.siswa s ON rp.siswa_id = s.id
                    LEFT JOIN core.tenants t ON rp.tenant_id = t.id
                    ORDER BY rp.created_at DESC
                    LIMIT 200
                ");
            }

            $this->jsonResponse([
                'success' => true,
                'data'    => $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []
            ]);
        } catch (\Throwable $e) {
            error_log('[TracerModuleController::apiGetPekerjaan] ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'error' => 'Gagal memuat riwayat pekerjaan: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // API: Simpan Riwayat Pekerjaan Baru (CREATE)
    // POST /api/v1/tracer/pekerjaan
    // =========================================================================
    public function storePekerjaan(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed.'], 405);
            return;
        }

        $roleName = $_SESSION['role_name'] ?? '';
        $siswaId  = $_SESSION['user_id']   ?? '';
        $tenantId = $this->getTenantContext();
        $body     = $this->getJsonInput();

        $isManual = !empty($body['is_manual']);
        if ($roleName === 'siswa') {
            if (empty($siswaId)) {
                $this->jsonResponse(['error' => 'Session tidak valid.'], 401);
                return;
            }
            $isManual = false;
        } else {
            $selectedSiswaId = $this->sanitizeStr($body['siswa_id'] ?? '');
            $manualNamaAlumni = $this->sanitizeStr($body['nama_alumni'] ?? '');
            if (!$isManual && empty($selectedSiswaId)) {
                $this->jsonResponse(['error' => 'Silakan pilih siswa dari sistem atau aktifkan "Input Alumni Luar Sistem".'], 422);
                return;
            }
            if ($isManual && empty($manualNamaAlumni)) {
                $this->jsonResponse(['error' => 'Nama lengkap alumni wajib diisi.'], 422);
                return;
            }
            $siswaId = $isManual ? null : $selectedSiswaId;
        }

        $namaAlumni        = $this->sanitizeStr($body['nama_alumni'] ?? '');
        $nisn              = $this->sanitizeStr($body['nisn'] ?? '');
        $namaPerusahaan    = $this->sanitizeStr($body['nama_perusahaan'] ?? '');
        $posisiJabatan     = $this->sanitizeStr($body['posisi_jabatan'] ?? $body['posisi'] ?? '');
        $jenisInstansi     = $this->sanitizeStr($body['jenis_instansi'] ?? 'Swasta');
        $pendapatanBulanan = $this->sanitizeStr($body['pendapatan_bulanan'] ?? '');
        $tahunMulai        = (int)($body['tahun_mulai'] ?? date('Y'));
        $tahunSelesai      = !empty($body['tahun_selesai']) ? (int)$body['tahun_selesai'] : null;
        $statusKerja       = $this->sanitizeStr($body['status_kerja'] ?? 'Kontrak');

        if (empty($namaPerusahaan)) {
            $this->jsonResponse(['error' => 'Nama perusahaan / tempat kerja wajib diisi.'], 422);
            return;
        }
        if (empty($posisiJabatan)) {
            $this->jsonResponse(['error' => 'Posisi / jabatan wajib diisi.'], 422);
            return;
        }

        try {
            $db = Database::getConnection();
            $db->beginTransaction();

            $stmt = $db->prepare("
                INSERT INTO tracer.riwayat_pekerjaan
                    (id, tenant_id, siswa_id, nama_alumni, nisn, nama_perusahaan, posisi, posisi_jabatan, jenis_instansi, pendapatan_bulanan, tahun_mulai, tahun_selesai, status_kerja, is_manual, created_at, updated_at)
                VALUES
                    (gen_random_uuid(), :tenant_id, :siswa_id, :nama_alumni, :nisn, :nama_perusahaan, :posisi, :posisi_jabatan, :jenis_instansi, :pendapatan_bulanan, :tahun_mulai, :tahun_selesai, :status_kerja, :is_manual, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                RETURNING id
            ");

            $stmt->execute([
                'tenant_id'          => $tenantId,
                'siswa_id'           => $siswaId ?: null,
                'nama_alumni'        => $namaAlumni ?: null,
                'nisn'               => $nisn ?: null,
                'nama_perusahaan'    => $namaPerusahaan,
                'posisi'             => $posisiJabatan,
                'posisi_jabatan'     => $posisiJabatan,
                'jenis_instansi'     => $jenisInstansi,
                'pendapatan_bulanan' => $pendapatanBulanan ?: null,
                'tahun_mulai'        => $tahunMulai,
                'tahun_selesai'      => $tahunSelesai,
                'status_kerja'       => $statusKerja,
                'is_manual'          => $isManual ? 'TRUE' : 'FALSE'
            ]);

            $newId = $stmt->fetchColumn();
            $db->commit();

            $this->jsonResponse([
                'success' => true,
                'id'      => $newId,
                'message' => 'Riwayat pekerjaan berhasil disimpan.'
            ]);
        } catch (\Throwable $e) {
            if (isset($db) && $db->inTransaction()) $db->rollBack();
            error_log('[TracerModuleController::storePekerjaan] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal menyimpan riwayat pekerjaan: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // API: Update Riwayat Pekerjaan (UPDATE)
    // POST /api/v1/tracer/pekerjaan/update
    // =========================================================================
    public function updatePekerjaan(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed.'], 405);
            return;
        }

        $roleName = $_SESSION['role_name'] ?? '';
        $tenantId = $this->getTenantContext();
        $body     = $this->getJsonInput();

        $id = $this->sanitizeStr($body['id'] ?? '');
        if (empty($id)) {
            $this->jsonResponse(['error' => 'ID data riwayat pekerjaan tidak valid.'], 422);
            return;
        }

        $namaAlumni        = $this->sanitizeStr($body['nama_alumni'] ?? '');
        $nisn              = $this->sanitizeStr($body['nisn'] ?? '');
        $namaPerusahaan    = $this->sanitizeStr($body['nama_perusahaan'] ?? '');
        $posisiJabatan     = $this->sanitizeStr($body['posisi_jabatan'] ?? $body['posisi'] ?? '');
        $jenisInstansi     = $this->sanitizeStr($body['jenis_instansi'] ?? 'Swasta');
        $pendapatanBulanan = $this->sanitizeStr($body['pendapatan_bulanan'] ?? '');
        $tahunMulai        = (int)($body['tahun_mulai'] ?? date('Y'));
        $tahunSelesai      = !empty($body['tahun_selesai']) ? (int)$body['tahun_selesai'] : null;
        $statusKerja       = $this->sanitizeStr($body['status_kerja'] ?? 'Kontrak');

        if (empty($namaPerusahaan)) {
            $this->jsonResponse(['error' => 'Nama perusahaan / tempat kerja wajib diisi.'], 422);
            return;
        }
        if (empty($posisiJabatan)) {
            $this->jsonResponse(['error' => 'Posisi / jabatan wajib diisi.'], 422);
            return;
        }

        try {
            $db = Database::getConnection();

            if ($roleName !== 'super_admin') {
                $checkStmt = $db->prepare("SELECT id FROM tracer.riwayat_pekerjaan WHERE id = ? AND tenant_id = ?");
                $checkStmt->execute([$id, $tenantId]);
                if (!$checkStmt->fetch()) {
                    $this->jsonResponse(['error' => 'Data tidak ditemukan atau akses ditolak.'], 404);
                    return;
                }
            }

            $stmt = $db->prepare("
                UPDATE tracer.riwayat_pekerjaan
                SET nama_alumni = :nama_alumni,
                    nisn = :nisn,
                    nama_perusahaan = :nama_perusahaan,
                    posisi = :posisi,
                    posisi_jabatan = :posisi_jabatan,
                    jenis_instansi = :jenis_instansi,
                    pendapatan_bulanan = :pendapatan_bulanan,
                    tahun_mulai = :tahun_mulai,
                    tahun_selesai = :tahun_selesai,
                    status_kerja = :status_kerja,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");

            $stmt->execute([
                'id'                 => $id,
                'nama_alumni'        => $namaAlumni ?: null,
                'nisn'               => $nisn ?: null,
                'nama_perusahaan'    => $namaPerusahaan,
                'posisi'             => $posisiJabatan,
                'posisi_jabatan'     => $posisiJabatan,
                'jenis_instansi'     => $jenisInstansi,
                'pendapatan_bulanan' => $pendapatanBulanan ?: null,
                'tahun_mulai'        => $tahunMulai,
                'tahun_selesai'      => $tahunSelesai,
                'status_kerja'       => $statusKerja
            ]);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Data riwayat pekerjaan berhasil diperbarui.'
            ]);
        } catch (\Throwable $e) {
            error_log('[TracerModuleController::updatePekerjaan] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal memperbarui riwayat pekerjaan: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // API: Hapus Riwayat Pekerjaan (DELETE)
    // POST /api/v1/tracer/pekerjaan/delete
    // =========================================================================
    public function deletePekerjaan(): void {
        $roleName = $_SESSION['role_name'] ?? '';
        $tenantId = $this->getTenantContext();

        if (!in_array($roleName, ['super_admin', 'admin', 'operator', 'operator_sekolah', 'guru_bk'], true)) {
            $this->jsonResponse(['error' => 'Akses ditolak.'], 403);
            return;
        }

        $id = $this->sanitizeStr($_POST['id'] ?? $_GET['id'] ?? '');
        if (empty($id)) {
            $body = $this->getJsonInput();
            $id = $this->sanitizeStr($body['id'] ?? '');
        }

        if (empty($id)) {
            $this->jsonResponse(['error' => 'ID data riwayat pekerjaan tidak valid.'], 400);
            return;
        }

        try {
            $db = Database::getConnection();

            if ($roleName !== 'super_admin') {
                $stmt = $db->prepare("SELECT id FROM tracer.riwayat_pekerjaan WHERE id = ? AND tenant_id = ?");
                $stmt->execute([$id, $tenantId]);
                if (!$stmt->fetch()) {
                    $this->jsonResponse(['error' => 'Data tidak ditemukan atau akses ditolak.'], 404);
                    return;
                }
            }

            $stmt = $db->prepare("DELETE FROM tracer.riwayat_pekerjaan WHERE id = ?");
            $stmt->execute([$id]);

            $this->jsonResponse(['success' => true, 'message' => 'Riwayat pekerjaan berhasil dihapus.']);
        } catch (\Throwable $e) {
            error_log('[TracerModuleController::deletePekerjaan] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal menghapus data: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================
    private function getSiswaStatus(string $siswaId): ?string {
        try {
            $db   = Database::getConnection();
            $stmt = $db->prepare("SELECT status_siswa FROM siswa.siswa WHERE id = ? LIMIT 1");
            $stmt->execute([$siswaId]);
            return $stmt->fetchColumn() ?: null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function sanitizeStr(mixed $value): string {
        if (!is_string($value)) return '';
        return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
    }
}
