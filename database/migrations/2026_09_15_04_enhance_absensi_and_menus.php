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
        // 1. Enhance absensi.presensi_siswa_harian
        Schema::table('absensi.presensi_siswa_harian', function (Blueprint $table) {
            if (!Schema::hasColumn('absensi.presensi_siswa_harian', 'siswa_id')) {
                $table->uuid('siswa_id')->nullable()->index();
            }
            if (!Schema::hasColumn('absensi.presensi_siswa_harian', 'nama_siswa')) {
                $table->string('nama_siswa', 255)->nullable();
            }
            if (!Schema::hasColumn('absensi.presensi_siswa_harian', 'nisn')) {
                $table->string('nisn', 30)->nullable();
            }
            if (!Schema::hasColumn('absensi.presensi_siswa_harian', 'kelas_id')) {
                $table->uuid('kelas_id')->nullable()->index();
            }
            if (!Schema::hasColumn('absensi.presensi_siswa_harian', 'nama_kelas')) {
                $table->string('nama_kelas', 100)->nullable();
            }
            if (!Schema::hasColumn('absensi.presensi_siswa_harian', 'tanggal')) {
                $table->date('tanggal')->nullable()->index();
            }
            if (!Schema::hasColumn('absensi.presensi_siswa_harian', 'jam_masuk')) {
                $table->string('jam_masuk', 10)->nullable();
            }
            if (!Schema::hasColumn('absensi.presensi_siswa_harian', 'jam_pulang')) {
                $table->string('jam_pulang', 10)->nullable();
            }
            if (!Schema::hasColumn('absensi.presensi_siswa_harian', 'status_kehadiran')) {
                $table->string('status_kehadiran', 30)->default('Hadir');
            }
            if (!Schema::hasColumn('absensi.presensi_siswa_harian', 'metode_presensi')) {
                $table->string('metode_presensi', 50)->default('QR_Code');
            }
            if (!Schema::hasColumn('absensi.presensi_siswa_harian', 'keterangan')) {
                $table->text('keterangan')->nullable();
            }
        });

        // 2. Enhance absensi.presensi_ptk_harian
        Schema::table('absensi.presensi_ptk_harian', function (Blueprint $table) {
            if (!Schema::hasColumn('absensi.presensi_ptk_harian', 'ptk_id')) {
                $table->uuid('ptk_id')->nullable()->index();
            }
            if (!Schema::hasColumn('absensi.presensi_ptk_harian', 'nama_ptk')) {
                $table->string('nama_ptk', 255)->nullable();
            }
            if (!Schema::hasColumn('absensi.presensi_ptk_harian', 'nip')) {
                $table->string('nip', 30)->nullable();
            }
            if (!Schema::hasColumn('absensi.presensi_ptk_harian', 'tanggal')) {
                $table->date('tanggal')->nullable()->index();
            }
            if (!Schema::hasColumn('absensi.presensi_ptk_harian', 'jam_masuk')) {
                $table->string('jam_masuk', 10)->nullable();
            }
            if (!Schema::hasColumn('absensi.presensi_ptk_harian', 'jam_pulang')) {
                $table->string('jam_pulang', 10)->nullable();
            }
            if (!Schema::hasColumn('absensi.presensi_ptk_harian', 'status_kehadiran')) {
                $table->string('status_kehadiran', 30)->default('Hadir');
            }
            if (!Schema::hasColumn('absensi.presensi_ptk_harian', 'metode_presensi')) {
                $table->string('metode_presensi', 50)->default('Geolokasi_GPS');
            }
            if (!Schema::hasColumn('absensi.presensi_ptk_harian', 'latitude')) {
                $table->decimal('latitude', 10, 8)->nullable();
            }
            if (!Schema::hasColumn('absensi.presensi_ptk_harian', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable();
            }
            if (!Schema::hasColumn('absensi.presensi_ptk_harian', 'jarak_meter')) {
                $table->integer('jarak_meter')->nullable();
            }
            if (!Schema::hasColumn('absensi.presensi_ptk_harian', 'status_geofence')) {
                $table->string('status_geofence', 30)->default('Valid');
            }
            if (!Schema::hasColumn('absensi.presensi_ptk_harian', 'keterangan')) {
                $table->text('keterangan')->nullable();
            }
        });

        // 3. Enhance absensi.lokasi_absensi_setting
        Schema::table('absensi.lokasi_absensi_setting', function (Blueprint $table) {
            if (!Schema::hasColumn('absensi.lokasi_absensi_setting', 'nama_lokasi')) {
                $table->string('nama_lokasi', 150)->nullable();
            }
            if (!Schema::hasColumn('absensi.lokasi_absensi_setting', 'latitude_pusat')) {
                $table->decimal('latitude_pusat', 10, 8)->default(-6.2088);
            }
            if (!Schema::hasColumn('absensi.lokasi_absensi_setting', 'longitude_pusat')) {
                $table->decimal('longitude_pusat', 11, 8)->default(106.8456);
            }
            if (!Schema::hasColumn('absensi.lokasi_absensi_setting', 'radius_meter')) {
                $table->integer('radius_meter')->default(150);
            }
            if (!Schema::hasColumn('absensi.lokasi_absensi_setting', 'jam_masuk_normal')) {
                $table->string('jam_masuk_normal', 10)->default('07:00');
            }
            if (!Schema::hasColumn('absensi.lokasi_absensi_setting', 'jam_pulang_normal')) {
                $table->string('jam_pulang_normal', 10)->default('15:30');
            }
            if (!Schema::hasColumn('absensi.lokasi_absensi_setting', 'toleransi_terlambat_menit')) {
                $table->integer('toleransi_terlambat_menit')->default(15);
            }
        });

        // 4. Enhance absensi.pengajuan_izin_cuti
        Schema::table('absensi.pengajuan_izin_cuti', function (Blueprint $table) {
            if (!Schema::hasColumn('absensi.pengajuan_izin_cuti', 'pemohon_type')) {
                $table->string('pemohon_type', 30)->default('siswa'); // 'siswa', 'gtk'
            }
            if (!Schema::hasColumn('absensi.pengajuan_izin_cuti', 'pemohon_id')) {
                $table->uuid('pemohon_id')->nullable()->index();
            }
            if (!Schema::hasColumn('absensi.pengajuan_izin_cuti', 'nama_pemohon')) {
                $table->string('nama_pemohon', 255)->nullable();
            }
            if (!Schema::hasColumn('absensi.pengajuan_izin_cuti', 'jenis_izin')) {
                $table->string('jenis_izin', 50)->default('Sakit');
            }
            if (!Schema::hasColumn('absensi.pengajuan_izin_cuti', 'tanggal_mulai')) {
                $table->date('tanggal_mulai')->nullable();
            }
            if (!Schema::hasColumn('absensi.pengajuan_izin_cuti', 'tanggal_selesai')) {
                $table->date('tanggal_selesai')->nullable();
            }
            if (!Schema::hasColumn('absensi.pengajuan_izin_cuti', 'alasan')) {
                $table->text('alasan')->nullable();
            }
            if (!Schema::hasColumn('absensi.pengajuan_izin_cuti', 'surat_lampiran_url')) {
                $table->string('surat_lampiran_url', 500)->nullable();
            }
            if (!Schema::hasColumn('absensi.pengajuan_izin_cuti', 'status_persetujuan')) {
                $table->string('status_persetujuan', 30)->default('Menunggu'); // 'Menunggu', 'Disetujui', 'Ditolak'
            }
            if (!Schema::hasColumn('absensi.pengajuan_izin_cuti', 'catatan_approver')) {
                $table->text('catatan_approver')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safe down
    }
};
