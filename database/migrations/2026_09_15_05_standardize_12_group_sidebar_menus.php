<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Standarisasi Menu Induk & Submenu 12 Grup Utama di core.menus
        $groups = [
            [
                'name'     => 'Dashboard & Kendali',
                'icon'     => 'bi bi-grid-1x2',
                'urutan'   => 1,
                'children' => [
                    ['name' => 'Dashboard Utama', 'url' => '/dashboard', 'icon' => 'bi bi-speedometer2', 'urutan' => 1],
                    ['name' => 'Identitas Sekolah', 'url' => '/sekolah/identitas', 'icon' => 'bi bi-building', 'urutan' => 2],
                    ['name' => 'Pengumuman', 'url' => '/informasi/pengumuman', 'icon' => 'bi bi-megaphone', 'urutan' => 3],
                    ['name' => 'Agenda & Timeline', 'url' => '/informasi/agenda', 'icon' => 'bi bi-calendar-event', 'urutan' => 4],
                ],
            ],
            [
                'name'     => 'Buku Induk & Kesiswaan',
                'icon'     => 'bi bi-person-lines-fill',
                'urutan'   => 2,
                'children' => [
                    ['name' => 'Buku Induk Siswa', 'url' => '/buku-induk', 'icon' => 'bi bi-book', 'urutan' => 1],
                    ['name' => 'Calon Siswa PPDB', 'url' => '/ppdb/calon-siswa', 'icon' => 'bi bi-person-plus', 'urutan' => 2],
                    ['name' => 'Ekstrakurikuler', 'url' => '/kesiswaan/ekskul', 'icon' => 'bi bi-trophy', 'urutan' => 3],
                    ['name' => 'Kedisiplinan (BK)', 'url' => '/bk/kedisiplinan', 'icon' => 'bi bi-shield-exclamation', 'urutan' => 4],
                    ['name' => 'Layanan Konseling', 'url' => '/bk/layanan', 'icon' => 'bi bi-chat-heart', 'urutan' => 5],
                ],
            ],
            [
                'name'     => 'Akademik & Kurikulum',
                'icon'     => 'bi bi-mortarboard',
                'urutan'   => 3,
                'children' => [
                    ['name' => 'Master Kelembagaan', 'url' => '/master-data', 'icon' => 'bi bi-diagram-3', 'urutan' => 1],
                    ['name' => 'Jadwal Pelajaran', 'url' => '/akademik/jadwal', 'icon' => 'bi bi-calendar-week', 'urutan' => 2],
                    ['name' => 'Pencetakan Rapor & Ledger', 'url' => '/akademik/rapor', 'icon' => 'bi bi-printer', 'urutan' => 3],
                    ['name' => 'PDSS & Jalur Masuk PTN', 'url' => '/bk/akademik', 'icon' => 'bi bi-mortarboard', 'urutan' => 4],
                ],
            ],
            [
                'name'     => 'Presensi & Kehadiran',
                'icon'     => 'bi bi-fingerprint',
                'urutan'   => 4,
                'children' => [
                    ['name' => 'Presensi Siswa & GTK', 'url' => '/absensi', 'icon' => 'bi bi-qr-code-scan', 'urutan' => 1],
                ],
            ],
            [
                'name'     => 'Kepegawaian & GTK',
                'icon'     => 'bi bi-person-badge',
                'urutan'   => 5,
                'children' => [
                    ['name' => 'Buku Induk GTK & Recruitment', 'url' => '/kepegawaian', 'icon' => 'bi bi-people', 'urutan' => 1],
                    ['name' => 'Pembinaan & Supervisi', 'url' => '/kepala-sekolah/pembinaan', 'icon' => 'bi bi-award', 'urutan' => 2],
                    ['name' => 'Survei Kinerja Guru', 'url' => '/kepala-sekolah/survei-guru', 'icon' => 'bi bi-star', 'urutan' => 3],
                ],
            ],
            [
                'name'     => 'Keuangan & Billing SPP',
                'icon'     => 'bi bi-credit-card',
                'urutan'   => 6,
                'children' => [
                    ['name' => 'Kasir POS Pembayaran', 'url' => '/keuangan/kasir', 'icon' => 'bi bi-receipt', 'urutan' => 1],
                    ['name' => 'Tagihan & Billing SPP', 'url' => '/keuangan/tagihan', 'icon' => 'bi bi-wallet2', 'urutan' => 2],
                    ['name' => 'Buku Kas & Rekening', 'url' => '/keuangan/master', 'icon' => 'bi bi-cash-stack', 'urutan' => 3],
                ],
            ],
            [
                'name'     => 'Perpustakaan INLISLite',
                'icon'     => 'bi bi-journal-bookmark',
                'urutan'   => 7,
                'children' => [
                    ['name' => 'Katalog Bibliografi', 'url' => '/perpustakaan/katalog', 'icon' => 'bi bi-collection', 'urutan' => 1],
                    ['name' => 'Sirkulasi & Denda Kilat', 'url' => '/perpustakaan/sirkulasi', 'icon' => 'bi bi-arrow-left-right', 'urutan' => 2],
                    ['name' => 'Kiosk Buku Tamu & Loker', 'url' => '/perpustakaan/kiosk', 'icon' => 'bi bi-display', 'urutan' => 3],
                    ['name' => 'Anggota Perpustakaan', 'url' => '/perpustakaan/anggota', 'icon' => 'bi bi-card-heading', 'urutan' => 4],
                    ['name' => 'OPAC Publik', 'url' => '/perpustakaan/opac', 'icon' => 'bi bi-search', 'urutan' => 5],
                ],
            ],
            [
                'name'     => 'Sarana & Prasarana',
                'icon'     => 'bi bi-box-seam',
                'urutan'   => 8,
                'children' => [
                    ['name' => 'Inventaris Aset, BHP & KIR', 'url' => '/sarpras', 'icon' => 'bi bi-boxes', 'urutan' => 1],
                ],
            ],
            [
                'name'     => 'Persuratan & E-Disposisi',
                'icon'     => 'bi bi-envelope-paper',
                'urutan'   => 9,
                'children' => [
                    ['name' => 'Agenda Surat Masuk', 'url' => '/persuratan/surat-masuk', 'icon' => 'bi bi-inbox', 'urutan' => 1],
                    ['name' => 'Registrasi Surat Keluar', 'url' => '/persuratan/surat-keluar', 'icon' => 'bi bi-send', 'urutan' => 2],
                ],
            ],
            [
                'name'     => 'Alumni & Vokasi SMK',
                'icon'     => 'bi bi-gear-wide-connected',
                'urutan'   => 10,
                'children' => [
                    ['name' => 'Tracer Study Alumni', 'url' => '/bk/alumni', 'icon' => 'bi bi-person-check', 'urutan' => 1],
                    ['name' => 'Mitra DUDI & PKL / UKK', 'url' => '/smk', 'icon' => 'bi bi-building-gear', 'urutan' => 2],
                ],
            ],
            [
                'name'     => 'CMS & Portal Publik',
                'icon'     => 'bi bi-globe2',
                'urutan'   => 11,
                'children' => [
                    ['name' => 'CMS Web & Banner Promosi', 'url' => '/super-admin/cms-promosi', 'icon' => 'bi bi-browser-chrome', 'urutan' => 1],
                ],
            ],
            [
                'name'     => 'Sistem & Super Admin',
                'icon'     => 'bi bi-shield-lock',
                'urutan'   => 12,
                'children' => [
                    ['name' => 'Manajemen Pengguna', 'url' => '/pengguna', 'icon' => 'bi bi-people-fill', 'urutan' => 1],
                    ['name' => 'Hak Akses RBAC', 'url' => '/konfigurasi/akses', 'icon' => 'bi bi-key', 'urutan' => 2],
                    ['name' => 'Sesi Aktif Real-Time', 'url' => '/utilitas/sesi-aktif', 'icon' => 'bi bi-laptop', 'urutan' => 3],
                    ['name' => 'Antrean Queue Worker', 'url' => '/utilitas/antrean', 'icon' => 'bi bi-cpu', 'urutan' => 4],
                    ['name' => 'Kelola Sekolah & Paket', 'url' => '/super-admin/tenants', 'icon' => 'bi bi-buildings', 'urutan' => 5],
                    ['name' => 'Pusat Bantuan & Tiket', 'url' => '/bantuan', 'icon' => 'bi bi-question-circle', 'urutan' => 6],
                ],
            ],
        ];

        foreach ($groups as $grp) {
            $parent = DB::table('core.menus')->where('nama_menu', $grp['name'])->first();
            $parentId = $parent ? $parent->id : (string) Str::uuid();

            if (!$parent) {
                DB::table('core.menus')->insert([
                    'id'        => $parentId,
                    'nama_menu' => $grp['name'],
                    'url'       => '#',
                    'icon'      => $grp['icon'],
                    'parent_id' => null,
                    'urutan'    => $grp['urutan'],
                    'is_active' => true,
                ]);
            } else {
                DB::table('core.menus')->where('id', $parentId)->update([
                    'icon'      => $grp['icon'],
                    'urutan'    => $grp['urutan'],
                    'is_active' => true,
                ]);
            }

            foreach ($grp['children'] as $child) {
                $sub = DB::table('core.menus')->where('url', $child['url'])->first();
                if (!$sub) {
                    DB::table('core.menus')->insert([
                        'id'        => (string) Str::uuid(),
                        'nama_menu' => $child['name'],
                        'url'       => $child['url'],
                        'icon'      => $child['icon'],
                        'parent_id' => $parentId,
                        'urutan'    => $child['urutan'],
                        'is_active' => true,
                    ]);
                } else {
                    DB::table('core.menus')->where('id', $sub->id)->update([
                        'nama_menu' => $child['name'],
                        'icon'      => $child['icon'],
                        'parent_id' => $parentId,
                        'urutan'    => $child['urutan'],
                        'is_active' => true,
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safe down
    }
};
