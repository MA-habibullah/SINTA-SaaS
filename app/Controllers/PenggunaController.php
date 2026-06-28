<?php

namespace App\Controllers;

use App\Models\Pengguna;
use App\Core\SessionManager;

class PenggunaController extends BaseController {

    private Pengguna $model;

    public function __construct() {
        parent::__construct();
        
        // 1. Wajib login (Secure by Design)
        SessionManager::requireLogin();
        
        // 2. Ambil tenant ID aktif dari user session
        $tenantId = SessionManager::getTenantId();
        $this->model = new Pengguna($tenantId);
    }

    /**
     * Tampilkan halaman utama Manajemen Pengguna
     * GET /pengguna
     */
    public function index(): void {
        $roleName = $_SESSION['role_name'] ?? '';
        $title = $roleName === 'siswa' ? 'Profil Data Diri' : 'Manajemen Pengguna';
        
        $npsn = '';
        $tenantId = SessionManager::getTenantId();
        if ($tenantId !== null) {
            try {
                $db = \App\Config\Database::getConnection();
                $stmt = $db->prepare("SELECT npsn FROM tenants WHERE id = ? AND deleted_at IS NULL LIMIT 1");
                $stmt->execute([$tenantId]);
                $npsn = $stmt->fetchColumn() ?: '';
            } catch (\Throwable $e) {
                // fallback
            }
        }

        $data = [
            'title' => $title,
            'user_nama' => $_SESSION['nama_lengkap'] ?? 'User',
            'user_role' => $roleName,
            'user_npsn' => $npsn
        ];
        
        $this->render('pengguna_index', $data);
    }

