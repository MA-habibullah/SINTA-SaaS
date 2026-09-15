<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Surat Masuk
        DB::statement("
            ALTER TABLE persuratan.surat_masuk 
            ADD COLUMN IF NOT EXISTS nomor_agenda VARCHAR(100) NULL,
            ADD COLUMN IF NOT EXISTS nomor_surat_asal VARCHAR(150) NULL,
            ADD COLUMN IF NOT EXISTS tanggal_surat DATE NULL,
            ADD COLUMN IF NOT EXISTS tanggal_diterima DATE NULL,
            ADD COLUMN IF NOT EXISTS pengirim VARCHAR(255) NULL,
            ADD COLUMN IF NOT EXISTS perihal VARCHAR(500) NULL,
            ADD COLUMN IF NOT EXISTS kategori_surat VARCHAR(100) DEFAULT 'Dinas',
            ADD COLUMN IF NOT EXISTS sifat_surat VARCHAR(50) DEFAULT 'Biasa',
            ADD COLUMN IF NOT EXISTS file_surat_url VARCHAR(512) NULL,
            ADD COLUMN IF NOT EXISTS status_disposisi VARCHAR(50) DEFAULT 'Belum Disposisi';
        ");

        // 2. Surat Keluar
        DB::statement("
            ALTER TABLE persuratan.surat_keluar 
            ADD COLUMN IF NOT EXISTS nomor_surat VARCHAR(150) NULL,
            ADD COLUMN IF NOT EXISTS klasifikasi_kode VARCHAR(100) NULL,
            ADD COLUMN IF NOT EXISTS tujuan_surat VARCHAR(255) NULL,
            ADD COLUMN IF NOT EXISTS tanggal_surat DATE NULL,
            ADD COLUMN IF NOT EXISTS perihal VARCHAR(500) NULL,
            ADD COLUMN IF NOT EXISTS ringkasan_isi TEXT NULL,
            ADD COLUMN IF NOT EXISTS penandatangan_nama VARCHAR(255) NULL,
            ADD COLUMN IF NOT EXISTS penandatangan_jabatan VARCHAR(255) DEFAULT 'Kepala Sekolah',
            ADD COLUMN IF NOT EXISTS status_surat VARCHAR(50) DEFAULT 'Diterbitkan',
            ADD COLUMN IF NOT EXISTS file_surat_url VARCHAR(512) NULL;
        ");

        // 3. Disposisi Surat
        DB::statement("
            ALTER TABLE persuratan.disposisi_surat 
            ADD COLUMN IF NOT EXISTS surat_masuk_id UUID NULL,
            ADD COLUMN IF NOT EXISTS dari_jabatan VARCHAR(150) NULL,
            ADD COLUMN IF NOT EXISTS diteruskan_kepada VARCHAR(150) NULL,
            ADD COLUMN IF NOT EXISTS instruksi_disposisi VARCHAR(500) NULL,
            ADD COLUMN IF NOT EXISTS catatan_tambahan TEXT NULL,
            ADD COLUMN IF NOT EXISTS batas_waktu_tindak_lanjut DATE NULL,
            ADD COLUMN IF NOT EXISTS status_tindak_lanjut VARCHAR(50) DEFAULT 'Menunggu';
        ");
    }

    public function down(): void
    {
        // No down rollback required
    }
};
