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
        // Jika belum login, redirect ke halaman login
        if (!isset($_SESSION['user_id'])) {
            header('Location: /SINTA-SaaS/login');
            exit;
        }

        // 3. Dapatkan tenant_id (sekolah) dari session pengguna yang sedang aktif
        $tenantId = $_SESSION['tenant_id'];
        $isSuperAdmin = ($_SESSION['role_name'] === 'super_admin');

        try {
            $db = Database::getConnection();

            if ($isSuperAdmin) {
                // 4. Kueri Global Platform untuk Super Admin (Tanpa Filter tenant_id)
                
                // A. Menghitung Jumlah Sekolah (Tenants)
                $stmtTotalSekolah = $db->query("SELECT COUNT(*) as total FROM tenants WHERE deleted_at IS NULL");
                $totalSekolah = $stmtTotalSekolah->fetch()['total'] ?? 0;

                // B. Menghitung Jumlah Paket Aktif (Total Tenant dengan Status Active)
                $stmtActiveTenants = $db->query("SELECT COUNT(*) as total FROM tenants WHERE status = 'active' AND deleted_at IS NULL");
                $activeCount = $stmtActiveTenants->fetch()['total'] ?? 0;
                $paketAktif = "SaaS Enterprise (" . $activeCount . " Sekolah)";

                // C. Menghitung Jumlah Siswa Aktif Lintas Semua Sekolah
                $stmtSiswa = $db->query("SELECT COUNT(*) as total FROM siswa WHERE deleted_at IS NULL");
                $totalSiswa = $stmtSiswa->fetch()['total'] ?? 0;

                // D. Menghitung Status Sinkronisasi Global
                $stmtSync = $db->query("SELECT COUNT(*) as total FROM tenants WHERE status_sinkronisasi = 'Tersinkronisasi' AND deleted_at IS NULL");
                $syncCount = $stmtSync->fetch()['total'] ?? 0;
                $statusSinkronisasi = "Node OK ({$syncCount}/{$totalSekolah})";

                // E. Profil Instansi untuk Super Admin
                $schoolInfo = [
                    'nama_sekolah' => 'Pusat Kendali SaaS (Global)',
                    'npsn' => 'PLATFORM',
                    'subdomain' => 'admin',
                    'status' => 'active',
                    'paket_aktif' => 'Global SaaS Owner',
                    'status_sinkronisasi' => '100% Online',
                    'created_at' => date('Y-m-d H:i:s')
                ];

                // F. Daftar Siswa Lintas Tenant untuk Tab Visualisasi
                $stmtSiswaList = $db->query("
                    SELECT s.nis, s.nisn, s.nama_lengkap, s.jenis_kelamin, s.tempat_lahir, s.tanggal_lahir, s.alamat, t.nama_sekolah 
                    FROM siswa s
                    JOIN tenants t ON s.tenant_id = t.id
                    WHERE s.deleted_at IS NULL 
                    ORDER BY s.nama_lengkap ASC 
                    LIMIT 20
                ");
                $siswaList = $stmtSiswaList->fetchAll() ?: [];

                // G. Daftar Guru (GTK) Lintas Tenant untuk Tab Visualisasi
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
                // 4. Kueri Spesifik Tenant untuk Operator / Guru (Dengan Filter tenant_id)
                if ($tenantId === null) {
                    // Jika bukan super admin tapi tidak memiliki tenant_id, paksa logout
                    SessionManager::logout();
                    header('Location: /SINTA-SaaS/login?error=tenant_suspended');
                    exit;
                }

                // A. Menghitung Jumlah Sekolah di bawah Tenant Ini (Hasilnya 1)
                $stmtTotalSekolah = $db->prepare("
                    SELECT COUNT(*) as total 
                    FROM tenants 
                    WHERE id = :tenant_id AND deleted_at IS NULL
                ");
                $stmtTotalSekolah->execute(['tenant_id' => $tenantId]);
                $totalSekolah = $stmtTotalSekolah->fetch()['total'] ?? 0;

                // B. Mengambil Informasi Profil Sekolah (Tenant) - Termasuk paket & sinkronisasi
                $stmtTenant = $db->prepare("
                    SELECT nama_sekolah, npsn, subdomain, status, paket_aktif, status_sinkronisasi, created_at 
                    FROM tenants 
                    WHERE id = :tenant_id AND deleted_at IS NULL
                ");
                $stmtTenant->execute(['tenant_id' => $tenantId]);
                $schoolInfo = $stmtTenant->fetch();

                if (!$schoolInfo) {
                    // Jika tenant_id session tidak valid (misal sekolah dinonaktifkan secara global)
                    SessionManager::logout();
                    header('Location: /SINTA-SaaS/login?error=tenant_suspended');
                    exit;
                }

                $paketAktif = $schoolInfo['paket_aktif'];
                $statusSinkronisasi = $schoolInfo['status_sinkronisasi'];

                // C. Menghitung Jumlah Siswa Aktif di Sekolah ini
                $stmtSiswa = $db->prepare("
                    SELECT COUNT(*) as total 
                    FROM siswa 
                    WHERE tenant_id = :tenant_id AND deleted_at IS NULL
                ");
                $stmtSiswa->execute(['tenant_id' => $tenantId]);
                $totalSiswa = $stmtSiswa->fetch()['total'] ?? 0;

                // D. Daftar Siswa di Sekolah Ini untuk Tab Visualisasi
                $stmtSiswaList = $db->prepare("
                    SELECT nis, nisn, nama_lengkap, jenis_kelamin, tempat_lahir, tanggal_lahir, alamat 
                    FROM siswa 
                    WHERE tenant_id = :tenant_id AND deleted_at IS NULL 
                    ORDER BY nama_lengkap ASC 
                    LIMIT 20
                ");
                $stmtSiswaList->execute(['tenant_id' => $tenantId]);
                $siswaList = $stmtSiswaList->fetchAll() ?: [];

                // E. Daftar Guru (GTK) di Sekolah Ini untuk Tab Visualisasi
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

            // Query log perubahan aktivitas untuk siswa (untuk tabel dashboard)
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

            // Resolve database IDs in log data to human-readable names
            $this->resolveLogDataIds($recentChangesRaw, $db);

            $recentChanges = [];
            foreach ($recentChangesRaw as $log) {
                $oldObj = json_decode($log['old_data'] ?? '', true) ?: [];
                $newObj = json_decode($log['new_data'] ?? '', true) ?: [];
                
                // Cari nama siswa
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

            // H. Fetch Pengumuman
            $pengumumanModel = new PengumumanModel($tenantId);
            $pengumuman_list = $pengumumanModel->getActiveForUser($_SESSION['role_id'] ?? 0);

            // 5. Kemas data untuk disalurkan ke View
            $stats = [
                'nama_sekolah' => $schoolInfo['nama_sekolah'],
                'npsn' => $schoolInfo['npsn'],
                'subdomain' => $schoolInfo['subdomain'],
                'total_sekolah' => $totalSekolah,
                'paket_aktif' => $paketAktif,
                'total_siswa' => $totalSiswa,
                'status_sinkronisasi' => $statusSinkronisasi,
                'user_nama' => $_SESSION['nama_lengkap'],
                'user_role' => $_SESSION['role_name'],
                'school_info' => $schoolInfo,
                'siswa_list' => $siswaList,
                'gtk_list' => $gtkList,
                'recent_changes' => $recentChanges,
                'pengumuman_list' => $pengumuman_list
            ];

            // 6. Muat file Tampilan menggunakan Master Layout
            $stats['title'] = 'Dashboard - ' . $schoolInfo['nama_sekolah'];
            $this->render('dashboard_view', ['stats' => $stats, 'data' => $stats]);

        } catch (\PDOException $e) {
            // Log kesalahan dan tampilkan halaman error yang aman
            error_log("Dashboard query error: " . $e->getMessage());
            die("Terjadi kesalahan pada sistem. Silakan hubungi Administrator.");
        }
    }
}
