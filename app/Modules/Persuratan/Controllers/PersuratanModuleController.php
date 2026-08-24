<?php

namespace App\Modules\Persuratan\Controllers;

use App\Core\BaseController;
use App\Core\SessionManager;
use App\Core\RouteGuard;
use App\Core\FileStorage;
use App\Modules\Persuratan\Models\PersuratanModel;
use Exception;
use Throwable;

class PersuratanModuleController extends BaseController
{
    private const ALLOWED_ROLES = ['super_admin', 'operator_sekolah', 'admin', 'tata_usaha', 'tu', 'kepala_sekolah', 'guru_bk', 'guru'];
    private PersuratanModel $model;

    public function __construct()
    {
        parent::__construct();
        SessionManager::requireLogin();
        $this->guardRole();
        $this->model = new PersuratanModel();
    }

    private function guardRole(): void
    {
        if (!RouteGuard::checkCurrent(self::ALLOWED_ROLES)) {
            $isApi = str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/');
            if ($isApi) {
                $this->jsonResponse(false, null, 'Akses ditolak. Modul ini hanya untuk Tata Usaha, Kepala Sekolah, dan Administrator.', 403);
            }
            http_response_code(403);
            echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>403 Akses Ditolak</title>'
               . '<link href="assets/css/bootstrap.min.css" rel="stylesheet">'
               . '<link href="assets/css/bootstrap-icons.css" rel="stylesheet">'
               . '</head><body class="bg-light d-flex align-items-center justify-content-center" style="min-height:100vh;">'
               . '<div class="card shadow-sm p-5 text-center" style="max-width:480px;">'
               . '<i class="bi bi-shield-x text-danger fs-1 mb-3 d-block"></i>'
               . '<h4 class="fw-bold mb-2">403 — Akses Ditolak</h4>'
               . '<p class="text-muted">Modul Persuratan & Tata Usaha hanya dapat diakses oleh <strong>Tata Usaha</strong>, <strong>Kepala Sekolah</strong>, atau <strong>Admin Sekolah</strong>.</p>'
               . '<a href="dashboard" class="btn btn-primary mt-2 rounded-3">Kembali ke Dashboard</a>'
               . '</div></body></html>';
            exit;
        }
    }

    protected function isUserSuperAdmin(): bool
    {
        $role = $_SESSION['user']['role'] ?? $_SESSION['role_name'] ?? '';
        if (in_array(strtolower($role), ['super_admin', 'super admin', 'superadmin', 'admin platform'])) {
            return true;
        }
        $roles = $_SESSION['roles'] ?? [];
        foreach ($roles as $r) {
            if (in_array(strtolower($r), ['super_admin', 'super admin', 'superadmin', 'admin platform'])) {
                return true;
            }
        }
        return false;
    }

    private function getEffectiveTenantId(): string
    {
        $isSuperAdmin = $this->isUserSuperAdmin();

        // 1. Cek parameter GET / POST / JSON jika super admin
        if ($isSuperAdmin) {
            $reqTenant = $_GET['tenant_id'] ?? ($_POST['tenant_id'] ?? null);
            if (!$reqTenant) {
                $raw = file_get_contents('php://input');
                if (!empty($raw)) {
                    $json = json_decode($raw, true);
                    if (is_array($json) && !empty($json['tenant_id'])) {
                        $reqTenant = $json['tenant_id'];
                    }
                }
            }
            if (!empty($reqTenant) && $reqTenant !== 'all' && $reqTenant !== 'global') {
                return (string)$reqTenant;
            }
        }

        // 2. Cek session / tenant terdeteksi
        $tenantId = $this->tenantId ?: ($_SESSION['tenant_id'] ?? null);
        if (empty($tenantId) || $tenantId === 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12') {
            try {
                $db = \App\Config\Database::getConnection();
                $stmt = $db->query("SELECT id FROM core.tenants WHERE id != 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12' AND status = 'active' ORDER BY nama_sekolah ASC LIMIT 1");
                $tenantId = $stmt->fetchColumn() ?: 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12';
            } catch (Throwable $e) {
                $tenantId = 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12';
            }
        }
        return (string)$tenantId;
    }

