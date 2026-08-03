<?php

namespace App\Modules\Core\Controllers;

use App\Config\Database;
use App\Core\SessionManager;
use App\Modules\Core\Models\PengumumanModel;
use PDO;

use App\Core\BaseController;

class DashboardModuleController extends BaseController {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Render the Dashboard Page
     */
    public function index(): void {
        // 1. Inisialisasi session aman
        SessionManager::start();

        // 2. Amankan Dashboard: Cek apakah session user_id telah disetel
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . $this->getBaseUrl() . '/login');
            exit;
        }

        // 3. Dapatkan tenant_id (sekolah) dari session pengguna yang sedang aktif
        $tenantId = $_SESSION['tenant_id'];
        $isSuperAdmin = ($_SESSION['role_name'] === 'super_admin');

        // CHECK IF AJAX REQUEST
        if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
            $action = $_GET['action'] ?? '';
            if ($action === 'get_dashboard_stats') {
                $db = Database::getConnection();
                try {
                    // --- A. Query Data Siswa & GTK ---
                    if ($isSuperAdmin) {
                        $stmtSiswaList = $db->query("
                            SELECT s.nis, s.nisn, s.nama_lengkap, s.jenis_kelamin, s.tempat_lahir, s.tanggal_lahir, s.alamat, t.nama_sekolah 
                            FROM siswa.siswa s
                            JOIN core.tenants t ON s.tenant_id = t.id
                            WHERE s.is_active = true 
                            ORDER BY s.nama_lengkap ASC 
                            LIMIT 20
                        ");
                        $siswaList = $stmtSiswaList->fetchAll() ?: [];

                        $stmtGtkList = $db->query("
                            SELECT u.nama_lengkap, u.email, u.is_active as status, r.nama_role, t.nama_sekolah
                            FROM core.users u
                            JOIN core.roles r ON u.role_id = r.id
                            JOIN core.tenants t ON u.tenant_id = t.id
                            WHERE LOWER(r.nama_role) = 'guru' AND u.is_active = true
                            ORDER BY u.nama_lengkap ASC
                        ");
                        $gtkList = $stmtGtkList->fetchAll() ?: [];
                    } else {
                        $stmtSiswaList = $db->prepare("
                            SELECT nis, nisn, nama_lengkap, jenis_kelamin, tempat_lahir, tanggal_lahir, alamat 
                            FROM siswa.siswa 
                            WHERE tenant_id = :tenant_id AND status_siswa = 'aktif' 
                            ORDER BY nama_lengkap ASC 
                            LIMIT 20
                        ");
                        $stmtSiswaList->execute(['tenant_id' => $tenantId]);
                        $siswaList = $stmtSiswaList->fetchAll() ?: [];

                        $stmtGtkList = $db->prepare("
                            SELECT u.nama_lengkap, u.email, u.is_active as status, r.nama_role 
                            FROM core.users u
                            JOIN core.roles r ON u.role_id = r.id
                            WHERE u.tenant_id = :tenant_id 
                              AND LOWER(r.nama_role) = 'guru' 
                              AND u.is_active = true
                            ORDER BY u.nama_lengkap ASC
                        ");
                        $stmtGtkList->execute(['tenant_id' => $tenantId]);
                        $gtkList = $stmtGtkList->fetchAll() ?: [];
                    }

                    // --- B. Query Audit Trail ---
                    $logWhere = "l.table_name = 'siswa'";
                    $logParams = [];
                    if (!$isSuperAdmin) {
                        $logWhere .= " AND l.tenant_id = :tenant_id";
                        $logParams['tenant_id'] = $tenantId;
                    }

                    $stmtLogs = $db->prepare("
                        SELECT l.*, u.nama_lengkap AS actor_name, t.nama_sekolah
                        FROM sistem.activity_logs l
                        LEFT JOIN core.users u ON l.user_id = u.id
                        LEFT JOIN core.tenants t ON l.tenant_id = t.id
                        WHERE {$logWhere}
                        ORDER BY l.created_at DESC
                        LIMIT 50
                    ");
                    $stmtLogs->execute($logParams);
                    $recentChangesRaw = $stmtLogs->fetchAll(PDO::FETCH_ASSOC) ?: [];
                    // $this->resolveLogDataIds($recentChangesRaw, $db);

                    $recentChanges = [];
                    foreach ($recentChangesRaw as $log) {
                        $oldObj = json_decode($log['old_data'] ?? '', true) ?: [];
                        $newObj = json_decode($log['new_data'] ?? '', true) ?: [];
                        $studentName = $newObj['nama_lengkap'] ?? ($oldObj['nama_lengkap'] ?? 'Tidak Diketahui');
                        $recentChanges[] = [
                            'id' => $log['id'],
                            'waktu' => $log['created_at'],
                            'action' => $log['action'],
                            'table_name' => $log['table_name'],
                            'sekolah' => $log['nama_sekolah'] ?? 'Sistem (Global)',
                            'nama_siswa' => $studentName,
                            'actor_name' => $log['actor_name'] ?? 'System',
                            'user_role' => $log['user_role'] ?? 'system',
                            'ip_address' => $log['ip_address'] ?? '127.0.0.1',
                            'old_data' => $log['old_data'],
                            'new_data' => $log['new_data']
                        ];
                    }

                    // --- C. Query Statistik Utama & Profil Sekolah ---
                    if ($isSuperAdmin) {
                        $stmtTotalSekolah = $db->query("SELECT COUNT(*) as total FROM core.tenants");
                        $totalSekolah = $stmtTotalSekolah->fetch()['total'] ?? 0;

                        $stmtActiveTenants = $db->query("SELECT COUNT(*) as total FROM core.tenants WHERE status = 'active'");
                        $activeCount = $stmtActiveTenants->fetch()['total'] ?? 0;
                        $paketAktif = "SaaS Enterprise (" . $activeCount . " Sekolah)";

                        $stmtSiswa = $db->query("SELECT COUNT(*) as total FROM siswa.siswa WHERE status_siswa = 'aktif'");
                        $totalSiswa = $stmtSiswa->fetch()['total'] ?? 0;

                        $stmtSync = $db->query("SELECT COUNT(*) as total FROM core.tenants WHERE status_sinkronisasi = 'Tersinkronisasi'");
                        $syncCount = $stmtSync->fetch()['total'] ?? 0;
                        $statusSinkronisasi = "Node OK ({$syncCount}/{$totalSekolah})";

                        $schoolInfo = [
                            'nama_sekolah' => 'Pusat Kendali SaaS (Global)',
                            'npsn' => 'PLATFORM',
                            'subdomain' => 'admin',
                            'status' => 'active',
                            'paket_aktif' => 'Global SaaS Owner',
                            'status_sinkronisasi' => '100% Online',
                            'created_at' => date('Y-m-d H:i:s')
                        ];
                    } else {
                        if ($tenantId === null) {
                            $this->jsonResponse(false, null, 'Tenant tidak valid.', 403);
                        }

                        $stmtTotalSekolah = $db->prepare("
                            SELECT COUNT(*) as total 
                            FROM core.tenants 
                            WHERE id = :tenant_id
                        ");
                        $stmtTotalSekolah->execute(['tenant_id' => $tenantId]);
                        $totalSekolah = $stmtTotalSekolah->fetch()['total'] ?? 0;

                        $stmtTenant = $db->prepare("
                            SELECT nama_sekolah, npsn, subdomain, status, paket_aktif, status_sinkronisasi, created_at 
                            FROM core.tenants 
                            WHERE id = :tenant_id
                        ");
                        $stmtTenant->execute(['tenant_id' => $tenantId]);
                        $schoolInfo = $stmtTenant->fetch() ?: [];

                        $paketAktif = $schoolInfo['paket_aktif'] ?? 'Trial';
                        $statusSinkronisasi = $schoolInfo['status_sinkronisasi'] ?? 'Offline';

                        $stmtSiswa = $db->prepare("
                            SELECT COUNT(*) as total 
                            FROM siswa.siswa 
                            WHERE tenant_id = :tenant_id AND status_siswa = 'aktif'
                        ");
                        $stmtSiswa->execute(['tenant_id' => $tenantId]);
                        $totalSiswa = $stmtSiswa->fetch()['total'] ?? 0;
                    }

                    // --- D. Fetch Pengumuman ---
                    $roleName = $_SESSION['role_name'] ?? '';
                    
                    $pengumumanModel = new \App\Modules\Core\Models\PengumumanModel($tenantId);
                    $pengumuman_list = $pengumumanModel->getActiveForUser($roleName, 1, 0) ?: [];
                    $total_pengumuman = $pengumumanModel->countActiveForUser($roleName) ?: 0;

                    $this->jsonResponse(true, [
                        'siswaList' => $siswaList,
                        'gtkList' => $gtkList,
                        'recentChanges' => $recentChanges,
                        'stats' => [
                            'nama_sekolah' => $schoolInfo['nama_sekolah'] ?? 'Sekolah',
                            'npsn' => $schoolInfo['npsn'] ?? 'NPSN',
                            'subdomain' => $schoolInfo['subdomain'] ?? 'subdomain',
                            'total_sekolah' => $totalSekolah,
                            'paket_aktif' => $paketAktif,
                            'total_siswa' => $totalSiswa,
                            'status_sinkronisasi' => $statusSinkronisasi,
                            'user_nama' => $_SESSION['nama_lengkap'] ?? 'User',
                            'user_role' => $_SESSION['role_name'] ?? 'guest',
                            'pengumuman_list' => $pengumuman_list,
                            'total_pengumuman' => $total_pengumuman,
                            'school_info' => $schoolInfo
                        ]
                    ]);
                } catch (\PDOException $e) {
                    $this->jsonResponse(false, null, $e->getMessage(), 500);
                }
            }
        }

        // Tampilkan halaman skeleton kosong (TANPA DATA dari DB)
        try {
            $db = Database::getConnection();
            $namaSekolah = 'Dashboard';
            if (!$isSuperAdmin && $tenantId !== null) {
                $stmt = $db->prepare("SELECT nama_sekolah FROM core.tenants WHERE id = ? LIMIT 1");
                $stmt->execute([$tenantId]);
                $res = $stmt->fetch();
                if ($res) {
                    $namaSekolah = $res['nama_sekolah'];
                }
            } else {
                $namaSekolah = 'Pusat Kendali SaaS (Global)';
            }
            
            $data = [
                'title' => 'Dashboard - ' . $namaSekolah,
                'user_role' => $_SESSION['role_name'],
                'user_nama' => $_SESSION['nama_lengkap']
            ];
            $this->render('core/dashboard_view', ['stats' => $data, 'data' => $data]);
        } catch (\Throwable $e) {
            $data = [
                'title' => 'Dashboard',
                'user_role' => $_SESSION['role_name'],
                'user_nama' => $_SESSION['nama_lengkap']
            ];
            $this->render('core/dashboard_view', ['stats' => $data, 'data' => $data]);
        }
    }

    /**
     * Render the Halaman Arsip Pengumuman
     */
    public function pengumumanArsip(): void {
        SessionManager::start();
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . $this->getBaseUrl() . '/login');
            exit;
        }

        $tenantId = $_SESSION['tenant_id'];
        $roleName = $_SESSION['role_name'] ?? '';
        
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) $page = 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;
        $search = trim($_GET['search'] ?? '');
        $kategori = trim($_GET['kategori'] ?? '');
        $tanggal = trim($_GET['tanggal'] ?? '');
        
        $pengumumanModel = new \App\Modules\Core\Models\PengumumanModel($tenantId);
        $total = $pengumumanModel->countActiveForUser($roleName, $search, $kategori, $tanggal);
        $list = $pengumumanModel->getActiveForUser($roleName, $limit, $offset, $search, $kategori, $tanggal);
        
        $totalPages = ceil($total / $limit);
        
        // Fetch categories for the filter dropdown
        $kategoriModel = new \App\Modules\Core\Models\KategoriPengumumanModel($tenantId);
        $kategoriList = $kategoriModel->getAll();
        
        $data = [
            'title' => 'Arsip Pengumuman',
            'pengumuman_list' => $list,
            'kategori_list' => $kategoriList,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => $totalPages,
            'search' => $search,
            'kategori' => $kategori,
            'tanggal' => $tanggal
        ];
        
        $this->render('core/pengumuman_arsip_view', $data);
    }
}

