<?php
/**
 * Migration: Remove default PPDB menu access for Guru role
 * Location: database/migrations/2026_07_20_03_remove_guru_default_ppdb_access.php
 */

return [
    'up' => function (PDO $pdo): void {
        // Hapus akses PPDB default (menu_id: 2, 3, 4, 10) untuk role guru (role_id: 3) dari role_menu_access
        $pdo->exec("DELETE FROM role_menu_access WHERE role_id = 3 AND menu_id IN (2, 3, 4, 10)");
        echo "- Berhasil menghapus akses menu PPDB default untuk role Guru.\n";
    },
    'down' => function (PDO $pdo): void {
        // Pulihkan default akses PPDB ke role guru (role_id: 3) untuk tenant default global, tenant dummy & tenant sekolah utama
        $tenants = [
            '00000000-0000-0000-0000-000000000000',
            '11111111-1111-1111-1111-111111111111',
            'e33ddee4-3c36-4bbe-b4b8-6b4fc482dc46'
        ];
        $menuIds = [2, 3, 4, 10];

        $stmt = $pdo->prepare("INSERT INTO role_menu_access (tenant_id, role_id, menu_id) VALUES (?, 3, ?)");
        foreach ($tenants as $tenantId) {
            // Pastikan tenant ada di database sebelum insert
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM tenants WHERE id = ?");
            $stmtCheck->execute([$tenantId]);
            $exists = (int)$stmtCheck->fetchColumn() > 0;
            
            // Default UUID ('00000000...') tidak terdaftar di tabel tenants tetapi tetap digunakan untuk fallback global
            if ($exists || $tenantId === '00000000-0000-0000-0000-000000000000') {
                foreach ($menuIds as $menuId) {
                    try {
                        $stmt->execute([$tenantId, $menuId]);
                    } catch (\PDOException $e) {
                        // Abaikan jika sudah ada (duplicate key)
                    }
                }
            }
        }
        echo "- Berhasil memulihkan akses menu PPDB default untuk role Guru.\n";
    },
];
