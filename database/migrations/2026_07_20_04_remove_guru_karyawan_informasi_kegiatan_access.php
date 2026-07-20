<?php
/**
 * Migration: Remove default Informasi & Kegiatan menu access for Guru and Karyawan roles
 * Location: database/migrations/2026_07_20_04_remove_guru_karyawan_informasi_kegiatan_access.php
 */

return [
    'up' => function (PDO $pdo): void {
        // Hapus akses menu Informasi & Kegiatan (menu_id: 45, 46, 47) untuk role guru (3) dan karyawan (6)
        $pdo->exec("DELETE FROM role_menu_access WHERE role_id IN (3, 6) AND menu_id IN (45, 46, 47)");
        echo "- Berhasil mencabut akses default Informasi & Kegiatan untuk Guru dan Karyawan.\n";
    },
    'down' => function (PDO $pdo): void {
        // Pulihkan default akses jika diperlukan (opsional/rollback)
        $tenants = ['00000000-0000-0000-0000-000000000000'];
        $stmt = $pdo->prepare("INSERT INTO role_menu_access (tenant_id, role_id, menu_id) VALUES (?, ?, ?)");
        
        foreach ($tenants as $tenantId) {
            // Guru (3)
            try {
                $stmt->execute([$tenantId, 3, 45]);
                $stmt->execute([$tenantId, 3, 47]);
            } catch (\PDOException $e) {}
            
            // Karyawan (6)
            try {
                $stmt->execute([$tenantId, 6, 45]);
            } catch (\PDOException $e) {}
        }
        echo "- Berhasil memulihkan akses default Informasi & Kegiatan untuk Guru dan Karyawan.\n";
    },
];
