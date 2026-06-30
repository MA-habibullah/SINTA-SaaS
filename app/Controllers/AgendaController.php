<?php

namespace App\Controllers;

use App\Models\AgendaModel;
use App\Core\SessionManager;
use PDO;

class AgendaController extends BaseController {
    private AgendaModel $agendaModel;

    public function __construct() {
        parent::__construct();
        SessionManager::requireLogin();
        
        $role = $_SESSION['role_name'] ?? '';
        // Akses menu ini: super_admin, admin, kesiswaan, humas, kurikulum, sarpras
        $allowed = ['super_admin', 'operator_sekolah', 'kesiswaan', 'humas', 'kurikulum', 'sarpras'];
        if (!in_array($role, $allowed)) {
            http_response_code(403);
            die("403 - Forbidden: Anda tidak memiliki akses ke Agenda & Timeline Sekolah.");
        }
        
        $this->agendaModel = new AgendaModel($this->tenantId);
    }

    public function index(): void {
        $data = $this->agendaModel->getAll();
        
        $tenants = [];
        $isSuperAdmin = ($_SESSION['role_name'] === 'super_admin');
        if ($isSuperAdmin) {
            $db = \App\Config\Database::getConnection();
            $tenants = $db->query("SELECT id, nama_sekolah FROM tenants WHERE deleted_at IS NULL ORDER BY nama_sekolah ASC")->fetchAll(PDO::FETCH_ASSOC);
        }
        
        $this->render('sekolah/agenda_terpadu', [
            'title' => 'Agenda & Timeline Sekolah',
            'agenda' => $data,
            'roleList' => $this->getRolesList(),
            'tenants' => $tenants,
            'isSuperAdmin' => $isSuperAdmin
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
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $tanggal_mulai = $_POST['tanggal_mulai'] ?? '';
        $tanggal_selesai = $_POST['tanggal_selesai'] ?? '';
        $waktu = $_POST['waktu'] ?? '';
        $tipe = $_POST['tipe'] ?? 'Agenda Umum';
        $status_kegiatan = $_POST['status_kegiatan'] ?? 'Rencana';
        $visibilitas = $_POST['visibilitas'] ?? 'public';
        $target_roles = $_POST['target_roles'] ?? [];
        
        if (empty($judul) || empty($tanggal_mulai) || empty($tanggal_selesai)) {
            $this->redirectWithError("Judul dan Tanggal (Mulai-Selesai) wajib diisi!");
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
            'judul' => $judul,
            'deskripsi' => $deskripsi,
            'tanggal_mulai' => $tanggal_mulai,
            'tanggal_selesai' => $tanggal_selesai,
            'waktu' => $waktu,
            'tipe' => $tipe,
            'status_kegiatan' => $status_kegiatan,
            'lampiran_file' => $lampiranPath,
            'visibilitas' => $visibilitas,
            'target_roles' => $target_roles
        ];

        if ($_SESSION['role_name'] === 'super_admin') {
            $tid = $_POST['tenant_id'] ?? '';
            $dataToSave['tenant_id'] = ($tid === '' || $tid === 'global') ? null : $tid;
        }

        $this->agendaModel->create($dataToSave);

        header("Location: /SINTA-SaaS/informasi/agenda");
        exit;
    }

    public function update(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed'], 405);
        }

        $id = $_POST['id'] ?? '';
        $judul = trim($_POST['judul'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $tanggal_mulai = $_POST['tanggal_mulai'] ?? '';
        $tanggal_selesai = $_POST['tanggal_selesai'] ?? '';
        $waktu = $_POST['waktu'] ?? '';
        $tipe = $_POST['tipe'] ?? 'Agenda Umum';
        $status_kegiatan = $_POST['status_kegiatan'] ?? 'Rencana';
        $visibilitas = $_POST['visibilitas'] ?? 'public';
        $target_roles = $_POST['target_roles'] ?? [];

        if (empty($id) || empty($judul) || empty($tanggal_mulai) || empty($tanggal_selesai)) {
            $this->redirectWithError("Data tidak lengkap!");
        }
        
        $existing = $this->agendaModel->findById($id);
        if (!$existing) {
            $this->redirectWithError("Agenda tidak ditemukan.");
        }

        $lampiranPath = $existing['lampiran_file'];
        if (isset($_FILES['lampiran']) && $_FILES['lampiran']['error'] === UPLOAD_ERR_OK) {
            $newLampiran = $this->uploadLampiran($_FILES['lampiran']);
            if ($newLampiran) {
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
            'deskripsi' => $deskripsi,
            'tanggal_mulai' => $tanggal_mulai,
            'tanggal_selesai' => $tanggal_selesai,
            'waktu' => $waktu,
            'tipe' => $tipe,
            'status_kegiatan' => $status_kegiatan,
            'lampiran_file' => $lampiranPath,
            'visibilitas' => $visibilitas,
            'target_roles' => $target_roles
        ];

        if ($_SESSION['role_name'] === 'super_admin') {
            $tid = $_POST['tenant_id'] ?? '';
            $dataToSave['tenant_id'] = ($tid === '' || $tid === 'global') ? null : $tid;
        }

        $this->agendaModel->update($id, $dataToSave);

        header("Location: /SINTA-SaaS/informasi/agenda");
        exit;
    }

    public function delete(): void {
        $id = $_POST['id'] ?? '';
        $existing = $this->agendaModel->findById($id);
        if ($existing) {
            if ($existing['lampiran_file']) {
                @unlink(__DIR__ . '/../../storage/app/public/' . $existing['lampiran_file']);
            }
            $this->agendaModel->delete($id);
        }
        
        header("Location: /SINTA-SaaS/informasi/agenda");
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
        $relativeDir = 'uploads/agenda/' . $tenantId . '/';
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
        header("Location: /SINTA-SaaS/informasi/agenda");
        exit;
    }
}
