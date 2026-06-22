<?php

namespace App\Controllers;

use App\Config\Database;
use App\Core\SessionManager;
use PDO;

/**
 * ErrorMonitorController
 *
 * Halaman monitoring error sistem secara real-time.
 * RBAC Ketat: Hanya super_admin yang boleh mengakses.
 *
 * Routes (index.php):
 *   GET  /super-admin/error-monitor          → index()
 *   GET  /api/v1/error-monitor               → fetchApi()
 *   POST /api/v1/error-monitor/clear         → clearAll()
 *   POST /api/v1/error-monitor/delete        → deleteOne()
 */
class ErrorMonitorController extends BaseController
{
    /** Hanya role ini yang diizinkan masuk */
    private const ALLOWED_ROLES = ['super_admin'];

    public function __construct()
    {
        parent::__construct();

        // 1. Wajib Login
        SessionManager::requireLogin();

        // 2. RBAC Guard — hanya super_admin, tolak semua role lain
        $role = $_SESSION['role_name'] ?? '';
        if (!in_array($role, self::ALLOWED_ROLES, true)) {
            http_response_code(403);
            echo "<div style='font-family:sans-serif;text-align:center;padding:60px'>";
            echo "<h1 style='color:#dc3545;font-size:2rem;'>🔒 403 — Akses Ditolak</h1>";
            echo "<p style='color:#6c757d;font-size:1rem;'>Halaman Error Monitor bersifat rahasia dan hanya dapat diakses oleh <strong>Super Admin Platform</strong>.</p>";
            echo "<a href='/dapodik-spmb/dashboard' style='display:inline-block;margin-top:1rem;padding:0.5rem 1.5rem;background:#2563eb;color:#fff;text-decoration:none;border-radius:6px;'>Kembali ke Dashboard</a>";
            echo "</div>";
            exit;
        }
    }

    /**
     * GET /super-admin/error-monitor
     * Render halaman monitor.
     */
    public function index(): void
    {
        $this->render('error_monitor', [
            'title'     => 'Error Monitor — System Debugger',
            'user_role' => $_SESSION['role_name'] ?? '',
        ]);
    }

    /**
     * GET /api/v1/error-monitor
     * Ambil daftar error terpaginasi & terfilter.
     *
     * Query params: page, per_page, search, level_filter
     */
    public function fetchApi(): void
    {
        $page        = max(1, (int)($_GET['page']     ?? 1));
        $perPage     = min(100, max(10, (int)($_GET['per_page'] ?? 20)));
        $search      = trim($_GET['search']       ?? '');
        $levelFilter = trim($_GET['level_filter'] ?? '');
        $offset      = ($page - 1) * $perPage;

        try {
            $db = Database::getConnection();

            // Bangun klausa WHERE secara dinamis
            $whereClauses = [];
            $params       = [];

            if (!empty($search)) {
                $whereClauses[] = "(e.message LIKE :s1 OR e.file LIKE :s2 OR e.request_url LIKE :s3)";
                $like = '%' . $search . '%';
                $params['s1'] = $like;
                $params['s2'] = $like;
                $params['s3'] = $like;
            }

            if (!empty($levelFilter)) {
                $whereClauses[] = "e.error_level = :level_filter";
                $params['level_filter'] = $levelFilter;
            }

            $whereSql = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

            // Query utama
            $sql = "
                SELECT
                    e.id,
                    e.error_level,
                    e.message,
                    e.file,
                    e.line,
                    e.trace,
                    e.request_url,
                    e.request_method,
                    e.ip_address,
                    e.created_at,
                    t.nama_sekolah
                FROM `system_errors` e
                LEFT JOIN `tenants` t ON e.tenant_id = t.id
                {$whereSql}
                ORDER BY e.created_at DESC
                LIMIT :limit OFFSET :offset
            ";

            $stmt = $db->prepare($sql);
            foreach ($params as $k => $v) {
                $stmt->bindValue(':' . $k, $v, PDO::PARAM_STR);
            }
            $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
            $stmt->execute();
            $errors = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Query total
            $countStmt = $db->prepare("SELECT COUNT(*) FROM `system_errors` e {$whereSql}");
            foreach ($params as $k => $v) {
                $countStmt->bindValue(':' . $k, $v, PDO::PARAM_STR);
            }
            $countStmt->execute();
            $total = (int)$countStmt->fetchColumn();

            // Statistik ringkasan per level
            $statsStmt = $db->query("
                SELECT error_level, COUNT(*) AS jumlah
                FROM `system_errors`
                GROUP BY error_level
                ORDER BY jumlah DESC
            ");
            $stats = $statsStmt->fetchAll(PDO::FETCH_ASSOC);

            $this->jsonResponse([
                'success' => true,
                'data'    => $errors,
                'stats'   => $stats,
                'pagination' => [
                    'page'     => $page,
                    'per_page' => $perPage,
                    'total'    => $total,
                    'pages'    => (int)ceil($total / $perPage),
                ],
            ]);

        } catch (\Throwable $e) {
            error_log("ErrorMonitor fetchApi error: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal memuat data error monitor.'], 500);
        }
    }

    /**
     * POST /api/v1/error-monitor/clear
     * Hapus semua log error (TRUNCATE).
     */
    public function clearAll(): void
    {
        try {
            $db = Database::getConnection();
            $db->exec("DELETE FROM `system_errors`");

            $this->jsonResponse([
                'success' => true,
                'message' => 'Semua log error berhasil dihapus.',
            ]);
        } catch (\Throwable $e) {
            error_log("ErrorMonitor clearAll error: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal menghapus log error.'], 500);
        }
    }

    /**
     * POST /api/v1/error-monitor/delete
     * Hapus satu log error berdasarkan ID.
     * Body JSON: { "id": "uuid" }
     */
    public function deleteOne(): void
    {
        $body = $this->getJsonInput();
        $id   = trim($body['id'] ?? '');

        if (empty($id)) {
            $this->jsonResponse(['error' => 'ID error tidak boleh kosong.'], 422);
        }

        try {
            $db   = Database::getConnection();
            $stmt = $db->prepare("DELETE FROM `system_errors` WHERE `id` = :id LIMIT 1");
            $stmt->execute(['id' => $id]);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Log error berhasil dihapus.',
            ]);
        } catch (\Throwable $e) {
            error_log("ErrorMonitor deleteOne error: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal menghapus log error.'], 500);
        }
    }
}
