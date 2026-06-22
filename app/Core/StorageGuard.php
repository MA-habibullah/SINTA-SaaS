<?php

namespace App\Core;

use App\Config\Database;
use PDO;

class StorageGuard {

    /**
     * Get the total storage space currently used by a school/tenant (in bytes).
     */
    public static function getTenantStorageUsage(string $tenantId): int {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT d.file_sizes 
                FROM dokumen d 
                JOIN siswa s ON d.id_siswa = s.id 
                WHERE s.tenant_id = :tenant_id AND s.deleted_at IS NULL
            ");
            $stmt->execute(['tenant_id' => $tenantId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $totalSize = 0;
            foreach ($rows as $row) {
                if (!empty($row['file_sizes'])) {
                    $sizes = json_decode($row['file_sizes'], true);
                    if (is_array($sizes)) {
                        foreach ($sizes as $size) {
                            $totalSize += (int)$size;
                        }
                    }
                }
            }
            return $totalSize;
        } catch (\Throwable $e) {
            error_log("Failed to calculate storage usage: " . $e->getMessage());
            return 0;
        }
    }

    public static function getTenantStorageLimit(string $tenantId): int {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT storage_limit_mb FROM tenants WHERE id = :tenant_id LIMIT 1");
            $stmt->execute(['tenant_id' => $tenantId]);
            $limitMb = $stmt->fetchColumn();
            
            if ($limitMb !== false && $limitMb > 0) {
                return (int)$limitMb * 1024 * 1024; // MB to bytes
            }
            
            return 50 * 1024 * 1024;  // 50 MB fallback
        } catch (\Throwable $e) {
            error_log("Failed to fetch storage limit: " . $e->getMessage());
            return 50 * 1024 * 1024; // 50 MB fallback
        }
    }

    /**
     * Check if tenant has enough space left for an incoming file upload.
     */
    public static function checkStorageLimit(string $tenantId, int $incomingSize): bool {
        $used = self::getTenantStorageUsage($tenantId);
        $limit = self::getTenantStorageLimit($tenantId);
        return ($used + $incomingSize) <= $limit;
    }
}
