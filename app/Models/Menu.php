<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class Menu extends Model {

    /**
     * Ambil menu terotorisasi untuk sidebar berdasarkan role_name
     */
    public function getSidebarByRoleName(string $roleName): array {
        $targetTenant = $this->tenantId ?? '00000000-0000-0000-0000-000000000000';

        if ($this->tenantId !== null) {
            $stmtCount = $this->db->prepare("SELECT COUNT(*) FROM role_menu_access WHERE tenant_id = :tenant_id");
            $stmtCount->execute(['tenant_id' => $this->tenantId]);
            if ((int)$stmtCount->fetchColumn() === 0) {
                // Fallback ke default global jika belum ada kustomisasi tingkat sekolah
                $targetTenant = '00000000-0000-0000-0000-000000000000';
            }
        }

        // Query aman menggunakan JOIN dan PDO Prepared Statements (Secure by Design)
        $sql = "SELECT DISTINCT m.* 
                FROM menus m
                JOIN role_menu_access rma ON m.id = rma.menu_id
                JOIN roles r ON rma.role_id = r.id
                WHERE r.nama_role = :role_name AND rma.tenant_id = :tenant_id
                ORDER BY m.parent_id ASC, m.urutan ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'role_name' => $roleName,
            'tenant_id' => $targetTenant
        ]);
        $menus = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->buildMenuTree($menus);
    }

    /**
     * Membangun menu flat menjadi tree parent-child secara rekursif
     */
    private function buildMenuTree(array $menus, ?int $parentId = null): array {
        $branch = [];
        foreach ($menus as $menu) {
            if ($menu['parent_id'] == $parentId) {
                $children = $this->buildMenuTree($menus, $menu['id']);
                $menu['children'] = $children ?: [];
                $branch[] = $menu;
            }
        }
        return $branch;
    }

    /**
     * Ambil semua data menu
     */
    public function getAllMenus(): array {
        $sql = "SELECT * FROM menus ORDER BY parent_id ASC, urutan ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ambil semua data role
     */
    public function getAllRoles(): array {
        $sql = "SELECT * FROM roles ORDER BY id ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ambil data relasi akses yang aktif (untuk checklist matriks)
     * Format return: ['role_id-menu_id' => true]
     */
    public function getAccessMap(): array {
        // Cek apakah tenant saat ini memiliki kustomisasi di role_menu_access
        $targetTenant = $this->tenantId ?? '00000000-0000-0000-0000-000000000000';

        if ($this->tenantId !== null) {
            $stmtCount = $this->db->prepare("SELECT COUNT(*) FROM role_menu_access WHERE tenant_id = :tenant_id");
            $stmtCount->execute(['tenant_id' => $this->tenantId]);
            if ((int)$stmtCount->fetchColumn() === 0) {
                // Fallback ke default global jika belum ada kustomisasi tingkat sekolah
                $targetTenant = '00000000-0000-0000-0000-000000000000';
            }
        }

        $stmt = $this->db->prepare("SELECT role_id, menu_id FROM role_menu_access WHERE tenant_id = :tenant_id");
        $stmt->execute(['tenant_id' => $targetTenant]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $map = [];
        foreach ($rows as $row) {
            $key = $row['role_id'] . '-' . $row['menu_id'];
            $map[$key] = true;
        }
        return $map;
    }

    /**
     * Simpan matriks akses baru secara aman menggunakan transaksi database
     */
    public function saveAccessMap(array $accessData): bool {
        $targetTenant = $this->tenantId ?? '00000000-0000-0000-0000-000000000000';

        try {
            $this->db->beginTransaction();

            // 1. Hapus semua data pemetaan lama untuk tenant ini
            $stmt = $this->db->prepare("DELETE FROM role_menu_access WHERE tenant_id = :tenant_id");
            $stmt->execute(['tenant_id' => $targetTenant]);

            // 2. Tulis pemetaan baru jika ada data yang dicentang
            if (!empty($accessData)) {
                $sql = "INSERT INTO role_menu_access (tenant_id, role_id, menu_id) VALUES (:tenant_id, :role_id, :menu_id)";
                $stmt = $this->db->prepare($sql);

                foreach ($accessData as $roleId => $menus) {
                    foreach ($menus as $menuId) {
                        $stmt->execute([
                            'tenant_id' => $targetTenant,
                            'role_id' => (int)$roleId,
                            'menu_id' => (int)$menuId
                        ]);
                    }
                }
            }

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            error_log("Failed to save role menu access map: " . $e->getMessage());
            return false;
        }
    }
}
