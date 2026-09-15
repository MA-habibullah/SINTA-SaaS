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

        // 1. Seed Landing Page Navigation Menus (section_key: nav_menu)
        $navMenus = [
            [
                'id'           => Str::uuid()->toString(),
                'section_key'  => 'nav_menu',
                'title'        => 'Fitur Unggulan',
                'subtitle'     => '16 Modul Terpadu Tata Kelola Sekolah',
                'content'      => null,
                'content_json' => json_encode(['target' => '_self', 'color' => 'blue']),
                'badge_text'   => '16 Modul',
                'image_url'    => null,
                'icon_class'   => 'bi bi-grid-fill',
                'cta_text'     => 'Lihat Fitur',
                'cta_link'     => '#fitur',
                'is_active'    => true,
                'order_num'    => 1,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'id'           => Str::uuid()->toString(),
                'section_key'  => 'nav_menu',
                'title'        => 'Keuntungan Aplikasi',
                'subtitle'     => 'Keunggulan Arsitektur & Manfaat untuk Sekolah',
                'content'      => null,
                'content_json' => json_encode(['target' => '_self', 'color' => 'purple']),
                'badge_text'   => 'Keunggulan',
                'image_url'    => null,
                'icon_class'   => 'bi bi-shield-check',
                'cta_text'     => 'Lihat Keuntungan',
                'cta_link'     => '#keuntungan',
                'is_active'    => true,
                'order_num'    => 2,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'id'           => Str::uuid()->toString(),
                'section_key'  => 'nav_menu',
                'title'        => 'Simulasi Biaya',
                'subtitle'     => 'Kalkulator Anggaran Fleksibel RKAS',
                'content'      => null,
                'content_json' => json_encode(['target' => '_self', 'color' => 'emerald']),
                'badge_text'   => 'Kalkulator',
                'image_url'    => null,
                'icon_class'   => 'bi bi-calculator-fill',
                'cta_text'     => 'Hitung Biaya',
                'cta_link'     => '#kalkulator',
                'is_active'    => true,
                'order_num'    => 3,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'id'           => Str::uuid()->toString(),
                'section_key'  => 'nav_menu',
                'title'        => 'Paket & Harga',
                'subtitle'     => 'Pilihan Berlangganan & Free Trial 1 Bulan',
                'content'      => null,
                'content_json' => json_encode(['target' => '_self', 'color' => 'indigo']),
                'badge_text'   => 'Trial 1 Bln',
                'image_url'    => null,
                'icon_class'   => 'bi bi-box-seam-fill',
                'cta_text'     => 'Pilih Paket',
                'cta_link'     => '#paket',
                'is_active'    => true,
                'order_num'    => 4,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'id'           => Str::uuid()->toString(),
                'section_key'  => 'nav_menu',
                'title'        => 'FAQ & Bantuan',
                'subtitle'     => 'Pertanyaan yang Sering Diajukan',
                'content'      => null,
                'content_json' => json_encode(['target' => '_self', 'color' => 'amber']),
                'badge_text'   => 'Bantuan',
                'image_url'    => null,
                'icon_class'   => 'bi bi-question-circle',
                'cta_text'     => 'Lihat FAQ',
                'cta_link'     => '#faq',
                'is_active'    => true,
                'order_num'    => 5,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
        ];

        foreach ($navMenus as $menu) {
            $exists = DB::table('cms.cms_promotions')
                ->where('section_key', 'nav_menu')
                ->where('title', $menu['title'])
                ->exists();
            if (!$exists) {
                DB::table('cms.cms_promotions')->insert($menu);
            }
        }

        // 2. Seed Keuntungan Aplikasi / Benefits (section_key: benefits)
        $benefits = [
            [
                'id'           => Str::uuid()->toString(),
                'section_key'  => 'benefits',
                'title'        => 'Isolasi Skema Database Multi-Tenant',
                'subtitle'     => 'Privasi data sekolah terjamin 100%. Setiap sekolah memiliki skema PostgreSQL mandiri yang terisolasi total.',
                'content'      => 'Tidak ada risiko data bocor atau tertukar antar sekolah. Arsitektur enterprise menjamin keamanan dan performa maksimal.',
                'content_json' => json_encode([
                    'highlights' => ['Dedicated Schema PostgreSQL 16', 'Zero Cross-Tenant Leakage', 'Enkripsi AES-256 HMAC'],
                    'gradient'   => 'from-blue-600/20 to-indigo-600/20',
                    'border'     => 'border-blue-500/30',
                    'icon_color' => 'text-blue-400',
                ]),
                'badge_text'   => 'Keamanan Data',
                'image_url'    => null,
                'icon_class'   => 'bi bi-shield-lock-fill',
                'cta_text'     => null,
                'cta_link'     => null,
                'is_active'    => true,
                'order_num'    => 1,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'id'           => Str::uuid()->toString(),
                'section_key'  => 'benefits',
                'title'        => 'Otomasi Rapor Kurikulum Merdeka & K13',
                'subtitle'     => 'Hitung capaian pembelajaran otomatis, generate deskripsi nilai dinamis, dan cetak lembar rapor PDF resmi dalam hitungan detik.',
                'content'      => 'Sesuai format regulasi Kemendikbudristek terbaru. Mendukung rapor massal 1 rombel & ledger nilai format Excel.',
                'content_json' => json_encode([
                    'highlights' => ['Format Resmi Kemendikbud', 'Cetak Massal PDF Multi-Page', 'Ekspor Buku Ledger Excel'],
                    'gradient'   => 'from-emerald-600/20 to-teal-600/20',
                    'border'     => 'border-emerald-500/30',
                    'icon_color' => 'text-emerald-400',
                ]),
                'badge_text'   => 'Kurikulum & Rapor',
                'image_url'    => null,
                'icon_class'   => 'bi bi-award-fill',
                'cta_text'     => null,
                'cta_link'     => null,
                'is_active'    => true,
                'order_num'    => 2,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'id'           => Str::uuid()->toString(),
                'section_key'  => 'benefits',
                'title'        => 'Kasir SPP Kilat & Multi-Channel Payment',
                'subtitle'     => 'Terima pembayaran SPP melalui Kasir POS cepat (cetak nota thermal 58mm/80mm) atau transfer online otomatis via QRIS & Virtual Account.',
                'content'      => 'Terintegrasi langsung dengan payment gateway Midtrans dan auto-notifikasi WhatsApp tagihan ke wali murid.',
                'content_json' => json_encode([
                    'highlights' => ['Kasir POS Struk Thermal', 'Midtrans QRIS & VA Otomatis', 'Auto Notifikasi WhatsApp'],
                    'gradient'   => 'from-amber-600/20 to-orange-600/20',
                    'border'     => 'border-amber-500/30',
                    'icon_color' => 'text-amber-400',
                ]),
                'badge_text'   => 'Keuangan Terpadu',
                'image_url'    => null,
                'icon_class'   => 'bi bi-wallet2',
                'cta_text'     => null,
                'cta_link'     => null,
                'is_active'    => true,
                'order_num'    => 3,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'id'           => Str::uuid()->toString(),
                'section_key'  => 'benefits',
                'title'        => 'Presensi Siswa & GTK Berbasis GPS Geofencing',
                'subtitle'     => 'Presensi mandiri presisi tinggi dengan validasi radius lokasi sekolah, proteksi Anti-Fake GPS / Mock Location, dan jurnal mengajar KBM.',
                'content'      => 'Mencegah kecurangan presensi dengan audit log anti-fraud real-time serta kompresi otomatis surat izin/dokter < 500 KB.',
                'content_json' => json_encode([
                    'highlights' => ['Geofencing Radius Presisi', 'Anti-Fake GPS & Mock Shield', 'Auto Compress Bukti < 500 KB'],
                    'gradient'   => 'from-indigo-600/20 to-purple-600/20',
                    'border'     => 'border-indigo-500/30',
                    'icon_color' => 'text-indigo-400',
                ]),
                'badge_text'   => 'Presensi Anti-Fraud',
                'image_url'    => null,
                'icon_class'   => 'bi bi-geo-alt-fill',
                'cta_text'     => null,
                'cta_link'     => null,
                'is_active'    => true,
                'order_num'    => 4,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'id'           => Str::uuid()->toString(),
                'section_key'  => 'benefits',
                'title'        => 'Hemat Biaya Infrastruktur & Bebas Pemeliharaan',
                'subtitle'     => 'Sekolah tidak perlu membeli server mahal atau mempekerjakan tim IT khusus. Semua sistem dan backup dikelola otomatis di cloud SINTA.',
                'content'      => 'Akses dapat dibuka dari laptop, tablet, maupun smartphone secara responsif dengan kecepatan tinggi kapan saja.',
                'content_json' => json_encode([
                    'highlights' => ['100% Cloud Managed', 'Auto Backup Harian', 'Akses Cepat Semua Perangkat'],
                    'gradient'   => 'from-cyan-600/20 to-blue-600/20',
                    'border'     => 'border-cyan-500/30',
                    'icon_color' => 'text-cyan-400',
                ]),
                'badge_text'   => 'Efisiensi Anggaran',
                'image_url'    => null,
                'icon_class'   => 'bi bi-cloud-check-fill',
                'cta_text'     => null,
                'cta_link'     => null,
                'is_active'    => true,
                'order_num'    => 5,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'id'           => Str::uuid()->toString(),
                'section_key'  => 'benefits',
                'title'        => 'Keamanan Standar OWASP & Zero Data Leakage',
                'subtitle'     => 'Arsitektur Zero-SSR Client Hydration memastikan data sensitif seperti NIK, rekam medis, dan nilai tidak bocor pada View Source browser.',
                'content'      => 'Memenuhi standar OWASP ASVS Level 3 dengan sanitasi kredensial otomatis dan proteksi CSRF di seluruh mutasi data.',
                'content_json' => json_encode([
                    'highlights' => ['Zero SSR Data Exposure', 'OWASP ASVS L3 Compliance', 'Memory Security Sanitizer'],
                    'gradient'   => 'from-rose-600/20 to-pink-600/20',
                    'border'     => 'border-rose-500/30',
                    'icon_color' => 'text-rose-400',
                ]),
                'badge_text'   => 'Enterprise Security',
                'image_url'    => null,
                'icon_class'   => 'bi bi-cpu-fill',
                'cta_text'     => null,
                'cta_link'     => null,
                'is_active'    => true,
                'order_num'    => 6,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
        ];

        foreach ($benefits as $benefit) {
            $exists = DB::table('cms.cms_promotions')
                ->where('section_key', 'benefits')
                ->where('title', $benefit['title'])
                ->exists();
            if (!$exists) {
                DB::table('cms.cms_promotions')->insert($benefit);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('cms.cms_promotions')
            ->whereIn('section_key', ['nav_menu', 'benefits'])
            ->delete();
    }
};
