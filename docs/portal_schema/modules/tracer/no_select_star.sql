-- ════════════════════════════════════════════════════
-- NO SELECT * GUIDE: Schema TRACER
-- Perbandingan: ❌ SELECT * vs ✅ Explicit Columns
-- ════════════════════════════════════════════════════

-- ── tracer.tracer_study_alumni ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM tracer.tracer_study_alumni;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.siswa_id, t.tahun_lulus, t.status_alumni_bmw, t.nama_tempat_bekerja_kuliah_usaha
FROM tracer.tracer_study_alumni t
WHERE t.tenant_id = $1;

-- ── tracer.arsip_dokumen_alumni ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM tracer.arsip_dokumen_alumni;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.siswa_id, t.tenant_id, t.jenis_dokumen, t.file_path, t.file_size
FROM tracer.arsip_dokumen_alumni t
WHERE t.tenant_id = $1;

-- ── tracer.log_akses_arsip ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM tracer.log_akses_arsip;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.user_id, t.tenant_id, t.siswa_id, t.aktivitas, t.ip_address
FROM tracer.log_akses_arsip t
WHERE t.tenant_id = $1;

-- ── tracer.riwayat_kuliah ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM tracer.riwayat_kuliah;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.siswa_id, t.nama_alumni, t.nama_kampus, t.fakultas
FROM tracer.riwayat_kuliah t
WHERE t.tenant_id = $1;

-- ── tracer.riwayat_pekerjaan ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM tracer.riwayat_pekerjaan;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.siswa_id, t.nama_perusahaan, t.posisi_jabatan, t.pendapatan_bulanan
FROM tracer.riwayat_pekerjaan t
WHERE t.tenant_id = $1;
