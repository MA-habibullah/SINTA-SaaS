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
        if (Schema::hasTable('akademik.pemetaan_mapel')) {
            Schema::table('akademik.pemetaan_mapel', function (Blueprint $table) {
                if (!Schema::hasColumn('akademik.pemetaan_mapel', 'hari')) {
                    $table->string('hari', 20)->nullable()->after('jam_pelajaran');
                }
                if (!Schema::hasColumn('akademik.pemetaan_mapel', 'jam_mulai')) {
                    $table->string('jam_mulai', 10)->nullable()->after('hari');
                }
                if (!Schema::hasColumn('akademik.pemetaan_mapel', 'jam_selesai')) {
                    $table->string('jam_selesai', 10)->nullable()->after('jam_mulai');
                }
                if (!Schema::hasColumn('akademik.pemetaan_mapel', 'ruangan')) {
                    $table->string('ruangan', 100)->nullable()->after('jam_selesai');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('akademik.pemetaan_mapel')) {
            Schema::table('akademik.pemetaan_mapel', function (Blueprint $table) {
                $table->dropColumn(['hari', 'jam_mulai', 'jam_selesai', 'ruangan']);
            });
        }
    }
};
