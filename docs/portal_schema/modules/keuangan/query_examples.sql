-- ════════════════════════════════════════════════════
-- QUERY EXAMPLES: Schema KEUANGAN
-- Contoh query optimal: JOIN + Explicit Columns + Index
-- ════════════════════════════════════════════════════

-- ── keuangan.transaksi_spp_komponen ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: keuangan.transaksi_spp_komponen
SELECT
    t.id,
    t.tenant_id,
    t.nama_komponen,
    t.tipe_periode,
    t.is_active,
    t.created_at
FROM keuangan.transaksi_spp_komponen t
INNER JOIN tenants tena ON t.tenant_id = tena.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
  AND t.is_active = true
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── keuangan.transaksi_spp_tarif ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: keuangan.transaksi_spp_tarif
SELECT
    t.id,
    t.tenant_id,
    t.komponen_id,
    t.kelas_id,
    t.jenjang_id,
    t.jalur_masuk,
    t.nominal,
    t.tahun_ajaran_id
FROM keuangan.transaksi_spp_tarif t
INNER JOIN tenants tena ON t.tenant_id = tena.id
INNER JOIN transaksi_spp_komponen tran ON t.komponen_id = tran.id
INNER JOIN kelas kela ON t.kelas_id = kela.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── keuangan.transaksi_spp_tagihan ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: keuangan.transaksi_spp_tagihan
SELECT
    t.id,
    t.tenant_id,
    t.siswa_id,
    t.komponen_id,
    t.tarif_id,
    t.tahun_ajaran_id,
    t.bulan,
    t.nominal_tagihan
FROM keuangan.transaksi_spp_tagihan t
INNER JOIN tenants tena ON t.tenant_id = tena.id
INNER JOIN siswa sisw ON t.siswa_id = sisw.id
INNER JOIN transaksi_spp_komponen tran ON t.komponen_id = tran.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── keuangan.transaksi_spp_pembayaran ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: keuangan.transaksi_spp_pembayaran
SELECT
    t.id,
    t.tenant_id,
    t.tagihan_id,
    t.siswa_id,
    t.nominal_dibayar,
    t.metode_pembayaran,
    t.tanggal_bayar,
    t.kasir_id
FROM keuangan.transaksi_spp_pembayaran t
INNER JOIN tenants tena ON t.tenant_id = tena.id
INNER JOIN transaksi_spp_tagihan tran ON t.tagihan_id = tran.id
INNER JOIN siswa sisw ON t.siswa_id = sisw.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.tenant_id DESC
LIMIT 50 OFFSET 0;

-- ── keuangan.transaksi_spp_keringanan ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: keuangan.transaksi_spp_keringanan
SELECT
    t.id,
    t.tenant_id,
    t.siswa_id,
    t.komponen_id,
    t.tipe_keringanan,
    t.nilai,
    t.keterangan,
    t.created_at
FROM keuangan.transaksi_spp_keringanan t
INNER JOIN tenants tena ON t.tenant_id = tena.id
INNER JOIN siswa sisw ON t.siswa_id = sisw.id
INNER JOIN transaksi_spp_komponen tran ON t.komponen_id = tran.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── keuangan.transaksi_spp_audit_log ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: keuangan.transaksi_spp_audit_log
SELECT
    t.id,
    t.tenant_id,
    t.user_id,
    t.aksi,
    t.tabel_target,
    t.target_id,
    t.data_sebelum,
    t.data_sesudah
FROM keuangan.transaksi_spp_audit_log t
INNER JOIN tenants tena ON t.tenant_id = tena.id
INNER JOIN users user ON t.user_id = user.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;
