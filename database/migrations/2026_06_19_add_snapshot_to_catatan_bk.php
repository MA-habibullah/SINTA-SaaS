<?php
/**
 * Migration: Snapshot Kelas pada Catatan BK
 *
 * Menambahkan kolom snapshot historis ke tabel `catatan_bk` agar kelas siswa
 * saat kejadian tersimpan permanen, tidak berubah meskipun siswa naik kelas.
 *
 * Kolom baru:
 *   - snapshot_nama_kelas   : Nama kelas saat konseling direkam (e.g. "X IPA 1")
 *   - snapshot_nisn         : NISN siswa saat konseling direkam (immutable history)
 *   - snapshot_nis          : NIS siswa saat konseling direkam
 *   - snapshot_nama_siswa   : Nama lengkap siswa (guard terhadap perubahan nama)
 *   - id_kelas_snapshot     : FK soft ke kelas.id (nullable, untuk referensi)
 */
return [

    'up' => function (PDO $pdo): void {

        // 1. Cek apakah kolom sudah ada (idempotent migration)
        $cols = $pdo->query("SHOW COLUMNS FROM catatan_bk")->fetchAll(PDO::FETCH_COLUMN);

        if (!in_array('snapshot_nama_kelas', $cols)) {
            $pdo->exec("
                ALTER TABLE catatan_bk
                    ADD COLUMN `snapshot_nama_siswa` VARCHAR(255) DEFAULT NULL
                        COMMENT 'Nama siswa saat kasus direkam (snapshot historis)'
                        AFTER `id_siswa`,
                    ADD COLUMN `snapshot_nisn`       VARCHAR(20)  DEFAULT NULL
                        COMMENT 'NISN siswa saat kasus direkam (snapshot historis)'
                        AFTER `snapshot_nama_siswa`,
                    ADD COLUMN `snapshot_nis`        VARCHAR(20)  DEFAULT NULL
                        COMMENT 'NIS siswa saat kasus direkam (snapshot historis)'
                        AFTER `snapshot_nisn`,
                    ADD COLUMN `snapshot_nama_kelas` VARCHAR(100) DEFAULT NULL
                        COMMENT 'Nama kelas saat kasus direkam (snapshot historis — immutable)'
                        AFTER `snapshot_nis`,
                    ADD COLUMN `id_kelas_snapshot`   BIGINT UNSIGNED DEFAULT NULL
                        COMMENT 'ID kelas saat kasus direkam (soft reference)'
                        AFTER `snapshot_nama_kelas`,
                    ADD INDEX  `idx_catatan_kelas_snap` (`id_kelas_snapshot`)
            ");
            echo "  OK Kolom snapshot historis berhasil ditambahkan ke catatan_bk.\n";
        } else {
            echo "  INFO Kolom snapshot sudah ada di catatan_bk, tidak ada perubahan.\n";
        }
    },

    'down' => function (PDO $pdo): void {
        $cols = $pdo->query("SHOW COLUMNS FROM catatan_bk")->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('snapshot_nama_kelas', $cols)) {
            $pdo->exec("
                ALTER TABLE catatan_bk
                    DROP INDEX  IF EXISTS `idx_catatan_kelas_snap`,
                    DROP COLUMN IF EXISTS `snapshot_nama_siswa`,
                    DROP COLUMN IF EXISTS `snapshot_nisn`,
                    DROP COLUMN IF EXISTS `snapshot_nis`,
                    DROP COLUMN IF EXISTS `snapshot_nama_kelas`,
                    DROP COLUMN IF EXISTS `id_kelas_snapshot`
            ");
            echo "  OK Rollback: kolom snapshot dihapus dari catatan_bk.\n";
        }
    },
];
