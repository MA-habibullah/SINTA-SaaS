<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Hapus root menu lama yang tidak memiliki anak dan bukan menu direct url
        $emptyRoots = DB::table('core.menus as m')
            ->whereNull('m.parent_id')
            ->where('m.url', '#')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('core.menus as c')
                  ->whereRaw('c.parent_id = m.id');
            })
            ->pluck('id')
            ->toArray();

        if (!empty($emptyRoots)) {
            DB::table('core.menus')->whereIn('id', $emptyRoots)->delete();
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
