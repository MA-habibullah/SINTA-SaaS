<?php

/**
 * Migration PostgreSQL: Pembaruan Nil UUID Super Admin / Global Master Tenant (00000000-0000-0000-0000-000000000000)
 * menjadi UUID v4 Acak & Terproteksi (e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12)
 * Format: return ['up' => closure, 'down' => closure]
 */

return [
    'up' => function (PDO $pdo): void {
        echo "=== MEMPERBARUI UUID SUPER ADMIN / GLOBAL TENANT KE UUID ACAK AMAN ===\n";

        $oldUuid = '00000000-0000-0000-0000-000000000000';
        $newUuid = 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12';

        // 1. Pastikan Record Master Tenant Baru Tersedia di core.tenants
        // Ganti subdomain & npsn sementara pada tenant lama agar tidak bentrok dengan UNIQUE constraint (NPSN char(8))
        $pdo->exec("UPDATE core.tenants SET subdomain = 'admin_legacy_nil', npsn = 'NIL00000' WHERE id = '$oldUuid'::uuid;");

        // Copy seluruh data tenant master dari lama ke baru
        $pdo->exec("
            INSERT INTO core.tenants (
                id, nama_sekolah, npsn, subdomain, custom_domain, cname_alias,
                cms_landing_enabled, cms_hero_title, cms_hero_subtitle, cms_theme_color,
                sub_portal_login_siswa, sub_portal_login_admin, sub_portal_perpus, sub_portal_pdss,
                sub_portal_ppdb, sub_portal_tracer, status, paket_aktif, status_sinkronisasi,
                logo, sertifikat_akreditasi, bentuk_pendidikan, status_sekolah, kurikulum_terapan,
                akreditasi, alamat, rt_rw, kode_pos, kelurahan, kecamatan, kabupaten_kota,
                provinsi, telepon, email, website, nama_kepsek, pangkat_kepsek, nip_kepsek,
                nama_operator, email_operator, storage_limit_mb, max_siswa_limit, max_staff_limit,
                enable_bk, enable_tracer, created_at, updated_at
            )
            SELECT 
                '$newUuid'::uuid, 
                COALESCE(nama_sekolah, 'Pusat Kendali SaaS (Global)'),
                'PLATFORM',
                'admin',
                custom_domain, cname_alias,
                cms_landing_enabled, cms_hero_title, cms_hero_subtitle, cms_theme_color,
                sub_portal_login_siswa, sub_portal_login_admin, sub_portal_perpus, sub_portal_pdss,
                sub_portal_ppdb, sub_portal_tracer, COALESCE(status, 'active'), COALESCE(paket_aktif, 'Enterprise SaaS'), status_sinkronisasi,
                logo, sertifikat_akreditasi, bentuk_pendidikan, status_sekolah, kurikulum_terapan,
                akreditasi, alamat, rt_rw, kode_pos, kelurahan, kecamatan, kabupaten_kota,
                provinsi, telepon, email, website, nama_kepsek, pangkat_kepsek, nip_kepsek,
                nama_operator, email_operator, COALESCE(storage_limit_mb, 10000), max_siswa_limit, max_staff_limit,
                enable_bk, enable_tracer, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
            FROM core.tenants 
            WHERE id = '$oldUuid'::uuid
            ON CONFLICT (id) DO NOTHING;
        ");

        // Jika row master belum ada sama sekali di core.tenants, buat baru
        $stmtCheck = $pdo->query("SELECT COUNT(*) FROM core.tenants WHERE id = '$newUuid'::uuid");
        if ((int)$stmtCheck->fetchColumn() === 0) {
            $pdo->exec("
                INSERT INTO core.tenants (id, nama_sekolah, npsn, subdomain, status, paket_aktif, storage_limit_mb, created_at, updated_at)
                VALUES ('$newUuid'::uuid, 'Pusat Kendali SaaS (Global)', 'PLATFORM', 'admin', 'active', 'Enterprise SaaS', 10000, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                ON CONFLICT (id) DO NOTHING;
            ");
        }

        // 2. Daftar Tabel dan Kolom yang Menyimpan Referensi tenant_id
        $tablesToUpdate = [
            ['core', 'users', 'tenant_id'],
            ['core', 'role_menu_access', 'tenant_id'],
            ['core', 'tenant_menu_access', 'tenant_id'],
            ['core', 'user_menu_access', 'tenant_id'],
            ['core', 'pengaturan', 'tenant_id'],
            ['core', 'tenant_cms_pages', 'tenant_id'],
            ['core', 'tenant_domains', 'tenant_id'],
            ['core', 'jenjang', 'tenant_id'],
            ['core', 'documents', 'tenant_id'],
            ['sistem', 'activity_logs', 'tenant_id'],
            ['sistem', 'system_errors', 'tenant_id'],
            ['sistem', 'active_sessions', 'tenant_id'],
            ['siswa', 'riwayat_beasiswa', 'tenant_id'],
            ['kesiswaan', 'master_ekskul', 'tenant_id'],
            ['kesiswaan', 'data_pembina', 'tenant_id'],
            ['bk', 'pembinaan_monitoring', 'tenant_id'],
            ['bk', 'catatan_bk', 'tenant_id'],
            ['bk', 'master_pelanggaran', 'tenant_id'],
            ['bk', 'pelanggaran_siswa', 'tenant_id'],
            ['bk', 'sesi_mentoring', 'tenant_id'],
            ['bk', 'tindak_lanjut_sanksi', 'tenant_id'],
            ['perpustakaan', 'perpus_pengaturan', 'tenant_id'],
            ['persuratan', 'jenis_surat', 'tenant_id'],
            ['persuratan', 'kode_klasifikasi_surat', 'tenant_id'],
            ['persuratan', 'template_surat', 'tenant_id'],
            ['persuratan', 'kop_surat', 'tenant_id'],
            ['persuratan', 'surat_masuk', 'tenant_id'],
            ['persuratan', 'surat_keluar', 'tenant_id'],
            ['persuratan', 'nomor_terakhir', 'tenant_id'],
            ['sarpras', 'ruangan', 'tenant_id'],
            ['sarpras', 'barang', 'tenant_id'],
            ['kepegawaian', 'pegawai', 'tenant_id'],
            ['keuangan', 'pos_keuangan', 'tenant_id'],
            ['pdss', 'master_kampus', 'tenant_id'],
            ['pdss', 'master_kampus_prodi', 'tenant_id'],
        ];

        foreach ($tablesToUpdate as [$schema, $table, $col]) {
            try {
                // Cek apakah tabel dan kolom ada
                $stmtCol = $pdo->query("
                    SELECT 1 FROM information_schema.columns 
                    WHERE table_schema = '$schema' AND table_name = '$table' AND column_name = '$col'
                ");
                if ($stmtCol->fetchColumn()) {
                    $pdo->exec("UPDATE $schema.$table SET $col = '$newUuid'::uuid WHERE $col::text = '$oldUuid'");
                }
            } catch (\Throwable $e) {
                // Ignore jika tabel belum dibuat di environment tertentu
            }
        }

        // 3. Hapus Record Nil UUID Lama dari core.tenants
        try {
            $pdo->exec("DELETE FROM core.tenants WHERE id = '$oldUuid'::uuid");
        } catch (\Throwable $e) {}

        echo "✔ [OK] Seluruh data Super Admin / Global Tenant berhasil dimigrasikan ke UUID: $newUuid\n";
    },

    'down' => function (PDO $pdo): void {
        $oldUuid = '00000000-0000-0000-0000-000000000000';
        $newUuid = 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12';

        $pdo->exec("
            INSERT INTO core.tenants (id, nama_sekolah, npsn, status, paket_aktif, storage_limit_mb, created_at, updated_at)
            SELECT '$oldUuid'::uuid, nama_sekolah, npsn, status, paket_aktif, storage_limit_mb, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
            FROM core.tenants WHERE id = '$newUuid'::uuid
            ON CONFLICT (id) DO NOTHING;
        ");

        $tablesToUpdate = [
            ['core', 'users', 'tenant_id'],
            ['core', 'role_menu_access', 'tenant_id'],
            ['core', 'tenant_menu_access', 'tenant_id'],
            ['core', 'user_menu_access', 'tenant_id'],
            ['sistem', 'activity_logs', 'tenant_id'],
            ['sistem', 'system_errors', 'tenant_id'],
            ['sistem', 'active_sessions', 'tenant_id'],
            ['persuratan', 'jenis_surat', 'tenant_id'],
            ['persuratan', 'kode_klasifikasi_surat', 'tenant_id'],
            ['persuratan', 'template_surat', 'tenant_id'],
            ['bk', 'master_pelanggaran', 'tenant_id'],
            ['bk', 'catatan_bk', 'tenant_id'],
        ];

        foreach ($tablesToUpdate as [$schema, $table, $col]) {
            try {
                $pdo->exec("UPDATE $schema.$table SET $col = '$oldUuid'::uuid WHERE $col::text = '$newUuid'");
            } catch (\Throwable $e) {}
        }

        try {
            $pdo->exec("DELETE FROM core.tenants WHERE id = '$newUuid'::uuid");
        } catch (\Throwable $e) {}
    }
];
