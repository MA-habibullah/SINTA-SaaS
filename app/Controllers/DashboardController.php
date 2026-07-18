<?php

namespace App\Controllers;

use App\Config\Database;
use App\Core\SessionManager;
use App\Models\PengumumanModel;
use PDO;

class DashboardController extends BaseController {

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
            header('Location: /SINTA-SaaS/login');
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
                            FROM siswa s
                            JOIN tenants t ON s.tenant_id = t.id
                            WHERE s.deleted_at IS NULL 
                            ORDER BY s.nama_lengkap ASC 
                            LIMIT 20
                        ");
                        $siswaList = $stmtSiswaList->fetchAll() ?: [];

                        $stmtGtkList = $db->query("
                            SELECT u.nama_lengkap, u.email, u.status, r.nama_role, t.nama_sekolah
                            FROM users u
                            JOIN roles r ON u.role_id = r.id
                            JOIN tenants t ON u.tenant_id = t.id
                            WHERE r.nama_role = 'guru' AND u.deleted_at IS NULL
                            ORDER BY u.nama_lengkap ASC
                        ");
                        $gtkList = $stmtGtkList->fetchAll() ?: [];
                    } else {
                        $stmtSiswaList = $db->prepare("
                            SELECT nis, nisn, nama_lengkap, jenis_kelamin, tempat_lahir, tanggal_lahir, alamat 
                            FROM siswa 
                            WHERE tenant_id = :tenant_id AND deleted_at IS NULL 
                            ORDER BY nama_lengkap ASC 
                            LIMIT 20
                        ");
                        $stmtSiswaList->execute(['tenant_id' => $tenantId]);
                        $siswaList = $stmtSiswaList->fetchAll() ?: [];

                        $stmtGtkList = $db->prepare("
                            SELECT u.nama_lengkap, u.email, u.status, r.nama_role 
                            FROM users u
                            JOIN roles r ON u.role_id = r.id
                            WHERE u.tenant_id = :tenant_id 
                              AND r.nama_role = 'guru' 
                              AND u.deleted_at IS NULL
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
                        FROM activity_logs l
                        LEFT JOIN users u ON l.user_id = u.id
                        LEFT JOIN tenants t ON l.tenant_id = t.id
                        WHERE {$logWhere}
                        ORDER BY l.created_at DESC
                        LIMIT 50
                    ");
                    $stmtLogs->execute($logParams);
                    $recentChangesRaw = $stmtLogs->fetchAll(PDO::FETCH_ASSOC) ?: [];
                    $this->resolveLogDataIds($recentChangesRaw, $db);

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
                        $stmtTotalSekolah = $db->query("SELECT COUNT(*) as total FROM tenants WHERE deleted_at IS NULL");
                        $totalSekolah = $stmtTotalSekolah->fetch()['total'] ?? 0;

                        $stmtActiveTenants = $db->query("SELECT COUNT(*) as total FROM tenants WHERE status = 'active' AND deleted_at IS NULL");
                        $activeCount = $stmtActiveTenants->fetch()['total'] ?? 0;
                        $paketAktif = "SaaS Enterprise (" . $activeCount . " Sekolah)";

                        $stmtSiswa = $db->query("SELECT COUNT(*) as total FROM siswa WHERE deleted_at IS NULL");
                        $totalSiswa = $stmtSiswa->fetch()['total'] ?? 0;

                        $stmtSync = $db->query("SELECT COUNT(*) as total FROM tenants WHERE status_sinkronisasi = 'Tersinkronisasi' AND deleted_at IS NULL");
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
                            $this->jsonResponse(['error' => 'Tenant tidak valid.'], 403);
                        }

                        $stmtTotalSekolah = $db->prepare("
                            SELECT COUNT(*) as total 
                            FROM tenants 
                            WHERE id = :tenant_id AND deleted_at IS NULL
                        ");
                        $stmtTotalSekolah->execute(['tenant_id' => $tenantId]);
                        $totalSekolah = $stmtTotalSekolah->fetch()['total'] ?? 0;

                        $stmtTenant = $db->prepare("
                            SELECT nama_sekolah, npsn, subdomain, status, paket_aktif, status_sinkronisasi, created_at 
                            FROM tenants 
                            WHERE id = :tenant_id AND deleted_at IS NULL
                        ");
                        $stmtTenant->execute(['tenant_id' => $tenantId]);
                        $schoolInfo = $stmtTenant->fetch() ?: [];

                        $paketAktif = $schoolInfo['paket_aktif'] ?? 'Trial';
                        $statusSinkronisasi = $schoolInfo['status_sinkronisasi'] ?? 'Offline';

                        $stmtSiswa = $db->prepare("
                            SELECT COUNT(*) as total 
                            FROM siswa 
                            WHERE tenant_id = :tenant_id AND deleted_at IS NULL
                        ");
                        $stmtSiswa->execute(['tenant_id' => $tenantId]);
                        $totalSiswa = $stmtSiswa->fetch()['total'] ?? 0;
                    }

                    // --- D. Fetch Pengumuman ---
                    $roleId = $_SESSION['role_id'] ?? null;
                    if ($roleId === null) {
                        $roleName = $_SESSION['role_name'] ?? '';
                        if ($roleName === 'super_admin') $roleId = 1;
                        elseif ($roleName === 'operator_sekolah') $roleId = 2;
                        elseif ($roleName === 'siswa') $roleId = 6;
                        else $roleId = 0;
                    }

                    $pengumumanModel = new PengumumanModel($tenantId);
                    $pengumuman_list = $pengumumanModel->getActiveForUser($roleId, 1, 0) ?: [];
                    $total_pengumuman = $pengumumanModel->countActiveForUser($roleId) ?: 0;

                    $this->jsonResponse([
                        'success' => true,
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
                    $this->jsonResponse(['error' => $e->getMessage()], 500);
                }
            }
        }

        // Tampilkan halaman skeleton kosong (TANPA DATA dari DB)
        try {
            $db = Database::getConnection();
            $namaSekolah = 'Dashboard';
            if (!$isSuperAdmin && $tenantId !== null) {
                $stmt = $db->prepare("SELECT nama_sekolah FROM tenants WHERE id = ? LIMIT 1");
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
            $this->render('dashboard_view', ['stats' => $data, 'data' => $data]);
        } catch (\Throwable $e) {
            $data = [
                'title' => 'Dashboard',
                'user_role' => $_SESSION['role_name'],
                'user_nama' => $_SESSION['nama_lengkap']
            ];
            $this->render('dashboard_view', ['stats' => $data, 'data' => $data]);
        }
    }

    /**
     * Render the Halaman Arsip Pengumuman
     */
    public function pengumumanArsip(): void {
        SessionManager::start();
        if (!isset($_SESSION['user_id'])) {
            header('Location: /SINTA-SaaS/login');
            exit;
        }

        $tenantId = $_SESSION['tenant_id'];
        $roleId = $_SESSION['role_id'] ?? null;
        if ($roleId === null) {
            $roleName = $_SESSION['role_name'] ?? '';
            if ($roleName === 'super_admin') $roleId = 1;
            elseif ($roleName === 'operator_sekolah') $roleId = 2;
            elseif ($roleName === 'siswa') $roleId = 6;
            else $roleId = 0;
        }
        
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) $page = 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;
        $search = trim($_GET['search'] ?? '');
        $kategori = trim($_GET['kategori'] ?? '');
        $tanggal = trim($_GET['tanggal'] ?? '');
        
        $pengumumanModel = new \App\Models\PengumumanModel($tenantId);
        $total = $pengumumanModel->countActiveForUser($roleId, $search, $kategori, $tanggal);
        $list = $pengumumanModel->getActiveForUser($roleId, $limit, $offset, $search, $kategori, $tanggal);
        
        $totalPages = ceil($total / $limit);
        
        // Fetch categories for the filter dropdown
        $kategoriModel = new \App\Models\KategoriPengumumanModel($tenantId);
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
        
        $this->render('pengumuman_arsip_view', $data);
    }
}

