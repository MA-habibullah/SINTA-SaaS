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
        // 1. Tambahkan kolom trial, PIC, dan approval ke core.tenants jika belum ada
        Schema::table('core.tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('core.tenants', 'trial_ends_at')) {
                $table->timestampTz('trial_ends_at')->nullable();
            }
            if (!Schema::hasColumn('core.tenants', 'trial_duration_months')) {
                $table->integer('trial_duration_months')->default(3);
            }
            if (!Schema::hasColumn('core.tenants', 'subscription_type')) {
                $table->string('subscription_type', 50)->default('Free Trial');
            }
            if (!Schema::hasColumn('core.tenants', 'pic_nama')) {
                $table->string('pic_nama', 255)->nullable();
            }
            if (!Schema::hasColumn('core.tenants', 'pic_jabatan')) {
                $table->string('pic_jabatan', 100)->nullable();
            }
            if (!Schema::hasColumn('core.tenants', 'pic_telepon')) {
                $table->string('pic_telepon', 50)->nullable();
            }
            if (!Schema::hasColumn('core.tenants', 'pic_email')) {
                $table->string('pic_email', 150)->nullable();
            }
            if (!Schema::hasColumn('core.tenants', 'approved_at')) {
                $table->timestampTz('approved_at')->nullable();
            }
            if (!Schema::hasColumn('core.tenants', 'approved_by')) {
                $table->uuid('approved_by')->nullable();
            }
            if (!Schema::hasColumn('core.tenants', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable();
            }
        });

        // 2. Buat tabel cms.cms_promotions untuk Super Admin CMS Promosi & Landing Page SINTA SaaS
        if (!Schema::hasTable('cms.cms_promotions')) {
            Schema::create('cms.cms_promotions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('section_key', 50)->index(); // hero, features, pricing, testimonials, faq, contact, cta
                $table->string('title', 255);
                $table->text('subtitle')->nullable();
                $table->text('content')->nullable();
                $table->jsonb('content_json')->nullable();
                $table->string('badge_text', 100)->nullable();
                $table->text('image_url')->nullable();
                $table->string('icon_class', 100)->nullable();
                $table->string('cta_text', 100)->nullable();
                $table->string('cta_link', 255)->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('order_num')->default(0);
                $table->timestamps();
            });

            // Seed initial promotional contents
            $now = now();
            $initialData = [
                [
                    'id'           => Str::uuid()->toString(),
                    'section_key'  => 'hero',
                    'title'        => 'Transformasi Digital Manajemen Sekolah Terpadu & Terintegrasi',
                    'subtitle'     => 'SINTA SaaS adalah ekosistem digital all-in-one untuk tata kelola akademik, kesiswaan, keuangan, BK, perpustakaan, hingga rapor Kurikulum Merdeka. Nikmati uji coba gratis 3 bulan untuk sekolah Anda!',
                    'content'      => 'Solusi cloud multi-tenant yang aman, cepat, dan sesuai regulasi Kemendikbudristek.',
                    'content_json' => json_encode(['highlight' => 'Gratis 3 Bulan Uji Coba Penuh', 'active_schools' => '500+ Sekolah Terdaftar']),
                    'badge_text'   => 'Platform SaaS Sekolah #1 di Indonesia',
                    'image_url'    => null,
                    'icon_class'   => 'bi bi-stars',
                    'cta_text'     => 'Daftarkan Sekolah Anda (Free Trial)',
                    'cta_link'     => '/daftar-sekolah',
                    'is_active'    => true,
                    'order_num'    => 1,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ],
                [
                    'id'           => Str::uuid()->toString(),
                    'section_key'  => 'pricing',
                    'title'        => 'Paket Percobaan Gratis (Free Trial 3 Bulan)',
                    'subtitle'     => 'Akses seluruh fitur modular SINTA tanpa komitmen biaya. Dapatkan pendampingan teknis gratis saat onboarding.',
                    'content'      => 'Fitur Lengkap 16 Modul, Unlimited Siswa & GTK, Penyimpanan Cloud Cepat, Dukungan Teknis 24/7, dan Cetak Rapor Digital.',
                    'content_json' => json_encode([
                        'features' => [
                            '16 Modul Terintegrasi Penuh (Akademik, Keuangan, PPDB, BK, dll)',
                            'Koneksi Multi-User (Admin, Guru, Siswa, Orang Tua)',
                            'Cetak Rapor Kurikulum Merdeka & K13 Otomatis',
                            'Integrasi WhatsApp Gateway & Payment Gateway',
                            'Backup Database Terenkripsi Multi-Schema'
                        ],
                        'badge' => 'Paling Populer',
                        'price_text' => 'Rp 0 / 3 Bulan',
                        'duration' => '3 Bulan Penuh'
                    ]),
                    'badge_text'   => 'Free Trial 3 Bulan',
                    'image_url'    => null,
                    'icon_class'   => 'bi bi-gift',
                    'cta_text'     => 'Mulai Coba Gratis Sekarang',
                    'cta_link'     => '/daftar-sekolah',
                    'is_active'    => true,
                    'order_num'    => 1,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ],
                [
                    'id'           => Str::uuid()->toString(),
                    'section_key'  => 'pricing',
                    'title'        => 'Paket Uji Coba Cepat (Free Trial 1 Bulan)',
                    'subtitle'     => 'Eksplorasi singkat bagi sekolah yang ingin menguji integrasi modul utama sebelum implementasi penuh.',
                    'content'      => 'Akses modul pokok: Data Siswa, Buku Induk, Akademik, dan Keuangan SPP.',
                    'content_json' => json_encode([
                        'features' => [
                            'Modul Pokok Akademik & Buku Induk',
                            'Manajemen Tagihan & Pembayaran SPP',
                            'Presensi & Absensi Siswa GTK',
                            'Dukungan Teknis via WhatsApp'
                        ],
                        'badge' => 'Uji Coba Singkat',
                        'price_text' => 'Rp 0 / 1 Bulan',
                        'duration' => '1 Bulan'
                    ]),
                    'badge_text'   => 'Free Trial 1 Bulan',
                    'image_url'    => null,
                    'icon_class'   => 'bi bi-lightning-charge',
                    'cta_text'     => 'Coba 1 Bulan Gratis',
                    'cta_link'     => '/daftar-sekolah?trial=1',
                    'is_active'    => true,
                    'order_num'    => 2,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ],
                [
                    'id'           => Str::uuid()->toString(),
                    'section_key'  => 'features',
                    'title'        => 'Buku Induk & Manajemen Siswa Terpadu',
                    'subtitle'     => 'Pengelolaan data induk siswa terlengkap dengan riwayat mutasi, beasiswa, dan pencatatan NISN otomatis.',
                    'content'      => 'Sinkronisasi data Dapodik dengan format cetak lembar buku induk berstandar nasional.',
                    'content_json' => json_encode(['tag' => 'Core Module']),
                    'badge_text'   => 'Akademik',
                    'image_url'    => null,
                    'icon_class'   => 'bi bi-journal-text',
                    'cta_text'     => 'Pelajari Selengkapnya',
                    'cta_link'     => '/daftar-sekolah',
                    'is_active'    => true,
                    'order_num'    => 1,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ],
                [
                    'id'           => Str::uuid()->toString(),
                    'section_key'  => 'features',
                    'title'        => 'Otomasi Penilaian & Cetak Rapor Digital',
                    'subtitle'     => 'Generator Rapor Kurikulum Merdeka dan K13 siap cetak format PDF berkualitas tinggi dalam hitungan detik.',
                    'content'      => 'Perhitungan capaian pembelajaran otomatis, deskripsi nilai dinamis, dan catatan wali kelas.',
                    'content_json' => json_encode(['tag' => 'Kurikulum']),
                    'badge_text'   => 'Rapor Digital',
                    'image_url'    => null,
                    'icon_class'   => 'bi bi-award',
                    'cta_text'     => 'Pelajari Selengkapnya',
                    'cta_link'     => '/daftar-sekolah',
                    'is_active'    => true,
                    'order_num'    => 2,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ],
                [
                    'id'           => Str::uuid()->toString(),
                    'section_key'  => 'features',
                    'title'        => 'Keuangan & Tagihan SPP Multi-Channel',
                    'subtitle'     => 'Manajemen pos tarif, cetak kuitansi kasir, dan integrasi auto-settlement payment gateway Midtrans.',
                    'content'      => 'Laporan rekapitulasi kas masuk, tunggakan siswa, dan notifikasi WhatsApp tagihan otomatis ke wali murid.',
                    'content_json' => json_encode(['tag' => 'Finansial']),
                    'badge_text'   => 'Keuangan',
                    'image_url'    => null,
                    'icon_class'   => 'bi bi-wallet2',
                    'cta_text'     => 'Pelajari Selengkapnya',
                    'cta_link'     => '/daftar-sekolah',
                    'is_active'    => true,
                    'order_num'    => 3,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ],
                [
                    'id'           => Str::uuid()->toString(),
                    'section_key'  => 'faq',
                    'title'        => 'Bagaimana cara mendapatkan uji coba gratis (Free Trial)?',
                    'subtitle'     => 'Anda cukup mengisi formulir pendaftaran sekolah di halaman Daftar Sekolah. Super Admin akan memverifikasi permohonan dalam waktu 1x24 jam dan mengaktifkan akses Anda.',
                    'content'      => null,
                    'content_json' => null,
                    'badge_text'   => 'Pendaftaran',
                    'image_url'    => null,
                    'icon_class'   => 'bi bi-question-circle',
                    'cta_text'     => null,
                    'cta_link'     => null,
                    'is_active'    => true,
                    'order_num'    => 1,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ],
                [
                    'id'           => Str::uuid()->toString(),
                    'section_key'  => 'faq',
                    'title'        => 'Apakah data sekolah aman dan terisolasi?',
                    'subtitle'     => 'Ya, SINTA SaaS menerapkan arsitektur Multi-Schema PostgreSQL dan isolasi tenant per sekolah sehingga data sekolah Anda terpisah secara aman dan terlindungi.',
                    'content'      => null,
                    'content_json' => null,
                    'badge_text'   => 'Keamanan',
                    'image_url'    => null,
                    'icon_class'   => 'bi bi-shield-check',
                    'cta_text'     => null,
                    'cta_link'     => null,
                    'is_active'    => true,
                    'order_num'    => 2,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ],
            ];

            DB::table('cms.cms_promotions')->insert($initialData);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cms.cms_promotions');

        Schema::table('core.tenants', function (Blueprint $table) {
            $cols = [
                'trial_ends_at',
                'trial_duration_months',
                'subscription_type',
                'pic_nama',
                'pic_jabatan',
                'pic_telepon',
                'pic_email',
                'approved_at',
                'approved_by',
                'rejection_reason',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('core.tenants', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
