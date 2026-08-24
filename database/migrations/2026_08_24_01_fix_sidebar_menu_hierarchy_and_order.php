<?php

return [
    'up' => function (PDO $pdo): void {
        echo "- Menstandarisasi urutan dan relasi hierarki core.menus...\n";

        // 1. Standarisasi urutan Menu Induk (Root Menus)
        $rootOrder = [
            'Dashboard'               => 1,
            'PPDB'                    => 2,
            'Data Pokok'              => 3,
            'Informasi & Kegiatan'    => 4,
            'Kesiswaan'               => 5,
            'BIMBINGAN KONSELING'     => 6,
            'Alumni & Tracer Study'   => 7,
            'Perpustakaan'            => 8,
            'Persuratan & Tata Usaha' => 9,
            'Keuangan'                => 10,
            'Sistem & Utilitas'       => 11,
            'Pembinaan & Supervisi'   => 12,
            'Pusat Bantuan'           => 13,
        ];

        foreach ($rootOrder as $namaKeyword => $urutan) {
            $stmt = $pdo->prepare("UPDATE core.menus SET urutan = :urutan WHERE nama_menu ILIKE :kw AND parent_id IS NULL");
            $stmt->execute(['urutan' => $urutan, 'kw' => "%{$namaKeyword}%"]);
        }

        // 2. Kaitkan submenu Sistem & Utilitas yang lepas ke menu induk Sistem & Utilitas
        $stmtParentSistem = $pdo->query("SELECT id FROM core.menus WHERE nama_menu ILIKE '%Sistem & Utilitas%' AND parent_id IS NULL LIMIT 1");
        $parentSistemId = $stmtParentSistem->fetchColumn();

        if ($parentSistemId) {
            $sistemSubmenus = [
                ['/sekolah/identitas', 'Identitas Sekolah', 1],
                ['/pengguna', 'Manajemen User & Hak Akses', 2],
                ['/utilitas/sesi-aktif', 'Monitoring Sesi Aktif', 3],
                ['/utilitas/antrean', 'Antrean Sistem & Background Jobs', 4],
                ['/sistem/fitur-sekolah', 'Akses Fitur Sekolah', 5],
                ['/sistem/sekolah', 'Kelola Sekolah', 6],
                ['/utilitas/log-aktivitas', 'Log Aktivitas', 7],
                ['/utilitas/error-monitor', 'Error Monitor', 8],
                ['/utilitas/server-monitor', 'Server Monitor', 9],
                ['/utility/document-scanner', 'Pemindai Dokumen', 10],
            ];

            foreach ($sistemSubmenus as $sub) {
                $stmt = $pdo->prepare("UPDATE core.menus SET parent_id = :parent_id, urutan = :urutan WHERE url = :url OR nama_menu ILIKE :nama");
                $stmt->execute([
                    'parent_id' => $parentSistemId,
                    'urutan'    => $sub[2],
                    'url'       => $sub[0],
                    'nama'      => "%{$sub[1]}%"
                ]);
            }
        }

        // 3. Kaitkan submenu Perpustakaan yang lepas ke menu induk Perpustakaan
        $stmtParentPerpus = $pdo->query("SELECT id FROM core.menus WHERE nama_menu ILIKE '%Perpustakaan%' AND parent_id IS NULL LIMIT 1");
        $parentPerpusId = $stmtParentPerpus->fetchColumn();

        if ($parentPerpusId) {
            $perpusSubmenus = [
                ['/perpustakaan/katalog', 'Katalog & Inventori', 1],
                ['/perpustakaan/sirkulasi', 'Sirkulasi & Layanan', 2],
                ['/perpustakaan/anggota', 'Administrasi & Keanggotaan', 3],
                ['/perpustakaan/opac', 'OPAC Publik', 4],
                ['/perpustakaan/pengaturan', 'Pengaturan Perpustakaan', 5],
            ];

            foreach ($perpusSubmenus as $sub) {
                $stmt = $pdo->prepare("UPDATE core.menus SET parent_id = :parent_id, urutan = :urutan WHERE url = :url OR nama_menu ILIKE :nama");
                $stmt->execute([
                    'parent_id' => $parentPerpusId,
                    'urutan'    => $sub[2],
                    'url'       => $sub[0],
                    'nama'      => "%{$sub[1]}%"
                ]);
            }
        }

        echo "- Standarisasi hierarki core.menus selesai.\n";
    },

    'down' => function (PDO $pdo): void {
        // Rollback tidak diperlukan karena hanya penyesuaian metadata urutan dan relasi parent
    }
];
