<?php
/**
 * Migration 008 - Create Kelembagaan Tables
 * Mendefinisikan tabel-tabel Master Data Kelembagaan dan melakukan seeding awal data uji.
 */

return [
    'up' => function(PDO $pdo) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

        // 1. Tabel jenjang
        $pdo->exec("CREATE TABLE IF NOT EXISTS jenjang (
            id INT UNSIGNED AUTO_INCREMENT NOT NULL,
            tenant_id CHAR(36) NOT NULL,
            kode_jenjang VARCHAR(20) NOT NULL,
            nama_jenjang VARCHAR(100) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY (id),
            CONSTRAINT fk_jenjang_tenant_id FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE,
            UNIQUE KEY uq_jenjang_tenant_kode (tenant_id, kode_jenjang, deleted_at),
            INDEX idx_jenjang_tenant (tenant_id)
        ) ENGINE=InnoDB;");

        // 2. Tabel jurusan
        $pdo->exec("CREATE TABLE IF NOT EXISTS jurusan (
            id INT UNSIGNED AUTO_INCREMENT NOT NULL,
            tenant_id CHAR(36) NOT NULL,
            kode_jurusan VARCHAR(50) NOT NULL,
            nama_jurusan VARCHAR(150) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY (id),
            CONSTRAINT fk_jurusan_tenant_id FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE,
            UNIQUE KEY uq_jurusan_tenant_kode (tenant_id, kode_jurusan, deleted_at),
            INDEX idx_jurusan_tenant (tenant_id)
        ) ENGINE=InnoDB;");

        // 3. Tabel kelas
        $pdo->exec("CREATE TABLE IF NOT EXISTS kelas (
            id INT UNSIGNED AUTO_INCREMENT NOT NULL,
            tenant_id CHAR(36) NOT NULL,
            id_jenjang INT UNSIGNED NOT NULL,
            id_jurusan INT UNSIGNED NOT NULL,
            kode_kelas VARCHAR(50) NOT NULL,
            nama_kelas VARCHAR(100) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY (id),
            CONSTRAINT fk_kelas_tenant_id FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE,
            CONSTRAINT fk_kelas_id_jenjang FOREIGN KEY (id_jenjang) REFERENCES jenjang (id) ON DELETE RESTRICT,
            CONSTRAINT fk_kelas_id_jurusan FOREIGN KEY (id_jurusan) REFERENCES jurusan (id) ON DELETE RESTRICT,
            UNIQUE KEY uq_kelas_tenant_kode (tenant_id, kode_kelas, deleted_at),
            INDEX idx_kelas_tenant (tenant_id)
        ) ENGINE=InnoDB;");

        // 4. Tabel mata_pelajaran
        $pdo->exec("CREATE TABLE IF NOT EXISTS mata_pelajaran (
            id INT UNSIGNED AUTO_INCREMENT NOT NULL,
            tenant_id CHAR(36) NOT NULL,
            kode_mapel VARCHAR(50) NOT NULL,
            nama_mapel VARCHAR(150) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY (id),
            CONSTRAINT fk_mapel_tenant_id FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE,
            UNIQUE KEY uq_mapel_tenant_kode (tenant_id, kode_mapel, deleted_at),
            INDEX idx_mapel_tenant (tenant_id)
        ) ENGINE=InnoDB;");

        // 5. Tabel pendidikan
        $pdo->exec("CREATE TABLE IF NOT EXISTS pendidikan (
            id INT UNSIGNED AUTO_INCREMENT NOT NULL,
            tenant_id CHAR(36) NOT NULL,
            kode_pendidikan VARCHAR(50) NOT NULL,
            nama_pendidikan VARCHAR(100) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY (id),
            CONSTRAINT fk_pendidikan_tenant_id FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE,
            UNIQUE KEY uq_pendidikan_tenant_kode (tenant_id, kode_pendidikan, deleted_at),
            INDEX idx_pendidikan_tenant (tenant_id)
        ) ENGINE=InnoDB;");

        // 6. Tabel program_pengajaran
        $pdo->exec("CREATE TABLE IF NOT EXISTS program_pengajaran (
            id INT UNSIGNED AUTO_INCREMENT NOT NULL,
            tenant_id CHAR(36) NOT NULL,
            kode_program VARCHAR(50) NOT NULL,
            nama_program VARCHAR(150) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY (id),
            CONSTRAINT fk_program_tenant_id FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE,
            UNIQUE KEY uq_program_tenant_kode (tenant_id, kode_program, deleted_at),
            INDEX idx_program_tenant (tenant_id)
        ) ENGINE=InnoDB;");

        // 7. Tabel tahun_ajaran
        $pdo->exec("CREATE TABLE IF NOT EXISTS tahun_ajaran (
            id INT UNSIGNED AUTO_INCREMENT NOT NULL,
            tenant_id CHAR(36) NOT NULL,
            tahun_ajaran VARCHAR(50) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY (id),
            CONSTRAINT fk_tahun_ajaran_tenant_id FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE,
            UNIQUE KEY uq_tahun_ajaran_tenant (tenant_id, tahun_ajaran, deleted_at),
            INDEX idx_tahun_ajaran_tenant (tenant_id)
        ) ENGINE=InnoDB;");

        // 8. Tabel angkatan
        $pdo->exec("CREATE TABLE IF NOT EXISTS angkatan (
            id INT UNSIGNED AUTO_INCREMENT NOT NULL,
            tenant_id CHAR(36) NOT NULL,
            tahun_angkatan VARCHAR(4) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY (id),
            CONSTRAINT fk_angkatan_tenant_id FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE,
            UNIQUE KEY uq_angkatan_tenant (tenant_id, tahun_angkatan, deleted_at),
            INDEX idx_angkatan_tenant (tenant_id)
        ) ENGINE=InnoDB;");

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

        // --- SEED INITIAL TEST DATA ---
        // SMAN 1 Jakarta Tenant ID: 11111111-1111-1111-1111-111111111111
        $t1 = '11111111-1111-1111-1111-111111111111';
        // SMKN 2 Bandung Tenant ID: 22222222-2222-2222-2222-222222222222
        $t2 = '22222222-2222-2222-2222-222222222222';

        // Seed Jenjang
        $pdo->exec("INSERT INTO jenjang (tenant_id, kode_jenjang, nama_jenjang, is_active) VALUES
            ('{$t1}', 'SMA', 'Sekolah Menengah Atas', 1),
            ('{$t2}', 'SMK', 'Sekolah Menengah Kejuruan', 1)
        ON DUPLICATE KEY UPDATE nama_jenjang=VALUES(nama_jenjang);");

        // Seed Jurusan
        $pdo->exec("INSERT INTO jurusan (tenant_id, kode_jurusan, nama_jurusan, is_active) VALUES
            ('{$t1}', 'MIPA', 'Matematika dan Ilmu Pengetahuan Alam', 1),
            ('{$t1}', 'IPS', 'Ilmu Pengetahuan Sosial', 1),
            ('{$t2}', 'RPL', 'Rekayasa Perangkat Lunak', 1),
            ('{$t2}', 'TKJ', 'Teknik Komputer dan Jaringan', 1)
        ON DUPLICATE KEY UPDATE nama_jurusan=VALUES(nama_jurusan);");

        // Ambil ID jenjang & jurusan untuk insert kelas
        $stmt = $pdo->query("SELECT id, tenant_id, kode_jenjang FROM jenjang");
        $jenjangs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $jenjangMap = [];
        foreach ($jenjangs as $j) {
            $jenjangMap[$j['tenant_id'] . '-' . $j['kode_jenjang']] = $j['id'];
        }

        $stmt = $pdo->query("SELECT id, tenant_id, kode_jurusan FROM jurusan");
        $jurusans = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $jurusanMap = [];
        foreach ($jurusans as $ju) {
            $jurusanMap[$ju['tenant_id'] . '-' . $ju['kode_jurusan']] = $ju['id'];
        }

        // Seed Kelas
        if (isset($jenjangMap["{$t1}-SMA"], $jurusanMap["{$t1}-MIPA"], $jurusanMap["{$t1}-IPS"])) {
            $jSMA1 = $jenjangMap["{$t1}-SMA"];
            $juMIPA1 = $jurusanMap["{$t1}-MIPA"];
            $juIPS1 = $jurusanMap["{$t1}-IPS"];
            $pdo->exec("INSERT INTO kelas (tenant_id, id_jenjang, id_jurusan, kode_kelas, nama_kelas, is_active) VALUES
                ('{$t1}', {$jSMA1}, {$juMIPA1}, 'KLS-10MIPA1', 'Kelas X MIPA 1', 1),
                ('{$t1}', {$jSMA1}, {$juMIPA1}, 'KLS-10MIPA2', 'Kelas X MIPA 2', 1),
                ('{$t1}', {$jSMA1}, {$juIPS1}, 'KLS-10IPS1', 'Kelas X IPS 1', 1)
            ON DUPLICATE KEY UPDATE nama_kelas=VALUES(nama_kelas);");
        }

        if (isset($jenjangMap["{$t2}-SMK"], $jurusanMap["{$t2}-RPL"], $jurusanMap["{$t2}-TKJ"])) {
            $jSMK2 = $jenjangMap["{$t2}-SMK"];
            $juRPL2 = $jurusanMap["{$t2}-RPL"];
            $juTKJ2 = $jurusanMap["{$t2}-TKJ"];
            $pdo->exec("INSERT INTO kelas (tenant_id, id_jenjang, id_jurusan, kode_kelas, nama_kelas, is_active) VALUES
                ('{$t2}', {$jSMK2}, {$juRPL2}, 'KLS-10RPL1', 'Kelas X RPL 1', 1),
                ('{$t2}', {$jSMK2}, {$juTKJ2}, 'KLS-10TKJ1', 'Kelas X TKJ 1', 1)
            ON DUPLICATE KEY UPDATE nama_kelas=VALUES(nama_kelas);");
        }

        // Seed Mata Pelajaran
        $pdo->exec("INSERT INTO mata_pelajaran (tenant_id, kode_mapel, nama_mapel, is_active) VALUES
            ('{$t1}', 'MP-MTK', 'Matematika Wajib', 1),
            ('{$t1}', 'MP-IND', 'Bahasa Indonesia', 1),
            ('{$t1}', 'MP-ING', 'Bahasa Inggris', 1),
            ('{$t2}', 'MP-PROG', 'Pemrograman Dasar', 1),
            ('{$t2}', 'MP-JARKOM', 'Jaringan Komputer', 1)
        ON DUPLICATE KEY UPDATE nama_mapel=VALUES(nama_mapel);");

        // Seed Pendidikan
        $pdo->exec("INSERT INTO pendidikan (tenant_id, kode_pendidikan, nama_pendidikan, is_active) VALUES
            ('{$t1}', 'PED-SMA', 'Pendidikan Menengah Atas', 1),
            ('{$t2}', 'PED-SMK', 'Pendidikan Menengah Kejuruan', 1)
        ON DUPLICATE KEY UPDATE nama_pendidikan=VALUES(nama_pendidikan);");

        // Seed Program Pengajaran
        $pdo->exec("INSERT INTO program_pengajaran (tenant_id, kode_program, nama_program, is_active) VALUES
            ('{$t1}', 'PROG-REG', 'Program Reguler', 1),
            ('{$t1}', 'PROG-BIL', 'Program Bilingual', 1),
            ('{$t2}', 'PROG-INDUSTRI', 'Program Kelas Industri (Axioo)', 1)
        ON DUPLICATE KEY UPDATE nama_program=VALUES(nama_program);");

        // Seed Tahun Ajaran
        $pdo->exec("INSERT INTO tahun_ajaran (tenant_id, tahun_ajaran, is_active) VALUES
            ('{$t1}', '2025/2026', 1),
            ('{$t1}', '2026/2027', 1),
            ('{$t2}', '2025/2026', 1)
        ON DUPLICATE KEY UPDATE is_active=VALUES(is_active);");

        // Seed Angkatan
        $pdo->exec("INSERT INTO angkatan (tenant_id, tahun_angkatan, is_active) VALUES
            ('{$t1}', '2024', 1),
            ('{$t1}', '2025', 1),
            ('{$t1}', '2026', 1),
            ('{$t2}', '2025', 1),
            ('{$t2}', '2026', 1)
        ON DUPLICATE KEY UPDATE is_active=VALUES(is_active);");
    },

    'down' => function(PDO $pdo) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec("DROP TABLE IF EXISTS angkatan;");
        $pdo->exec("DROP TABLE IF EXISTS tahun_ajaran;");
        $pdo->exec("DROP TABLE IF EXISTS program_pengajaran;");
        $pdo->exec("DROP TABLE IF EXISTS pendidikan;");
        $pdo->exec("DROP TABLE IF EXISTS mata_pelajaran;");
        $pdo->exec("DROP TABLE IF EXISTS kelas;");
        $pdo->exec("DROP TABLE IF EXISTS jurusan;");
        $pdo->exec("DROP TABLE IF EXISTS jenjang;");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }
];
