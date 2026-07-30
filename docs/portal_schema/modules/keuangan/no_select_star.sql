-- ════════════════════════════════════════════════════
-- NO SELECT * GUIDE: Schema KEUANGAN
-- Perbandingan: ❌ SELECT * vs ✅ Explicit Columns
-- ════════════════════════════════════════════════════

-- ── keuangan.transaksi_spp_komponen ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM keuangan.transaksi_spp_komponen;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.nama_komponen, t.tipe_periode, t.is_active, t.created_at
FROM keuangan.transaksi_spp_komponen t
WHERE t.tenant_id = $1;

-- ── keuangan.transaksi_spp_tarif ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM keuangan.transaksi_spp_tarif;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.komponen_id, t.kelas_id, t.jenjang_id, t.jalur_masuk
FROM keuangan.transaksi_spp_tarif t
WHERE t.tenant_id = $1;

-- ── keuangan.transaksi_spp_tagihan ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM keuangan.transaksi_spp_tagihan;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.siswa_id, t.komponen_id, t.tarif_id, t.tahun_ajaran_id
FROM keuangan.transaksi_spp_tagihan t
WHERE t.tenant_id = $1;

-- ── keuangan.transaksi_spp_pembayaran ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM keuangan.transaksi_spp_pembayaran;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.tagihan_id, t.siswa_id, t.nominal_dibayar, t.metode_pembayaran
FROM keuangan.transaksi_spp_pembayaran t
WHERE t.tenant_id = $1;

-- ── keuangan.transaksi_spp_keringanan ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM keuangan.transaksi_spp_keringanan;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.siswa_id, t.komponen_id, t.tipe_keringanan, t.nilai
FROM keuangan.transaksi_spp_keringanan t
WHERE t.tenant_id = $1;

-- ── keuangan.transaksi_spp_audit_log ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM keuangan.transaksi_spp_audit_log;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.user_id, t.aksi, t.tabel_target, t.target_id
FROM keuangan.transaksi_spp_audit_log t
WHERE t.tenant_id = $1;