    /**
     * API: Ambil data terpaginasi berdasarkan kategori aktif
     * GET /api/v1/pengguna?tab=...&search=...&page=...&per_page=...&trash=...
     */
    public function fetchApi(): void {
        $tab = $_GET['tab'] ?? '';
        if (empty($tab) || !in_array($tab, ['siswa', 'guru', 'karyawan', 'operator', 'mutasi'])) {
            $this->jsonResponse(['error' => 'Kategori pengguna tidak valid.'], 400);
        }

        $roleName = $_SESSION['role_name'] ?? '';
        if ($roleName === 'siswa' && $tab !== 'siswa') {
            $this->jsonResponse(['error' => 'Akses ditolak.'], 403);
        }

        try {
            $filters = [
                'search' => trim($_GET['search'] ?? ''),
                'page' => (int)($_GET['page'] ?? 1),
                'per_page' => (int)($_GET['per_page'] ?? 10),
                'trash' => $_GET['trash'] ?? 'false',
                'status' => trim($_GET['status'] ?? ''),
                'id_kelas' => trim($_GET['id_kelas'] ?? ''),
                'tenant_id' => trim($_GET['tenant_id'] ?? '')
            ];

            if ($roleName === 'siswa') {
                $filters['siswa_id'] = $_SESSION['user_id'] ?? '';
            }

            $result = $this->model->getPaginated($tab, $filters);
            $this->jsonResponse($result);
        } catch (\Throwable $e) {
            error_log("Failed to fetch pengguna data: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal mengambil data dari server: ' . $e->getMessage()], 500);
        }
    }

    /**
     * API: Ambil daftar tenant/sekolah (untuk dropdown Super Admin)
     * GET /api/v1/pengguna/tenants
     */
    public function getTenantsApi(): void {
        // Hanya Super Admin yang boleh mengakses endpoint ini
        if (($_SESSION['role_name'] ?? '') !== 'super_admin') {
            $this->jsonResponse(['error' => 'Akses ditolak.'], 403);
        }

        try {
            $tenants = $this->model->getTenants();
            $this->jsonResponse(['data' => $tenants]);
        } catch (\Throwable $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Ambil daftar kelas / rombel untuk filter
     * GET /api/v1/pengguna/kelas
     */
    public function getKelasApi(): void {
        $roleName = $_SESSION['role_name'] ?? '';
        $tenantId = $_GET['tenant_id'] ?? '';

        // Jika bukan Super Admin, gunakan tenant_id dari session
        if ($roleName !== 'super_admin') {
            $tenantId = \App\Core\SessionManager::getTenantId();
        }

        try {
            $db = \App\Config\Database::getConnection();
            $q = "SELECT id, nama_kelas FROM kelas WHERE is_active = 1";
            $params = [];
            if (!empty($tenantId)) {
                $q .= " AND tenant_id = :tenant_id";
                $params['tenant_id'] = $tenantId;
            }
            $q .= " ORDER BY nama_kelas ASC";

            $stmt = $db->prepare($q);
            $stmt->execute($params);
            $kelas = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $this->jsonResponse(['data' => $kelas]);
        } catch (\Throwable $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Simpan Baru / Update Data Pengguna
     * POST /api/v1/pengguna/simpan
     */
    public function storeApi(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Metode request tidak valid.'], 405);
        }

        $input = $this->getJsonInput();
        $tab = $input['tab'] ?? '';
        $id = !empty($input['id']) ? $input['id'] : null;

        if (empty($tab) || !in_array($tab, ['siswa', 'guru', 'karyawan', 'operator'])) {
            $this->jsonResponse(['error' => 'Kategori pengguna tidak valid.'], 400);
        }

        // Set tenant ID secara dinamis pada model jika Super Admin
        if (SessionManager::getTenantId() === null) {
            if ($id !== null) {
                // Untuk update, cari record-nya dulu untuk mengambil tenant_id-nya
                $exists = $this->model->findById($tab, $id);
                if ($exists && isset($exists['tenant_id'])) {
                    $this->model->setTenantId($exists['tenant_id']);
                }
            } else {
                // Untuk create, ambil tenant_id yang dipilih dari form input
                $reqTenantId = $input['tenant_id'] ?? null;
                if ($reqTenantId) {
                    $this->model->setTenantId($reqTenantId);
                }
            }
        }

        // Validasi input server-side
        $errors = $this->validateInput($tab, $input, $id);
        if (!empty($errors)) {
            $this->jsonResponse(['errors' => $errors], 422);
        }

        try {
            if ($id !== null) {
                // Proses Edit/Update
                $exists = $this->model->findById($tab, $id);
                if (!$exists) {
                    $this->jsonResponse(['error' => 'Data tidak ditemukan.'], 404);
                }

                if ($tab === 'siswa') {
                    $this->model->updateSiswa($id, $input);
                } else {
                    $this->model->updateStaff($tab, $id, $input);
                }

                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Data pengguna berhasil diperbarui.'
                ]);
            } else {
                // Proses Tambah Baru
                if ($tab === 'siswa') {
                    $insertedId = $this->model->createSiswa($input);
                } else {
                    $insertedId = $this->model->createStaff($tab, $input);
                }

                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Data pengguna berhasil ditambahkan.',
                    'id' => $insertedId
                ], 201);
            }
        } catch (\Throwable $e) {
            error_log("Failed to save pengguna: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal menyimpan data: ' . $e->getMessage()], 500);
        }
    }

    /**
     * API: Pindahkan ke Tong Sampah (Soft Delete)
     * POST /api/v1/pengguna/hapus
     */
    public function deleteApi(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Metode request tidak valid.'], 405);
        }

        $input = $this->getJsonInput();
        $tab = $input['tab'] ?? '';
        $id = !empty($input['id']) ? $input['id'] : null;

        if (empty($tab) || $id === null) {
            $this->jsonResponse(['error' => 'Kategori pengguna dan ID wajib diisi.'], 400);
        }

        try {
            $exists = $this->model->findById($tab, $id);
            if (!$exists) {
                $this->jsonResponse(['error' => 'Data tidak ditemukan.'], 404);
            }

            $this->model->delete($tab, $id);
            $this->jsonResponse([
                'success' => true,
                'message' => 'Data berhasil dipindahkan ke tong sampah.'
            ]);
        } catch (\Throwable $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Pulihkan dari Tong Sampah
     * POST /api/v1/pengguna/restore
     */
    public function restoreApi(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Metode request tidak valid.'], 405);
        }

        $input = $this->getJsonInput();
        $tab = $input['tab'] ?? '';
        $id = !empty($input['id']) ? $input['id'] : null;

        if (empty($tab) || $id === null) {
            $this->jsonResponse(['error' => 'Kategori pengguna dan ID wajib diisi.'], 400);
        }

        try {
            $this->model->restore($tab, $id);
            $this->jsonResponse([
                'success' => true,
                'message' => 'Data berhasil dikembalikan dari tong sampah.'
            ]);
        } catch (\Throwable $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Ubah Status Keaktifan (status)
     * POST /api/v1/pengguna/toggle-status
     */
    public function toggleStatusApi(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Metode request tidak valid.'], 405);
        }

        $input = $this->getJsonInput();
        $tab = $input['tab'] ?? '';
        $id = !empty($input['id']) ? $input['id'] : null;

        if (empty($tab) || $id === null) {
            $this->jsonResponse(['error' => 'Kategori pengguna dan ID wajib diisi.'], 400);
        }

        try {
            $success = $this->model->toggleStatus($tab, $id);
            if ($success) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Status keaktifan berhasil diubah.'
                ]);
            } else {
                $this->jsonResponse(['error' => 'Gagal mengubah status atau pengguna tidak memiliki akun.'], 400);
            }
        } catch (\Throwable $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Validasi Input Server-Side
     */
    private function validateInput(string $tab, array $data, ?string $id): array {
        $errors = [];

        // Validasi tenant_id untuk Super Admin saat Tambah Data
        if (SessionManager::getTenantId() === null && $id === null) {
            if (empty($data['tenant_id'])) {
                $errors['tenant_id'] = ['Sekolah / Tenant wajib dipilih.'];
            }
        }

        // 1. Validasi Nama Lengkap (Wajib untuk semua)
        if (empty($data['nama_lengkap'])) {
            $errors['nama_lengkap'] = ['Nama lengkap wajib diisi.'];
        }

        if ($tab === 'siswa') {
            // 2. Validasi Jenis Kelamin
            if (empty($data['jenis_kelamin'])) {
                $errors['jenis_kelamin'] = ['Jenis kelamin wajib dipilih.'];
            } elseif (!in_array($data['jenis_kelamin'], ['L', 'P'])) {
                $errors['jenis_kelamin'] = ['Jenis kelamin tidak valid.'];
            }

            // 3. Validasi NISN (Opsional, tapi jika diisi wajib unik & format benar)
            if (!empty($data['nisn'])) {
                if (!preg_match('/^[0-9]{10}$/', $data['nisn'])) {
                    $errors['nisn'] = ['NISN harus berupa 10 digit angka.'];
                } else {
                    $excludeUserId = null;
                    if ($id !== null) {
                        // Cari data siswa aktif untuk exclude ID
                        $oldSiswa = $this->model->findById('siswa', $id);
                        if ($oldSiswa) {
                            $excludeUserId = $oldSiswa['id'];
                        }
                    }
                    if (!$this->model->isNisnUnique($data['nisn'], $excludeUserId)) {
                        $errors['nisn'] = ['NISN sudah terdaftar di sistem nasional.'];
                    }
                }
            }

            // 4. Validasi NIS (Opsional, tapi jika diisi wajib unik per tenant)
            if (!empty($data['nis'])) {
                $excludeUserId = null;
                if ($id !== null) {
                    $oldSiswa = $this->model->findById('siswa', $id);
                    if ($oldSiswa) {
                        $excludeUserId = $oldSiswa['id'];
                    }
                }
                if (!$this->model->isNisUnique($data['nis'], $excludeUserId)) {
                    $errors['nis'] = ['NIS sudah terdaftar di sekolah ini.'];
                }
            }

            // 5. Validasi Email untuk Siswa (Opsional)
            if (!empty($data['email'])) {
                if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    $errors['email'] = ['Format email tidak valid.'];
                } else {
                    $excludeUserId = null;
                    if ($id !== null) {
                        $oldSiswa = $this->model->findById('siswa', $id);
                        if ($oldSiswa && $oldSiswa['user_id']) {
                            $excludeUserId = $oldSiswa['user_id'];
                        }
                    }
                    if (!$this->model->isEmailUnique($data['email'], $excludeUserId)) {
                        $errors['email'] = ['Email sudah terdaftar di sekolah ini.'];
                    }
                }

                // Validasi password jika email diisi
                if ($id === null && empty($data['password'])) {
                    $errors['password'] = ['Password wajib diisi jika email disediakan.'];
                } elseif (!empty($data['password']) && strlen($data['password']) < 6) {
                    $errors['password'] = ['Password minimal 6 karakter.'];
                }
            }
        } else {
            // Kategori guru, karyawan, operator
            // 2. Validasi Email (Wajib & Unik per tenant)
            if (empty($data['email'])) {
                $errors['email'] = ['Email wajib diisi.'];
            } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = ['Format email tidak valid.'];
            } else {
                $excludeUserId = null;
                if ($id !== null) {
                    $oldUser = $this->model->findById($tab, $id);
                    if ($oldUser) {
                        $excludeUserId = $oldUser['id'];
                    }
                }
                if (!$this->model->isEmailUnique($data['email'], $excludeUserId)) {
                    $errors['email'] = ['Email sudah terdaftar di sekolah ini.'];
                }
            }

            // 3. Validasi Password
            if ($id === null) {
                // Tambah data baru -> Password wajib
                if (empty($data['password'])) {
                    $errors['password'] = ['Password wajib diisi.'];
                } elseif (strlen($data['password']) < 6) {
                    $errors['password'] = ['Password minimal 6 karakter.'];
                }
            } else {
                // Edit data -> Password opsional
                if (!empty($data['password']) && strlen($data['password']) < 6) {
                    $errors['password'] = ['Password minimal 6 karakter.'];
                }
            }
        }

