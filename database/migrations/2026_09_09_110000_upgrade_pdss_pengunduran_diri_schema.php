<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared("
            ALTER TABLE pdss.pengunduran_diri ALTER COLUMN path_file DROP NOT NULL;
        ");
    }

    public function down(): void
    {
        // No action needed
    }
};
