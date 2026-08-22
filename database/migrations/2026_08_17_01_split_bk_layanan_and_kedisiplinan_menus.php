<?php

return [
    'up' => function (\PDO $pdo): void {
        // 1. Rename "Layanan & Kedisiplinan" -> "Layanan BK"
        $stmtRename = $pdo->prepare("UPDATE core.menus SET nama_menu = 'Layanan BK' WHERE url = '/bk/layanan' OR nama_menu = 'Layanan & Kedisiplinan'");
        $stmtRename->execute();

        // 2. Find parent_id for "BIMBINGAN KONSELING" header
        $stmtParent = $pdo->prepare("SELECT id FROM core.menus WHERE nama_menu = 'BIMBINGAN KONSELING' LIMIT 1");
        $stmtParent->execute();
        $parentId = $stmtParent->fetchColumn();

        if (!$parentId) return;

        // 3. Check if menu "Kedisiplinan Siswa" already exists
        $stmtCheck = $pdo->prepare("SELECT id FROM core.menus WHERE url = '/bk/kedisiplinan' OR nama_menu = 'Kedisiplinan Siswa' LIMIT 1");
        $stmtCheck->execute();
        $existingKedisiplinanId = $stmtCheck->fetchColumn();

        if (!$existingKedisiplinanId) {
            $menuId = 'e3b8a1c9-8812-4f9e-9d22-771144220033';
            $stmtInsert = $pdo->prepare("
                INSERT INTO core.menus (id, parent_id, nama_menu, url, icon, urutan)
                VALUES (?, ?, 'Kedisiplinan Siswa', '/bk/kedisiplinan', 'bi bi-shield-exclamation', 22)
            ");
            $stmtInsert->execute([$menuId, $parentId]);
            $existingKedisiplinanId = $menuId;
        }

        // 4. Grant access in core.tenant_menu_access for ALL tenants
        $stmtTenants = $pdo->query("SELECT id FROM core.tenants");
        $tenants = $stmtTenants->fetchAll(\PDO::FETCH_COLUMN);
        $tenants[] = 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12';

        $stmtTma = $pdo->prepare("
            INSERT INTO core.tenant_menu_access (id, tenant_id, menu_id)
            VALUES (gen_random_uuid(), ?, ?)
            ON CONFLICT DO NOTHING
        ");

        foreach (array_unique($tenants) as $tId) {
            $stmtTma->execute([$tId, $existingKedisiplinanId]);
        }

        // 5. Grant access in core.role_menu_access for relevant roles
        $stmtRoles = $pdo->query("SELECT id FROM core.roles WHERE LOWER(nama_role) IN ('super_admin', 'superadmin', 'admin', 'operator_sekolah', 'guru_bk', 'guru', 'petugas_bk', 'kurikulum')");
        $roles = $stmtRoles->fetchAll(\PDO::FETCH_COLUMN);

        $stmtRma = $pdo->prepare("
            INSERT INTO core.role_menu_access (tenant_id, role_id, menu_id)
            VALUES ('e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12', ?, ?)
            ON CONFLICT DO NOTHING
        ");

        foreach ($roles as $rId) {
            $stmtRma->execute([$rId, $existingKedisiplinanId]);
        }

        echo "- Menu Layanan BK dan Kedisiplinan Siswa berhasil dipisahkan.\n";
    },
    'down' => function (\PDO $pdo): void {
        $pdo->exec("DELETE FROM core.menus WHERE url = '/bk/kedisiplinan'");
        $pdo->exec("UPDATE core.menus SET nama_menu = 'Layanan & Kedisiplinan' WHERE url = '/bk/layanan'");
    },
];
