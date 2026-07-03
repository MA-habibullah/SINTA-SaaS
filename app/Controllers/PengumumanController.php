<?php

namespace App\Controllers;

use App\Models\PengumumanModel;
use App\Models\KategoriPengumumanModel;
use App\Core\SessionManager;
use PDO;

class PengumumanController extends BaseController {
    private PengumumanModel $pengumumanModel;
    private KategoriPengumumanModel $kategoriModel;

    public function __construct() {
        parent::__construct();
        SessionManager::requireLogin();
        
        $role = $_SESSION['role_name'] ?? '';
        // Hanya super_admin, admin, dan humas yang boleh mengelola pengumuman
        if (!in_array($role, ['super_admin', 'operator_sekolah', 'humas'])) {
            http_response_code(403);
            die("403 - Forbidden: Anda tidak memiliki akses ke Manajemen Pengumuman.");
        }
        
        $this->pengumumanModel = new PengumumanModel($this->tenantId);
        $this->kategoriModel = new KategoriPengumumanModel($this->tenantId);
    }

    public function index(): void {
        $filters = [
            'search' => $_GET['search'] ?? '',
            'kategori_id' => $_GET['kategori'] ?? '',
            'tanggal' => $_GET['tanggal'] ?? '',
            'tenant_id' => $_GET['tenant'] ?? ''
        ];
        
        $isSuperAdmin = ($_SESSION['role_name'] === 'super_admin');
        $mustSelectTenant = false;
        
        if ($isSuperAdmin && empty($filters['tenant_id'])) {
            $data = [];
            $kategoriList = [];
            $mustSelectTenant = true;
        } else {
            $data = $this->pengumumanModel->getAll(100, 0, $filters);
            $kategoriList = $this->kategoriModel->getAll($filters);
        }
        
        $tenants = [];
        if ($isSuperAdmin) {
            $db = \App\Config\Database::getConnection();
            $tenants = $db->query("SELECT id, nama_sekolah FROM tenants WHERE deleted_at IS NULL ORDER BY nama_sekolah ASC")->fetchAll(\PDO::FETCH_ASSOC);
        }
        
        $this->render('humas/pengumuman', [
            'title' => 'Manajemen Pengumuman',
            'pengumuman' => $data,
            'kategoriList' => $kategoriList,
            'roleList' => $this->getRolesList(),
            'tenants' => $tenants,
            'isSuperAdmin' => $isSuperAdmin,
            'mustSelectTenant' => $mustSelectTenant,
            'filters' => $filters
        ]);
    }
    
    private function getRolesList(): array {
        $db = \App\Config\Database::getConnection();
        return $db->query("SELECT id, nama_role FROM roles WHERE nama_role != 'super_admin' ORDER BY nama_role ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function store(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed'], 405);
        }

        $judul = trim($_POST['judul'] ?? '');
        $isi_pengumuman = trim($_POST['isi_pengumuman'] ?? '');
        $visibilitas = $_POST['visibilitas'] ?? 'public';
        $kategori_id = $_POST['kategori_id'] ?? '';
        $target_roles = $_POST['target_roles'] ?? [];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($judul) || empty($isi_pengumuman)) {
            $this->redirectWithError("Judul dan isi pengumuman wajib diisi!");
        }

        $lampiranPath = null;
        if (isset($_FILES['lampiran']) && $_FILES['lampiran']['error'] === UPLOAD_ERR_OK) {
            $lampiranPath = $this->uploadLampiran($_FILES['lampiran']);
            if (!$lampiranPath) {
                $this->redirectWithError("Gagal mengunggah lampiran. Pastikan tipe file adalah PDF, JPG, atau PNG.");
            }
        }

        $dataToSave = [
            'created_by' => $_SESSION['user_id'],
            'kategori_id' => $kategori_id !== '' ? $kategori_id : null,
            'judul' => $judul,
            'isi_pengumuman' => $isi_pengumuman,
            'visibilitas' => $visibilitas,
            'target_roles' => $target_roles,
            'lampiran_file' => $lampiranPath,
            'is_active' => $is_active
        ];

        if ($_SESSION['role_name'] === 'super_admin') {
            $tid = $_POST['tenant_id'] ?? '';
            $dataToSave['tenant_id'] = ($tid === '' || $tid === 'global') ? null : $tid;
        }

        $this->pengumumanModel->create($dataToSave);

