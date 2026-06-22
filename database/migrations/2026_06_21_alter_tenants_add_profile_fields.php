<?php
/**
 * Migration: Alter Tenants Add Profile Fields
 * 
 * Menambahkan kolom-kolom baru pada tabel tenants untuk melengkapi profil
 * sekolah (Identitas Sekolah) seperti alamat, RT/RW, kode pos, pimpinan, operator, logo, dan berkas akreditasi.
 */
return [

    'up' => function (PDO $pdo): void {
        $pdo->exec("
            ALTER TABLE `tenants`
            ADD COLUMN `bentuk_pendidikan` VARCHAR(50) DEFAULT 'SMA' AFTER `nama_sekolah`,
            ADD COLUMN `status_sekolah` VARCHAR(50) DEFAULT 'Negeri' AFTER `bentuk_pendidikan`,
            ADD COLUMN `kurikulum` VARCHAR(100) DEFAULT 'Merdeka' AFTER `status_sekolah`,
            ADD COLUMN `alamat_sekolah` TEXT DEFAULT NULL AFTER `status_sinkronisasi`,
            ADD COLUMN `rt_rw` VARCHAR(20) DEFAULT NULL AFTER `alamat_sekolah`,
            ADD COLUMN `kode_pos` VARCHAR(10) DEFAULT NULL AFTER `rt_rw`,
            ADD COLUMN `kelurahan` VARCHAR(100) DEFAULT NULL AFTER `kode_pos`,
            ADD COLUMN `kecamatan` VARCHAR(100) DEFAULT 'Kec. Tandes' AFTER `kelurahan`,
            ADD COLUMN `kabupaten_kota` VARCHAR(100) DEFAULT 'Kota Surabaya' AFTER `kecamatan`,
            ADD COLUMN `provinsi` VARCHAR(100) DEFAULT 'Prov. Jawa Timur' AFTER `kabupaten_kota`,
            ADD COLUMN `no_telp` VARCHAR(20) DEFAULT NULL AFTER `provinsi`,
            ADD COLUMN `email_sekolah` VARCHAR(100) DEFAULT NULL AFTER `no_telp`,
            ADD COLUMN `website` VARCHAR(255) DEFAULT NULL AFTER `email_sekolah`,
            ADD COLUMN `nama_kepsek` VARCHAR(255) DEFAULT 'Nana Petty Puspitasari' AFTER `website`,
            ADD COLUMN `nip_kepsek` VARCHAR(50) DEFAULT NULL AFTER `nama_kepsek`,
            ADD COLUMN `nama_operator` VARCHAR(255) DEFAULT 'Edi Sugiarto' AFTER `nip_kepsek`,
            ADD COLUMN `email_operator` VARCHAR(100) DEFAULT 'aidasugiarto@gmail.com' AFTER `nama_operator`,
            ADD COLUMN `logo` VARCHAR(255) DEFAULT NULL AFTER `email_operator`,
            ADD COLUMN `sertifikat_akreditasi` VARCHAR(255) DEFAULT NULL AFTER `logo`;
        ");
        echo "  OK Kolom-kolom baru berhasil ditambahkan pada tabel tenants.\n";
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("
            ALTER TABLE `tenants`
            DROP COLUMN `bentuk_pendidikan`,
            DROP COLUMN `status_sekolah`,
            DROP COLUMN `kurikulum`,
            DROP COLUMN `alamat_sekolah`,
            DROP COLUMN `rt_rw`,
            DROP COLUMN `kode_pos`,
            DROP COLUMN `kelurahan`,
            DROP COLUMN `kecamatan`,
            DROP COLUMN `kabupaten_kota`,
            DROP COLUMN `provinsi`,
            DROP COLUMN `no_telp`,
            DROP COLUMN `email_sekolah`,
            DROP COLUMN `website`,
            DROP COLUMN `nama_kepsek`,
            DROP COLUMN `nip_kepsek`,
            DROP COLUMN `nama_operator`,
            DROP COLUMN `email_operator`,
            DROP COLUMN `logo`,
            DROP COLUMN `sertifikat_akreditasi`;
        ");
        echo "  OK Kolom-kolom profil berhasil dihapus dari tabel tenants.\n";
    },
];
