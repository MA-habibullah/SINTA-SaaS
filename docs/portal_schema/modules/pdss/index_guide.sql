-- ════════════════════════════════════════════════════
-- INDEX GUIDE: Schema PDSS
-- Rekomendasi index untuk performa query optimal
-- Jalankan: psql -d sinta_db -f index_guide.sql
-- ════════════════════════════════════════════════════

-- ── pdss.pdss_simulasi ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_pdss_simulasi_tenant ON pdss.pdss_simulasi (tenant_id);

-- ✅ Composite: filter tenant + status (query paling umum)
CREATE INDEX IF NOT EXISTS idx_pdss_simulasi_tenant_status ON pdss.pdss_simulasi (tenant_id, status);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_pdss_simulasi_created ON pdss.pdss_simulasi (created_at DESC);

-- ── pdss.target_kampus ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_target_kampus_tenant ON pdss.target_kampus (tenant_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_target_kampus_created ON pdss.target_kampus (created_at DESC);

-- ── pdss.master_kampus_prodi ──
-- ✅ FK JOIN index: kampus_id
CREATE INDEX IF NOT EXISTS idx_master_kampus_prodi_kampus_id ON pdss.master_kampus_prodi (kampus_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_master_kampus_prodi_created ON pdss.master_kampus_prodi (created_at DESC);

-- ── pdss.pdss_config_mapel ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_pdss_config_mapel_tenant ON pdss.pdss_config_mapel (tenant_id);

-- ✅ FK JOIN index: mapel_id
CREATE INDEX IF NOT EXISTS idx_pdss_config_mapel_mapel_id ON pdss.pdss_config_mapel (mapel_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_pdss_config_mapel_created ON pdss.pdss_config_mapel (created_at DESC);

-- ── pdss.pdss_lock ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_pdss_lock_tenant ON pdss.pdss_lock (tenant_id);

-- ── pdss.pdss_manual_eligible ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_pdss_manual_eligible_tenant ON pdss.pdss_manual_eligible (tenant_id);

-- ✅ FK JOIN index: siswa_id
CREATE INDEX IF NOT EXISTS idx_pdss_manual_eligible_siswa_id ON pdss.pdss_manual_eligible (siswa_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_pdss_manual_eligible_created ON pdss.pdss_manual_eligible (created_at DESC);

-- ── pdss.pdss_simulasi_setting ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_pdss_simulasi_setting_tenant ON pdss.pdss_simulasi_setting (tenant_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_pdss_simulasi_setting_created ON pdss.pdss_simulasi_setting (created_at DESC);
