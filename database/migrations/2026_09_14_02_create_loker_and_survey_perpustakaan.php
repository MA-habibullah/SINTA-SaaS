<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. TABEL LOKER PENITIPAN BARANG
        if (!Schema::hasTable('perpustakaan.perpus_loker')) {
            Schema::create('perpustakaan.perpus_loker', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('tenant_id')->index();
                $table->string('nomor_loker', 50);
                $table->string('lokasi_ruangan', 100)->default('Lobi Utama Perpustakaan');
                $table->string('status', 20)->default('tersedia'); // tersedia, terisi, rusak, kunci_hilang
                $table->text('keterangan')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 2. TABEL LOG TRANSAKSI PEMINJAMAN LOKER
        if (!Schema::hasTable('perpustakaan.perpus_loker_log')) {
            Schema::create('perpustakaan.perpus_loker_log', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('tenant_id')->index();
                $table->uuid('loker_id')->index();
                $table->uuid('anggota_id')->nullable()->index();
                $table->string('nama_peminjam', 255);
                $table->string('identitas_jaminan', 100)->nullable(); // KTA, Kartu Pelajar, KTP
                $table->timestampTz('waktu_pinjam')->useCurrent();
                $table->timestampTz('waktu_kembali')->nullable();
                $table->string('status_pinjam', 20)->default('dipinjam'); // dipinjam, kembali, pelanggaran
                $table->decimal('denda', 12, 2)->default(0.00);
                $table->uuid('petugas_id')->nullable();
                $table->text('catatan')->nullable();
                $table->timestamps();
            });
        }

        // 3. TABEL SURVEY KEPUASAN & IKM PEMUSTAKA
        if (!Schema::hasTable('perpustakaan.perpus_survey')) {
            Schema::create('perpustakaan.perpus_survey', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('tenant_id')->index();
                $table->string('judul_survey', 255);
                $table->text('deskripsi')->nullable();
                $table->date('tanggal_buka');
                $table->date('tanggal_tutup')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 4. TABEL PERTANYAAN SURVEY
        if (!Schema::hasTable('perpustakaan.perpus_survey_pertanyaan')) {
            Schema::create('perpustakaan.perpus_survey_pertanyaan', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('tenant_id')->index();
                $table->uuid('survey_id')->index();
                $table->integer('urutan')->default(1);
                $table->text('pertanyaan');
                $table->string('tipe_pertanyaan', 50)->default('skala_likert'); // skala_likert, esai, pilihan_ganda
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 5. TABEL RESPON SURVEY
        if (!Schema::hasTable('perpustakaan.perpus_survey_respon')) {
            Schema::create('perpustakaan.perpus_survey_respon', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('tenant_id')->index();
                $table->uuid('survey_id')->index();
                $table->uuid('pertanyaan_id')->index();
                $table->uuid('anggota_id')->nullable()->index();
                $table->string('nama_responden', 255)->nullable();
                $table->integer('skor_nilai')->nullable(); // 1 - 5
                $table->text('jawaban_teks')->nullable();
                $table->timestampTz('created_at')->useCurrent();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('perpustakaan.perpus_survey_respon');
        Schema::dropIfExists('perpustakaan.perpus_survey_pertanyaan');
        Schema::dropIfExists('perpustakaan.perpus_survey');
        Schema::dropIfExists('perpustakaan.perpus_loker_log');
        Schema::dropIfExists('perpustakaan.perpus_loker');
    }
};
