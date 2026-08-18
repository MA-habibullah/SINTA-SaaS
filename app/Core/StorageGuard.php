<?php

namespace App\Core;

use App\Config\Database;
use PDO;

class StorageGuard {

    /**
     * Get the total storage space currently used by a school/tenant (in bytes).
     */
    public static function getTenantStorageUsage(string $tenantId): int {
        $storageRoot = dirname(__DIR__, 2) . '/storage/app/public';
        $bytes = 0;

        if (!is_dir($storageRoot)) {
            return 0;
        }

        try {
            $modules = array_diff(scandir($storageRoot) ?: [], ['.', '..']);
            foreach ($modules as $modul) {
                $modulPath = $storageRoot . '/' . $modul;
                if (!is_dir($modulPath)) continue;

                // Direct folder: storage/app/public/{modul}/{tenant_id}
                $tenantDir = $modulPath . '/' . $tenantId;
                if (is_dir($tenantDir)) {
                    $bytes += self::calcFolderBytes($tenantDir);
                }

                // Nested folder: e.g. storage/app/public/perpustakaan/covers/{tenant_id}
                $subDirs = array_diff(scandir($modulPath) ?: [], ['.', '..']);
                foreach ($subDirs as $sub) {
                    $nestedTenantDir = $modulPath . '/' . $sub . '/' . $tenantId;
                    if (is_dir($nestedTenantDir)) {
                        $bytes += self::calcFolderBytes($nestedTenantDir);
                    }
                }
            }
        } catch (\Throwable $e) {
            error_log("StorageGuard: Failed to calculate usage for {$tenantId}: " . $e->getMessage());
        }

        return $bytes;
    }

    private static function calcFolderBytes(string $dir): int {
        $bytes = 0;
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $bytes += $file->getSize();
                }
            }
        } catch (\Throwable $e) {}
        return $bytes;
    }

    /**
     * Get tenant storage limit in bytes based on active package from database.
     */
    public static function getTenantStorageLimit(string $tenantId): int {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT storage_limit_mb, paket_aktif FROM core.tenants WHERE id = ?");
            $stmt->execute([$tenantId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                if (!empty($row['storage_limit_mb']) && (int)$row['storage_limit_mb'] > 0) {
                    return (int)$row['storage_limit_mb'] * 1024 * 1024;
                }

                $paket = $row['paket_aktif'] ?? '';
                if ($paket === 'Basic') {
                    return 50 * 1024 * 1024; // 50 MB
                } elseif ($paket === 'Pro') {
                    return 250 * 1024 * 1024; // 250 MB
                } elseif ($paket === 'Enterprise SaaS') {
                    return 5120 * 1024 * 1024; // 5 GB (5120 MB)
                }
            }
        } catch (\Throwable $e) {
            error_log("StorageGuard limit check error for {$tenantId}: " . $e->getMessage());
        }

        return 1024 * 1024 * 1024; // 1 GB default
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
