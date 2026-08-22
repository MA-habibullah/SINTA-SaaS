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

    private function getEffectiveTenantId(): string
    {
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
    public function indexView(): void
    {
        $tenantId = $this->getEffectiveTenantId();
        $roles    = $_SESSION['roles'] ?? [$_SESSION['role_name'] ?? ''];
        $userNama = $_SESSION['nama_lengkap'] ?? ($_SESSION['username'] ?? 'Petugas TU');

        $this->render('persuratan/persuratan_index', [
            'title'     => 'Persuratan & Tata Usaha',
            'user_role' => is_array($roles) ? ($roles[0] ?? '') : $roles,
            'user_nama' => $userNama,
            'tenant_id' => $tenantId,
        ]);
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
            if (isset($_FILES['file_lampiran']) && $_FILES['file_lampiran']['error'] === UPLOAD_ERR_OK) {
                $fileLampiran = FileStorage::store(
                    $_FILES['file_lampiran']['tmp_name'],
                    'persuratan_masuk',
                    $tenantId,
                    'lampiran',
                    'default'
                );
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

            $this->jsonResponse(true, ['id' => $saveId], 'Surat masuk berhasil disimpan.');
        } catch (Throwable $e) {
            $this->logApiException($e, __METHOD__);
            $this->jsonResponse(false, null, 'Gagal menyimpan surat masuk: ' . $e->getMessage(), 400);
        }
    }

    /** POST /api/v1/persuratan/surat-masuk/delete */
    public function apiDeleteSuratMasuk(): void
    {
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

    /** GET /api/v1/persuratan/klasifikasi */
    public function apiGetKlasifikasi(): void
    {
        try {
            $tenantId = $this->getEffectiveTenantId();
            $data = $this->model->getKlasifikasi($tenantId);
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

    /** GET /api/v1/persuratan/verify/:token - Validator Publik */
    public function apiVerifyTteToken(): void
    {
        try {
            $token = $this->sanitize($_GET['token'] ?? '');
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
