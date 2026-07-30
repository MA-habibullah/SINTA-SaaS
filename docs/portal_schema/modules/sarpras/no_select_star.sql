-- ════════════════════════════════════════════════════
-- NO SELECT * GUIDE: Schema SARPRAS
-- Perbandingan: ❌ SELECT * vs ✅ Explicit Columns
-- ════════════════════════════════════════════════════

-- ── sarpras.tanah ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM sarpras.tanah;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.kode_tanah_pemda, t.nama_lokasi_tanah, t.luas_m2, t.status_kepemilikan
FROM sarpras.tanah t
WHERE t.tenant_id = $1;

-- ── sarpras.bangunan ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM sarpras.bangunan;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.tanah_id, t.kode_bangunan_pemda, t.nama_bangunan, t.jumlah_lantai
FROM sarpras.bangunan t
WHERE t.tenant_id = $1;

-- ── sarpras.ruang ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM sarpras.ruang;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.bangunan_id, t.kode_ruang_pemda, t.nama_ruang, t.jenis_ruang
FROM sarpras.ruang t
WHERE t.tenant_id = $1;

-- ── sarpras.kategori_barang ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM sarpras.kategori_barang;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.kode_kategori, t.nama_kategori, t.kelompok_utama, t.sub_kelompok
FROM sarpras.kategori_barang t
WHERE t.tenant_id = $1;

-- ── sarpras.sumber_dana ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM sarpras.sumber_dana;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.nama_sumber_dana, t.kode_sumber_dana, t.tahun_anggaran, t.keterangan
FROM sarpras.sumber_dana t
WHERE t.tenant_id = $1;

-- ── sarpras.vendor_supplier ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM sarpras.vendor_supplier;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.nama_vendor, t.nama_pemilik_contact, t.no_telepon_hp, t.alamat_lengkap
FROM sarpras.vendor_supplier t
WHERE t.tenant_id = $1;

-- ── sarpras.barang_habis_pakai ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM sarpras.barang_habis_pakai;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.kategori_id, t.kode_barang_pemda, t.nama_barang, t.merk_spesifikasi
FROM sarpras.barang_habis_pakai t
WHERE t.tenant_id = $1;

-- ── sarpras.penerimaan_barang ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM sarpras.penerimaan_barang;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.no_faktur_nota, t.tgl_penerimaan_beli, t.vendor_id, t.sumber_dana_id
FROM sarpras.penerimaan_barang t
WHERE t.tenant_id = $1;

-- ── sarpras.penerimaan_barang_detail ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM sarpras.penerimaan_barang_detail;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.penerimaan_id, t.barang_bhp_id, t.qty_masuk, t.satuan, t.harga_satuan_beli
FROM sarpras.penerimaan_barang_detail t
WHERE t.tenant_id = $1;

-- ── sarpras.pengeluaran_barang ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM sarpras.pengeluaran_barang;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.no_bon_pengeluaran, t.tgl_pengeluaran, t.pemohon_ptk_id, t.peruntukan_kegiatan
FROM sarpras.pengeluaran_barang t
WHERE t.tenant_id = $1;

-- ── sarpras.pengeluaran_barang_detail ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM sarpras.pengeluaran_barang_detail;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.pengeluaran_id, t.barang_bhp_id, t.qty_keluar, t.satuan, t.created_at
FROM sarpras.pengeluaran_barang_detail t
WHERE t.tenant_id = $1;

-- ── sarpras.stock_opname_gudang ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM sarpras.stock_opname_gudang;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.no_berita_acara_opname, t.tgl_opname, t.auditor_ptk_id, t.catatan_audit_opname
FROM sarpras.stock_opname_gudang t
WHERE t.tenant_id = $1;

-- ── sarpras.stock_opname_detail ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM sarpras.stock_opname_detail;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.opname_id, t.barang_bhp_id, t.stok_sistem, t.stok_fisik_hitung, t.selisih_stok
FROM sarpras.stock_opname_detail t
WHERE t.tenant_id = $1;

-- ── sarpras.barang_modal ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM sarpras.barang_modal;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.kategori_id, t.kode_barang_pemda, t.no_register_aset, t.nama_barang
FROM sarpras.barang_modal t
WHERE t.tenant_id = $1;

-- ── sarpras.penempatan_barang_modal ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM sarpras.penempatan_barang_modal;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.barang_modal_id, t.ruang_id, t.tgl_pemasangan_penempatan, t.penanggung_jawab_ruang_id
FROM sarpras.penempatan_barang_modal t
WHERE t.tenant_id = $1;

-- ── sarpras.riwayat_perbaikan ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM sarpras.riwayat_perbaikan;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.kategori_objek, t.barang_modal_id, t.bangunan_id, t.ruang_id
FROM sarpras.riwayat_perbaikan t
WHERE t.tenant_id = $1;

-- ── sarpras.rencana_perbaikan ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM sarpras.rencana_perbaikan;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.tahun_anggaran, t.kategori_objek, t.barang_modal_id, t.bangunan_id
FROM sarpras.rencana_perbaikan t
WHERE t.tenant_id = $1;
