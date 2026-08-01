<?php

return [
    'up' => function (PDO $pdo): void {
        $globalTenantId = '00000000-0000-0000-0000-000000000000';

        // 1. Bersihkan data matriks default lama yang terlalu terbuka (semua role centang semua menu)
        $pdo->exec("DELETE FROM core.role_menu_access WHERE tenant_id = '{$globalTenantId}'::uuid OR tenant_id IS NULL");

        // 2. Load pemetaan role dari core.roles
        $stRoles = $pdo->query("SELECT id, nama_role FROM core.roles");
        $rolesMap = [];
        foreach ($stRoles->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $rolesMap[$r['nama_role']] = $r['id'];
        }

        // 3. Load seluruh menu dari core.menus
        $stMenus = $pdo->query("SELECT id, url, nama_menu, parent_id FROM core.menus");
        $menus = $stMenus->fetchAll(PDO::FETCH_ASSOC);

        // Helper untuk mencocokkan ID menu berdasarkan URL / nama menu
        $getMenuIds = function (array $keywords) use ($menus): array {
            $ids = [];
            foreach ($menus as $m) {
                foreach ($keywords as $kw) {
                    if ($kw === '*' || strcasecmp($m['url'], $kw) === 0 || stripos($m['url'], $kw) !== false || stripos($m['nama_menu'], $kw) !== false) {
                        $ids[] = $m['id'];
                        break;
                    }
                }
            }
            return array_unique($ids);
        };

        // 4. Definisikan aturan hak akses ketat (Strict RBAC Rules) sesuai fungsi masing-masing peran
        $rolePermissions = [
            // SUPER ADMIN: Mengakses 100% seluruh menu platform
            'super_admin' => ['*'],

            // ADMIN SEKOLAH & OPERATOR SEKOLAH: Manajemen sekolah & DAPODIK
            'admin_sekolah' => [
                '/dashboard', '/ppdb', '/pengguna', '/master-data', '/buku-induk',
                '/sekolah/identitas', '/konfigurasi/akses', '/utilitas/sesi-aktif',
                '/utilitas/antrean', '/utilitas/log-aktivitas', '/utility/document-scanner',
                '/akademik', '/keuangan', '/informasi', '/kesiswaan', '/perpustakaan', '/bk', '/bantuan'
            ],
            'operator_sekolah' => [
                '/dashboard', '/ppdb', '/pengguna', '/master-data', '/buku-induk',
                '/sekolah/identitas', '/konfigurasi/akses', '/utilitas/sesi-aktif',
                '/utilitas/antrean', '/utilitas/log-aktivitas', '/utility/document-scanner',
                '/akademik', '/keuangan', '/informasi', '/kesiswaan', '/perpustakaan', '/bk', '/bantuan'
            ],

            // KEPALA SEKOLAH: Eksekutif, monitoring, supervisi, dan laporan
            'kepala_sekolah' => [
                '/dashboard', '/master-data', '/buku-induk', '/sekolah/identitas',
                '/pembinaan', '/akademik/jadwal', '/akademik/mapel', '/akademik/kelas',
                '/akademik/rapor', '/keuangan/dashboard', '/keuangan/laporan',
                '/informasi', '/kesiswaan', '/perpustakaan/katalog', '/perpustakaan/opac',
                '/bk', '/bantuan'
            ],

            // GURU: Fasilitas mengajar (Jadwal, Mapel, Kelas, Input Nilai/Rapor, Induk Siswa, Pengumuman)
            'guru' => [
                '/dashboard', '/buku-induk', '/akademik/jadwal', '/akademik/mapel',
                '/akademik/kelas', '/akademik/rapor', '/informasi/pengumuman',
                '/informasi/agenda', '/kesiswaan/ekskul', '/perpustakaan/opac',
                '/perpustakaan/riwayat-saya', '/bk/layanan', '/bantuan'
            ],

            // SISWA: Portal Siswa (Dashboard, Jadwal, Nilai/Rapor Saya, Tagihan Saya, Perpus Saya, Pengumuman)
            'siswa' => [
                '/dashboard', '/keuangan/tagihan-saya', '/informasi/pengumuman',
                '/informasi/agenda', '/perpustakaan/opac', '/perpustakaan/riwayat-saya', '/bantuan'
            ],

            // KARYAWAN: Staf Administrasi Umum
            'karyawan' => [
                '/dashboard', '/informasi/pengumuman', '/informasi/agenda',
                '/utility/document-scanner', '/perpustakaan/opac', '/bantuan'
            ],

            // KESISWAAN: Pengelola Kesiswaan, PPDB, Ekstrakurikuler
            'kesiswaan' => [
                '/dashboard', '/ppdb/verifikasi', '/ppdb/calon-siswa', '/ppdb/riwayat',
                '/buku-induk', '/kesiswaan/ekskul', '/bk/layanan', '/informasi/pengumuman',
                '/informasi/agenda', '/bantuan'
            ],

            // BK (Bimbingan Konseling): Layanan Konseling, Kedisiplinan, PDSS, Tracer Study
            'bk' => [
                '/dashboard', '/buku-induk', '/bk/layanan', '/bk/akademik', '/bk/alumni',
                '/informasi/pengumuman', '/informasi/agenda', '/bantuan'
            ]
        ];

        $stmtIns = $pdo->prepare("INSERT INTO core.role_menu_access (tenant_id, role_id, menu_id) VALUES (?, ?, ?)");

        $totalInserted = 0;
        foreach ($rolePermissions as $roleName => $keywords) {
            if (!isset($rolesMap[$roleName])) continue;
            $roleId = $rolesMap[$roleName];

            $matchedMenuIds = $getMenuIds($keywords);
            foreach ($matchedMenuIds as $menuId) {
                $stmtIns->execute([$globalTenantId, $roleId, $menuId]);
                $totalInserted++;
            }
        }

        echo "- Successfully seeded strict, professional RBAC matrix ({$totalInserted} active permissions across roles).\n";
    },
    'down' => function (PDO $pdo): void {
        // Rollback
    },
];
