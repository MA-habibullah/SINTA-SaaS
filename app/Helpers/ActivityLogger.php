<?php

namespace App\Helpers;

use App\Config\Database;
use PDO;

class ActivityLogger {

    /**
     * Daftar kunci sensitif yang wajib disanitasi sebelum masuk audit trail
     */
    private static array $sensitiveKeys = [
        'password',
        'password_hash',
        'kata_sandi',
        'token',
        'remember_token',
        'secret',
        'api_key',
        'access_token',
        'refresh_token',
        'pin'
    ];

    /**
     * Catat log audit trail aktivitas pengguna (INSERT, UPDATE, DELETE, LOGIN, LOGOUT, dsb.)
     * 
     * @param string $action Jenis aksi (INSERT, UPDATE, DELETE, LOGIN, LOGOUT, RESTORE, TOGGLE_STATUS, etc)
     * @param string $tableName Nama tabel / modul yang dimanipulasi
     * @param mixed $oldData State data sebelum perubahan (atau Record ID / Detail)
     * @param mixed $newData State data sesudah perubahan
     * @param string|null $tenantId Tenant ID opsional (override)
     * @param string|null $userId User ID opsional (override)
     * @return bool
     */
    public static function record(
        string $action, 
        string $tableName, 
        $oldData = null, 
        $newData = null,
        $tenantId = null,
        $userId = null
    ): bool {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        // Resolusi User ID
        $effectiveUserId = is_string($userId) && !empty($userId) 
            ? $userId 
            : ($_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? null);

        // Resolusi User Role
        $effectiveUserRole = $_SESSION['role_name'] ?? $_SESSION['user']['role'] ?? null;
        if (!$effectiveUserRole && isset($_SESSION['roles']) && is_array($_SESSION['roles']) && !empty($_SESSION['roles'])) {
            $effectiveUserRole = $_SESSION['roles'][0];
        }
        if (!$effectiveUserRole) {
            $effectiveUserRole = 'system';
        }

        // Resolusi Tenant ID
        $effectiveTenantId = is_string($tenantId) && !empty($tenantId) 
            ? $tenantId 
            : ($_SESSION['tenant_id'] ?? $_SESSION['user']['tenant_id'] ?? null);

        if ($effectiveTenantId === 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12' || $effectiveTenantId === 'global') {
            $effectiveTenantId = null;
        }

        // IP Address
        $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        if (strpos($ipAddress, ',') !== false) {
            $ipAddress = trim(explode(',', $ipAddress)[0]);
        }
        $ipAddress = filter_var($ipAddress, FILTER_VALIDATE_IP) ? $ipAddress : '127.0.0.1';

        // Sanitasi & format payload old_data & new_data
        $oldPayload = self::sanitizePayload($oldData);
        $newPayload = self::sanitizePayload($newData);

        try {
            $db = Database::getConnection();

            // Verifikasi integritas relasi foreign key
            if ($effectiveTenantId) {
                $checkTenantStmt = $db->prepare("SELECT COUNT(*) FROM core.tenants WHERE id::text = :tid");
                $checkTenantStmt->execute(['tid' => (string)$effectiveTenantId]);
                if ((int)$checkTenantStmt->fetchColumn() === 0) {
                    $effectiveTenantId = null;
                }
            }

            if ($effectiveUserId) {
                $sqlUser = "SELECT COUNT(*) FROM core.users WHERE id::text = :uid";
                $paramsUser = ['uid' => (string)$effectiveUserId];
                if ($effectiveTenantId) {
                    $sqlUser .= " AND (tenant_id = :tenant_id OR tenant_id IS NULL)";
                    $paramsUser['tenant_id'] = (string)$effectiveTenantId;
                }
                $checkUserStmt = $db->prepare($sqlUser);
                $checkUserStmt->execute($paramsUser);
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
                'user_role'  => (string)$effectiveUserRole,
                'table_name' => (string)$tableName,
                'action'     => strtoupper(trim($action)),
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
        $newData = null,
        ?string $tenantId = null,
        ?string $userId = null
    ): bool {
        return static::record($action, $tableName, $oldData, $newData, $tenantId, $userId);
    }

    /**
     * Sanitasi data payload rekaman audit
     */
    private static function sanitizePayload($data): ?array {
        if ($data === null) {
            return null;
        }

        if (is_string($data)) {
            $decoded = json_decode($data, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $data = $decoded;
            } else {
                return ['detail' => $data];
            }
        }

        if (is_object($data)) {
            $data = (array)$data;
        }

        if (!is_array($data)) {
            return ['detail' => (string)$data];
        }

        return self::maskSensitiveRecursive($data);
    }

    /**
     * Rekursif masking password dan kredensial rahasia
     */
    private static function maskSensitiveRecursive(array $arr): array {
        $result = [];
        foreach ($arr as $key => $val) {
            $lowerKey = strtolower((string)$key);
            if (in_array($lowerKey, self::$sensitiveKeys, true)) {
                $result[$key] = '******** [DISAMARKAN_DEMI_KEAMANAN]';
            } elseif (is_array($val)) {
                $result[$key] = self::maskSensitiveRecursive($val);
            } else {
                $result[$key] = $val;
            }
        }
        return $result;
    }
}