        header("Location: /SINTA-SaaS/informasi/pengumuman");
        exit;
    }

    public function update(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed'], 405);
        }

        $id = $_POST['id'] ?? '';
        $judul = trim($_POST['judul'] ?? '');
        $isi_pengumuman = trim($_POST['isi_pengumuman'] ?? '');
        $visibilitas = $_POST['visibilitas'] ?? 'public';
        $kategori_id = $_POST['kategori_id'] ?? '';
        $target_roles = $_POST['target_roles'] ?? [];
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (empty($id) || empty($judul) || empty($isi_pengumuman)) {
            $this->redirectWithError("Data tidak lengkap!");
        }
        
        $existing = $this->pengumumanModel->findById($id);
        if (!$existing) {
            $this->redirectWithError("Pengumuman tidak ditemukan.");
        }

        $lampiranPath = $existing['lampiran_file'];
        if (isset($_FILES['lampiran']) && $_FILES['lampiran']['error'] === UPLOAD_ERR_OK) {
            $newLampiran = $this->uploadLampiran($_FILES['lampiran']);
            if ($newLampiran) {
                // Delete old file if exists
                if ($lampiranPath) {
                    @unlink(__DIR__ . '/../../storage/app/public/' . $lampiranPath);
                }
                $lampiranPath = $newLampiran;
            }
        } elseif (isset($_POST['hapus_lampiran']) && $_POST['hapus_lampiran'] == '1') {
            if ($lampiranPath) {
                @unlink(__DIR__ . '/../../storage/app/public/' . $lampiranPath);
                $lampiranPath = null;
            }
        }

        $dataToSave = [
            'judul' => $judul,
            'kategori_id' => $kategori_id !== '' ? $kategori_id : null,
            'isi_pengumuman' => $isi_pengumuman,
            'visibilitas' => $visibilitas,
            'target_roles' => $target_roles,
            'is_active' => $is_active
        ];

        if (isset($_POST['hapus_lampiran']) && $_POST['hapus_lampiran'] == '1') {
            $dataToSave['lampiran_file'] = null;
        } elseif ($lampiranPath) {
            $dataToSave['lampiran_file'] = $lampiranPath;
        }
        
        if ($_SESSION['role_name'] === 'super_admin') {
            $tid = $_POST['tenant_id'] ?? '';
            $dataToSave['tenant_id'] = ($tid === '' || $tid === 'global') ? null : $tid;
        }

        $this->pengumumanModel->update($id, $dataToSave);

        header("Location: /SINTA-SaaS/informasi/pengumuman");
        exit;
    }
    
    public function storeKategori(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed'], 405);
        }
        
        $nama_kategori = trim($_POST['nama_kategori'] ?? '');
        if (empty($nama_kategori)) {
            $this->redirectWithError("Nama kategori wajib diisi!");
        }
        
        $data = ['nama_kategori' => $nama_kategori];
        if ($_SESSION['role_name'] === 'super_admin') {
            $tid = $_POST['tenant_id'] ?? '';
            $data['tenant_id'] = ($tid === '' || $tid === 'global') ? null : $tid;
        }
        
        $this->kategoriModel->create($data);
        header("Location: /SINTA-SaaS/informasi/pengumuman");
        exit;
    }

    public function updateKategori(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed'], 405);
        }
        
        $id = $_POST['id'] ?? '';
        $nama_kategori = trim($_POST['nama_kategori'] ?? '');
        
        if (empty($id) || empty($nama_kategori)) {
            $this->redirectWithError("Data tidak lengkap!");
        }
        
        $this->kategoriModel->update($id, $nama_kategori);
        header("Location: /SINTA-SaaS/informasi/pengumuman");
        exit;
    }

    public function deleteKategori(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed'], 405);
        }
        
        $id = $_POST['id'] ?? '';
        if (empty($id)) {
            $this->redirectWithError("ID Kategori tidak valid!");
        }
        
        $this->kategoriModel->delete($id);
        header("Location: /SINTA-SaaS/informasi/pengumuman");
        exit;
    }

    public function delete(): void {
        $id = $_POST['id'] ?? '';
        $existing = $this->pengumumanModel->findById($id);
        if ($existing) {
            if ($existing['lampiran_file']) {
                @unlink(__DIR__ . '/../../storage/app/public/' . $existing['lampiran_file']);
            }
            $this->pengumumanModel->delete($id);
        }
        
        header("Location: /SINTA-SaaS/informasi/pengumuman");
        exit;
    }
    
    private function uploadLampiran(array $file): ?string {
        $allowedExts = ['pdf', 'jpg', 'jpeg', 'png'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExts)) {
            return null;
        }
        
        $fileName = bin2hex(random_bytes(16)) . '.' . $ext;
        $tenantId = $this->tenantId ?? 'global';
        $relativeDir = 'uploads/pengumuman/' . $tenantId . '/';
        $uploadDir = __DIR__ . '/../../storage/app/public/' . $relativeDir;
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $destPath = $uploadDir . $fileName;
        if (move_uploaded_file($file['tmp_name'], $destPath)) {
            return $relativeDir . $fileName;
        }
        return null;
    }

    private function redirectWithError(string $msg): void {
        $_SESSION['flash_error'] = $msg;
        header("Location: /SINTA-SaaS/informasi/pengumuman");
        exit;
    }
}
