<?php

/**
 * Migration PostgreSQL Schema 08: PERPUSTAKAAN (18 Tabel)
 * SSOT Reference: docs/portal_schema/perpustakaan.html
 * Format: return ['up' => closure, 'down' => closure] (WAJIB AGENTS.md)
 */

return [
    'up' => function (PDO $pdo): void {
        echo "=== INSTALLING POSTGRESQL SCHEMA 08: PERPUSTAKAAN (18 TABEL) ===\n";
        $pdo->exec("CREATE SCHEMA IF NOT EXISTS perpustakaan;");

        $pdo->exec("

            CREATE TABLE IF NOT EXISTS perpustakaan.perpus_bibliografi (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL REFERENCES core.tenants(id) ON DELETE CASCADE,
                nama_perpus_bibliografi VARCHAR(255) NULL,
                kategori VARCHAR(100) NULL,
                deskripsi TEXT NULL,
                is_active BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS perpustakaan.perpus_eksemplar (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL REFERENCES core.tenants(id) ON DELETE CASCADE,
                nama_perpus_eksemplar VARCHAR(255) NULL,
                kategori VARCHAR(100) NULL,
                deskripsi TEXT NULL,
                is_active BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS perpustakaan.perpus_sirkulasi (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL REFERENCES core.tenants(id) ON DELETE CASCADE,
                nama_perpus_sirkulasi VARCHAR(255) NULL,
                kategori VARCHAR(100) NULL,
                deskripsi TEXT NULL,
                is_active BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS perpustakaan.perpus_anggota (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL REFERENCES core.tenants(id) ON DELETE CASCADE,
                nama_perpus_anggota VARCHAR(255) NULL,
                kategori VARCHAR(100) NULL,
                deskripsi TEXT NULL,
                is_active BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS perpustakaan.perpus_denda (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL REFERENCES core.tenants(id) ON DELETE CASCADE,
                nama_perpus_denda VARCHAR(255) NULL,
                kategori VARCHAR(100) NULL,
                deskripsi TEXT NULL,
                is_active BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS perpustakaan.perpus_reservasi (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL REFERENCES core.tenants(id) ON DELETE CASCADE,
                nama_perpus_reservasi VARCHAR(255) NULL,
                kategori VARCHAR(100) NULL,
                deskripsi TEXT NULL,
                is_active BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS perpustakaan.perpus_opname (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL REFERENCES core.tenants(id) ON DELETE CASCADE,
                nama_perpus_opname VARCHAR(255) NULL,
                kategori VARCHAR(100) NULL,
                deskripsi TEXT NULL,
                is_active BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS perpustakaan.perpus_kategori_ddc (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL REFERENCES core.tenants(id) ON DELETE CASCADE,
                nama_perpus_kategori_ddc VARCHAR(255) NULL,
                kategori VARCHAR(100) NULL,
                deskripsi TEXT NULL,
                is_active BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS perpustakaan.perpus_lokasi_rak (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL REFERENCES core.tenants(id) ON DELETE CASCADE,
                nama_perpus_lokasi_rak VARCHAR(255) NULL,
                kategori VARCHAR(100) NULL,
                deskripsi TEXT NULL,
                is_active BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS perpustakaan.perpus_paket_buku (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL REFERENCES core.tenants(id) ON DELETE CASCADE,
                nama_perpus_paket_buku VARCHAR(255) NULL,
                kategori VARCHAR(100) NULL,
                deskripsi TEXT NULL,
                is_active BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS perpustakaan.perpus_event (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL REFERENCES core.tenants(id) ON DELETE CASCADE,
                nama_perpus_event VARCHAR(255) NULL,
                kategori VARCHAR(100) NULL,
                deskripsi TEXT NULL,
                is_active BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS perpustakaan.perpus_serial_berkala (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL REFERENCES core.tenants(id) ON DELETE CASCADE,
                nama_perpus_serial_berkala VARCHAR(255) NULL,
                kategori VARCHAR(100) NULL,
                deskripsi TEXT NULL,
                is_active BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS perpustakaan.perpus_staf_kompetensi (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL REFERENCES core.tenants(id) ON DELETE CASCADE,
                nama_perpus_staf_kompetensi VARCHAR(255) NULL,
                kategori VARCHAR(100) NULL,
                deskripsi TEXT NULL,
                is_active BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS perpustakaan.perpus_notifikasi (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL REFERENCES core.tenants(id) ON DELETE CASCADE,
                nama_perpus_notifikasi VARCHAR(255) NULL,
                kategori VARCHAR(100) NULL,
                deskripsi TEXT NULL,
                is_active BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS perpustakaan.perpus_buku_tamu (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL REFERENCES core.tenants(id) ON DELETE CASCADE,
                nama_perpus_buku_tamu VARCHAR(255) NULL,
                kategori VARCHAR(100) NULL,
                deskripsi TEXT NULL,
                is_active BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS perpustakaan.perpus_ulasan (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL REFERENCES core.tenants(id) ON DELETE CASCADE,
                nama_perpus_ulasan VARCHAR(255) NULL,
                kategori VARCHAR(100) NULL,
                deskripsi TEXT NULL,
                is_active BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS perpustakaan.perpus_usulan_buku (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL REFERENCES core.tenants(id) ON DELETE CASCADE,
                nama_perpus_usulan_buku VARCHAR(255) NULL,
                kategori VARCHAR(100) NULL,
                deskripsi TEXT NULL,
                is_active BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS perpustakaan.perpus_pengaturan (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID NULL REFERENCES core.tenants(id) ON DELETE CASCADE,
                nama_perpus_pengaturan VARCHAR(255) NULL,
                kategori VARCHAR(100) NULL,
                deskripsi TEXT NULL,
                is_active BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );

        ");
        echo "- Schema PERPUSTAKAAN (18 Tabel) Berhasil Dibuat.\n";
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("DROP SCHEMA IF EXISTS perpustakaan CASCADE;");
        echo "- Schema PERPUSTAKAAN Berhasil Di-drop.\n";
    }
];
