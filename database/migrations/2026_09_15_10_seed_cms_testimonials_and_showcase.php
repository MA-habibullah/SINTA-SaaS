<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        // 1. Seed complete 16 module features in cms.cms_promotions
        $modules = [
            [
                'title'       => 'Buku Induk & Data Siswa',
                'subtitle'    => 'Pencatatan NISN, biodata terpadu, riwayat mutasi, beasiswa, dan cetak lembar buku induk resmi Kemendikbud.',
                'badge_text'  => 'Akademik',
                'icon_class'  => 'bi bi-journal-text',
                'category'    => 'akademik',
                'order_num'   => 1,
            ],
            [
                'title'       => 'Rapor Kurikulum Merdeka & K13',
                'subtitle'    => 'Otomasi kalkulasi nilai CP/TP, deskripsi capaian dinamis, cetak massal rapor PDF multi-page & ledger Excel.',
                'badge_text'  => 'Kurikulum',
                'icon_class'  => 'bi bi-award-fill',
                'category'    => 'akademik',
                'order_num'   => 2,
            ],
            [
                'title'       => 'Jadwal Pelajaran Anti-Bentrok',
                'subtitle'    => 'Matriks visual jadwal pelajaran kelas & guru, radar deteksi konflik ruangan, dan ekspor/impor Excel multi-sheet.',
                'badge_text'  => 'Akademik',
                'icon_class'  => 'bi bi-calendar3',
                'category'    => 'akademik',
                'order_num'   => 3,
            ],
            [
                'title'       => 'Keuangan & Kasir SPP POS',
                'subtitle'    => 'Billing tagihan massal, kasir kilat nota thermal 58/80mm, integrasi Midtrans QRIS/VA & notifikasi WA.',
                'badge_text'  => 'Keuangan',
                'icon_class'  => 'bi bi-wallet2',
                'category'    => 'keuangan',
                'order_num'   => 4,
            ],
            [
                'title'       => 'Presensi Geofencing GPS & Anti-Fraud',
                'subtitle'    => 'Presensi mandiri siswa & GTK radius akurat, deteksi Fake GPS / mock provider, dan kompresi surat izin < 500 KB.',
                'badge_text'  => 'Presensi',
                'icon_class'  => 'bi bi-geo-alt-fill',
                'category'    => 'presensi',
                'order_num'   => 5,
            ],
            [
                'title'       => 'Jurnal Mengajar Guru (KBM)',
                'subtitle'    => 'Auto-fill jadwal mengajar hari ini, input capaian pembelajaran, absensi KBM per jam & upload dokumentasi.',
                'badge_text'  => 'Presensi',
                'icon_class'  => 'bi bi-book-half',
                'category'    => 'presensi',
                'order_num'   => 6,
            ],
            [
                'title'       => 'PPDB Online & Seleksi Masuk',
                'subtitle'    => 'Formulir pendaftaran calon siswa mandiri, verifikasi berkas online, jalur zonasi/afirmasi/prestasi, dan kelulusan.',
                'badge_text'  => 'Kesiswaan',
                'icon_class'  => 'bi bi-person-plus-fill',
                'category'    => 'kesiswaan',
                'order_num'   => 7,
            ],
            [
                'title'       => 'Bimbingan Konseling & Kedisiplinan',
                'subtitle'    => 'Pencatatan sesi konseling pribadi, buku saku poin pelanggaran siswa, dan otomatisasi surat panggilan ortu.',
                'badge_text'  => 'Kesiswaan',
                'icon_class'  => 'bi bi-shield-check',
                'category'    => 'kesiswaan',
                'order_num'   => 8,
            ],
            [
                'title'       => 'Kesiswaan & Ekstrakurikuler',
                'subtitle'    => 'Registrasi anggota ekskul, jurnal kegiatan pembina, absensi kehadiran ekskul, dan konversi predikat nilai.',
                'badge_text'  => 'Kesiswaan',
                'icon_class'  => 'bi bi-trophy-fill',
                'category'    => 'kesiswaan',
                'order_num'   => 9,
            ],
            [
                'title'       => 'Perpustakaan Standar INLISLite',
                'subtitle'    => 'Sirkulasi barcode peminjaman kilat, katalog OPAC MARC21, anjungan kiosk buku tamu touchscreen & kartu anggota.',
                'badge_text'  => 'Sarpras',
                'icon_class'  => 'bi bi-book-fill',
                'category'    => 'sarpras',
                'order_num'   => 10,
            ],
            [
                'title'       => 'Sarana & Prasarana (KIR Aset)',
                'subtitle'    => 'Label barcode QR aset tetap, cetak Kartu Inventaris Ruangan (KIR) Kemendikbud, stok BHP & peminjaman alat/lab.',
                'badge_text'  => 'Sarpras',
                'icon_class'  => 'bi bi-building-fill-check',
                'category'    => 'sarpras',
                'order_num'   => 11,
            ],
            [
                'title'       => 'Kepegawaian & Buku Induk GTK',
                'subtitle'    => 'Data pokok NIP/NUPTK guru, riwayat kepangkatan & KGB, sertifikasi pendidik, dan e-recruitment pegawai baru.',
                'badge_text'  => 'Kepegawaian',
                'icon_class'  => 'bi bi-person-vcard-fill',
                'category'    => 'kepegawaian',
                'order_num'   => 12,
            ],
            [
                'title'       => 'Vokasi SMK & Praktik Kerja (PKL)',
                'subtitle'    => 'Database mitra industri DUDI & MoU, plotting siswa magang, jurnal harian PKL, dan rubrik penilaian UKK LSP.',
                'badge_text'  => 'Kejuruan',
                'icon_class'  => 'bi bi-tools',
                'category'    => 'kepegawaian',
                'order_num'   => 13,
            ],
            [
                'title'       => 'Persuratan & E-Disposisi',
                'subtitle'    => 'Penomoran agenda surat otomatis, arsip surat masuk/keluar PDF terenkripsi, dan alur disposisi pimpinan realtime.',
                'badge_text'  => 'Administrasi',
                'icon_class'  => 'bi bi-envelope-paper-fill',
                'category'    => 'kepegawaian',
                'order_num'   => 14,
            ],
            [
                'title'       => 'PDSS & Analisis Peluang Kampus',
                'subtitle'    => 'Pemeringkatan siswa eligible SNBP, kalkulasi rerata rapor 5 semester, dan riwayat kelulusan alumni di PTN.',
                'badge_text'  => 'Alumni',
                'icon_class'  => 'bi bi-mortarboard-fill',
                'category'    => 'akademik',
                'order_num'   => 15,
            ],
            [
                'title'       => 'CMS Website Portal & Informasi',
                'subtitle'    => 'Publikasi pengumuman sekolah, agenda akademik kalender, galeri kegiatan, dan landing page sekolah multi-tenant.',
                'badge_text'  => 'Portal Publik',
                'icon_class'  => 'bi bi-globe2',
                'category'    => 'kepegawaian',
                'order_num'   => 16,
            ],
        ];

        foreach ($modules as $m) {
            $exists = DB::table('cms.cms_promotions')
                ->where('section_key', 'features')
                ->where('title', $m['title'])
                ->exists();
            if (!$exists) {
                DB::table('cms.cms_promotions')->insert([
                    'id'           => Str::uuid()->toString(),
                    'section_key'  => 'features',
                    'title'        => $m['title'],
                    'subtitle'     => $m['subtitle'],
                    'content'      => null,
                    'content_json' => json_encode(['category' => $m['category']]),
                    'badge_text'   => $m['badge_text'],
                    'image_url'    => null,
                    'icon_class'   => $m['icon_class'],
                    'cta_text'     => 'Eksplorasi Modul',
                    'cta_link'     => '/daftar-sekolah',
                    'is_active'    => true,
                    'order_num'    => $m['order_num'],
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ]);
            }
        }

        // 2. Seed Testimonials
        $testimonials = [
            [
                'id'           => Str::uuid()->toString(),
                'section_key'  => 'testimonials',
                'title'        => 'Drs. H. Ahmad Fauzi, M.Pd.',
                'subtitle'     => 'Kepala SMA Negeri Unggulan',
                'content'      => 'Penerapan SINTA memangkas waktu cetak rapor dari 2 minggu menjadi hanya 1 hari. Pengelolaan SPP dan presensi GPS sangat transparan dan memudahkan koordinasi dengan wali murid.',
                'content_json' => json_encode([
                    'school_name' => 'SMAN 1 Teladan',
                    'rating'      => 5,
                    'avatar'      => 'AF',
                    'badge'       => 'Sekolah Negeri Terakreditasi A',
                    'city'        => 'Surabaya'
                ]),
                'badge_text'   => 'Kepala Sekolah',
                'image_url'    => null,
                'icon_class'   => 'bi bi-quote',
                'cta_text'     => null,
                'cta_link'     => null,
                'is_active'    => true,
                'order_num'    => 1,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'id'           => Str::uuid()->toString(),
                'section_key'  => 'testimonials',
                'title'        => 'Siti Nurhaliza, S.Kom., M.T.',
                'subtitle'     => 'Waka Kurikulum & IT SMK Vokasi',
                'content'      => 'Fitur Jurnal Mengajar dan Modul PKL Mitra Industri sangat luar biasa. Guru-guru merasa sangat terbantu karena presensi KBM dan CP otomatis terhubung ke jadwal harian.',
                'content_json' => json_encode([
                    'school_name' => 'SMK Mitra Industri Vokasi',
                    'rating'      => 5,
                    'avatar'      => 'SN',
                    'badge'       => 'SMK Pusat Keunggulan',
                    'city'        => 'Bandung'
                ]),
                'badge_text'   => 'Waka Kurikulum',
                'image_url'    => null,
                'icon_class'   => 'bi bi-quote',
                'cta_text'     => null,
                'cta_link'     => null,
                'is_active'    => true,
                'order_num'    => 2,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'id'           => Str::uuid()->toString(),
                'section_key'  => 'testimonials',
                'title'        => 'Dra. Hj. Wahyuni Rahayu',
                'subtitle'     => 'Ketua Pengurus Yayasan Pendidikan Islam',
                'content'      => 'Sistem Multi-Tenant SINTA memungkinkan yayasan kami memantau 4 unit sekolah (SD, SMP, SMA, SMK) dalam satu dashboard terpadu. Tagihan SPP Midtrans langsung cair otomatis ke rekening yayasan.',
                'content_json' => json_encode([
                    'school_name' => 'Yayasan Pendidikan Al-Hikmah',
                    'rating'      => 5,
                    'avatar'      => 'WR',
                    'badge'       => 'Multi-Unit Yayasan',
                    'city'        => 'Jakarta Selatan'
                ]),
                'badge_text'   => 'Pengurus Yayasan',
                'image_url'    => null,
                'icon_class'   => 'bi bi-quote',
                'cta_text'     => null,
                'cta_link'     => null,
                'is_active'    => true,
                'order_num'    => 3,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
        ];

        foreach ($testimonials as $t) {
            $exists = DB::table('cms.cms_promotions')
                ->where('section_key', 'testimonials')
                ->where('title', $t['title'])
                ->exists();
            if (!$exists) {
                DB::table('cms.cms_promotions')->insert($t);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('cms.cms_promotions')
            ->where('section_key', 'testimonials')
            ->delete();
    }
};
