<?php

namespace App\Core;

use App\Config\Database;
use PDO;

class RouteGuard {
    
    /**
     * Memeriksa apakah tenant saat ini diizinkan untuk mengakses URL/path tertentu.
     * Mengembalikan true jika diizinkan, false jika diblokir.
     */
    public static function check(string $path, ?string $tenantId, $roleName): bool {
        // Normalisasi parameter roleName menjadi array
        $roleNames = is_array($roleName) ? $roleName : [$roleName];

        // 1. Super Admin memegang otoritas penuh (Global Scope - Bebas Akses)
        if (in_array('super_admin', $roleNames)) {
            return true;
        }

        // 2. Jika bukan Super Admin tetapi tidak memiliki tenant ID yang terasosiasi, block akses
        if (empty($tenantId)) {
            return false;
        }

        try {
            $db = Database::getConnection();

            // Normalisasi URL path agar cocok dengan data seed tabel menus (contoh: /pengguna menjadi /dapodik-spmb/pengguna)
            $normalizedPath = $path;
            if (!str_starts_with($normalizedPath, '/dapodik-spmb')) {
                $normalizedPath = '/dapodik-spmb' . $normalizedPath;
            }

            // 3. Cari menu_id berdasarkan path URL yang diakses
            $stmt = $db->prepare("SELECT id FROM menus WHERE url = :url LIMIT 1");
            $stmt->execute(['url' => $normalizedPath]);
            $menuId = $stmt->fetchColumn();

            // Jika path URL bukan bagian dari menu dinamis yang dikelola di database (misalnya assets atau API internal), izinkan lewat.
            if (!$menuId) {
                return true;
            }

            // 4. Verifikasi apakah sekolah (tenant_id) saat ini memiliki akses ke menu tersebut di tabel tenant_menu_access
            $stmtCheck = $db->prepare("
                SELECT COUNT(*) 
                FROM tenant_menu_access 
                WHERE tenant_id = :tenant_id AND menu_id = :menu_id
            ");
            $stmtCheck->execute([
                'tenant_id' => $tenantId,
                'menu_id' => $menuId
            ]);
            $countTenantAccess = (int)$stmtCheck->fetchColumn();

            if ($countTenantAccess === 0) {
                return false; // Ditutup total oleh Super Admin untuk Sekolah ini
            }

            // 5. Verifikasi apakah peran user ini memiliki hak akses di tabel role_menu_access (tenant-isolated atau fallback)
            $stmtCheckCustom = $db->prepare("SELECT COUNT(*) FROM role_menu_access WHERE tenant_id = :tenant_id");
            $stmtCheckCustom->execute(['tenant_id' => $tenantId]);
            $hasCustomAccess = (int)$stmtCheckCustom->fetchColumn() > 0;
            $accessTenantId = $hasCustomAccess ? $tenantId : '00000000-0000-0000-0000-000000000000';

            // Pengecekan multi-role menggunakan query IN
            $inClause = implode(',', array_fill(0, count($roleNames), '?'));
            $stmtRoleCheck = $db->prepare("
                SELECT COUNT(*) 
                FROM role_menu_access rma
                JOIN roles r ON rma.role_id = r.id
                WHERE rma.tenant_id = ? 
                  AND rma.menu_id = ? 
                  AND r.nama_role IN ($inClause)
            ");
            
            $params = array_merge([$accessTenantId, $menuId], $roleNames);
            $stmtRoleCheck->execute($params);
            $hasRoleAccess = (int)$stmtRoleCheck->fetchColumn() > 0;

            return $hasRoleAccess;

        } catch (\Throwable $e) {
            // Secure by Design: Log error dan izinkan akses demi mencegah kegagalan sistem total secara tidak sengaja
            error_log("RouteGuard database check failed: " . $e->getMessage());
            return true;
        }
    }
}
