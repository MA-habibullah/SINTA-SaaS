-- ════════════════════════════════════════════════════
-- NO SELECT * GUIDE: Schema CMS
-- Perbandingan: ❌ SELECT * vs ✅ Explicit Columns
-- ════════════════════════════════════════════════════

-- ── cms.cms_settings ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM cms.cms_settings;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.site_title, t.site_tagline, t.logo_url, t.favicon_url
FROM cms.cms_settings t
WHERE t.tenant_id = $1;

-- ── cms.cms_banners ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM cms.cms_banners;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.title, t.subtitle, t.image_url, t.cta_text
FROM cms.cms_banners t
WHERE t.tenant_id = $1;

-- ── cms.cms_posts ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM cms.cms_posts;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.category_id, t.title, t.slug, t.excerpt
FROM cms.cms_posts t
WHERE t.tenant_id = $1;

-- ── cms.cms_categories ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM cms.cms_categories;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.name, t.slug, t.description, t.created_at
FROM cms.cms_categories t
WHERE t.tenant_id = $1;

-- ── cms.cms_pages ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM cms.cms_pages;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.slug, t.title, t.content_html, t.builder_json
FROM cms.cms_pages t
WHERE t.tenant_id = $1;

-- ── cms.cms_galleries ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM cms.cms_galleries;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.title, t.cover_image, t.media_type, t.media_items_json
FROM cms.cms_galleries t
WHERE t.tenant_id = $1;

-- ── cms.cms_widgets ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM cms.cms_widgets;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.widget_name, t.widget_type, t.config_json, t.position_section
FROM cms.cms_widgets t
WHERE t.tenant_id = $1;

-- ── cms.cms_menus ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM cms.cms_menus;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.parent_id, t.title, t.link_type, t.target_route
FROM cms.cms_menus t
WHERE t.tenant_id = $1;
