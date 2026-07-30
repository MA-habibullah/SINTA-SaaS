-- ════════════════════════════════════════════════════
-- INDEX GUIDE: Schema KESISWAAN
-- Rekomendasi index untuk performa query optimal
-- Jalankan: psql -d sinta_db -f index_guide.sql
-- ════════════════════════════════════════════════════

-- ── kesiswaan.master_ekskul ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_master_ekskul_tenant ON kesiswaan.master_ekskul (tenant_id);

-- ✅ FK JOIN index: pembina_id
CREATE INDEX IF NOT EXISTS idx_master_ekskul_pembina_id ON kesiswaan.master_ekskul (pembina_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_master_ekskul_created ON kesiswaan.master_ekskul (created_at DESC);

-- ── kesiswaan.anggota_ekskul ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_anggota_ekskul_tenant ON kesiswaan.anggota_ekskul (tenant_id);

-- ✅ FK JOIN index: ekskul_id
CREATE INDEX IF NOT EXISTS idx_anggota_ekskul_ekskul_id ON kesiswaan.anggota_ekskul (ekskul_id);

-- ✅ FK JOIN index: siswa_id
CREATE INDEX IF NOT EXISTS idx_anggota_ekskul_siswa_id ON kesiswaan.anggota_ekskul (siswa_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_anggota_ekskul_created ON kesiswaan.anggota_ekskul (created_at DESC);

-- ── kesiswaan.nilai_ekskul ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_nilai_ekskul_tenant ON kesiswaan.nilai_ekskul (tenant_id);

-- ✅ FK JOIN index: ekskul_id
CREATE INDEX IF NOT EXISTS idx_nilai_ekskul_ekskul_id ON kesiswaan.nilai_ekskul (ekskul_id);

-- ✅ FK JOIN index: siswa_id
CREATE INDEX IF NOT EXISTS idx_nilai_ekskul_siswa_id ON kesiswaan.nilai_ekskul (siswa_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_nilai_ekskul_created ON kesiswaan.nilai_ekskul (created_at DESC);

-- ── kesiswaan.kunci_ekskul ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_kunci_ekskul_tenant ON kesiswaan.kunci_ekskul (tenant_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_kunci_ekskul_created ON kesiswaan.kunci_ekskul (created_at DESC);

-- ── kesiswaan.jadwal_ekskul ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_jadwal_ekskul_tenant ON kesiswaan.jadwal_ekskul (tenant_id);

-- ✅ FK JOIN index: ekskul_id
CREATE INDEX IF NOT EXISTS idx_jadwal_ekskul_ekskul_id ON kesiswaan.jadwal_ekskul (ekskul_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_jadwal_ekskul_created ON kesiswaan.jadwal_ekskul (created_at DESC);

-- ── kesiswaan.jurnal_ekskul ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_jurnal_ekskul_tenant ON kesiswaan.jurnal_ekskul (tenant_id);

-- ✅ FK JOIN index: ekskul_id
CREATE INDEX IF NOT EXISTS idx_jurnal_ekskul_ekskul_id ON kesiswaan.jurnal_ekskul (ekskul_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_jurnal_ekskul_created ON kesiswaan.jurnal_ekskul (created_at DESC);

-- ── kesiswaan.data_pembina ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_data_pembina_tenant ON kesiswaan.data_pembina (tenant_id);

-- ✅ FK JOIN index: user_id
CREATE INDEX IF NOT EXISTS idx_data_pembina_user_id ON kesiswaan.data_pembina (user_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_data_pembina_created ON kesiswaan.data_pembina (created_at DESC);

-- ── kesiswaan.prestasi_siswa ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_prestasi_siswa_tenant ON kesiswaan.prestasi_siswa (tenant_id);

-- ✅ FK JOIN index: tahun_ajaran_id
CREATE INDEX IF NOT EXISTS idx_prestasi_siswa_tahun_ajaran_id ON kesiswaan.prestasi_siswa (tahun_ajaran_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_prestasi_siswa_created ON kesiswaan.prestasi_siswa (created_at DESC);

-- ── kesiswaan.pendaftaran_spmb ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_pendaftaran_spmb_tenant ON kesiswaan.pendaftaran_spmb (tenant_id);

-- ✅ FK JOIN index: siswa_id
CREATE INDEX IF NOT EXISTS idx_pendaftaran_spmb_siswa_id ON kesiswaan.pendaftaran_spmb (siswa_id);

-- ✅ FK JOIN index: diverifikasi_oleh
CREATE INDEX IF NOT EXISTS idx_pendaftaran_spmb_diverifikasi_oleh ON kesiswaan.pendaftaran_spmb (diverifikasi_oleh);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_pendaftaran_spmb_created ON kesiswaan.pendaftaran_spmb (created_at DESC);
