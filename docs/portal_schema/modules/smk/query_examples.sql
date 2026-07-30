-- ════════════════════════════════════════════════════
-- QUERY EXAMPLES: Schema SMK
-- Contoh query optimal: JOIN + Explicit Columns + Index
-- ════════════════════════════════════════════════════

-- ── smk.mitra_dudi ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: smk.mitra_dudi
SELECT
    t.id,
    t.tenant_id,
    t.nama_perusahaan_dudi,
    t.bidang_usaha,
    t.alamat_lengkap,
    t.kontak_person_nama,
    t.kontak_person_no_hp,
    t.mou_nomor
FROM smk.mitra_dudi t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
  AND t.is_active = true
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── smk.pkl_penempatan ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: smk.pkl_penempatan
SELECT
    t.id,
    t.tenant_id,
    t.siswa_id,
    t.mitra_dudi_id,
    t.tahun_ajaran_id,
    t.pembimbing_sekolah_ptk_id,
    t.pembimbing_dudi_nama,
    t.pembimbing_dudi_no_hp
FROM smk.pkl_penempatan t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
INNER JOIN siswa.siswa sisw ON t.siswa_id = sisw.id
INNER JOIN smk.mitra_dudi mitr ON t.mitra_dudi_id = mitr.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── smk.pkl_jurnal_harian ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: smk.pkl_jurnal_harian
SELECT
    t.id,
    t.tenant_id,
    t.pkl_penempatan_id,
    t.tgl_jurnal,
    t.jam_mulai,
    t.jam_selesai,
    t.uraian_kegiatan_pekerjaan,
    t.foto_kegiatan_url
FROM smk.pkl_jurnal_harian t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
INNER JOIN smk.pkl_penempatan pkl_ ON t.pkl_penempatan_id = pkl_.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── smk.pkl_penilaian ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: smk.pkl_penilaian
SELECT
    t.id,
    t.tenant_id,
    t.pkl_penempatan_id,
    t.nilai_aspek_teknis_keahlian,
    t.nilai_aspek_softskill_disiplin,
    t.nilai_laporan_pkl,
    t.nilai_akhir_pkl,
    t.predikat_pkl
FROM smk.pkl_penilaian t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
INNER JOIN smk.pkl_penempatan pkl_ ON t.pkl_penempatan_id = pkl_.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── smk.ukk_penilaian ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: smk.ukk_penilaian
SELECT
    t.id,
    t.tenant_id,
    t.siswa_id,
    t.tahun_ajaran_id,
    t.skema_sertifikasi_nama,
    t.penguji_internal_ptk_id,
    t.penguji_eksternal_dudi_id,
    t.penguji_eksternal_nama
FROM smk.ukk_penilaian t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
INNER JOIN siswa.siswa sisw ON t.siswa_id = sisw.id
INNER JOIN akademik.tahun_ajaran tahu ON t.tahun_ajaran_id = tahu.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.tenant_id DESC
LIMIT 50 OFFSET 0;
