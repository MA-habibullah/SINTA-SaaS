<?php

namespace App\Modules\Core\Controllers;

use App\Config\Database;
use App\Core\SessionManager;
use App\Core\BaseController;
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
class ErrorMonitorModuleController extends BaseController
{
    private const ALLOWED_ROLES = ['super_admin'];

    public function __construct()
    {
        parent::__construct();

        SessionManager::requireLogin();

        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        if (str_contains($uri, '/log-client')) {
            return; // Allow logging client JS errors for any logged-in user
        }

        if (!$this->isUserSuperAdmin()) {
            if ($this->isJsonRequest()) {
                $this->jsonResponse(false, null, 'Akses ditolak. Fitur ini khusus Super Admin Platform.', 403);
            }
            header('Location: ' . $this->getBaseUrl() . '/dashboard');
            exit;
        }
    }

    /**
     * GET /super-admin/error-monitor
     */
    public function index(): void
    {
        $this->render('core/error_monitor', [
            'title'     => 'Error Monitor — System Debugger',
            'user_role' => $_SESSION['role_name'] ?? '',
        ]);
    }

    /**
     * GET /api/v1/error-monitor
     */
    public function fetchApi(): void
    {
        $page           = max(1, (int)($_GET['page']        ?? 1));
        $perPage        = min(100, max(10, (int)($_GET['per_page'] ?? 20)));
        $search         = trim($_GET['search']          ?? '');
        $levelFilter    = trim($_GET['level_filter']    ?? '');
        $tenantIdFilter = trim($_GET['tenant_id']       ?? '');
        $offset         = ($page - 1) * $perPage;

        try {
            $db = Database::getConnection();

            $whereClauses = [];
            $params       = [];

            if (!empty($search)) {
                $whereClauses[] = "(e.message ILIKE :s1 OR e.file ILIKE :s2 OR e.request_url ILIKE :s3)";
                $like = '%' . $search . '%';
                $params['s1'] = $like;
                $params['s2'] = $like;
                $params['s3'] = $like;
            }

            if (!empty($levelFilter)) {
                $whereClauses[] = "e.error_level = :level_filter";
                $params['level_filter'] = $levelFilter;
            }

            $sessionTenantId = $this->getSecureTenantId();
            $isSuperAdmin    = $this->isUserSuperAdmin();

            if (!$isSuperAdmin && !empty($sessionTenantId)) {
                $whereClauses[] = "(e.tenant_id = :session_tenant_id OR e.tenant_id IS NULL)";
                $params['session_tenant_id'] = $sessionTenantId;
            } elseif (!empty($tenantIdFilter)) {
                if ($tenantIdFilter === 'global') {
                    $whereClauses[] = "e.tenant_id IS NULL";
                } elseif (preg_match('/^[a-f0-9\-]{36}$/i', $tenantIdFilter)) {
                    $whereClauses[] = "e.tenant_id = :tenant_filter";
                    $params['tenant_filter'] = $tenantIdFilter;
                }
            }

            $whereSql = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

            $sql = "
                SELECT
                    e.id,
                    e.tenant_id,
                    e.error_level,
                    e.message,
                    e.file,
                    e.line,
                    e.trace,
                    e.request_url,
                    e.request_method,
                    e.ip_address,
                    e.context,
                    e.created_at,
                    t.nama_sekolah
                FROM sistem.system_errors e
                LEFT JOIN core.tenants t ON e.tenant_id = t.id
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

            // Query total terisolasi filter
            $countStmt = $db->prepare("SELECT COUNT(*) FROM sistem.system_errors e {$whereSql}");
            foreach ($params as $k => $v) {
                $countStmt->bindValue(':' . $k, $v, PDO::PARAM_STR);
            }
            $countStmt->execute();
            $total = (int)$countStmt->fetchColumn();

            // Statistik ringkasan per level
            $statsSql = "SELECT error_level, COUNT(*) AS jumlah FROM sistem.system_errors e {$whereSql} GROUP BY error_level ORDER BY jumlah DESC";
            $statsStmt = $db->prepare($statsSql);
            foreach ($params as $k => $v) {
                $statsStmt->bindValue(':' . $k, $v, PDO::PARAM_STR);
            }
            $statsStmt->execute();
            $stats = $statsStmt->fetchAll(PDO::FETCH_ASSOC);

            header('Content-Type: application/json');
            echo json_encode([
                'success'    => true,
                'data'       => $errors,
                'stats'      => $stats,
                'pagination' => [
                    'page'     => $page,
                    'per_page' => $perPage,
                    'total'    => $total,
                    'pages'    => (int)ceil($total / $perPage),
                ],
            ]);
            exit;

        } catch (\Throwable $e) {
            error_log("ErrorMonitor fetchApi error: " . $e->getMessage());
            $this->jsonResponse(false, null, 'Gagal memuat data error monitor.', 500);
        }
    }

