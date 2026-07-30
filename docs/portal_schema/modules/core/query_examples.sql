-- ════════════════════════════════════════════════════
-- QUERY EXAMPLES: Schema CORE
-- Contoh query optimal: JOIN + Explicit Columns + Index
-- ════════════════════════════════════════════════════

-- ── core.tenants ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: core.tenants
SELECT
    t.id,
    t.nama_sekolah,
    t.npsn,
    t.subdomain,
    t.custom_domain,
    t.cname_alias,
    t.cms_landing_enabled,
    t.cms_hero_title
FROM core.tenants t
-- (tabel standalone, tidak ada FK JOIN diperlukan)
WHERE t.status = 'aktif' -- ✅ Manfaatkan composite index
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── core.tenant_menu_access ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: core.tenant_menu_access
SELECT
    t.id,
    t.tenant_id,
    t.menu_id,
    t.created_at,
    t.updated_at
FROM core.tenant_menu_access t
INNER JOIN tenants tena ON t.tenant_id = tena.id
INNER JOIN menus menu ON t.menu_id = menu.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── core.menus ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: core.menus
SELECT
    t.id,
    t.nama_menu,
    t.url,
    t.icon,
    t.parent_id,
    t.urutan,
    t.created_at,
    t.updated_at
FROM core.menus t
INNER JOIN menus menu ON t.parent_id = menu.id
WHERE t.id = $1
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── core.users ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: core.users
SELECT
    t.id,
    t.tenant_id,
    t.role_id,
    t.nama_lengkap,
    t.email,
    t.password,
    t.status,
    t.created_at
FROM core.users t
INNER JOIN tenants tena ON t.tenant_id = tena.id
INNER JOIN roles role ON t.role_id = role.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
  AND t.status = 'aktif' -- ✅ Manfaatkan composite index
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── core.user_roles ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: core.user_roles
SELECT
    t.user_id,
    t.role_id
FROM core.user_roles t
INNER JOIN users user ON t.user_id = user.id
INNER JOIN roles role ON t.role_id = role.id
WHERE t.id = $1
ORDER BY t.role_id DESC
LIMIT 50 OFFSET 0;

-- ── core.roles ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: core.roles
SELECT
    t.id,
    t.nama_role,
    t.deskripsi,
    t.created_at,
    t.updated_at
FROM core.roles t
-- (tabel standalone, tidak ada FK JOIN diperlukan)
WHERE t.id = $1
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── core.role_menu_access ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: core.role_menu_access
SELECT
    t.tenant_id,
    t.role_id,
    t.menu_id
FROM core.role_menu_access t
INNER JOIN roles role ON t.role_id = role.id
INNER JOIN menus menu ON t.menu_id = menu.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.role_id DESC
LIMIT 50 OFFSET 0;

-- ── core.user_menu_access ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: core.user_menu_access
SELECT
    t.id,
    t.user_id,
    t.menu_id,
    t.tenant_id,
    t.created_at
FROM core.user_menu_access t
INNER JOIN users user ON t.user_id = user.id
INNER JOIN menus menu ON t.menu_id = menu.id
INNER JOIN tenants tena ON t.tenant_id = tena.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── core.active_sessions ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: core.active_sessions
SELECT
    t.id,
    t.user_id,
    t.tenant_id,
    t.ip_address,
    t.user_agent,
    t.tanggal_login,
    t.last_activity
FROM core.active_sessions t
INNER JOIN tenants tena ON t.tenant_id = tena.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.user_id DESC
LIMIT 50 OFFSET 0;

-- ── core.pengaturan ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: core.pengaturan
SELECT
    t.id_pengaturan,
    t.nama_pengaturan,
    t.nilai
FROM core.pengaturan t
-- (tabel standalone, tidak ada FK JOIN diperlukan)
WHERE t.id = $1
ORDER BY t.nama_pengaturan DESC
LIMIT 50 OFFSET 0;

-- ── core.jenjang ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: core.jenjang
SELECT
    t.id,
    t.tenant_id,
    t.kode_jenjang,
    t.nama_jenjang,
    t.is_active,
    t.created_at,
    t.updated_at,
    t.deleted_at
FROM core.jenjang t
INNER JOIN tenants tena ON t.tenant_id = tena.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
  AND t.is_active = true
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── core.provinsi ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: core.provinsi
SELECT
    t.id_provinsi,
    t.nama_provinsi
FROM core.provinsi t
-- (tabel standalone, tidak ada FK JOIN diperlukan)
WHERE t.id = $1
ORDER BY t.nama_provinsi DESC
LIMIT 50 OFFSET 0;

-- ── core.kota ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: core.kota
SELECT
    t.id_kota,
    t.id_provinsi,
    t.nama_kota
FROM core.kota t
INNER JOIN provinsi prov ON t.id_provinsi = prov.id_provinsi
WHERE t.id = $1
ORDER BY t.id_provinsi DESC
LIMIT 50 OFFSET 0;

-- ── core.kecamatan ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: core.kecamatan
SELECT
    t.id_kecamatan,
    t.id_kota,
    t.nama_kecamatan
FROM core.kecamatan t
INNER JOIN kota kota ON t.id_kota = kota.id_kota
WHERE t.id = $1
ORDER BY t.id_kota DESC
LIMIT 50 OFFSET 0;

-- ── core.kelurahan ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: core.kelurahan
SELECT
    t.id_kelurahan,
    t.id_kecamatan,
    t.nama_kelurahan
FROM core.kelurahan t
INNER JOIN kecamatan keca ON t.id_kecamatan = keca.id_kecamatan
WHERE t.id = $1
ORDER BY t.id_kecamatan DESC
LIMIT 50 OFFSET 0;

-- ── core.tenant_cms_pages ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: core.tenant_cms_pages
SELECT
    t.id,
    t.tenant_id,
    t.slug,
    t.title,
    t.page_type,
    t.target_sub_portal,
    t.content_html,
    t.is_published
FROM core.tenant_cms_pages t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── core.tenant_domains ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: core.tenant_domains
SELECT
    t.id,
    t.tenant_id,
    t.domain_name,
    t.domain_type,
    t.target_module,
    t.verification_token,
    t.is_verified,
    t.ssl_status
FROM core.tenant_domains t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
  AND t.is_active = true
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;
