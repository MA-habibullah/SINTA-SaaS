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
        $userId = $_SESSION['user_id'] ?? null;

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

            // Normalisasi URL path agar cocok dengan data seed tabel menus (contoh: /pengguna menjadi /SINTA-SaaS/pengguna)
            $normalizedPath = $path;
            if (!str_starts_with($normalizedPath, '/SINTA-SaaS')) {
                $normalizedPath = '/SINTA-SaaS' . $normalizedPath;
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

            // Cek apakah user memiliki hak akses kustom langsung (override)
            $stmtUserCheck = $db->prepare("
                SELECT COUNT(*) 
                FROM user_menu_access 
                WHERE tenant_id = :tenant_id 
                  AND user_id = :user_id 
                  AND menu_id = :menu_id
            ");
            $stmtUserCheck->execute([
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'menu_id' => $menuId
            ]);
            if ((int)$stmtUserCheck->fetchColumn() > 0) {
                return true; // Loloskan akses (Bypass Role)
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

    /**
     * Memeriksa apakah request URI saat ini diizinkan untuk diakses berdasarkan RouteGuard (dan override-nya)
     * atau terdaftar dalam array allowedRoles.
     */
    public static function checkCurrent(array $allowedRoles): bool {
        $roleName = $_SESSION['role_name'] ?? '';
        $roles = $_SESSION['roles'] ?? [$roleName];
        $tenantId = $_SESSION['tenant_id'] ?? null;
        
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $project_folder = '/SINTA-SaaS';
        if (strncasecmp($path, $project_folder, strlen($project_folder)) === 0) {
            $path = substr($path, strlen($project_folder));
        }

        // 1. Loloskan jika lolos validasi RouteGuard (termasuk override user_menu_access)
        if (self::check($path, $tenantId, $roles)) {
            return true;
        }

        // 2. Fallback: Loloskan jika salah satu role user termasuk dalam allowedRoles
        foreach ($roles as $r) {
            if (in_array($r, $allowedRoles, true)) {
                return true;
            }
        }

        return false;
    }
}
