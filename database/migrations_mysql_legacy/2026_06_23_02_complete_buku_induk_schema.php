<?php
/**
 * Migration: Complete Buku Induk Database Schema
 * 
 * Melengkapi skema database siswa, orang tua, registrasi, nilai, dan beasiswa
 * agar selaras 100% dengan Format Resmi Buku Induk Siswa.
 */
return [

    'up' => function (PDO $pdo): void {
        // Disable foreign key checks temporarily during modifications
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

        // Cek dan tambah kolom-kolom siswa yang tidak bergantung pada kolom lain
        $siswaCols = [
            'kewarganegaraan'           => "ADD COLUMN `kewarganegaraan` VARCHAR(50) DEFAULT 'WNI' AFTER `agama`",
            'bahasa_sehari_hari'        => "ADD COLUMN `bahasa_sehari_hari` VARCHAR(50) DEFAULT 'Indonesia' AFTER `kewarganegaraan`",
            'no_ijazah_sebelumnya'      => "ADD COLUMN `no_ijazah_sebelumnya` VARCHAR(50) DEFAULT NULL AFTER `sekolah_asal`",
            'tanggal_ijazah_sebelumnya' => "ADD COLUMN `tanggal_ijazah_sebelumnya` DATE DEFAULT NULL AFTER `no_ijazah_sebelumnya`",
            'lama_belajar_sebelumnya'   => "ADD COLUMN `lama_belajar_sebelumnya` TINYINT UNSIGNED DEFAULT NULL AFTER `tanggal_ijazah_sebelumnya`",
            'nomor_ijazah_kelulusan'    => null, // ditangani di bawah (butuh AFTER kolom dinamis)
            'nomor_skl'                 => "ADD COLUMN `nomor_skl` VARCHAR(50) DEFAULT NULL AFTER `nomor_ijazah_kelulusan`",
            'keterangan_setelah_lulus'  => "ADD COLUMN `keterangan_setelah_lulus` TEXT DEFAULT NULL AFTER `nomor_skl`",
        ];

        foreach ($siswaCols as $col => $sql) {
            if ($sql === null) continue;
            $exists = $pdo->query("SHOW COLUMNS FROM `siswa` LIKE '$col'")->fetch();
            if (!$exists) {
                try { $pdo->exec("ALTER TABLE `siswa` $sql;"); } catch (\Throwable $e) { /* skip jika kolom referensi belum ada */ }
            }
        }

        // Tambah nomor_ijazah_kelulusan dengan posisi AFTER yang dinamis
        $exists = $pdo->query("SHOW COLUMNS FROM `siswa` LIKE 'nomor_ijazah_kelulusan'")->fetch();
        if (!$exists) {
            $afterCol = 'lama_belajar_sebelumnya';
            if ($pdo->query("SHOW COLUMNS FROM `siswa` LIKE 'tanggal_lulus'")->fetch()) {
                $afterCol = 'tanggal_lulus';
            } elseif ($pdo->query("SHOW COLUMNS FROM `siswa` LIKE 'status'")->fetch()) {
                $afterCol = 'status';
            }
            $pdo->exec("ALTER TABLE `siswa` ADD COLUMN `nomor_ijazah_kelulusan` VARCHAR(50) DEFAULT NULL AFTER `{$afterCol}`;");
        }

        echo "  OK Tabel siswa berhasil diperbarui dengan kolom-kolom baru.\n";

        // 2. Lengkapi tabel rincian_pelajar
        $pdo->exec("
            ALTER TABLE `rincian_pelajar`
            ADD COLUMN `kelainan_jasmani` VARCHAR(255) DEFAULT 'Tidak Ada' AFTER `penyakit_yang_diderita`;
        ");
        echo "  OK Tabel rincian_pelajar berhasil diperbarui.\n";

        // 3. Lengkapi tabel rincian_alamat
        $pdo->exec("
            ALTER TABLE `rincian_alamat`
            ADD COLUMN `tinggal_dengan` ENUM('Orang Tua', 'Wali', 'Kos', 'Asrama', 'Lainnya') DEFAULT 'Orang Tua' AFTER `status_tinggal`;
        ");
        echo "  OK Tabel rincian_alamat berhasil diperbarui.\n";

        // 4. Lengkapi tabel orang_tua
        $pdo->exec("
            ALTER TABLE `orang_tua`
            ADD COLUMN `tanggal_lahir_ayah` DATE DEFAULT NULL AFTER `nama_ayah`,
            ADD COLUMN `kewarganegaraan_ayah` VARCHAR(50) DEFAULT 'WNI' AFTER `agama_ayah`,
            ADD COLUMN `status_hidup_ayah` ENUM('Hidup', 'Meninggal') DEFAULT 'Hidup' AFTER `kewarganegaraan_ayah`,
            ADD COLUMN `tanggal_lahir_ibu` DATE DEFAULT NULL AFTER `nama_ibu`,
            ADD COLUMN `kewarganegaraan_ibu` VARCHAR(50) DEFAULT 'WNI' AFTER `agama_ibu`,
            ADD COLUMN `status_hidup_ibu` ENUM('Hidup', 'Meninggal') DEFAULT 'Hidup' AFTER `kewarganegaraan_ibu`,
            ADD COLUMN `tanggal_lahir_wali` DATE DEFAULT NULL AFTER `nama_wali`,
            ADD COLUMN `kewarganegaraan_wali` VARCHAR(50) DEFAULT NULL AFTER `agama_wali`,
            ADD COLUMN `hubungan_wali` VARCHAR(50) DEFAULT NULL AFTER `kewarganegaraan_wali`;
        ");
        echo "  OK Tabel orang_tua berhasil diperbarui.\n";

        // 5. Lengkapi tabel registrasi
        $pdo->exec("
            ALTER TABLE `registrasi`
            ADD COLUMN `sekolah_tujuan` VARCHAR(255) DEFAULT NULL AFTER `alasan_keluar`,
            ADD COLUMN `nomor_skp` VARCHAR(100) DEFAULT NULL AFTER `sekolah_tujuan`;
        ");
        echo "  OK Tabel registrasi berhasil diperbarui.\n";

        // 6. Lengkapi tabel detail_nilai_rapor
        $pdo->exec("
            ALTER TABLE `detail_nilai_rapor`
            ADD COLUMN `predikat` CHAR(2) DEFAULT NULL AFTER `nilai_akhir`,
            ADD COLUMN `deskripsi` TEXT DEFAULT NULL AFTER `predikat`;
        ");
        echo "  OK Tabel detail_nilai_rapor berhasil diperbarui.\n";

        // 7. Buat tabel nilai_ekstrakurikuler
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `nilai_ekstrakurikuler` (
                `id` INT UNSIGNED AUTO_INCREMENT,
                `tenant_id` CHAR(36) NOT NULL,
                `siswa_id` CHAR(36) NOT NULL,
                `tahun_ajaran_id` INT UNSIGNED NOT NULL,
                `semester` ENUM('Ganjil', 'Genap') NOT NULL,
                `nama_ekstra` VARCHAR(100) NOT NULL,
                `predikat` ENUM('Sangat Baik', 'Baik', 'Cukup', 'Kurang') NOT NULL DEFAULT 'Baik',
                `keterangan` VARCHAR(255) DEFAULT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                CONSTRAINT `fk_ekskul_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_ekskul_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB;
        ");
        echo "  OK Tabel nilai_ekstrakurikuler berhasil dibuat.\n";

        // 8. Buat tabel absensi_semester
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `absensi_semester` (
                `id` INT UNSIGNED AUTO_INCREMENT,
                `tenant_id` CHAR(36) NOT NULL,
                `siswa_id` CHAR(36) NOT NULL,
                `tahun_ajaran_id` INT UNSIGNED NOT NULL,
                `semester` ENUM('Ganjil', 'Genap') NOT NULL,
                `sakit` INT UNSIGNED DEFAULT 0,
                `izin` INT UNSIGNED DEFAULT 0,
                `alfa` INT UNSIGNED DEFAULT 0,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                CONSTRAINT `fk_absensi_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_absensi_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB;
        ");
        echo "  OK Tabel absensi_semester berhasil dibuat.\n";

        // 9. Buat tabel catatan_wali_kelas
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `catatan_wali_kelas` (
                `id` INT UNSIGNED AUTO_INCREMENT,
                `tenant_id` CHAR(36) NOT NULL,
                `siswa_id` CHAR(36) NOT NULL,
                `tahun_ajaran_id` INT UNSIGNED NOT NULL,
                `semester` ENUM('Ganjil', 'Genap') NOT NULL,
                `catatan` TEXT NOT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                CONSTRAINT `fk_catatan_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_catatan_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB;
        ");
        echo "  OK Tabel catatan_wali_kelas berhasil dibuat.\n";

        // 10. Buat tabel nilai_p5
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `nilai_p5` (
                `id` INT UNSIGNED AUTO_INCREMENT,
                `tenant_id` CHAR(36) NOT NULL,
                `siswa_id` CHAR(36) NOT NULL,
                `tahun_ajaran_id` INT UNSIGNED NOT NULL,
                `semester` ENUM('Ganjil', 'Genap') NOT NULL,
                `nama_projek` VARCHAR(255) NOT NULL,
                `deskripsi_projek` TEXT NOT NULL,
                `kualifikasi_karakter` VARCHAR(100) NOT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                CONSTRAINT `fk_p5_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_p5_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB;
        ");
        echo "  OK Tabel nilai_p5 berhasil dibuat.\n";

        // 11. Buat tabel riwayat_beasiswa
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `riwayat_beasiswa` (
                `id` INT UNSIGNED AUTO_INCREMENT,
                `tenant_id` CHAR(36) NOT NULL,
                `siswa_id` CHAR(36) NOT NULL,
                `jenis_beasiswa` VARCHAR(100) NOT NULL,
                `sumber` VARCHAR(100) DEFAULT NULL,
                `tahun_menerima` YEAR(4) NOT NULL,
                `nominal` DECIMAL(12,2) DEFAULT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                CONSTRAINT `fk_beasiswa_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_beasiswa_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB;
        ");
        echo "  OK Tabel riwayat_beasiswa berhasil dibuat.\n";

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

        // Drop new tables
        $pdo->exec("DROP TABLE IF EXISTS `riwayat_beasiswa`;");
        $pdo->exec("DROP TABLE IF EXISTS `nilai_p5`;");
        $pdo->exec("DROP TABLE IF EXISTS `catatan_wali_kelas`;");
        $pdo->exec("DROP TABLE IF EXISTS `absensi_semester`;");
        $pdo->exec("DROP TABLE IF EXISTS `nilai_ekstrakurikuler`;");

        // Drop added columns from existing tables
        $pdo->exec("
            ALTER TABLE `detail_nilai_rapor`
            DROP COLUMN `predikat`,
            DROP COLUMN `deskripsi`;
        ");
        
        $pdo->exec("
            ALTER TABLE `registrasi`
            DROP COLUMN `sekolah_tujuan`,
            DROP COLUMN `nomor_skp`;
        ");

        $pdo->exec("
            ALTER TABLE `orang_tua`
            DROP COLUMN `tanggal_lahir_ayah`,
            DROP COLUMN `kewarganegaraan_ayah`,
            DROP COLUMN `status_hidup_ayah`,
            DROP COLUMN `tanggal_lahir_ibu`,
            DROP COLUMN `kewarganegaraan_ibu`,
            DROP COLUMN `status_hidup_ibu`,
            DROP COLUMN `tanggal_lahir_wali`,
            DROP COLUMN `kewarganegaraan_wali`,
            DROP COLUMN `hubungan_wali`;
        ");

        $pdo->exec("
            ALTER TABLE `rincian_alamat`
            DROP COLUMN `tinggal_dengan`;
        ");

        $pdo->exec("
            ALTER TABLE `rincian_pelajar`
            DROP COLUMN `kelainan_jasmani`;
        ");

        $pdo->exec("
            ALTER TABLE `siswa`
            DROP COLUMN `kewarganegaraan`,
            DROP COLUMN `bahasa_sehari_hari`,
            DROP COLUMN `no_ijazah_sebelumnya`,
            DROP COLUMN `tanggal_ijazah_sebelumnya`,
            DROP COLUMN `lama_belajar_sebelumnya`,
            DROP COLUMN `nomor_ijazah_kelulusan`,
            DROP COLUMN `nomor_skl`,
            DROP COLUMN `keterangan_setelah_lulus`;
        ");

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "  OK Semua tabel dan kolom Buku Induk berhasil di-rollback.\n";
    },
];
