-- ════════════════════════════════════════════════════
-- QUERY EXAMPLES: Schema TRACER
-- Contoh query optimal: JOIN + Explicit Columns + Index
-- ════════════════════════════════════════════════════

-- ── tracer.tracer_study_alumni ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: tracer.tracer_study_alumni
SELECT
    t.id,
    t.tenant_id,
    t.siswa_id,
    t.tahun_lulus,
    t.status_alumni_bmw,
    t.nama_tempat_bekerja_kuliah_usaha,
    t.jabatan_bidang_studi_usaha,
    t.tgl_mulai_bekerja
FROM tracer.tracer_study_alumni t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
INNER JOIN siswa.siswa sisw ON t.siswa_id = sisw.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── tracer.arsip_dokumen_alumni ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: tracer.arsip_dokumen_alumni
SELECT
    t.id,
    t.siswa_id,
    t.tenant_id,
    t.jenis_dokumen,
    t.file_path,
    t.file_size,
    t.keterangan,
    t.uploaded_by
FROM tracer.arsip_dokumen_alumni t
INNER JOIN siswa sisw ON t.siswa_id = sisw.id
INNER JOIN tenants tena ON t.tenant_id = tena.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── tracer.log_akses_arsip ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: tracer.log_akses_arsip
SELECT
    t.id,
    t.user_id,
    t.tenant_id,
    t.siswa_id,
    t.aktivitas,
    t.ip_address,
    t.user_agent,
    t.created_at
FROM tracer.log_akses_arsip t
INNER JOIN tenants tena ON t.tenant_id = tena.id
INNER JOIN siswa sisw ON t.siswa_id = sisw.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── tracer.riwayat_kuliah ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: tracer.riwayat_kuliah
SELECT
    t.id,
    t.tenant_id,
    t.siswa_id,
    t.nama_alumni,
    t.nama_kampus,
    t.fakultas,
    t.jurusan,
    t.tahun_masuk
FROM tracer.riwayat_kuliah t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
INNER JOIN siswa.siswa sisw ON t.siswa_id = sisw.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── tracer.riwayat_pekerjaan ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: tracer.riwayat_pekerjaan
SELECT
    t.id,
    t.tenant_id,
    t.siswa_id,
    t.nama_perusahaan,
    t.posisi_jabatan,
    t.pendapatan_bulanan,
    t.tahun_mulai,
    t.tahun_selesai
FROM tracer.riwayat_pekerjaan t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
INNER JOIN siswa.siswa sisw ON t.siswa_id = sisw.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;
