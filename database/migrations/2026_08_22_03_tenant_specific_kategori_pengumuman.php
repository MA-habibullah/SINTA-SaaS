<?php

return [
    'up' => function (PDO $pdo): void {
        // Standard categories template
        $defaultCategories = [
            'Akademik & Ujian',
            'Kegiatan & Ekstrakurikuler',
            'Administrasi & Keuangan',
            'Kedisiplinan & Tata Tertib',
            'Libur & Hari Besar Nasional',
            'Prestasi Siswa & Guru'
        ];

        // 1. Get all active school tenants
        $stmtTenants = $pdo->query("SELECT id, subdomain, nama_sekolah FROM core.tenants WHERE subdomain != 'admin' ORDER BY subdomain");
        $schoolTenants = $stmtTenants->fetchAll(PDO::FETCH_ASSOC);

        // Keep global categories for global/pusat (tenant_id IS NULL)
        foreach ($defaultCategories as $catName) {
            $stmtCheck = $pdo->prepare("SELECT id FROM sistem.kategori_pengumuman WHERE nama_kategori = :nama AND tenant_id IS NULL");
            $stmtCheck->execute(['nama' => $catName]);
            if (!$stmtCheck->fetchColumn()) {
                $stmtIns = $pdo->prepare("INSERT INTO sistem.kategori_pengumuman (id, tenant_id, nama_kategori, created_at, updated_at) VALUES (gen_random_uuid(), NULL, :nama, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
                $stmtIns->execute(['nama' => $catName]);
            }
        }

        // 2. For each school tenant, ensure they have their own independent categories
        foreach ($schoolTenants as $t) {
            $tenantId = $t['id'];
            foreach ($defaultCategories as $catName) {
                $stmtCheck = $pdo->prepare("SELECT id FROM sistem.kategori_pengumuman WHERE nama_kategori = :nama AND tenant_id = :tenant_id");
                $stmtCheck->execute(['nama' => $catName, 'tenant_id' => $tenantId]);
                $existingId = $stmtCheck->fetchColumn();

                if (!$existingId) {
                    $stmtIns = $pdo->prepare("INSERT INTO sistem.kategori_pengumuman (id, tenant_id, nama_kategori, created_at, updated_at) VALUES (gen_random_uuid(), :tenant_id, :nama, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
                    $stmtIns->execute(['tenant_id' => $tenantId, 'nama' => $catName]);
                }
            }
        }

        // 3. Update existing announcements of school tenants to reference their own tenant category instead of global category
        foreach ($schoolTenants as $t) {
            $tenantId = $t['id'];
            $stmtP = $pdo->prepare("SELECT id, kategori_id FROM sistem.pengumuman WHERE tenant_id = :tenant_id");
            $stmtP->execute(['tenant_id' => $tenantId]);
            $pengumumanList = $stmtP->fetchAll(PDO::FETCH_ASSOC);

            foreach ($pengumumanList as $p) {
                if ($p['kategori_id']) {
                    // Check the category name of this old kategori_id
                    $stmtCatName = $pdo->prepare("SELECT nama_kategori FROM sistem.kategori_pengumuman WHERE id = :id");
                    $stmtCatName->execute(['id' => $p['kategori_id']]);
                    $catName = $stmtCatName->fetchColumn();

                    if ($catName) {
                        // Find matching category belonging to this tenant
                        $stmtMatch = $pdo->prepare("SELECT id FROM sistem.kategori_pengumuman WHERE nama_kategori = :nama AND tenant_id = :tenant_id");
                        $stmtMatch->execute(['nama' => $catName, 'tenant_id' => $tenantId]);
                        $newCatId = $stmtMatch->fetchColumn();

                        if ($newCatId && $newCatId !== $p['kategori_id']) {
                            $stmtUpd = $pdo->prepare("UPDATE sistem.pengumuman SET kategori_id = :new_id WHERE id = :id");
                            $stmtUpd->execute(['new_id' => $newCatId, 'id' => $p['id']]);
                        }
                    }
                }
            }
        }

        echo "- Tenant-specific kategori_pengumuman successfully provisioned and synchronized.\n";
    },
    'down' => function (PDO $pdo): void {
        // Rollback: Keep standard
    }
];
