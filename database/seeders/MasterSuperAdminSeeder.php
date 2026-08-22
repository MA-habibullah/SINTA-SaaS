<?php
require_once __DIR__ . '/../../app/Core/Env.php';
\App\Core\Env::load(dirname(dirname(__DIR__)));
require_once __DIR__ . '/../../app/Config/Database.php';

use App\Config\Database;

try {
    $db = Database::getConnection();
    $db->beginTransaction();

    echo "Memulai eksekusi MasterSuperAdminSeeder...\n";

    // 1. Bersihkan tabel-tabel konfigurasi master (CASCADE akan menghapus baris terkait di tabel relasional)
    echo "1. Membersihkan tabel-tabel master...\n";
    $db->exec("TRUNCATE TABLE core.users CASCADE");
    $db->exec("TRUNCATE TABLE core.roles CASCADE");
    $db->exec("TRUNCATE TABLE core.tenants CASCADE");
    $db->exec("TRUNCATE TABLE core.menus CASCADE");

    // 2. Buat Global Tenant (ID: e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12)
    echo "2. Membuat Global Tenant...\n";
    $tenantId = 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12';
    $stmtTenant = $db->prepare("INSERT INTO core.tenants (id, nama_sekolah, npsn, subdomain, status) VALUES (?, 'Pusat Kendali SaaS (Global)', 'PLATFORM', 'admin', 'active')");
    $stmtTenant->execute([$tenantId]);

    // 3. Buat Role Super Admin Platform (UUID v4 Acak Kriptografis)
    echo "3. Membuat Role Super Admin Platform...\n";
    $roleId = 'a1f87c2b-9e43-4b6e-8d91-3c5e7b2a9d01';
    $stmtRole = $db->prepare("INSERT INTO core.roles (id, nama_role, deskripsi) VALUES (?, 'super_admin', 'Administrator tertinggi untuk manajemen platform SaaS')");
    $stmtRole->execute([$roleId]);

    // 4. Buat User Super Admin (UUID v4 Acak Kriptografis)
    echo "4. Membuat User Super Admin...\n";
    $userId = 'b2e98d3c-0f54-4c7f-9e02-4d6f8c3b0e12';
    $password = password_hash('superadmin123', PASSWORD_BCRYPT);
    $stmtUser = $db->prepare("INSERT INTO core.users (id, tenant_id, role_id, username, email, password_hash, nama_lengkap, is_active) VALUES (?, ?, ?, 'superadmin', 'superadmin@sinta.com', ?, 'Administrator Platform', true)");
    $stmtUser->execute([$userId, $tenantId, $roleId, $password]);

    // 5. Mendaftarkan 44 Menu secara terstruktur dengan URL Dinamis
    echo "5. Mendaftarkan struktur menu hierarkis (URL dinamis)...\n";
    $menus = [
        ['Dashboard', '/dashboard', 'bi bi-grid-fill', null],
        ['PPDB / Penerimaan Siswa Baru', '#', 'bi bi-clipboard-check', null],
        ['Verifikasi Pendaftaran', '/ppdb/verifikasi', 'bi bi-check2-square', 'PPDB / Penerimaan Siswa Baru'],
        ['Kelola Calon Siswa', '/ppdb/calon-siswa', 'bi bi-person-plus', 'PPDB / Penerimaan Siswa Baru'],
        ['Riwayat Jalur PPDB', '/ppdb/riwayat', 'bi bi-clock-history', 'PPDB / Penerimaan Siswa Baru'],
        ['Data Pokok / Core Dapodik', '#', 'bi bi-folder2-open', null],
        ['Pengguna', '/pengguna', 'bi bi-people', 'Data Pokok / Core Dapodik'],
        ['Master Data', '/master-data', 'bi bi-diagram-3', 'Data Pokok / Core Dapodik'],
        ['Buku Induk', '/buku-induk', 'bi bi-book-half', 'Data Pokok / Core Dapodik'],
        ['Sistem & Utilitas', '#', 'bi bi-shield-lock-fill', null],
        ['Identitas Sekolah', '/sekolah/identitas', 'bi bi-info-circle', 'Sistem & Utilitas'],
        ['Manajemen User & Hak Akses', '/konfigurasi/akses', 'bi bi-shield-lock-fill', 'Sistem & Utilitas'],
        ['Monitoring Sesi Aktif', '/utilitas/sesi-aktif', 'bi bi-clock', 'Sistem & Utilitas'],
        ['Antrean Sistem & Background Jobs', '/utilitas/antrean', 'bi bi-cpu', 'Sistem & Utilitas'],
        ['Akses Fitur Sekolah', '/super-admin/tenant-menus', 'bi bi-building-lock', 'Sistem & Utilitas'],
        ['Kelola Sekolah', '/super-admin/tenants', 'bi bi-building-gear', 'Sistem & Utilitas'],
        ['Log Aktivitas', '/utilitas/log-aktivitas', 'bi bi-journal-text', 'Sistem & Utilitas'],
        ['Error Monitor', '/super-admin/error-monitor', 'bi bi-bug-fill', 'Sistem & Utilitas'],
        ['Server Monitor', '/super-admin/server-monitor', 'bi bi-hdd-network-fill', 'Sistem & Utilitas'],
        ['Pemindai Dokumen', '/utility/document-scanner', 'bi bi-camera-fill', 'Sistem & Utilitas'],
        ['BIMBINGAN KONSELING', '#', 'bi bi-heart-pulse-fill', null],
        ['Layanan BK', '/bk/layanan', 'bi bi-person-badge', 'BIMBINGAN KONSELING'],
        ['Kedisiplinan Siswa', '/bk/kedisiplinan', 'bi bi-shield-exclamation', 'BIMBINGAN KONSELING'],
        ['Kesiapan Akademik & PDSS', '/bk/akademik', 'bi bi-journal-check', 'BIMBINGAN KONSELING'],
        ['Alumni & Tracer Study', '/bk/alumni', 'bi bi-mortarboard', 'BIMBINGAN KONSELING'],
        ['Informasi & Kegiatan', '#', 'bi bi-calendar-event', null],
        ['Pengumuman', '/informasi/pengumuman', 'bi bi-megaphone', 'Informasi & Kegiatan'],
        ['Agenda & Timeline', '/informasi/agenda', 'bi bi-kanban', 'Informasi & Kegiatan'],
        ['Kesiswaan', '#', 'bi bi-person-badge', null],
        ['Ekstrakurikuler', '/kesiswaan/ekskul', 'bi bi-dribbble', 'Kesiswaan'],
        ['Perpustakaan', '#', 'bi bi-journal-bookmark-fill', null],
        ['Katalog & Inventori', '/perpustakaan/katalog', 'bi bi-journal-album', 'Perpustakaan'],
        ['Sirkulasi & Layanan', '/perpustakaan/sirkulasi', 'bi bi-arrow-repeat', 'Perpustakaan'],
        ['Administrasi & Keanggotaan', '/perpustakaan/anggota', 'bi bi-people-fill', 'Perpustakaan'],
        ['OPAC (Public)', '/perpustakaan/opac', 'bi bi-search', 'Perpustakaan'],
        ['Riwayat Pinjaman Saya', '/perpustakaan/riwayat-saya', 'bi bi-clock-history', 'Perpustakaan'],
        ['Kurikulum & Akademik', '#', 'bi bi-journal-text', null],
        ['Jadwal Pelajaran', '/akademik/jadwal', 'bi bi-calendar-week', 'Kurikulum & Akademik'],
        ['Mata Pelajaran', '/akademik/mapel', 'bi bi-book', 'Kurikulum & Akademik'],
        ['Kelas & Rombel', '/akademik/kelas', 'bi bi-building', 'Kurikulum & Akademik'],
        ['Penilaian & Rapor', '/akademik/rapor', 'bi bi-file-earmark-text', 'Kurikulum & Akademik'],
        ['Keuangan', '#', 'bi bi-wallet2', null],
        ['Tagihan & Pembayaran', '/keuangan/tagihan', 'bi bi-cash-coin', 'Keuangan'],
        ['Laporan Keuangan', '/keuangan/laporan', 'bi bi-graph-up', 'Keuangan'],
        ['Bantuan', '/bantuan', 'bi bi-question-circle', null],
        ['Pembinaan', '/pembinaan', 'bi bi-person-workspace', null]
    ];

    $stmtInsertMenu = $db->prepare("INSERT INTO core.menus (id, nama_menu, url, icon, parent_id, urutan, is_active) VALUES (?, ?, ?, ?, ?, ?, true)");
    $stmtInsertRoleAccess = $db->prepare("INSERT INTO core.role_menu_access (tenant_id, role_id, menu_id) VALUES (?, ?, ?)");
    $stmtInsertTenantAccess = $db->prepare("INSERT INTO core.tenant_menu_access (tenant_id, menu_id) VALUES (?, ?)");

    // Simpan id dari menu parent untuk merelasikan sub-menu
    $parentIds = [];
    $urutan = 1;

    foreach ($menus as $m) {
        $menuId = bin2hex(random_bytes(16));
        $menuId = substr($menuId, 0, 8) . '-' . substr($menuId, 8, 4) . '-' . substr($menuId, 12, 4) . '-' . substr($menuId, 16, 4) . '-' . substr($menuId, 20);

        $namaMenu = $m[0];
        $url = $m[1];
        $icon = $m[2];
        $parentName = $m[3];

        $parentId = null;
        if ($parentName !== null && isset($parentIds[$parentName])) {
            $parentId = $parentIds[$parentName];
        }

        // Simpan sebagai referensi parent jika ini bisa jadi parent
        $parentIds[$namaMenu] = $menuId;

        $stmtInsertMenu->execute([$menuId, $namaMenu, $url, $icon, $parentId, $urutan]);
        
        // 6. & 7. Mendaftarkan hak akses ke Role Super Admin dan Global Tenant
        $stmtInsertRoleAccess->execute([$tenantId, $roleId, $menuId]);
        $stmtInsertTenantAccess->execute([$tenantId, $menuId]);

        $urutan++;
    }

    $db->commit();
    echo "SELESAI! Master Seeder berhasil mengeksekusi semua tugas dengan aman.\n";
    echo "- Akun Super Admin: superadmin@sinta.com / superadmin123\n";
} catch (\Exception $e) {
    if (isset($db)) $db->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
}
