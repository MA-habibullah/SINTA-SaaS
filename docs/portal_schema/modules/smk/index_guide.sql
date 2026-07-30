-- ════════════════════════════════════════════════════
-- INDEX GUIDE: Schema SMK
-- Rekomendasi index untuk performa query optimal
-- Jalankan: psql -d sinta_db -f index_guide.sql
-- ════════════════════════════════════════════════════

-- ── smk.mitra_dudi ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_mitra_dudi_tenant ON smk.mitra_dudi (tenant_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_mitra_dudi_created ON smk.mitra_dudi (created_at DESC);

-- ── smk.pkl_penempatan ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_pkl_penempatan_tenant ON smk.pkl_penempatan (tenant_id);

-- ✅ FK JOIN index: siswa_id
CREATE INDEX IF NOT EXISTS idx_pkl_penempatan_siswa_id ON smk.pkl_penempatan (siswa_id);

-- ✅ FK JOIN index: mitra_dudi_id
CREATE INDEX IF NOT EXISTS idx_pkl_penempatan_mitra_dudi_id ON smk.pkl_penempatan (mitra_dudi_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_pkl_penempatan_created ON smk.pkl_penempatan (created_at DESC);

-- ── smk.pkl_jurnal_harian ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_pkl_jurnal_harian_tenant ON smk.pkl_jurnal_harian (tenant_id);

-- ✅ FK JOIN index: pkl_penempatan_id
CREATE INDEX IF NOT EXISTS idx_pkl_jurnal_harian_pkl_penempatan_id ON smk.pkl_jurnal_harian (pkl_penempatan_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_pkl_jurnal_harian_created ON smk.pkl_jurnal_harian (created_at DESC);

-- ── smk.pkl_penilaian ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_pkl_penilaian_tenant ON smk.pkl_penilaian (tenant_id);

-- ✅ FK JOIN index: pkl_penempatan_id
CREATE INDEX IF NOT EXISTS idx_pkl_penilaian_pkl_penempatan_id ON smk.pkl_penilaian (pkl_penempatan_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_pkl_penilaian_created ON smk.pkl_penilaian (created_at DESC);

-- ── smk.ukk_penilaian ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_ukk_penilaian_tenant ON smk.ukk_penilaian (tenant_id);

-- ✅ FK JOIN index: siswa_id
CREATE INDEX IF NOT EXISTS idx_ukk_penilaian_siswa_id ON smk.ukk_penilaian (siswa_id);

-- ✅ FK JOIN index: tahun_ajaran_id
CREATE INDEX IF NOT EXISTS idx_ukk_penilaian_tahun_ajaran_id ON smk.ukk_penilaian (tahun_ajaran_id);
