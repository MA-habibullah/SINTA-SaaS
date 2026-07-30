-- ════════════════════════════════════════════════════
-- INDEX GUIDE: Schema KEPEGAWAIAN
-- Rekomendasi index untuk performa query optimal
-- Jalankan: psql -d sinta_db -f index_guide.sql
-- ════════════════════════════════════════════════════

-- ── kepegawaian.ptk_identitas ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_ptk_identitas_tenant ON kepegawaian.ptk_identitas (tenant_id);

-- ✅ FK JOIN index: user_id
CREATE INDEX IF NOT EXISTS idx_ptk_identitas_user_id ON kepegawaian.ptk_identitas (user_id);

-- ✅ FK JOIN index: mapel_utama_id
CREATE INDEX IF NOT EXISTS idx_ptk_identitas_mapel_utama_id ON kepegawaian.ptk_identitas (mapel_utama_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_ptk_identitas_created ON kepegawaian.ptk_identitas (created_at DESC);

-- ── kepegawaian.riwayat_kepangkatan ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_riwayat_kepangkatan_tenant ON kepegawaian.riwayat_kepangkatan (tenant_id);

-- ✅ FK JOIN index: ptk_id
CREATE INDEX IF NOT EXISTS idx_riwayat_kepangkatan_ptk_id ON kepegawaian.riwayat_kepangkatan (ptk_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_riwayat_kepangkatan_created ON kepegawaian.riwayat_kepangkatan (created_at DESC);

-- ── kepegawaian.sertifikasi_ptk ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_sertifikasi_ptk_tenant ON kepegawaian.sertifikasi_ptk (tenant_id);

-- ✅ FK JOIN index: ptk_id
CREATE INDEX IF NOT EXISTS idx_sertifikasi_ptk_ptk_id ON kepegawaian.sertifikasi_ptk (ptk_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_sertifikasi_ptk_created ON kepegawaian.sertifikasi_ptk (created_at DESC);

-- ── kepegawaian.diklat_pelatihan ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_diklat_pelatihan_tenant ON kepegawaian.diklat_pelatihan (tenant_id);

-- ✅ FK JOIN index: ptk_id
CREATE INDEX IF NOT EXISTS idx_diklat_pelatihan_ptk_id ON kepegawaian.diklat_pelatihan (ptk_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_diklat_pelatihan_created ON kepegawaian.diklat_pelatihan (created_at DESC);

-- ── kepegawaian.riwayat_pendidikan_ptk ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_riwayat_pendidikan_ptk_tenant ON kepegawaian.riwayat_pendidikan_ptk (tenant_id);

-- ✅ FK JOIN index: ptk_id
CREATE INDEX IF NOT EXISTS idx_riwayat_pendidikan_ptk_ptk_id ON kepegawaian.riwayat_pendidikan_ptk (ptk_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_riwayat_pendidikan_ptk_created ON kepegawaian.riwayat_pendidikan_ptk (created_at DESC);

-- ── kepegawaian.riwayat_kepala_sekolah ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_riwayat_kepala_sekolah_tenant ON kepegawaian.riwayat_kepala_sekolah (tenant_id);

-- ✅ FK JOIN index: ptk_id
CREATE INDEX IF NOT EXISTS idx_riwayat_kepala_sekolah_ptk_id ON kepegawaian.riwayat_kepala_sekolah (ptk_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_riwayat_kepala_sekolah_created ON kepegawaian.riwayat_kepala_sekolah (created_at DESC);

-- ── kepegawaian.dokumen_ptk ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_dokumen_ptk_tenant ON kepegawaian.dokumen_ptk (tenant_id);

-- ✅ FK JOIN index: ptk_id
CREATE INDEX IF NOT EXISTS idx_dokumen_ptk_ptk_id ON kepegawaian.dokumen_ptk (ptk_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_dokumen_ptk_created ON kepegawaian.dokumen_ptk (created_at DESC);

-- ── kepegawaian.lowongan_kerja ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_lowongan_kerja_tenant ON kepegawaian.lowongan_kerja (tenant_id);

-- ✅ Composite: filter tenant + status (query paling umum)
CREATE INDEX IF NOT EXISTS idx_lowongan_kerja_tenant_status ON kepegawaian.lowongan_kerja (tenant_id, status);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_lowongan_kerja_created ON kepegawaian.lowongan_kerja (created_at DESC);

-- ── kepegawaian.pelamar_kerja ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_pelamar_kerja_tenant ON kepegawaian.pelamar_kerja (tenant_id);

-- ✅ FK JOIN index: lowongan_id
CREATE INDEX IF NOT EXISTS idx_pelamar_kerja_lowongan_id ON kepegawaian.pelamar_kerja (lowongan_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_pelamar_kerja_created ON kepegawaian.pelamar_kerja (created_at DESC);

-- ── kepegawaian.tahapan_seleksi_pelamar ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_tahapan_seleksi_pelamar_tenant ON kepegawaian.tahapan_seleksi_pelamar (tenant_id);

-- ✅ FK JOIN index: pelamar_id
CREATE INDEX IF NOT EXISTS idx_tahapan_seleksi_pelamar_pelamar_id ON kepegawaian.tahapan_seleksi_pelamar (pelamar_id);

-- ✅ FK JOIN index: penguji_ptk_id
CREATE INDEX IF NOT EXISTS idx_tahapan_seleksi_pelamar_penguji_ptk_id ON kepegawaian.tahapan_seleksi_pelamar (penguji_ptk_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_tahapan_seleksi_pelamar_created ON kepegawaian.tahapan_seleksi_pelamar (created_at DESC);
