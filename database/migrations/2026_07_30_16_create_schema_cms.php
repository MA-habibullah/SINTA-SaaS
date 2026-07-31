<?php

/**
 * Migration PostgreSQL Schema 16: CMS (8 Tabel)
 * Format: return ['up' => closure, 'down' => closure]
 */

return [
    'up' => function (PDO $pdo): void {
        echo "=== INSTALLING POSTGRESQL SCHEMA 16: CMS (8 TABEL) ===\n";
        $pdo->exec("CREATE SCHEMA IF NOT EXISTS cms;");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS cms.cms_banners (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL,
                judul VARCHAR(255) NULL,
                kategori VARCHAR(100) NULL,
                deskripsi TEXT NULL,
                is_active BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS cms.cms_categories (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL,
                nama_cms_categories VARCHAR(255) NULL,
                kategori VARCHAR(100) NULL,
                deskripsi TEXT NULL,
                is_active BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS cms.cms_galleries (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL,
                nama_cms_galleries VARCHAR(255) NULL,
                kategori VARCHAR(100) NULL,
                deskripsi TEXT NULL,
                is_active BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS cms.cms_menus (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL,
                nama_cms_menus VARCHAR(255) NULL,
                kategori VARCHAR(100) NULL,
                deskripsi TEXT NULL,
                is_active BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS cms.cms_pages (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL,
                nama_cms_pages VARCHAR(255) NULL,
                kategori VARCHAR(100) NULL,
                deskripsi TEXT NULL,
                is_active BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS cms.cms_posts (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL,
                nama_cms_posts VARCHAR(255) NULL,
                kategori VARCHAR(100) NULL,
                deskripsi TEXT NULL,
                is_active BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS cms.cms_settings (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL,
                nama_cms_settings VARCHAR(255) NULL,
                kategori VARCHAR(100) NULL,
                deskripsi TEXT NULL,
                is_active BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS cms.cms_widgets (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL,
                nama_cms_widgets VARCHAR(255) NULL,
                kategori VARCHAR(100) NULL,
                deskripsi TEXT NULL,
                is_active BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP
            );

        ");
        echo "- Schema CMS (8 Tabel) Berhasil Dibuat.\n";
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("DROP SCHEMA IF EXISTS cms CASCADE;");
        echo "- Schema CMS Berhasil Di-drop.\n";
    }
];
