<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared("
            ALTER TABLE bk.catatan_bk ADD COLUMN IF NOT EXISTS siswa_id UUID NULL;
            ALTER TABLE bk.catatan_bk ADD COLUMN IF NOT EXISTS topik_masalah VARCHAR(255) NULL;
            ALTER TABLE bk.catatan_bk ADD COLUMN IF NOT EXISTS jenis_konseling VARCHAR(100) NULL;
            ALTER TABLE bk.catatan_bk ADD COLUMN IF NOT EXISTS ringkasan_konseling TEXT NULL;
            ALTER TABLE bk.catatan_bk ADD COLUMN IF NOT EXISTS solusi_tindak_lanjut TEXT NULL;
            ALTER TABLE bk.catatan_bk ADD COLUMN IF NOT EXISTS guru_bk_nama VARCHAR(255) NULL;
            ALTER TABLE bk.catatan_bk ADD COLUMN IF NOT EXISTS is_rahasia SMALLINT DEFAULT 0;
            ALTER TABLE bk.catatan_bk ADD COLUMN IF NOT EXISTS foto_panggilan VARCHAR(512) NULL;
            ALTER TABLE bk.catatan_bk ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP WITH TIME ZONE NULL;

            -- Sinkronisasi kolom lama bila siswa_id masih null
            UPDATE bk.catatan_bk SET siswa_id = id_siswa WHERE siswa_id IS NULL AND id_siswa IS NOT NULL;
            UPDATE bk.catatan_bk SET topik_masalah = nama_catatan_bk WHERE topik_masalah IS NULL AND nama_catatan_bk IS NOT NULL;
            UPDATE bk.catatan_bk SET jenis_konseling = COALESCE(jenis_kasus, kategori, 'Pribadi') WHERE jenis_konseling IS NULL;
            UPDATE bk.catatan_bk SET ringkasan_konseling = COALESCE(catatan, deskripsi) WHERE ringkasan_konseling IS NULL;
            UPDATE bk.catatan_bk SET solusi_tindak_lanjut = tindak_lanjut WHERE solusi_tindak_lanjut IS NULL AND tindak_lanjut IS NOT NULL;
            UPDATE bk.catatan_bk SET tanggal_konseling = CURRENT_DATE WHERE tanggal_konseling IS NULL;
        ");
    }

    public function down(): void
    {
        // No-op for safety
    }
};
