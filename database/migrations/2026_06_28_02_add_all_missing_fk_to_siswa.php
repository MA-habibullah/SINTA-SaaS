<?php
/**
 * Migration: Add all missing FK columns to siswa table
 * 
 * Kolom-kolom ini dibutuhkan oleh banyak query JOIN di seluruh aplikasi
 * tapi tidak ada di definisi awal tabel siswa (migration 004).
 */
return [
    'up' => function (PDO $pdo): void {
        // Daftar semua kolom FK yang harus ada di tabel siswa
        $columns = [
            'id_angkatan'    => "INT UNSIGNED DEFAULT NULL COMMENT 'FK ke tabel angkatan'",
            'id_tahun_ajaran'=> "INT UNSIGNED DEFAULT NULL COMMENT 'FK ke tabel tahun_ajaran'",
            'id_pendidikan'  => "INT UNSIGNED DEFAULT NULL COMMENT 'FK ke tabel pendidikan'",
            'id_jenjang'     => "INT UNSIGNED DEFAULT NULL COMMENT 'FK ke tabel jenjang'",
            'id_jurusan'     => "INT UNSIGNED DEFAULT NULL COMMENT 'FK ke tabel jurusan'",
            'id_kelas'       => "INT UNSIGNED DEFAULT NULL COMMENT 'FK ke tabel kelas'",
            'tanggal_lulus'  => "DATE DEFAULT NULL COMMENT 'Tanggal kelulusan siswa'",
            'password'       => "VARCHAR(255) DEFAULT NULL COMMENT 'Password login siswa'",
        ];

        $added = [];
        $skipped = [];

        foreach ($columns as $col => $definition) {
            $exists = $pdo->query("SHOW COLUMNS FROM `siswa` LIKE '$col'")->fetch();
            if ($exists) {
                $skipped[] = $col;
                continue;
            }
            try {
                $pdo->exec("ALTER TABLE `siswa` ADD COLUMN `{$col}` {$definition};");
                $added[] = $col;
            } catch (\Throwable $e) {
                echo "  WARN: Gagal tambah kolom {$col}: " . $e->getMessage() . "\n";
            }
        }

        if (!empty($added)) {
            echo "  OK Kolom ditambahkan: " . implode(', ', $added) . "\n";
        }
        if (!empty($skipped)) {
            echo "  OK Kolom sudah ada (skip): " . implode(', ', $skipped) . "\n";
        }
    },

    'down' => function (PDO $pdo): void {
        $cols = ['id_angkatan', 'id_tahun_ajaran', 'id_pendidikan', 'id_jenjang', 'id_jurusan', 'id_kelas', 'tanggal_lulus', 'password'];
        foreach ($cols as $col) {
            try {
                $pdo->exec("ALTER TABLE `siswa` DROP COLUMN IF EXISTS `{$col}`;");
            } catch (\Throwable $e) { /* skip */ }
        }
        echo "  OK Rollback: kolom FK siswa berhasil dihapus.\n";
    },
];
