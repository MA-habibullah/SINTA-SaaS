<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sistem.kategori_agenda')) {
            Schema::create('sistem.kategori_agenda', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('tenant_id')->nullable()->index();
                $table->string('nama_kategori', 100);
                $table->timestamps();
            });

            // Seed default master categories
            $defaults = [
                'Akademik & Pembelajaran',
                'Kedinasan & Rapat',
                'Kesiswaan & Ekskul',
                'Ujian & Asesmen',
                'Hari Libur & Peringatan',
                'Keagamaan & Ibadah',
                'Kunjungan & Studi Lapangan',
            ];

            foreach ($defaults as $kat) {
                DB::table('sistem.kategori_agenda')->insert([
                    'id'            => (string) Str::uuid(),
                    'tenant_id'     => null,
                    'nama_kategori' => $kat,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sistem.kategori_agenda');
    }
};