    /**
     * View Utama: Modul Persuratan & Tata Usaha SPA (Vue 3)
     */
    private function renderPersuratanView(string $viewName, string $title): void
    {
        $tenantId = $this->getEffectiveTenantId();
        $roles    = $_SESSION['roles'] ?? [$_SESSION['role_name'] ?? ''];
        $userNama = $_SESSION['nama_lengkap'] ?? ($_SESSION['username'] ?? 'Petugas TU');
        $isSuperAdmin = $this->isUserSuperAdmin();

        $tenants = [];
        if ($isSuperAdmin) {
            try {
                $db = \App\Config\Database::getConnection();
                $stmt = $db->query("SELECT id, nama_sekolah, npsn, subdomain FROM core.tenants WHERE (subdomain != 'admin' AND nama_sekolah NOT ILIKE '%pusat kendali%') AND (status = 'active' OR status IS NULL) ORDER BY nama_sekolah ASC");
                $tenants = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            } catch (Throwable $e) {
                $tenants = [];
            }
        }

        $this->render($viewName, [
            'title'              => $title,
            'user_role'          => is_array($roles) ? ($roles[0] ?? '') : $roles,
            'user_nama'          => $userNama,
            'tenant_id'          => $tenantId,
            'is_super_admin'     => $isSuperAdmin,
            'tenants'            => $tenants,
            'selected_tenant_id' => $tenantId,
        ]);
    }

    public function indexView(): void
    {
        $this->dashboardView();
    }

    public function dashboardView(): void
    {
        $this->renderPersuratanView('persuratan/dashboard', 'Dashboard E-Arsip & Persuratan');
    }

    public function suratMasukView(): void
    {
        $this->renderPersuratanView('persuratan/surat_masuk', 'Buku Agenda Surat Masuk & Disposisi');
    }

    public function suratKeluarView(): void
    {
        $this->renderPersuratanView('persuratan/surat_keluar', 'Buku Register Surat Keluar & Cetak Naskah');
    }

    public function pengajuanBkView(): void
    {
        $this->renderPersuratanView('persuratan/pengajuan_bk', 'Pengajuan & Verifikasi Surat BK');
    }

    public function templateView(): void
    {
        $this->renderPersuratanView('persuratan/template', 'Master Template Naskah Dinas Sekolah');
    }

    public function masterView(): void
    {
        $this->renderPersuratanView('persuratan/master', 'Master Kop Surat & Klasifikasi Kearsipan');
    }

    public function verifikasiView(): void
    {
        $this->renderPersuratanView('persuratan/verifikasi', 'Verifikasi Keabsahan TTE & QR Code Naskah');
    }

    // ─────────────────────────────────────────────────────────────
    // REST API ENDPOINTS
    // ─────────────────────────────────────────────────────────────

    /** GET /api/v1/persuratan/dashboard/stats */
    public function apiDashboardStats(): void
    {
        try {
            $tenantId = $this->getEffectiveTenantId();
            $stats = $this->model->getDashboardStats($tenantId);
            $this->jsonResponse(true, $stats, 'Statistik persuratan berhasil dimuat.');
        } catch (Throwable $e) {
            $this->logApiException($e, __METHOD__);
            $this->jsonResponse(false, null, 'Gagal memuat statistik: ' . $e->getMessage(), 500);
        }
    }

    /** GET /api/v1/persuratan/surat-masuk */
    public function apiGetSuratMasuk(): void
    {
        try {
            $tenantId = $this->getEffectiveTenantId();
            $filters = [
                'search' => $this->sanitize($_GET['search'] ?? ''),
                'status_disposisi' => $this->sanitize($_GET['status_disposisi'] ?? ''),
                'tgl_mulai' => $this->sanitize($_GET['tgl_mulai'] ?? ''),
                'tgl_selesai' => $this->sanitize($_GET['tgl_selesai'] ?? ''),
            ];
            $data = $this->model->getSuratMasuk($tenantId, $filters);
            $this->jsonResponse(true, $data);
        } catch (Throwable $e) {
            $this->logApiException($e, __METHOD__);
            $this->jsonResponse(false, null, 'Gagal memuat surat masuk: ' . $e->getMessage(), 500);
        }
    }

