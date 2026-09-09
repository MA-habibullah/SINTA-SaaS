<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('core.tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('core.tenants', 'enable_ppdb')) {
                $table->smallInteger('enable_ppdb')->default(1)->nullable();
            }
            if (!Schema::hasColumn('core.tenants', 'enable_perpustakaan')) {
                $table->smallInteger('enable_perpustakaan')->default(1)->nullable();
            }
            if (!Schema::hasColumn('core.tenants', 'enable_keuangan')) {
                $table->smallInteger('enable_keuangan')->default(1)->nullable();
            }
            if (!Schema::hasColumn('core.tenants', 'enable_pdss')) {
                $table->smallInteger('enable_pdss')->default(1)->nullable();
            }
            if (!Schema::hasColumn('core.tenants', 'enable_smk')) {
                $table->smallInteger('enable_smk')->default(1)->nullable();
            }
            if (!Schema::hasColumn('core.tenants', 'enable_sarpras')) {
                $table->smallInteger('enable_sarpras')->default(1)->nullable();
            }
            if (!Schema::hasColumn('core.tenants', 'enable_persuratan')) {
                $table->smallInteger('enable_persuratan')->default(1)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('core.tenants', function (Blueprint $table) {
            $table->dropColumn([
                'enable_ppdb',
                'enable_perpustakaan',
                'enable_keuangan',
                'enable_pdss',
                'enable_smk',
                'enable_sarpras',
                'enable_persuratan',
            ]);
        });
    }
};
