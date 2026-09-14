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
                if (!Schema::hasColumn('akademik.pemetaan_mapel', 'jam_ke')) {
                    $table->string('jam_ke', 50)->nullable()->after('jam_pelajaran');
                }
                if (!Schema::hasColumn('akademik.pemetaan_mapel', 'warna_label')) {
                    $table->string('warna_label', 20)->nullable()->default('#3b82f6')->after('ruangan');
                }
                if (!Schema::hasColumn('akademik.pemetaan_mapel', 'catatan')) {
                    $table->text('catatan')->nullable()->after('warna_label');
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
                $cols = [];
                if (Schema::hasColumn('akademik.pemetaan_mapel', 'jam_ke')) $cols[] = 'jam_ke';
                if (Schema::hasColumn('akademik.pemetaan_mapel', 'warna_label')) $cols[] = 'warna_label';
                if (Schema::hasColumn('akademik.pemetaan_mapel', 'catatan')) $cols[] = 'catatan';
                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }
    }
};
