-- ════════════════════════════════════════════════════
-- INDEX GUIDE: Schema AKADEMIK
-- Rekomendasi index untuk performa query optimal
-- Jalankan: psql -d sinta_db -f index_guide.sql
-- ════════════════════════════════════════════════════

-- ── akademik.tahun_ajaran ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_tahun_ajaran_tenant ON akademik.tahun_ajaran (tenant_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_tahun_ajaran_created ON akademik.tahun_ajaran (created_at DESC);

-- ── akademik.angkatan ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_angkatan_tenant ON akademik.angkatan (tenant_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_angkatan_created ON akademik.angkatan (created_at DESC);

-- ── akademik.pendidikan ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_pendidikan_tenant ON akademik.pendidikan (tenant_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_pendidikan_created ON akademik.pendidikan (created_at DESC);

-- ── akademik.ref_kurikulum ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_ref_kurikulum_tenant ON akademik.ref_kurikulum (tenant_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_ref_kurikulum_created ON akademik.ref_kurikulum (created_at DESC);

-- ── akademik.jurusan ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_jurusan_tenant ON akademik.jurusan (tenant_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_jurusan_created ON akademik.jurusan (created_at DESC);

-- ── akademik.kelas ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_kelas_tenant ON akademik.kelas (tenant_id);

-- ✅ FK JOIN index: id_jenjang
CREATE INDEX IF NOT EXISTS idx_kelas_id_jenjang ON akademik.kelas (id_jenjang);

-- ✅ FK JOIN index: id_jurusan
CREATE INDEX IF NOT EXISTS idx_kelas_id_jurusan ON akademik.kelas (id_jurusan);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_kelas_created ON akademik.kelas (created_at DESC);

-- ── akademik.kelas_kurikulum ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_kelas_kurikulum_tenant ON akademik.kelas_kurikulum (tenant_id);

-- ✅ FK JOIN index: kelas_id
CREATE INDEX IF NOT EXISTS idx_kelas_kurikulum_kelas_id ON akademik.kelas_kurikulum (kelas_id);

-- ✅ FK JOIN index: kurikulum_id
CREATE INDEX IF NOT EXISTS idx_kelas_kurikulum_kurikulum_id ON akademik.kelas_kurikulum (kurikulum_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_kelas_kurikulum_created ON akademik.kelas_kurikulum (created_at DESC);

-- ── akademik.mata_pelajaran ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_mata_pelajaran_tenant ON akademik.mata_pelajaran (tenant_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_mata_pelajaran_created ON akademik.mata_pelajaran (created_at DESC);

-- ── akademik.pemetaan_mapel ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_pemetaan_mapel_tenant ON akademik.pemetaan_mapel (tenant_id);

-- ✅ FK JOIN index: kelas_id
CREATE INDEX IF NOT EXISTS idx_pemetaan_mapel_kelas_id ON akademik.pemetaan_mapel (kelas_id);

-- ✅ FK JOIN index: mapel_id
CREATE INDEX IF NOT EXISTS idx_pemetaan_mapel_mapel_id ON akademik.pemetaan_mapel (mapel_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_pemetaan_mapel_created ON akademik.pemetaan_mapel (created_at DESC);

-- ── akademik.kunci_akademik ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_kunci_akademik_tenant ON akademik.kunci_akademik (tenant_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_kunci_akademik_created ON akademik.kunci_akademik (created_at DESC);

-- ── akademik.detail_nilai_rapor ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_detail_nilai_rapor_tenant ON akademik.detail_nilai_rapor (tenant_id);

-- ✅ FK JOIN index: siswa_id
CREATE INDEX IF NOT EXISTS idx_detail_nilai_rapor_siswa_id ON akademik.detail_nilai_rapor (siswa_id);

-- ✅ FK JOIN index: kelas_id
CREATE INDEX IF NOT EXISTS idx_detail_nilai_rapor_kelas_id ON akademik.detail_nilai_rapor (kelas_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_detail_nilai_rapor_created ON akademik.detail_nilai_rapor (created_at DESC);

-- ── akademik.log_nilai_rapor ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_log_nilai_rapor_tenant ON akademik.log_nilai_rapor (tenant_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_log_nilai_rapor_created ON akademik.log_nilai_rapor (created_at DESC);

-- ── akademik.nilai_sikap_k13 ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_nilai_sikap_k13_tenant ON akademik.nilai_sikap_k13 (tenant_id);

-- ✅ FK JOIN index: siswa_id
CREATE INDEX IF NOT EXISTS idx_nilai_sikap_k13_siswa_id ON akademik.nilai_sikap_k13 (siswa_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_nilai_sikap_k13_created ON akademik.nilai_sikap_k13 (created_at DESC);

-- ── akademik.nilai_p5 ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_nilai_p5_tenant ON akademik.nilai_p5 (tenant_id);

-- ✅ FK JOIN index: siswa_id
CREATE INDEX IF NOT EXISTS idx_nilai_p5_siswa_id ON akademik.nilai_p5 (siswa_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_nilai_p5_created ON akademik.nilai_p5 (created_at DESC);

-- ── akademik.nilai_ujian_sekolah ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_nilai_ujian_sekolah_tenant ON akademik.nilai_ujian_sekolah (tenant_id);

-- ✅ FK JOIN index: id_siswa
CREATE INDEX IF NOT EXISTS idx_nilai_ujian_sekolah_id_siswa ON akademik.nilai_ujian_sekolah (id_siswa);

-- ✅ FK JOIN index: id_mata_pelajaran
CREATE INDEX IF NOT EXISTS idx_nilai_ujian_sekolah_id_mata_pelajaran ON akademik.nilai_ujian_sekolah (id_mata_pelajaran);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_nilai_ujian_sekolah_created ON akademik.nilai_ujian_sekolah (created_at DESC);

-- ── akademik.catatan_wali_kelas ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_catatan_wali_kelas_tenant ON akademik.catatan_wali_kelas (tenant_id);

-- ✅ FK JOIN index: siswa_id
CREATE INDEX IF NOT EXISTS idx_catatan_wali_kelas_siswa_id ON akademik.catatan_wali_kelas (siswa_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_catatan_wali_kelas_created ON akademik.catatan_wali_kelas (created_at DESC);

-- ── akademik.semester ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_semester_tenant ON akademik.semester (tenant_id);

-- ✅ FK JOIN index: tahun_ajaran_id
CREATE INDEX IF NOT EXISTS idx_semester_tahun_ajaran_id ON akademik.semester (tahun_ajaran_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_semester_created ON akademik.semester (created_at DESC);

-- ── akademik.penugasan_mengajar ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_penugasan_mengajar_tenant ON akademik.penugasan_mengajar (tenant_id);

-- ✅ FK JOIN index: semester_id
CREATE INDEX IF NOT EXISTS idx_penugasan_mengajar_semester_id ON akademik.penugasan_mengajar (semester_id);

-- ✅ FK JOIN index: guru_ptk_id
CREATE INDEX IF NOT EXISTS idx_penugasan_mengajar_guru_ptk_id ON akademik.penugasan_mengajar (guru_ptk_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_penugasan_mengajar_created ON akademik.penugasan_mengajar (created_at DESC);

-- ── akademik.nilai_sumatif ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_nilai_sumatif_tenant ON akademik.nilai_sumatif (tenant_id);

-- ✅ FK JOIN index: semester_id
CREATE INDEX IF NOT EXISTS idx_nilai_sumatif_semester_id ON akademik.nilai_sumatif (semester_id);

-- ✅ FK JOIN index: rombongan_belajar_id
CREATE INDEX IF NOT EXISTS idx_nilai_sumatif_rombongan_belajar_id ON akademik.nilai_sumatif (rombongan_belajar_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_nilai_sumatif_created ON akademik.nilai_sumatif (created_at DESC);

-- ── akademik.target_capaian_tp ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_target_capaian_tp_tenant ON akademik.target_capaian_tp (tenant_id);

-- ✅ FK JOIN index: mata_pelajaran_id
CREATE INDEX IF NOT EXISTS idx_target_capaian_tp_mata_pelajaran_id ON akademik.target_capaian_tp (mata_pelajaran_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_target_capaian_tp_created ON akademik.target_capaian_tp (created_at DESC);
