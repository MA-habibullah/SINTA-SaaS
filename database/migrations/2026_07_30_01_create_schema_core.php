<?php

/**
 * Migration PostgreSQL Schema 01: CORE (17 Tabel)
 * Format: return ['up' => closure, 'down' => closure] (WAJIB AGENTS.md)
 */

return [
    'up' => function (PDO $pdo): void {
        echo "=== INSTALLING POSTGRESQL SCHEMA 01: CORE (17 TABEL) ===\n";
        $pdo->exec("CREATE SCHEMA IF NOT EXISTS core;");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS core.tenants (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                nama_sekolah VARCHAR(255) NOT NULL,
                npsn CHAR(8) NOT NULL UNIQUE,
                subdomain VARCHAR(100) NOT NULL UNIQUE,
                custom_domain VARCHAR(255) NULL,
                cname_alias VARCHAR(255) NULL,
                cms_landing_enabled BOOLEAN NOT NULL DEFAULT true,
                cms_hero_title VARCHAR(255) NULL,
                cms_hero_subtitle TEXT NULL,
                cms_theme_color VARCHAR(30) NOT NULL DEFAULT '#1565C0',
                sub_portal_login_siswa VARCHAR(100) NULL DEFAULT 'siswa',
                sub_portal_login_admin VARCHAR(100) NULL DEFAULT 'admin',
                sub_portal_perpus VARCHAR(100) NULL DEFAULT 'perpustakaan',
                sub_portal_pdss VARCHAR(100) NULL DEFAULT 'pdss',
                sub_portal_ppdb VARCHAR(100) NULL DEFAULT 'ppdb',
                sub_portal_tracer VARCHAR(100) NULL DEFAULT 'tracer',
                status VARCHAR(20) NULL DEFAULT 'active',
                paket_aktif VARCHAR(50) NOT NULL DEFAULT 'Premium SaaS',
                status_sinkronisasi VARCHAR(50) NOT NULL DEFAULT 'Tersinkronisasi',
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS core.tenant_menu_access (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NOT NULL REFERENCES core.tenants(id) ON DELETE CASCADE,
                menu_id UUID NOT NULL,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS core.menus (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                nama_menu VARCHAR(100) NOT NULL,
                url VARCHAR(255) NOT NULL,
                icon VARCHAR(100) NULL,
                parent_id UUID NULL REFERENCES core.menus(id) ON DELETE SET NULL,
                urutan INT NOT NULL DEFAULT 0,
                is_active BOOLEAN NOT NULL DEFAULT true
            );

            CREATE TABLE IF NOT EXISTS core.roles (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                nama_role VARCHAR(100) NOT NULL UNIQUE,
                deskripsi TEXT NULL,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS core.users (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NOT NULL REFERENCES core.tenants(id) ON DELETE CASCADE,
                role_id UUID NOT NULL REFERENCES core.roles(id) ON DELETE RESTRICT,
                nama_lengkap VARCHAR(255) NOT NULL,
                username VARCHAR(100) NOT NULL,
                email VARCHAR(255) NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                is_active BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT uq_users_tenant_email UNIQUE(tenant_id, email)
            );

            CREATE TABLE IF NOT EXISTS core.user_roles (
                user_id UUID NOT NULL REFERENCES core.users(id) ON DELETE CASCADE,
                role_id UUID NOT NULL REFERENCES core.roles(id) ON DELETE CASCADE,
                PRIMARY KEY (user_id, role_id)
            );

            CREATE TABLE IF NOT EXISTS core.role_menu_access (
                tenant_id UUID NOT NULL REFERENCES core.tenants(id) ON DELETE CASCADE,
                role_id UUID NOT NULL REFERENCES core.roles(id) ON DELETE CASCADE,
                menu_id UUID NOT NULL REFERENCES core.menus(id) ON DELETE CASCADE,
                PRIMARY KEY (tenant_id, role_id, menu_id)
            );

            CREATE TABLE IF NOT EXISTS core.user_menu_access (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NOT NULL REFERENCES core.tenants(id) ON DELETE CASCADE,
                user_id UUID NOT NULL REFERENCES core.users(id) ON DELETE CASCADE,
                menu_id UUID NOT NULL REFERENCES core.menus(id) ON DELETE CASCADE,
                is_allowed BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS core.active_sessions (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NOT NULL REFERENCES core.tenants(id) ON DELETE CASCADE,
                user_id UUID NOT NULL REFERENCES core.users(id) ON DELETE CASCADE,
                session_token VARCHAR(255) NOT NULL UNIQUE,
                ip_address VARCHAR(45) NULL,
                user_agent TEXT NULL,
                last_activity TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS core.pengaturan (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NOT NULL REFERENCES core.tenants(id) ON DELETE CASCADE,
                nama_pengaturan VARCHAR(100) NOT NULL,
                nilai TEXT NULL,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT uq_pengaturan_tenant_key UNIQUE(tenant_id, nama_pengaturan)
            );

            CREATE TABLE IF NOT EXISTS core.jenjang (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NOT NULL REFERENCES core.tenants(id) ON DELETE CASCADE,
                kode_jenjang VARCHAR(20) NOT NULL,
                nama_jenjang VARCHAR(100) NOT NULL,
                is_active BOOLEAN NOT NULL DEFAULT true
            );

            CREATE TABLE IF NOT EXISTS core.provinsi (
                id_provinsi VARCHAR(10) PRIMARY KEY,
                nama_provinsi VARCHAR(100) NOT NULL
            );

            CREATE TABLE IF NOT EXISTS core.kota (
                id_kota VARCHAR(10) PRIMARY KEY,
                id_provinsi VARCHAR(10) NOT NULL REFERENCES core.provinsi(id_provinsi) ON DELETE CASCADE,
                nama_kota VARCHAR(100) NOT NULL
            );

            CREATE TABLE IF NOT EXISTS core.kecamatan (
                id_kecamatan VARCHAR(10) PRIMARY KEY,
                id_kota VARCHAR(10) NOT NULL REFERENCES core.kota(id_kota) ON DELETE CASCADE,
                nama_kecamatan VARCHAR(100) NOT NULL
            );

            CREATE TABLE IF NOT EXISTS core.kelurahan (
                id_kelurahan VARCHAR(10) PRIMARY KEY,
                id_kecamatan VARCHAR(10) NOT NULL REFERENCES core.kecamatan(id_kecamatan) ON DELETE CASCADE,
                nama_kelurahan VARCHAR(100) NOT NULL
            );

            CREATE TABLE IF NOT EXISTS core.tenant_cms_pages (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NOT NULL REFERENCES core.tenants(id) ON DELETE CASCADE,
                slug VARCHAR(150) NOT NULL,
                title VARCHAR(255) NOT NULL,
                content TEXT NULL,
                is_published BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS core.tenant_domains (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NOT NULL REFERENCES core.tenants(id) ON DELETE CASCADE,
                domain_name VARCHAR(255) NOT NULL UNIQUE,
                domain_type VARCHAR(50) NOT NULL DEFAULT 'subdomain',
                is_verified BOOLEAN NOT NULL DEFAULT false,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );

            /* =========================================================
               CORE TICKETING & PUSAT BANTUAN SAAS
               ========================================================= */
            CREATE TABLE IF NOT EXISTS core.ticket_categories (
                id SERIAL PRIMARY KEY,
                nama_kategori VARCHAR(100) NOT NULL,
                deskripsi TEXT NULL,
                sla_hours INT NOT NULL DEFAULT 48,
                is_active BOOLEAN NOT NULL DEFAULT TRUE,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS core.tickets (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL REFERENCES core.tenants(id) ON DELETE CASCADE,
                user_id UUID NULL REFERENCES core.users(id) ON DELETE SET NULL,
                category_id INT NOT NULL,
                nomor_tiket VARCHAR(50) NULL,
                judul VARCHAR(255) NOT NULL,
                deskripsi TEXT NOT NULL,
                urgensi VARCHAR(50) NOT NULL DEFAULT 'Sedang',
                status VARCHAR(50) NOT NULL DEFAULT 'Menunggu',
                lampiran TEXT NULL,
                user_agent TEXT NULL,
                last_url TEXT NULL,
                sla_deadline TIMESTAMP WITH TIME ZONE NULL,
                user_unread BOOLEAN NOT NULL DEFAULT FALSE,
                admin_unread BOOLEAN NOT NULL DEFAULT TRUE,
                is_active BOOLEAN NOT NULL DEFAULT TRUE,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );

            CREATE INDEX IF NOT EXISTS idx_tickets_tenant ON core.tickets (tenant_id);
            CREATE INDEX IF NOT EXISTS idx_tickets_user ON core.tickets (user_id);
            CREATE INDEX IF NOT EXISTS idx_tickets_status ON core.tickets (status);

            CREATE TABLE IF NOT EXISTS core.ticket_replies (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                ticket_id UUID NOT NULL REFERENCES core.tickets(id) ON DELETE CASCADE,
                user_id UUID NULL REFERENCES core.users(id) ON DELETE SET NULL,
                is_superadmin BOOLEAN NOT NULL DEFAULT FALSE,
                pesan TEXT NOT NULL,
                lampiran TEXT NULL,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );

            CREATE INDEX IF NOT EXISTS idx_ticket_replies_ticket ON core.ticket_replies (ticket_id);

            CREATE TABLE IF NOT EXISTS core.ticket_canned_responses (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                judul VARCHAR(200) NOT NULL,
                konten TEXT NOT NULL,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS core.ticket_faqs (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                category_id INT NULL,
                pertanyaan VARCHAR(255) NOT NULL,
                jawaban TEXT NOT NULL,
                is_active BOOLEAN NOT NULL DEFAULT TRUE,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );
        ");

        // Seed default categories if empty
        $stmtCheck = $pdo->query("SELECT COUNT(*) FROM core.ticket_categories");
        if ((int)$stmtCheck->fetchColumn() === 0) {
            $pdo->exec("
                INSERT INTO core.ticket_categories (nama_kategori, deskripsi, sla_hours) VALUES
                ('Teknis / Sistem', 'Kendala teknis, error aplikasi, bug, atau gangguan performa sistem.', 24),
                ('Akun & Akses', 'Permintaan reset password, penyesuaian hak akses RBAC, atau akun terkunci.', 12),
                ('Keuangan & SPP', 'Permasalahan integrasi pembayaran, pos tarif tagihan, atau jurnal kas.', 24),
                ('Pertanyaan Umum', 'Panduan penggunaan modul, pertanyaan fitur, dan konsultasi administrasi.', 48);
            ");
        }

        echo "- Schema CORE (22 Tabel) Berhasil Dibuat.\n";
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("DROP SCHEMA IF EXISTS core CASCADE;");
        echo "- Schema CORE Berhasil Di-drop.\n";
    }
];
