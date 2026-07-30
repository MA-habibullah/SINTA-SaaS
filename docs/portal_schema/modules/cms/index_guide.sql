-- ════════════════════════════════════════════════════
-- INDEX GUIDE: Schema CMS
-- Rekomendasi index untuk performa query optimal
-- Jalankan: psql -d sinta_db -f index_guide.sql
-- ════════════════════════════════════════════════════

-- ── cms.cms_settings ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_cms_settings_tenant ON cms.cms_settings (tenant_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_cms_settings_created ON cms.cms_settings (created_at DESC);

-- ── cms.cms_banners ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_cms_banners_tenant ON cms.cms_banners (tenant_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_cms_banners_created ON cms.cms_banners (created_at DESC);

-- ── cms.cms_posts ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_cms_posts_tenant ON cms.cms_posts (tenant_id);

-- ✅ Composite: filter tenant + status (query paling umum)
CREATE INDEX IF NOT EXISTS idx_cms_posts_tenant_status ON cms.cms_posts (tenant_id, status);

-- ✅ FK JOIN index: category_id
CREATE INDEX IF NOT EXISTS idx_cms_posts_category_id ON cms.cms_posts (category_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_cms_posts_created ON cms.cms_posts (created_at DESC);

-- ── cms.cms_categories ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_cms_categories_tenant ON cms.cms_categories (tenant_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_cms_categories_created ON cms.cms_categories (created_at DESC);

-- ── cms.cms_pages ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_cms_pages_tenant ON cms.cms_pages (tenant_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_cms_pages_created ON cms.cms_pages (created_at DESC);

-- ── cms.cms_galleries ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_cms_galleries_tenant ON cms.cms_galleries (tenant_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_cms_galleries_created ON cms.cms_galleries (created_at DESC);

-- ── cms.cms_widgets ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_cms_widgets_tenant ON cms.cms_widgets (tenant_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_cms_widgets_created ON cms.cms_widgets (created_at DESC);

-- ── cms.cms_menus ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_cms_menus_tenant ON cms.cms_menus (tenant_id);

-- ✅ FK JOIN index: parent_id
CREATE INDEX IF NOT EXISTS idx_cms_menus_parent_id ON cms.cms_menus (parent_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_cms_menus_created ON cms.cms_menus (created_at DESC);
