-- ════════════════════════════════════════════════════
-- INDEX GUIDE: Schema ABSENSI
-- Rekomendasi index untuk performa query optimal
-- Jalankan: psql -d sinta_db -f index_guide.sql
-- ════════════════════════════════════════════════════

-- ── absensi.lokasi_absensi_setting ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_lokasi_absensi_setting_tenant ON absensi.lokasi_absensi_setting (tenant_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_lokasi_absensi_setting_created ON absensi.lokasi_absensi_setting (created_at DESC);

-- ── absensi.jam_kerja_shift ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_jam_kerja_shift_tenant ON absensi.jam_kerja_shift (tenant_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_jam_kerja_shift_created ON absensi.jam_kerja_shift (created_at DESC);

-- ── absensi.kebijakan_absensi_ptk ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_kebijakan_absensi_ptk_tenant ON absensi.kebijakan_absensi_ptk (tenant_id);

-- ✅ FK JOIN index: ptk_id
CREATE INDEX IF NOT EXISTS idx_kebijakan_absensi_ptk_ptk_id ON absensi.kebijakan_absensi_ptk (ptk_id);

-- ✅ FK JOIN index: lokasi_absensi_id
CREATE INDEX IF NOT EXISTS idx_kebijakan_absensi_ptk_lokasi_absensi_id ON absensi.kebijakan_absensi_ptk (lokasi_absensi_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_kebijakan_absensi_ptk_created ON absensi.kebijakan_absensi_ptk (created_at DESC);

-- ── absensi.presensi_ptk_harian ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_presensi_ptk_harian_tenant ON absensi.presensi_ptk_harian (tenant_id);

-- ✅ FK JOIN index: ptk_id
CREATE INDEX IF NOT EXISTS idx_presensi_ptk_harian_ptk_id ON absensi.presensi_ptk_harian (ptk_id);

-- ✅ FK JOIN index: jam_kerja_shift_id
CREATE INDEX IF NOT EXISTS idx_presensi_ptk_harian_jam_kerja_shift_id ON absensi.presensi_ptk_harian (jam_kerja_shift_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_presensi_ptk_harian_created ON absensi.presensi_ptk_harian (created_at DESC);

-- ── absensi.aturan_absensi_siswa ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_aturan_absensi_siswa_tenant ON absensi.aturan_absensi_siswa (tenant_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_aturan_absensi_siswa_created ON absensi.aturan_absensi_siswa (created_at DESC);

-- ── absensi.presensi_siswa_harian ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_presensi_siswa_harian_tenant ON absensi.presensi_siswa_harian (tenant_id);

-- ✅ FK JOIN index: siswa_id
CREATE INDEX IF NOT EXISTS idx_presensi_siswa_harian_siswa_id ON absensi.presensi_siswa_harian (siswa_id);

-- ✅ FK JOIN index: rombongan_belajar_id
CREATE INDEX IF NOT EXISTS idx_presensi_siswa_harian_rombongan_belajar_id ON absensi.presensi_siswa_harian (rombongan_belajar_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_presensi_siswa_harian_created ON absensi.presensi_siswa_harian (created_at DESC);

-- ── absensi.presensi_siswa_kbm ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_presensi_siswa_kbm_tenant ON absensi.presensi_siswa_kbm (tenant_id);

-- ✅ FK JOIN index: jadwal_pelajaran_id
CREATE INDEX IF NOT EXISTS idx_presensi_siswa_kbm_jadwal_pelajaran_id ON absensi.presensi_siswa_kbm (jadwal_pelajaran_id);

-- ✅ FK JOIN index: rombongan_belajar_id
CREATE INDEX IF NOT EXISTS idx_presensi_siswa_kbm_rombongan_belajar_id ON absensi.presensi_siswa_kbm (rombongan_belajar_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_presensi_siswa_kbm_created ON absensi.presensi_siswa_kbm (created_at DESC);

-- ── absensi.pengajuan_izin_cuti ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_pengajuan_izin_cuti_tenant ON absensi.pengajuan_izin_cuti (tenant_id);

-- ✅ FK JOIN index: ptk_id
CREATE INDEX IF NOT EXISTS idx_pengajuan_izin_cuti_ptk_id ON absensi.pengajuan_izin_cuti (ptk_id);

-- ✅ FK JOIN index: siswa_id
CREATE INDEX IF NOT EXISTS idx_pengajuan_izin_cuti_siswa_id ON absensi.pengajuan_izin_cuti (siswa_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_pengajuan_izin_cuti_created ON absensi.pengajuan_izin_cuti (created_at DESC);
