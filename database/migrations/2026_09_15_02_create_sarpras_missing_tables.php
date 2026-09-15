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
        if (!Schema::hasTable('sarpras.ruangan')) {
            Schema::create('sarpras.ruangan', function (Blueprint $table) {
                $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
                $table->uuid('tenant_id')->nullable()->index();
                $table->uuid('bangunan_id')->nullable()->index();
                $table->string('kode_ruangan', 50)->nullable();
                $table->string('nama_ruangan', 150);
                $table->string('jenis_ruangan', 50)->default('Teori/Kelas');
                $table->integer('kapasitas_siswa')->default(36);
                $table->decimal('luas_m2', 8, 2)->nullable();
                $table->string('penanggung_jawab', 150)->nullable();
                $table->string('kondisi', 50)->default('Baik');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('sarpras.peminjaman_sarpras')) {
            Schema::create('sarpras.peminjaman_sarpras', function (Blueprint $table) {
                $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
                $table->uuid('tenant_id')->nullable()->index();
                $table->uuid('barang_id')->nullable()->index();
                $table->uuid('ruangan_id')->nullable()->index();
                $table->string('nama_peminjam', 200);
                $table->string('peran_peminjam', 50)->default('Guru');
                $table->string('tujuan_peminjaman', 255)->nullable();
                $table->dateTime('tanggal_pinjam');
                $table->dateTime('tanggal_kembali_rencana')->nullable();
                $table->dateTime('tanggal_kembali_aktual')->nullable();
                $table->integer('jumlah_pinjam')->default(1);
                $table->string('status_peminjaman', 50)->default('Menunggu');
                $table->text('catatan')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('sarpras.riwayat_pemeliharaan')) {
            Schema::create('sarpras.riwayat_pemeliharaan', function (Blueprint $table) {
                $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
                $table->uuid('tenant_id')->nullable()->index();
                $table->uuid('barang_id')->nullable()->index();
                $table->uuid('ruangan_id')->nullable()->index();
                $table->string('jenis_pemeliharaan', 100)->default('Perbaikan Rutin');
                $table->date('tanggal_mulai');
                $table->date('tanggal_selesai')->nullable();
                $table->decimal('biaya', 14, 2)->default(0);
                $table->string('pelaksana', 150)->nullable();
                $table->string('status_perbaikan', 50)->default('Dalam Pengerjaan');
                $table->text('deskripsi_kerusakan')->nullable();
                $table->text('tindakan_dilakukan')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
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
