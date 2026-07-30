<?php
/**
 * Migration: Add Pembinaan & Supervisi Menu and Roles
 */
return [
    'up' => function (PDO $pdo): void {
        // 1. Check and Create 'kepala_sekolah' role
        $stmt = $pdo->query("SELECT id FROM roles WHERE nama_role = 'kepala_sekolah'");
        $kepsekRoleId = $stmt->fetchColumn();
        if (!$kepsekRoleId) {
            $stmt = $pdo->query("SELECT MAX(id) FROM roles");
            $maxId = (int)$stmt->fetchColumn();
            $kepsekRoleId = $maxId + 1;
            $stmtInsert = $pdo->prepare("INSERT INTO roles (id, nama_role) VALUES (?, 'kepala_sekolah')");
            $stmtInsert->execute([$kepsekRoleId]);
            echo "  OK Role kepala_sekolah created with ID $kepsekRoleId\n";
        } else {
            echo "  INFO Role kepala_sekolah already exists (ID: $kepsekRoleId)\n";
        }

        // 2. Create Parent Menu: Pembinaan & Supervisi
        $stmt = $pdo->query("SELECT id FROM menus WHERE nama_menu = 'Pembinaan & Supervisi'");
        $parentId = $stmt->fetchColumn();
        if (!$parentId) {
            $stmtInsert = $pdo->prepare("INSERT INTO menus (nama_menu, url, icon, parent_id, urutan, created_at, updated_at) VALUES ('Pembinaan & Supervisi', '/SINTA-SaaS/pembinaan', 'bi bi-person-video3', NULL, 10, NOW(), NOW())");
            $stmtInsert->execute();
            $parentId = $pdo->lastInsertId();
            echo "  OK Menu 'Pembinaan & Supervisi' created with ID $parentId\n";
        } else {
            echo "  INFO Menu 'Pembinaan & Supervisi' already exists (ID: $parentId)\n";
        }

        // 3. Get super_admin role ID
        $stmt = $pdo->query("SELECT id FROM roles WHERE nama_role = 'super_admin'");
        $superAdminId = $stmt->fetchColumn();

        // Roles to grant access (super_admin, operator_sekolah, kepala_sekolah)
        $rolesToGrant = [2, $kepsekRoleId]; // 2 is typically operator_sekolah
        if ($superAdminId) {
            $rolesToGrant[] = $superAdminId;
        }

        // 4. Grant access in role_menu_access
        foreach ($rolesToGrant as $roleId) {
            $stmt = $pdo->prepare("SELECT 1 FROM role_menu_access WHERE role_id = ? AND menu_id = ?");
            $stmt->execute([$roleId, $parentId]);
            if (!$stmt->fetch()) {
                $stmtInsert = $pdo->prepare("INSERT INTO role_menu_access (tenant_id, role_id, menu_id) VALUES ('00000000-0000-0000-0000-000000000000', ?, ?)");
                $stmtInsert->execute([$roleId, $parentId]);
                echo "  OK Access granted to role ID $roleId\n";
            }
        }

        // 5. Grant access to all tenants in tenant_menu_access
        $tenants = $pdo->query("SELECT id FROM tenants")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($tenants as $t) {
            $stmt = $pdo->prepare("SELECT 1 FROM tenant_menu_access WHERE tenant_id = ? AND menu_id = ?");
            $stmt->execute([$t['id'], $parentId]);
            if (!$stmt->fetch()) {
                $stmtInsert = $pdo->prepare("INSERT INTO tenant_menu_access (tenant_id, menu_id) VALUES (?, ?)");
                $stmtInsert->execute([$t['id'], $parentId]);
            }
        }
        echo "  OK Tenant menu access updated.\n";
    },

    'down' => function (PDO $pdo): void {
        $stmt = $pdo->query("SELECT id FROM menus WHERE nama_menu = 'Pembinaan & Supervisi'");
        $parentId = $stmt->fetchColumn();
        
        if ($parentId) {
            $pdo->prepare("DELETE FROM role_menu_access WHERE menu_id = ?")->execute([$parentId]);
            $pdo->prepare("DELETE FROM tenant_menu_access WHERE menu_id = ?")->execute([$parentId]);
            $pdo->prepare("DELETE FROM menus WHERE id = ?")->execute([$parentId]);
            echo "  OK Menu 'Pembinaan & Supervisi' and accesses removed.\n";
        }
    }
];
