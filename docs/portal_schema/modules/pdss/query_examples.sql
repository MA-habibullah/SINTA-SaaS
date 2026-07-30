-- ════════════════════════════════════════════════════
-- QUERY EXAMPLES: Schema PDSS
-- Contoh query optimal: JOIN + Explicit Columns + Index
-- ════════════════════════════════════════════════════

-- ── pdss.pdss_simulasi ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: pdss.pdss_simulasi
SELECT
    t.id,
    t.tenant_id,
    t.siswa_id,
    t.tahun_ajaran_id,
    t.no_simulasi,
    t.kampus_id_1,
    t.prodi_id_1,
    t.nama_kampus_1
FROM pdss.pdss_simulasi t
-- (tabel standalone, tidak ada FK JOIN diperlukan)
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
  AND t.status = 'aktif' -- ✅ Manfaatkan composite index
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── pdss.target_kampus ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: pdss.target_kampus
SELECT
    t.id,
    t.tenant_id,
    t.nama_kampus,
    t.jenis_kampus,
    t.kuota_target,
    t.created_at,
    t.updated_at
FROM pdss.target_kampus t
INNER JOIN tenants tena ON t.tenant_id = tena.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── pdss.master_kampus_prodi ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: pdss.master_kampus_prodi
SELECT
    t.id,
    t.kampus_id,
    t.fakultas,
    t.program_studi,
    t.jenjang,
    t.created_at,
    t.updated_at,
    t.kode_prodi
FROM pdss.master_kampus_prodi t
INNER JOIN master_kampus mast ON t.kampus_id = mast.id
WHERE t.id = $1
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── pdss.pdss_config_mapel ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: pdss.pdss_config_mapel
SELECT
    t.id,
    t.tenant_id,
    t.tahun_ajaran_id,
    t.mapel_id,
    t.sem_1,
    t.sem_2,
    t.sem_3,
    t.sem_4
FROM pdss.pdss_config_mapel t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
INNER JOIN akademik.mata_pelajaran mata ON t.mapel_id = mata.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── pdss.pdss_lock ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: pdss.pdss_lock
SELECT
    t.tenant_id,
    t.tahun_ajaran_id,
    t.step,
    t.is_locked,
    t.locked_by,
    t.locked_at
FROM pdss.pdss_lock t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.tahun_ajaran_id DESC
LIMIT 50 OFFSET 0;

-- ── pdss.pdss_manual_eligible ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: pdss.pdss_manual_eligible
SELECT
    t.id,
    t.tenant_id,
    t.siswa_id,
    t.status_eligible,
    t.created_at,
    t.updated_at
FROM pdss.pdss_manual_eligible t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
INNER JOIN siswa.siswa sisw ON t.siswa_id = sisw.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── pdss.pdss_simulasi_setting ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: pdss.pdss_simulasi_setting
SELECT
    t.id,
    t.tenant_id,
    t.tahun_ajaran_id,
    t.no_simulasi,
    t.is_open,
    t.is_locked,
    t.dibuka_oleh,
    t.dibuka_at
FROM pdss.pdss_simulasi_setting t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;
