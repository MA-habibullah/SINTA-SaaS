-- ════════════════════════════════════════════════════
-- INDEX GUIDE: Schema SISWA
-- Rekomendasi index untuk performa query optimal
-- Jalankan: psql -d sinta_db -f index_guide.sql
-- ════════════════════════════════════════════════════

-- ── siswa.siswa ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_siswa_tenant ON siswa.siswa (tenant_id);

-- ✅ FK JOIN index: user_id
CREATE INDEX IF NOT EXISTS idx_siswa_user_id ON siswa.siswa (user_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_siswa_created ON siswa.siswa (created_at DESC);

-- ── siswa.fisik_kesehatan_siswa ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_fisik_kesehatan_siswa_tenant ON siswa.fisik_kesehatan_siswa (tenant_id);

-- ✅ FK JOIN index: siswa_id
CREATE INDEX IF NOT EXISTS idx_fisik_kesehatan_siswa_siswa_id ON siswa.fisik_kesehatan_siswa (siswa_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_fisik_kesehatan_siswa_created ON siswa.fisik_kesehatan_siswa (created_at DESC);

-- ── siswa.orang_tua ──
-- ✅ FK JOIN index: id_siswa
CREATE INDEX IF NOT EXISTS idx_orang_tua_id_siswa ON siswa.orang_tua (id_siswa);

-- ✅ FK JOIN index: id_tempat_lahir_ayah
CREATE INDEX IF NOT EXISTS idx_orang_tua_id_tempat_lahir_ayah ON siswa.orang_tua (id_tempat_lahir_ayah);

-- ✅ FK JOIN index: id_tempat_lahir_ibu
CREATE INDEX IF NOT EXISTS idx_orang_tua_id_tempat_lahir_ibu ON siswa.orang_tua (id_tempat_lahir_ibu);

-- ── siswa.registrasi ──
-- ✅ FK JOIN index: id_siswa
CREATE INDEX IF NOT EXISTS idx_registrasi_id_siswa ON siswa.registrasi (id_siswa);

-- ── siswa.absensi_semester ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_absensi_semester_tenant ON siswa.absensi_semester (tenant_id);

-- ✅ FK JOIN index: siswa_id
CREATE INDEX IF NOT EXISTS idx_absensi_semester_siswa_id ON siswa.absensi_semester (siswa_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_absensi_semester_created ON siswa.absensi_semester (created_at DESC);

-- ── siswa.riwayat_kenaikan_kelas ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_riwayat_kenaikan_kelas_tenant ON siswa.riwayat_kenaikan_kelas (tenant_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_riwayat_kenaikan_kelas_created ON siswa.riwayat_kenaikan_kelas (created_at DESC);

-- ── siswa.riwayat_beasiswa ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_riwayat_beasiswa_tenant ON siswa.riwayat_beasiswa (tenant_id);

-- ✅ FK JOIN index: siswa_id
CREATE INDEX IF NOT EXISTS idx_riwayat_beasiswa_siswa_id ON siswa.riwayat_beasiswa (siswa_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_riwayat_beasiswa_created ON siswa.riwayat_beasiswa (created_at DESC);

-- ── siswa.anggota_kelas ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_anggota_kelas_tenant ON siswa.anggota_kelas (tenant_id);

-- ✅ FK JOIN index: semester_id
CREATE INDEX IF NOT EXISTS idx_anggota_kelas_semester_id ON siswa.anggota_kelas (semester_id);

-- ✅ FK JOIN index: rombongan_belajar_id
CREATE INDEX IF NOT EXISTS idx_anggota_kelas_rombongan_belajar_id ON siswa.anggota_kelas (rombongan_belajar_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_anggota_kelas_created ON siswa.anggota_kelas (created_at DESC);

-- ── siswa.dokumen ──
-- ✅ FK JOIN index: id_siswa
CREATE INDEX IF NOT EXISTS idx_dokumen_id_siswa ON siswa.dokumen (id_siswa);
