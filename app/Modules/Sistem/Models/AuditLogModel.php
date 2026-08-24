<?php

namespace App\Modules\Sistem\Models;

use App\Config\Database;
use PDO;
use Exception;

class AuditLogModel {
    protected PDO $db;
    protected string $table = 'sistem.activity_logs';

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Ambil data log aktivitas terpaginasi dengan filter cerdas
     */
    public function getPaginatedLogs(array $filters, int $page = 1, int $perPage = 15): array {
        if ($page < 1) $page = 1;
        if ($perPage < 1 || $perPage > 100) $perPage = 15;
        $offset = ($page - 1) * $perPage;

        $whereClauses = [];
        $params = [];

        $isSuperAdmin = !empty($filters['is_super_admin']);
        $tenantFilter = $filters['tenant_filter'] ?? '';
        $tenantId = $filters['tenant_id'] ?? null;
        $roleFilter = $filters['role_filter'] ?? '';
        $actionFilter = $filters['action_filter'] ?? '';
        $tableFilter = $filters['table_filter'] ?? '';
        $search = $filters['search'] ?? '';
        $startDate = $filters['start_date'] ?? '';
        $endDate = $filters['end_date'] ?? '';

        // Tenant scope filtering
        if ($isSuperAdmin) {
            if ($tenantFilter === 'system' || $tenantFilter === 'global') {
                $whereClauses[] = "l.tenant_id IS NULL";
            } elseif (!empty($tenantFilter) && $tenantFilter !== 'all') {
                $whereClauses[] = "l.tenant_id = :tenant_filter";
                $params['tenant_filter'] = $tenantFilter;
            }
        } else {
            if (!empty($tenantId)) {
                $whereClauses[] = "(l.tenant_id = :tenant_id OR l.tenant_id IS NULL)";
                $params['tenant_id'] = $tenantId;
            }
            $whereClauses[] = "l.user_role != 'super_admin'";
        }

        // Role filter
        if (!empty($roleFilter) && $roleFilter !== 'all') {
            $whereClauses[] = "l.user_role = :role_filter";
            $params['role_filter'] = $roleFilter;
        }

        // Action filter
        if (!empty($actionFilter) && $actionFilter !== 'all') {
            $whereClauses[] = "UPPER(l.action) = :action_filter";
            $params['action_filter'] = strtoupper($actionFilter);
        }

        // Table / Module filter
        if (!empty($tableFilter) && $tableFilter !== 'all') {
            $whereClauses[] = "l.table_name = :table_filter";
            $params['table_filter'] = $tableFilter;
        }

        // Date range filter
        if (!empty($startDate)) {
            $whereClauses[] = "l.created_at::date >= :start_date::date";
            $params['start_date'] = $startDate;
        }
        if (!empty($endDate)) {
            $whereClauses[] = "l.created_at::date <= :end_date::date";
            $params['end_date'] = $endDate;
        }

        // Universal Search
        if (!empty($search)) {
            $whereClauses[] = "(
                l.action ILIKE :search OR 
                l.table_name ILIKE :search OR 
                l.user_role ILIKE :search OR
                u.nama_lengkap ILIKE :search OR
                l.ip_address ILIKE :search OR
                l.old_data::text ILIKE :search OR
                l.new_data::text ILIKE :search
            )";
            $params['search'] = '%' . $search . '%';
        }

        $whereSql = '';
        if (!empty($whereClauses)) {
            $whereSql = 'WHERE ' . implode(' AND ', $whereClauses);
        }

