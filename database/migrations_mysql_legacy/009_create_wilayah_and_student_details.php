<?php
/**
 * Migration 009 - Create Wilayah and Student Details Tables
 * Membuat tabel provinsi, kota, kecamatan, kelurahan, rincian_alamat, dokumen, kip, kontak, orang_tua, rincian_pelajar, registrasi, dan pengaturan.
 */

return [
    'up' => function(PDO $pdo) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

        // 1. Tabel Provinsi
        $pdo->exec("CREATE TABLE IF NOT EXISTS provinsi (
            id_provinsi TINYINT(3) UNSIGNED NOT NULL AUTO_INCREMENT,
            nama_provinsi VARCHAR(50) NOT NULL,
            PRIMARY KEY (id_provinsi)
        ) ENGINE=InnoDB;");

        // 2. Tabel Kota
        $pdo->exec("CREATE TABLE IF NOT EXISTS kota (
            id_kota SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
            id_provinsi TINYINT(3) UNSIGNED NOT NULL,
            nama_kota VARCHAR(50) NOT NULL,
            PRIMARY KEY (id_kota),
            CONSTRAINT fk_kota_id_provinsi FOREIGN KEY (id_provinsi) REFERENCES provinsi (id_provinsi) ON DELETE CASCADE ON UPDATE CASCADE,
            INDEX idx_kota_provinsi (id_provinsi)
        ) ENGINE=InnoDB;");

        // 3. Tabel Kecamatan
        $pdo->exec("CREATE TABLE IF NOT EXISTS kecamatan (
            id_kecamatan SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
            id_kota SMALLINT(5) UNSIGNED NOT NULL,
            nama_kecamatan VARCHAR(50) NOT NULL,
            PRIMARY KEY (id_kecamatan),
            CONSTRAINT fk_kecamatan_id_kota FOREIGN KEY (id_kota) REFERENCES kota (id_kota) ON DELETE CASCADE ON UPDATE CASCADE,
            INDEX idx_kecamatan_kota (id_kota)
        ) ENGINE=InnoDB;");

        // 4. Tabel Kelurahan
        $pdo->exec("CREATE TABLE IF NOT EXISTS kelurahan (
            id_kelurahan INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            id_kecamatan SMALLINT(5) UNSIGNED NOT NULL,
            nama_kelurahan VARCHAR(50) NOT NULL,
            PRIMARY KEY (id_kelurahan),
            CONSTRAINT fk_kelurahan_id_kecamatan FOREIGN KEY (id_kecamatan) REFERENCES kecamatan (id_kecamatan) ON DELETE CASCADE ON UPDATE CASCADE,
            INDEX idx_kelurahan_kecamatan (id_kecamatan)
        ) ENGINE=InnoDB;");

        // 5. Tabel Rincian Alamat
        $pdo->exec("CREATE TABLE IF NOT EXISTS rincian_alamat (
            id_rincian_alamat INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            id_siswa CHAR(36) NOT NULL,
            id_kelurahan INT(10) UNSIGNED NOT NULL,
            alamat_kk TEXT NOT NULL COMMENT 'Alamat Kartu Keluarga',
            alamat_domisili TEXT NULL COMMENT 'Alamat Domisili',
            rt CHAR(3) NOT NULL COMMENT 'Rukun Tetangga',
            rw CHAR(3) NOT NULL COMMENT 'Rukun Warga',
            kode_pos CHAR(5) NOT NULL,
            status_tinggal ENUM('Asrama Sekolah','Kontrak / Sewa','Kos','Lainnya','Menumpang','Milik Sendiri','Rumah Dinas') NOT NULL,
            PRIMARY KEY (id_rincian_alamat),
            CONSTRAINT fk_rincian_alamat_id_siswa FOREIGN KEY (id_siswa) REFERENCES siswa (id) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT fk_rincian_alamat_id_kelurahan FOREIGN KEY (id_kelurahan) REFERENCES kelurahan (id_kelurahan) ON DELETE RESTRICT ON UPDATE CASCADE,
            INDEX idx_rincian_alamat_siswa (id_siswa),
            INDEX idx_rincian_alamat_kelurahan (id_kelurahan)
        ) ENGINE=InnoDB;");

        // 6. Tabel Dokumen
        $pdo->exec("CREATE TABLE IF NOT EXISTS dokumen (
            id_dokumen INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            id_siswa CHAR(36) NOT NULL,
            berkas_kk VARCHAR(100) DEFAULT NULL COMMENT 'Berkas Kartu Keluarga',
            berkas_akta VARCHAR(100) DEFAULT NULL COMMENT 'Berkas Akta',
            berkas_ijazah_sd VARCHAR(100) DEFAULT NULL COMMENT 'Berkas Sekolah Dasar',
            berkas_ijazah_smp VARCHAR(100) DEFAULT NULL COMMENT 'Berkas Sekolah Menengah Pertama',
            berkas_ijazah_sma VARCHAR(100) DEFAULT NULL COMMENT 'Berkas Sekolah Menengah Atas',
            berkas_mutasi_masuk VARCHAR(100) DEFAULT NULL COMMENT 'Berkas mutasi masuk',
            berkas_mutasi_keluar VARCHAR(100) DEFAULT NULL COMMENT 'Berkas mutasi keluar',
            berkas_kip VARCHAR(100) DEFAULT NULL COMMENT 'Berkas Kartu Indonesia Pintar',
            PRIMARY KEY (id_dokumen),
            CONSTRAINT fk_dokumen_id_siswa FOREIGN KEY (id_siswa) REFERENCES siswa (id) ON DELETE CASCADE ON UPDATE CASCADE,
            INDEX idx_dokumen_siswa (id_siswa)
        ) ENGINE=InnoDB;");

        // 7. Tabel KIP
        $pdo->exec("CREATE TABLE IF NOT EXISTS kip (
            id_kip INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            id_siswa CHAR(36) NOT NULL,
            penerima_kps TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Penerima Keluarga Penerima Sejahtera',
            punya_kip TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Punya Kartu Indonesia Pintar',
            layak_kip TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Layak Kartu Indonesia Pintar',
            alasan_layak ENUM('Daerah Konflik','Dampak Bencana Alam','Kelainan Fisik','Keluarga Terpidana / Berada di LAPAS','Pemegang PKH / KPS / KKS','Pernah Drop Out','Siswa Miskin','Tidak Ada') NOT NULL,
            no_kip CHAR(15) NOT NULL COMMENT 'Nomor Kartu Indonesia Pintar',
            status_anak ENUM('Piatu','Yatim','Yatim Piatu') NOT NULL,
            PRIMARY KEY (id_kip),
            CONSTRAINT fk_kip_id_siswa FOREIGN KEY (id_siswa) REFERENCES siswa (id) ON DELETE CASCADE ON UPDATE CASCADE,
            INDEX idx_kip_siswa (id_siswa)
        ) ENGINE=InnoDB;");

        // 8. Tabel Kontak
        $pdo->exec("CREATE TABLE IF NOT EXISTS kontak (
            id_kontak INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            id_siswa CHAR(36) NOT NULL,
            email VARCHAR(191) NOT NULL,
            no_telepon_rumah VARCHAR(10) DEFAULT NULL,
            no_telepon_orang_tua VARCHAR(13) DEFAULT NULL,
            no_telepon_siswa VARCHAR(13) NOT NULL,
            PRIMARY KEY (id_kontak),
            CONSTRAINT fk_kontak_id_siswa FOREIGN KEY (id_siswa) REFERENCES siswa (id) ON DELETE CASCADE ON UPDATE CASCADE,
            INDEX idx_kontak_siswa (id_siswa)
        ) ENGINE=InnoDB;");

        // 9. Tabel Orang Tua
        $pdo->exec("CREATE TABLE IF NOT EXISTS orang_tua (
            id_orang_tua INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            id_siswa CHAR(36) NOT NULL,
            id_tempat_lahir_ayah SMALLINT(5) UNSIGNED DEFAULT NULL,
            nik_ayah CHAR(16) DEFAULT NULL COMMENT 'Nomor Induk Kependudukan ayah',
            nama_ayah VARCHAR(60) DEFAULT NULL,
            tahun_lahir_ayah YEAR(4) DEFAULT NULL,
            pendidikan_ayah ENUM('Tidak Tamat Sekolah','SD','SMP','SMA','D3','D4','S1','S2','S3') DEFAULT NULL,
            pekerjaan_ayah ENUM('Buruh','Dokter / Perawat','Guru / Dosen','Meninggal','Nelayan','Pedagang','Pegawai Swasta','Petani','PNS / TNI / Polri','Tidak Bekerja','Wiraswasta') DEFAULT NULL,
            penghasilan_ayah ENUM('Tidak Berpenghasilan','Kurang dari Rp500.000','Rp500.000 sampai Rp999.999','Rp1.000.000 sampai Rp1.999.999','Rp2.000.000 sampai Rp4.999.999','Rp5.000.000 sampai Rp20.000.000','Lebih dari Rp20.000.000') DEFAULT NULL,
            agama_ayah ENUM('Buddha','Hindu','Islam','Katolik','Khonghucu','Kristen') DEFAULT NULL,
            id_tempat_lahir_ibu SMALLINT(5) UNSIGNED NOT NULL,
            nik_ibu CHAR(16) NOT NULL COMMENT 'Nomor Induk Kependudukan ibu',
            nama_ibu VARCHAR(60) NOT NULL,
            tahun_lahir_ibu YEAR(4) NOT NULL,
            pendidikan_ibu ENUM('Tidak Tamat Sekolah','SD','SMP','SMA','D3','D4','S1','S2','S3') NOT NULL,
            pekerjaan_ibu ENUM('Buruh','Dokter / Perawat','Guru / Dosen','Meninggal','Nelayan','Pedagang','Pegawai Swasta','Petani','PNS / TNI / Polri','Tidak Bekerja','Wiraswasta') NOT NULL,
            penghasilan_ibu ENUM('Tidak Berpenghasilan','Kurang dari Rp500.000','Rp500.000 sampai Rp999.999','Rp1.000.000 sampai Rp1.999.999','Rp2.000.000 sampai Rp4.999.999','Rp5.000.000 sampai Rp20.000.000','Lebih dari Rp20.000.000') NOT NULL,
            agama_ibu ENUM('Buddha','Hindu','Islam','Katolik','Khonghucu','Kristen') NOT NULL,
            id_tempat_lahir_wali SMALLINT(5) UNSIGNED DEFAULT NULL,
            nik_wali CHAR(16) DEFAULT NULL COMMENT 'Nomor Induk Kependudukan wali',
            nama_wali VARCHAR(255) DEFAULT NULL,
            tahun_lahir_wali YEAR(4) DEFAULT NULL,
            pendidikan_wali ENUM('Tidak Tamat Sekolah','SD','SMP','SMA','D3','D4','S1','S2','S3') DEFAULT NULL,
            pekerjaan_wali ENUM('Buruh','Dokter / Perawat','Guru / Dosen','Meninggal','Nelayan','Pedagang','Pegawai Swasta','Petani','PNS / TNI / Polri','Tidak Bekerja','Wiraswasta') DEFAULT NULL,
            penghasilan_wali ENUM('Tidak Berpenghasilan','Kurang dari Rp500.000','Rp500.000 sampai Rp999.999','Rp1.000.000 sampai Rp1.999.999','Rp2.000.000 sampai Rp4.999.999','Rp5.000.000 sampai Rp20.000.000','Lebih dari Rp20.000.000') DEFAULT NULL,
            agama_wali ENUM('Buddha','Hindu','Islam','Katolik','Khonghucu','Kristen') DEFAULT NULL,
            PRIMARY KEY (id_orang_tua),
            CONSTRAINT fk_orang_tua_id_siswa FOREIGN KEY (id_siswa) REFERENCES siswa (id) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT fk_orang_tua_ayah_lahir FOREIGN KEY (id_tempat_lahir_ayah) REFERENCES kota (id_kota) ON DELETE SET NULL ON UPDATE CASCADE,
            CONSTRAINT fk_orang_tua_ibu_lahir FOREIGN KEY (id_tempat_lahir_ibu) REFERENCES kota (id_kota) ON DELETE RESTRICT ON UPDATE CASCADE,
            CONSTRAINT fk_orang_tua_wali_lahir FOREIGN KEY (id_tempat_lahir_wali) REFERENCES kota (id_kota) ON DELETE SET NULL ON UPDATE CASCADE,
            INDEX idx_orang_tua_siswa (id_siswa)
        ) ENGINE=InnoDB;");

        // 10. Tabel Rincian Pelajar
        $pdo->exec("CREATE TABLE IF NOT EXISTS rincian_pelajar (
            id_rincian_pelajar INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            id_siswa CHAR(36) NOT NULL,
            lingkar_kepala TINYINT(3) UNSIGNED NOT NULL,
            tinggi_badan TINYINT(3) UNSIGNED NOT NULL,
            berat_badan TINYINT(3) UNSIGNED NOT NULL,
            golongan_darah ENUM('A','AB','B','O') NOT NULL,
            anak_ke TINYINT(3) UNSIGNED NOT NULL,
            jarak_rumah SMALLINT(5) UNSIGNED NOT NULL COMMENT 'Dalam meter',
            transportasi ENUM('Angkutan Umum','Antar Jemput','Jalan Kaki','Lainnya','Mobil','Motor','Sepeda') NOT NULL,
            jumlah_saudara TINYINT(3) UNSIGNED NOT NULL COMMENT 'Saudara kandung',
            penyakit_yang_diderita TINYTEXT DEFAULT NULL,
            foto_profil VARCHAR(100) DEFAULT NULL,
            PRIMARY KEY (id_rincian_pelajar),
            CONSTRAINT fk_rincian_pelajar_id_siswa FOREIGN KEY (id_siswa) REFERENCES siswa (id) ON DELETE CASCADE ON UPDATE CASCADE,
            INDEX idx_rincian_pelajar_siswa (id_siswa)
        ) ENGINE=InnoDB;");

        // 11. Tabel Registrasi
        $pdo->exec("CREATE TABLE IF NOT EXISTS registrasi (
            id_registrasi INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            id_siswa CHAR(36) NOT NULL,
            jalur_diterima ENUM('Afirmasi','Anak Guru / Tenaga Kependidikan','Khusus','Perpindahan Tugas','Prestasi Akademik','Prestasi Non-akademik','Zonasi') DEFAULT NULL,
            jenis_pendaftaran ENUM('Kembali Sekolah','Pindahan','Siswa Baru') NOT NULL,
            tanggal_masuk DATE NOT NULL,
            paud_formal TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Pendidikan Anak Usia Dini formal',
            paud_non_formal TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Pendidikan Anak Usia Dini non-formal',
            hobi TINYTEXT NOT NULL,
            keluar_karena ENUM('Dikeluarkan','Hilang','Mengundurkan Diri','Mutasi','Putus Sekolah','Wafat') DEFAULT NULL,
            tanggal_keluar DATE DEFAULT NULL,
            alasan_keluar TINYTEXT DEFAULT NULL,
            PRIMARY KEY (id_registrasi),
            CONSTRAINT fk_registrasi_id_siswa FOREIGN KEY (id_siswa) REFERENCES siswa (id) ON DELETE CASCADE ON UPDATE CASCADE,
            INDEX idx_registrasi_siswa (id_siswa)
        ) ENGINE=InnoDB;");

        // 12. Tabel Pengaturan
        $pdo->exec("CREATE TABLE IF NOT EXISTS pengaturan (
            id_pengaturan TINYINT(3) UNSIGNED NOT NULL AUTO_INCREMENT,
            nama_pengaturan VARCHAR(30) NOT NULL,
            nilai VARCHAR(191) DEFAULT NULL,
            PRIMARY KEY (id_pengaturan),
            UNIQUE KEY uq_pengaturan_nama (nama_pengaturan)
        ) ENGINE=InnoDB;");

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    },

    'down' => function(PDO $pdo) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec("DROP TABLE IF EXISTS registrasi;");
        $pdo->exec("DROP TABLE IF EXISTS rincian_pelajar;");
        $pdo->exec("DROP TABLE IF EXISTS orang_tua;");
        $pdo->exec("DROP TABLE IF EXISTS kontak;");
        $pdo->exec("DROP TABLE IF EXISTS kip;");
        $pdo->exec("DROP TABLE IF EXISTS dokumen;");
        $pdo->exec("DROP TABLE IF EXISTS rincian_alamat;");
        $pdo->exec("DROP TABLE IF EXISTS kelurahan;");
        $pdo->exec("DROP TABLE IF EXISTS kecamatan;");
        $pdo->exec("DROP TABLE IF EXISTS kota;");
        $pdo->exec("DROP TABLE IF EXISTS provinsi;");
        $pdo->exec("DROP TABLE IF EXISTS pengaturan;");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }
];
