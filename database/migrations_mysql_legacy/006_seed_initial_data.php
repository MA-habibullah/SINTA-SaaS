<?php
/**
 * Migration 006 - Seed Initial Data
 */

return [
    'up' => function(PDO $pdo) {
        // 1. Seed Roles
        $pdo->exec("INSERT INTO roles (id, nama_role, deskripsi) VALUES
            (1, 'super_admin', 'SaaS Platform Owner with global access across all tenants'),
            (2, 'operator_sekolah', 'School administrator with full school-level permissions'),
            (3, 'guru', 'Teacher role with teaching and evaluation access'),
            (4, 'siswa', 'Student role with access to academic reports and profiles'),
            (5, 'pendaftar', 'Applicant role with access only to their own SPMB registration'),
            (6, 'karyawan', 'School staff or non-teaching employees')
        ON DUPLICATE KEY UPDATE deskripsi=VALUES(deskripsi);");

        // 2. Seed Tenants (Sekolah)
        $pdo->exec("INSERT INTO tenants (id, nama_sekolah, npsn, subdomain, status, paket_aktif, status_sinkronisasi) VALUES
            ('11111111-1111-1111-1111-111111111111', 'SMA Negeri 1 Jakarta', '10101010', 'sman1jkt', 'active', 'Premium SaaS', 'Tersinkronisasi'),
            ('22222222-2222-2222-2222-222222222222', 'SMK Negeri 2 Bandung', '20202020', 'smkn2bdg', 'active', 'Enterprise SaaS', 'Tersinkronisasi')
        ON DUPLICATE KEY UPDATE nama_sekolah=VALUES(nama_sekolah), subdomain=VALUES(subdomain), paket_aktif=VALUES(paket_aktif), status_sinkronisasi=VALUES(status_sinkronisasi);");

        // 3. Seed Users (Password for all: 'admin123')
        $pdo->exec("INSERT INTO users (id, tenant_id, role_id, nama_lengkap, email, password, status) VALUES
            ('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa', NULL, 1, 'Super Admin Platform', 'admin@dapodikspmb.id', '\$2y\$10\$CQn/WfM6Ph6n.GXU4UgIpe8O7hsIiEgq0RZ0LyJughjeZApKUS1Ge', 'active'),
            ('bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb', '11111111-1111-1111-1111-111111111111', 2, 'Budi Utomo (Operator)', 'budi@sman1jkt.sch.id', '\$2y\$10\$CQn/WfM6Ph6n.GXU4UgIpe8O7hsIiEgq0RZ0LyJughjeZApKUS1Ge', 'active'),
            ('cccccccc-cccc-cccc-cccc-cccccccccccc', '11111111-1111-1111-1111-111111111111', 3, 'Siti Aminah (Guru)', 'siti@sman1jkt.sch.id', '\$2y\$10\$CQn/WfM6Ph6n.GXU4UgIpe8O7hsIiEgq0RZ0LyJughjeZApKUS1Ge', 'active'),
            ('ffffffff-ffff-ffff-ffff-ffffffffffff', '22222222-2222-2222-2222-222222222222', 2, 'Cecep Gorbachev (Operator)', 'cecep@smkn2bdg.sch.id', '\$2y\$10\$CQn/WfM6Ph6n.GXU4UgIpe8O7hsIiEgq0RZ0LyJughjeZApKUS1Ge', 'active')
        ON DUPLICATE KEY UPDATE nama_lengkap=VALUES(nama_lengkap), password=VALUES(password);");

        // 4. Seed Siswa
        $pdo->exec("INSERT INTO siswa (id, tenant_id, nisn, nis, nama_lengkap, jenis_kelamin, alamat) VALUES
            ('dddddddd-dddd-dddd-dddd-dddddddddddd', '11111111-1111-1111-1111-111111111111', '0012345678', '12345', 'Ahmad Fauzi', 'L', 'Jl. Merdeka No. 10, Jakarta'),
            ('99999999-9999-9999-9999-999999999999', '22222222-2222-2222-2222-222222222222', '0087654321', '54321', 'Rina Herawati', 'P', 'Jl. Dago No. 45, Bandung')
        ON DUPLICATE KEY UPDATE nama_lengkap=VALUES(nama_lengkap), jenis_kelamin=VALUES(jenis_kelamin);");

        // 5. Seed Pendaftaran SPMB
        $pdo->exec("INSERT INTO pendaftaran_spmb (id, tenant_id, siswa_id, nomor_pendaftaran, jalur_pendaftaran, status_pendaftaran, nilai_seleksi) VALUES
            ('eeeeeeee-eeee-eeee-eeee-eeeeeeeeeeee', '11111111-1111-1111-1111-111111111111', 'dddddddd-dddd-dddd-dddd-dddddddddddd', 'PPDB/2026/0001', 'reguler', 'diajukan', 85.50),
            ('88888888-8888-8888-8888-888888888888', '22222222-2222-2222-2222-222222222222', '99999999-9999-9999-9999-999999999999', 'PPDB/2026/0002', 'prestasi', 'diajukan', 92.00)
        ON DUPLICATE KEY UPDATE jalur_pendaftaran=VALUES(jalur_pendaftaran), nilai_seleksi=VALUES(nilai_seleksi);");
    },

    'down' => function(PDO $pdo) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec("TRUNCATE TABLE pendaftaran_spmb;");
        $pdo->exec("TRUNCATE TABLE siswa;");
        $pdo->exec("TRUNCATE TABLE users;");
        $pdo->exec("TRUNCATE TABLE tenants;");
        $pdo->exec("TRUNCATE TABLE roles;");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }
];