    /**
     * POST /api/v1/error-monitor/clear
     */
    public function clearAll(): void
    {
        try {
            $this->validateCsrfToken();
            $db = Database::getConnection();

            $tenantIdFilter = trim($_POST['tenant_id'] ?? ($_GET['tenant_id'] ?? ''));
            $sessionTenantId = $this->getSecureTenantId();
            $targetTenant = !empty($tenantIdFilter) && preg_match('/^[a-f0-9\-]{36}$/i', $tenantIdFilter) ? $tenantIdFilter : $sessionTenantId;

            if (!$this->isUserSuperAdmin() || !empty($targetTenant)) {
                $stmt = $db->prepare("DELETE FROM sistem.system_errors WHERE (:tenant_id::uuid IS NULL OR tenant_id = :tenant_id::uuid OR tenant_id IS NULL)");
                $stmt->execute(['tenant_id' => $targetTenant]);
            } else {
                $stmt = $db->prepare("DELETE FROM sistem.system_errors WHERE (:tenant_id::uuid IS NULL OR tenant_id = :tenant_id::uuid OR tenant_id IS NULL)");
                $stmt->bindValue(':tenant_id', null);
                $stmt->execute();
            }

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Semua log error berhasil dihapus.',
            ]);
            exit;
        } catch (\Throwable $e) {
            error_log("ErrorMonitor clearAll error: " . $e->getMessage());
            $this->jsonResponse(false, null, 'Gagal menghapus log error.', 500);
        }
    }

    /**
     * POST /api/v1/error-monitor/delete
     */
    public function deleteOne(): void
    {
        try {
            $this->validateCsrfToken();
            $body = $this->getJsonInput();
            $id   = trim((string)($body['id'] ?? ($_POST['id'] ?? '')));

            if (empty($id) || !preg_match('/^[a-f0-9\-]{36}$/i', $id)) {
                $this->jsonResponse(false, null, 'ID error tidak valid.', 422);
                return;
            }

            $sessionTenantId = $this->getSecureTenantId();
            $db   = Database::getConnection();

            if (!$this->isUserSuperAdmin() && !empty($sessionTenantId)) {
                $stmt = $db->prepare("DELETE FROM sistem.system_errors WHERE id = :id::uuid AND (tenant_id = :tenant_id::uuid OR tenant_id IS NULL)");
                $stmt->execute(['id' => $id, 'tenant_id' => $sessionTenantId]);
            } else {
                $stmt = $db->prepare("DELETE FROM sistem.system_errors WHERE id = :id::uuid AND (:tenant_id::uuid IS NULL OR tenant_id = :tenant_id::uuid OR tenant_id IS NULL)");
                $stmt->bindValue(':id', $id);
                $stmt->bindValue(':tenant_id', $sessionTenantId);
                $stmt->execute();
            }

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Log error berhasil dihapus.',
            ]);
            exit;
        } catch (\Throwable $e) {
            error_log("ErrorMonitor deleteOne error: " . $e->getMessage());
            $this->jsonResponse(false, null, 'Gagal menghapus log error.', 500);
        }
    }

    /**
     * POST /api/v1/error-monitor/log-client
     */
    public function logClientErrorApi(): void
    {
        $raw = file_get_contents('php://input');
        if (strlen($raw) > 100000) {
            $this->jsonResponse(false, null, 'Payload too large', 413);
            return;
        }

        $data = json_decode($raw, true);
        if (!$data || !isset($data['message'])) {
            $this->jsonResponse(false, null, 'Invalid data', 400);
            return;
        }

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                INSERT INTO sistem.system_errors 
                    (id, tenant_id, error_level, message, file, line, 
                     trace, request_url, request_method, user_agent, ip_address, context) 
                VALUES 
                    (gen_random_uuid(), :tenant_id, :error_level, :message, :file, :line, 
                     :trace, :request_url, :request_method, :user_agent, :ip_address, :context)
            ");

            $tenantId = $_SESSION['tenant_id'] ?? null;
            if (empty($tenantId) && isset($data['tenant_id'])) {
                $tenantId = $data['tenant_id'];
            }
            if ($tenantId === '') {
                $tenantId = null;
            }

            $stmt->execute([
                'tenant_id'      => $tenantId,
                'error_level'    => substr($data['type'] ?? 'JS_ERROR', 0, 50),
                'message'        => substr($data['message'], 0, 65535),
                'file'           => substr($data['file'] ?? '', 0, 500) ?: null,
                'line'           => isset($data['line']) ? (int)$data['line'] : 0,
                'trace'          => isset($data['trace']) ? json_encode($data['trace'], JSON_UNESCAPED_SLASHES) : null,
                'request_url'    => substr($data['url'] ?? $_SERVER['HTTP_REFERER'] ?? '', 0, 1000),
                'request_method' => 'CLIENT',
                'user_agent'     => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500) ?: null,
                'ip_address'     => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                'context'        => isset($data['context']) ? json_encode($data['context'], JSON_UNESCAPED_SLASHES) : null,
            ]);

            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        } catch (\Throwable $e) {
            error_log("[Client Error Logger Failed] " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        }
    }
}
