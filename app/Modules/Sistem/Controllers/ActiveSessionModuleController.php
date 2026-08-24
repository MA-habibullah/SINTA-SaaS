<?php

namespace App\Modules\Sistem\Controllers;

use App\Core\BaseController;
use App\Config\Database;
use App\Core\SessionManager;
use App\Helpers\ActivityLogger;
use PDO;

class ActiveSessionModuleController extends BaseController {

    public function __construct() {
        parent::__construct();
        
        SessionManager::requireLogin();
        
        if (!\App\Core\RouteGuard::checkCurrent(['super_admin', 'operator_sekolah'])) {
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
        
        $this->render('sistem/active_sessions', $data);
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

        SessionManager::cleanupStaleSessions();

        try {
            $db = Database::getConnection();

            // 1. QUERY: Tabel Riwayat Sesi
            $onlineWhere = "1=1";
            $onlineParams = [];

            if (!empty($startDate) && !empty($endDate)) {
                $onlineWhere .= " AND s.tanggal_login BETWEEN :start_date AND :end_date";
                $onlineParams['start_date'] = $startDate;
                $onlineParams['end_date'] = $endDate;
            } else {
                $onlineWhere .= " AND s.last_activity >= CURRENT_TIMESTAMP - INTERVAL '1 day'";
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
                    COALESCE(u.nama_lengkap, sw.nama_lengkap, 'Super Admin Platform') AS nama_lengkap,
                    COALESCE(r.nama_role, 'siswa') AS user_role,
                    COALESCE(t.nama_sekolah, 'Pusat Kendali SaaS (Global)') AS nama_sekolah
                FROM sistem.active_sessions s
                LEFT JOIN core.users u ON s.user_id = u.id
                LEFT JOIN core.roles r ON u.role_id = r.id
                LEFT JOIN siswa.siswa sw ON s.user_id = sw.id
                LEFT JOIN core.tenants t ON s.tenant_id = t.id
                WHERE {$onlineWhere}
                ORDER BY s.last_activity DESC
            ";

            $stmtOnline = $db->prepare($sqlOnline);
            $stmtOnline->execute($onlineParams);
            $onlineUsers = $stmtOnline->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // 2. QUERY: Tren Sesi Pengguna Unik
            $chartParams = [];
            $groupBy = "s.tanggal_login";
            $selectGroup = "s.tanggal_login::text AS label";
            $chartWhere = "1=1";

            if ($timeframe === '30_minutes') {
                $chartWhere .= " AND s.last_activity >= CURRENT_TIMESTAMP - INTERVAL '30 minutes'";
                $groupBy = "to_char(s.last_activity, 'HH24:MI')";
                $selectGroup = "to_char(s.last_activity, 'HH24:MI') AS label";
            } elseif ($timeframe === '1_hour') {
                $chartWhere .= " AND s.last_activity >= CURRENT_TIMESTAMP - INTERVAL '1 hour'";
                $groupBy = "to_char(s.last_activity, 'HH24:MI')";
                $selectGroup = "to_char(s.last_activity, 'HH24:MI') AS label";
            } elseif ($timeframe === '1_day') {
                $chartWhere .= " AND s.last_activity >= CURRENT_TIMESTAMP - INTERVAL '1 day'";
                $groupBy = "to_char(s.last_activity, 'HH24:00')";
                $selectGroup = "to_char(s.last_activity, 'HH24:00') AS label";
            } elseif ($timeframe === '15_days') {
                $chartWhere .= " AND s.tanggal_login >= CURRENT_DATE - INTERVAL '15 days'";
            } else {
                $chartWhere .= " AND s.tanggal_login >= CURRENT_DATE - INTERVAL '30 days'";
            }

            if (!$isSuperAdmin) {
                $chartWhere .= " AND s.tenant_id = :tenant_id";
                $chartParams['tenant_id'] = $tenantId;
            }

            $sqlChart = "
                SELECT 
                    {$selectGroup}, 
                    COUNT(DISTINCT s.user_id) AS total_users
                FROM sistem.active_sessions s
                WHERE {$chartWhere}
                GROUP BY {$groupBy}
                ORDER BY MIN(s.last_activity) ASC
            ";

            $stmtChart = $db->prepare($sqlChart);
            $stmtChart->execute($chartParams);
            $chartData = $stmtChart->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // 3. QUERY: Tren Login & Logout (dari sistem.activity_logs)
            $auditParams = [];
            $auditGroupBy = "al.created_at::date";
            $auditSelectGroup = "al.created_at::date::text AS label";
            $auditWhere = "al.action IN ('LOGIN', 'LOGOUT', 'SYSTEM_TIMEOUT')";
            $auditOrderBy = "MIN(al.created_at)";

            if ($timeframe === '30_minutes') {
                $auditWhere .= " AND al.created_at >= CURRENT_TIMESTAMP - INTERVAL '30 minutes'";
                $auditGroupBy = "to_char(al.created_at, 'HH24:MI')";
                $auditSelectGroup = "to_char(al.created_at, 'HH24:MI') AS label";
            } elseif ($timeframe === '1_hour') {
                $auditWhere .= " AND al.created_at >= CURRENT_TIMESTAMP - INTERVAL '1 hour'";
                $auditGroupBy = "to_char(al.created_at, 'HH24:MI')";
                $auditSelectGroup = "to_char(al.created_at, 'HH24:MI') AS label";
            } elseif ($timeframe === '1_day') {
                $auditWhere .= " AND al.created_at >= CURRENT_TIMESTAMP - INTERVAL '1 day'";
                $auditGroupBy = "to_char(al.created_at, 'HH24:00')";
                $auditSelectGroup = "to_char(al.created_at, 'HH24:00') AS label";
            } elseif ($timeframe === '15_days') {
                $auditWhere .= " AND al.created_at::date >= CURRENT_DATE - INTERVAL '15 days'";
            } else {
                $auditWhere .= " AND al.created_at::date >= CURRENT_DATE - INTERVAL '30 days'";
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
                FROM sistem.activity_logs al
                WHERE {$auditWhere}
                GROUP BY {$auditGroupBy}
                ORDER BY {$auditOrderBy} ASC
            ";

            $stmtAuditChart = $db->prepare($sqlAuditChart);
            $stmtAuditChart->execute($auditParams);
            $auditChartData = $stmtAuditChart->fetchAll(PDO::FETCH_ASSOC) ?: [];

            header('Content-Type: application/json');
            echo json_encode([
                'success'          => true,
                'online_users'     => $onlineUsers,
                'chart_data'       => $chartData,
                'audit_chart_data' => $auditChartData
            ]);
            exit;
        } catch (\Throwable $e) {
            error_log("Failed to fetch sessions data: " . $e->getMessage());
            $this->jsonResponse(false, null, 'Terjadi kesalahan sistem saat memuat data analitik.', 500);
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

        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $dateLimit = isset($input['date_limit']) ? trim($input['date_limit']) : '';
        $targetTenantId = $isSuperAdmin ? ($input['tenant_id'] ?? $tenantId) : $tenantId;

        if (empty($dateLimit)) {
            $this->jsonResponse(false, null, 'Tanggal batas retensi wajib dipilih.', 400);
            return;
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateLimit)) {
            $this->jsonResponse(false, null, 'Format tanggal batas retensi tidak valid.', 400);
            return;
        }

        try {
            $db = Database::getConnection();

            if ($isSuperAdmin && empty($targetTenantId)) {
                $stmtCount = $db->prepare("SELECT COUNT(*) FROM sistem.active_sessions WHERE tanggal_login <= :date_limit::date AND (:tenant_id::uuid IS NULL OR tenant_id = :tenant_id::uuid)");
                $stmtCount->execute(['date_limit' => $dateLimit, 'tenant_id' => null]);
                $affectedCount = (int)$stmtCount->fetchColumn();

                if ($affectedCount === 0) {
                    $this->jsonResponse(true, null, 'Tidak ada data log sesi yang memenuhi kriteria retensi.');
                    return;
                }

                $stmtDelete = $db->prepare("DELETE FROM sistem.active_sessions WHERE tanggal_login <= :date_limit::date AND (:tenant_id::uuid IS NULL OR tenant_id = :tenant_id::uuid)");
                $stmtDelete->execute(['date_limit' => $dateLimit, 'tenant_id' => null]);
            } else {
                $stmtCount = $db->prepare("SELECT COUNT(*) FROM sistem.active_sessions WHERE tanggal_login <= :date_limit::date AND tenant_id = :tenant_id");
                $stmtCount->execute(['date_limit' => $dateLimit, 'tenant_id' => $targetTenantId]);
                $affectedCount = (int)$stmtCount->fetchColumn();

                if ($affectedCount === 0) {
                    $this->jsonResponse(true, null, 'Tidak ada data log sesi yang memenuhi kriteria retensi.');
                    return;
                }

                $stmtDelete = $db->prepare("DELETE FROM sistem.active_sessions WHERE tanggal_login <= :date_limit::date AND tenant_id = :tenant_id");
                $stmtDelete->execute(['date_limit' => $dateLimit, 'tenant_id' => $targetTenantId]);
            }

            ActivityLogger::record(
                'DELETE',
                'sistem.active_sessions',
                $dateLimit,
                ['keterangan' => "Pembersihan log retensi sesi aktif sebelum/pada tanggal {$dateLimit}", 'jumlah_terhapus' => $affectedCount],
                null
            );

            $this->jsonResponse(true, null, "Berhasil menghapus {$affectedCount} log riwayat sesi sebelum tanggal {$dateLimit}.");

        } catch (\Throwable $e) {
            error_log("Retention clean error: " . $e->getMessage());
            $this->jsonResponse(false, null, 'Terjadi kesalahan sistem saat membersihkan log retensi.', 500);
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
                $where .= " AND al.created_at::date BETWEEN :start_date::date AND :end_date::date";
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
                FROM sistem.activity_logs al
                LEFT JOIN core.users u ON al.user_id = u.id
                LEFT JOIN siswa.siswa sw ON al.user_id = sw.id
                LEFT JOIN core.tenants t ON al.tenant_id = t.id
                WHERE {$where}
                ORDER BY al.created_at DESC
                LIMIT 1000
            ";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            header('Content-Type: application/json');
            echo json_encode([
                'success'    => true,
                'audit_logs' => $logs
            ]);
            exit;
        } catch (\Throwable $e) {
            error_log("Failed to fetch audit logs: " . $e->getMessage());
            $this->jsonResponse(false, null, 'Gagal memuat log keamanan.', 500);
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

        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $dateLimit = isset($input['date_limit']) ? trim($input['date_limit']) : '';
        $targetTenantId = $isSuperAdmin ? ($input['tenant_id'] ?? $tenantId) : $tenantId;

        if (empty($dateLimit)) {
            $this->jsonResponse(false, null, 'Tanggal batas retensi wajib dipilih.', 400);
            return;
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateLimit)) {
            $this->jsonResponse(false, null, 'Format tanggal batas retensi tidak valid.', 400);
            return;
        }

        try {
            $db = Database::getConnection();

            if ($isSuperAdmin && empty($targetTenantId)) {
                $stmtCount = $db->prepare("SELECT COUNT(*) FROM sistem.activity_logs WHERE action IN ('LOGIN', 'LOGOUT', 'SYSTEM_TIMEOUT') AND created_at::date <= :date_limit::date AND (:tenant_id::uuid IS NULL OR tenant_id = :tenant_id::uuid)");
                $stmtCount->execute(['date_limit' => $dateLimit, 'tenant_id' => null]);
                $affectedCount = (int)$stmtCount->fetchColumn();

                if ($affectedCount === 0) {
                    $this->jsonResponse(true, null, 'Tidak ada data log audit yang memenuhi kriteria.');
                    return;
                }

                $stmtDelete = $db->prepare("DELETE FROM sistem.activity_logs WHERE action IN ('LOGIN', 'LOGOUT', 'SYSTEM_TIMEOUT') AND created_at::date <= :date_limit::date AND (:tenant_id::uuid IS NULL OR tenant_id = :tenant_id::uuid)");
                $stmtDelete->execute(['date_limit' => $dateLimit, 'tenant_id' => null]);
            } else {
                $stmtCount = $db->prepare("SELECT COUNT(*) FROM sistem.activity_logs WHERE action IN ('LOGIN', 'LOGOUT', 'SYSTEM_TIMEOUT') AND created_at::date <= :date_limit::date AND tenant_id = :tenant_id");
                $stmtCount->execute(['date_limit' => $dateLimit, 'tenant_id' => $targetTenantId]);
                $affectedCount = (int)$stmtCount->fetchColumn();

                if ($affectedCount === 0) {
                    $this->jsonResponse(true, null, 'Tidak ada data log audit yang memenuhi kriteria.');
                    return;
                }

                $stmtDelete = $db->prepare("DELETE FROM sistem.activity_logs WHERE action IN ('LOGIN', 'LOGOUT', 'SYSTEM_TIMEOUT') AND created_at::date <= :date_limit::date AND tenant_id = :tenant_id");
                $stmtDelete->execute(['date_limit' => $dateLimit, 'tenant_id' => $targetTenantId]);
            }

            ActivityLogger::record(
                'DELETE',
                'sistem.activity_logs',
                $dateLimit,
                ['keterangan' => "Pembersihan log audit (Login/Logout) sebelum/pada tanggal {$dateLimit}", 'jumlah_terhapus' => $affectedCount],
                null
            );

            $this->jsonResponse(true, null, "Berhasil menghapus {$affectedCount} log audit sebelum/pada tanggal {$dateLimit}.");

        } catch (\Throwable $e) {
            error_log("Audit Retention clean error: " . $e->getMessage());
            $this->jsonResponse(false, null, 'Terjadi kesalahan sistem saat membersihkan log audit.', 500);
        }
    }
}
