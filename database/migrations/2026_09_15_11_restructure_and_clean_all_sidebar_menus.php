<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations: Restrukturisasi Total & Standarisasi Bersih core.menus & role_menu_access
     */
    public function up(): void
    {
        // 1. Bersihkan seluruh data menu lama dan relasi role access untuk di-rebuild secara bersih & konsisten
        DB::table('core.role_menu_access')->delete();
        DB::table('core.tenant_menu_access')->delete();
        DB::table('core.menus')->delete();

        // 2. Definisi Struktur Menu Bersih (Superadmin, Operasional Sekolah, Portal Siswa)
        $menuTree = [
            // ==========================================
            // CONTEXT 1: SUPER ADMIN (Platform Owner)
            // ==========================================
            [
                'id' => '10000000-0000-0000-0000-000000000001',
                'name' => 'Dashboard Platform',
                'url' => '/dashboard',
                'icon' => 'bi bi-speedometer2',
                'urutan' => 1,
                'roles' => ['super_admin'],
                'children' => []
            ],
            [
                'id' => '10000000-0000-0000-0000-000000000002',
                'name' => 'Kelola Sekolah & Tenant',
                'url' => '#',
                'icon' => 'bi bi-buildings',
                'urutan' => 2,
                'roles' => ['super_admin'],
                'children' => [
                    ['id' => '11000000-0000-0000-0000-000000000001', 'name' => 'Daftar Sekolah & Paket', 'url' => '/super-admin/tenants', 'icon' => 'bi bi-building-gear', 'urutan' => 1, 'roles' => ['super_admin']],
                    ['id' => '11000000-0000-0000-0000-000000000002', 'name' => 'Akses Fitur Sekolah', 'url' => '/super-admin/tenant-menus', 'icon' => 'bi bi-toggles', 'urutan' => 2, 'roles' => ['super_admin']],
                ]
            ],
            [
                'id' => '10000000-0000-0000-0000-000000000003',
                'name' => 'CMS & Portal Publik',
                'url' => '#',
                'icon' => 'bi bi-globe2',
                'urutan' => 3,
                'roles' => ['super_admin'],
                'children' => [
                    ['id' => '11000000-0000-0000-0000-000000000003', 'name' => 'CMS Landing & Promosi', 'url' => '/super-admin/cms-promosi', 'icon' => 'bi bi-layout-text-window-reverse', 'urutan' => 1, 'roles' => ['super_admin']],
                ]
            ],
            [
                'id' => '10000000-0000-0000-0000-000000000004',
                'name' => 'Server & Health Monitor',
                'url' => '#',
                'icon' => 'bi bi-hdd-network',
                'urutan' => 4,
                'roles' => ['super_admin'],
                'children' => [
                    ['id' => '11000000-0000-0000-0000-000000000004', 'name' => 'Server Monitor', 'url' => '/super-admin/server-monitor', 'icon' => 'bi bi-cpu', 'urutan' => 1, 'roles' => ['super_admin']],
                    ['id' => '11000000-0000-0000-0000-000000000005', 'name' => 'Error & Exception Log', 'url' => '/super-admin/error-monitor', 'icon' => 'bi bi-shield-exclamation', 'urutan' => 2, 'roles' => ['super_admin']],
                    ['id' => '11000000-0000-0000-0000-000000000006', 'name' => 'Antrean Queue Worker', 'url' => '/utilitas/antrean', 'icon' => 'bi bi-arrow-repeat', 'urutan' => 3, 'roles' => ['super_admin']],
                    ['id' => '11000000-0000-0000-0000-000000000007', 'name' => 'Sesi Aktif Real-Time', 'url' => '/utilitas/sesi-aktif', 'icon' => 'bi bi-activity', 'urutan' => 4, 'roles' => ['super_admin']],
                    ['id' => '11000000-0000-0000-0000-000000000008', 'name' => 'Audit Trail & Log Global', 'url' => '/utilitas/log-aktivitas', 'icon' => 'bi bi-journal-text', 'urutan' => 5, 'roles' => ['super_admin']],
                ]
            ],

            // ==========================================
            // CONTEXT 2: OPERASIONAL SEKOLAH (Super Admin & Staff Sekolah)
            // ==========================================
            [
                'id' => '20000000-0000-0000-0000-000000000001',
                'name' => 'Dashboard & Informasi',
                'url' => '#',
                'icon' => 'bi bi-grid-1x2-fill',
                'urutan' => 10,
                'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'guru', 'wali_kelas', 'guru_bk', 'bk', 'operator_sekolah', 'staf_tu', 'keuangan', 'staf_keuangan', 'perpustakaan', 'sarpras', 'kesiswaan', 'kurikulum', 'humas', 'pembina_ekskul', 'karyawan', 'admin'],
                'children' => [
                    ['id' => '21000000-0000-0000-0000-000000000001', 'name' => 'Dashboard Sekolah', 'url' => '/dashboard', 'icon' => 'bi bi-speedometer2', 'urutan' => 1, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'guru', 'wali_kelas', 'guru_bk', 'bk', 'operator_sekolah', 'staf_tu', 'keuangan', 'staf_keuangan', 'perpustakaan', 'sarpras', 'kesiswaan', 'kurikulum', 'humas', 'pembina_ekskul', 'karyawan', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000002', 'name' => 'Identitas Sekolah', 'url' => '/sekolah/identitas', 'icon' => 'bi bi-building', 'urutan' => 2, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'operator_sekolah', 'staf_tu', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000003', 'name' => 'Pengumuman', 'url' => '/informasi/pengumuman', 'icon' => 'bi bi-megaphone', 'urutan' => 3, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'guru', 'wali_kelas', 'guru_bk', 'bk', 'operator_sekolah', 'staf_tu', 'keuangan', 'staf_keuangan', 'perpustakaan', 'sarpras', 'kesiswaan', 'kurikulum', 'humas', 'pembina_ekskul', 'karyawan', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000004', 'name' => 'Agenda & Timeline', 'url' => '/informasi/agenda', 'icon' => 'bi bi-calendar-event', 'urutan' => 4, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'guru', 'wali_kelas', 'guru_bk', 'bk', 'operator_sekolah', 'staf_tu', 'keuangan', 'staf_keuangan', 'perpustakaan', 'sarpras', 'kesiswaan', 'kurikulum', 'humas', 'pembina_ekskul', 'karyawan', 'admin']],
                ]
            ],
            [
                'id' => '20000000-0000-0000-0000-000000000002',
                'name' => 'Buku Induk & Kesiswaan',
                'url' => '#',
                'icon' => 'bi bi-person-vcard',
                'urutan' => 11,
                'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'guru', 'wali_kelas', 'operator_sekolah', 'staf_tu', 'kesiswaan', 'admin'],
                'children' => [
                    ['id' => '21000000-0000-0000-0000-000000000005', 'name' => 'Buku Induk Siswa', 'url' => '/buku-induk', 'icon' => 'bi bi-journal-text', 'urutan' => 1, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'guru', 'wali_kelas', 'operator_sekolah', 'staf_tu', 'kesiswaan', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000006', 'name' => 'Calon Siswa PPDB', 'url' => '/ppdb/calon-siswa', 'icon' => 'bi bi-person-plus', 'urutan' => 2, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'operator_sekolah', 'staf_tu', 'kesiswaan', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000007', 'name' => 'Verifikasi PPDB', 'url' => '/ppdb/verifikasi', 'icon' => 'bi bi-patch-check', 'urutan' => 3, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'operator_sekolah', 'staf_tu', 'kesiswaan', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000008', 'name' => 'Ekstrakurikuler', 'url' => '/kesiswaan/ekskul', 'icon' => 'bi bi-trophy', 'urutan' => 4, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'guru', 'wali_kelas', 'kesiswaan', 'pembina_ekskul', 'operator_sekolah', 'admin']],
                ]
            ],
            [
                'id' => '20000000-0000-0000-0000-000000000003',
                'name' => 'Bimbingan Konseling (BK)',
                'url' => '#',
                'icon' => 'bi bi-shield-heart',
                'urutan' => 12,
                'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'guru', 'wali_kelas', 'guru_bk', 'bk', 'operator_sekolah', 'kesiswaan', 'admin'],
                'children' => [
                    ['id' => '21000000-0000-0000-0000-000000000009', 'name' => 'Kedisiplinan & Poin', 'url' => '/bk/kedisiplinan', 'icon' => 'bi bi-shield-slash', 'urutan' => 1, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'guru', 'wali_kelas', 'guru_bk', 'bk', 'operator_sekolah', 'kesiswaan', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000010', 'name' => 'Layanan Konseling', 'url' => '/bk/layanan', 'icon' => 'bi bi-chat-heart', 'urutan' => 2, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'guru', 'wali_kelas', 'guru_bk', 'bk', 'operator_sekolah', 'admin']],
                ]
            ],
            [
                'id' => '20000000-0000-0000-0000-000000000004',
                'name' => 'Akademik & Kurikulum',
                'url' => '#',
                'icon' => 'bi bi-mortarboard',
                'urutan' => 13,
                'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'guru', 'wali_kelas', 'operator_sekolah', 'staf_tu', 'kurikulum', 'admin'],
                'children' => [
                    ['id' => '21000000-0000-0000-0000-000000000011', 'name' => 'Master Data Kelembagaan', 'url' => '/master-data', 'icon' => 'bi bi-diagram-3', 'urutan' => 1, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'operator_sekolah', 'staf_tu', 'kurikulum', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000012', 'name' => 'Jadwal Pelajaran & KBM', 'url' => '/akademik/jadwal', 'icon' => 'bi bi-calendar3', 'urutan' => 2, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'guru', 'wali_kelas', 'operator_sekolah', 'kurikulum', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000013', 'name' => 'Pencetakan Rapor & Ledger', 'url' => '/akademik/rapor', 'icon' => 'bi bi-award', 'urutan' => 3, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'guru', 'wali_kelas', 'operator_sekolah', 'kurikulum', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000014', 'name' => 'PDSS & Peluang PTN', 'url' => '/bk/akademik', 'icon' => 'bi bi-mortarboard-fill', 'urutan' => 4, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'guru', 'wali_kelas', 'guru_bk', 'bk', 'operator_sekolah', 'kurikulum', 'admin']],
                ]
            ],
            [
                'id' => '20000000-0000-0000-0000-000000000005',
                'name' => 'Presensi & Kehadiran',
                'url' => '#',
                'icon' => 'bi bi-geo-alt',
                'urutan' => 14,
                'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'guru', 'wali_kelas', 'operator_sekolah', 'staf_tu', 'kesiswaan', 'admin'],
                'children' => [
                    ['id' => '21000000-0000-0000-0000-000000000015', 'name' => 'Presensi Siswa & GTK', 'url' => '/absensi', 'icon' => 'bi bi-pin-map-fill', 'urutan' => 1, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'guru', 'wali_kelas', 'operator_sekolah', 'staf_tu', 'kesiswaan', 'admin']],
                ]
            ],
            [
                'id' => '20000000-0000-0000-0000-000000000006',
                'name' => 'Kepegawaian & GTK',
                'url' => '#',
                'icon' => 'bi bi-people',
                'urutan' => 15,
                'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'operator_sekolah', 'staf_tu', 'admin'],
                'children' => [
                    ['id' => '21000000-0000-0000-0000-000000000016', 'name' => 'Buku Induk GTK & Recruitment', 'url' => '/kepegawaian', 'icon' => 'bi bi-person-lines-fill', 'urutan' => 1, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'operator_sekolah', 'staf_tu', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000017', 'name' => 'Pembinaan & Supervisi', 'url' => '/kepala-sekolah/pembinaan', 'icon' => 'bi bi-person-workspace', 'urutan' => 2, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'operator_sekolah', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000018', 'name' => 'Survei Kinerja Guru', 'url' => '/kepala-sekolah/survei-guru', 'icon' => 'bi bi-clipboard-data', 'urutan' => 3, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'operator_sekolah', 'admin']],
                ]
            ],
            [
                'id' => '20000000-0000-0000-0000-000000000007',
                'name' => 'Keuangan & Billing SPP',
                'url' => '#',
                'icon' => 'bi bi-wallet2',
                'urutan' => 16,
                'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'keuangan', 'staf_keuangan', 'operator_sekolah', 'staf_tu', 'admin'],
                'children' => [
                    ['id' => '21000000-0000-0000-0000-000000000019', 'name' => 'Dashboard Keuangan', 'url' => '/keuangan/dashboard', 'icon' => 'bi bi-speedometer2', 'urutan' => 1, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'keuangan', 'staf_keuangan', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000020', 'name' => 'Kasir POS Pembayaran', 'url' => '/keuangan/kasir', 'icon' => 'bi bi-receipt-cutoff', 'urutan' => 2, 'roles' => ['super_admin', 'admin_sekolah', 'keuangan', 'staf_keuangan', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000021', 'name' => 'Tagihan & Billing SPP', 'url' => '/keuangan/tagihan', 'icon' => 'bi bi-credit-card-2-front', 'urutan' => 3, 'roles' => ['super_admin', 'admin_sekolah', 'keuangan', 'staf_keuangan', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000022', 'name' => 'Pos Tarif & Buku Kas', 'url' => '/keuangan/master', 'icon' => 'bi bi-bank', 'urutan' => 4, 'roles' => ['super_admin', 'admin_sekolah', 'keuangan', 'staf_keuangan', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000023', 'name' => 'Laporan Keuangan', 'url' => '/keuangan/laporan', 'icon' => 'bi bi-file-earmark-bar-graph', 'urutan' => 5, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'keuangan', 'staf_keuangan', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000024', 'name' => 'Audit Trail Transaksi', 'url' => '/keuangan/audit-log', 'icon' => 'bi bi-shield-check', 'urutan' => 6, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'keuangan', 'admin']],
                ]
            ],
            [
                'id' => '20000000-0000-0000-0000-000000000008',
                'name' => 'Perpustakaan INLISLite',
                'url' => '#',
                'icon' => 'bi bi-journal-bookmark',
                'urutan' => 17,
                'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'perpustakaan', 'operator_sekolah', 'guru', 'staf_tu', 'admin'],
                'children' => [
                    ['id' => '21000000-0000-0000-0000-000000000025', 'name' => 'Katalog Bibliografi', 'url' => '/perpustakaan/katalog', 'icon' => 'bi bi-journals', 'urutan' => 1, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'perpustakaan', 'operator_sekolah', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000026', 'name' => 'Sirkulasi & Denda', 'url' => '/perpustakaan/sirkulasi', 'icon' => 'bi bi-arrow-left-right', 'urutan' => 2, 'roles' => ['super_admin', 'admin_sekolah', 'perpustakaan', 'operator_sekolah', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000027', 'name' => 'Anjungan Kiosk & Tamu', 'url' => '/perpustakaan/kiosk', 'icon' => 'bi bi-display', 'urutan' => 3, 'roles' => ['super_admin', 'admin_sekolah', 'perpustakaan', 'operator_sekolah', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000028', 'name' => 'Anggota Perpustakaan', 'url' => '/perpustakaan/anggota', 'icon' => 'bi bi-person-badge', 'urutan' => 4, 'roles' => ['super_admin', 'admin_sekolah', 'perpustakaan', 'operator_sekolah', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000029', 'name' => 'OPAC Publik', 'url' => '/perpustakaan/opac', 'icon' => 'bi bi-search', 'urutan' => 5, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'perpustakaan', 'operator_sekolah', 'guru', 'admin']],
                ]
            ],
            [
                'id' => '20000000-0000-0000-0000-000000000009',
                'name' => 'Sarana & Prasarana',
                'url' => '#',
                'icon' => 'bi bi-box-seam',
                'urutan' => 18,
                'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'sarpras', 'operator_sekolah', 'staf_tu', 'admin'],
                'children' => [
                    ['id' => '21000000-0000-0000-0000-000000000030', 'name' => 'Inventaris Aset, BHP & KIR', 'url' => '/sarpras', 'icon' => 'bi bi-building-check', 'urutan' => 1, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'sarpras', 'operator_sekolah', 'staf_tu', 'admin']],
                ]
            ],
            [
                'id' => '20000000-0000-0000-0000-000000000010',
                'name' => 'Persuratan & Tata Usaha',
                'url' => '#',
                'icon' => 'bi bi-envelope-paper',
                'urutan' => 19,
                'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'staf_tu', 'operator_sekolah', 'admin'],
                'children' => [
                    ['id' => '21000000-0000-0000-0000-000000000031', 'name' => 'Agenda Surat Masuk', 'url' => '/persuratan/surat-masuk', 'icon' => 'bi bi-inbox', 'urutan' => 1, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'staf_tu', 'operator_sekolah', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000032', 'name' => 'Registrasi Surat Keluar', 'url' => '/persuratan/surat-keluar', 'icon' => 'bi bi-send', 'urutan' => 2, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'staf_tu', 'operator_sekolah', 'admin']],
                ]
            ],
            [
                'id' => '20000000-0000-0000-0000-000000000011',
                'name' => 'Alumni & Vokasi SMK',
                'url' => '#',
                'icon' => 'bi bi-compass',
                'urutan' => 20,
                'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'guru', 'wali_kelas', 'guru_bk', 'bk', 'operator_sekolah', 'humas', 'admin'],
                'children' => [
                    ['id' => '21000000-0000-0000-0000-000000000033', 'name' => 'Tracer Study Alumni', 'url' => '/bk/alumni', 'icon' => 'bi bi-mortarboard', 'urutan' => 1, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'guru_bk', 'bk', 'operator_sekolah', 'humas', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000034', 'name' => 'Mitra DUDI & PKL / UKK', 'url' => '/smk', 'icon' => 'bi bi-tools', 'urutan' => 2, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'guru', 'operator_sekolah', 'humas', 'admin']],
                ]
            ],
            [
                'id' => '20000000-0000-0000-0000-000000000012',
                'name' => 'Sistem & Manajemen Sekolah',
                'url' => '#',
                'icon' => 'bi bi-gear-wide-connected',
                'urutan' => 21,
                'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'operator_sekolah', 'admin'],
                'children' => [
                    ['id' => '21000000-0000-0000-0000-000000000035', 'name' => 'Manajemen Pengguna', 'url' => '/pengguna', 'icon' => 'bi bi-people-fill', 'urutan' => 1, 'roles' => ['super_admin', 'admin_sekolah', 'operator_sekolah', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000036', 'name' => 'Hak Akses RBAC', 'url' => '/konfigurasi/akses', 'icon' => 'bi bi-key-fill', 'urutan' => 2, 'roles' => ['super_admin', 'admin_sekolah', 'operator_sekolah', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000037', 'name' => 'Pusat Bantuan & Tiket', 'url' => '/bantuan', 'icon' => 'bi bi-question-circle-fill', 'urutan' => 3, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'operator_sekolah', 'guru', 'staf_tu', 'keuangan', 'perpustakaan', 'sarpras', 'admin']],
                ]
            ],

            // ==========================================
            // CONTEXT 3: PORTAL SISWA & ORANG TUA (Self-Service)
            // ==========================================
            [
                'id' => '30000000-0000-0000-0000-000000000001',
                'name' => 'Dashboard Siswa',
                'url' => '/dashboard',
                'icon' => 'bi bi-house-door-fill',
                'urutan' => 1,
                'roles' => ['siswa', 'orang_tua'],
                'children' => []
            ],
            [
                'id' => '30000000-0000-0000-0000-000000000002',
                'name' => 'Tagihan & SPP Saya',
                'url' => '/keuangan/tagihan-saya',
                'icon' => 'bi bi-wallet2',
                'urutan' => 2,
                'roles' => ['siswa', 'orang_tua'],
                'children' => []
            ],
            [
                'id' => '30000000-0000-0000-0000-000000000003',
                'name' => 'Presensi Mandiri GPS',
                'url' => '/absensi',
                'icon' => 'bi bi-geo-alt-fill',
                'urutan' => 3,
                'roles' => ['siswa', 'orang_tua'],
                'children' => []
            ],
            [
                'id' => '30000000-0000-0000-0000-000000000004',
                'name' => 'Rapor & Nilai Akademik',
                'url' => '/akademik/rapor',
                'icon' => 'bi bi-award-fill',
                'urutan' => 4,
                'roles' => ['siswa', 'orang_tua'],
                'children' => []
            ],
            [
                'id' => '30000000-0000-0000-0000-000000000005',
                'name' => 'Jadwal Pelajaran Kelas',
                'url' => '/akademik/jadwal',
                'icon' => 'bi bi-calendar3',
                'urutan' => 5,
                'roles' => ['siswa', 'orang_tua'],
                'children' => []
            ],
            [
                'id' => '30000000-0000-0000-0000-000000000006',
                'name' => 'Perpustakaan Saya',
                'url' => '/perpustakaan/riwayat-saya',
                'icon' => 'bi bi-book-half',
                'urutan' => 6,
                'roles' => ['siswa', 'orang_tua'],
                'children' => []
            ],
            [
                'id' => '30000000-0000-0000-0000-000000000007',
                'name' => 'Konseling & Catatan BK',
                'url' => '/bk/layanan',
                'icon' => 'bi bi-chat-dots-fill',
                'urutan' => 7,
                'roles' => ['siswa', 'orang_tua'],
                'children' => []
            ],
            [
                'id' => '30000000-0000-0000-0000-000000000008',
                'name' => 'Pengumuman Sekolah',
                'url' => '/informasi/pengumuman',
                'icon' => 'bi bi-megaphone-fill',
                'urutan' => 8,
                'roles' => ['siswa', 'orang_tua'],
                'children' => []
            ],
            [
                'id' => '30000000-0000-0000-0000-000000000009',
                'name' => 'Pusat Bantuan',
                'url' => '/bantuan',
                'icon' => 'bi bi-question-circle',
                'urutan' => 9,
                'roles' => ['siswa', 'orang_tua'],
                'children' => []
            ],
        ];

        // 3. Ambil peta role id & tenant list
        $rolesMap = DB::table('core.roles')->pluck('id', 'nama_role')->toArray();
        $tenantIds = DB::table('core.tenants')->pluck('id')->toArray();

        // 4. Masukkan ke tabel core.menus & relasi role_menu_access
        foreach ($menuTree as $parent) {
            DB::table('core.menus')->insert([
                'id' => $parent['id'],
                'parent_id' => null,
                'nama_menu' => $parent['name'],
                'url' => $parent['url'],
                'icon' => $parent['icon'],
                'urutan' => $parent['urutan'],
                'is_active' => true,
            ]);

            // Assign role access parent ke seluruh tenant
            foreach ($parent['roles'] as $roleName) {
                if (isset($rolesMap[$roleName])) {
                    $rId = $rolesMap[$roleName];
                    foreach ($tenantIds as $tId) {
                        DB::table('core.role_menu_access')->insert([
                            'tenant_id' => $tId,
                            'role_id'   => $rId,
                            'menu_id'   => $parent['id'],
                        ]);
                    }
                }
            }

            // Insert Children
            foreach ($parent['children'] as $child) {
                DB::table('core.menus')->insert([
                    'id' => $child['id'],
                    'parent_id' => $parent['id'],
                    'nama_menu' => $child['name'],
                    'url' => $child['url'],
                    'icon' => $child['icon'],
                    'urutan' => $child['urutan'],
                    'is_active' => true,
                ]);

                foreach ($child['roles'] as $roleName) {
                    if (isset($rolesMap[$roleName])) {
                        $rId = $rolesMap[$roleName];
                        foreach ($tenantIds as $tId) {
                            DB::table('core.role_menu_access')->insert([
                                'tenant_id' => $tId,
                                'role_id'   => $rId,
                                'menu_id'   => $child['id'],
                            ]);
                        }
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('core.role_menu_access')->delete();
        DB::table('core.menus')->delete();
    }
};
