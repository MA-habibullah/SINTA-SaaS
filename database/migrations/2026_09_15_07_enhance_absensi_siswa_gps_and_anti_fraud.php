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
        // 1. Add GPS & Anti-Fraud Columns to absensi.presensi_siswa_harian
        Schema::table('absensi.presensi_siswa_harian', function (Blueprint $table) {
            if (!Schema::hasColumn('absensi.presensi_siswa_harian', 'latitude')) {
                $table->decimal('latitude', 10, 8)->nullable();
            }
            if (!Schema::hasColumn('absensi.presensi_siswa_harian', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable();
            }
            if (!Schema::hasColumn('absensi.presensi_siswa_harian', 'jarak_meter')) {
                $table->integer('jarak_meter')->nullable();
            }
            if (!Schema::hasColumn('absensi.presensi_siswa_harian', 'status_geofence')) {
                $table->string('status_geofence', 30)->default('Valid'); // 'Valid', 'Luar Radius', 'Tanpa GPS'
            }
            if (!Schema::hasColumn('absensi.presensi_siswa_harian', 'akurasi_meter')) {
                $table->decimal('akurasi_meter', 8, 2)->nullable();
            }
            if (!Schema::hasColumn('absensi.presensi_siswa_harian', 'is_mock_location')) {
                $table->boolean('is_mock_location')->default(false);
            }
            if (!Schema::hasColumn('absensi.presensi_siswa_harian', 'is_suspicious')) {
                $table->boolean('is_suspicious')->default(false);
            }
            if (!Schema::hasColumn('absensi.presensi_siswa_harian', 'fraud_reason')) {
                $table->text('fraud_reason')->nullable();
            }
            if (!Schema::hasColumn('absensi.presensi_siswa_harian', 'device_info')) {
                $table->text('device_info')->nullable();
            }
            if (!Schema::hasColumn('absensi.presensi_siswa_harian', 'ip_address')) {
                $table->string('ip_address', 45)->nullable();
            }
        });

        // 2. Create Anti-Fraud Audit Log Table absensi.presensi_fraud_logs
        if (!Schema::hasTable('absensi.presensi_fraud_logs')) {
            Schema::create('absensi.presensi_fraud_logs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('tenant_id')->index();
                $table->uuid('user_id')->nullable()->index();
                $table->uuid('siswa_id')->nullable()->index();
                $table->uuid('ptk_id')->nullable()->index();
                $table->string('tipe_pengguna', 30)->default('siswa'); // 'siswa', 'gtk'
                $table->string('nama_pelaku', 255)->nullable();
                $table->string('identifier', 50)->nullable(); // NISN or NIP
                $table->decimal('latitude', 10, 8)->nullable();
                $table->decimal('longitude', 11, 8)->nullable();
                $table->integer('jarak_meter')->nullable();
                $table->decimal('akurasi_meter', 8, 2)->nullable();
                $table->string('fraud_type', 60); // 'FAKE_GPS', 'OUTSIDE_GEOFENCE', 'MOCK_PROVIDER', 'ACCURACY_ANOMALY', 'SPEED_ANOMALY', 'DEVICE_EMULATION'
                $table->text('fraud_reason')->nullable();
                $table->jsonb('fraud_details')->nullable();
                $table->text('device_info')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->boolean('is_blocked')->default(true);
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
