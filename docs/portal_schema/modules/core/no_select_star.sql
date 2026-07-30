-- ════════════════════════════════════════════════════
-- NO SELECT * GUIDE: Schema CORE
-- Perbandingan: ❌ SELECT * vs ✅ Explicit Columns
-- ════════════════════════════════════════════════════

-- ── core.tenants ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM core.tenants;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.nama_sekolah, t.npsn, t.subdomain, t.custom_domain, t.cname_alias
FROM core.tenants t
WHERE t.tenant_id = $1;

-- ── core.tenant_menu_access ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM core.tenant_menu_access;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.menu_id, t.created_at, t.updated_at
FROM core.tenant_menu_access t
WHERE t.tenant_id = $1;

-- ── core.menus ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM core.menus;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.nama_menu, t.url, t.icon, t.parent_id, t.urutan
FROM core.menus t
WHERE t.tenant_id = $1;

-- ── core.users ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM core.users;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.role_id, t.nama_lengkap, t.email, t.password
FROM core.users t
WHERE t.tenant_id = $1;

-- ── core.user_roles ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM core.user_roles;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.user_id, t.role_id
FROM core.user_roles t
WHERE t.tenant_id = $1;

-- ── core.roles ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM core.roles;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.nama_role, t.deskripsi, t.created_at, t.updated_at
FROM core.roles t
WHERE t.tenant_id = $1;

-- ── core.role_menu_access ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM core.role_menu_access;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.tenant_id, t.role_id, t.menu_id
FROM core.role_menu_access t
WHERE t.tenant_id = $1;

-- ── core.user_menu_access ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM core.user_menu_access;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.user_id, t.menu_id, t.tenant_id, t.created_at
FROM core.user_menu_access t
WHERE t.tenant_id = $1;

-- ── core.active_sessions ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM core.active_sessions;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.user_id, t.tenant_id, t.ip_address, t.user_agent, t.tanggal_login
FROM core.active_sessions t
WHERE t.tenant_id = $1;

-- ── core.pengaturan ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM core.pengaturan;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id_pengaturan, t.nama_pengaturan, t.nilai
FROM core.pengaturan t
WHERE t.tenant_id = $1;

-- ── core.jenjang ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM core.jenjang;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.kode_jenjang, t.nama_jenjang, t.is_active, t.created_at
FROM core.jenjang t
WHERE t.tenant_id = $1;

-- ── core.provinsi ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM core.provinsi;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id_provinsi, t.nama_provinsi
FROM core.provinsi t
WHERE t.tenant_id = $1;

-- ── core.kota ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM core.kota;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id_kota, t.id_provinsi, t.nama_kota
FROM core.kota t
WHERE t.tenant_id = $1;

-- ── core.kecamatan ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM core.kecamatan;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id_kecamatan, t.id_kota, t.nama_kecamatan
FROM core.kecamatan t
WHERE t.tenant_id = $1;

-- ── core.kelurahan ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM core.kelurahan;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id_kelurahan, t.id_kecamatan, t.nama_kelurahan
FROM core.kelurahan t
WHERE t.tenant_id = $1;

-- ── core.tenant_cms_pages ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM core.tenant_cms_pages;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.slug, t.title, t.page_type, t.target_sub_portal
FROM core.tenant_cms_pages t
WHERE t.tenant_id = $1;

-- ── core.tenant_domains ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM core.tenant_domains;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.domain_name, t.domain_type, t.target_module, t.verification_token
FROM core.tenant_domains t
WHERE t.tenant_id = $1;
