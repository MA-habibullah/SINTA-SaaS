<?php
/**
 * Migration: Create Library Module Tables (Integrated Library System)
 * Prefix: perpus_
 */

return [
    'up' => function (PDO $pdo): void {
        // 1. Alter tenants table
        try {
            $pdo->query("SELECT enable_perpustakaan FROM tenants LIMIT 1");
        } catch (\PDOException $e) {
            $pdo->exec("ALTER TABLE tenants 
                ADD COLUMN enable_perpustakaan TINYINT(1) NOT NULL DEFAULT 0 AFTER enable_tracer,
                ADD COLUMN max_koleksi_buku INT NOT NULL DEFAULT 1000 AFTER enable_perpustakaan");
        }

        // 2. perpus_kategori_ddc
        $pdo->exec("CREATE TABLE IF NOT EXISTS `perpus_kategori_ddc` (
            `kode`       VARCHAR(10) NOT NULL,
            `nama`       VARCHAR(255) NOT NULL,
            `induk_kode` VARCHAR(10) DEFAULT NULL,
            `tingkat`    TINYINT UNSIGNED DEFAULT 1,
            PRIMARY KEY (`kode`),
            INDEX `idx_induk` (`induk_kode`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

        // 3. perpus_bibliografi
        $pdo->exec("CREATE TABLE IF NOT EXISTS `perpus_bibliografi` (
            `id`              CHAR(36) NOT NULL DEFAULT (UUID()),
            `tenant_id`       CHAR(36) NOT NULL,
            `isbn`            VARCHAR(20) DEFAULT NULL,
            `judul`           VARCHAR(500) NOT NULL,
            `judul_seri`      VARCHAR(255) DEFAULT NULL,
            `edisi`           VARCHAR(100) DEFAULT NULL,
            `penulis`         JSON NOT NULL,
            `penerbit`        VARCHAR(255) DEFAULT NULL,
            `kota_terbit`     VARCHAR(100) DEFAULT NULL,
            `tahun_terbit`    YEAR DEFAULT NULL,
            `halaman`         SMALLINT UNSIGNED DEFAULT NULL,
            `dimensi`         VARCHAR(30) DEFAULT NULL,
            `klasifikasi_ddc` VARCHAR(30) DEFAULT NULL,
            `nomor_panggil`   VARCHAR(50) DEFAULT NULL,
            `subjek`          JSON DEFAULT NULL,
            `bahasa`          VARCHAR(50) DEFAULT 'Indonesia',
            `abstrak`         TEXT DEFAULT NULL,
            `cover`           VARCHAR(255) DEFAULT NULL,
            `jenis_buku`      ENUM('Umum','Paket Pelajaran','Referensi','Fiksi','Non-Fiksi','Majalah','OSN','Lainnya') DEFAULT 'Umum',
            `status_opac`     TINYINT(1) NOT NULL DEFAULT 1,
            `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `deleted_at`      TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
            INDEX `idx_tenant_judul` (`tenant_id`, `judul`(100)),
            INDEX `idx_tenant_isbn` (`tenant_id`, `isbn`),
            INDEX `idx_tenant_ddc` (`tenant_id`, `klasifikasi_ddc`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

        // 4. perpus_lokasi_rak
        $pdo->exec("CREATE TABLE IF NOT EXISTS `perpus_lokasi_rak` (
            `id`          CHAR(36) NOT NULL DEFAULT (UUID()),
            `tenant_id`   CHAR(36) NOT NULL,
            `kode`        VARCHAR(30) NOT NULL,
            `nama`        VARCHAR(255) NOT NULL,
            `gedung`      VARCHAR(100) DEFAULT NULL,
            `lantai`      VARCHAR(30) DEFAULT NULL,
            `ruangan`     VARCHAR(100) DEFAULT NULL,
            `nama_rak`    VARCHAR(50) DEFAULT NULL,
            `baris`       VARCHAR(30) DEFAULT NULL,
            `kapasitas`   SMALLINT UNSIGNED DEFAULT 50,
            `ddc_mulai`   VARCHAR(10) DEFAULT NULL,
            `ddc_selesai` VARCHAR(10) DEFAULT NULL,
            `keterangan`  TEXT DEFAULT NULL,
            `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_tenant_kode` (`tenant_id`, `kode`),
            FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

        // 5. perpus_eksemplar
        $pdo->exec("CREATE TABLE IF NOT EXISTS `perpus_eksemplar` (
            `id`              CHAR(36) NOT NULL DEFAULT (UUID()),
            `tenant_id`       CHAR(36) NOT NULL,
            `bibliografi_id`  CHAR(36) NOT NULL,
            `barcode`         VARCHAR(50) NOT NULL,
            `nomor_induk`     VARCHAR(30) NOT NULL,
            `tanggal_masuk`   DATE NOT NULL,
            `sumber_buku`     ENUM('Dana BOS','Sumbangan Siswa','Sumbangan Alumni','Hibah Pemerintah','Pembelian Mandiri','Lainnya') DEFAULT 'Dana BOS',
            `lokasi_rak_id`   CHAR(36) DEFAULT NULL,
            `kondisi`         ENUM('Baik','Rusak Ringan','Rusak Berat') DEFAULT 'Baik',
            `status`          ENUM('Tersedia','Dipinjam Reguler','Dipinjam Paket','Dipinjam Event','Dipesan','Rusak','Hilang','Diperbaiki') DEFAULT 'Tersedia',
            `harga_perolehan` DECIMAL(12,2) DEFAULT NULL,
            `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_barcode` (`barcode`),
            FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`bibliografi_id`) REFERENCES `perpus_bibliografi`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`lokasi_rak_id`) REFERENCES `perpus_lokasi_rak`(`id`) ON DELETE SET NULL,
            INDEX `idx_tenant_status` (`tenant_id`, `status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

        // 6. perpus_anggota
        $pdo->exec("CREATE TABLE IF NOT EXISTS `perpus_anggota` (
            `id`               CHAR(36) NOT NULL DEFAULT (UUID()),
            `tenant_id`        CHAR(36) NOT NULL,
            `tipe_anggota`     ENUM('Siswa','Guru','Staf','Eksternal') DEFAULT 'Siswa',
            `user_id`          CHAR(36) DEFAULT NULL,
            `siswa_id`         CHAR(36) DEFAULT NULL,
            `nisn`             VARCHAR(20) DEFAULT NULL,
            `nip`              VARCHAR(30) DEFAULT NULL,
            `nama_eksternal`   VARCHAR(255) DEFAULT NULL,
            `no_anggota`       VARCHAR(30) NOT NULL,
            `foto_anggota`     VARCHAR(255) DEFAULT NULL,
            `limit_pinjam_reguler` TINYINT UNSIGNED DEFAULT 3,
            `masa_berlaku`     DATE DEFAULT NULL,
            `status`           ENUM('Aktif','Dibekukan','Bebas Pustaka','Kadaluarsa') DEFAULT 'Aktif',
            `keterangan_beku`  TEXT DEFAULT NULL,
            `bebas_pustaka_at` TIMESTAMP NULL DEFAULT NULL,
            `bebas_pustaka_oleh` CHAR(36) DEFAULT NULL,
            `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_tenant_no_anggota` (`tenant_id`, `no_anggota`),
            FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

        // 7. perpus_sirkulasi
        $pdo->exec("CREATE TABLE IF NOT EXISTS `perpus_sirkulasi` (
            `id`                     CHAR(36) NOT NULL DEFAULT (UUID()),
            `tenant_id`              CHAR(36) NOT NULL,
            `no_transaksi`           VARCHAR(30) NOT NULL,
            `anggota_id`             CHAR(36) NOT NULL,
            `eksemplar_id`           CHAR(36) NOT NULL,
            `pustakawan_id`          CHAR(36) NOT NULL,
            `tanggal_pinjam`         DATE NOT NULL,
            `tanggal_kembali_rencana` DATE NOT NULL,
            `tanggal_kembali_aktual`  DATE DEFAULT NULL,
            `perpanjangan_ke`        TINYINT UNSIGNED DEFAULT 0,
            `status`                 ENUM('Dipinjam','Dikembalikan','Terlambat','Hilang') DEFAULT 'Dipinjam',
            `keterangan`             TEXT DEFAULT NULL,
            `created_at`             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at`             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_no_transaksi` (`tenant_id`, `no_transaksi`),
            FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`anggota_id`) REFERENCES `perpus_anggota`(`id`),
            FOREIGN KEY (`eksemplar_id`) REFERENCES `perpus_eksemplar`(`id`),
            INDEX `idx_tenant_status` (`tenant_id`, `status`),
            INDEX `idx_anggota_aktif` (`anggota_id`, `status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

        // 8. perpus_denda
        $pdo->exec("CREATE TABLE IF NOT EXISTS `perpus_denda` (
            `id`                CHAR(36) NOT NULL DEFAULT (UUID()),
            `tenant_id`         CHAR(36) NOT NULL,
            `sumber_transaksi`  ENUM('Sirkulasi Reguler','Buku Paket','Event Khusus') NOT NULL,
            `sirkulasi_id`      CHAR(36) DEFAULT NULL,
            `distribusi_id`     CHAR(36) DEFAULT NULL,
            `event_detail_id`   CHAR(36) DEFAULT NULL,
            `anggota_id`        CHAR(36) NOT NULL,
            `jenis_denda`       ENUM('Keterlambatan','Buku Hilang','Buku Rusak') DEFAULT 'Keterlambatan',
            `jumlah_hari`       SMALLINT UNSIGNED DEFAULT NULL,
            `nominal_per_hari`  DECIMAL(10,2) DEFAULT NULL,
            `total_denda`       DECIMAL(12,2) NOT NULL,
            `status`            ENUM('Belum Dibayar','Dibayar Tunai','Tagihan SPP','Dihapus') DEFAULT 'Belum Dibayar',
            `spp_tagihan_id`    CHAR(36) DEFAULT NULL,
            `dibayar_at`        TIMESTAMP NULL DEFAULT NULL,
            `dibayar_oleh`      CHAR(36) DEFAULT NULL,
            `created_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

        // 9. perpus_reservasi
        $pdo->exec("CREATE TABLE IF NOT EXISTS `perpus_reservasi` (
            `id`                  CHAR(36) NOT NULL DEFAULT (UUID()),
            `tenant_id`           CHAR(36) NOT NULL,
            `bibliografi_id`      CHAR(36) NOT NULL,
            `anggota_id`          CHAR(36) NOT NULL,
            `tanggal_reservasi`   DATE NOT NULL,
            `tanggal_kadaluarsa`  DATE NOT NULL,
            `status`              ENUM('Menunggu','Siap Diambil','Dibatalkan','Kadaluarsa','Dipenuhi') DEFAULT 'Menunggu',
            `eksemplar_id`        CHAR(36) DEFAULT NULL,
            `notif_dikirim_at`    TIMESTAMP NULL DEFAULT NULL,
            `created_at`          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`bibliografi_id`) REFERENCES `perpus_bibliografi`(`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

        // 10. perpus_paket_buku
        $pdo->exec("CREATE TABLE IF NOT EXISTS `perpus_paket_buku` (
            `id`              CHAR(36) NOT NULL DEFAULT (UUID()),
            `tenant_id`       CHAR(36) NOT NULL,
            `nama_paket`      VARCHAR(255) NOT NULL,
            `kelas_id`        CHAR(36) DEFAULT NULL,
            `jenjang`         VARCHAR(10) DEFAULT NULL,
            `jurusan`         VARCHAR(50) DEFAULT NULL,
            `tahun_ajaran_id` CHAR(36) NOT NULL,
            `semester`        TINYINT UNSIGNED DEFAULT 1,
            `durasi_pinjam`   ENUM('1 Semester','2 Semester','1 Tahun Ajaran') DEFAULT '1 Semester',
            `tanggal_mulai`   DATE NOT NULL,
            `tanggal_selesai` DATE NOT NULL,
            `status`          ENUM('Draft','Aktif','Selesai','Dibatalkan') DEFAULT 'Draft',
            `keterangan`      TEXT DEFAULT NULL,
            `created_by`      CHAR(36) NOT NULL,
            `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

        // 11. perpus_paket_item
        $pdo->exec("CREATE TABLE IF NOT EXISTS `perpus_paket_item` (
            `id`               CHAR(36) NOT NULL DEFAULT (UUID()),
            `paket_id`         CHAR(36) NOT NULL,
            `bibliografi_id`   CHAR(36) NOT NULL,
            `mata_pelajaran`   VARCHAR(100) NOT NULL,
            `jumlah_per_siswa` TINYINT UNSIGNED DEFAULT 1,
            `urutan`           TINYINT UNSIGNED DEFAULT 1,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`paket_id`) REFERENCES `perpus_paket_buku`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`bibliografi_id`) REFERENCES `perpus_bibliografi`(`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

        // 12. perpus_distribusi_paket
        $pdo->exec("CREATE TABLE IF NOT EXISTS `perpus_distribusi_paket` (
            `id`               CHAR(36) NOT NULL DEFAULT (UUID()),
            `tenant_id`        CHAR(36) NOT NULL,
            `paket_id`         CHAR(36) NOT NULL,
            `paket_item_id`    CHAR(36) NOT NULL,
            `siswa_id`         CHAR(36) NOT NULL,
            `anggota_id`       CHAR(36) NOT NULL,
            `eksemplar_id`     CHAR(36) NOT NULL,
            `bibliografi_id`   CHAR(36) NOT NULL,
            `tanggal_terima`   DATE NOT NULL,
            `tanda_terima`     TINYINT(1) DEFAULT 0,
            `tanda_terima_at`  TIMESTAMP NULL DEFAULT NULL,
            `tanggal_kembali`  DATE DEFAULT NULL,
            `kondisi_kembali`  ENUM('Baik','Rusak Ringan','Rusak Berat','Hilang') DEFAULT NULL,
            `status`           ENUM('Dipinjam','Dikembalikan','Hilang') DEFAULT 'Dipinjam',
            `denda_id`         CHAR(36) DEFAULT NULL,
            `keterangan`       TEXT DEFAULT NULL,
            `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`paket_id`) REFERENCES `perpus_paket_buku`(`id`),
            FOREIGN KEY (`siswa_id`) REFERENCES `siswa`(`id`),
            FOREIGN KEY (`eksemplar_id`) REFERENCES `perpus_eksemplar`(`id`),
            INDEX `idx_siswa_status` (`siswa_id`, `status`),
            INDEX `idx_paket_siswa` (`paket_id`, `siswa_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

        // 13. perpus_event_pinjam
        $pdo->exec("CREATE TABLE IF NOT EXISTS `perpus_event_pinjam` (
            `id`               CHAR(36) NOT NULL DEFAULT (UUID()),
            `tenant_id`        CHAR(36) NOT NULL,
            `nama_event`       VARCHAR(255) NOT NULL,
            `kategori`         ENUM('OSN','Olimpiade','Try Out','Lomba','Ujian Khusus','Lainnya') DEFAULT 'OSN',
            `tanggal_mulai`    DATE NOT NULL,
            `tanggal_selesai`  DATE NOT NULL,
            `penanggung_jawab` VARCHAR(255) DEFAULT NULL,
            `keterangan`       TEXT DEFAULT NULL,
            `created_by`       CHAR(36) NOT NULL,
            `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

        // 14. perpus_event_detail
        $pdo->exec("CREATE TABLE IF NOT EXISTS `perpus_event_detail` (
            `id`              CHAR(36) NOT NULL DEFAULT (UUID()),
            `tenant_id`       CHAR(36) NOT NULL,
            `event_id`        CHAR(36) NOT NULL,
            `siswa_id`        CHAR(36) NOT NULL,
            `anggota_id`      CHAR(36) NOT NULL,
            `eksemplar_id`    CHAR(36) NOT NULL,
            `bibliografi_id`  CHAR(36) NOT NULL,
            `tanggal_terima`  DATE NOT NULL,
            `tanggal_kembali_rencana` DATE NOT NULL,
            `tanggal_kembali_aktual`  DATE DEFAULT NULL,
            `kondisi_kembali` ENUM('Baik','Rusak Ringan','Rusak Berat','Hilang') DEFAULT NULL,
            `status`          ENUM('Dipinjam','Dikembalikan','Terlambat','Hilang') DEFAULT 'Dipinjam',
            `denda_id`        CHAR(36) DEFAULT NULL,
            `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`event_id`) REFERENCES `perpus_event_pinjam`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`eksemplar_id`) REFERENCES `perpus_eksemplar`(`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

        // 15. perpus_pengaturan
        $pdo->exec("CREATE TABLE IF NOT EXISTS `perpus_pengaturan` (
            `id`                      CHAR(36) NOT NULL DEFAULT (UUID()),
            `tenant_id`               CHAR(36) NOT NULL,
            `nama_perpustakaan`       VARCHAR(255) DEFAULT NULL,
            `nomor_pokok`             VARCHAR(50) DEFAULT NULL,
            `kepala_perpustakaan`     VARCHAR(255) DEFAULT NULL,
            `nip_kepala`              VARCHAR(30) DEFAULT NULL,
            `tarif_denda_per_hari`    DECIMAL(10,2) NOT NULL DEFAULT 500,
            `max_hari_pinjam_siswa`   TINYINT UNSIGNED DEFAULT 7,
            `max_hari_pinjam_guru`    TINYINT UNSIGNED DEFAULT 14,
            `max_perpanjangan`        TINYINT UNSIGNED DEFAULT 2,
            `max_pinjam_reguler_siswa` TINYINT UNSIGNED DEFAULT 3,
            `max_pinjam_reguler_guru`  TINYINT UNSIGNED DEFAULT 5,
            `max_hari_paket_kadaluarsa` SMALLINT UNSIGNED DEFAULT 7,
            `opac_aktif`              TINYINT(1) NOT NULL DEFAULT 1,
            `allow_self_reservasi`    TINYINT(1) NOT NULL DEFAULT 1,
            `notif_jatuh_tempo_hari`  TINYINT UNSIGNED DEFAULT 2,
            `logo_perpustakaan`       VARCHAR(255) DEFAULT NULL,
            `jam_operasional`         JSON DEFAULT NULL,
            `format_no_anggota`       VARCHAR(100) DEFAULT '[NPSN]-[TAHUN]-[NO4]',
            `format_no_transaksi`     VARCHAR(100) DEFAULT 'CIRC-[TAHUN]-[NO5]',
            `created_at`              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at`              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_tenant` (`tenant_id`),
            FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

        // 16. perpus_buku_tamu
        $pdo->exec("CREATE TABLE IF NOT EXISTS `perpus_buku_tamu` (
            `id`               CHAR(36) NOT NULL DEFAULT (UUID()),
            `tenant_id`        CHAR(36) NOT NULL,
            `nisn`             VARCHAR(20) DEFAULT NULL,
            `nama_pengunjung`  VARCHAR(255) NOT NULL,
            `kelas`            VARCHAR(50) DEFAULT NULL,
            `tujuan`           ENUM('Membaca','Mencari Referensi','Mengerjakan Tugas','Belajar Kelompok','Lainnya') DEFAULT 'Membaca',
            `tanggal`          DATE NOT NULL,
            `jam_masuk`        TIME NOT NULL,
            `jam_keluar`       TIME DEFAULT NULL,
            `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
            INDEX `idx_tenant_tanggal` (`tenant_id`, `tanggal`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

        // 17. perpus_opname
        $pdo->exec("CREATE TABLE IF NOT EXISTS `perpus_opname` (
            `id`               CHAR(36) NOT NULL DEFAULT (UUID()),
            `tenant_id`        CHAR(36) NOT NULL,
            `nama_sesi`        VARCHAR(255) NOT NULL,
            `tanggal_mulai`    DATE NOT NULL,
            `tanggal_selesai`  DATE DEFAULT NULL,
            `status`           ENUM('Berjalan','Selesai','Dibatalkan') DEFAULT 'Berjalan',
            `petugas_id`       CHAR(36) NOT NULL,
            `total_scan`       INT UNSIGNED DEFAULT 0,
            `total_ditemukan`  INT UNSIGNED DEFAULT 0,
            `total_selisih`    INT UNSIGNED DEFAULT 0,
            `catatan`          TEXT DEFAULT NULL,
            `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

        // 18. perpus_opname_detail
        $pdo->exec("CREATE TABLE IF NOT EXISTS `perpus_opname_detail` (
            `id`            CHAR(36) NOT NULL DEFAULT (UUID()),
            `opname_id`     CHAR(36) NOT NULL,
            `eksemplar_id`  CHAR(36) DEFAULT NULL,
            `barcode`       VARCHAR(50) NOT NULL,
            `status_scan`   ENUM('Ditemukan','Tidak Ditemukan','Anomali','Salah Rak') DEFAULT 'Ditemukan',
            `lokasi_scan`   VARCHAR(255) DEFAULT NULL,
            `keterangan`    VARCHAR(255) DEFAULT NULL,
            `scanned_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`opname_id`) REFERENCES `perpus_opname`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

        // 19. perpus_notifikasi
        $pdo->exec("CREATE TABLE IF NOT EXISTS `perpus_notifikasi` (
            `id`             CHAR(36) NOT NULL DEFAULT (UUID()),
            `tenant_id`      CHAR(36) NOT NULL,
            `anggota_id`     CHAR(36) NOT NULL,
            `tipe`           ENUM('JatuhTempo','Terlambat','ReservasiSiap','DendaBelumLunas','PaketHarusDikembalikan') NOT NULL,
            `pesan`          TEXT NOT NULL,
            `referensi_id`   CHAR(36) DEFAULT NULL,
            `referensi_tipe` VARCHAR(50) DEFAULT NULL,
            `sudah_baca`     TINYINT(1) DEFAULT 0,
            `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
            INDEX `idx_anggota_baca` (`anggota_id`, `sudah_baca`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $tables = [
            'perpus_notifikasi', 'perpus_opname_detail', 'perpus_opname', 'perpus_buku_tamu',
            'perpus_pengaturan', 'perpus_event_detail', 'perpus_event_pinjam', 'perpus_distribusi_paket',
            'perpus_paket_item', 'perpus_paket_buku', 'perpus_reservasi', 'perpus_denda',
            'perpus_sirkulasi', 'perpus_anggota', 'perpus_eksemplar', 'perpus_lokasi_rak',
            'perpus_bibliografi', 'perpus_kategori_ddc'
        ];
        foreach ($tables as $t) {
            $pdo->exec("DROP TABLE IF EXISTS `{$t}`;");
        }
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }
];
