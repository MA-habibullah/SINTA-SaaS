<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Skema bk.pelanggaran_siswa
        DB::unprepared("
            ALTER TABLE bk.pelanggaran_siswa ADD COLUMN IF NOT EXISTS siswa_id UUID NULL;
            ALTER TABLE bk.pelanggaran_siswa ADD COLUMN IF NOT EXISTS pelanggaran_id UUID NULL;
            ALTER TABLE bk.pelanggaran_siswa ADD COLUMN IF NOT EXISTS nama_pelanggaran VARCHAR(255) NULL;
            ALTER TABLE bk.pelanggaran_siswa ADD COLUMN IF NOT EXISTS poin_pelanggaran INTEGER DEFAULT 5;
            ALTER TABLE bk.pelanggaran_siswa ADD COLUMN IF NOT EXISTS tanggal_kejadian DATE DEFAULT CURRENT_DATE;
            ALTER TABLE bk.pelanggaran_siswa ADD COLUMN IF NOT EXISTS tindakan_hukuman TEXT NULL;
            ALTER TABLE bk.pelanggaran_siswa ADD COLUMN IF NOT EXISTS petugas_pencatat VARCHAR(255) NULL;
            ALTER TABLE bk.pelanggaran_siswa ADD COLUMN IF NOT EXISTS keterangan TEXT NULL;
            ALTER TABLE bk.pelanggaran_siswa ADD COLUMN IF NOT EXISTS snapshot_nama_siswa VARCHAR(255) NULL;
            ALTER TABLE bk.pelanggaran_siswa ADD COLUMN IF NOT EXISTS snapshot_nisn VARCHAR(50) NULL;
            ALTER TABLE bk.pelanggaran_siswa ADD COLUMN IF NOT EXISTS snapshot_nis VARCHAR(50) NULL;
            ALTER TABLE bk.pelanggaran_siswa ADD COLUMN IF NOT EXISTS snapshot_nama_kelas VARCHAR(100) NULL;
            ALTER TABLE bk.pelanggaran_siswa ADD COLUMN IF NOT EXISTS id_kelas_snapshot UUID NULL;
            ALTER TABLE bk.pelanggaran_siswa ADD COLUMN IF NOT EXISTS status_pembinaan VARCHAR(50) DEFAULT 'Belum Dibina';
            ALTER TABLE bk.pelanggaran_siswa ADD COLUMN IF NOT EXISTS surat_panggilan_pdf VARCHAR(512) NULL;
            ALTER TABLE bk.pelanggaran_siswa ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP WITH TIME ZONE NULL;
        ");

        // 2. Skema bk.master_pelanggaran
        DB::unprepared("
            ALTER TABLE bk.master_pelanggaran ADD COLUMN IF NOT EXISTS nama_pelanggaran VARCHAR(255) NULL;
            ALTER TABLE bk.master_pelanggaran ADD COLUMN IF NOT EXISTS bobot_poin INTEGER DEFAULT 5;
            ALTER TABLE bk.master_pelanggaran ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP WITH TIME ZONE NULL;
        ");

        // 3. Pastikan kolom nama_pelanggaran di master_pelanggaran sinkron dengan nama_master_pelanggaran bila kosong
        DB::unprepared("
            UPDATE bk.master_pelanggaran 
            SET nama_pelanggaran = nama_master_pelanggaran 
            WHERE (nama_pelanggaran IS NULL OR nama_pelanggaran = '') AND nama_master_pelanggaran IS NOT NULL;
        ");

        // 4. Update default bobot_poin untuk master pelanggaran default jika masih 0
        DB::unprepared("
            UPDATE bk.master_pelanggaran SET bobot_poin = 5 WHERE kategori = 'Ringan' AND (bobot_poin IS NULL OR bobot_poin = 0);
            UPDATE bk.master_pelanggaran SET bobot_poin = 15 WHERE kategori = 'Sedang' AND (bobot_poin IS NULL OR bobot_poin = 0);
            UPDATE bk.master_pelanggaran SET bobot_poin = 30 WHERE kategori = 'Berat' AND (bobot_poin IS NULL OR bobot_poin = 0);
            UPDATE bk.master_pelanggaran SET bobot_poin = 50 WHERE kategori = 'Khusus' AND (bobot_poin IS NULL OR bobot_poin = 0);
        ");

        // 5. Sinkronisasi data lama pelanggaran_siswa bila nama_pelanggaran masih kosong
        DB::unprepared("
            UPDATE bk.pelanggaran_siswa 
            SET nama_pelanggaran = nama_pelanggaran_siswa 
            WHERE (nama_pelanggaran IS NULL OR nama_pelanggaran = '') AND nama_pelanggaran_siswa IS NOT NULL;
        ");

        // 6. Sinkronisasi keterangan dari deskripsi jika kosong
        DB::unprepared("
            UPDATE bk.pelanggaran_siswa 
            SET keterangan = deskripsi 
            WHERE (keterangan IS NULL OR keterangan = '') AND deskripsi IS NOT NULL;
        ");
    }

    public function down(): void
    {
        // No-op for safety
    }
};
