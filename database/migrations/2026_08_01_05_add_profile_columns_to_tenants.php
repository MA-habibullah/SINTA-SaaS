<?php

return [
    'up' => function (PDO $pdo): void {
        $pdo->exec("
            ALTER TABLE core.tenants 
            ADD COLUMN IF NOT EXISTS logo TEXT,
            ADD COLUMN IF NOT EXISTS sertifikat_akreditasi TEXT,
            ADD COLUMN IF NOT EXISTS bentuk_pendidikan VARCHAR(50) DEFAULT 'SMA',
            ADD COLUMN IF NOT EXISTS status_sekolah VARCHAR(50) DEFAULT 'Negeri',
            ADD COLUMN IF NOT EXISTS kurikulum_terapan VARCHAR(100) DEFAULT 'Merdeka',
            ADD COLUMN IF NOT EXISTS akreditasi VARCHAR(50) DEFAULT 'A (Unggul)',
            ADD COLUMN IF NOT EXISTS alamat TEXT,
            ADD COLUMN IF NOT EXISTS rt_rw VARCHAR(50),
            ADD COLUMN IF NOT EXISTS kode_pos VARCHAR(10),
            ADD COLUMN IF NOT EXISTS kelurahan VARCHAR(100),
            ADD COLUMN IF NOT EXISTS kecamatan VARCHAR(100),
            ADD COLUMN IF NOT EXISTS kabupaten_kota VARCHAR(100),
            ADD COLUMN IF NOT EXISTS provinsi VARCHAR(100),
            ADD COLUMN IF NOT EXISTS telepon VARCHAR(50),
            ADD COLUMN IF NOT EXISTS email VARCHAR(255),
            ADD COLUMN IF NOT EXISTS website VARCHAR(255),
            ADD COLUMN IF NOT EXISTS nama_kepsek VARCHAR(255),
            ADD COLUMN IF NOT EXISTS pangkat_kepsek VARCHAR(100),
            ADD COLUMN IF NOT EXISTS nip_kepsek VARCHAR(50),
            ADD COLUMN IF NOT EXISTS nama_operator VARCHAR(255),
            ADD COLUMN IF NOT EXISTS email_operator VARCHAR(255);
        ");

        echo "- Added complete school identity profile columns to core.tenants table.\n";
    },
    'down' => function (PDO $pdo): void {
        $pdo->exec("
            ALTER TABLE core.tenants 
            DROP COLUMN IF EXISTS logo,
            DROP COLUMN IF EXISTS sertifikat_akreditasi,
            DROP COLUMN IF EXISTS bentuk_pendidikan,
            DROP COLUMN IF EXISTS status_sekolah,
            DROP COLUMN IF EXISTS kurikulum_terapan,
            DROP COLUMN IF EXISTS akreditasi,
            DROP COLUMN IF EXISTS alamat,
            DROP COLUMN IF EXISTS rt_rw,
            DROP COLUMN IF EXISTS kode_pos,
            DROP COLUMN IF EXISTS kelurahan,
            DROP COLUMN IF EXISTS kecamatan,
            DROP COLUMN IF EXISTS kabupaten_kota,
            DROP COLUMN IF EXISTS provinsi,
            DROP COLUMN IF EXISTS telepon,
            DROP COLUMN IF EXISTS email,
            DROP COLUMN IF EXISTS website,
            DROP COLUMN IF EXISTS nama_kepsek,
            DROP COLUMN IF EXISTS pangkat_kepsek,
            DROP COLUMN IF EXISTS nip_kepsek,
            DROP COLUMN IF EXISTS nama_operator,
            DROP COLUMN IF EXISTS email_operator;
        ");
    },
];
