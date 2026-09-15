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
        // 1. Enhance absensi.presensi_siswa_harian with verification & proof file columns
        Schema::table('absensi.presensi_siswa_harian', function (Blueprint $table) {
            if (!Schema::hasColumn('absensi.presensi_siswa_harian', 'bukti_izin_url')) {
                $table->string('bukti_izin_url', 500)->nullable();
            }
            if (!Schema::hasColumn('absensi.presensi_siswa_harian', 'nama_berkas_asli')) {
                $table->string('nama_berkas_asli', 255)->nullable();
            }
            if (!Schema::hasColumn('absensi.presensi_siswa_harian', 'ukuran_berkas_kb')) {
                $table->decimal('ukuran_berkas_kb', 8, 2)->nullable();
            }
            if (!Schema::hasColumn('absensi.presensi_siswa_harian', 'status_verifikasi')) {
                $table->string('status_verifikasi', 30)->default('Terverifikasi'); // 'Menunggu', 'Terverifikasi', 'Ditolak'
            }
            if (!Schema::hasColumn('absensi.presensi_siswa_harian', 'diverifikasi_oleh')) {
                $table->uuid('diverifikasi_oleh')->nullable()->index();
            }
            if (!Schema::hasColumn('absensi.presensi_siswa_harian', 'waktu_verifikasi')) {
                $table->timestamp('waktu_verifikasi')->nullable();
            }
            if (!Schema::hasColumn('absensi.presensi_siswa_harian', 'catatan_wali_kelas')) {
                $table->text('catatan_wali_kelas')->nullable();
            }
            if (!Schema::hasColumn('absensi.presensi_siswa_harian', 'diinput_oleh')) {
                $table->string('diinput_oleh', 50)->default('siswa'); // 'siswa', 'wali_kelas', 'guru_piket', 'admin'
            }
        });

        // 2. Create absensi.jurnal_mengajar Table
        if (!Schema::hasTable('absensi.jurnal_mengajar')) {
            Schema::create('absensi.jurnal_mengajar', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('tenant_id')->index();
                $table->uuid('jadwal_id')->nullable()->index();
                $table->uuid('guru_id')->index();
                $table->string('nama_guru', 255)->nullable();
                $table->uuid('kelas_id')->index();
                $table->string('nama_kelas', 100)->nullable();
                $table->uuid('mapel_id')->index();
                $table->string('nama_mapel', 255)->nullable();
                $table->string('tahun_ajaran', 50)->nullable();
                $table->string('semester', 20)->nullable();
                $table->date('tanggal')->index();
                $table->string('jam_ke', 50)->nullable(); // e.g. "1-2"
                $table->string('jam_mulai', 10)->nullable();
                $table->string('jam_selesai', 10)->nullable();
                $table->text('capaian_pembelajaran')->nullable();
                $table->text('aktivitas_pembelajaran')->nullable();
                $table->text('kendala_pembelajaran')->nullable();
                $table->string('foto_kegiatan_url', 500)->nullable();
                $table->decimal('foto_ukuran_kb', 8, 2)->nullable();
                $table->integer('jumlah_hadir')->default(0);
                $table->integer('jumlah_sakit')->default(0);
                $table->integer('jumlah_izin')->default(0);
                $table->integer('jumlah_alpa')->default(0);
                $table->integer('jumlah_terlambat')->default(0);
                $table->string('status_kbm', 30)->default('Selesai'); // 'Selesai', 'Terganti', 'Daring', 'Tugas Mandiri'
                $table->text('catatan_supervisor')->nullable();
                $table->boolean('is_verified')->default(false);
                $table->timestamps();
            });
        }

        // 3. Enhance / Create absensi.presensi_siswa_kbm Table
        if (!Schema::hasTable('absensi.presensi_siswa_kbm')) {
            Schema::create('absensi.presensi_siswa_kbm', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('tenant_id')->index();
                $table->uuid('jurnal_id')->index();
                $table->uuid('siswa_id')->index();
                $table->string('nama_siswa', 255)->nullable();
                $table->string('nisn', 30)->nullable();
                $table->string('status_kehadiran', 30)->default('Hadir'); // 'Hadir', 'Sakit', 'Izin', 'Alpa', 'Terlambat'
                $table->text('catatan')->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('absensi.presensi_siswa_kbm', function (Blueprint $table) {
                if (!Schema::hasColumn('absensi.presensi_siswa_kbm', 'jurnal_id')) {
                    $table->uuid('jurnal_id')->nullable()->index();
                }
                if (!Schema::hasColumn('absensi.presensi_siswa_kbm', 'siswa_id')) {
                    $table->uuid('siswa_id')->nullable()->index();
                }
                if (!Schema::hasColumn('absensi.presensi_siswa_kbm', 'nama_siswa')) {
                    $table->string('nama_siswa', 255)->nullable();
                }
                if (!Schema::hasColumn('absensi.presensi_siswa_kbm', 'nisn')) {
                    $table->string('nisn', 30)->nullable();
                }
                if (!Schema::hasColumn('absensi.presensi_siswa_kbm', 'status_kehadiran')) {
                    $table->string('status_kehadiran', 30)->default('Hadir');
                }
                if (!Schema::hasColumn('absensi.presensi_siswa_kbm', 'catatan')) {
                    $table->text('catatan')->nullable();
                }
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
