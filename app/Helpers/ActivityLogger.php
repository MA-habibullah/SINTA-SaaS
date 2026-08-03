<?php

namespace App\Helpers;

use App\Config\Database;
use PDO;

class ActivityLogger {
    /**
     * Catat log audit trail aktivitas pengguna (INSERT, UPDATE, DELETE, LOGIN, LOGOUT, dsb.)
     * 
     * @param string $action Jenis aksi (INSERT, UPDATE, DELETE, LOGIN, LOGOUT, RESTORE, TOGGLE_STATUS, etc)
     * @param string $tableName Nama tabel / modul yang dimanipulasi
     * @param string|array|null $oldData State data sebelum perubahan (atau Record ID / Detail)
     * @param array|null $newData State data sesudah perubahan
     * @param string|null $tenantId Tenant ID opsional (override)
     * @param string|null $userId User ID opsional (override)
     * @return bool
     */
    public static function record(
        string $action, 
        string $tableName, 
        $oldData = null, 
        ?array $newData = null,
        ?string $tenantId = null,
        ?string $userId = null
    ): bool {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $effectiveUserId   = $userId ?? ($_SESSION['user_id'] ?? null);
        $effectiveUserRole = $_SESSION['role_name'] ?? 'system';
        $effectiveTenantId = $tenantId ?? ($_SESSION['tenant_id'] ?? null);

        $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        if (strpos($ipAddress, ',') !== false) {
            $ipAddress = trim(explode(',', $ipAddress)[0]);
        }
        $ipAddress = filter_var($ipAddress, FILTER_VALIDATE_IP) ? $ipAddress : '127.0.0.1';

        // Format payload old_data & new_data secara terstruktur
        $oldPayload = is_array($oldData) ? $oldData : ($oldData !== null ? ['detail' => (string)$oldData] : null);
        $newPayload = is_array($newData) ? $newData : ($newData !== null ? ['detail' => (string)$newData] : null);

        try {
            $db = Database::getConnection();

            if ($effectiveTenantId) {
                if ($effectiveTenantId === '00000000-0000-0000-0000-000000000000') {
                    $effectiveTenantId = null;
                } else {
                    $checkTenantStmt = $db->prepare("SELECT COUNT(*) FROM core.tenants WHERE id::text = ?");
                    $checkTenantStmt->execute([$effectiveTenantId]);
                    if ((int)$checkTenantStmt->fetchColumn() === 0) {
                        $effectiveTenantId = null;
                    }
                }
            }

            if ($effectiveUserId) {
                $checkUserStmt = $db->prepare("SELECT COUNT(*) FROM core.users WHERE id::text = ?");
                $checkUserStmt->execute([$effectiveUserId]);
                if ((int)$checkUserStmt->fetchColumn() === 0) {
                    $effectiveUserId = null;
                }
            }

            $stmt = $db->prepare("
                INSERT INTO sistem.activity_logs (
                    id, tenant_id, user_id, user_role, table_name, action, old_data, new_data, ip_address, created_at
                ) VALUES (
                    gen_random_uuid(), :tenant_id, :user_id, :user_role, :table_name, :action, :old_data, :new_data, :ip_address, CURRENT_TIMESTAMP
                )
            ");

            return $stmt->execute([
                'tenant_id'  => $effectiveTenantId,
                'user_id'    => $effectiveUserId,
                'user_role'  => $effectiveUserRole,
                'table_name' => $tableName,
                'action'     => strtoupper($action),
                'old_data'   => $oldPayload !== null ? json_encode($oldPayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null,
                'new_data'   => $newPayload !== null ? json_encode($newPayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null,
                'ip_address' => $ipAddress
            ]);
        } catch (\Throwable $e) {
            error_log("Failed to write Activity Log: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Alias method `log()` agar kompatibel dengan pemanggilan ActivityLogger::log(...) maupun ActivityLogger::record(...)
     */
    public static function log(
        string $action, 
        string $tableName, 
        $oldData = null, 
        ?array $newData = null,
        ?string $tenantId = null,
        ?string $userId = null
    ): bool {
        return static::record($action, $tableName, $oldData, $newData, $tenantId, $userId);
    }
}
