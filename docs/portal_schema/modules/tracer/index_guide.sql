-- ════════════════════════════════════════════════════
-- INDEX GUIDE: Schema TRACER
-- Rekomendasi index untuk performa query optimal
-- Jalankan: psql -d sinta_db -f index_guide.sql
-- ════════════════════════════════════════════════════

-- ── tracer.tracer_study_alumni ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_tracer_study_alumni_tenant ON tracer.tracer_study_alumni (tenant_id);

-- ✅ FK JOIN index: siswa_id
CREATE INDEX IF NOT EXISTS idx_tracer_study_alumni_siswa_id ON tracer.tracer_study_alumni (siswa_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_tracer_study_alumni_created ON tracer.tracer_study_alumni (created_at DESC);

-- ── tracer.arsip_dokumen_alumni ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_arsip_dokumen_alumni_tenant ON tracer.arsip_dokumen_alumni (tenant_id);

-- ✅ FK JOIN index: siswa_id
CREATE INDEX IF NOT EXISTS idx_arsip_dokumen_alumni_siswa_id ON tracer.arsip_dokumen_alumni (siswa_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_arsip_dokumen_alumni_created ON tracer.arsip_dokumen_alumni (created_at DESC);

-- ── tracer.log_akses_arsip ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_log_akses_arsip_tenant ON tracer.log_akses_arsip (tenant_id);

-- ✅ FK JOIN index: siswa_id
CREATE INDEX IF NOT EXISTS idx_log_akses_arsip_siswa_id ON tracer.log_akses_arsip (siswa_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_log_akses_arsip_created ON tracer.log_akses_arsip (created_at DESC);

-- ── tracer.riwayat_kuliah ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_riwayat_kuliah_tenant ON tracer.riwayat_kuliah (tenant_id);

-- ✅ FK JOIN index: siswa_id
CREATE INDEX IF NOT EXISTS idx_riwayat_kuliah_siswa_id ON tracer.riwayat_kuliah (siswa_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_riwayat_kuliah_created ON tracer.riwayat_kuliah (created_at DESC);

-- ── tracer.riwayat_pekerjaan ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_riwayat_pekerjaan_tenant ON tracer.riwayat_pekerjaan (tenant_id);

-- ✅ FK JOIN index: siswa_id
CREATE INDEX IF NOT EXISTS idx_riwayat_pekerjaan_siswa_id ON tracer.riwayat_pekerjaan (siswa_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_riwayat_pekerjaan_created ON tracer.riwayat_pekerjaan (created_at DESC);
