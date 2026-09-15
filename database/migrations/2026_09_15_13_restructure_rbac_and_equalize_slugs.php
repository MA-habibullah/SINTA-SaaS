<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations: Restrukturisasi Total Sidebar, Database Menus & RBAC Equalizing Slugs
     */
    public function up(): void
    {
        // 1. Bersihkan seluruh data menu lama dan relasi role access untuk di-rebuild secara bersih & konsisten
        DB::table('core.role_menu_access')->delete();
        DB::table('core.tenant_menu_access')->delete();
        DB::table('core.menus')->delete();

        // 2. Definisi Struktur Menu Bersih Berdasarkan 3 Kelompok Context & Matriks RBAC
        $menuTree = [
            // ==========================================
            // KELOMPOK A: SUPER ADMIN (Platform Owner Only)
            // ==========================================
            [
                'id' => '10000000-0000-0000-0000-000000000001',
                'name' => 'Dashboard Platform',
                'url' => '/super-admin/dashboard',
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
                'name' => 'CMS Landing & Promosi',
                'url' => '/super-admin/cms-promosi',
                'icon' => 'bi bi-layout-text-window-reverse',
                'urutan' => 3,
                'roles' => ['super_admin'],
                'children' => []
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
            // KELOMPOK B: OPERASIONAL SEKOLAH (Admin, TU, Kepala Sekolah, Guru, Staf)
            // ==========================================
            [
                'id' => '20000000-0000-0000-0000-000000000001',
                'name' => 'Dashboard & Informasi',
                'url' => '#',
                'icon' => 'bi bi-grid-1x2-fill',
                'urutan' => 10,
                'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'guru', 'wali_kelas', 'guru_bk', 'bk', 'operator_sekolah', 'staf_tu', 'keuangan', 'staf_keuangan', 'perpustakaan', 'sarpras', 'kesiswaan', 'kurikulum', 'humas', 'pembina_ekskul', 'karyawan', 'admin'],
                'children' => [
                    ['id' => '21000000-0000-0000-0000-000000000001', 'name' => 'Dashboard Sekolah', 'url' => '/admin/dashboard', 'icon' => 'bi bi-speedometer2', 'urutan' => 1, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'guru', 'wali_kelas', 'guru_bk', 'bk', 'operator_sekolah', 'staf_tu', 'keuangan', 'staf_keuangan', 'perpustakaan', 'sarpras', 'kesiswaan', 'kurikulum', 'humas', 'pembina_ekskul', 'karyawan', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000002', 'name' => 'Identitas Sekolah', 'url' => '/sekolah/identitas', 'icon' => 'bi bi-building', 'urutan' => 2, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'operator_sekolah', 'staf_tu', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000003', 'name' => 'Pengumuman', 'url' => '/informasi/pengumuman', 'icon' => 'bi bi-megaphone', 'urutan' => 3, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'guru', 'wali_kelas', 'guru_bk', 'bk', 'operator_sekolah', 'staf_tu', 'keuangan', 'staf_keuangan', 'perpustakaan', 'sarpras', 'kesiswaan', 'kurikulum', 'humas', 'pembina_ekskul', 'karyawan', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000004', 'name' => 'Agenda & Timeline', 'url' => '/informasi/agenda', 'icon' => 'bi bi-calendar-event', 'urutan' => 4, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'guru', 'wali_kelas', 'guru_bk', 'bk', 'operator_sekolah', 'staf_tu', 'keuangan', 'staf_keuangan', 'perpustakaan', 'sarpras', 'kesiswaan', 'kurikulum', 'humas', 'pembina_ekskul', 'karyawan', 'admin']],
                ]
            ],
            [
                'id' => '20000000-0000-0000-0000-000000000002',
                'name' => 'Penerimaan Siswa Baru (PPDB)',
                'url' => '#',
                'icon' => 'bi bi-person-plus-fill',
                'urutan' => 11,
                'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'operator_sekolah', 'staf_tu', 'kesiswaan', 'admin'],
                'children' => [
                    ['id' => '21000000-0000-0000-0000-000000000005', 'name' => 'Calon Siswa PPDB', 'url' => '/ppdb/calon-siswa', 'icon' => 'bi bi-person-plus', 'urutan' => 1, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'operator_sekolah', 'staf_tu', 'kesiswaan', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000006', 'name' => 'Verifikasi PPDB', 'url' => '/ppdb/verifikasi', 'icon' => 'bi bi-patch-check', 'urutan' => 2, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'operator_sekolah', 'staf_tu', 'kesiswaan', 'admin']],
                ]
            ],
            [
                'id' => '20000000-0000-0000-0000-000000000003',
                'name' => 'Buku Induk & Kesiswaan',
                'url' => '#',
                'icon' => 'bi bi-person-vcard',
                'urutan' => 12,
                'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'guru', 'wali_kelas', 'operator_sekolah', 'staf_tu', 'kesiswaan', 'admin'],
                'children' => [
                    ['id' => '21000000-0000-0000-0000-000000000007', 'name' => 'Buku Induk Siswa', 'url' => '/buku-induk', 'icon' => 'bi bi-journal-text', 'urutan' => 1, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'guru', 'wali_kelas', 'operator_sekolah', 'staf_tu', 'kesiswaan', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000008', 'name' => 'Ekstrakurikuler', 'url' => '/kesiswaan/ekskul', 'icon' => 'bi bi-trophy', 'urutan' => 2, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'guru', 'wali_kelas', 'kesiswaan', 'pembina_ekskul', 'operator_sekolah', 'admin']],
                ]
            ],
            [
                'id' => '20000000-0000-0000-0000-000000000004',
                'name' => 'Bimbingan Konseling (BK)',
                'url' => '#',
                'icon' => 'bi bi-shield-heart',
                'urutan' => 13,
                'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'guru_bk', 'bk', 'wali_kelas', 'admin'],
                'children' => [
                    ['id' => '21000000-0000-0000-0000-000000000009', 'name' => 'Kedisiplinan & Pelanggaran', 'url' => '/bk/kedisiplinan', 'icon' => 'bi bi-shield-slash', 'urutan' => 1, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'guru_bk', 'bk', 'wali_kelas', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000010', 'name' => 'Layanan Konseling', 'url' => '/bk/layanan', 'icon' => 'bi bi-chat-heart', 'urutan' => 2, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'guru_bk', 'bk', 'wali_kelas', 'admin']],
                ]
            ],
            [
                'id' => '20000000-0000-0000-0000-000000000005',
                'name' => 'Akademik & Kurikulum',
                'url' => '#',
                'icon' => 'bi bi-mortarboard',
                'urutan' => 14,
                'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'guru', 'wali_kelas', 'kurikulum', 'operator_sekolah', 'admin'],
                'children' => [
                    ['id' => '21000000-0000-0000-0000-000000000011', 'name' => 'Master Data Akademik', 'url' => '/master-data', 'icon' => 'bi bi-diagram-3', 'urutan' => 1, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'kurikulum', 'operator_sekolah', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000012', 'name' => 'Jadwal Pelajaran', 'url' => '/akademik/jadwal', 'icon' => 'bi bi-calendar3', 'urutan' => 2, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'guru', 'wali_kelas', 'kurikulum', 'operator_sekolah', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000013', 'name' => 'Rapor & Penilaian', 'url' => '/akademik/rapor', 'icon' => 'bi bi-file-earmark-spreadsheet', 'urutan' => 3, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'guru', 'wali_kelas', 'kurikulum', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000014', 'name' => 'PDSS & Peluang PTN', 'url' => '/akademik/pdss', 'icon' => 'bi bi-graph-up-arrow', 'urutan' => 4, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'guru_bk', 'bk', 'kurikulum', 'admin']],
                ]
            ],
            [
                'id' => '20000000-0000-0000-0000-000000000006',
                'name' => 'Presensi & Kehadiran',
                'url' => '/absensi',
                'icon' => 'bi bi-calendar-check',
                'urutan' => 15,
                'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'guru', 'wali_kelas', 'operator_sekolah', 'staf_tu', 'kesiswaan', 'kurikulum', 'admin'],
                'children' => []
            ],
            [
                'id' => '20000000-0000-0000-0000-000000000007',
                'name' => 'Kepegawaian & GTK',
                'url' => '#',
                'icon' => 'bi bi-people-fill',
                'urutan' => 16,
                'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'staf_tu', 'operator_sekolah', 'guru', 'wali_kelas', 'admin'],
                'children' => [
                    ['id' => '21000000-0000-0000-0000-000000000015', 'name' => 'Data GTK & Kepegawaian', 'url' => '/kepegawaian', 'icon' => 'bi bi-person-workspace', 'urutan' => 1, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'staf_tu', 'operator_sekolah', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000016', 'name' => 'Pembinaan GTK', 'url' => '/kepala-sekolah/pembinaan', 'icon' => 'bi bi-person-lines-fill', 'urutan' => 2, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000017', 'name' => 'Survei Kinerja Guru', 'url' => '/kepala-sekolah/survei-guru', 'icon' => 'bi bi-clipboard2-pulse', 'urutan' => 3, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'guru', 'wali_kelas', 'admin']],
                ]
            ],
            [
                'id' => '20000000-0000-0000-0000-000000000008',
                'name' => 'Keuangan & Billing SPP',
                'url' => '#',
                'icon' => 'bi bi-cash-stack',
                'urutan' => 17,
                'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'keuangan', 'staf_keuangan', 'admin'],
                'children' => [
                    ['id' => '21000000-0000-0000-0000-000000000018', 'name' => 'Dashboard Keuangan', 'url' => '/keuangan/dashboard', 'icon' => 'bi bi-pie-chart', 'urutan' => 1, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'keuangan', 'staf_keuangan', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000019', 'name' => 'Kasir & Pembayaran', 'url' => '/keuangan/kasir', 'icon' => 'bi bi-calculator', 'urutan' => 2, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'keuangan', 'staf_keuangan', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000020', 'name' => 'Tagihan & Billing', 'url' => '/keuangan/tagihan', 'icon' => 'bi bi-receipt-cutoff', 'urutan' => 3, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'keuangan', 'staf_keuangan', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000021', 'name' => 'Pos Tarif & Master', 'url' => '/keuangan/master', 'icon' => 'bi bi-tags', 'urutan' => 4, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'keuangan', 'staf_keuangan', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000022', 'name' => 'Laporan Keuangan', 'url' => '/keuangan/laporan', 'icon' => 'bi bi-file-earmark-bar-graph', 'urutan' => 5, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'keuangan', 'staf_keuangan', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000023', 'name' => 'Audit Log Keuangan', 'url' => '/keuangan/audit-log', 'icon' => 'bi bi-shield-check', 'urutan' => 6, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'keuangan', 'staf_keuangan', 'admin']],
                ]
            ],
            [
                'id' => '20000000-0000-0000-0000-000000000009',
                'name' => 'Perpustakaan INLISLite',
                'url' => '#',
                'icon' => 'bi bi-book-half',
                'urutan' => 18,
                'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'perpustakaan', 'guru', 'operator_sekolah', 'admin'],
                'children' => [
                    ['id' => '21000000-0000-0000-0000-000000000024', 'name' => 'Katalog Buku', 'url' => '/perpustakaan/katalog', 'icon' => 'bi bi-journal-album', 'urutan' => 1, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'perpustakaan', 'guru', 'operator_sekolah', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000025', 'name' => 'Sirkulasi Peminjaman', 'url' => '/perpustakaan/sirkulasi', 'icon' => 'bi bi-arrow-left-right', 'urutan' => 2, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'perpustakaan', 'operator_sekolah', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000026', 'name' => 'Anggota Perpustakaan', 'url' => '/perpustakaan/anggota', 'icon' => 'bi bi-person-badge', 'urutan' => 3, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'perpustakaan', 'operator_sekolah', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000027', 'name' => 'OPAC Perpustakaan', 'url' => '/perpustakaan/opac', 'icon' => 'bi bi-search', 'urutan' => 4, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'perpustakaan', 'guru', 'operator_sekolah', 'admin']],
                ]
            ],
            [
                'id' => '20000000-0000-0000-0000-000000000010',
                'name' => 'Sarana & Prasarana',
                'url' => '/sarpras',
                'icon' => 'bi bi-box-seam',
                'urutan' => 19,
                'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'sarpras', 'operator_sekolah', 'admin'],
                'children' => []
            ],
            [
                'id' => '20000000-0000-0000-0000-000000000011',
                'name' => 'Persuratan & Tata Usaha',
                'url' => '#',
                'icon' => 'bi bi-envelope-paper',
                'urutan' => 20,
                'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'staf_tu', 'operator_sekolah', 'admin'],
                'children' => [
                    ['id' => '21000000-0000-0000-0000-000000000028', 'name' => 'Surat Masuk', 'url' => '/persuratan/surat-masuk', 'icon' => 'bi bi-inbox', 'urutan' => 1, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'staf_tu', 'operator_sekolah', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000029', 'name' => 'Surat Keluar', 'url' => '/persuratan/surat-keluar', 'icon' => 'bi bi-send', 'urutan' => 2, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'staf_tu', 'operator_sekolah', 'admin']],
                ]
            ],
            [
                'id' => '20000000-0000-0000-0000-000000000012',
                'name' => 'Alumni & Vokasi SMK',
                'url' => '#',
                'icon' => 'bi bi-briefcase',
                'urutan' => 21,
                'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'guru', 'operator_sekolah', 'kurikulum', 'kesiswaan', 'admin'],
                'children' => [
                    ['id' => '21000000-0000-0000-0000-000000000030', 'name' => 'Tracer Study Alumni', 'url' => '/alumni/tracer-study', 'icon' => 'bi bi-mortarboard', 'urutan' => 1, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'guru', 'operator_sekolah', 'kurikulum', 'kesiswaan', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000031', 'name' => 'Vokasi & BKK SMK', 'url' => '/smk', 'icon' => 'bi bi-wrench-adjustable', 'urutan' => 2, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'guru', 'operator_sekolah', 'kurikulum', 'kesiswaan', 'admin']],
                ]
            ],
            [
                'id' => '20000000-0000-0000-0000-000000000013',
                'name' => 'Sistem & Manajemen Sekolah',
                'url' => '#',
                'icon' => 'bi bi-gear-wide-connected',
                'urutan' => 22,
                'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'operator_sekolah', 'staf_tu', 'admin'],
                'children' => [
                    ['id' => '21000000-0000-0000-0000-000000000032', 'name' => 'Manajemen Pengguna', 'url' => '/pengguna', 'icon' => 'bi bi-people', 'urutan' => 1, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'operator_sekolah', 'staf_tu', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000033', 'name' => 'Konfigurasi Hak Akses', 'url' => '/konfigurasi/akses', 'icon' => 'bi bi-shield-lock', 'urutan' => 2, 'roles' => ['super_admin', 'admin_sekolah', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000034', 'name' => 'Pusat Bantuan', 'url' => '/bantuan', 'icon' => 'bi bi-question-circle', 'urutan' => 3, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'operator_sekolah', 'staf_tu', 'admin']],
                ]
            ],

            // ==========================================
            // KELOMPOK C: PORTAL MANDIRI (Siswa & Orang Tua)
            // ==========================================
            [
                'id' => '30000000-0000-0000-0000-000000000001',
                'name' => 'Dashboard Siswa',
                'url' => '/siswa/dashboard',
                'icon' => 'bi bi-speedometer2',
                'urutan' => 1,
                'roles' => ['siswa', 'orang_tua'],
                'children' => []
            ],
            [
                'id' => '30000000-0000-0000-0000-000000000002',
                'name' => 'Presensi Mandiri GPS',
                'url' => '/absensi/mandiri',
                'icon' => 'bi bi-geo-alt-fill',
                'urutan' => 2,
                'roles' => ['siswa', 'orang_tua'],
                'children' => []
            ],
            [
                'id' => '30000000-0000-0000-0000-000000000003',
                'name' => 'Tagihan & SPP Saya',
                'url' => '/keuangan/tagihan-saya',
                'icon' => 'bi bi-wallet2',
                'urutan' => 3,
                'roles' => ['siswa', 'orang_tua'],
                'children' => []
            ],
            [
                'id' => '30000000-0000-0000-0000-000000000004',
                'name' => 'Rapor & Nilai Saya',
                'url' => '/akademik/rapor-saya',
                'icon' => 'bi bi-award-fill',
                'urutan' => 4,
                'roles' => ['siswa', 'orang_tua'],
                'children' => []
            ],
            [
                'id' => '30000000-0000-0000-0000-000000000005',
                'name' => 'Perpustakaan Saya',
                'url' => '/perpustakaan/riwayat-saya',
                'icon' => 'bi bi-book',
                'urutan' => 5,
                'roles' => ['siswa', 'orang_tua'],
                'children' => []
            ],
            [
                'id' => '30000000-0000-0000-0000-000000000006',
                'name' => 'Konseling Saya',
                'url' => '/bk/konseling-saya',
                'icon' => 'bi bi-chat-heart',
                'urutan' => 6,
                'roles' => ['siswa', 'orang_tua'],
                'children' => []
            ],
        ];

        // 3. Muat pemetaan Role ID dari tabel core.roles dan tenants
        $rolesMap = DB::table('core.roles')->pluck('id', 'nama_role')->toArray();
        $tenantIds = DB::table('core.tenants')->pluck('id')->toArray();
        if (empty($tenantIds)) {
            $tenantIds = ['00000000-0000-0000-0000-000000000000'];
        }

        // 4. Masukkan ke tabel core.menus & relasi role_menu_access
        foreach ($menuTree as $parent) {
            DB::table('core.menus')->insert([
                'id'        => $parent['id'],
                'parent_id' => null,
                'nama_menu' => $parent['name'],
                'url'       => $parent['url'],
                'icon'      => $parent['icon'],
                'urutan'    => $parent['urutan'],
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
            if (!empty($parent['children'])) {
                foreach ($parent['children'] as $child) {
                    DB::table('core.menus')->insert([
                        'id'        => $child['id'],
                        'parent_id' => $parent['id'],
                        'nama_menu' => $child['name'],
                        'url'       => $child['url'],
                        'icon'      => $child['icon'],
                        'urutan'    => $child['urutan'],
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
