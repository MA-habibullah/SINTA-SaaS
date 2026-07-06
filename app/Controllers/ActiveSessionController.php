<?php

namespace App\Controllers;

use App\Config\Database;
use App\Core\SessionManager;
use App\Helpers\ActivityLogger;
use PDO;

class ActiveSessionController extends BaseController {

    public function __construct() {
        parent::__construct();
        
        // 1. Wajib Login (Security Gate)
        SessionManager::requireLogin();
        
        // 2. Hak Akses: Hanya Super Admin & Operator Sekolah
        $role = $_SESSION['role_name'] ?? '';
        if ($role !== 'super_admin' && $role !== 'operator_sekolah') {
            http_response_code(403);
            echo "<div style='font-family: sans-serif; text-align: center; padding: 50px;'>";
            echo "<h1 style='color: #dc3545;'>403 Akses Ditolak</h1>";
            echo "<p style='color: #6c757d;'>Anda tidak memiliki wewenang untuk mengakses halaman monitoring sesi aktif.</p>";
            echo "<a href='/SINTA-SaaS/dashboard'>Kembali ke Dashboard</a>";
            echo "</div>";
            exit;
        }
    }

    /**
     * Tampilkan Halaman Utama Monitoring Sesi Aktif
     * GET /utilitas/sesi-aktif
     */
    public function index(): void {
        $data = [
            'title'     => 'Monitoring Sesi Aktif & Analitik',
            'user_nama' => $_SESSION['nama_lengkap'] ?? 'User',
            'user_role' => $_SESSION['role_name'] ?? '',
        ];
        
        $this->render('active_sessions', $data);
    }

