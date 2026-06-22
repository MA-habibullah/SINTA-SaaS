<?php

namespace App\Controllers;

use App\Models\Siswa;
use App\Core\SessionManager;

class SiswaController extends BaseController {

    private Siswa $siswaModel;

    public function __construct() {
        parent::__construct();
        
        // 1. Amankan Operasi: Pastikan pengguna sudah terautentikasi (Secure by Design)
        SessionManager::requireLogin();
        
        // Ambil tenant_id dari session saat inisialisasi Model
        // (Super Admin tidak memiliki tenant_id, tapi Operator/Guru wajib punya)
        $tenantId = SessionManager::getTenantId();
        $this->siswaModel = new Siswa($tenantId);
    }

    /**
     * Helper to fetch academic options list
     */
    private function getAcademicOptions(?string $tenantId): array {
        $db = \App\Config\Database::getConnection();
        $res = [
            'angkatan' => [],
            'tahun_ajaran' => [],
            'jenjang' => [],
            'jurusan' => [],
            'kelas' => [],
            'pendidikan' => []
        ];

        if (!$tenantId) {
            $tenantId = SessionManager::getTenantId();
        }

        // Fetch Angkatan
        $q = "SELECT id, tahun_angkatan FROM angkatan WHERE is_active = 1";
        if ($tenantId) { $q .= " AND tenant_id = :tenant_id"; }
        $q .= " ORDER BY tahun_angkatan DESC";
        $stmt = $db->prepare($q);
        if ($tenantId) { $stmt->execute(['tenant_id' => $tenantId]); } else { $stmt->execute(); }
        $res['angkatan'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Fetch Tahun Ajaran
        $q = "SELECT id, tahun_ajaran FROM tahun_ajaran WHERE is_active = 1";
        if ($tenantId) { $q .= " AND tenant_id = :tenant_id"; }
        $q .= " ORDER BY tahun_ajaran DESC";
        $stmt = $db->prepare($q);
        if ($tenantId) { $stmt->execute(['tenant_id' => $tenantId]); } else { $stmt->execute(); }
        $res['tahun_ajaran'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Fetch Jenjang
        $q = "SELECT id, nama_jenjang FROM jenjang WHERE is_active = 1";
        if ($tenantId) { $q .= " AND tenant_id = :tenant_id"; }
        $q .= " ORDER BY nama_jenjang ASC";
        $stmt = $db->prepare($q);
        if ($tenantId) { $stmt->execute(['tenant_id' => $tenantId]); } else { $stmt->execute(); }
        $res['jenjang'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Fetch Jurusan
        $q = "SELECT id, nama_jurusan FROM jurusan WHERE is_active = 1";
        if ($tenantId) { $q .= " AND tenant_id = :tenant_id"; }
        $q .= " ORDER BY nama_jurusan ASC";
        $stmt = $db->prepare($q);
        if ($tenantId) { $stmt->execute(['tenant_id' => $tenantId]); } else { $stmt->execute(); }
        $res['jurusan'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Fetch Kelas
        $q = "SELECT id, nama_kelas, id_jenjang, id_jurusan FROM kelas WHERE is_active = 1";
        if ($tenantId) { $q .= " AND tenant_id = :tenant_id"; }
        $q .= " ORDER BY nama_kelas ASC";
        $stmt = $db->prepare($q);
        if ($tenantId) { $stmt->execute(['tenant_id' => $tenantId]); } else { $stmt->execute(); }
        $res['kelas'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Fetch Pendidikan
        $q = "SELECT id, nama_pendidikan FROM pendidikan WHERE is_active = 1";
        if ($tenantId) { $q .= " AND tenant_id = :tenant_id"; }
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
                $stmt = $db->query("SELECT id_provinsi, nama_provinsi FROM provinsi ORDER BY nama_provinsi ASC");
                $this->jsonResponse($stmt->fetchAll(\PDO::FETCH_ASSOC));
            }
            
            if ($action === 'get_kota') {
                $idProv = $_GET['id_provinsi'] ?? 0;
                $stmt = $db->prepare("SELECT id_kota, id_provinsi, nama_kota FROM kota WHERE id_provinsi = ? ORDER BY nama_kota ASC");
                $stmt->execute([$idProv]);
                $this->jsonResponse($stmt->fetchAll(\PDO::FETCH_ASSOC));
            }
            
            if ($action === 'get_kecamatan') {
                $idKota = $_GET['id_kota'] ?? 0;
                $stmt = $db->prepare("SELECT id_kecamatan, id_kota, nama_kecamatan FROM kecamatan WHERE id_kota = ? ORDER BY nama_kecamatan ASC");
                $stmt->execute([$idKota]);
                $this->jsonResponse($stmt->fetchAll(\PDO::FETCH_ASSOC));
            }
            
            if ($action === 'get_kelurahan') {
                $idKec = $_GET['id_kecamatan'] ?? 0;
                $stmt = $db->prepare("SELECT id_kelurahan, id_kecamatan, nama_kelurahan FROM kelurahan WHERE id_kecamatan = ? ORDER BY nama_kelurahan ASC");
                $stmt->execute([$idKec]);
                $this->jsonResponse($stmt->fetchAll(\PDO::FETCH_ASSOC));
            }
            
            if ($action === 'get_all_kota') {
                $stmt = $db->query("SELECT id_kota, nama_kota FROM kota ORDER BY nama_kota ASC");
                $this->jsonResponse($stmt->fetchAll(\PDO::FETCH_ASSOC));
            }
            
            if ($action === 'get_academic_options') {
                $tenantId = $_GET['tenant_id'] ?? $_SESSION['tenant_id'] ?? null;
                $res = $this->getAcademicOptions($tenantId);
                $this->jsonResponse($res);
            }
            
            $this->jsonResponse(['error' => 'Aksi AJAX tidak dikenal.'], 400);
        } catch (\Throwable $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }


    /**
     * Save draft state into session
     * POST /siswa/save-draft
     */
    public function saveDraft(): void {
        $input = $this->getJsonInput();
        if (empty($input)) {
            $input = $this->sanitizeInput($_POST);
        }
        
        $_SESSION['siswa_draft'] = $input;
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Draft berhasil disimpan']);
        exit;
    }

    /**
     * Validate uploaded files: extension, MIME type, size, and tenant storage limit.
     * Called BEFORE any file is touched on disk — if this adds errors, no file is uploaded.
     *
     * @param ?string $tenantId     Tenant UUID for storage limit check
     * @param array   $existingSizes Current file sizes from DB (keyed by field name)
     * @param array   &$errors      Validation errors array (passed by reference)
     */
    private function validateUploadedFiles(?string $tenantId, array $existingSizes, array &$errors): void {
        $fileKeys = [
            'foto_profil', 'berkas_kk', 'berkas_akta', 'berkas_ijazah_sd',
            'berkas_ijazah_smp', 'berkas_ijazah_sma', 'berkas_mutasi_masuk',
            'berkas_mutasi_keluar', 'berkas_kip', 'berkas_pernyataan_baru', 'berkas_pernyataan_tka'
        ];

        // Allowed extensions → their valid MIME types
        $allowedMimes = [
            'jpg'  => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png'  => ['image/png'],
            'pdf'  => ['application/pdf'],
        ];

        $netChange = 0;

        foreach ($fileKeys as $key) {
            if (!isset($_FILES[$key]) || $_FILES[$key]['error'] !== UPLOAD_ERR_OK) {
                continue;
            }

            $tmpPath   = $_FILES[$key]['tmp_name'];
            $origName  = $_FILES[$key]['name'];
            $fileSize  = $_FILES[$key]['size'];
            $fieldLabel = ucwords(str_replace('_', ' ', $key));

            // 0. Security check: Only Admins/Super Admins can upload statements
            $roleName = $_SESSION['role_name'] ?? '';
            if ($roleName === 'siswa' && in_array($key, ['berkas_pernyataan_baru', 'berkas_pernyataan_tka'])) {
                $errors[$key] = "Siswa tidak diizinkan mengunggah berkas {$fieldLabel}.";
                continue;
            }

            // 1. Size check (500 KB hard limit)
            if ($fileSize > 500 * 1024) {
                $errors[$key] = "Ukuran {$fieldLabel} melebihi batas maksimal 500 KB.";
                continue;
            }

            // 2. Extension check — use pathinfo() (safe against double-extension tricks)
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            if (!array_key_exists($ext, $allowedMimes)) {
                $errors[$key] = "Format {$fieldLabel} tidak valid. Diizinkan: jpg, jpeg, png, pdf.";
                continue;
            }

            // 3. Server-side MIME-type check (reads actual file magic bytes — cannot be faked)
            if (function_exists('finfo_open')) {
                $finfo    = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $tmpPath);
                finfo_close($finfo);

                if (!in_array($mimeType, $allowedMimes[$ext], true)) {
                    $errors[$key] = "Konten {$fieldLabel} tidak sesuai ekstensi. Kemungkinan file berbahaya.";
                    continue;
                }
            }

            // 4. Accumulate net storage change for tenant limit check
            $oldSize    = $existingSizes[$key] ?? 0;
            $netChange += max(0, $fileSize - $oldSize);
        }

        // 5. Check tenant storage quota (only if net change > 0 and no prior errors)
        if ($netChange > 0 && !empty($tenantId) && empty($errors)) {
            if (!\App\Core\StorageGuard::checkStorageLimit($tenantId, $netChange)) {
                $errors['storage_limit'] = 'Penyimpanan sekolah penuh (melebihi limit paket aktif sekolah).';
            }
        }
    }

    /**
     * Handle file uploads securely.
     *
     * SECURITY: Old files are NOT deleted here. Paths are returned via $oldPathsToDelete
     * and deleted only AFTER a successful DB commit (post-commit cleanup).
     * This prevents permanent file loss if the DB transaction rolls back.
     *
     * @param  string $tenantId         Tenant UUID (from session — not from user input)
     * @param  string $siswaId          Siswa UUID
     * @param  array  $existingFiles    Current DB paths keyed by field name
     * @param  array  $existingSizes    Current DB file sizes keyed by field name
     * @param  array  &$uploadedPaths   Absolute paths of NEW files (for cleanup on exception)
     * @param  array  &$oldPathsToDelete Absolute paths of OLD files (deleted post-commit)
     * @return array  ['files' => [...], 'sizes' => [...]]
     */
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

        $baseDir = __DIR__ . '/../../storage/app/public/uploads/' . $tenantId . '/' . $siswaId . '/';

        // Trusted prefix — any existing DB path MUST start with this to be safe to delete
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

            // Double-check size & extension (primary check done in validateUploadedFiles)
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if (!in_array($fileExtension, ['jpg', 'jpeg', 'png', 'pdf'], true)) {
                continue;
            }
            if ($fileSize > 500 * 1024) {
                continue;
            }

            // Create directory if needed
            if (!is_dir($baseDir)) {
                mkdir($baseDir, 0755, true);
            }

            // Generate cryptographically-safe hash filename (no original name on disk)
            $newFileName = bin2hex(random_bytes(20)) . '.' . $fileExtension;
            $destPath    = $baseDir . $newFileName;

            if (!move_uploaded_file($fileTmpPath, $destPath)) {
                throw new \Exception('Gagal mengunggah berkas ' . str_replace('_', ' ', $key) . '.');
            }

            // Track new file for rollback cleanup
            $uploadedPaths[] = $destPath;

            // --- SECURITY: validate old path before scheduling deletion ---
            $oldRelativePath = $existingFiles[$key] ?? '';
            if (!empty($oldRelativePath)) {
                // Prevent path traversal: only delete if path is within this tenant+siswa dir
                if (str_starts_with($oldRelativePath, $trustedPrefix)) {
                    $oldAbsPath = __DIR__ . '/../../storage/app/public/' . $oldRelativePath;
                    // Schedule for post-commit deletion (NOT deleted here)
                    if (file_exists($oldAbsPath)) {
                        $oldPathsToDelete[] = $oldAbsPath;
                    }
                } else {
                    // Suspicious path — log and skip deletion (do NOT delete)
                    error_log('[SECURITY] Blocked deletion of suspicious old file path: ' . $oldRelativePath . ' for siswa ' . $siswaId);
                }
            }

            $relativeDbPath     = $trustedPrefix . $newFileName;
            $uploaded[$key]     = $relativeDbPath;
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
        $provinces = $db->query("SELECT id_provinsi, nama_provinsi FROM provinsi ORDER BY nama_provinsi ASC")->fetchAll(\PDO::FETCH_ASSOC);
        $cities = $db->query("SELECT id_kota, nama_kota FROM kota ORDER BY nama_kota ASC")->fetchAll(\PDO::FETCH_ASSOC);
        $academicOptions = $this->getAcademicOptions(null);

        $draft = $_SESSION['siswa_draft'] ?? null;

        $data = [
            'title' => 'Tambah Siswa Baru',
            'user_nama' => $_SESSION['nama_lengkap'],
            'user_role' => $_SESSION['role_name'],
            'provinces_list' => $provinces,
            'cities_list' => $cities,
            'academic_options' => $academicOptions,
            'draft' => $draft
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
        
        // Set tenant ID dinamis jika Super Admin saat create (sebelum validasi)
        $tenantId = SessionManager::getTenantId() ?: ($input['tenant_id'] ?? null);
        if (SessionManager::getTenantId() === null && !empty($tenantId)) {
            $this->siswaModel->setTenantId($tenantId);
        }

        $errors = $this->validateSiswaData($input);

        // Validate uploaded files (TUGAS 2: size and format check)
        $this->validateUploadedFiles($tenantId, [], $errors);

        if (!empty($errors)) {
            $db = \App\Config\Database::getConnection();
            $provinces = $db->query("SELECT id_provinsi, nama_provinsi FROM provinsi ORDER BY nama_provinsi ASC")->fetchAll(\PDO::FETCH_ASSOC);
            $cities = $db->query("SELECT id_kota, nama_kota FROM kota ORDER BY nama_kota ASC")->fetchAll(\PDO::FETCH_ASSOC);
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

            // Upload files into specific student directory under tenant directory
            $uploadResult = $this->uploadFiles($tenantId, $siswaId, [], [], $uploadedPaths);
            $input = array_merge($input, $uploadResult['files']);
            $input['file_sizes'] = $uploadResult['sizes'];

            $this->siswaModel->create($input);
            \App\Helpers\ActivityLogger::record('INSERT', 'siswa', $siswaId, null, $input);

            $db->commit();

            // Clear draft session after successful creation
            unset($_SESSION['siswa_draft']);

            $this->redirectWithSuccess('Data siswa berhasil ditambahkan.', '/dapodik-spmb/pengguna');
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            // Clean up files uploaded in this request
            foreach ($uploadedPaths as $path) {
                if (file_exists($path)) {
                    @unlink($path);
                }
            }
            error_log("Gagal tambah siswa: " . $e->getMessage());
            $this->redirectWithError('Terjadi kegagalan sistem saat menyimpan data: ' . $e->getMessage(), '/dapodik-spmb/pengguna');
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
            $this->redirectWithError('ID siswa tidak valid.', '/dapodik-spmb/pengguna');
        }

        // Keamanan: Jika yang login adalah Siswa, hanya boleh edit datanya sendiri
        $roleName = $_SESSION['role_name'] ?? '';
        $sessionUserId = $_SESSION['user_id'] ?? '';
        if ($roleName === 'siswa' && $id !== $sessionUserId) {
            http_response_code(403);
            die("<h1>403 Forbidden</h1><p>Anda tidak diizinkan mengubah data orang lain.</p>");
        }

        $siswa = $this->siswaModel->findFullById($id);
        if (!$siswa) {
            $this->redirectWithError('Data siswa tidak ditemukan atau Anda tidak memiliki akses.', '/dapodik-spmb/pengguna');
        }

        // Keamanan: Jika yang login adalah Siswa, dan status sudah Lulus/Pindah, kunci akses
        if ($roleName === 'siswa') {
            $statusDiDb = $siswa['status'] ?? 'Aktif';
            if ($statusDiDb === 'Lulus' || $statusDiDb === 'Pindah') {
                http_response_code(403);
                echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>403 Data Dikunci</title>'
                   . '<link href="/dapodik-spmb/assets/css/bootstrap.min.css" rel="stylesheet">'
                   . '</head><body class="bg-light d-flex align-items-center justify-content-center" style="min-height:100vh;">'
                   . '<div class="card shadow-sm p-5 text-center" style="max-width:480px;">'
                   . '<div class="text-warning fs-1 mb-3"><i class="bi bi-lock-fill"></i></div>'
                   . '<h4 class="fw-bold mb-2">Data Telah Dikunci</h4>'
                   . '<p class="text-muted">Akses ditolak. Data Anda telah dikunci oleh sistem (Status: ' . htmlspecialchars($statusDiDb) . '). Hubungi Admin Sekolah jika ada kesalahan data.</p>'
                   . '<a href="/dapodik-spmb/dashboard" class="btn btn-primary mt-3">Kembali ke Dashboard</a>'
                   . '</div>'
                   . '<link href="/dapodik-spmb/assets/css/bootstrap-icons.css" rel="stylesheet">'
                   . '</body></html>';
                exit;
            }
        }

        $tenantId = $siswa['tenant_id'] ?? null;
        if (SessionManager::getTenantId() === null && isset($tenantId)) {
            $this->siswaModel->setTenantId($tenantId);
        }

        $db = \App\Config\Database::getConnection();
        $provinces       = $db->query("SELECT id_provinsi, nama_provinsi FROM provinsi ORDER BY nama_provinsi ASC")->fetchAll(\PDO::FETCH_ASSOC);
        $cities          = $db->query("SELECT id_kota, nama_kota FROM kota ORDER BY nama_kota ASC")->fetchAll(\PDO::FETCH_ASSOC);
        $academicOptions = $this->getAcademicOptions($tenantId);

        // Sertakan status siswa ke view agar form dapat menampilkan READ-ONLY mode
        $data = [
            'title'           => 'Edit Data Siswa',
            'siswa'           => $siswa,
            'siswa_status'    => $siswa['status'] ?? 'Aktif', // Digunakan view untuk state lock UI
            'user_nama'       => $_SESSION['nama_lengkap'],
            'user_role'       => $_SESSION['role_name'],
            'provinces_list'  => $provinces,
            'cities_list'     => $cities,
            'academic_options'=> $academicOptions
        ];

        $this->render('tambah_siswa', $data);
    }

    /**
     * Simpan Perubahan Data Siswa (POST)
     * POST /siswa/update
     */
    public function update(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectWithError('Metode request tidak diizinkan.', '/dapodik-spmb/pengguna');
        }

        $id = $_POST['id'] ?? '';
        if (empty($id)) {
            $this->redirectWithError('ID siswa tidak valid.', '/dapodik-spmb/pengguna');
        }

        // Keamanan: Jika yang login adalah Siswa, hanya boleh update datanya sendiri
        $roleName = $_SESSION['role_name'] ?? '';
        $sessionUserId = $_SESSION['user_id'] ?? '';
        if ($roleName === 'siswa' && $id !== $sessionUserId) {
            http_response_code(403);
            die("<h1>403 Forbidden</h1><p>Anda tidak diizinkan mengubah data orang lain.</p>");
        }

        $siswa = $this->siswaModel->findById($id);
        if (!$siswa) {
            $this->redirectWithError('Data siswa tidak ditemukan atau Anda tidak memiliki akses.', '/dapodik-spmb/pengguna');
        }

        // ================================================================
        // STATE LOCK GATEKEEPER (Spesifikasi No. 2)
        // Jika yang login adalah role 'siswa' DAN status di DB sudah 'Lulus',
        // blokir mutasi pada semua tabel data pokok siswa.
        //
        // PENTING: Status diambil langsung dari DB (bukan session) untuk
        // mencegah manipulasi session oleh user (session tampering bypass).
        // ================================================================
        if ($roleName === 'siswa') {
            $statusDiDb = $siswa['status'] ?? 'Aktif';
            if ($statusDiDb === 'Lulus' || $statusDiDb === 'Pindah') {
                $isAjaxCheck = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
                               isset($_POST['current_step']);

                $msg = 'Akses ditolak. Data Anda telah dikunci oleh sistem (Status: ' . htmlspecialchars($statusDiDb) . '). Hubungi Admin Sekolah jika ada kesalahan data.';

                if ($isAjaxCheck) {
                    header('Content-Type: application/json');
                    http_response_code(403);
                    echo json_encode(['success' => false, 'error' => $msg]);
                    exit;
                }

                http_response_code(403);
                // Render halaman error informatif (tidak sekedar die())
                echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>403 Data Dikunci</title>'
                   . '<link href="/dapodik-spmb/assets/css/bootstrap.min.css" rel="stylesheet">'
                   . '</head><body class="bg-light d-flex align-items-center justify-content-center" style="min-height:100vh;">'
                   . '<div class="card shadow-sm p-5 text-center" style="max-width:480px;">'
                   . '<div class="text-warning fs-1 mb-3"><i class="bi bi-lock-fill"></i></div>'
                   . '<h4 class="fw-bold mb-2">Data Telah Dikunci</h4>'
                   . '<p class="text-muted">' . htmlspecialchars($msg) . '</p>'
                   . '<a href="/dapodik-spmb/dashboard" class="btn btn-primary mt-3">Kembali ke Dashboard</a>'
                   . '</div>'
                   . '<link href="/dapodik-spmb/assets/css/bootstrap-icons.css" rel="stylesheet">'
                   . '</body></html>';
                exit;
            }
        }
        // END STATE LOCK GATEKEEPER

        $tenantId = SessionManager::getTenantId() ?: ($siswa['tenant_id'] ?? null);
        // Set tenant ID dinamis jika Super Admin
        if (SessionManager::getTenantId() === null && isset($tenantId)) {
            $this->siswaModel->setTenantId($tenantId);
        }

        $currentStep = isset($_POST['current_step']) ? (int) $_POST['current_step'] : null;
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
                  || $currentStep !== null;

        $input = $this->sanitizeInput($_POST);
        
        // Proteksi: Siswa tidak boleh mengubah NAMA, NISN, dan NIS
        if ($roleName === 'siswa') {
            $input['nama_lengkap'] = $siswa['nama_lengkap'];
            $input['nisn'] = $siswa['nisn'];
            $input['nis'] = $siswa['nis'];
        }

        $errors = $this->validateSiswaData($input, $id, $currentStep);

        // Fetch existing files and sizes only if full submit or on step 5 (Registrasi & Berkas)
        $existingSizes = [];
        $existing = [];
        $db = \App\Config\Database::getConnection();

        if ($currentStep === null || $currentStep === 5) {
            $stmt = $db->prepare("SELECT foto_profil FROM rincian_pelajar WHERE id_siswa = ?");
            $stmt->execute([$id]);
            $existing['foto_profil'] = $stmt->fetchColumn() ?: '';
            
            $stmt = $db->prepare("SELECT berkas_kk, berkas_akta, berkas_ijazah_sd, berkas_ijazah_smp, berkas_ijazah_sma, berkas_mutasi_masuk, berkas_mutasi_keluar, berkas_kip, berkas_pernyataan_baru, berkas_pernyataan_tka, file_sizes FROM dokumen WHERE id_siswa = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
            
            if (!empty($row['file_sizes'])) {
                $existingSizes = json_decode($row['file_sizes'], true) ?: [];
            }
            unset($row['file_sizes']);
            
            foreach ($row as $k => $v) {
                $existing[$k] = $v ?: '';
            }

            // Validate uploaded files (size & format check)
            $this->validateUploadedFiles($tenantId, $existingSizes, $errors);
        }

        if (!empty($errors)) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'errors' => $errors]);
                exit;
            }

            $provinces = $db->query("SELECT id_provinsi, nama_provinsi FROM provinsi ORDER BY nama_provinsi ASC")->fetchAll(\PDO::FETCH_ASSOC);
            $cities = $db->query("SELECT id_kota, nama_kota FROM kota ORDER BY nama_kota ASC")->fetchAll(\PDO::FETCH_ASSOC);
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

        $uploadedPaths    = []; // new file abs paths — cleaned up on exception
        $oldPathsToDelete = []; // old file abs paths — deleted only AFTER commit
        try {
            $db->beginTransaction();

            // Hash password jika diisi sebelum diupdate ke DB
            if (!empty($input['password'])) {
                $input['password'] = password_hash($input['password'], PASSWORD_BCRYPT);
            } else {
                unset($input['password']);
            }

            $uploadedFilesForJson = [];
            if ($currentStep === null || $currentStep === 5) {
                // uploadFiles: moves new files to disk, schedules old paths for post-commit deletion
                $uploadResult = $this->uploadFiles(
                    $tenantId, $id,
                    $existing, $existingSizes,
                    $uploadedPaths,       // new files (cleanup on throw)
                    $oldPathsToDelete     // old files (deleted after commit)
                );
                $input = array_merge($input, $uploadResult['files']);
                $input['file_sizes'] = $uploadResult['sizes'];
                $uploadedFilesForJson = $uploadResult['files'];
            }

            $this->siswaModel->update($id, $input);
            \App\Helpers\ActivityLogger::record('UPDATE', 'siswa', $id, $siswa, $input);

            // ----------------------------------------------------------------
            // COMMIT — data is safe in DB. Now permanently delete old files.
            // If deletions fail here, it is a disk-space issue only (not data
            // integrity issue). Log it but do NOT roll back.
            // ----------------------------------------------------------------
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
                ]);
                exit;
            }

            $this->redirectWithSuccess('Data siswa berhasil diperbarui.', '/dapodik-spmb/pengguna');

        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            // Clean up only NEW files uploaded in this request.
            // Old files are NEVER touched here — they remain safe on disk.
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
                ]);
                exit;
            }
            $this->redirectWithError(
                'Terjadi kegagalan sistem saat memperbarui data: ' . $e->getMessage(),
                '/dapodik-spmb/pengguna'
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
            $this->redirectWithError('Metode request tidak diizinkan.', '/dapodik-spmb/pengguna');
        }

        $id = $_POST['id'] ?? '';
        if (empty($id)) {
            $this->redirectWithError('ID siswa tidak valid.', '/dapodik-spmb/pengguna');
        }

        $siswa = $this->siswaModel->findById($id);
        if (!$siswa) {
            $this->redirectWithError('Data siswa tidak ditemukan atau Anda tidak memiliki akses.', '/dapodik-spmb/pengguna');
        }

        // Set tenant ID dinamis jika Super Admin
        if (SessionManager::getTenantId() === null && isset($siswa['tenant_id'])) {
            $this->siswaModel->setTenantId($siswa['tenant_id']);
        }

        try {
            $this->siswaModel->delete($id);
            \App\Helpers\ActivityLogger::record('DELETE', 'siswa', $id, $siswa, null);
            $this->redirectWithSuccess('Data siswa berhasil dihapus.', '/dapodik-spmb/pengguna');
        } catch (\PDOException $e) {
            error_log("Gagal hapus siswa: " . $e->getMessage());
            $this->redirectWithError('Terjadi kegagalan sistem saat menghapus data.', '/dapodik-spmb/pengguna');
        }
    }

    /**
     * Sanitasi Input Dasar
     */
    private function sanitizeInput(array $data): array {
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $val = trim($value);
                if ($key === 'ukuran_seragam_sekolah' || $key === 'ukuran_seragam_olahraga') {
                    $val = strtoupper($val);
                }
                $sanitized[$key] = $val;
            } else {
                $sanitized[$key] = $value;
            }
        }
        return $sanitized;
    }

    /**
     * Validasi Data Siswa di Sisi Server (Server-Side Validation)
     */
    private function validateSiswaData(array $data, ?string $excludeId = null, ?int $currentStep = null): array {
        $errors = [];

        // --- LANGKAH 1: DATA POKOK & AKADEMIK ---
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

            if (!empty($data['nisn'])) {
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

            if (!empty($data['tanggal_lahir'])) {
                $d = \DateTime::createFromFormat('Y-m-d', $data['tanggal_lahir']);
                if (!$d || $d->format('Y-m-d') !== $data['tanggal_lahir']) {
                    $errors['tanggal_lahir'] = 'Format tanggal lahir tidak valid (gunakan format YYYY-MM-DD).';
                }
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

        // --- LANGKAH 2: DETAIL ALAMAT & KONTAK ---
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

        // --- LANGKAH 3: KONDISI FISIK, RIWAYAT & KESEJAHTERAAN ---
        if ($currentStep === 3 || $currentStep === null) {
            if (!isset($data['tinggi_badan']) || $data['tinggi_badan'] === '' || $data['tinggi_badan'] < 30) {
                $errors['tinggi_badan'] = 'Tinggi badan wajib diisi minimal 30 cm.';
            }
            if (!isset($data['berat_badan']) || $data['berat_badan'] === '' || $data['berat_badan'] < 5) {
                $errors['berat_badan'] = 'Berat badan wajib diisi minimal 5 kg.';
            }
            if (!isset($data['lingkar_kepala']) || $data['lingkar_kepala'] === '' || $data['lingkar_kepala'] < 20) {
                $errors['lingkar_kepala'] = 'Lingkar kepala wajib diisi minimal 20 cm.';
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

        // --- LANGKAH 4: DATA ORANG TUA & WALI ---
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
            if (empty($data['tahun_lahir_ibu']) || $data['tahun_lahir_ibu'] < 1930 || $data['tahun_lahir_ibu'] > 2020) {
                $errors['tahun_lahir_ibu'] = 'Tahun lahir Ibu kandung wajib diisi (1930-2020).';
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

        // --- LANGKAH 5: REGISTRASI & BERKAS DOKUMEN ---
        if ($currentStep === 5 || $currentStep === null) {
            if (empty($data['jenis_pendaftaran'])) {
                $errors['jenis_pendaftaran'] = 'Jenis pendaftaran wajib dipilih.';
            }
            
            $roleName = $_SESSION['role_name'] ?? '';
            if ($roleName !== 'siswa') {
                if (empty($data['jalur_diterima'])) {
                    $errors['jalur_diterima'] = 'Jalur diterima wajib dipilih.';
                }
            }

            if (empty($data['tanggal_masuk'])) {
                $errors['tanggal_masuk'] = 'Tanggal masuk wajib diisi.';
            }
            if (empty($data['hobi'])) {
                $errors['hobi'] = 'Hobi wajib diisi.';
            }

            // Validasi Data Keluar hanya untuk Admin/Super Admin.
            // Form keluar/mutasi TIDAK ditampilkan ke siswa, sehingga tidak wajib diisi oleh siswa.
            // Admin tetap diwajibkan mengisi jika mengubah status menjadi bukan 'Aktif'.
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

    /**
     * Helper Redirect
     */
    private function redirectWithError(string $msg, string $target = '/dapodik-spmb/pengguna'): void {
        header("Location: {$target}?error=" . urlencode($msg));
        exit;
    }

    private function redirectWithSuccess(string $msg, string $target = '/dapodik-spmb/pengguna'): void {
        header("Location: {$target}?success=" . urlencode($msg));
        exit;
    }

    private function generateUuidV4(): string {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
