<?php

namespace App\Modules\Siswa\Controllers;

use App\Core\BaseController;
use App\Modules\Siswa\Models\SiswaModel;
use App\Core\SessionManager;
use App\Libraries\FileCompressor;

/**
 * SiswaModuleController — Controller Modul Siswa (Arsitektur Baru).
 * Namespace: App\Modules\Siswa\Controllers
 * Model: App\Modules\Siswa\Models\SiswaModel
 */
class SiswaModuleController extends BaseController {
    private SiswaModel $siswaModel;

    public function __construct() {
        parent::__construct();
        SessionManager::requireLogin();
        $tenantId = SessionManager::getTenantId();
        $this->siswaModel = new SiswaModel($tenantId);
    }

    private function getAcademicOptions(?string $tenantId): array {
        $db = \App\Config\Database::getConnection();
        $res = [
            'angkatan' => [], 'tahun_ajaran' => [], 'jenjang' => [],
            'jurusan' => [], 'kelas' => [], 'pendidikan' => []
        ];
        if (!$tenantId) { $tenantId = SessionManager::getTenantId(); }

        $q = "SELECT id, nama_angkatan AS tahun_angkatan FROM akademik.angkatan WHERE is_active = true";
        if ($tenantId) { $q .= " AND tenant_id = :tenant_id"; }
        $q .= " ORDER BY nama_angkatan DESC";
        $stmt = $db->prepare($q);
        if ($tenantId) { $stmt->execute(['tenant_id' => $tenantId]); } else { $stmt->execute(); }
        $res['angkatan'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $q = "SELECT id, nama_tahun_ajaran AS tahun_ajaran FROM akademik.tahun_ajaran WHERE is_active = true";
        if ($tenantId) { $q .= " AND tenant_id = :tenant_id"; }
        $q .= " ORDER BY nama_tahun_ajaran DESC";
        $stmt = $db->prepare($q);
        if ($tenantId) { $stmt->execute(['tenant_id' => $tenantId]); } else { $stmt->execute(); }
        $res['tahun_ajaran'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $q = "SELECT id, nama_jenjang FROM core.jenjang WHERE is_active = true";
        if ($tenantId) { $q .= " AND (tenant_id = :tenant_id OR tenant_id IS NULL)"; }
        $q .= " ORDER BY nama_jenjang ASC";
        $stmt = $db->prepare($q);
        if ($tenantId) { $stmt->execute(['tenant_id' => $tenantId]); } else { $stmt->execute(); }
        $res['jenjang'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $q = "SELECT id, nama_jurusan FROM akademik.jurusan WHERE is_active = true";
        if ($tenantId) { $q .= " AND tenant_id = :tenant_id"; }
        $q .= " ORDER BY nama_jurusan ASC";
        $stmt = $db->prepare($q);
        if ($tenantId) { $stmt->execute(['tenant_id' => $tenantId]); } else { $stmt->execute(); }
        $res['jurusan'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $q = "SELECT id, nama_kelas, id_jenjang, id_jurusan FROM akademik.kelas WHERE is_active = true";
        if ($tenantId) { $q .= " AND tenant_id = :tenant_id"; }
        $q .= " ORDER BY nama_kelas ASC";
        $stmt = $db->prepare($q);
        if ($tenantId) { $stmt->execute(['tenant_id' => $tenantId]); } else { $stmt->execute(); }
        $res['kelas'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $q = "SELECT id, nama_pendidikan FROM akademik.pendidikan WHERE is_active = true";
        if ($tenantId) { $q .= " AND (tenant_id = :tenant_id OR tenant_id IS NULL)"; }
        $q .= " ORDER BY nama_pendidikan ASC";
        $stmt = $db->prepare($q);
        if ($tenantId) { $stmt->execute(['tenant_id' => $tenantId]); } else { $stmt->execute(); }
        $res['pendidikan'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $res;
    }

    /**
     * AJAX Actions Handler
     */
    private function handleAjax(): void {
        $action = $_GET['action'] ?? '';
        $db = \App\Config\Database::getConnection();

        try {
            if ($action === 'get_provinsi') {
                $stmt = $db->query("SELECT id_provinsi, nama_provinsi FROM core.provinsi ORDER BY nama_provinsi ASC");
                $this->jsonResponse($stmt->fetchAll(\PDO::FETCH_ASSOC));
            }
            
            if ($action === 'get_kota') {
                $idProv = $_GET['id_provinsi'] ?? 0;
                $stmt = $db->prepare("SELECT id_kota, id_provinsi, nama_kota FROM core.kota WHERE id_provinsi = ? ORDER BY nama_kota ASC");
                $stmt->execute([$idProv]);
                $this->jsonResponse($stmt->fetchAll(\PDO::FETCH_ASSOC));
            }
            
            if ($action === 'get_kecamatan') {
                $idKota = $_GET['id_kota'] ?? 0;
                $stmt = $db->prepare("SELECT id_kecamatan, id_kota, nama_kecamatan FROM core.kecamatan WHERE id_kota = ? ORDER BY nama_kota ASC");
                $stmt->execute([$idKota]);
                $this->jsonResponse($stmt->fetchAll(\PDO::FETCH_ASSOC));
            }
            
            if ($action === 'get_kelurahan') {
                $idKec = $_GET['id_kecamatan'] ?? 0;
                $stmt = $db->prepare("SELECT id_kelurahan, id_kecamatan, nama_kelurahan FROM core.kelurahan WHERE id_kecamatan = ? ORDER BY nama_kelurahan ASC");
                $stmt->execute([$idKec]);
                $this->jsonResponse($stmt->fetchAll(\PDO::FETCH_ASSOC));
            }
            
            if ($action === 'get_all_kota') {
                $stmt = $db->query("SELECT id_kota, nama_kota FROM core.kota ORDER BY nama_kota ASC");
                $this->jsonResponse($stmt->fetchAll(\PDO::FETCH_ASSOC));
            }
            
            if ($action === 'get_academic_options') {
                $tenantId = $_GET['tenant_id'] ?? $_SESSION['tenant_id'] ?? null;
                $res = $this->getAcademicOptions($tenantId);
                $this->jsonResponse($res);
            }

            if ($action === 'get_siswa_detail') {
                $id = $_GET['id'] ?? '';
                $roleName = $_SESSION['role_name'] ?? '';
                $sessionUserId = $_SESSION['user_id'] ?? '';
                if ($roleName === 'siswa' && $id !== $sessionUserId) {
                    $this->jsonResponse(['error' => 'Akses ditolak.'], 403);
                }
                $siswa = $this->siswaModel->findFullById($id);
                $kesehatan = $this->siswaModel->getKesehatanSiswa($id);
                if ($siswa) {
                    if (isset($siswa['password'])) {
                        unset($siswa['password']);
                    }
                    $this->jsonResponse([
                        'success' => true,
                        'data' => $siswa,
                        'kesehatan' => $kesehatan
                    ]);
                } else {
                    $this->jsonResponse(['error' => 'Siswa tidak ditemukan.'], 404);
                }
            }

            if ($action === 'get_siswa_draft') {
                $draft = $_SESSION['siswa_draft'] ?? null;
                if (is_array($draft) && isset($draft['password'])) {
                    unset($draft['password']);
                }
                $old = $_SESSION['siswa_old'] ?? null;
                if (is_array($old) && isset($old['password'])) {
                    unset($old['password']);
                }
                $errors = $_SESSION['siswa_errors'] ?? null;
                
                unset($_SESSION['siswa_old']);
                unset($_SESSION['siswa_errors']);
                
                $this->jsonResponse([
                    'success' => true,
                    'draft' => $draft,
                    'old' => $old,
                    'errors' => $errors
                ]);
            }
            
            $this->jsonResponse(['error' => 'Aksi AJAX tidak dikenal.'], 400);
        } catch (\Throwable $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    private function validateUploadedFiles(?string $tenantId, array $existingSizes, array &$errors): void {
        $fileKeys = [
            'berkas_ijazah_smp', 'berkas_ijazah_sma', 'berkas_mutasi_masuk',
            'berkas_mutasi_keluar', 'berkas_kip', 'berkas_pernyataan_baru', 'berkas_pernyataan_tka'
        ];

        $allowedMimes = [
            'jpg'  => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png'  => ['image/png'],
            'pdf'  => ['application/pdf'],
        ];

        $netChange = 0;

        foreach ($fileKeys as $key) {
            if (!isset($_FILES[$key])) {
                continue;
            }
            $fieldLabel = ucwords(str_replace('_', ' ', $key));
            if ($_FILES[$key]['error'] !== UPLOAD_ERR_OK) {
                if ($_FILES[$key]['error'] !== UPLOAD_ERR_NO_FILE) {
                    $errors[$key] = "Gagal mengunggah {$fieldLabel} (Kode Error: " . $_FILES[$key]['error'] . "). Mungkin ukuran file melebihi batas maksimal server.";
                }
                continue;
            }

            $tmpPath   = $_FILES[$key]['tmp_name'];
            $origName  = $_FILES[$key]['name'];
            $fileSize  = $_FILES[$key]['size'];

            $roleName = $_SESSION['role_name'] ?? '';
            if ($roleName === 'siswa' && in_array($key, ['berkas_pernyataan_baru', 'berkas_pernyataan_tka'])) {
                $errors[$key] = "Siswa tidak diizinkan mengunggah berkas {$fieldLabel}.";
                continue;
            }

            if ($fileSize > 500 * 1024) {
                $errors[$key] = "Ukuran {$fieldLabel} melebihi batas maksimal 500 KB.";
                continue;
            }

            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            if (!array_key_exists($ext, $allowedMimes)) {
                $errors[$key] = "Format {$fieldLabel} tidak valid. Diizinkan: jpg, jpeg, png, pdf.";
                continue;
            }

            if (function_exists('finfo_open')) {
                $finfo    = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $tmpPath);
                finfo_close($finfo);

                if (!in_array($mimeType, $allowedMimes[$ext], true)) {
                    $errors[$key] = "Konten {$fieldLabel} tidak sesuai ekstensi. Kemungkinan file berbahaya.";
                    continue;
                }
            }

            $oldSize    = $existingSizes[$key] ?? 0;
            $netChange += max(0, $fileSize - $oldSize);
        }

        if ($netChange > 0 && !empty($tenantId) && empty($errors)) {
            if (!\App\Core\StorageGuard::checkStorageLimit($tenantId, $netChange)) {
                $errors['storage_limit'] = 'Penyimpanan sekolah penuh (melebihi limit paket aktif sekolah).';
            }
        }
    }

    private function uploadFiles(
        string $tenantId,
        string $siswaId,
        array  $existingFiles    = [],
        array  $existingSizes    = [],
        array  &$uploadedPaths   = [],
        array  &$oldPathsToDelete = []
    ): array {
        $uploaded      = $existingFiles;
        $uploadedSizes = $existingSizes;

        $baseDir = __DIR__ . '/../../../../storage/app/public/uploads/' . $tenantId . '/' . $siswaId . '/';
        $trustedPrefix = 'uploads/' . $tenantId . '/' . $siswaId . '/';

        $fileKeys = [
            'foto_profil', 'berkas_kk', 'berkas_akta', 'berkas_ijazah_sd',
            'berkas_ijazah_smp', 'berkas_ijazah_sma', 'berkas_mutasi_masuk',
            'berkas_mutasi_keluar', 'berkas_kip', 'berkas_pernyataan_baru', 'berkas_pernyataan_tka'
        ];

        foreach ($fileKeys as $key) {
            if (!isset($_FILES[$key]) || $_FILES[$key]['error'] !== UPLOAD_ERR_OK) {
                continue;
            }

            $fileTmpPath   = $_FILES[$key]['tmp_name'];
            $fileName      = $_FILES[$key]['name'];
            $fileSize      = $_FILES[$key]['size'];

            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if (!in_array($fileExtension, ['jpg', 'jpeg', 'png', 'pdf'], true)) {
                continue;
            }
            if ($fileSize > 500 * 1024) {
                continue;
            }

            if (!is_dir($baseDir)) {
                mkdir($baseDir, 0755, true);
            }

            try {
                if (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                    $maxWidth = ($key === 'foto_profil') ? 800 : 1200;
                    $result   = FileCompressor::compressImage($fileTmpPath, $baseDir, $maxWidth, 75);
                } else {
                    $result = FileCompressor::processPdf($fileTmpPath, $baseDir, 500 * 1024);
                }
            } catch (\RuntimeException $e) {
                throw new \Exception('Berkas ' . str_replace('_', ' ', $key) . ': ' . $e->getMessage());
            }

            $newFileName = $result['filename'];
            $destPath    = $result['path'];
            $uploadedPaths[] = $destPath;

            $oldRelativePath = $existingFiles[$key] ?? '';
            if (!empty($oldRelativePath)) {
                if (str_starts_with($oldRelativePath, $trustedPrefix)) {
                    $oldAbsPath = __DIR__ . '/../../../../storage/app/public/' . $oldRelativePath;
                    if (file_exists($oldAbsPath)) {
                        $oldPathsToDelete[] = $oldAbsPath;
                    }
                } else {
                    error_log('[SECURITY] Blocked deletion of suspicious old file path: ' . $oldRelativePath . ' for siswa ' . $siswaId);
                }
            }

            $relativeDbPath      = $trustedPrefix . $newFileName;
            $uploaded[$key]      = $relativeDbPath;
            $uploadedSizes[$key] = $fileSize;
        }

        return [
            'files' => $uploaded,
            'sizes' => $uploadedSizes,
        ];
    }

    /**
     * Tampilkan Form Tambah Siswa
     * GET /siswa/tambah
     */
    public function tambah(): void {
        $roleName = $_SESSION['role_name'] ?? '';
        if ($roleName === 'siswa') {
            http_response_code(403);
            die("<h1>403 Forbidden</h1><p>Anda tidak diizinkan mengakses halaman ini.</p>");
        }

        if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
            $this->handleAjax();
            return;
        }

        $db = \App\Config\Database::getConnection();
        $provinces = $db->query("SELECT id_provinsi, nama_provinsi FROM core.provinsi ORDER BY nama_provinsi ASC")->fetchAll(\PDO::FETCH_ASSOC);
        $cities = $db->query("SELECT id_kota, nama_kota FROM core.kota ORDER BY nama_kota ASC")->fetchAll(\PDO::FETCH_ASSOC);
        $academicOptions = $this->getAcademicOptions(null);

        unset($_SESSION['siswa_old']);
        unset($_SESSION['siswa_errors']);

        $draft = $_SESSION['siswa_draft'] ?? null;
        if (is_array($draft) && isset($draft['password'])) {
            unset($draft['password']);
        }

        $data = [
            'title'          => 'Tambah Siswa Baru',
            'user_nama'      => $_SESSION['nama_lengkap'],
            'user_role'      => $_SESSION['role_name'],
            'provinces_list' => $provinces,
            'cities_list'    => $cities,
            'academic_options' => $academicOptions,
            'draft'          => $draft
        ];
        
        $this->render('tambah_siswa', $data);
    }

    /**
     * Simpan Data Siswa Baru (POST)
     * POST /siswa/simpan
     */
    public function store(): void {
        $roleName = $_SESSION['role_name'] ?? '';
        if ($roleName === 'siswa') {
            http_response_code(403);
            die("<h1>403 Forbidden</h1><p>Anda tidak diizinkan mengakses halaman ini.</p>");
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectWithError('Metode request tidak diizinkan.');
        }

        $input = $this->sanitizeInput($_POST);
        
        $tenantId = SessionManager::getTenantId() ?: ($input['tenant_id'] ?? null);
        if (SessionManager::getTenantId() === null && !empty($tenantId)) {
            $this->siswaModel->setTenantId($tenantId);
        }

        $errors = $this->validateSiswaData($input);
        $this->validateUploadedFiles($tenantId, [], $errors);

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || isset($_POST['ajax']);

        if (!empty($errors)) {
            if (isset($input['password'])) {
                unset($input['password']);
            }
            $_SESSION['siswa_old'] = $input;
            $_SESSION['siswa_errors'] = $errors;
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'errors' => $errors], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
                exit;
            }

            $db = \App\Config\Database::getConnection();
            $provinces = $db->query("SELECT id_provinsi, nama_provinsi FROM core.provinsi ORDER BY nama_provinsi ASC")->fetchAll(\PDO::FETCH_ASSOC);
            $cities = $db->query("SELECT id_kota, nama_kota FROM core.kota ORDER BY nama_kota ASC")->fetchAll(\PDO::FETCH_ASSOC);
            $academicOptions = $this->getAcademicOptions($input['tenant_id'] ?? null);

            $data = [
                'title' => 'Tambah Siswa Baru',
                'errors' => $errors,
                'old' => $input,
                'user_nama' => $_SESSION['nama_lengkap'],
                'user_role' => $_SESSION['role_name'],
                'provinces_list' => $provinces,
                'cities_list' => $cities,
                'academic_options' => $academicOptions
            ];
            $this->render('tambah_siswa', $data);
            exit;
        }

        $db = \App\Config\Database::getConnection();
        $uploadedPaths = [];
        try {
            $db->beginTransaction();

            $siswaId = $this->generateUuidV4();
            $input['id'] = $siswaId;

            $uploadResult = $this->uploadFiles($tenantId, $siswaId, [], [], $uploadedPaths);
            $input = array_merge($input, $uploadResult['files']);
            $input['file_sizes'] = $uploadResult['sizes'];

            $this->siswaModel->create($input);
            if (isset($_POST['kesehatan']) && is_array($_POST['kesehatan'])) {
                $this->siswaModel->saveKesehatanSiswa($siswaId, $_POST['kesehatan']);
            }
            \App\Helpers\ActivityLogger::record('INSERT', 'siswa', $siswaId, null, $input);

            $db->commit();

            unset($_SESSION['siswa_draft']);

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Data siswa berhasil ditambahkan.',
                    'id'      => $siswaId,
                    'files'   => $uploadResult['files'] ?? []
                ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
                exit;
            }

            $this->redirectWithSuccess('Data siswa berhasil ditambahkan.', '/SINTA-SaaS/pengguna');
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            foreach ($uploadedPaths as $path) {
                if (file_exists($path)) {
                    @unlink($path);
                }
            }
            error_log("Gagal tambah siswa: " . $e->getMessage());
            
            if ($isAjax) {
                header('Content-Type: application/json');
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Terjadi kegagalan sistem saat menyimpan data: ' . $e->getMessage()], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
                exit;
            }

            $this->redirectWithError('Terjadi kegagalan sistem saat menyimpan data: ' . $e->getMessage(), '/SINTA-SaaS/pengguna');
        }
    }

    /**
     * Tampilkan Form Edit Siswa
     * GET /siswa/edit?id=...
     */
    public function edit(): void {
        if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
            $this->handleAjax();
            return;
        }

        $id = $_GET['id'] ?? '';
        if (empty($id)) {
            $this->redirectWithError('ID siswa tidak valid.', '/SINTA-SaaS/pengguna');
        }

        $roleName = $_SESSION['role_name'] ?? '';
        $sessionUserId = $_SESSION['user_id'] ?? '';
        if ($roleName === 'siswa' && $id !== $sessionUserId) {
            http_response_code(403);
            die("<h1>403 Forbidden</h1><p>Anda tidak diizinkan mengubah data orang lain.</p>");
        }

        $siswa = $this->siswaModel->findFullById($id);
        if (!$siswa) {
            $this->redirectWithError('Data siswa tidak ditemukan atau Anda tidak memiliki akses.', '/SINTA-SaaS/pengguna');
        }
        if (is_array($siswa) && isset($siswa['password'])) {
            unset($siswa['password']);
        }

        unset($_SESSION['siswa_old']);
        unset($_SESSION['siswa_errors']);

        if ($roleName === 'siswa') {
            $statusDiDb = $siswa['status_siswa'] ?? ($siswa['status'] ?? 'Aktif');
            if ($statusDiDb === 'Lulus' || $statusDiDb === 'Pindah') {
                http_response_code(403);
                echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>403 Data Dikunci</title>'
                   . '<link href="/SINTA-SaaS/assets/css/bootstrap.min.css" rel="stylesheet">'
                   . '</head><body class="bg-light d-flex align-items-center justify-content-center" style="min-height:100vh;">'
                   . '<div class="card shadow-sm p-5 text-center" style="max-width:480px;">'
                   . '<div class="text-warning fs-1 mb-3"><i class="bi bi-lock-fill"></i></div>'
                   . '<h4 class="fw-bold mb-2">Data Telah Dikunci</h4>'
                   . '<p class="text-muted">Akses ditolak. Data Anda telah dikunci oleh sistem (Status: ' . htmlspecialchars($statusDiDb) . '). Hubungi Admin Sekolah jika ada kesalahan data.</p>'
                   . '<a href="/SINTA-SaaS/dashboard" class="btn btn-primary mt-3">Kembali ke Dashboard</a>'
                   . '</div>'
                   . '<link href="/SINTA-SaaS/assets/css/bootstrap-icons.css" rel="stylesheet">'
                   . '</body></html>';
                exit;
            }
        }

        $tenantId = $siswa['tenant_id'] ?? null;
        if (SessionManager::getTenantId() === null && isset($tenantId)) {
            $this->siswaModel->setTenantId($tenantId);
        }

        $db = \App\Config\Database::getConnection();
        $provinces       = $db->query("SELECT id_provinsi, nama_provinsi FROM core.provinsi ORDER BY nama_provinsi ASC")->fetchAll(\PDO::FETCH_ASSOC);
        $cities          = $db->query("SELECT id_kota, nama_kota FROM core.kota ORDER BY nama_kota ASC")->fetchAll(\PDO::FETCH_ASSOC);
        $academicOptions = $this->getAcademicOptions($tenantId);

        $kesehatan = $this->siswaModel->getKesehatanSiswa($id);

        $data = [
            'title'            => 'Edit Data Siswa',
            'siswa'            => $siswa,
            'kesehatan'        => $kesehatan,
            'siswa_status'     => $siswa['status_siswa'] ?? ($siswa['status'] ?? 'Aktif'),
            'user_nama'        => $_SESSION['nama_lengkap'],
            'user_role'        => $_SESSION['role_name'],
            'provinces_list'   => $provinces,
            'cities_list'      => $cities,
            'academic_options' => $academicOptions
        ];

        $this->render('tambah_siswa', $data);
    }

    /**
     * Simpan Perubahan Data Siswa (POST)
     * POST /siswa/update
     */
    public function update(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectWithError('Metode request tidak diizinkan.', '/SINTA-SaaS/pengguna');
        }

        $id = $_POST['id'] ?? '';
        if (empty($id)) {
            $this->redirectWithError('ID siswa tidak valid.', '/SINTA-SaaS/pengguna');
        }

        $roleName = $_SESSION['role_name'] ?? '';
        $sessionUserId = $_SESSION['user_id'] ?? '';
        if ($roleName === 'siswa' && $id !== $sessionUserId) {
            http_response_code(403);
            die("<h1>403 Forbidden</h1><p>Anda tidak diizinkan mengubah data orang lain.</p>");
        }

        $siswa = $this->siswaModel->findFullById($id);
        if (!$siswa) {
            $this->redirectWithError('Data siswa tidak ditemukan atau Anda tidak memiliki akses.', '/SINTA-SaaS/pengguna');
        }

        if ($roleName === 'siswa') {
            $statusDiDb = $siswa['status_siswa'] ?? ($siswa['status'] ?? 'Aktif');
            if ($statusDiDb === 'Lulus' || $statusDiDb === 'Pindah') {
                $isAjaxCheck = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
                               isset($_POST['current_step']);

                $msg = 'Akses ditolak. Data Anda telah dikunci oleh sistem (Status: ' . htmlspecialchars($statusDiDb) . '). Hubungi Admin Sekolah jika ada kesalahan data.';

                if ($isAjaxCheck) {
                    header('Content-Type: application/json');
                    http_response_code(403);
                    echo json_encode(['success' => false, 'error' => $msg], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
                    exit;
                }

                http_response_code(403);
                echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>403 Data Dikunci</title>'
                   . '<link href="/SINTA-SaaS/assets/css/bootstrap.min.css" rel="stylesheet">'
                   . '</head><body class="bg-light d-flex align-items-center justify-content-center" style="min-height:100vh;">'
                   . '<div class="card shadow-sm p-5 text-center" style="max-width:480px;">'
                   . '<div class="text-warning fs-1 mb-3"><i class="bi bi-lock-fill"></i></div>'
                   . '<h4 class="fw-bold mb-2">Data Telah Dikunci</h4>'
                   . '<p class="text-muted">' . htmlspecialchars($msg) . '</p>'
                   . '<a href="/SINTA-SaaS/dashboard" class="btn btn-primary mt-3">Kembali ke Dashboard</a>'
                   . '</div>'
                   . '<link href="/SINTA-SaaS/assets/css/bootstrap-icons.css" rel="stylesheet">'
                   . '</body></html>';
                exit;
            }
        }

        $tenantId = SessionManager::getTenantId() ?: ($siswa['tenant_id'] ?? null);
        if (SessionManager::getTenantId() === null && isset($tenantId)) {
            $this->siswaModel->setTenantId($tenantId);
        }

        $currentStep = isset($_POST['current_step']) ? (int) $_POST['current_step'] : null;
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
                  || $currentStep !== null;

        $input = $this->sanitizeInput($_POST);

        if (!empty($input['tanggal_lahir_ayah'])) {
            $input['tahun_lahir_ayah'] = date('Y', strtotime($input['tanggal_lahir_ayah']));
        }
        if (!empty($input['tanggal_lahir_ibu'])) {
            $input['tahun_lahir_ibu'] = date('Y', strtotime($input['tanggal_lahir_ibu']));
        }
        if (!empty($input['tanggal_lahir_wali'])) {
            $input['tahun_lahir_wali'] = date('Y', strtotime($input['tanggal_lahir_wali']));
        }
        
        if ($roleName === 'siswa' && $siswa) {
            $input['nama_lengkap'] = $siswa['nama_lengkap'] ?? '';
            $input['nisn'] = $siswa['nisn'] ?? '';
            $input['nis'] = $siswa['nis'] ?? '';
        }

        $errors = $this->validateSiswaData($input, $id, $currentStep);

        $existingSizes = [];
        $existing = [];
        $db = \App\Config\Database::getConnection();

        if ($currentStep === null || $currentStep === 5) {
            $stmt = $db->prepare("SELECT foto_profil FROM siswa.rincian_pelajar WHERE id_siswa::text = ?");
            $stmt->execute([$id]);
            $existing['foto_profil'] = $stmt->fetchColumn() ?: '';
            
            $stmt = $db->prepare("SELECT berkas_kk, berkas_akta, berkas_ijazah_sd, berkas_ijazah_smp, berkas_ijazah_sma, berkas_mutasi_masuk, berkas_mutasi_keluar, berkas_kip, berkas_pernyataan_baru, berkas_pernyataan_tka, file_sizes FROM siswa.dokumen WHERE id_siswa::text = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
            
            if (!empty($row['file_sizes'])) {
                $existingSizes = json_decode($row['file_sizes'], true) ?: [];
            }
            unset($row['file_sizes']);
            
            foreach ($row as $k => $v) {
                $existing[$k] = $v ?: '';
            }

            $this->validateUploadedFiles($tenantId, $existingSizes, $errors);
        }

        if (!empty($errors)) {
            if (is_array($siswa) && isset($siswa['password'])) {
                unset($siswa['password']);
            }
            if (isset($input['password'])) {
                unset($input['password']);
            }
            $_SESSION['siswa_old'] = array_merge($siswa, $input);
            $_SESSION['siswa_errors'] = $errors;
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'errors' => $errors], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
                exit;
            }

            $provinces = $db->query("SELECT id_provinsi, nama_provinsi FROM core.provinsi ORDER BY nama_provinsi ASC")->fetchAll(\PDO::FETCH_ASSOC);
            $cities = $db->query("SELECT id_kota, nama_kota FROM core.kota ORDER BY nama_kota ASC")->fetchAll(\PDO::FETCH_ASSOC);
            $academicOptions = $this->getAcademicOptions($tenantId);

            $data = [
                'title' => 'Edit Data Siswa',
                'errors' => $errors,
                'siswa' => array_merge($siswa, $input),
                'user_nama' => $_SESSION['nama_lengkap'],
                'user_role' => $_SESSION['role_name'],
                'provinces_list' => $provinces,
                'cities_list' => $cities,
                'academic_options' => $academicOptions
            ];
            $this->render('tambah_siswa', $data);
            exit;
        }

        $uploadedPaths    = [];
        $oldPathsToDelete = [];
        try {
            $db->beginTransaction();

            if (!empty($input['password'])) {
                $input['password'] = password_hash($input['password'], PASSWORD_BCRYPT);
            } else {
                unset($input['password']);
            }

            $uploadedFilesForJson = [];
            if ($currentStep === null || $currentStep === 5) {
                $uploadResult = $this->uploadFiles(
                    $tenantId, $id,
                    $existing, $existingSizes,
                    $uploadedPaths,
                    $oldPathsToDelete
                );
                $input = array_merge($input, $uploadResult['files']);
                $input['file_sizes'] = $uploadResult['sizes'];
                $uploadedFilesForJson = $uploadResult['files'];
            }

            $this->siswaModel->update($id, $input);
            if (isset($_POST['kesehatan']) && is_array($_POST['kesehatan'])) {
                $this->siswaModel->saveKesehatanSiswa($id, $_POST['kesehatan']);
            }
            \App\Helpers\ActivityLogger::record('UPDATE', 'siswa', $id, $siswa, $input);
            \App\Helpers\CacheInvalidator::clearStudentCache($id, $tenantId);

            $db->commit();

            foreach ($oldPathsToDelete as $oldPath) {
                if (file_exists($oldPath)) {
                    if (!@unlink($oldPath)) {
                        error_log('[CLEANUP] Gagal menghapus file lama: ' . $oldPath);
                    }
                }
            }

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Perubahan Step ' . ($currentStep ?: '5') . ' berhasil disimpan.',
                    'files'   => $uploadedFilesForJson
                ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
                exit;
            }

            $this->redirectWithSuccess('Data siswa berhasil diperbarui.', '/SINTA-SaaS/pengguna');

        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            foreach ($uploadedPaths as $newPath) {
                if (file_exists($newPath)) {
                    @unlink($newPath);
                }
            }
            error_log('Gagal update siswa [' . $id . ']: ' . $e->getMessage());
            if ($isAjax) {
                header('Content-Type: application/json');
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error'   => 'Terjadi kegagalan sistem saat memperbarui data: ' . $e->getMessage()
                ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
                exit;
            }
            $this->redirectWithError(
                'Terjadi kegagalan sistem saat memperbarui data: ' . $e->getMessage(),
                '/SINTA-SaaS/pengguna'
            );
        }
    }