    /**
     * API: Ambil data analitik sesi aktif
     * GET /api/v1/sessions/data
     */
    public function fetchDataApi(): void {
        $role = $_SESSION['role_name'] ?? '';
        $tenantId = $_SESSION['tenant_id'] ?? null;
        $isSuperAdmin = ($role === 'super_admin');

        $timeframe = $_GET['timeframe'] ?? '30_days';
        $startDate = $_GET['start_date'] ?? '';
        $endDate = $_GET['end_date'] ?? '';

        // --- LAZY CLEANUP TRIGGER (100% probability when loading dashboard) ---
        // Bersihkan sesi usang dan catat sebagai SYSTEM_TIMEOUT ke activity_logs
        \App\Core\SessionManager::cleanupStaleSessions();

        try {
            $db = Database::getConnection();

            // ==========================================
            // 1. QUERY: Tabel Riwayat Sesi (Default 1 Hari Terakhir / Date Range)
            // ==========================================
            $onlineWhere = "1=1";
            $onlineParams = [];

            if (!empty($startDate) && !empty($endDate)) {
                $onlineWhere .= " AND s.tanggal_login BETWEEN :start_date AND :end_date";
                $onlineParams['start_date'] = $startDate;
                $onlineParams['end_date'] = $endDate;
            } else {
                $onlineWhere .= " AND s.last_activity >= NOW() - INTERVAL 1 DAY";
            }

            if (!$isSuperAdmin) {
                $onlineWhere .= " AND s.tenant_id = :tenant_id";
                $onlineParams['tenant_id'] = $tenantId;
            }

            $sqlOnline = "
                SELECT 
                    s.id, 
                    s.ip_address, 
                    s.user_agent, 
                    s.last_activity,
                    s.tanggal_login,
                    COALESCE(u.nama_lengkap, sw.nama_lengkap, 'User') AS nama_lengkap,
                    COALESCE(r.nama_role, 'siswa') AS user_role,
                    t.nama_sekolah
                FROM active_sessions s
                LEFT JOIN users u ON s.user_id = u.id
                LEFT JOIN roles r ON u.role_id = r.id
                LEFT JOIN siswa sw ON s.user_id = sw.id
                LEFT JOIN tenants t ON s.tenant_id = t.id
                WHERE {$onlineWhere}
                ORDER BY s.last_activity DESC
            ";

            $stmtOnline = $db->prepare($sqlOnline);
            $stmtOnline->execute($onlineParams);
            $onlineUsers = $stmtOnline->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // ==========================================
            // 2. QUERY: Tren Sesi Pengguna Unik (Berdasarkan timeframe dari active_sessions)
            // ==========================================
            $chartParams = [];
            $groupBy = "s.tanggal_login";
            $selectGroup = "s.tanggal_login AS label";
            $chartWhere = "1=1";

            if ($timeframe === '30_minutes') {
                $chartWhere .= " AND s.last_activity >= NOW() - INTERVAL 30 MINUTE";
                $groupBy = "DATE_FORMAT(s.last_activity, '%H:%i')";
                $selectGroup = "DATE_FORMAT(s.last_activity, '%H:%i') AS label";
            } elseif ($timeframe === '1_hour') {
                $chartWhere .= " AND s.last_activity >= NOW() - INTERVAL 1 HOUR";
                $groupBy = "DATE_FORMAT(s.last_activity, '%H:%i')";
                $selectGroup = "DATE_FORMAT(s.last_activity, '%H:%i') AS label";
            } elseif ($timeframe === '1_day') {
                $chartWhere .= " AND s.last_activity >= NOW() - INTERVAL 1 DAY";
                $groupBy = "DATE_FORMAT(s.last_activity, '%H:00')";
                $selectGroup = "DATE_FORMAT(s.last_activity, '%H:00') AS label";
            } elseif ($timeframe === '15_days') {
                $chartWhere .= " AND s.tanggal_login >= DATE_SUB(CURDATE(), INTERVAL 15 DAY)";
            } else {
                // Default 30_days
                $chartWhere .= " AND s.tanggal_login >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
            }

            if (!$isSuperAdmin) {
                $chartWhere .= " AND s.tenant_id = :tenant_id";
                $chartParams['tenant_id'] = $tenantId;
            }

            $sqlChart = "
                SELECT 
                    {$selectGroup}, 
                    COUNT(DISTINCT s.user_id) AS total_users
                FROM active_sessions s
                WHERE {$chartWhere}
                GROUP BY {$groupBy}
                ORDER BY MIN(s.last_activity) ASC
            ";

            $stmtChart = $db->prepare($sqlChart);
            $stmtChart->execute($chartParams);
            $chartData = $stmtChart->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // ==========================================
            // 3. QUERY: Tren Login & Logout (Berdasarkan timeframe dari activity_logs)
            // ==========================================
            $auditParams = [];
            $auditGroupBy = "DATE(al.created_at)";
            $auditSelectGroup = "DATE(al.created_at) AS label";
            $auditWhere = "al.action IN ('LOGIN', 'LOGOUT', 'SYSTEM_TIMEOUT')";
            $auditOrderBy = "MIN(al.created_at)";

            if ($timeframe === '30_minutes') {
                $auditWhere .= " AND al.created_at >= NOW() - INTERVAL 30 MINUTE";
                $auditGroupBy = "DATE_FORMAT(al.created_at, '%H:%i')";
                $auditSelectGroup = "DATE_FORMAT(al.created_at, '%H:%i') AS label";
            } elseif ($timeframe === '1_hour') {
                $auditWhere .= " AND al.created_at >= NOW() - INTERVAL 1 HOUR";
                $auditGroupBy = "DATE_FORMAT(al.created_at, '%H:%i')";
                $auditSelectGroup = "DATE_FORMAT(al.created_at, '%H:%i') AS label";
            } elseif ($timeframe === '1_day') {
                $auditWhere .= " AND al.created_at >= NOW() - INTERVAL 1 DAY";
                $auditGroupBy = "DATE_FORMAT(al.created_at, '%H:00')";
                $auditSelectGroup = "DATE_FORMAT(al.created_at, '%H:00') AS label";
            } elseif ($timeframe === '15_days') {
                $auditWhere .= " AND al.created_at >= DATE_SUB(CURDATE(), INTERVAL 15 DAY)";
            } else {
                // Default 30_days
                $auditWhere .= " AND al.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
            }

            if (!$isSuperAdmin) {
                $auditWhere .= " AND al.tenant_id = :tenant_id";
                $auditParams['tenant_id'] = $tenantId;
            }

            $sqlAuditChart = "
                SELECT 
                    {$auditSelectGroup}, 
                    SUM(CASE WHEN al.action = 'LOGIN' THEN 1 ELSE 0 END) AS total_logins,
                    SUM(CASE WHEN al.action = 'LOGOUT' THEN 1 ELSE 0 END) AS total_logouts
                FROM activity_logs al
                WHERE {$auditWhere}
                GROUP BY {$auditGroupBy}
                ORDER BY {$auditOrderBy} ASC
            ";

            $stmtAuditChart = $db->prepare($sqlAuditChart);
            $stmtAuditChart->execute($auditParams);
            $auditChartData = $stmtAuditChart->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $this->jsonResponse([
                'success' => true,
                'online_users' => $onlineUsers,
                'chart_data' => $chartData,
                'audit_chart_data' => $auditChartData
            ]);
        } catch (\Throwable $e) {
            error_log("Failed to fetch sessions data: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Terjadi kesalahan sistem saat memuat data analitik.'], 500);
        }
    }

    /**
     * API: Hapus riwayat log sesi (Data Retention)
     * POST /api/v1/sessions/retention
     */
    public function deleteRetentionApi(): void {
        $role = $_SESSION['role_name'] ?? '';
        $tenantId = $_SESSION['tenant_id'] ?? null;
        $isSuperAdmin = ($role === 'super_admin');

        $input = $this->getJsonInput();
        $dateLimit = isset($input['date_limit']) ? trim($input['date_limit']) : '';

        // 1. Validasi Input
        if (empty($dateLimit)) {
            $this->jsonResponse(['error' => 'Tanggal batas retensi wajib dipilih.'], 400);
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateLimit)) {
            $this->jsonResponse(['error' => 'Format tanggal batas retensi tidak valid.'], 400);
        }

        try {
            $db = Database::getConnection();

            // 2. Bangun klausa WHERE & Parameter hapus
            $where = "tanggal_login <= :date_limit";
            $params = ['date_limit' => $dateLimit];

            if (!$isSuperAdmin) {
                $where .= " AND tenant_id = :tenant_id";
                $params['tenant_id'] = $tenantId;
            }

            // Hitung dulu berapa record yang akan terpengaruh untuk keperluan log audit
            $stmtCount = $db->prepare("SELECT COUNT(*) FROM active_sessions WHERE {$where}");
            $stmtCount->execute($params);
            $affectedCount = (int)$stmtCount->fetchColumn();

            if ($affectedCount === 0) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Tidak ada data log sesi yang memenuhi kriteria retensi.'
                ]);
            }

            // Lakukan penghapusan
            $stmtDelete = $db->prepare("DELETE FROM active_sessions WHERE {$where}");
            $stmtDelete->execute($params);

            // 3. Catat di Log Aktivitas Audit Trail (Cybersecurity Best Practice)
            ActivityLogger::record(
                'DELETE',
                'active_sessions',
                $dateLimit,
                ['keterangan' => "Pembersihan log retensi sesi aktif sebelum/pada tanggal {$dateLimit}", 'jumlah_terhapus' => $affectedCount],
                null
            );

            $this->jsonResponse([
                'success' => true,
                'message' => "Berhasil menghapus {$affectedCount} log riwayat sesi sebelum tanggal {$dateLimit}."
            ]);

        } catch (\Throwable $e) {
            error_log("Retention clean error: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Terjadi kesalahan sistem saat membersihkan log retensi.'], 500);
        }
    }

    /**
     * API: Ambil log jejak audit (Login/Logout)
     * GET /api/v1/sessions/audit
     */
    public function fetchAuditApi(): void {
        $role = $_SESSION['role_name'] ?? '';
        $tenantId = $_SESSION['tenant_id'] ?? null;
        $isSuperAdmin = ($role === 'super_admin');

        $input = $_GET;
        $startDate = isset($input['start_date']) ? trim($input['start_date']) : '';
        $endDate = isset($input['end_date']) ? trim($input['end_date']) : '';

        try {
            $db = Database::getConnection();
            
            $where = "al.action IN ('LOGIN', 'LOGOUT', 'SYSTEM_TIMEOUT')";
            $params = [];

            if (!$isSuperAdmin) {
                $where .= " AND al.tenant_id = :tenant_id";
                $params['tenant_id'] = $tenantId;
            }

            if (!empty($startDate) && !empty($endDate)) {
                $where .= " AND DATE(al.created_at) BETWEEN :start_date AND :end_date";
                $params['start_date'] = $startDate;
                $params['end_date'] = $endDate;
            }

            $sql = "
                SELECT 
                    al.id,
                    al.action,
                    al.created_at,
                    al.ip_address,
                    al.user_role,
                    COALESCE(u.nama_lengkap, sw.nama_lengkap, 'System/Unknown') AS nama_lengkap,
                    t.nama_sekolah
                FROM activity_logs al
                LEFT JOIN users u ON al.record_id = u.id AND al.table_name = 'users'
                LEFT JOIN siswa sw ON al.record_id = sw.id AND al.table_name = 'siswa'
                LEFT JOIN tenants t ON al.tenant_id = t.id
                WHERE {$where}
                ORDER BY al.created_at DESC
                LIMIT 1000
            ";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $this->jsonResponse([
                'success' => true,
                'audit_logs' => $logs
            ]);
        } catch (\Throwable $e) {
            error_log("Failed to fetch audit logs: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal memuat log keamanan.'], 500);
        }
    }

    /**
     * API: Hapus log jejak audit (Data Retention)
     * POST /api/v1/sessions/audit/retention
     */
    public function deleteAuditRetentionApi(): void {
        $role = $_SESSION['role_name'] ?? '';
        $tenantId = $_SESSION['tenant_id'] ?? null;
        $isSuperAdmin = ($role === 'super_admin');

        $input = $this->getJsonInput();
        $dateLimit = isset($input['date_limit']) ? trim($input['date_limit']) : '';

        // 1. Validasi Input
        if (empty($dateLimit)) {
            $this->jsonResponse(['error' => 'Tanggal batas retensi wajib dipilih.'], 400);
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateLimit)) {
            $this->jsonResponse(['error' => 'Format tanggal batas retensi tidak valid.'], 400);
        }

        try {
            $db = Database::getConnection();

            // 2. Bangun klausa WHERE & Parameter hapus
            $where = "action IN ('LOGIN', 'LOGOUT', 'SYSTEM_TIMEOUT') AND DATE(created_at) <= :date_limit";
            $params = ['date_limit' => $dateLimit];

            if (!$isSuperAdmin) {
                $where .= " AND tenant_id = :tenant_id";
                $params['tenant_id'] = $tenantId;
            }

            // Hitung dulu berapa record yang akan terpengaruh
            $stmtCount = $db->prepare("SELECT COUNT(*) FROM activity_logs WHERE {$where}");
            $stmtCount->execute($params);
            $affectedCount = (int)$stmtCount->fetchColumn();

            if ($affectedCount === 0) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Tidak ada data log audit yang memenuhi kriteria.'
                ]);
                return;
            }

            // Lakukan penghapusan
            $stmtDelete = $db->prepare("DELETE FROM activity_logs WHERE {$where}");
            $stmtDelete->execute($params);

            // 3. Catat di Log Aktivitas Audit Trail
            ActivityLogger::record(
                'DELETE',
                'activity_logs',
                $dateLimit,
                ['keterangan' => "Pembersihan log audit (Login/Logout) sebelum/pada tanggal {$dateLimit}", 'jumlah_terhapus' => $affectedCount],
                null
            );

            $this->jsonResponse([
                'success' => true,
                'message' => "Berhasil menghapus {$affectedCount} log audit sebelum/pada tanggal {$dateLimit}."
            ]);

        } catch (\Throwable $e) {
            error_log("Audit Retention clean error: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Terjadi kesalahan sistem saat membersihkan log audit.'], 500);
        }
    }
}
