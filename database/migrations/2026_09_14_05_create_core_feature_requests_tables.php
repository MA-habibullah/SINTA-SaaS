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
        // 1. Tabel Usulan & Request Fitur Baru Aplikasi SINTA
        if (!Schema::hasTable('core.feature_requests')) {
            Schema::create('core.feature_requests', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('tenant_id')->nullable()->index();
                $table->uuid('user_id')->nullable()->index();
                $table->string('judul_fitur', 255);
                $table->string('modul_terkait', 100)->index(); // Akademik, Siswa, Keuangan, Perpustakaan, BK, Absensi, Kepegawaian, Sarpras, Persuratan, Kesiswaan, PDSS, Tracer, SMK, CMS, Sistem, Core
                $table->string('urgensi_bisnis', 50)->default('Sedang'); // Rendah, Sedang, Tinggi, Sangat Mendesak
                $table->text('deskripsi_kebutuhan');
                $table->text('ekspektasi_solusi')->nullable();
                $table->string('status', 50)->default('Review')->index(); // Review, Disetujui, Dalam Antrian, Sedang Dikembangkan, Selesai, Ditolak
                $table->string('estimasi_rilis', 100)->nullable(); // e.g. "Rilis v2.5.0", "Q4 2026"
                $table->text('catatan_pengembang')->nullable();
                $table->integer('votes_count')->default(0)->index();
                $table->text('lampiran')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                // Foreign Keys
                $table->foreign('tenant_id')->references('id')->on('core.tenants')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('core.users')->onDelete('set null');
            });
        }

        // 2. Tabel Upvote Dukungan Pengguna (Crowd-Sourced Priority)
        if (!Schema::hasTable('core.feature_request_votes')) {
            Schema::create('core.feature_request_votes', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('feature_request_id')->index();
                $table->uuid('user_id')->index();
                $table->timestampTz('created_at')->useCurrent();

                $table->foreign('feature_request_id')->references('id')->on('core.feature_requests')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('core.users')->onDelete('cascade');
                $table->unique(['feature_request_id', 'user_id']);
            });
        }

        // Seeding Contoh Data Usulan Fitur Pengembangan SINTA
        $reqCount = DB::table('core.feature_requests')->count();
        if ($reqCount === 0) {
            $adminUser = DB::table('core.users')->where('username', 'superadmin')->first() ?: DB::table('core.users')->first();
            $adminId = $adminUser?->id;
            $tenantId = $adminUser?->tenant_id;

            DB::table('core.feature_requests')->insert([
                [
                    'id'                  => (string) Str::uuid(),
                    'tenant_id'           => $tenantId,
                    'user_id'             => $adminId,
                    'judul_fitur'         => 'Integrasi Notifikasi WhatsApp Otomatis untuk Tagihan & Presensi Siswa',
                    'modul_terkait'       => 'Keuangan',
                    'urgensi_bisnis'      => 'Tinggi',
                    'deskripsi_kebutuhan' => 'Sekolah membutuhkan pengiriman notifikasi pengingat SPP bulanan dan notifikasi keterlambatan/ketidakhadiran siswa langsung ke nomor WhatsApp wali murid secara otomatis.',
                    'ekspektasi_solusi'   => 'Disediakan tombol broadcast tagihan massal dan webhook otomatis dari modul Absensi saat scan presensi pagi selesai.',
                    'status'              => 'Sedang Dikembangkan',
                    'estimasi_rilis'      => 'Versi 2.6.0 (Q4 2026)',
                    'catatan_pengembang'  => 'Fitur gateway WhatsApp sedang diintegrasikan melalui App\\Services\\WhatsAppGatewayService.',
                    'votes_count'         => 12,
                    'is_active'           => true,
                    'created_at'          => now()->subDays(5),
                    'updated_at'          => now()->subDays(1),
                ],
                [
                    'id'                  => (string) Str::uuid(),
                    'tenant_id'           => $tenantId,
                    'user_id'             => $adminId,
                    'judul_fitur'         => 'Cetak Kartu Anggota Perpustakaan & Barcode Buku Massal Format PDF',
                    'modul_terkait'       => 'Perpustakaan',
                    'urgensi_bisnis'      => 'Sedang',
                    'deskripsi_kebutuhan' => 'Petugas perpustakaan ingin mencetak stiker barcode nomor induk buku dan kartu anggota siswa dalam 1 lembar kertas A4 siap potong sesuai standar INLISLite.',
                    'ekspektasi_solusi'   => 'Tersedia pilihan layout cetak 9 kartu per halaman A4 dan label stiker punggung buku (spine label) 2x5 baris.',
                    'status'              => 'Selesai',
                    'estimasi_rilis'      => 'Versi 2.5.0 (Rilis)',
                    'catatan_pengembang'  => 'Fitur labeling dan KTA perpustakaan standar INLISLite telah aktif pada Modul Perpustakaan.',
                    'votes_count'         => 8,
                    'is_active'           => true,
                    'created_at'          => now()->subDays(10),
                    'updated_at'          => now()->subDays(2),
                ],
                [
                    'id'                  => (string) Str::uuid(),
                    'tenant_id'           => $tenantId,
                    'user_id'             => $adminId,
                    'judul_fitur'         => 'Generator Rapor Kurikulum Merdeka Terintegrasi P5 dan Ekstrakurikuler',
                    'modul_terkait'       => 'Akademik',
                    'urgensi_bisnis'      => 'Tinggi',
                    'deskripsi_kebutuhan' => 'Memudahkan wali kelas mengunduh rapor lengkap semester beserta lembar deskripsi capaian pembelajaran P5 dan catatan wali kelas dalam format PDF satu klik.',
                    'ekspektasi_solusi'   => 'Cetak rapor massal per rombel kelas dengan antrean background queue Spatie Browsershot.',
                    'status'              => 'Disetujui',
                    'estimasi_rilis'      => 'Versi 2.7.0',
                    'catatan_pengembang'  => 'Desain template rapor K-Merdeka sudah disiapkan dan masuk roadmap rilis berikutnya.',
                    'votes_count'         => 15,
                    'is_active'           => true,
                    'created_at'          => now()->subDays(3),
                    'updated_at'          => now()->subHours(12),
                ],
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('core.feature_request_votes');
        Schema::dropIfExists('core.feature_requests');
    }
};