    /**
     * Hapus Siswa (Soft Delete)
     * POST /siswa/hapus
     */
    public function delete(): void {
        $roleName = $_SESSION['role_name'] ?? '';
        if ($roleName === 'siswa') {
            http_response_code(403);
            die("<h1>403 Forbidden</h1><p>Anda tidak diizinkan mengakses halaman ini.</p>");
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectWithError('Metode request tidak diizinkan.', '/SINTA-SaaS/pengguna');
        }

        $id = $_POST['id'] ?? '';
        if (empty($id)) {
            $this->redirectWithError('ID siswa tidak valid.', '/SINTA-SaaS/pengguna');
        }

        $siswa = $this->siswaModel->findFullById($id);
        if (!$siswa) {
            $this->redirectWithError('Data siswa tidak ditemukan atau Anda tidak memiliki akses.', '/SINTA-SaaS/pengguna');
        }

        if (SessionManager::getTenantId() === null && isset($siswa['tenant_id'])) {
            $this->siswaModel->setTenantId($siswa['tenant_id']);
        }

        try {
            $this->siswaModel->delete($id);
            \App\Helpers\ActivityLogger::record('DELETE', 'siswa', $id, $siswa, null);
            \App\Helpers\CacheInvalidator::clearStudentCache($id, $siswa['tenant_id'] ?? null);
            $this->redirectWithSuccess('Data siswa berhasil dihapus.', '/SINTA-SaaS/pengguna');
        } catch (\PDOException $e) {
            error_log("Gagal hapus siswa: " . $e->getMessage());
            $this->redirectWithError('Terjadi kegagalan sistem saat menghapus data.', '/SINTA-SaaS/pengguna');
        }
    }

