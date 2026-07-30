-- ════════════════════════════════════════════════════
-- NO SELECT * GUIDE: Schema SISTEM
-- Perbandingan: ❌ SELECT * vs ✅ Explicit Columns
-- ════════════════════════════════════════════════════

-- ── sistem.activity_logs ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM sistem.activity_logs;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.user_id, t.user_role, t.module_name, t.action
FROM sistem.activity_logs t
WHERE t.tenant_id = $1;

-- ── sistem.system_errors ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM sistem.system_errors;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.error_level, t.message, t.file, t.line
FROM sistem.system_errors t
WHERE t.tenant_id = $1;

-- ── sistem.system_jobs ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM sistem.system_jobs;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.job_type, t.payload, t.status, t.attempts
FROM sistem.system_jobs t
WHERE t.tenant_id = $1;

-- ── sistem.tickets ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM sistem.tickets;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.user_id, t.category_id, t.judul, t.deskripsi
FROM sistem.tickets t
WHERE t.tenant_id = $1;

-- ── sistem.ticket_replies ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM sistem.ticket_replies;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.ticket_id, t.user_id, t.is_superadmin, t.pesan, t.created_at
FROM sistem.ticket_replies t
WHERE t.tenant_id = $1;

-- ── sistem.ticket_faqs ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM sistem.ticket_faqs;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.pertanyaan, t.jawaban, t.kategori, t.created_at
FROM sistem.ticket_faqs t
WHERE t.tenant_id = $1;

-- ── sistem.agenda_sekolah ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM sistem.agenda_sekolah;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.created_by, t.judul, t.deskripsi, t.tanggal_mulai
FROM sistem.agenda_sekolah t
WHERE t.tenant_id = $1;

-- ── sistem.pengumuman ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM sistem.pengumuman;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.created_by, t.judul, t.isi_pengumuman, t.lampiran_file
FROM sistem.pengumuman t
WHERE t.tenant_id = $1;
