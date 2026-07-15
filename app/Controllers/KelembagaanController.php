<?php

namespace App\Controllers;

use App\Models\Kelembagaan;
use App\Core\SessionManager;

class KelembagaanController extends BaseController {

    private Kelembagaan $model;

    public function __construct() {
        parent::__construct();
        
        // 1. Wajib login (Secure by Design)
        SessionManager::requireLogin();
        
        // 2. Ambil tenant ID aktif dari user session
        $tenantId = SessionManager::getTenantId();
        $this->model = new Kelembagaan($tenantId);
    }

    /**
     * Tampilkan halaman utama Master Data Kelembagaan
     * GET /master-data
     */
    public function index(): void {
        $tenantList = [];
        if (($_SESSION['role_name'] ?? '') === 'super_admin') {
            try {
                $tenantList = $this->model->getTenants();
            } catch (\Throwable) {}
        }

        $data = [
            'title'       => 'Master Data Kelembagaan',
            'user_nama'   => $_SESSION['nama_lengkap'] ?? 'User',
            'user_role'   => $_SESSION['role_name'] ?? '',
            'tenant_list' => $tenantList,
        ];
        
        $this->render('master_kelembagaan', $data);
    }

    /**
     * API: Ambil data terpaginasi untuk tabel aktif
     * GET /api/v1/kelembagaan?module=...&search=...&page=...&per_page=...&trash=...
     */
    public function fetchApi(): void {
        $module = $_GET['module'] ?? '';
        if (empty($module)) {
            $this->jsonResponse(['error' => 'Parameter modul wajib ditentukan.'], 400);
        }

        // Super Admin: boleh filter per sekolah via ?filter_tenant_id=
        // Validasi ke DB untuk mencegah spoofing (anti-IDOR)
        $isSuperAdmin = (($_SESSION['role_name'] ?? '') === 'super_admin');
        if ($isSuperAdmin && !empty($_GET['filter_tenant_id'])) {
            $tid = $_GET['filter_tenant_id'];
            try {
                $db   = \App\Config\Database::getConnection();
                $stmtV = $db->prepare("SELECT id FROM tenants WHERE id = ? AND deleted_at IS NULL LIMIT 1");
                $stmtV->execute([$tid]);
                if ($stmtV->fetchColumn()) {
                    // Set tenant filter pada model — hanya untuk query ini
                    $this->model->setTenantId($tid);
                }
                // Jika UUID tidak valid, tetap tampilkan semua (tidak difilter)
            } catch (\Throwable) {}
        }

        try {
            $filters = [
                'search'   => trim($_GET['search'] ?? ''),
                'page'     => (int)($_GET['page'] ?? 1),
                'per_page' => (int)($_GET['per_page'] ?? 10),
                'trash'    => $_GET['trash'] ?? 'false'
            ];

            $result = $this->model->getPaginated($module, $filters);
            $this->jsonResponse($result);
        } catch (\InvalidArgumentException $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            error_log("Failed to fetch kelembagaan data: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal mengambil data dari server: ' . $e->getMessage()], 500);
        }
    }

    /**
     * API: Ambil opsi data relasi (untuk select dropdown di Form)
     * GET /api/v1/kelembagaan/options?module=...
     */
    public function getOptionsApi(): void {
        $module = $_GET['module'] ?? '';
        if (empty($module)) {
            $this->jsonResponse(['error' => 'Parameter modul wajib ditentukan.'], 400);
        }

        try {
            if (SessionManager::getTenantId() === null && !empty($_GET['tenant_id'])) {
                $this->model->setTenantId($_GET['tenant_id']);
            }
            $options = $this->model->getOptions($module);
            $this->jsonResponse(['data' => $options]);
        } catch (\InvalidArgumentException $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Ambil daftar tenant/sekolah (untuk dropdown Super Admin)
     * GET /api/v1/kelembagaan/tenants
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
     * API: Simpan Baru / Update Data Kelembagaan
     * POST /api/v1/kelembagaan/simpan
     */
    public function storeApi(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Metode request tidak valid.'], 405);
        }

        // Ambil data JSON input
        $input = $this->getJsonInput();
        $module = $input['module'] ?? '';
        $id = isset($input['id']) ? (int)$input['id'] : null;

        if (empty($module)) {
            $this->jsonResponse(['error' => 'Parameter modul wajib ditentukan.'], 400);
        }

        // Set tenant ID secara dinamis pada model jika Super Admin
        if (SessionManager::getTenantId() === null) {
            if ($id !== null && $id > 0) {
                // Untuk update, cari record-nya dulu untuk mengambil tenant_id-nya
                $exists = $this->model->findById($module, $id);
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
        $errors = $this->validateInput($module, $input, $id);
        if (!empty($errors)) {
            $this->jsonResponse(['success' => false, 'errors' => $errors], 200);
        }

        try {
            if ($id !== null && $id > 0) {
                // Proses Edit/Update
                $exists = $this->model->findById($module, $id);
                if (!$exists) {
                    $this->jsonResponse(['error' => 'Data tidak ditemukan.'], 404);
                }

                $this->model->update($module, $id, $input);
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Data berhasil diperbarui.'
                ]);
            } else {
                // Proses Tambah Baru
                $insertedId = $this->model->create($module, $input);
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Data berhasil ditambahkan.',
                    'id' => $insertedId
                ], 201);
            }
        } catch (\Throwable $e) {
            error_log("Failed to save kelembagaan: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal menyimpan data: ' . $e->getMessage()], 500);
        }
    }

    /**
     * API: Pindahkan ke Tong Sampah (Soft Delete)
     * POST /api/v1/kelembagaan/hapus
     */
    public function deleteApi(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Metode request tidak valid.'], 405);
        }

        $input = $this->getJsonInput();
        $module = $input['module'] ?? '';
        $id = isset($input['id']) ? (int)$input['id'] : null;

        if (empty($module) || $id === null) {
            $this->jsonResponse(['error' => 'Parameter modul dan ID wajib diisi.'], 400);
        }

        try {
            $exists = $this->model->findById($module, $id);
            if (!$exists) {
                $this->jsonResponse(['error' => 'Data tidak ditemukan.'], 404);
            }

            $this->model->delete($module, $id);
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
     * POST /api/v1/kelembagaan/restore
     */
    public function restoreApi(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Metode request tidak valid.'], 405);
        }

        $input = $this->getJsonInput();
        $module = $input['module'] ?? '';
        $id = isset($input['id']) ? (int)$input['id'] : null;

        if (empty($module) || $id === null) {
            $this->jsonResponse(['error' => 'Parameter modul dan ID wajib diisi.'], 400);
        }

        try {
            $this->model->restore($module, $id);
            $this->jsonResponse([
                'success' => true,
                'message' => 'Data berhasil dikembalikan dari tong sampah.'
            ]);
        } catch (\Throwable $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Ubah Status Keaktifan (is_active)
     * POST /api/v1/kelembagaan/toggle-status
     */
    public function toggleStatusApi(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Metode request tidak valid.'], 405);
        }

        $input = $this->getJsonInput();
        $module = $input['module'] ?? '';
        $id = isset($input['id']) ? (int)$input['id'] : null;

        if (empty($module) || $id === null) {
            $this->jsonResponse(['error' => 'Parameter modul dan ID wajib diisi.'], 400);
        }

        try {
            $success = $this->model->toggleStatus($module, $id);
            if ($success) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Status keaktifan berhasil diubah.'
                ]);
            } else {
                $this->jsonResponse(['error' => 'Gagal mengubah status.'], 400);
            }
        } catch (\Throwable $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Validasi Input Server-Side
     */
    private function validateInput(string $module, array $data, ?int $id): array {
        $errors = [];

        // Validasi tenant_id untuk Super Admin saat Tambah Data
        if (SessionManager::getTenantId() === null && $id === null) {
            if (empty($data['tenant_id'])) {
                $errors['tenant_id'] = ['Sekolah / Tenant wajib dipilih.'];
            }
        }

        if ($module === 'kelas') {
            // Validasi khusus Kelas
            if (empty($data['id_jenjang'])) {
                $errors['id_jenjang'] = ['Jenjang pendidikan wajib diisi.'];
            }
            if (empty($data['id_jurusan'])) {
                $errors['id_jurusan'] = ['Jurusan wajib diisi.'];
            }
            if (empty($data['kode_kelas'])) {
                $errors['kode_kelas'] = ['Kode kelas wajib diisi.'];
            } elseif (!preg_match('/^[A-Za-z0-9\-_]+$/', $data['kode_kelas'])) {
                $errors['kode_kelas'] = ['Kode kelas hanya boleh berisi huruf, angka, strip, dan garis bawah.'];
            } elseif (!$this->model->isCodeUnique('kelas', $data['kode_kelas'], $id)) {
                $errors['kode_kelas'] = ['Kode kelas sudah digunakan di sekolah Anda.'];
            }

            if (empty($data['nama_kelas'])) {
                $errors['nama_kelas'] = ['Nama kelas wajib diisi.'];
            }
        } elseif ($module === 'kurikulum') {
            $namaKur = trim($data['nama_kurikulum'] ?? $data['nama'] ?? '');
            if (empty($namaKur)) {
                $errors['nama'] = ['Nama kurikulum wajib diisi.'];
            }
            if (empty($data['tipe_penilaian'])) {
                $errors['tipe_penilaian'] = ['Tipe penilaian wajib diisi.'];
            } elseif (!in_array($data['tipe_penilaian'], ['sederhana', 'klasik', 'kompleks'])) {
                $errors['tipe_penilaian'] = ['Tipe penilaian tidak valid.'];
            }
            
            // Check unique nama_kurikulum per tenant
            if (!empty($namaKur)) {
                try {
                    $db = \App\Config\Database::getConnection();
                    $tId = SessionManager::getTenantId() ?: ($data['tenant_id'] ?? null);
                    $sqlUq = "SELECT COUNT(*) FROM ref_kurikulum WHERE nama_kurikulum = ? AND deleted_at IS NULL";
                    $uqParams = [$namaKur];
                    if ($tId) {
                        $sqlUq .= " AND (tenant_id = ? OR tenant_id IS NULL)";
                        $uqParams[] = $tId;
                    } else {
                        $sqlUq .= " AND tenant_id IS NULL";
                    }
                    if ($id !== null) {
                        $sqlUq .= " AND id != ?";
                        $uqParams[] = $id;
                    }
                    $stmtUq = $db->prepare($sqlUq);
                    $stmtUq->execute($uqParams);
                    if ($stmtUq->fetchColumn() > 0) {
                        $errors['nama'] = ['Nama kurikulum tersebut sudah terdaftar.'];
                    }
                } catch (\Throwable $e) {}
            }
        } else {
            // Validasi umum untuk 7 modul lainnya
            if (empty($data['kode'])) {
                $errors['kode'] = ['Kode wajib diisi.'];
            } else {
                // Map nama fields
                $codeMap = [
                    'jenjang' => 'kode_jenjang',
                    'jurusan' => 'kode_jurusan',
                    'mata_pelajaran' => 'kode_mapel',
                    'pendidikan' => 'kode_pendidikan',
                    'program_pengajaran' => 'kode_program',
                    'tahun_ajaran' => 'tahun_ajaran',
                    'angkatan' => 'tahun_angkatan'
                ];
                $codeCol = $codeMap[$module] ?? 'kode';
                
                // Aturan validasi tambahan
                if ($module === 'angkatan') {
                    if (!preg_match('/^[0-9]{4}$/', $data['kode'])) {
                        $errors['kode'] = ['Angkatan harus berupa 4 digit tahun (contoh: 2026).'];
                    }
                } elseif ($module === 'tahun_ajaran') {
                    if (!preg_match('/^[0-9]{4}\/[0-9]{4}$/', $data['kode'])) {
                        $errors['kode'] = ['Tahun ajaran harus berformat YYYY/YYYY (contoh: 2025/2026).'];
                    }
                }

                if (empty($errors['kode']) && !$this->model->isCodeUnique($module, $data['kode'], $id)) {
                    $errors['kode'] = ['Kode sudah terdaftar di sekolah Anda.'];
                }
            }

            // Validasi Nama (untuk modul selain tahun_ajaran dan angkatan yang kodenya merangkap nama)
            if ($module !== 'tahun_ajaran' && $module !== 'angkatan') {
                if (empty($data['nama'])) {
                    $errors['nama'] = ['Nama wajib diisi.'];
                }
            }
        }

        return $errors;
    }
}
