-- ════════════════════════════════════════════════════
-- QUERY EXAMPLES: Schema BK
-- Contoh query optimal: JOIN + Explicit Columns + Index
-- ════════════════════════════════════════════════════

-- ── bk.pilihan_penjurusan ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: bk.pilihan_penjurusan
SELECT
    t.id,
    t.siswa_id,
    t.tenant_id,
    t.id_jurusan,
    t.id_tahun_ajaran,
    t.status,
    t.catatan_bk,
    t.dikunci
FROM bk.pilihan_penjurusan t
-- (tabel standalone, tidak ada FK JOIN diperlukan)
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
  AND t.status = 'aktif' -- ✅ Manfaatkan composite index
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── bk.master_jalur_masuk ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: bk.master_jalur_masuk
SELECT
    t.id,
    t.tenant_id,
    t.nama_jalur,
    t.kategori,
    t.created_at,
    t.updated_at
FROM bk.master_jalur_masuk t
-- (tabel standalone, tidak ada FK JOIN diperlukan)
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── bk.catatan_bk ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: bk.catatan_bk
SELECT
    t.id,
    t.siswa_id,
    t.tenant_id,
    t.id_guru_bk,
    t.tanggal_konseling,
    t.jenis_kasus,
    t.catatan,
    t.tindak_lanjut
FROM bk.catatan_bk t
-- (tabel standalone, tidak ada FK JOIN diperlukan)
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── bk.master_pelanggaran ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: bk.master_pelanggaran
SELECT
    t.id,
    t.tenant_id,
    t.kategori,
    t.nama_pelanggaran,
    t.bobot_poin,
    t.created_at,
    t.updated_at,
    t.deleted_at
FROM bk.master_pelanggaran t
INNER JOIN tenants tena ON t.tenant_id = tena.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── bk.pelanggaran_siswa ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: bk.pelanggaran_siswa
SELECT
    t.id,
    t.tenant_id,
    t.siswa_id,
    t.tgl_pelanggaran,
    t.master_pelanggaran_id,
    t.nama_pelanggaran,
    t.kategori_pelanggaran,
    t.poin_pelanggaran
FROM bk.pelanggaran_siswa t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
INNER JOIN siswa.siswa sisw ON t.siswa_id = sisw.id
INNER JOIN kepegawaian.ptk_identitas ptk_ ON t.guru_bk_ptk_id = ptk_.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── bk.tindak_lanjut_sanksi ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: bk.tindak_lanjut_sanksi
SELECT
    t.id,
    t.tenant_id,
    t.siswa_id,
    t.tahun_ajaran_id,
    t.tanggal_tindakan,
    t.jenis_tindakan,
    t.keterangan_tindakan,
    t.guru_id
FROM bk.tindak_lanjut_sanksi t
INNER JOIN tenants tena ON t.tenant_id = tena.id
INNER JOIN siswa sisw ON t.siswa_id = sisw.id
INNER JOIN tahun_ajaran tahu ON t.tahun_ajaran_id = tahu.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── bk.pembinaan_monitoring ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: bk.pembinaan_monitoring
SELECT
    t.id,
    t.tenant_id,
    t.siswa_id,
    t.guru_bk_id,
    t.tanggal_bimbingan,
    t.jenis_bimbingan,
    t.penanganan_solusi,
    t.status
FROM bk.pembinaan_monitoring t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
INNER JOIN siswa.siswa sisw ON t.siswa_id = sisw.id
INNER JOIN core.users user ON t.guru_bk_id = user.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
  AND t.status = 'aktif' -- ✅ Manfaatkan composite index
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── bk.sesi_mentoring ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: bk.sesi_mentoring
SELECT
    t.id,
    t.tenant_id,
    t.monitoring_id,
    t.kepsek_id,
    t.tanggal_sesi,
    t.catatan_fakta,
    t.rencana_tindak_lanjut,
    t.ttd_digital_kepsek
FROM bk.sesi_mentoring t
INNER JOIN tenants tena ON t.tenant_id = tena.id
INNER JOIN guru_monitoring guru ON t.monitoring_id = guru.id
INNER JOIN users user ON t.kepsek_id = user.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;
