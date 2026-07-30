-- ════════════════════════════════════════════════════
-- INDEX GUIDE: Schema BK
-- Rekomendasi index untuk performa query optimal
-- Jalankan: psql -d sinta_db -f index_guide.sql
-- ════════════════════════════════════════════════════

-- ── bk.pilihan_penjurusan ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_pilihan_penjurusan_tenant ON bk.pilihan_penjurusan (tenant_id);

-- ✅ Composite: filter tenant + status (query paling umum)
CREATE INDEX IF NOT EXISTS idx_pilihan_penjurusan_tenant_status ON bk.pilihan_penjurusan (tenant_id, status);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_pilihan_penjurusan_created ON bk.pilihan_penjurusan (created_at DESC);

-- ── bk.master_jalur_masuk ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_master_jalur_masuk_tenant ON bk.master_jalur_masuk (tenant_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_master_jalur_masuk_created ON bk.master_jalur_masuk (created_at DESC);

-- ── bk.catatan_bk ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_catatan_bk_tenant ON bk.catatan_bk (tenant_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_catatan_bk_created ON bk.catatan_bk (created_at DESC);

-- ── bk.master_pelanggaran ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_master_pelanggaran_tenant ON bk.master_pelanggaran (tenant_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_master_pelanggaran_created ON bk.master_pelanggaran (created_at DESC);

-- ── bk.pelanggaran_siswa ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_pelanggaran_siswa_tenant ON bk.pelanggaran_siswa (tenant_id);

-- ✅ FK JOIN index: siswa_id
CREATE INDEX IF NOT EXISTS idx_pelanggaran_siswa_siswa_id ON bk.pelanggaran_siswa (siswa_id);

-- ✅ FK JOIN index: guru_bk_ptk_id
CREATE INDEX IF NOT EXISTS idx_pelanggaran_siswa_guru_bk_ptk_id ON bk.pelanggaran_siswa (guru_bk_ptk_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_pelanggaran_siswa_created ON bk.pelanggaran_siswa (created_at DESC);

-- ── bk.tindak_lanjut_sanksi ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_tindak_lanjut_sanksi_tenant ON bk.tindak_lanjut_sanksi (tenant_id);

-- ✅ FK JOIN index: siswa_id
CREATE INDEX IF NOT EXISTS idx_tindak_lanjut_sanksi_siswa_id ON bk.tindak_lanjut_sanksi (siswa_id);

-- ✅ FK JOIN index: tahun_ajaran_id
CREATE INDEX IF NOT EXISTS idx_tindak_lanjut_sanksi_tahun_ajaran_id ON bk.tindak_lanjut_sanksi (tahun_ajaran_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_tindak_lanjut_sanksi_created ON bk.tindak_lanjut_sanksi (created_at DESC);

-- ── bk.pembinaan_monitoring ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_pembinaan_monitoring_tenant ON bk.pembinaan_monitoring (tenant_id);

-- ✅ Composite: filter tenant + status (query paling umum)
CREATE INDEX IF NOT EXISTS idx_pembinaan_monitoring_tenant_status ON bk.pembinaan_monitoring (tenant_id, status);

-- ✅ FK JOIN index: siswa_id
CREATE INDEX IF NOT EXISTS idx_pembinaan_monitoring_siswa_id ON bk.pembinaan_monitoring (siswa_id);

-- ✅ FK JOIN index: guru_bk_id
CREATE INDEX IF NOT EXISTS idx_pembinaan_monitoring_guru_bk_id ON bk.pembinaan_monitoring (guru_bk_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_pembinaan_monitoring_created ON bk.pembinaan_monitoring (created_at DESC);

-- ── bk.sesi_mentoring ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_sesi_mentoring_tenant ON bk.sesi_mentoring (tenant_id);

-- ✅ FK JOIN index: monitoring_id
CREATE INDEX IF NOT EXISTS idx_sesi_mentoring_monitoring_id ON bk.sesi_mentoring (monitoring_id);

-- ✅ FK JOIN index: kepsek_id
CREATE INDEX IF NOT EXISTS idx_sesi_mentoring_kepsek_id ON bk.sesi_mentoring (kepsek_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_sesi_mentoring_created ON bk.sesi_mentoring (created_at DESC);
