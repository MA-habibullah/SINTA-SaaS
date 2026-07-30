-- ════════════════════════════════════════════════════
-- INDEX GUIDE: Schema KEUANGAN
-- Rekomendasi index untuk performa query optimal
-- Jalankan: psql -d sinta_db -f index_guide.sql
-- ════════════════════════════════════════════════════

-- ── keuangan.transaksi_spp_komponen ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_transaksi_spp_komponen_tenant ON keuangan.transaksi_spp_komponen (tenant_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_transaksi_spp_komponen_created ON keuangan.transaksi_spp_komponen (created_at DESC);

-- ── keuangan.transaksi_spp_tarif ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_transaksi_spp_tarif_tenant ON keuangan.transaksi_spp_tarif (tenant_id);

-- ✅ FK JOIN index: komponen_id
CREATE INDEX IF NOT EXISTS idx_transaksi_spp_tarif_komponen_id ON keuangan.transaksi_spp_tarif (komponen_id);

-- ✅ FK JOIN index: kelas_id
CREATE INDEX IF NOT EXISTS idx_transaksi_spp_tarif_kelas_id ON keuangan.transaksi_spp_tarif (kelas_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_transaksi_spp_tarif_created ON keuangan.transaksi_spp_tarif (created_at DESC);

-- ── keuangan.transaksi_spp_tagihan ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_transaksi_spp_tagihan_tenant ON keuangan.transaksi_spp_tagihan (tenant_id);

-- ✅ FK JOIN index: siswa_id
CREATE INDEX IF NOT EXISTS idx_transaksi_spp_tagihan_siswa_id ON keuangan.transaksi_spp_tagihan (siswa_id);

-- ✅ FK JOIN index: komponen_id
CREATE INDEX IF NOT EXISTS idx_transaksi_spp_tagihan_komponen_id ON keuangan.transaksi_spp_tagihan (komponen_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_transaksi_spp_tagihan_created ON keuangan.transaksi_spp_tagihan (created_at DESC);

-- ── keuangan.transaksi_spp_pembayaran ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_transaksi_spp_pembayaran_tenant ON keuangan.transaksi_spp_pembayaran (tenant_id);

-- ✅ FK JOIN index: tagihan_id
CREATE INDEX IF NOT EXISTS idx_transaksi_spp_pembayaran_tagihan_id ON keuangan.transaksi_spp_pembayaran (tagihan_id);

-- ✅ FK JOIN index: siswa_id
CREATE INDEX IF NOT EXISTS idx_transaksi_spp_pembayaran_siswa_id ON keuangan.transaksi_spp_pembayaran (siswa_id);

-- ── keuangan.transaksi_spp_keringanan ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_transaksi_spp_keringanan_tenant ON keuangan.transaksi_spp_keringanan (tenant_id);

-- ✅ FK JOIN index: siswa_id
CREATE INDEX IF NOT EXISTS idx_transaksi_spp_keringanan_siswa_id ON keuangan.transaksi_spp_keringanan (siswa_id);

-- ✅ FK JOIN index: komponen_id
CREATE INDEX IF NOT EXISTS idx_transaksi_spp_keringanan_komponen_id ON keuangan.transaksi_spp_keringanan (komponen_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_transaksi_spp_keringanan_created ON keuangan.transaksi_spp_keringanan (created_at DESC);

-- ── keuangan.transaksi_spp_audit_log ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_transaksi_spp_audit_log_tenant ON keuangan.transaksi_spp_audit_log (tenant_id);

-- ✅ FK JOIN index: user_id
CREATE INDEX IF NOT EXISTS idx_transaksi_spp_audit_log_user_id ON keuangan.transaksi_spp_audit_log (user_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_transaksi_spp_audit_log_created ON keuangan.transaksi_spp_audit_log (created_at DESC);