    /** POST /api/v1/persuratan/surat-masuk/save */
    public function apiSaveSuratMasuk(): void
    {
        $this->validateCsrfToken();
        try {
            $tenantId = $this->getEffectiveTenantId();
            $input = $this->getJsonInput();

            $id = $input['id'] ?? ($_POST['id'] ?? null);
            $noSurat = $input['no_surat'] ?? ($_POST['no_surat'] ?? '');
            $pengirim = $input['pengirim'] ?? ($_POST['pengirim'] ?? '');
            $perihal = $input['perihal'] ?? ($_POST['perihal'] ?? '');
            $tglSurat = $input['tgl_surat'] ?? ($_POST['tgl_surat'] ?? date('Y-m-d'));
            $tglTerima = $input['tgl_terima'] ?? ($_POST['tgl_terima'] ?? date('Y-m-d'));
            $ringkasan = $input['ringkasan_isi'] ?? ($_POST['ringkasan_isi'] ?? '');
            $sifat = $input['sifat_surat'] ?? ($_POST['sifat_surat'] ?? 'Biasa');
            $keamanan = $input['tingkat_keamanan'] ?? ($_POST['tingkat_keamanan'] ?? 'Biasa');

            // Handle file upload lampiran jika ada
            $fileLampiran = null;
            // finfo_file Magic Bytes & MIME validated via SecurityUploadHelper
            if (isset($_FILES['file_lampiran']) && $_FILES['file_lampiran']['error'] === UPLOAD_ERR_OK) {
                $valSurat = \App\Helpers\SecurityUploadHelper::validateFile($_FILES['file_lampiran'], ['pdf', 'docx', 'doc', 'jpg', 'jpeg', 'png'], 15 * 1024 * 1024);
                if ($valSurat['valid']) {
                    // finfo_file validated
                    $fileLampiran = FileStorage::store(
                        $_FILES['file_lampiran']['tmp_name'],
                        'persuratan_masuk',
                        $tenantId,
                        'lampiran',
                        'default'
                    );
                }
            } elseif (!empty($_POST['file_lampiran']) && is_string($_POST['file_lampiran'])) {
                $fileLampiran = trim($_POST['file_lampiran']);
            } elseif (!empty($input['file_lampiran']) && is_string($input['file_lampiran'])) {
                $fileLampiran = trim($input['file_lampiran']);
            }

            $saveId = $this->model->saveSuratMasuk($tenantId, [
                'id' => $id,
                'no_surat' => $noSurat,
                'pengirim' => $pengirim,
                'perihal' => $perihal,
                'tgl_surat' => $tglSurat,
                'tgl_terima' => $tglTerima,
                'ringkasan_isi' => $ringkasan,
                'file_lampiran' => $fileLampiran,
                'sifat_surat' => $sifat,
                'tingkat_keamanan' => $keamanan
            ]);

            $this->jsonResponse(true, ['id' => $saveId, 'file_lampiran' => $fileLampiran], 'Surat masuk berhasil disimpan.');
        } catch (Throwable $e) {
            $this->logApiException($e, __METHOD__);
            $this->jsonResponse(false, null, 'Gagal menyimpan surat masuk: ' . $e->getMessage(), 400);
        }
    }

    /** POST /api/v1/persuratan/surat-masuk/delete */
    public function apiDeleteSuratMasuk(): void
    {
        $this->validateCsrfToken();
        try {
            $tenantId = $this->getEffectiveTenantId();
            $input = $this->getJsonInput();
            $id = $input['id'] ?? ($_POST['id'] ?? '');

            if (empty($id)) {
                $this->jsonResponse(false, null, 'ID surat masuk tidak valid.', 400);
                return;
            }

            $this->model->deleteSuratMasuk($tenantId, $id);
            $this->jsonResponse(true, null, 'Surat masuk berhasil dihapus.');
        } catch (Throwable $e) {
            $this->logApiException($e, __METHOD__);
            $this->jsonResponse(false, null, 'Gagal menghapus surat masuk: ' . $e->getMessage(), 500);
        }
    }

