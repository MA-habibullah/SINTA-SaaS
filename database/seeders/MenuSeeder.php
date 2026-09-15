<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds: Seeding Hierarchy Menu Berbasis Tupoksi Divisi Sekolah & Multi-Context SaaS
     */
    public function run(): void
    {
        // 1. Bersihkan seluruh data menu lama dan relasi role access untuk di-rebuild secara bersih & konsisten
        DB::table('core.role_menu_access')->delete();
        DB::table('core.tenant_menu_access')->delete();
        DB::table('core.menus')->delete();

        // 2. Definisi Struktur Menu Berdasarkan 3 Kelompok Context SINTA
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
            // KELOMPOK B: OPERASIONAL SEKOLAH (BERBASIS TUPOKSI DIVISI SEKOLAH)
            // ==========================================
            // 1. UTAMA (Dashboard, Pengumuman, Agenda)
            [
                'id' => '20000000-0000-0000-0000-000000000001',
                'name' => 'Dashboard & Informasi',
                'url' => '#',
                'icon' => 'bi bi-grid-1x2-fill',
                'urutan' => 10,
                'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'guru', 'wali_kelas', 'guru_bk', 'bk', 'operator_sekolah', 'staf_tu', 'keuangan', 'staf_keuangan', 'perpustakaan', 'sarpras', 'kesiswaan', 'kurikulum', 'humas', 'pembina_ekskul', 'karyawan', 'admin'],
                'children' => [
                    ['id' => '21000000-0000-0000-0000-000000000001', 'name' => 'Dashboard Sekolah', 'url' => '/admin/dashboard', 'icon' => 'bi bi-speedometer2', 'urutan' => 1, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'guru', 'wali_kelas', 'guru_bk', 'bk', 'operator_sekolah', 'staf_tu', 'keuangan', 'staf_keuangan', 'perpustakaan', 'sarpras', 'kesiswaan', 'kurikulum', 'humas', 'pembina_ekskul', 'karyawan', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000002', 'name' => 'Pengumuman Sekolah', 'url' => '/informasi/pengumuman', 'icon' => 'bi bi-megaphone', 'urutan' => 2, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'guru', 'wali_kelas', 'guru_bk', 'bk', 'operator_sekolah', 'staf_tu', 'keuangan', 'staf_keuangan', 'perpustakaan', 'sarpras', 'kesiswaan', 'kurikulum', 'humas', 'pembina_ekskul', 'karyawan', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000003', 'name' => 'Agenda & Timeline', 'url' => '/informasi/agenda', 'icon' => 'bi bi-calendar-event', 'urutan' => 3, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'guru', 'wali_kelas', 'guru_bk', 'bk', 'operator_sekolah', 'staf_tu', 'keuangan', 'staf_keuangan', 'perpustakaan', 'sarpras', 'kesiswaan', 'kurikulum', 'humas', 'pembina_ekskul', 'karyawan', 'admin']],
                ]
            ],

            // 2. TATA USAHA & ADMINISTRASI (TU)
            // Tupoksi: PPDB, Buku Induk Siswa, Data GTK & Kepegawaian, Persuratan, Arsip AeroScan, Identitas Sekolah
            [
                'id' => '20000000-0000-0000-0000-000000000002',
                'name' => 'Tata Usaha & Administrasi',
                'url' => '#',
                'icon' => 'bi bi-folder2-open',
                'urutan' => 11,
                'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'staf_tu', 'operator_sekolah', 'admin'],
                'children' => [
                    ['id' => '21000000-0000-0000-0000-000000000004', 'name' => 'Calon Siswa PPDB', 'url' => '/ppdb/calon-siswa', 'icon' => 'bi bi-person-plus', 'urutan' => 1, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'staf_tu', 'operator_sekolah', 'kesiswaan', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000005', 'name' => 'Verifikasi PPDB', 'url' => '/ppdb/verifikasi', 'icon' => 'bi bi-patch-check', 'urutan' => 2, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'staf_tu', 'operator_sekolah', 'kesiswaan', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000006', 'name' => 'Buku Induk Siswa', 'url' => '/buku-induk', 'icon' => 'bi bi-journal-text', 'urutan' => 3, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'staf_tu', 'operator_sekolah', 'guru', 'wali_kelas', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000007', 'name' => 'Data GTK & Kepegawaian', 'url' => '/kepegawaian', 'icon' => 'bi bi-person-workspace', 'urutan' => 4, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'staf_tu', 'operator_sekolah', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000008', 'name' => 'Surat Masuk', 'url' => '/persuratan/surat-masuk', 'icon' => 'bi bi-inbox', 'urutan' => 5, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'staf_tu', 'operator_sekolah', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000009', 'name' => 'Surat Keluar', 'url' => '/persuratan/surat-keluar', 'icon' => 'bi bi-send', 'urutan' => 6, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'staf_tu', 'operator_sekolah', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000010', 'name' => 'Pemindai Dokumen (AeroScan)', 'url' => '/utilitas/pemindai-dokumen', 'icon' => 'bi bi-scanner', 'urutan' => 7, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'staf_tu', 'operator_sekolah', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000011', 'name' => 'Profil & Identitas Sekolah', 'url' => '/sekolah/identitas', 'icon' => 'bi bi-building', 'urutan' => 8, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'staf_tu', 'operator_sekolah', 'admin']],
                ]
            ],

            // 3. AKADEMIK & KURIKULUM
            // Tupoksi: Master Data Akademik, Jadwal Pelajaran Matrix, Presensi & Jurnal KBM, Rapor & Penilaian
            [
                'id' => '20000000-0000-0000-0000-000000000003',
                'name' => 'Akademik & Kurikulum',
                'url' => '#',
                'icon' => 'bi bi-mortarboard',
                'urutan' => 12,
                'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'guru', 'wali_kelas', 'kurikulum', 'operator_sekolah', 'admin'],
                'children' => [
                    ['id' => '21000000-0000-0000-0000-000000000012', 'name' => 'Master Data Akademik', 'url' => '/master-data', 'icon' => 'bi bi-diagram-3', 'urutan' => 1, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'kurikulum', 'operator_sekolah', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000013', 'name' => 'Jadwal Pelajaran', 'url' => '/akademik/jadwal', 'icon' => 'bi bi-calendar3', 'urutan' => 2, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'guru', 'wali_kelas', 'kurikulum', 'operator_sekolah', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000014', 'name' => 'Presensi & Kehadiran', 'url' => '/absensi', 'icon' => 'bi bi-calendar-check', 'urutan' => 3, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'guru', 'wali_kelas', 'kurikulum', 'staf_tu', 'kesiswaan', 'operator_sekolah', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000015', 'name' => 'Rapor & Penilaian', 'url' => '/akademik/rapor', 'icon' => 'bi bi-file-earmark-spreadsheet', 'urutan' => 4, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'guru', 'wali_kelas', 'kurikulum', 'admin']],
                ]
            ],

            // 4. BIMBINGAN KONSELING (BK) & KARIR
            // Tupoksi: Layanan Konseling, Kedisiplinan Siswa, PDSS Peluang PTN, Tracer Study Alumni
            [
                'id' => '20000000-0000-0000-0000-000000000004',
                'name' => 'Bimbingan Konseling & Karir',
                'url' => '#',
                'icon' => 'bi bi-shield-heart',
                'urutan' => 13,
                'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'guru_bk', 'bk', 'wali_kelas', 'admin'],
                'children' => [
                    ['id' => '21000000-0000-0000-0000-000000000016', 'name' => 'Layanan Konseling Siswa', 'url' => '/bk/layanan', 'icon' => 'bi bi-chat-heart', 'urutan' => 1, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'guru_bk', 'bk', 'wali_kelas', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000017', 'name' => 'Kedisiplinan & Pelanggaran', 'url' => '/bk/kedisiplinan', 'icon' => 'bi bi-shield-slash', 'urutan' => 2, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'guru_bk', 'bk', 'wali_kelas', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000018', 'name' => 'PDSS & Peluang PTN', 'url' => '/akademik/pdss', 'icon' => 'bi bi-graph-up-arrow', 'urutan' => 3, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'guru_bk', 'bk', 'kurikulum', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000019', 'name' => 'Tracer Study Alumni', 'url' => '/alumni/tracer-study', 'icon' => 'bi bi-mortarboard', 'urutan' => 4, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'guru_bk', 'bk', 'kurikulum', 'kesiswaan', 'admin']],
                ]
            ],

            // 5. KESISWAAN & PEMBINAAN
            // Tupoksi: Ekstrakurikuler, Organisasi Siswa & Prestasi
            [
                'id' => '20000000-0000-0000-0000-000000000005',
                'name' => 'Kesiswaan & Pembinaan',
                'url' => '#',
                'icon' => 'bi bi-trophy',
                'urutan' => 14,
                'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'kesiswaan', 'pembina_ekskul', 'guru', 'wali_kelas', 'operator_sekolah', 'admin'],
                'children' => [
                    ['id' => '21000000-0000-0000-0000-000000000020', 'name' => 'Ekstrakurikuler & Prestasi', 'url' => '/kesiswaan/ekskul', 'icon' => 'bi bi-award', 'urutan' => 1, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'kesiswaan', 'pembina_ekskul', 'guru', 'wali_kelas', 'operator_sekolah', 'admin']],
                ]
            ],

            // 6. KEUANGAN & BENDAHARA
            // Tupoksi: Billing SPP, Kasir POS, Pos Tarif, Tagihan, Laporan, Audit
            [
                'id' => '20000000-0000-0000-0000-000000000006',
                'name' => 'Keuangan & Billing SPP',
                'url' => '#',
                'icon' => 'bi bi-cash-stack',
                'urutan' => 15,
                'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'keuangan', 'staf_keuangan', 'admin'],
                'children' => [
                    ['id' => '21000000-0000-0000-0000-000000000021', 'name' => 'Dashboard Keuangan', 'url' => '/keuangan/dashboard', 'icon' => 'bi bi-pie-chart', 'urutan' => 1, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'keuangan', 'staf_keuangan', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000022', 'name' => 'Kasir & Pembayaran SPP', 'url' => '/keuangan/kasir', 'icon' => 'bi bi-calculator', 'urutan' => 2, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'keuangan', 'staf_keuangan', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000023', 'name' => 'Tagihan & Billing Siswa', 'url' => '/keuangan/tagihan', 'icon' => 'bi bi-receipt-cutoff', 'urutan' => 3, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'keuangan', 'staf_keuangan', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000024', 'name' => 'Pos Tarif & Master Biaya', 'url' => '/keuangan/master', 'icon' => 'bi bi-tags', 'urutan' => 4, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'keuangan', 'staf_keuangan', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000025', 'name' => 'Laporan Keuangan', 'url' => '/keuangan/laporan', 'icon' => 'bi bi-file-earmark-bar-graph', 'urutan' => 5, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'keuangan', 'staf_keuangan', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000026', 'name' => 'Audit Log Transaksi', 'url' => '/keuangan/audit-log', 'icon' => 'bi bi-shield-check', 'urutan' => 6, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'keuangan', 'staf_keuangan', 'admin']],
                ]
            ],

            // 7. SARANA, PRASARANA & PERPUSTAKAAN
            // Tupoksi: Aset Inventaris Ruangan, Perpustakaan INLISLite (Katalog, Sirkulasi, OPAC)
            [
                'id' => '20000000-0000-0000-0000-000000000007',
                'name' => 'Sarana, Prasarana & Perpustakaan',
                'url' => '#',
                'icon' => 'bi bi-box-seam',
                'urutan' => 16,
                'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'sarpras', 'perpustakaan', 'guru', 'operator_sekolah', 'admin'],
                'children' => [
                    ['id' => '21000000-0000-0000-0000-000000000027', 'name' => 'Sarana & Prasarana (Sarpras)', 'url' => '/sarpras', 'icon' => 'bi bi-buildings', 'urutan' => 1, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'sarpras', 'operator_sekolah', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000028', 'name' => 'Katalog Buku (INLISLite)', 'url' => '/perpustakaan/katalog', 'icon' => 'bi bi-journal-album', 'urutan' => 2, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'perpustakaan', 'guru', 'operator_sekolah', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000029', 'name' => 'Sirkulasi Peminjaman', 'url' => '/perpustakaan/sirkulasi', 'icon' => 'bi bi-arrow-left-right', 'urutan' => 3, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'perpustakaan', 'operator_sekolah', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000030', 'name' => 'Anggota Perpustakaan', 'url' => '/perpustakaan/anggota', 'icon' => 'bi bi-person-badge', 'urutan' => 4, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'perpustakaan', 'operator_sekolah', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000031', 'name' => 'OPAC Perpustakaan', 'url' => '/perpustakaan/opac', 'icon' => 'bi bi-search', 'urutan' => 5, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'perpustakaan', 'guru', 'operator_sekolah', 'admin']],
                ]
            ],

            // 8. SUPERVISI KEPALA SEKOLAH & MANAJEMEN SISTEM
            // Tupoksi: Pembinaan Guru, Survei Kinerja Guru, Manajemen User & Hak Akses
            [
                'id' => '20000000-0000-0000-0000-000000000008',
                'name' => 'Supervisi & Manajemen Sistem',
                'url' => '#',
                'icon' => 'bi bi-gear-wide-connected',
                'urutan' => 17,
                'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'staf_tu', 'operator_sekolah', 'admin'],
                'children' => [
                    ['id' => '21000000-0000-0000-0000-000000000032', 'name' => 'Pembinaan Guru (Kepsek)', 'url' => '/kepala-sekolah/pembinaan', 'icon' => 'bi bi-person-lines-fill', 'urutan' => 1, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000033', 'name' => 'Survei Kinerja Guru', 'url' => '/kepala-sekolah/survei-guru', 'icon' => 'bi bi-clipboard2-pulse', 'urutan' => 2, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'guru', 'wali_kelas', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000034', 'name' => 'Manajemen Pengguna', 'url' => '/pengguna', 'icon' => 'bi bi-people', 'urutan' => 3, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'operator_sekolah', 'staf_tu', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000035', 'name' => 'Konfigurasi Hak Akses', 'url' => '/konfigurasi/akses', 'icon' => 'bi bi-shield-lock', 'urutan' => 4, 'roles' => ['super_admin', 'admin_sekolah', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000036', 'name' => 'Pusat Bantuan', 'url' => '/bantuan', 'icon' => 'bi bi-question-circle', 'urutan' => 5, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'operator_sekolah', 'staf_tu', 'admin']],
                    ['id' => '21000000-0000-0000-0000-000000000037', 'name' => 'Langganan & Billing SaaS', 'url' => '/sekolah/billing', 'icon' => 'bi bi-credit-card-2-front', 'urutan' => 6, 'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'keuangan', 'staf_tu', 'admin']],
                ]
            ],

            // 9. KHUSUS SMK / VOKASI (JIKA AKTIF)
            [
                'id' => '20000000-0000-0000-0000-000000000009',
                'name' => 'Vokasi & Kemitraan SMK',
                'url' => '/smk',
                'icon' => 'bi bi-wrench-adjustable',
                'urutan' => 18,
                'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah', 'guru', 'operator_sekolah', 'kurikulum', 'kesiswaan', 'admin'],
                'children' => []
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

        $totalMenus = DB::table('core.menus')->count();
        $totalAccess = DB::table('core.role_menu_access')->count();
        $this->command?->info("✔ MenuSeeder selesai: {$totalMenus} menus terdaftar & {$totalAccess} akses role dipetakan.");
    }
}