        return $errors;
    }

    /**
     * Unduh Excel Data Siswa
     * GET /pengguna/download-excel
     */
    public function downloadExcel(): void {
        $roleName = $_SESSION['role_name'] ?? '';
        if ($roleName === 'siswa') {
            http_response_code(403);
            die("<h1>403 Forbidden</h1><p>Anda tidak diizinkan mengakses fitur ini.</p>");
        }

        // Tentukan tenant ID filter berdasarkan hak akses
        $tenantId = null;
        if ($roleName !== 'super_admin') {
            $tenantId = SessionManager::getTenantId();
        } else {
            // Super Admin dapat memilih sekolah lewat query parameter
            $tenantId = !empty($_GET['tenant_id']) ? $_GET['tenant_id'] : null;
        }

        \App\Exports\SiswaExport::download($tenantId);
    }

    // =========================================================================
    // API AKSI: NAIKKAN KELAS & LULUSKAN SISWA
    // =========================================================================

    /**
     * API: Ambil daftar kelas untuk dropdown panel aksi
     * GET /api/v1/pengguna/aksi/kelas?tenant_id=
     */
    public function getKelasAksiApi(): void {
        $roleName = $_SESSION['role_name'] ?? '';

        if ($roleName === 'siswa') {
            $this->jsonResponse(['error' => 'Akses ditolak.'], 403);
            return;
        }

        // Tentukan tenant_id yang akan digunakan
        if ($roleName === 'super_admin') {
            $tenantId = trim($_GET['tenant_id'] ?? '');
            if (empty($tenantId)) {
                $this->jsonResponse(['error' => 'Pilih sekolah terlebih dahulu.'], 422);
                return;
            }
        } else {
            $tenantId = SessionManager::getTenantId();
        }

        try {
            $data = $this->model->getKelasForAction($tenantId);
            $this->jsonResponse(['data' => $data]);
        } catch (\Throwable $e) {
            error_log("getKelasAksiApi error: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal mengambil data kelas.'], 500);
        }
    }

    /**
     * API: Ambil daftar siswa aktif berdasarkan kelas (untuk tabel checklist)
     * GET /api/v1/pengguna/aksi/siswa?kelas_id=&tenant_id=
     */
    public function getSiswaUntukAksiApi(): void {
        $roleName = $_SESSION['role_name'] ?? '';

        if ($roleName === 'siswa') {
            $this->jsonResponse(['error' => 'Akses ditolak.'], 403);
            return;
        }

        $kelasId = (int)($_GET['kelas_id'] ?? 0);
        if ($kelasId <= 0) {
            $this->jsonResponse(['error' => 'Kelas tidak valid.'], 422);
            return;
        }

        if ($roleName === 'super_admin') {
            $tenantId = trim($_GET['tenant_id'] ?? '');
            if (empty($tenantId)) {
                $this->jsonResponse(['error' => 'Pilih sekolah terlebih dahulu.'], 422);
                return;
            }
        } else {
            $tenantId = SessionManager::getTenantId();
        }

        try {
            $data = $this->model->getSiswaByKelas($kelasId, $tenantId);
            $this->jsonResponse(['data' => $data]);
        } catch (\Throwable $e) {
            error_log("getSiswaUntukAksiApi error: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal mengambil data siswa.'], 500);
        }
    }

    /**
     * API: Eksekusi Naikkan Kelas massal
     * POST /api/v1/pengguna/aksi/naikkan-kelas
     * Body: { siswa_ids: [], id_kelas_tujuan: int, tahun_ajaran: string, catatan: string, tenant_id: string (super_admin only) }
     */
    public function naikkanKelasApi(): void {
        $roleName = $_SESSION['role_name'] ?? '';

        if (!in_array($roleName, ['super_admin', 'operator_sekolah'])) {
            $this->jsonResponse(['error' => 'Akses ditolak. Fitur ini hanya untuk Super Admin dan Admin Sekolah.'], 403);
            return;
        }

        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        // Validasi input dasar
        $siswaIds     = $body['siswa_ids']     ?? [];
        $idKelasTujuan = (int)($body['id_kelas_tujuan'] ?? 0);
        $tahunAjaran  = trim($body['tahun_ajaran'] ?? '');
        $catatan      = trim($body['catatan'] ?? '');

        $errors = [];
        if (empty($siswaIds) || !is_array($siswaIds)) {
            $errors[] = 'Pilih minimal satu siswa.';
        }
        if ($idKelasTujuan <= 0) {
            $errors[] = 'Pilih kelas tujuan.';
        }
        if (empty($tahunAjaran)) {
            $errors[] = 'Tahun ajaran wajib diisi.';
        }
        if (!empty($errors)) {
            $this->jsonResponse(['error' => implode(' ', $errors)], 422);
            return;
        }

        // Kunci tenant_id
        if ($roleName === 'super_admin') {
            $tenantId = trim($body['tenant_id'] ?? '');
            if (empty($tenantId)) {
                $this->jsonResponse(['error' => 'Super Admin wajib memilih sekolah terlebih dahulu.'], 422);
                return;
            }
        } else {
            $tenantId = SessionManager::getTenantId();
        }

        $auditData = [
            'tahun_ajaran'   => $tahunAjaran,
            'dilakukan_oleh' => $_SESSION['user_id'] ?? '',
            'nama_pelaku'    => $_SESSION['nama_lengkap'] ?? '',
            'catatan'        => $catatan ?: null
        ];

        try {
            $count = $this->model->naikkanKelas($siswaIds, $idKelasTujuan, $tenantId, $auditData);
            $this->jsonResponse([
                'success' => true,
                'message' => "{$count} siswa berhasil dinaikkan kelasnya dan riwayat telah dicatat."
            ]);
        } catch (\Throwable $e) {
            error_log("naikkanKelasApi error: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal memproses kenaikan kelas: ' . $e->getMessage()], 500);
        }
    }

    /**
     * API: Eksekusi Luluskan Siswa massal
     * POST /api/v1/pengguna/aksi/luluskan
     * Body: { siswa_ids: [], tahun_ajaran: string, catatan: string, tenant_id: string (super_admin only) }
     */
    public function luluskanSiswaApi(): void {
        $roleName = $_SESSION['role_name'] ?? '';

        if (!in_array($roleName, ['super_admin', 'operator_sekolah'])) {
            $this->jsonResponse(['error' => 'Akses ditolak. Fitur ini hanya untuk Super Admin dan Admin Sekolah.'], 403);
            return;
        }

        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $siswaIds    = $body['siswa_ids']    ?? [];
        $tahunAjaran = trim($body['tahun_ajaran'] ?? '');
        $catatan     = trim($body['catatan'] ?? '');

        $errors = [];
        if (empty($siswaIds) || !is_array($siswaIds)) {
            $errors[] = 'Pilih minimal satu siswa.';
        }
        if (empty($tahunAjaran)) {
            $errors[] = 'Tahun ajaran wajib diisi.';
        }
        if (!empty($errors)) {
            $this->jsonResponse(['error' => implode(' ', $errors)], 422);
            return;
        }

        // Kunci tenant_id
        if ($roleName === 'super_admin') {
            $tenantId = trim($body['tenant_id'] ?? '');
            if (empty($tenantId)) {
                $this->jsonResponse(['error' => 'Super Admin wajib memilih sekolah terlebih dahulu.'], 422);
                return;
            }
        } else {
            $tenantId = SessionManager::getTenantId();
        }

        $auditData = [
            'tahun_ajaran'   => $tahunAjaran,
            'dilakukan_oleh' => $_SESSION['user_id'] ?? '',
            'nama_pelaku'    => $_SESSION['nama_lengkap'] ?? '',
            'catatan'        => $catatan ?: null
        ];

        try {
            $count = $this->model->luluskanSiswa($siswaIds, $tenantId, $auditData);
            $this->jsonResponse([
                'success' => true,
                'message' => "{$count} siswa berhasil dinyatakan lulus dan riwayat telah dicatat."
            ]);
        } catch (\Throwable $e) {
            error_log("luluskanSiswaApi error: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal memproses kelulusan: ' . $e->getMessage()], 500);
        }
    }

    /**
     * API: Registrasi Cepat (Quick Add) Siswa
     * POST /api/v1/pengguna/quick-add-siswa
     */
    public function quickStoreSiswaApi(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Metode request tidak valid.'], 405);
            return;
        }

        $roleName = $_SESSION['role_name'] ?? '';
        if (empty($roleName) || in_array($roleName, ['siswa', 'guru'])) {
            $this->jsonResponse(['error' => 'Akses ditolak. Anda tidak memiliki wewenang.'], 403);
            return;
        }

        $input = $this->getJsonInput();
        $namaLengkap = isset($input['nama_lengkap']) ? trim($input['nama_lengkap']) : '';
        $nisn = isset($input['nisn']) ? trim($input['nisn']) : '';
        $tanggalLahir = isset($input['tanggal_lahir']) ? trim($input['tanggal_lahir']) : '';
        $email = isset($input['email']) ? strtolower(trim($input['email'])) : '';
        $npsn = isset($input['npsn']) ? trim($input['npsn']) : '';

        // Server-side validations
        $errors = [];
        if (empty($namaLengkap)) {
            $errors['nama_lengkap'] = ['Nama lengkap wajib diisi.'];
        }

        if (empty($nisn)) {
            $errors['nisn'] = ['NISN wajib diisi.'];
        } elseif (!preg_match('/^[0-9]{1,10}$/', $nisn)) {
            $errors['nisn'] = ['NISN harus berupa angka maksimal 10 digit.'];
        }

        if (empty($tanggalLahir)) {
            $errors['tanggal_lahir'] = ['Tanggal lahir wajib diisi.'];
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalLahir)) {
            $errors['tanggal_lahir'] = ['Format tanggal lahir harus YYYY-MM-DD.'];
        }

        if (empty($email)) {
            $errors['email'] = ['Email wajib diisi.'];
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = ['Format email tidak valid.'];
        }

        $db = \App\Config\Database::getConnection();
        $tenantId = null;

        if ($roleName === 'super_admin') {
            if (empty($npsn)) {
                $errors['npsn'] = ['Pilih instansi sekolah / masukkan NPSN.'];
            } else {
                $stmt = $db->prepare("SELECT id FROM tenants WHERE npsn = :npsn AND deleted_at IS NULL LIMIT 1");
                $stmt->execute(['npsn' => $npsn]);
                $tenant = $stmt->fetch(\PDO::FETCH_ASSOC);
                if (!$tenant) {
                    $errors['npsn'] = ['NPSN Sekolah tidak terdaftar atau tidak aktif.'];
                } else {
                    $tenantId = $tenant['id'];
                }
            }
        } else {
            $tenantId = SessionManager::getTenantId();
            if (empty($tenantId)) {
                $this->jsonResponse(['error' => 'Tenant ID tidak ditemukan pada sesi Anda.'], 400);
                return;
            }
        }

        if (!empty($errors)) {
            $this->jsonResponse(['errors' => $errors], 422);
            return;
        }

        try {
            // Check unique email
            $emailStmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = :email AND deleted_at IS NULL");
            $emailStmt->execute(['email' => $email]);
            if ($emailStmt->fetchColumn() > 0) {
                $this->jsonResponse(['errors' => ['email' => ['Email sudah terdaftar di sistem.']]], 422);
                return;
            }

            // Check unique NISN
            $nisnStmt = $db->prepare("SELECT COUNT(*) FROM siswa WHERE nisn = :nisn AND deleted_at IS NULL");
            $nisnStmt->execute(['nisn' => $nisn]);
            if ($nisnStmt->fetchColumn() > 0) {
                $this->jsonResponse(['errors' => ['nisn' => ['NISN sudah terdaftar di sistem.']]], 422);
                return;
            }

            $db->beginTransaction();

            $userId = $this->generateUuidV4();
            // Default password menggunakan Tanggal Lahir murni (YYYY-MM-DD)
            $hashedPassword = password_hash($tanggalLahir, PASSWORD_BCRYPT);

            // Insert users
            $userSql = "INSERT INTO users (id, tenant_id, role_id, nama_lengkap, email, password, status) 
                        VALUES (:id, :tenant_id, :role_id, :nama_lengkap, :email, :password, 'active')";
            $userStmt = $db->prepare($userSql);
            $userStmt->execute([
                'id' => $userId,
                'tenant_id' => $tenantId,
                'role_id' => 4,
                'nama_lengkap' => $namaLengkap,
                'email' => $email,
                'password' => $hashedPassword
            ]);

            // Insert user_roles
            $urSql = "INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, 4)";
            $urStmt = $db->prepare($urSql);
            $urStmt->execute(['user_id' => $userId]);

            // Insert siswa
            $siswaId = $this->generateUuidV4();
            $siswaSql = "INSERT INTO siswa (id, tenant_id, user_id, nisn, nama_lengkap, tanggal_lahir, password, status) 
                         VALUES (:id, :tenant_id, :user_id, :nisn, :nama_lengkap, :tanggal_lahir, :password, 'Aktif')";
            $siswaStmt = $db->prepare($siswaSql);
            $siswaStmt->execute([
                'id' => $siswaId,
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'nisn' => $nisn,
                'nama_lengkap' => $namaLengkap,
                'tanggal_lahir' => $tanggalLahir,
                'password' => $hashedPassword
            ]);

            $db->commit();

            // Log activity log
            \App\Helpers\ActivityLogger::record(
                'INSERT',
                'siswa',
                $siswaId,
                null,
                [
                    'id' => $siswaId,
                    'tenant_id' => $tenantId,
                    'user_id' => $userId,
                    'nisn' => $nisn,
                    'nama_lengkap' => $namaLengkap,
                    'tanggal_lahir' => $tanggalLahir,
                    'status' => 'Aktif',
                    'note' => 'Quick registration'
                ]
            );

            $this->jsonResponse([
                'success' => true,
                'message' => 'Registrasi cepat siswa berhasil! Akun telah aktif dengan password default (Sesuai Tanggal Lahir: YYYY-MM-DD).',
                'siswa_id' => $siswaId
            ], 201);

        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("quickStoreSiswaApi error: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal memproses registrasi cepat: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Helper to generate UUID v4
     */
    private function generateUuidV4(): string {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