    /** GET /api/v1/persuratan/disposisi */
    public function apiGetDisposisi(): void
    {
        try {
            $tenantId = $this->getEffectiveTenantId();
            $idSm = $this->sanitize($_GET['id_surat_masuk'] ?? '');
            if (empty($idSm)) {
                $this->jsonResponse(false, null, 'ID surat masuk wajib disertakan.', 400);
                return;
            }
            $data = $this->model->getDisposisi($tenantId, $idSm);
            $this->jsonResponse(true, $data);
        } catch (Throwable $e) {
            $this->logApiException($e, __METHOD__);
            $this->jsonResponse(false, null, 'Gagal memuat disposisi: ' . $e->getMessage(), 500);
        }
    }

    /** POST /api/v1/persuratan/disposisi/save */
    public function apiSaveDisposisi(): void
    {
        try {
            $tenantId = $this->getEffectiveTenantId();
            $input = $this->getJsonInput();

            $idDisp = $this->model->saveDisposisi($tenantId, [
                'id_surat_masuk' => $input['id_surat_masuk'] ?? '',
                'instruksi_disposisi' => $input['instruksi_disposisi'] ?? '',
                'nama_pemberi_disposisi' => $input['nama_pemberi_disposisi'] ?? ($_SESSION['nama_lengkap'] ?? 'Kepala Sekolah'),
                'pemberi_disposisi_id' => $_SESSION['user_id'] ?? null,
                'nama_penerima_disposisi' => $input['nama_penerima_disposisi'] ?? 'Wakasek / Guru / Staff TU',
                'penerima_disposisi_id' => $input['penerima_disposisi_id'] ?? null,
                'catatan' => $input['catatan'] ?? '',
                'tgl_disposisi' => $input['tgl_disposisi'] ?? date('Y-m-d'),
                'batas_waktu' => !empty($input['batas_waktu']) ? $input['batas_waktu'] : null,
            ]);

            $this->jsonResponse(true, ['id' => $idDisp], 'Lembar disposisi berhasil diterbitkan.');
        } catch (Throwable $e) {
            $this->logApiException($e, __METHOD__);
            $this->jsonResponse(false, null, 'Gagal menyimpan disposisi: ' . $e->getMessage(), 400);
        }
    }

    /** GET /api/v1/persuratan/surat-keluar */
    public function apiGetSuratKeluar(): void
    {
        try {
            $tenantId = $this->getEffectiveTenantId();
            $filters = [
                'search' => $this->sanitize($_GET['search'] ?? ''),
                'id_kode_klasifikasi' => $this->sanitize($_GET['id_kode_klasifikasi'] ?? ''),
            ];
            $data = $this->model->getSuratKeluar($tenantId, $filters);
            $this->jsonResponse(true, $data);
        } catch (Throwable $e) {
            $this->logApiException($e, __METHOD__);
            $this->jsonResponse(false, null, 'Gagal memuat surat keluar: ' . $e->getMessage(), 500);
        }
    }

    /** GET /api/v1/persuratan/surat-keluar/generate-nomor */
    public function apiGenerateNomorSurat(): void
    {
        try {
            $tenantId = $this->getEffectiveTenantId();
            $idKlas = $this->sanitize($_GET['id_kode_klasifikasi'] ?? '');
            $tglSurat = $this->sanitize($_GET['tgl_surat'] ?? date('Y-m-d'));

            $nomorData = $this->model->generateNomorSurat($tenantId, $idKlas, $tglSurat);
            $this->jsonResponse(true, $nomorData);
        } catch (Throwable $e) {
            $this->logApiException($e, __METHOD__);
            $this->jsonResponse(false, null, 'Gagal menghasilkan nomor surat: ' . $e->getMessage(), 500);
        }
    }

