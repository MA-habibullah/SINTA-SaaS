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
        // 1. Kolom lampiran berkas pada sistem.pengumuman
        if (Schema::hasTable('sistem.pengumuman')) {
            Schema::table('sistem.pengumuman', function (Blueprint $table) {
                if (!Schema::hasColumn('sistem.pengumuman', 'lampiran_url')) {
                    $table->string('lampiran_url', 500)->nullable();
                }
                if (!Schema::hasColumn('sistem.pengumuman', 'lampiran_nama')) {
                    $table->string('lampiran_nama', 255)->nullable();
                }
                if (!Schema::hasColumn('sistem.pengumuman', 'lampiran_ukuran')) {
                    $table->bigInteger('lampiran_ukuran')->nullable();
                }
                if (!Schema::hasColumn('sistem.pengumuman', 'lampiran_tipe')) {
                    $table->string('lampiran_tipe', 100)->nullable();
                }
            });
        }

        // 2. Kolom lampiran berkas pada sistem.agenda_sekolah
        if (Schema::hasTable('sistem.agenda_sekolah')) {
            Schema::table('sistem.agenda_sekolah', function (Blueprint $table) {
                if (!Schema::hasColumn('sistem.agenda_sekolah', 'lampiran_url')) {
                    $table->string('lampiran_url', 500)->nullable();
                }
                if (!Schema::hasColumn('sistem.agenda_sekolah', 'lampiran_nama')) {
                    $table->string('lampiran_nama', 255)->nullable();
                }
                if (!Schema::hasColumn('sistem.agenda_sekolah', 'lampiran_ukuran')) {
                    $table->bigInteger('lampiran_ukuran')->nullable();
                }
                if (!Schema::hasColumn('sistem.agenda_sekolah', 'lampiran_tipe')) {
                    $table->string('lampiran_tipe', 100)->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('sistem.pengumuman')) {
            Schema::table('sistem.pengumuman', function (Blueprint $table) {
                $table->dropColumn(['lampiran_url', 'lampiran_nama', 'lampiran_ukuran', 'lampiran_tipe']);
            });
        }

        if (Schema::hasTable('sistem.agenda_sekolah')) {
            Schema::table('sistem.agenda_sekolah', function (Blueprint $table) {
                $table->dropColumn(['lampiran_url', 'lampiran_nama', 'lampiran_ukuran', 'lampiran_tipe']);
            });
        }
    }
};
