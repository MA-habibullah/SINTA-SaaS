-- ════════════════════════════════════════════════════
-- INDEX GUIDE: Schema SARPRAS
-- Rekomendasi index untuk performa query optimal
-- Jalankan: psql -d sinta_db -f index_guide.sql
-- ════════════════════════════════════════════════════

-- ── sarpras.tanah ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_tanah_tenant ON sarpras.tanah (tenant_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_tanah_created ON sarpras.tanah (created_at DESC);

-- ── sarpras.bangunan ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_bangunan_tenant ON sarpras.bangunan (tenant_id);

-- ✅ FK JOIN index: tanah_id
CREATE INDEX IF NOT EXISTS idx_bangunan_tanah_id ON sarpras.bangunan (tanah_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_bangunan_created ON sarpras.bangunan (created_at DESC);

-- ── sarpras.ruang ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_ruang_tenant ON sarpras.ruang (tenant_id);

-- ✅ FK JOIN index: bangunan_id
CREATE INDEX IF NOT EXISTS idx_ruang_bangunan_id ON sarpras.ruang (bangunan_id);

-- ✅ FK JOIN index: penanggung_jawab_id
CREATE INDEX IF NOT EXISTS idx_ruang_penanggung_jawab_id ON sarpras.ruang (penanggung_jawab_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_ruang_created ON sarpras.ruang (created_at DESC);

-- ── sarpras.kategori_barang ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_kategori_barang_tenant ON sarpras.kategori_barang (tenant_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_kategori_barang_created ON sarpras.kategori_barang (created_at DESC);

-- ── sarpras.sumber_dana ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_sumber_dana_tenant ON sarpras.sumber_dana (tenant_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_sumber_dana_created ON sarpras.sumber_dana (created_at DESC);

-- ── sarpras.vendor_supplier ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_vendor_supplier_tenant ON sarpras.vendor_supplier (tenant_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_vendor_supplier_created ON sarpras.vendor_supplier (created_at DESC);

-- ── sarpras.barang_habis_pakai ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_barang_habis_pakai_tenant ON sarpras.barang_habis_pakai (tenant_id);

-- ✅ FK JOIN index: kategori_id
CREATE INDEX IF NOT EXISTS idx_barang_habis_pakai_kategori_id ON sarpras.barang_habis_pakai (kategori_id);

-- ✅ FK JOIN index: lokasi_gudang_id
CREATE INDEX IF NOT EXISTS idx_barang_habis_pakai_lokasi_gudang_id ON sarpras.barang_habis_pakai (lokasi_gudang_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_barang_habis_pakai_created ON sarpras.barang_habis_pakai (created_at DESC);

-- ── sarpras.penerimaan_barang ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_penerimaan_barang_tenant ON sarpras.penerimaan_barang (tenant_id);

-- ✅ FK JOIN index: vendor_id
CREATE INDEX IF NOT EXISTS idx_penerimaan_barang_vendor_id ON sarpras.penerimaan_barang (vendor_id);

-- ✅ FK JOIN index: sumber_dana_id
CREATE INDEX IF NOT EXISTS idx_penerimaan_barang_sumber_dana_id ON sarpras.penerimaan_barang (sumber_dana_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_penerimaan_barang_created ON sarpras.penerimaan_barang (created_at DESC);

-- ── sarpras.penerimaan_barang_detail ──
-- ✅ FK JOIN index: penerimaan_id
CREATE INDEX IF NOT EXISTS idx_penerimaan_barang_detail_penerimaan_id ON sarpras.penerimaan_barang_detail (penerimaan_id);

-- ✅ FK JOIN index: barang_bhp_id
CREATE INDEX IF NOT EXISTS idx_penerimaan_barang_detail_barang_bhp_id ON sarpras.penerimaan_barang_detail (barang_bhp_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_penerimaan_barang_detail_created ON sarpras.penerimaan_barang_detail (created_at DESC);

-- ── sarpras.pengeluaran_barang ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_pengeluaran_barang_tenant ON sarpras.pengeluaran_barang (tenant_id);

-- ✅ FK JOIN index: pemohon_ptk_id
CREATE INDEX IF NOT EXISTS idx_pengeluaran_barang_pemohon_ptk_id ON sarpras.pengeluaran_barang (pemohon_ptk_id);

-- ✅ FK JOIN index: ruang_tujuan_id
CREATE INDEX IF NOT EXISTS idx_pengeluaran_barang_ruang_tujuan_id ON sarpras.pengeluaran_barang (ruang_tujuan_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_pengeluaran_barang_created ON sarpras.pengeluaran_barang (created_at DESC);

-- ── sarpras.pengeluaran_barang_detail ──
-- ✅ FK JOIN index: pengeluaran_id
CREATE INDEX IF NOT EXISTS idx_pengeluaran_barang_detail_pengeluaran_id ON sarpras.pengeluaran_barang_detail (pengeluaran_id);

-- ✅ FK JOIN index: barang_bhp_id
CREATE INDEX IF NOT EXISTS idx_pengeluaran_barang_detail_barang_bhp_id ON sarpras.pengeluaran_barang_detail (barang_bhp_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_pengeluaran_barang_detail_created ON sarpras.pengeluaran_barang_detail (created_at DESC);

-- ── sarpras.stock_opname_gudang ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_stock_opname_gudang_tenant ON sarpras.stock_opname_gudang (tenant_id);

-- ✅ FK JOIN index: auditor_ptk_id
CREATE INDEX IF NOT EXISTS idx_stock_opname_gudang_auditor_ptk_id ON sarpras.stock_opname_gudang (auditor_ptk_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_stock_opname_gudang_created ON sarpras.stock_opname_gudang (created_at DESC);

-- ── sarpras.stock_opname_detail ──
-- ✅ FK JOIN index: opname_id
CREATE INDEX IF NOT EXISTS idx_stock_opname_detail_opname_id ON sarpras.stock_opname_detail (opname_id);

-- ✅ FK JOIN index: barang_bhp_id
CREATE INDEX IF NOT EXISTS idx_stock_opname_detail_barang_bhp_id ON sarpras.stock_opname_detail (barang_bhp_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_stock_opname_detail_created ON sarpras.stock_opname_detail (created_at DESC);

-- ── sarpras.barang_modal ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_barang_modal_tenant ON sarpras.barang_modal (tenant_id);

-- ✅ FK JOIN index: kategori_id
CREATE INDEX IF NOT EXISTS idx_barang_modal_kategori_id ON sarpras.barang_modal (kategori_id);

-- ✅ FK JOIN index: vendor_id
CREATE INDEX IF NOT EXISTS idx_barang_modal_vendor_id ON sarpras.barang_modal (vendor_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_barang_modal_created ON sarpras.barang_modal (created_at DESC);

-- ── sarpras.penempatan_barang_modal ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_penempatan_barang_modal_tenant ON sarpras.penempatan_barang_modal (tenant_id);

-- ✅ FK JOIN index: barang_modal_id
CREATE INDEX IF NOT EXISTS idx_penempatan_barang_modal_barang_modal_id ON sarpras.penempatan_barang_modal (barang_modal_id);

-- ✅ FK JOIN index: ruang_id
CREATE INDEX IF NOT EXISTS idx_penempatan_barang_modal_ruang_id ON sarpras.penempatan_barang_modal (ruang_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_penempatan_barang_modal_created ON sarpras.penempatan_barang_modal (created_at DESC);

-- ── sarpras.riwayat_perbaikan ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_riwayat_perbaikan_tenant ON sarpras.riwayat_perbaikan (tenant_id);

-- ✅ FK JOIN index: barang_modal_id
CREATE INDEX IF NOT EXISTS idx_riwayat_perbaikan_barang_modal_id ON sarpras.riwayat_perbaikan (barang_modal_id);

-- ✅ FK JOIN index: sumber_dana_id
CREATE INDEX IF NOT EXISTS idx_riwayat_perbaikan_sumber_dana_id ON sarpras.riwayat_perbaikan (sumber_dana_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_riwayat_perbaikan_created ON sarpras.riwayat_perbaikan (created_at DESC);

-- ── sarpras.rencana_perbaikan ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_rencana_perbaikan_tenant ON sarpras.rencana_perbaikan (tenant_id);

-- ✅ FK JOIN index: target_sumber_dana_id
CREATE INDEX IF NOT EXISTS idx_rencana_perbaikan_target_sumber_dana_id ON sarpras.rencana_perbaikan (target_sumber_dana_id);

-- ✅ FK JOIN index: usulan_oleh_ptk_id
CREATE INDEX IF NOT EXISTS idx_rencana_perbaikan_usulan_oleh_ptk_id ON sarpras.rencana_perbaikan (usulan_oleh_ptk_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_rencana_perbaikan_created ON sarpras.rencana_perbaikan (created_at DESC);