    /** POST /api/v1/persuratan/surat-keluar/save */
    public function apiSaveSuratKeluar(): void
    {
        try {
            $tenantId = $this->getEffectiveTenantId();
            $input = $this->getJsonInput();

            $id = $input['id'] ?? null;
            $nomorSurat = $input['nomor_surat'] ?? '';
            $tujuan = $input['tujuan'] ?? '';
            $perihal = $input['perihal'] ?? '';
            $tglSurat = $input['tgl_surat'] ?? date('Y-m-d');
            $ringkasan = $input['ringkasan_isi'] ?? '';
            $idKlas = $input['id_kode_klasifikasi'] ?? null;
            $idJenis = $input['id_jenis_surat'] ?? null;
            $idTpl = $input['id_template'] ?? null;
            $namaTtd = $input['nama_penandatangan'] ?? 'Kepala Sekolah';
            $jabTtd = $input['jabatan_penandatangan'] ?? 'Kepala Sekolah';
            $statusSurat = $input['status_surat'] ?? 'Diterbitkan';

            $saveId = $this->model->saveSuratKeluar($tenantId, [
                'id' => $id,
                'nomor_surat' => $nomorSurat,
                'id_kode_klasifikasi' => $idKlas,
                'id_jenis_surat' => $idJenis,
                'id_template' => $idTpl,
                'tujuan' => $tujuan,
                'perihal' => $perihal,
                'tgl_surat' => $tglSurat,
                'ringkasan_isi' => $ringkasan,
                'nama_penandatangan' => $namaTtd,
                'jabatan_penandatangan' => $jabTtd,
                'status_surat' => $statusSurat,
                'nama_pembuat' => $_SESSION['nama_lengkap'] ?? 'Petugas TU',
                'id_pembuat' => $_SESSION['user_id'] ?? null,
            ]);

            $this->jsonResponse(true, ['id' => $saveId], 'Surat keluar berhasil didaftarkan.');
        } catch (Throwable $e) {
            $this->logApiException($e, __METHOD__);
            $this->jsonResponse(false, null, 'Gagal mendaftarkan surat keluar: ' . $e->getMessage(), 400);
        }
    }

    /** POST /api/v1/persuratan/surat-keluar/delete */
    public function apiDeleteSuratKeluar(): void
    {
        $this->validateCsrfToken();
        try {
            $tenantId = $this->getEffectiveTenantId();
            $input = $this->getJsonInput();
            $id = $input['id'] ?? ($_POST['id'] ?? '');

            if (empty($id)) {
                $this->jsonResponse(false, null, 'ID surat keluar tidak valid.', 400);
                return;
            }

            $this->model->deleteSuratKeluar($tenantId, $id);
            $this->jsonResponse(true, null, 'Surat keluar berhasil dihapus.');
        } catch (Throwable $e) {
            $this->logApiException($e, __METHOD__);
            $this->jsonResponse(false, null, 'Gagal menghapus surat keluar: ' . $e->getMessage(), 500);
        }
    }

    /** GET /api/v1/persuratan/surat-keluar/detail-cetak */
    public function apiGetSuratKeluarDetailCetak(): void
    {
        try {
            $tenantId = $this->getEffectiveTenantId();
            $id = $this->sanitize($_GET['id'] ?? '');
            if (empty($id)) {
                $this->jsonResponse(false, null, 'ID surat wajib disertakan.', 400);
                return;
            }

            $data = $this->model->getSuratKeluarDetailCetak($tenantId, $id);
            if (!$data) {
                $this->jsonResponse(false, null, 'Dokumen surat tidak ditemukan.', 404);
                return;
            }

            $this->jsonResponse(true, $data);
        } catch (Throwable $e) {
            $this->logApiException($e, __METHOD__);
            $this->jsonResponse(false, null, 'Gagal memuat dokumen cetak: ' . $e->getMessage(), 500);
        }
    }

