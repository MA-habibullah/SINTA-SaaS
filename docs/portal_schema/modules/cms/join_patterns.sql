-- ════════════════════════════════════════════════════
-- JOIN PATTERNS: Schema CMS
-- Panduan JOIN yang benar berdasarkan FK Constraint
-- ════════════════════════════════════════════════════

-- ── Tabel: cms.cms_settings ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: cms.cms_settings
SELECT
    t.id,
    t.tenant_id,
    t.site_title,
    t.site_tagline,
    t.logo_url,
    t.favicon_url,
    t.primary_color,
    t.secondary_color
FROM cms.cms_settings t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: cms.cms_banners ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: cms.cms_banners
SELECT
    t.id,
    t.tenant_id,
    t.title,
    t.subtitle,
    t.image_url,
    t.cta_text,
    t.cta_link,
    t.order_index
FROM cms.cms_banners t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
  AND t.is_active = true
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: cms.cms_posts ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: cms.cms_posts
SELECT
    t.id,
    t.tenant_id,
    t.category_id,
    t.title,
    t.slug,
    t.excerpt,
    t.content_html,
    t.featured_image
FROM cms.cms_posts t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
INNER JOIN cms.cms_categories cms_ ON t.category_id = cms_.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
  AND t.status = 'aktif' -- ✅ Manfaatkan composite index
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: cms.cms_categories ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: cms.cms_categories
SELECT
    t.id,
    t.tenant_id,
    t.name,
    t.slug,
    t.description,
    t.created_at
FROM cms.cms_categories t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: cms.cms_pages ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: cms.cms_pages
SELECT
    t.id,
    t.tenant_id,
    t.slug,
    t.title,
    t.content_html,
    t.builder_json,
    t.meta_description,
    t.is_active
FROM cms.cms_pages t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
  AND t.is_active = true
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: cms.cms_galleries ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: cms.cms_galleries
SELECT
    t.id,
    t.tenant_id,
    t.title,
    t.cover_image,
    t.media_type,
    t.media_items_json,
    t.event_date,
    t.is_active
FROM cms.cms_galleries t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
  AND t.is_active = true
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: cms.cms_widgets ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: cms.cms_widgets
SELECT
    t.id,
    t.tenant_id,
    t.widget_name,
    t.widget_type,
    t.config_json,
    t.position_section,
    t.order_index,
    t.is_active
FROM cms.cms_widgets t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
  AND t.is_active = true
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: cms.cms_menus ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: cms.cms_menus
SELECT
    t.id,
    t.tenant_id,
    t.parent_id,
    t.title,
    t.link_type,
    t.target_route,
    t.target_sub_portal,
    t.icon_class
FROM cms.cms_menus t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
INNER JOIN cms.cms_menus cms_ ON t.parent_id = cms_.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
  AND t.is_active = true
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;
