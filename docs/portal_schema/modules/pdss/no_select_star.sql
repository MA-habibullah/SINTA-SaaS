-- ════════════════════════════════════════════════════
-- NO SELECT * GUIDE: Schema PDSS
-- Perbandingan: ❌ SELECT * vs ✅ Explicit Columns
-- ════════════════════════════════════════════════════

-- ── pdss.pdss_simulasi ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM pdss.pdss_simulasi;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.siswa_id, t.tahun_ajaran_id, t.no_simulasi, t.kampus_id_1
FROM pdss.pdss_simulasi t
WHERE t.tenant_id = $1;

-- ── pdss.target_kampus ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM pdss.target_kampus;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.nama_kampus, t.jenis_kampus, t.kuota_target, t.created_at
FROM pdss.target_kampus t
WHERE t.tenant_id = $1;

-- ── pdss.master_kampus_prodi ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM pdss.master_kampus_prodi;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.kampus_id, t.fakultas, t.program_studi, t.jenjang, t.created_at
FROM pdss.master_kampus_prodi t
WHERE t.tenant_id = $1;

-- ── pdss.pdss_config_mapel ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM pdss.pdss_config_mapel;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.tahun_ajaran_id, t.mapel_id, t.sem_1, t.sem_2
FROM pdss.pdss_config_mapel t
WHERE t.tenant_id = $1;

-- ── pdss.pdss_lock ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM pdss.pdss_lock;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.tenant_id, t.tahun_ajaran_id, t.step, t.is_locked, t.locked_by, t.locked_at
FROM pdss.pdss_lock t
WHERE t.tenant_id = $1;

-- ── pdss.pdss_manual_eligible ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM pdss.pdss_manual_eligible;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.siswa_id, t.status_eligible, t.created_at, t.updated_at
FROM pdss.pdss_manual_eligible t
WHERE t.tenant_id = $1;

-- ── pdss.pdss_simulasi_setting ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM pdss.pdss_simulasi_setting;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.tahun_ajaran_id, t.no_simulasi, t.is_open, t.is_locked
FROM pdss.pdss_simulasi_setting t
WHERE t.tenant_id = $1;