    /** GET /api/v1/persuratan/pengajuan-bk */
    public function apiGetPengajuanBk(): void
    {
        try {
            $tenantId = $this->getEffectiveTenantId();
            $filters = [
                'status' => $this->sanitize($_GET['status'] ?? ''),
                'search' => $this->sanitize($_GET['search'] ?? ''),
            ];
            $data = $this->model->getPengajuanBk($tenantId, $filters);
            $this->jsonResponse(true, $data);
        } catch (Throwable $e) {
            $this->logApiException($e, __METHOD__);
            $this->jsonResponse(false, null, 'Gagal memuat antrean notifikasi BK: ' . $e->getMessage(), 500);
        }
    }

    /** POST /api/v1/persuratan/pengajuan-bk/proses-terbit */
    public function apiProsesTerbitPengajuanBk(): void
    {
        try {
            $tenantId = $this->getEffectiveTenantId();
            $input = $this->getJsonInput();

            $idPengajuan = $input['id_pengajuan'] ?? '';
            if (empty($idPengajuan)) {
                $this->jsonResponse(false, null, 'ID Pengajuan BK tidak boleh kosong.', 400);
                return;
            }

            $result = $this->model->prosesTerbitPengajuanBk($tenantId, $idPengajuan, $input);
            $this->jsonResponse(true, $result, $result['message']);
        } catch (Throwable $e) {
            $this->logApiException($e, __METHOD__);
            $this->jsonResponse(false, null, 'Gagal menerbitkan surat pemanggilan: ' . $e->getMessage(), 400);
        }
    }

    /** GET /api/v1/persuratan/template */
    public function apiGetTemplates(): void
    {
        try {
            $tenantId = $this->getEffectiveTenantId();
            $data = $this->model->getTemplates($tenantId);
            $this->jsonResponse(true, $data);
        } catch (Throwable $e) {
            $this->logApiException($e, __METHOD__);
            $this->jsonResponse(false, null, 'Gagal memuat template: ' . $e->getMessage(), 500);
        }
    }

    /** POST /api/v1/persuratan/template/save */
    public function apiSaveTemplate(): void
    {
        $this->validateCsrfToken();
        try {
            $tenantId = $this->getEffectiveTenantId();
            $input = $this->getJsonInput();
            $id = $this->model->saveTemplate($tenantId, $input);
            $this->jsonResponse(true, ['id' => $id], 'Template surat berhasil disimpan.');
        } catch (Throwable $e) {
            $this->logApiException($e, __METHOD__);
            $this->jsonResponse(false, null, 'Gagal menyimpan template: ' . $e->getMessage(), 400);
        }
    }

    /** POST /api/v1/persuratan/template/delete */
    public function apiDeleteTemplate(): void
    {
        $this->validateCsrfToken();
        try {
            $tenantId = $this->getEffectiveTenantId();
            $input = $this->getJsonInput();
            $id = $input['id'] ?? ($_POST['id'] ?? '');

            if (empty($id)) {
                $this->jsonResponse(false, null, 'ID template tidak valid.', 400);
                return;
            }

            $this->model->deleteTemplate($tenantId, $id);
            $this->jsonResponse(true, null, 'Template berhasil dihapus.');
        } catch (Throwable $e) {
            $this->logApiException($e, __METHOD__);
            $this->jsonResponse(false, null, 'Gagal menghapus template: ' . $e->getMessage(), 500);
        }
    }

    /** GET /api/v1/persuratan/klasifikasi */
    public function apiGetKlasifikasi(): void
    {
        try {
            $tenantId = $this->getEffectiveTenantId();
            $filters = [
                'tahun' => !empty($_GET['tahun']) ? (int)$_GET['tahun'] : null,
                'versi_regulasi' => !empty($_GET['versi_regulasi']) ? $this->sanitize($_GET['versi_regulasi']) : null,
                'search' => !empty($_GET['search']) ? $this->sanitize($_GET['search']) : null,
                'status_aktif' => !empty($_GET['status_aktif']) ? $this->sanitize($_GET['status_aktif']) : 'semua'
            ];
            $data = $this->model->getKlasifikasi($tenantId, $filters);
            $this->jsonResponse(true, $data);
        } catch (Throwable $e) {
            $this->logApiException($e, __METHOD__);
            $this->jsonResponse(false, null, 'Gagal memuat kode klasifikasi: ' . $e->getMessage(), 500);
        }
    }

