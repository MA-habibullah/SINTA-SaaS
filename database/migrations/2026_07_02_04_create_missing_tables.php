<?php
/**
 * Migration: Create Missing Tables (kategori_pengumuman, kunci_ekskul, riwayat_kenaikan_kelas)
 */
return [
    'up' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

        // 1. kategori_pengumuman
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `kategori_pengumuman` (
              `id` char(36) NOT NULL,
              `tenant_id` char(36) DEFAULT NULL,
              `nama_kategori` varchar(255) NOT NULL,
              `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
              `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
              PRIMARY KEY (`id`),
              KEY `fk_kategori_tenant` (`tenant_id`),
              CONSTRAINT `fk_kategori_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ");
        echo "  OK Tabel kategori_pengumuman berhasil dibuat.\n";

        // 2. kunci_ekskul
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `kunci_ekskul` (
              `id` char(36) NOT NULL,
              `tenant_id` char(36) NOT NULL,
              `ekskul_id` char(36) NOT NULL,
              `tahun_ajaran_id` int(10) unsigned NOT NULL,
              `semester` enum('Ganjil','Genap') NOT NULL DEFAULT 'Ganjil',
              `kunci_anggota` tinyint(1) NOT NULL DEFAULT 0,
              `kunci_nilai` tinyint(1) NOT NULL DEFAULT 0,
              `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
              `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
              PRIMARY KEY (`id`),
              UNIQUE KEY `uk_kunci_ekskul` (`ekskul_id`,`tahun_ajaran_id`,`semester`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ");
        echo "  OK Tabel kunci_ekskul berhasil dibuat.\n";

        // 3. riwayat_kenaikan_kelas
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `riwayat_kenaikan_kelas` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `tenant_id` char(36) NOT NULL,
              `siswa_id` char(36) NOT NULL,
              `jenis_aksi` enum('naik_kelas','lulus') NOT NULL,
              `id_kelas_asal` int(11) DEFAULT NULL,
              `id_kelas_tujuan` int(11) DEFAULT NULL,
              `nama_kelas_asal` varchar(100) DEFAULT NULL,
              `nama_kelas_tujuan` varchar(100) DEFAULT NULL,
              `tahun_ajaran` varchar(20) NOT NULL,
              `dilakukan_oleh` char(36) NOT NULL,
              `nama_pelaku` varchar(150) NOT NULL,
              `catatan` text DEFAULT NULL,
              `created_at` datetime NOT NULL DEFAULT current_timestamp(),
              PRIMARY KEY (`id`),
              KEY `idx_riwayat_siswa` (`siswa_id`),
              KEY `idx_riwayat_tenant` (`tenant_id`),
              KEY `idx_riwayat_aksi` (`jenis_aksi`),
              KEY `idx_riwayat_tahun` (`tahun_ajaran`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ");
        echo "  OK Tabel riwayat_kenaikan_kelas berhasil dibuat.\n";

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec("DROP TABLE IF EXISTS `riwayat_kenaikan_kelas`");
        $pdo->exec("DROP TABLE IF EXISTS `kunci_ekskul`");
        $pdo->exec("DROP TABLE IF EXISTS `kategori_pengumuman`");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "  OK Tabel-tabel yang hilang berhasil dihapus.\n";
    }
];
