-- ════════════════════════════════════════════════════
-- INDEX GUIDE: Schema SISTEM
-- Rekomendasi index untuk performa query optimal
-- Jalankan: psql -d sinta_db -f index_guide.sql
-- ════════════════════════════════════════════════════

-- ── sistem.activity_logs ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_activity_logs_tenant ON sistem.activity_logs (tenant_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_activity_logs_created ON sistem.activity_logs (created_at DESC);

-- ── sistem.system_errors ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_system_errors_tenant ON sistem.system_errors (tenant_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_system_errors_created ON sistem.system_errors (created_at DESC);

-- ── sistem.system_jobs ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_system_jobs_tenant ON sistem.system_jobs (tenant_id);

-- ✅ Composite: filter tenant + status (query paling umum)
CREATE INDEX IF NOT EXISTS idx_system_jobs_tenant_status ON sistem.system_jobs (tenant_id, status);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_system_jobs_created ON sistem.system_jobs (created_at DESC);

-- ── sistem.tickets ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_tickets_tenant ON sistem.tickets (tenant_id);

-- ✅ Composite: filter tenant + status (query paling umum)
CREATE INDEX IF NOT EXISTS idx_tickets_tenant_status ON sistem.tickets (tenant_id, status);

-- ✅ FK JOIN index: user_id
CREATE INDEX IF NOT EXISTS idx_tickets_user_id ON sistem.tickets (user_id);

-- ✅ FK JOIN index: category_id
CREATE INDEX IF NOT EXISTS idx_tickets_category_id ON sistem.tickets (category_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_tickets_created ON sistem.tickets (created_at DESC);

-- ── sistem.ticket_replies ──
-- ✅ FK JOIN index: ticket_id
CREATE INDEX IF NOT EXISTS idx_ticket_replies_ticket_id ON sistem.ticket_replies (ticket_id);

-- ✅ FK JOIN index: user_id
CREATE INDEX IF NOT EXISTS idx_ticket_replies_user_id ON sistem.ticket_replies (user_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_ticket_replies_created ON sistem.ticket_replies (created_at DESC);

-- ── sistem.ticket_faqs ──
-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_ticket_faqs_created ON sistem.ticket_faqs (created_at DESC);

-- ── sistem.agenda_sekolah ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_agenda_sekolah_tenant ON sistem.agenda_sekolah (tenant_id);

-- ✅ FK JOIN index: created_by
CREATE INDEX IF NOT EXISTS idx_agenda_sekolah_created_by ON sistem.agenda_sekolah (created_by);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_agenda_sekolah_created ON sistem.agenda_sekolah (created_at DESC);

-- ── sistem.pengumuman ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_pengumuman_tenant ON sistem.pengumuman (tenant_id);

-- ✅ FK JOIN index: created_by
CREATE INDEX IF NOT EXISTS idx_pengumuman_created_by ON sistem.pengumuman (created_by);

-- ✅ FK JOIN index: kategori_id
CREATE INDEX IF NOT EXISTS idx_pengumuman_kategori_id ON sistem.pengumuman (kategori_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_pengumuman_created ON sistem.pengumuman (created_at DESC);
