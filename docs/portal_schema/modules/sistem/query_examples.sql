-- ════════════════════════════════════════════════════
-- QUERY EXAMPLES: Schema SISTEM
-- Contoh query optimal: JOIN + Explicit Columns + Index
-- ════════════════════════════════════════════════════

-- ── sistem.activity_logs ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: sistem.activity_logs
SELECT
    t.id,
    t.tenant_id,
    t.user_id,
    t.user_role,
    t.module_name,
    t.action,
    t.table_name,
    t.record_id
FROM sistem.activity_logs t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── sistem.system_errors ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: sistem.system_errors
SELECT
    t.id,
    t.tenant_id,
    t.error_level,
    t.message,
    t.file,
    t.line,
    t.trace,
    t.request_url
FROM sistem.system_errors t
-- (tabel standalone, tidak ada FK JOIN diperlukan)
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── sistem.system_jobs ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: sistem.system_jobs
SELECT
    t.id,
    t.tenant_id,
    t.job_type,
    t.payload,
    t.status,
    t.attempts,
    t.error_message,
    t.reserved_at
FROM sistem.system_jobs t
INNER JOIN tenants tena ON t.tenant_id = tena.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
  AND t.status = 'aktif' -- ✅ Manfaatkan composite index
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── sistem.tickets ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: sistem.tickets
SELECT
    t.id,
    t.tenant_id,
    t.user_id,
    t.category_id,
    t.judul,
    t.deskripsi,
    t.urgensi,
    t.status
FROM sistem.tickets t
INNER JOIN tenants tena ON t.tenant_id = tena.id
INNER JOIN users user ON t.user_id = user.id
INNER JOIN ticket_categories tick ON t.category_id = tick.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
  AND t.status = 'aktif' -- ✅ Manfaatkan composite index
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── sistem.ticket_replies ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: sistem.ticket_replies
SELECT
    t.id,
    t.ticket_id,
    t.user_id,
    t.is_superadmin,
    t.pesan,
    t.created_at
FROM sistem.ticket_replies t
INNER JOIN tickets tick ON t.ticket_id = tick.id
INNER JOIN users user ON t.user_id = user.id
WHERE t.id = $1
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── sistem.ticket_faqs ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: sistem.ticket_faqs
SELECT
    t.id,
    t.pertanyaan,
    t.jawaban,
    t.kategori,
    t.created_at
FROM sistem.ticket_faqs t
-- (tabel standalone, tidak ada FK JOIN diperlukan)
WHERE t.id = $1
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── sistem.agenda_sekolah ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: sistem.agenda_sekolah
SELECT
    t.id,
    t.tenant_id,
    t.created_by,
    t.judul,
    t.deskripsi,
    t.tanggal_mulai,
    t.tanggal_selesai,
    t.waktu
FROM sistem.agenda_sekolah t
INNER JOIN tenants tena ON t.tenant_id = tena.id
INNER JOIN users user ON t.created_by = user.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── sistem.pengumuman ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: sistem.pengumuman
SELECT
    t.id,
    t.tenant_id,
    t.created_by,
    t.judul,
    t.isi_pengumuman,
    t.lampiran_file,
    t.visibilitas,
    t.target_roles
FROM sistem.pengumuman t
INNER JOIN tenants tena ON t.tenant_id = tena.id
INNER JOIN users user ON t.created_by = user.id
INNER JOIN kategori_pengumuman kate ON t.kategori_id = kate.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
  AND t.is_active = true
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;