        // Query Logs
        $sql = "
            SELECT 
                l.id,
                l.tenant_id,
                l.user_id,
                l.user_role,
                l.table_name,
                l.action,
                l.old_data,
                l.new_data,
                l.ip_address,
                l.created_at,
                u.nama_lengkap AS actor_name,
                t.nama_sekolah
            FROM {$this->table} l
            LEFT JOIN core.users u ON l.user_id = u.id
            LEFT JOIN core.tenants t ON l.tenant_id = t.id
            {$whereSql}
            ORDER BY l.created_at DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue(':' . $key, $val, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Query Total Count
        $countSql = "
            SELECT COUNT(*) 
            FROM {$this->table} l
            LEFT JOIN core.users u ON l.user_id = u.id
            {$whereSql}
        ";
        $countStmt = $this->db->prepare($countSql);
        foreach ($params as $key => $val) {
            $countStmt->bindValue(':' . $key, $val, PDO::PARAM_STR);
        }
        $countStmt->execute();
        $total = (int)$countStmt->fetchColumn();

        return [
            'logs' => $logs,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'pages' => (int)ceil($total / max(1, $perPage))
            ]
        ];
    }

    /**
     * Ambil 4 statistik ringkasan metrik log aktivitas
     */
    public function getStatsSummary(?string $tenantId = null, bool $isSuperAdmin = false): array {
        $whereSql = '';
        $params = [];

        if (!$isSuperAdmin) {
            if (!empty($tenantId)) {
                $whereSql = "WHERE (tenant_id = :tenant_id OR tenant_id IS NULL) AND user_role != 'super_admin'";
                $params['tenant_id'] = $tenantId;
            } else {
                $whereSql = "WHERE user_role != 'super_admin'";
            }
        } elseif (!empty($tenantId) && $tenantId !== 'all') {
            if ($tenantId === 'system' || $tenantId === 'global') {
                $whereSql = "WHERE tenant_id IS NULL";
            } else {
                $whereSql = "WHERE tenant_id = :tenant_id";
                $params['tenant_id'] = $tenantId;
            }
        }

        $sql = "
            SELECT 
                COUNT(*) AS total_logs,
                COUNT(CASE WHEN UPPER(action) = 'UPDATE' THEN 1 END) AS total_update,
                COUNT(CASE WHEN UPPER(action) = 'INSERT' THEN 1 END) AS total_insert,
                COUNT(CASE WHEN UPPER(action) = 'DELETE' THEN 1 END) AS total_delete,
                COUNT(CASE WHEN created_at::date = CURRENT_DATE THEN 1 END) AS total_today
            FROM {$this->table}
            {$whereSql}
        ";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue(':' . $key, $val, PDO::PARAM_STR);
        }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total_logs'   => (int)($row['total_logs'] ?? 0),
            'total_update' => (int)($row['total_update'] ?? 0),
            'total_insert' => (int)($row['total_insert'] ?? 0),
            'total_delete' => (int)($row['total_delete'] ?? 0),
            'total_today'  => (int)($row['total_today'] ?? 0),
        ];
    }

    /**
     * Ambil opsi filter (daftar sekolah, peran, daftar aksi, dan modul/tabel)
     */
    public function getFiltersOptions(?string $tenantId = null, bool $isSuperAdmin = false): array {
        $tenants = [];
        if ($isSuperAdmin) {
            $stmtTenants = $this->db->query("
                SELECT id, nama_sekolah, npsn, subdomain 
                FROM core.tenants 
                WHERE status = 'active' OR status IS NULL 
                ORDER BY nama_sekolah ASC
            ");
            $tenants = $stmtTenants->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }

        if ($isSuperAdmin && empty($tenantId)) {
            // Super Admin Global Platform Options (All Tenants)
            // Roles
            $stmtRoles = $this->db->prepare("SELECT DISTINCT user_role FROM {$this->table} WHERE (:tenant_id::uuid IS NULL OR tenant_id = :tenant_id::uuid) AND user_role IS NOT NULL AND user_role != '' ORDER BY user_role ASC");
            $stmtRoles->execute(['tenant_id' => null]);
            $roles = $stmtRoles->fetchAll(PDO::FETCH_COLUMN) ?: [];

            // Actions
            $stmtActions = $this->db->prepare("SELECT DISTINCT UPPER(action) FROM {$this->table} WHERE (:tenant_id::uuid IS NULL OR tenant_id = :tenant_id::uuid) AND action IS NOT NULL AND action != '' ORDER BY 1 ASC");
            $stmtActions->execute(['tenant_id' => null]);
            $actions = $stmtActions->fetchAll(PDO::FETCH_COLUMN) ?: [];

            // Tables / Modules
            $stmtTables = $this->db->prepare("SELECT DISTINCT table_name FROM {$this->table} WHERE (:tenant_id::uuid IS NULL OR tenant_id = :tenant_id::uuid) AND table_name IS NOT NULL AND table_name != '' ORDER BY table_name ASC");
            $stmtTables->execute(['tenant_id' => null]);
            $tables = $stmtTables->fetchAll(PDO::FETCH_COLUMN) ?: [];
        } else {
            // Tenant Specific Context
            // Roles
            $stmtRoles = $this->db->prepare("SELECT DISTINCT user_role FROM {$this->table} WHERE tenant_id = :tenant_id::uuid AND user_role IS NOT NULL AND user_role != '' ORDER BY user_role ASC");
            $stmtRoles->execute(['tenant_id' => $tenantId]);
            $roles = $stmtRoles->fetchAll(PDO::FETCH_COLUMN) ?: [];

            // Actions
            $stmtActions = $this->db->prepare("SELECT DISTINCT UPPER(action) FROM {$this->table} WHERE tenant_id = :tenant_id::uuid AND action IS NOT NULL AND action != '' ORDER BY 1 ASC");
            $stmtActions->execute(['tenant_id' => $tenantId]);
            $actions = $stmtActions->fetchAll(PDO::FETCH_COLUMN) ?: [];

            // Tables / Modules
            $stmtTables = $this->db->prepare("SELECT DISTINCT table_name FROM {$this->table} WHERE tenant_id = :tenant_id::uuid AND table_name IS NOT NULL AND table_name != '' ORDER BY table_name ASC");
            $stmtTables->execute(['tenant_id' => $tenantId]);
            $tables = $stmtTables->fetchAll(PDO::FETCH_COLUMN) ?: [];
        }

        return [
            'tenants' => $tenants,
            'roles'   => $roles,
            'actions' => $actions,
            'tables'  => $tables
        ];
    }

    /**
     * Hapus log aktivitas berdasarkan rentang tanggal dan isolasi tenant
     */
    public function deleteLogs(string $startDate, string $endDate, ?string $targetTenantId = null, bool $isSuperAdmin = false): int {
        $sql = "DELETE FROM {$this->table} WHERE created_at::date BETWEEN :start_date::date AND :end_date::date";
        $params = [
            'start_date' => $startDate,
            'end_date'   => $endDate
        ];

        if ($targetTenantId === 'system' && $isSuperAdmin) {
            $sql .= " AND tenant_id IS NULL";
        } elseif (!empty($targetTenantId) && $targetTenantId !== 'all') {
            $sql .= " AND tenant_id = :target_tenant";
            $params['target_tenant'] = $targetTenantId;
        }

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v, PDO::PARAM_STR);
        }
        $stmt->execute();

        return $stmt->rowCount();
    }
}
