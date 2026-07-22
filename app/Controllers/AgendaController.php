<?php

namespace App\Controllers;

use App\Models\AgendaModel;
use App\Models\KategoriAgendaModel;
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
        $filterMonth = $_GET['month'] ?? date('Y-m');
        $isSuperAdmin = ($_SESSION['role_name'] === 'super_admin');
        $selectedTenant = $_GET['filter_tenant_id'] ?? '';

        if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
            $action = $_GET['action'] ?? '';
            if ($action === 'get_agenda_data') {
                $activeTenantId = $this->tenantId;
                if ($isSuperAdmin && $selectedTenant !== '') {
                    $activeTenantId = ($selectedTenant === 'global') ? null : $selectedTenant;
                }
                
                $agendaModel = new AgendaModel($activeTenantId);
                $agenda = $agendaModel->getAll();

                $events = [];
                $ganttTasks = [];
                
                if (!empty($agenda)) {
                    foreach ($agenda as $a) {
                        $events[] = [
                            'id' => $a['id'],
                            'title' => $a['judul'],
                            'start' => $a['waktu_mulai'] ?? $a['tanggal_mulai'],
                            'end' => $a['waktu_selesai'] ?? $a['tanggal_selesai'],
                            'backgroundColor' => $a['kode_warna'] ?? '#0b5ed7',
                            'borderColor' => $a['kode_warna'] ?? '#0b5ed7',
                            'extendedProps' => [
                                'fullData' => $a
                            ]
                        ];
                    }

                    // Batasi rentang waktu maksimal 6 bulan ke depan dari bulan berjalan
                    $startLimit = date('Y-m-01', strtotime('-1 month')); // Toleransi 1 bulan ke belakang
                    $endLimit = date('Y-m-t', strtotime('+5 months'));   // Total 6 bulan ke depan

                    foreach ($agenda as $a) {
                        $start = date('Y-m-d', strtotime($a['waktu_mulai'] ?? $a['tanggal_mulai']));
                        $end = date('Y-m-d', strtotime($a['waktu_selesai'] ?? $a['tanggal_selesai'] ?? $start));
                        
                        // Skip jika kegiatan berada di luar rentang 6 bulan
                        if ($start > $endLimit || $end < $startLimit) {
                            continue;
                        }

                        // Frappe Gantt requires end date > start date
                        if ($end <= $start) {
                            $end = date('Y-m-d', strtotime($start . ' + 1 day'));
                        }

                        // Potong tampilan chart visual jika melebihi batas 6 bulan
                        $displayStart = ($start < $startLimit) ? $startLimit : $start;
                        $displayEnd = ($end > $endLimit) ? $endLimit : $end;

                        $ganttTasks[] = [
                            'id' => 'Task_' . $a['id'],
                            'name' => $a['judul'],
                            'start' => $displayStart,
                            'end' => $displayEnd,
                            'progress' => 100,
                            'custom_class' => 'gantt-' . str_replace('#', '', $a['kode_warna'] ?? '#0b5ed7')
                        ];
                    }
                }

                $this->jsonResponse([
                    'success' => true,
                    'events' => $events,
                    'ganttTasks' => $ganttTasks
                ]);
                return;
            }
        }
        
        $db = \App\Config\Database::getConnection();
        $tenants = [];
        if ($isSuperAdmin) {
            $tenants = $db->query("SELECT id, nama_sekolah FROM tenants WHERE deleted_at IS NULL ORDER BY nama_sekolah ASC")->fetchAll(PDO::FETCH_ASSOC);
        }
        
        if ($isSuperAdmin && $selectedTenant === '') {
            $data = [];
            $filteredAgenda = [];
            $upcoming = [];
            $kategoriList = [];
            $pics = [];
        } else {
            $activeTenantId = $this->tenantId;
            if ($isSuperAdmin) {
                $activeTenantId = ($selectedTenant === 'global') ? null : $selectedTenant;
            }
            
            $agendaModel = new AgendaModel($activeTenantId);
            $kategoriModel = new KategoriAgendaModel($activeTenantId);
            
            $data = $agendaModel->getAll();
            $filteredAgenda = array_filter($data, function($row) use ($filterMonth) {
                $date = $row['waktu_mulai'] ?? $row['tanggal_mulai'];
                return strpos($date, $filterMonth) === 0;
            });
            
            $upcoming = $agendaModel->getUpcomingAgendas(5);
            $kategoriList = $kategoriModel->getAll();
            
            $whereTenantUsers = ($activeTenantId === null) ? "1=1" : "(tenant_id = :tenant_id OR tenant_id IS NULL)";
            $stmtUsers = $db->prepare("SELECT id, nama_lengkap FROM users WHERE $whereTenantUsers AND status = 'active' AND deleted_at IS NULL ORDER BY nama_lengkap ASC");
            if ($activeTenantId !== null) {
                $stmtUsers->bindValue(':tenant_id', $activeTenantId);
            }
            $stmtUsers->execute();
            $pics = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);
        }
        
        $this->render('sekolah/agenda_terpadu', [
            'title' => 'Agenda & Timeline Sekolah',
            'agenda' => $data,
            'filteredAgenda' => $filteredAgenda,
            'filterMonth' => $filterMonth,
            'upcoming' => $upcoming,
            'kategoriList' => $kategoriList,
            'pics' => $pics,
            'roleList' => $this->getRolesList(),
            'tenants' => $tenants,
            'isSuperAdmin' => $isSuperAdmin,
            'selectedTenant' => $selectedTenant
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
        $kategori_id = $_POST['kategori_id'] ?? null;
        $lokasi = trim($_POST['lokasi'] ?? '');
        $pic_id = $_POST['pic_id'] ?? null;
        $target_audiens = $_POST['target_audiens'] ?? 'Semua';
        $waktu_mulai = $_POST['waktu_mulai'] ?? null;
        $waktu_selesai = $_POST['waktu_selesai'] ?? null;
        $tanggal_mulai = $_POST['tanggal_mulai'] ?? '';
        $tanggal_selesai = $_POST['tanggal_selesai'] ?? '';
        $waktu = $_POST['waktu'] ?? '';
        $tipe = $_POST['tipe'] ?? 'Agenda Umum';
        $status_kegiatan = $_POST['status_kegiatan'] ?? 'Rencana';
        $visibilitas = $_POST['visibilitas'] ?? 'public';
        $target_roles = $_POST['target_roles'] ?? [];
        
        // Extract dari waktu_mulai jika form UI menggunakan datetime-local
        if (empty($tanggal_mulai) && !empty($waktu_mulai)) {
            $tanggal_mulai = date('Y-m-d', strtotime($waktu_mulai));
            $waktu = date('H:i:s', strtotime($waktu_mulai));
        }
        if (empty($tanggal_selesai)) {
            if (!empty($waktu_selesai)) {
                $tanggal_selesai = date('Y-m-d', strtotime($waktu_selesai));
            } else {
                $tanggal_selesai = $tanggal_mulai; // Fallback jika tidak diisi
            }
        }
        
        if (empty($judul) || empty($tanggal_mulai)) {
            $this->redirectWithError("Judul dan Tanggal Mulai wajib diisi!");
        }

        $lampiranPath = null;
        if (isset($_FILES['lampiran']) && $_FILES['lampiran']['error'] === UPLOAD_ERR_OK) {
            $lampiranPath = $this->uploadLampiran($_FILES['lampiran']);
            if (!$lampiranPath) {
                $this->redirectWithError("Gagal mengunggah lampiran. Pastikan tipe file adalah PDF, JPG, atau PNG.");
            }
        }

        $dataToSave = [
            'kategori_id'    => $kategori_id,
            'lokasi'         => $lokasi,
            'pic_id'         => $pic_id,
            'target_audiens' => $target_audiens,
            'waktu_mulai'    => $waktu_mulai,
            'waktu_selesai'  => $waktu_selesai,
            'created_by'     => $_SESSION['user_id'],
            'judul'          => $judul,
            'deskripsi'      => $deskripsi,
            'tanggal_mulai'  => $tanggal_mulai,
            'tanggal_selesai'=> $tanggal_selesai,
            'waktu'          => $waktu,
            'tipe'           => $tipe,
            'status_kegiatan'=> $status_kegiatan,
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
        $kategori_id = $_POST['kategori_id'] ?? null;
        $lokasi = trim($_POST['lokasi'] ?? '');
        $pic_id = $_POST['pic_id'] ?? null;
        $target_audiens = $_POST['target_audiens'] ?? 'Semua';
        $waktu_mulai = $_POST['waktu_mulai'] ?? null;
        $waktu_selesai = $_POST['waktu_selesai'] ?? null;
        $tanggal_mulai = $_POST['tanggal_mulai'] ?? '';
        $tanggal_selesai = $_POST['tanggal_selesai'] ?? '';
        $waktu = $_POST['waktu'] ?? '';
        $tipe = $_POST['tipe'] ?? 'Agenda Umum';
        $status_kegiatan = $_POST['status_kegiatan'] ?? 'Rencana';
        $visibilitas = $_POST['visibilitas'] ?? 'public';
        $target_roles = $_POST['target_roles'] ?? [];
        
        // Extract dari waktu_mulai jika form UI menggunakan datetime-local
        if (empty($tanggal_mulai) && !empty($waktu_mulai)) {
            $tanggal_mulai = date('Y-m-d', strtotime($waktu_mulai));
            $waktu = date('H:i:s', strtotime($waktu_mulai));
        }
        if (empty($tanggal_selesai)) {
            if (!empty($waktu_selesai)) {
                $tanggal_selesai = date('Y-m-d', strtotime($waktu_selesai));
            } else {
                $tanggal_selesai = $tanggal_mulai; // Fallback jika tidak diisi
            }
        }

        if (empty($id) || empty($judul) || empty($tanggal_mulai)) {
            $this->redirectWithError("Data tidak lengkap! Judul dan Tanggal Mulai wajib diisi.");
        }
        
        $existing = $this->agendaModel->findById($id);
        if (!$existing) {
            $this->redirectWithError("Agenda tidak ditemukan.");
        }

        $lampiranPath = $existing['lampiran_file'] ?? null;
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
            'kategori_id'    => $kategori_id,
            'lokasi'         => $lokasi,
            'pic_id'         => $pic_id,
            'target_audiens' => $target_audiens,
            'waktu_mulai'    => $waktu_mulai,
            'waktu_selesai'  => $waktu_selesai,
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
