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

        try {
            $db = Database::getConnection();

            // ==========================================
            // 1. QUERY: Pengguna Online (15 Menit Terakhir)
            // ==========================================
            $onlineWhere = "s.last_activity >= NOW() - INTERVAL 15 MINUTE";
            $onlineParams = [];

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
            // 2. QUERY: Tren Login Harian (30 Hari Terakhir)
            // ==========================================
            $chartWhere = "s.tanggal_login >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
            $chartParams = [];

            if (!$isSuperAdmin) {
                $chartWhere .= " AND s.tenant_id = :tenant_id";
                $chartParams['tenant_id'] = $tenantId;
            }

            $sqlChart = "
                SELECT 
                    s.tanggal_login, 
                    COUNT(DISTINCT s.user_id) AS total_logins
                FROM active_sessions s
                WHERE {$chartWhere}
                GROUP BY s.tanggal_login
                ORDER BY s.tanggal_login ASC
            ";

            $stmtChart = $db->prepare($sqlChart);
            $stmtChart->execute($chartParams);
            $chartData = $stmtChart->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $this->jsonResponse([
                'success' => true,
                'online_users' => $onlineUsers,
                'chart_data' => $chartData
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
}