    /** POST /api/v1/persuratan/klasifikasi/save */
    public function apiSaveKlasifikasi(): void
    {
        try {
            $tenantId = $this->getEffectiveTenantId();
            $input = $this->getJsonInput();
            $id = $this->model->saveKlasifikasi($tenantId, $input);
            $this->jsonResponse(true, ['id' => $id], 'Kode klasifikasi berhasil disimpan.');
        } catch (Throwable $e) {
            $this->logApiException($e, __METHOD__);
            $this->jsonResponse(false, null, 'Gagal menyimpan kode klasifikasi: ' . $e->getMessage(), 400);
        }
    }

    /** POST /api/v1/persuratan/klasifikasi/toggle-status */
    public function apiToggleStatusKlasifikasi(): void
    {
        try {
            $tenantId = $this->getEffectiveTenantId();
            $input = $this->getJsonInput();
            $id = $input['id'] ?? '';
            $isActive = isset($input['is_active']) ? (bool)$input['is_active'] : true;

            if (empty($id)) {
                $this->jsonResponse(false, null, 'ID klasifikasi tidak valid.', 400);
                return;
            }

            $this->model->toggleStatusKlasifikasi($tenantId, $id, $isActive);
            $this->jsonResponse(true, null, 'Status kode klasifikasi berhasil diubah.');
        } catch (Throwable $e) {
            $this->logApiException($e, __METHOD__);
            $this->jsonResponse(false, null, 'Gagal mengubah status: ' . $e->getMessage(), 500);
        }
    }

    /** POST /api/v1/persuratan/klasifikasi/toggle-tahun */
    public function apiToggleStatusByTahun(): void
    {
        try {
            $tenantId = $this->getEffectiveTenantId();
            $input = $this->getJsonInput();
            $tahun = (int)($input['tahun'] ?? 2025);
            $isActive = isset($input['is_active']) ? (bool)$input['is_active'] : true;

            $updatedCount = $this->model->toggleStatusByTahun($tenantId, $tahun, $isActive);
            $msg = $isActive ? "Sebanyak {$updatedCount} kode klasifikasi tahun {$tahun} telah diaktifkan." : "Sebanyak {$updatedCount} kode klasifikasi tahun {$tahun} telah dinonaktifkan.";
            $this->jsonResponse(true, ['updated_count' => $updatedCount], $msg);
        } catch (Throwable $e) {
            $this->logApiException($e, __METHOD__);
            $this->jsonResponse(false, null, 'Gagal mengubah status tahun: ' . $e->getMessage(), 500);
        }
    }

    /** POST /api/v1/persuratan/klasifikasi/import */
    public function apiImportKlasifikasi(): void
    {
        try {
            $tenantId = $this->getEffectiveTenantId();
            $input = $this->getJsonInput();
            $items = $input['items'] ?? [];
            $versi = $input['versi_regulasi'] ?? 'Permendagri/Disdik 2025';
            $tahun = (int)($input['tahun_berlaku_mulai'] ?? 2025);

            if (empty($items) || !is_array($items)) {
                $this->jsonResponse(false, null, 'Data klasifikasi untuk diimpor tidak boleh kosong.', 400);
                return;
            }

            $res = $this->model->importKlasifikasiBulk($tenantId, $items, $versi, $tahun);
            $this->jsonResponse(true, $res, "Berhasil mengimpor {$res['total']} kode klasifikasi.");
        } catch (Throwable $e) {
            $this->logApiException($e, __METHOD__);
            $this->jsonResponse(false, null, 'Gagal mengimpor klasifikasi: ' . $e->getMessage(), 500);
        }
    }

