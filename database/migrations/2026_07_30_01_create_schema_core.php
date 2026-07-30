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
        ");
        echo "- Schema CORE (17 Tabel) Berhasil Dibuat.\n";
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("DROP SCHEMA IF EXISTS core CASCADE;");
        echo "- Schema CORE Berhasil Di-drop.\n";
    }
];