    // ═══════════════════════════════════════════════════════
    // PRIVATE HELPERS
    // ═══════════════════════════════════════════════════════

    private function sanitizeInput(array $data): array {
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $val = trim($value);
                if ($val === '') {
                    $sanitized[$key] = null;
                } else {
                    if ($key === 'ukuran_seragam_sekolah' || $key === 'ukuran_seragam_olahraga') {
                        $val = strtoupper($val);
                    }
                    $sanitized[$key] = $val;
                }
            } else {
                $sanitized[$key] = $value;
            }
        }
        return $sanitized;
    }

    private function validateSiswaData(array $data, ?string $excludeId = null, ?int $currentStep = null): array {
        $errors = [];

        if ($currentStep === 1 || $currentStep === null) {
            if (empty($data['nama_lengkap'])) {
                $errors['nama_lengkap'] = 'Nama lengkap wajib diisi.';
            } elseif (strlen($data['nama_lengkap']) > 255) {
                $errors['nama_lengkap'] = 'Nama lengkap tidak boleh melebihi 255 karakter.';
            }
            if (empty($data['jenis_kelamin'])) {
                $errors['jenis_kelamin'] = 'Jenis kelamin wajib dipilih.';
            } elseif (!in_array($data['jenis_kelamin'], ['L', 'P'])) {
                $errors['jenis_kelamin'] = 'Pilihan jenis kelamin tidak valid.';
            }
            if (empty($data['nik'])) {
                $errors['nik'] = 'NIK wajib diisi.';
            }
            if (empty($data['nisn'])) {
                $errors['nisn'] = 'NISN wajib diisi.';
            } else {
                if (!preg_match('/^[0-9]{10}$/', $data['nisn'])) {
                    $errors['nisn'] = 'NISN harus berupa 10 digit angka.';
                } elseif (!$this->siswaModel->isNisnUnique($data['nisn'], $excludeId)) {
                    $errors['nisn'] = 'NISN sudah terdaftar pada sekolah lain (NISN harus unik nasional).';
                }
            }
            if (!empty($data['nis'])) {
                if (strlen($data['nis']) > 20) {
                    $errors['nis'] = 'NIS tidak boleh melebihi 20 karakter.';
                } elseif (!$this->siswaModel->isNisUnique($data['nis'], $excludeId)) {
                    $errors['nis'] = 'NIS sudah terdaftar di sekolah ini.';
                }
            }
            if (empty($data['agama'])) {
                $errors['agama'] = 'Agama wajib dipilih.';
            }
            if (empty($data['tempat_lahir'])) {
                $errors['tempat_lahir'] = 'Tempat lahir wajib diisi.';
            }
            if (empty($data['tanggal_lahir'])) {
                $errors['tanggal_lahir'] = 'Tanggal lahir wajib diisi.';
            } else {
                $d = \DateTime::createFromFormat('Y-m-d', $data['tanggal_lahir']);
                if (!$d || $d->format('Y-m-d') !== $data['tanggal_lahir']) {
                    $errors['tanggal_lahir'] = 'Format tanggal lahir tidak valid (gunakan format YYYY-MM-DD).';
                }
            }
            if (empty($data['sekolah_asal'])) {
                $errors['sekolah_asal'] = 'Sekolah asal wajib diisi.';
            }
            if (empty($data['kewarganegaraan'])) {
                $errors['kewarganegaraan'] = 'Kewarganegaraan wajib dipilih.';
            }
            if (!empty($data['kontak_wali'])) {
                if (!preg_match('/^[0-9]{8,15}$/', $data['kontak_wali'])) {
                    $errors['kontak_wali'] = 'Kontak wali harus berupa angka telepon valid (8-15 digit).';
                }
            }
            if (!empty($data['password'])) {
                if (strlen($data['password']) < 6) {
                    $errors['password'] = 'Password minimal 6 karakter.';
                }
            }
        }

        if ($currentStep === 2 || $currentStep === null) {
            if (empty($data['alamat_kk'])) {
                $errors['alamat_kk'] = 'Alamat sesuai KK wajib diisi.';
            }
            if (empty($data['alamat_domisili'])) {
                $errors['alamat_domisili'] = 'Alamat domisili wajib diisi.';
            }
            if (empty($data['rt']) || !preg_match('/^[0-9]{1,3}$/', $data['rt'])) {
                $errors['rt'] = 'RT wajib diisi dengan angka (max 3 digit).';
            }
            if (empty($data['rw']) || !preg_match('/^[0-9]{1,3}$/', $data['rw'])) {
                $errors['rw'] = 'RW wajib diisi dengan angka (max 3 digit).';
            }
            if (empty($data['kode_pos']) || !preg_match('/^[0-9]{5}$/', $data['kode_pos'])) {
                $errors['kode_pos'] = 'Kode pos harus berupa 5 digit angka.';
            }
            if (empty($data['id_kelurahan'])) {
                $errors['id_kelurahan'] = 'Kelurahan wajib dipilih.';
            }
            if (empty($data['status_tinggal'])) {
                $errors['status_tinggal'] = 'Status tinggal wajib dipilih.';
            }
            if (empty($data['email'])) {
                $errors['email'] = 'Email wajib diisi.';
            } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Format email tidak valid.';
            }
            if (empty($data['no_telepon_siswa']) || !preg_match('/^[0-9]{8,13}$/', $data['no_telepon_siswa'])) {
                $errors['no_telepon_siswa'] = 'No. HP siswa wajib diisi (8-13 digit angka).';
            }
            if (!empty($data['no_telepon_orang_tua']) && !preg_match('/^[0-9]{8,15}$/', $data['no_telepon_orang_tua'])) {
                $errors['no_telepon_orang_tua'] = 'No. HP orang tua harus berupa angka valid (8-15 digit).';
            }
        }

        if ($currentStep === 3 || $currentStep === null) {
            if (!isset($data['tinggi_badan']) || $data['tinggi_badan'] === '' || $data['tinggi_badan'] < 30) {
                $errors['tinggi_badan'] = 'Tinggi badan wajib diisi minimal 30 cm.';
            } elseif ($data['tinggi_badan'] > 255) {
                $errors['tinggi_badan'] = 'Tinggi badan maksimal 255 cm.';
            }
            if (!isset($data['berat_badan']) || $data['berat_badan'] === '' || $data['berat_badan'] < 5) {
                $errors['berat_badan'] = 'Berat badan wajib diisi minimal 5 kg.';
            } elseif ($data['berat_badan'] > 255) {
                $errors['berat_badan'] = 'Berat badan maksimal 255 kg.';
            }
            if (!isset($data['lingkar_kepala']) || $data['lingkar_kepala'] === '' || $data['lingkar_kepala'] < 20) {
                $errors['lingkar_kepala'] = 'Lingkar kepala wajib diisi minimal 20 cm.';
            } elseif ($data['lingkar_kepala'] > 255) {
                $errors['lingkar_kepala'] = 'Lingkar kepala maksimal 255 cm.';
            }
            if (empty($data['golongan_darah'])) {
                $errors['golongan_darah'] = 'Golongan darah wajib dipilih.';
            }
            if (!isset($data['anak_ke']) || $data['anak_ke'] === '' || $data['anak_ke'] < 1) {
                $errors['anak_ke'] = 'Kolom anak ke- wajib diisi minimal 1.';
            }
            if (!isset($data['jumlah_saudara']) || $data['jumlah_saudara'] === '' || $data['jumlah_saudara'] < 0) {
                $errors['jumlah_saudara'] = 'Jumlah saudara kandung wajib diisi.';
            }
            if (!isset($data['jarak_rumah']) || $data['jarak_rumah'] === '' || $data['jarak_rumah'] < 1) {
                $errors['jarak_rumah'] = 'Jarak rumah ke sekolah wajib diisi.';
            }
            if (empty($data['transportasi'])) {
                $errors['transportasi'] = 'Alat transportasi wajib dipilih.';
            }
            if (isset($data['punya_kip']) && $data['punya_kip'] == 1) {
                if (empty($data['no_kip'])) {
                    $errors['no_kip'] = 'Nomor KIP wajib diisi jika Anda memilih Ya pada Memiliki KIP.';
                }
            }
            if (isset($data['layak_kip']) && $data['layak_kip'] == 1 && empty($data['alasan_layak'])) {
                $errors['alasan_layak'] = 'Alasan layak KIP wajib diisi.';
            }
        }

        if ($currentStep === 4 || $currentStep === null) {
            if (empty($data['nik_ibu']) || !preg_match('/^[0-9]{16}$/', $data['nik_ibu'])) {
                $errors['nik_ibu'] = 'NIK Ibu kandung wajib berupa 16 digit angka.';
            }
            if (empty($data['nama_ibu'])) {
                $errors['nama_ibu'] = 'Nama Ibu kandung wajib diisi.';
            }
            if (empty($data['id_tempat_lahir_ibu'])) {
                $errors['id_tempat_lahir_ibu'] = 'Tempat lahir Ibu kandung wajib dipilih.';
            }
            if (empty($data['tanggal_lahir_ibu'])) {
                $errors['tanggal_lahir_ibu'] = 'Tanggal lahir Ibu kandung wajib diisi.';
            } else {
                $d = \DateTime::createFromFormat('Y-m-d', $data['tanggal_lahir_ibu']);
                if (!$d || $d->format('Y-m-d') !== $data['tanggal_lahir_ibu']) {
                    $errors['tanggal_lahir_ibu'] = 'Format tanggal lahir Ibu kandung tidak valid.';
                } else {
                    $year = (int) $d->format('Y');
                    if ($year < 1930 || $year > 2020) {
                        $errors['tanggal_lahir_ibu'] = 'Tahun lahir Ibu kandung tidak valid (1930-2020).';
                    }
                }
            }
            if (empty($data['pendidikan_ibu'])) {
                $errors['pendidikan_ibu'] = 'Pendidikan Ibu kandung wajib dipilih.';
            }
            if (empty($data['pekerjaan_ibu'])) {
                $errors['pekerjaan_ibu'] = 'Pekerjaan Ibu kandung wajib dipilih.';
            }
            if (empty($data['penghasilan_ibu'])) {
                $errors['penghasilan_ibu'] = 'Penghasilan Ibu kandung wajib dipilih.';
            }
            if (empty($data['agama_ibu'])) {
                $errors['agama_ibu'] = 'Agama Ibu kandung wajib dipilih.';
            }
            if (!empty($data['nik_ayah']) && !preg_match('/^[0-9]{16}$/', $data['nik_ayah'])) {
                $errors['nik_ayah'] = 'NIK Ayah harus berupa 16 digit angka.';
            }
            if (!empty($data['nik_wali']) && !preg_match('/^[0-9]{16}$/', $data['nik_wali'])) {
                $errors['nik_wali'] = 'NIK Wali harus berupa 16 digit angka.';
            }
        }

        if ($currentStep === 5 || $currentStep === null) {
            if (empty($data['jenis_pendaftaran'])) {
                $errors['jenis_pendaftaran'] = 'Jenis pendaftaran wajib dipilih.';
            }
            $roleName = $_SESSION['role_name'] ?? '';
            if (empty($data['tanggal_masuk'])) {
                $errors['tanggal_masuk'] = 'Tanggal masuk wajib diisi.';
            }
            if (empty($data['hobi'])) {
                $errors['hobi'] = 'Hobi wajib diisi.';
            }
            if ($roleName !== 'siswa' && isset($data['status']) && $data['status'] !== 'Aktif') {
                if (empty($data['keluar_karena'])) {
                    $errors['keluar_karena'] = 'Alasan keluar wajib dipilih karena status siswa bukan Aktif.';
                }
                if (empty($data['tanggal_keluar'])) {
                    $errors['tanggal_keluar'] = 'Tanggal keluar wajib diisi karena status siswa bukan Aktif.';
                }
            }
        }

        return $errors;
    }

    private function redirectWithError(string $msg, string $target = '/SINTA-SaaS/pengguna'): void {
        header("Location: {$target}?error=" . urlencode($msg));
        exit;
    }

    private function redirectWithSuccess(string $msg, string $target = '/SINTA-SaaS/pengguna'): void {
        header("Location: {$target}?success=" . urlencode($msg));
        exit;
    }

    private function generateUuidV4(): string {
        $data    = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