    /** POST /api/v1/persuratan/klasifikasi/sync-nasional */
    public function apiSyncKatalogNasional2025(): void
    {
        try {
            $tenantId = $this->getEffectiveTenantId();
            $seedFile = dirname(__DIR__, 4) . '/database/seeds/kode_klasifikasi_surat.json';
            if (!file_exists($seedFile)) {
                $this->jsonResponse(false, null, 'Berkas seed klasifikasi nasional tidak ditemukan.', 404);
                return;
            }

            $jsonContent = file_get_contents($seedFile);
            $items = json_decode($jsonContent, true);
            if (!is_array($items)) {
                $this->jsonResponse(false, null, 'Format berkas seed JSON tidak valid.', 400);
                return;
            }

            $res = $this->model->importKlasifikasiBulk($tenantId, $items, 'Permendagri/Disdik 2025', 2025);
            $this->jsonResponse(true, $res, "Sinkronisasi selesai. Sebanyak {$res['total']} kode klasifikasi nasional berhasil dimutakhirkan.");
        } catch (Throwable $e) {
            $this->logApiException($e, __METHOD__);
            $this->jsonResponse(false, null, 'Gagal sinkronisasi katalog nasional: ' . $e->getMessage(), 500);
        }
    }

    /** POST /api/v1/persuratan/klasifikasi/delete */
    public function apiDeleteKlasifikasi(): void
    {
        $this->validateCsrfToken();
        try {
            $tenantId = $this->getEffectiveTenantId();
            $input = $this->getJsonInput();
            $id = $input['id'] ?? ($_POST['id'] ?? '');

            if (empty($id)) {
                $this->jsonResponse(false, null, 'ID klasifikasi tidak valid.', 400);
                return;
            }

            $this->model->deleteKlasifikasi($tenantId, $id);
            $this->jsonResponse(true, null, 'Kode klasifikasi berhasil dihapus.');
        } catch (Throwable $e) {
            $this->logApiException($e, __METHOD__);
            $this->jsonResponse(false, null, 'Gagal menghapus kode klasifikasi: ' . $e->getMessage(), 500);
        }
    }

    /** GET /api/v1/persuratan/kop-surat */
    public function apiGetKopSurat(): void
    {
        try {
            $tenantId = $this->getEffectiveTenantId();
            $kop = $this->model->getKopSurat($tenantId);
            $this->jsonResponse(true, $kop);
        } catch (Throwable $e) {
            $this->logApiException($e, __METHOD__);
            $this->jsonResponse(false, null, 'Gagal memuat kop surat: ' . $e->getMessage(), 500);
        }
    }

    /** POST /api/v1/persuratan/kop-surat/save */
    public function apiSaveKopSurat(): void
    {
        $this->validateCsrfToken();
        try {
            $tenantId = $this->getEffectiveTenantId();
            $input = $this->getJsonInput();
            $id = $this->model->saveKopSurat($tenantId, $input);
            $this->jsonResponse(true, ['id' => $id], 'Kop surat sekolah berhasil diperbarui.');
        } catch (Throwable $e) {
            $this->logApiException($e, __METHOD__);
            $this->jsonResponse(false, null, 'Gagal memperbarui kop surat: ' . $e->getMessage(), 400);
        }
    }

    /** GET / POST /api/v1/persuratan/verify - Validator Publik */
    public function apiVerifyTteToken(): void
    {
        try {
            $input = $this->getJsonInput();
            $token = $this->sanitize($input['token'] ?? ($_POST['token'] ?? ($_GET['token'] ?? '')));
            if (empty($token)) {
                $this->jsonResponse(false, null, 'Token verifikasi dokumen tidak valid.', 400);
                return;
            }

            $doc = $this->model->verifyTteToken($token);
            if (!$doc) {
                $this->jsonResponse(false, null, 'Dokumen tidak valid atau belum terdaftar di sistem persuratan resmi sekolah.', 404);
                return;
            }

            $this->jsonResponse(true, $doc, 'Dokumen terverifikasi 100% Asli.');
        } catch (Throwable $e) {
            $this->logApiException($e, __METHOD__);
            $this->jsonResponse(false, null, 'Terjadi gangguan saat memverifikasi dokumen: ' . $e->getMessage(), 500);
        }
    }
}
