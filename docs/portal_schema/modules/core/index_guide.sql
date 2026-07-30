-- ════════════════════════════════════════════════════
-- INDEX GUIDE: Schema CORE
-- Rekomendasi index untuk performa query optimal
-- Jalankan: psql -d sinta_db -f index_guide.sql
-- ════════════════════════════════════════════════════

-- ── core.tenants ──
-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_tenants_created ON core.tenants (created_at DESC);

-- ── core.tenant_menu_access ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_tenant_menu_access_tenant ON core.tenant_menu_access (tenant_id);

-- ✅ FK JOIN index: menu_id
CREATE INDEX IF NOT EXISTS idx_tenant_menu_access_menu_id ON core.tenant_menu_access (menu_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_tenant_menu_access_created ON core.tenant_menu_access (created_at DESC);

-- ── core.menus ──
-- ✅ FK JOIN index: parent_id
CREATE INDEX IF NOT EXISTS idx_menus_parent_id ON core.menus (parent_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_menus_created ON core.menus (created_at DESC);

-- ── core.users ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_users_tenant ON core.users (tenant_id);

-- ✅ Composite: filter tenant + status (query paling umum)
CREATE INDEX IF NOT EXISTS idx_users_tenant_status ON core.users (tenant_id, status);

-- ✅ FK JOIN index: role_id
CREATE INDEX IF NOT EXISTS idx_users_role_id ON core.users (role_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_users_created ON core.users (created_at DESC);

-- ── core.user_roles ──
-- ✅ FK JOIN index: user_id
CREATE INDEX IF NOT EXISTS idx_user_roles_user_id ON core.user_roles (user_id);

-- ✅ FK JOIN index: role_id
CREATE INDEX IF NOT EXISTS idx_user_roles_role_id ON core.user_roles (role_id);

-- ── core.roles ──
-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_roles_created ON core.roles (created_at DESC);

-- ── core.role_menu_access ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_role_menu_access_tenant ON core.role_menu_access (tenant_id);

-- ✅ FK JOIN index: role_id
CREATE INDEX IF NOT EXISTS idx_role_menu_access_role_id ON core.role_menu_access (role_id);

-- ✅ FK JOIN index: menu_id
CREATE INDEX IF NOT EXISTS idx_role_menu_access_menu_id ON core.role_menu_access (menu_id);

-- ── core.user_menu_access ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_user_menu_access_tenant ON core.user_menu_access (tenant_id);

-- ✅ FK JOIN index: user_id
CREATE INDEX IF NOT EXISTS idx_user_menu_access_user_id ON core.user_menu_access (user_id);

-- ✅ FK JOIN index: menu_id
CREATE INDEX IF NOT EXISTS idx_user_menu_access_menu_id ON core.user_menu_access (menu_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_user_menu_access_created ON core.user_menu_access (created_at DESC);

-- ── core.active_sessions ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_active_sessions_tenant ON core.active_sessions (tenant_id);

-- ── core.pengaturan ──
-- Tabel pengaturan: sudah memiliki index optimal (PK only)

-- ── core.jenjang ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_jenjang_tenant ON core.jenjang (tenant_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_jenjang_created ON core.jenjang (created_at DESC);

-- ── core.provinsi ──
-- Tabel provinsi: sudah memiliki index optimal (PK only)

-- ── core.kota ──
-- ✅ FK JOIN index: id_provinsi
CREATE INDEX IF NOT EXISTS idx_kota_id_provinsi ON core.kota (id_provinsi);

-- ── core.kecamatan ──
-- ✅ FK JOIN index: id_kota
CREATE INDEX IF NOT EXISTS idx_kecamatan_id_kota ON core.kecamatan (id_kota);

-- ── core.kelurahan ──
-- ✅ FK JOIN index: id_kecamatan
CREATE INDEX IF NOT EXISTS idx_kelurahan_id_kecamatan ON core.kelurahan (id_kecamatan);

-- ── core.tenant_cms_pages ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_tenant_cms_pages_tenant ON core.tenant_cms_pages (tenant_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_tenant_cms_pages_created ON core.tenant_cms_pages (created_at DESC);

-- ── core.tenant_domains ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_tenant_domains_tenant ON core.tenant_domains (tenant_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_tenant_domains_created ON core.tenant_domains (created_at DESC);
