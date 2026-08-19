<?php

/**
 * Migration PostgreSQL: Tambah Kolom Lengkap Data Siswa (Step 1 s/d Step 5)
 * Format: return ['up' => closure, 'down' => closure] (WAJIB AGENTS.md)
 */

return [
    'up' => function (PDO $pdo): void {
        echo "=== MENAMBAHKAN KOLOM LENGKAP PADA SKEMA SISWA (ZERO NEW TABLES) ===\n";

        // 1. Tambah Kolom pada siswa.siswa
        $pdo->exec("
            ALTER TABLE siswa.siswa
                ADD COLUMN IF NOT EXISTS nik VARCHAR(16) NULL,
                ADD COLUMN IF NOT EXISTS no_kk VARCHAR(16) NULL,
                ADD COLUMN IF NOT EXISTS nama_panggilan VARCHAR(100) NULL,
                ADD COLUMN IF NOT EXISTS kewarganegaraan VARCHAR(10) DEFAULT 'WNI',
                ADD COLUMN IF NOT EXISTS bahasa_sehari_hari VARCHAR(50) NULL,
                ADD COLUMN IF NOT EXISTS ukuran_seragam_sekolah VARCHAR(10) NULL,
                ADD COLUMN IF NOT EXISTS ukuran_seragam_olahraga VARCHAR(10) NULL,
                ADD COLUMN IF NOT EXISTS alamat_domisili TEXT NULL,
                ADD COLUMN IF NOT EXISTS rt VARCHAR(5) NULL,
                ADD COLUMN IF NOT EXISTS rw VARCHAR(5) NULL,
                ADD COLUMN IF NOT EXISTS kode_pos VARCHAR(10) NULL,
                ADD COLUMN IF NOT EXISTS id_provinsi INTEGER NULL,
                ADD COLUMN IF NOT EXISTS id_kota INTEGER NULL,
                ADD COLUMN IF NOT EXISTS id_kecamatan INTEGER NULL,
                ADD COLUMN IF NOT EXISTS id_kelurahan BIGINT NULL,
                ADD COLUMN IF NOT EXISTS status_tinggal VARCHAR(50) NULL,
                ADD COLUMN IF NOT EXISTS tinggal_dengan VARCHAR(50) NULL,
                ADD COLUMN IF NOT EXISTS no_telepon_rumah VARCHAR(20) NULL,
                ADD COLUMN IF NOT EXISTS anak_ke SMALLINT NULL,
                ADD COLUMN IF NOT EXISTS jumlah_saudara SMALLINT NULL,
                ADD COLUMN IF NOT EXISTS saudara_tiri SMALLINT NULL,
                ADD COLUMN IF NOT EXISTS saudara_angkat SMALLINT NULL,
                ADD COLUMN IF NOT EXISTS status_anak VARCHAR(50) NULL,
                ADD COLUMN IF NOT EXISTS jarak_rumah INTEGER NULL,
                ADD COLUMN IF NOT EXISTS transportasi VARCHAR(50) NULL,
                ADD COLUMN IF NOT EXISTS penerima_kps BOOLEAN DEFAULT FALSE,
                ADD COLUMN IF NOT EXISTS punya_kip BOOLEAN DEFAULT FALSE,
                ADD COLUMN IF NOT EXISTS layak_kip BOOLEAN DEFAULT FALSE,
                ADD COLUMN IF NOT EXISTS no_kip VARCHAR(50) NULL,
                ADD COLUMN IF NOT EXISTS alasan_layak TEXT NULL;
        ");
        echo "- Kolom tabel siswa.siswa berhasil diperbarui.\n";

        // 2. Tambah Kolom pada siswa.orang_tua
        $pdo->exec("
            ALTER TABLE siswa.orang_tua
                ADD COLUMN IF NOT EXISTS tahun_lahir SMALLINT NULL,
                ADD COLUMN IF NOT EXISTS tanggal_lahir DATE NULL,
                ADD COLUMN IF NOT EXISTS agama VARCHAR(30) NULL,
                ADD COLUMN IF NOT EXISTS kewarganegaraan VARCHAR(10) DEFAULT 'WNI',
                ADD COLUMN IF NOT EXISTS status_hidup VARCHAR(20) DEFAULT 'Hidup',
                ADD COLUMN IF NOT EXISTS hubungan_wali VARCHAR(50) NULL;
        ");
        echo "- Kolom tabel siswa.orang_tua berhasil diperbarui.\n";

        // 3. Tambah Kolom pada siswa.fisik_kesehatan_siswa
        $pdo->exec("
            ALTER TABLE siswa.fisik_kesehatan_siswa
                ADD COLUMN IF NOT EXISTS lingkar_kepala SMALLINT NULL,
                ADD COLUMN IF NOT EXISTS detail_semester JSONB NULL;
        ");
        echo "- Kolom tabel siswa.fisik_kesehatan_siswa berhasil diperbarui.\n";

        // 4. Tambah Kolom pada siswa.registrasi
        $pdo->exec("
            ALTER TABLE siswa.registrasi
                ADD COLUMN IF NOT EXISTS jalur_diterima VARCHAR(50) NULL,
                ADD COLUMN IF NOT EXISTS tanggal_masuk DATE NULL,
                ADD COLUMN IF NOT EXISTS hobi VARCHAR(255) NULL,
                ADD COLUMN IF NOT EXISTS paud_formal BOOLEAN DEFAULT FALSE,
                ADD COLUMN IF NOT EXISTS paud_non_formal BOOLEAN DEFAULT FALSE,
                ADD COLUMN IF NOT EXISTS no_ijazah_sebelumnya VARCHAR(100) NULL,
                ADD COLUMN IF NOT EXISTS tanggal_ijazah_sebelumnya DATE NULL,
                ADD COLUMN IF NOT EXISTS lama_belajar_sebelumnya SMALLINT NULL,
                ADD COLUMN IF NOT EXISTS keluar_karena VARCHAR(50) NULL,
                ADD COLUMN IF NOT EXISTS tanggal_keluar DATE NULL,
                ADD COLUMN IF NOT EXISTS alasan_keluar TEXT NULL,
                ADD COLUMN IF NOT EXISTS sekolah_tujuan VARCHAR(255) NULL,
                ADD COLUMN IF NOT EXISTS nomor_skp VARCHAR(100) NULL,
                ADD COLUMN IF NOT EXISTS tingkat_ditinggalkan VARCHAR(20) NULL,
                ADD COLUMN IF NOT EXISTS diterima_di_tingkat VARCHAR(20) NULL,
                ADD COLUMN IF NOT EXISTS nomor_ijazah_kelulusan VARCHAR(100) NULL,
                ADD COLUMN IF NOT EXISTS nomor_skl VARCHAR(100) NULL,
                ADD COLUMN IF NOT EXISTS keterangan_setelah_lulus TEXT NULL,
                ADD COLUMN IF NOT EXISTS sekolah_asal_mutasi VARCHAR(255) NULL,
                ADD COLUMN IF NOT EXISTS pindah_dari_tingkat VARCHAR(20) NULL,
                ADD COLUMN IF NOT EXISTS pindah_no_surat VARCHAR(100) NULL;
        ");
        echo "- Kolom tabel siswa.registrasi berhasil diperbarui.\n";
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("
            ALTER TABLE siswa.siswa
                DROP COLUMN IF EXISTS nik,
                DROP COLUMN IF EXISTS no_kk,
                DROP COLUMN IF EXISTS nama_panggilan,
                DROP COLUMN IF EXISTS kewarganegaraan,
                DROP COLUMN IF EXISTS bahasa_sehari_hari,
                DROP COLUMN IF EXISTS ukuran_seragam_sekolah,
                DROP COLUMN IF EXISTS ukuran_seragam_olahraga,
                DROP COLUMN IF EXISTS alamat_domisili,
                DROP COLUMN IF EXISTS rt,
                DROP COLUMN IF EXISTS rw,
                DROP COLUMN IF EXISTS kode_pos,
                DROP COLUMN IF EXISTS id_provinsi,
                DROP COLUMN IF EXISTS id_kota,
                DROP COLUMN IF EXISTS id_kecamatan,
                DROP COLUMN IF EXISTS id_kelurahan,
                DROP COLUMN IF EXISTS status_tinggal,
                DROP COLUMN IF EXISTS tinggal_dengan,
                DROP COLUMN IF EXISTS no_telepon_rumah,
                DROP COLUMN IF EXISTS anak_ke,
                DROP COLUMN IF EXISTS jumlah_saudara,
                DROP COLUMN IF EXISTS saudara_tiri,
                DROP COLUMN IF EXISTS saudara_angkat,
                DROP COLUMN IF EXISTS status_anak,
                DROP COLUMN IF EXISTS jarak_rumah,
                DROP COLUMN IF EXISTS transportasi,
                DROP COLUMN IF EXISTS penerima_kps,
                DROP COLUMN IF EXISTS punya_kip,
                DROP COLUMN IF EXISTS layak_kip,
                DROP COLUMN IF EXISTS no_kip,
                DROP COLUMN IF EXISTS alasan_layak;
        ");

        $pdo->exec("
            ALTER TABLE siswa.orang_tua
                DROP COLUMN IF EXISTS tahun_lahir,
                DROP COLUMN IF EXISTS tanggal_lahir,
                DROP COLUMN IF EXISTS agama,
                DROP COLUMN IF EXISTS kewarganegaraan,
                DROP COLUMN IF EXISTS status_hidup,
                DROP COLUMN IF EXISTS hubungan_wali;
        ");

        $pdo->exec("
            ALTER TABLE siswa.fisik_kesehatan_siswa
                DROP COLUMN IF EXISTS lingkar_kepala,
                DROP COLUMN IF EXISTS detail_semester;
        ");

        $pdo->exec("
            ALTER TABLE siswa.registrasi
                DROP COLUMN IF EXISTS jalur_diterima,
                DROP COLUMN IF EXISTS tanggal_masuk,
                DROP COLUMN IF EXISTS hobi,
                DROP COLUMN IF EXISTS paud_formal,
                DROP COLUMN IF EXISTS paud_non_formal,
                DROP COLUMN IF EXISTS no_ijazah_sebelumnya,
                DROP COLUMN IF EXISTS tanggal_ijazah_sebelumnya,
                DROP COLUMN IF EXISTS lama_belajar_sebelumnya,
                DROP COLUMN IF EXISTS keluar_karena,
                DROP COLUMN IF EXISTS tanggal_keluar,
                DROP COLUMN IF EXISTS alasan_keluar,
                DROP COLUMN IF EXISTS sekolah_tujuan,
                DROP COLUMN IF EXISTS nomor_skp,
                DROP COLUMN IF EXISTS tingkat_ditinggalkan,
                DROP COLUMN IF EXISTS diterima_di_tingkat,
                DROP COLUMN IF EXISTS nomor_ijazah_kelulusan,
                DROP COLUMN IF EXISTS nomor_skl,
                DROP COLUMN IF EXISTS keterangan_setelah_lulus,
                DROP COLUMN IF EXISTS sekolah_asal_mutasi,
                DROP COLUMN IF EXISTS pindah_dari_tingkat,
                DROP COLUMN IF EXISTS pindah_no_surat;
        ");
        echo "- Seluruh kolom tambahan siswa berhasil di-rollback.\n";
    }
];
