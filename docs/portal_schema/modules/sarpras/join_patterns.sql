-- ════════════════════════════════════════════════════
-- JOIN PATTERNS: Schema SARPRAS
-- Panduan JOIN yang benar berdasarkan FK Constraint
-- ════════════════════════════════════════════════════

-- ── Tabel: sarpras.tanah ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: sarpras.tanah
SELECT
    t.id,
    t.tenant_id,
    t.kode_tanah_pemda,
    t.nama_lokasi_tanah,
    t.luas_m2,
    t.status_kepemilikan,
    t.no_sertifikat,
    t.tgl_sertifikat
FROM sarpras.tanah t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: sarpras.bangunan ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: sarpras.bangunan
SELECT
    t.id,
    t.tenant_id,
    t.tanah_id,
    t.kode_bangunan_pemda,
    t.nama_bangunan,
    t.jumlah_lantai,
    t.luas_lantai_m2,
    t.tahun_dibangun
FROM sarpras.bangunan t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
INNER JOIN sarpras.tanah tana ON t.tanah_id = tana.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: sarpras.ruang ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: sarpras.ruang
SELECT
    t.id,
    t.tenant_id,
    t.bangunan_id,
    t.kode_ruang_pemda,
    t.nama_ruang,
    t.jenis_ruang,
    t.kapasitas_siswa,
    t.panjang_m
FROM sarpras.ruang t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
INNER JOIN sarpras.bangunan bang ON t.bangunan_id = bang.id
INNER JOIN kepegawaian.ptk_identitas ptk_ ON t.penanggung_jawab_id = ptk_.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: sarpras.kategori_barang ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: sarpras.kategori_barang
SELECT
    t.id,
    t.tenant_id,
    t.kode_kategori,
    t.nama_kategori,
    t.kelompok_utama,
    t.sub_kelompok,
    t.masa_manfaat_bulan,
    t.sifat_barang
FROM sarpras.kategori_barang t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: sarpras.sumber_dana ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: sarpras.sumber_dana
SELECT
    t.id,
    t.tenant_id,
    t.nama_sumber_dana,
    t.kode_sumber_dana,
    t.tahun_anggaran,
    t.keterangan,
    t.created_at
FROM sarpras.sumber_dana t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: sarpras.vendor_supplier ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: sarpras.vendor_supplier
SELECT
    t.id,
    t.tenant_id,
    t.nama_vendor,
    t.nama_pemilik_contact,
    t.no_telepon_hp,
    t.alamat_lengkap,
    t.npwp_vendor,
    t.created_at
FROM sarpras.vendor_supplier t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: sarpras.barang_habis_pakai ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: sarpras.barang_habis_pakai
SELECT
    t.id,
    t.tenant_id,
    t.kategori_id,
    t.kode_barang_pemda,
    t.nama_barang,
    t.merk_spesifikasi,
    t.satuan,
    t.stok_minimal_alert
FROM sarpras.barang_habis_pakai t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
INNER JOIN sarpras.kategori_barang kate ON t.kategori_id = kate.id
INNER JOIN sarpras.ruang ruan ON t.lokasi_gudang_id = ruan.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: sarpras.penerimaan_barang ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: sarpras.penerimaan_barang
SELECT
    t.id,
    t.tenant_id,
    t.no_faktur_nota,
    t.tgl_penerimaan_beli,
    t.vendor_id,
    t.sumber_dana_id,
    t.penerima_ptk_id,
    t.total_nominal_transaksi
FROM sarpras.penerimaan_barang t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
INNER JOIN sarpras.vendor_supplier vend ON t.vendor_id = vend.id
INNER JOIN sarpras.sumber_dana sumb ON t.sumber_dana_id = sumb.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: sarpras.penerimaan_barang_detail ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: sarpras.penerimaan_barang_detail
SELECT
    t.id,
    t.penerimaan_id,
    t.barang_bhp_id,
    t.qty_masuk,
    t.satuan,
    t.harga_satuan_beli,
    t.subtotal_harga,
    t.tgl_kadaluarsa_exp
FROM sarpras.penerimaan_barang_detail t
INNER JOIN sarpras.penerimaan_barang pene ON t.penerimaan_id = pene.id
INNER JOIN sarpras.barang_habis_pakai bara ON t.barang_bhp_id = bara.id
WHERE t.id = $1
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: sarpras.pengeluaran_barang ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: sarpras.pengeluaran_barang
SELECT
    t.id,
    t.tenant_id,
    t.no_bon_pengeluaran,
    t.tgl_pengeluaran,
    t.pemohon_ptk_id,
    t.peruntukan_kegiatan,
    t.ruang_tujuan_id,
    t.petugas_gudang_id
FROM sarpras.pengeluaran_barang t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
INNER JOIN kepegawaian.ptk_identitas ptk_ ON t.pemohon_ptk_id = ptk_.id
INNER JOIN sarpras.ruang ruan ON t.ruang_tujuan_id = ruan.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: sarpras.pengeluaran_barang_detail ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: sarpras.pengeluaran_barang_detail
SELECT
    t.id,
    t.pengeluaran_id,
    t.barang_bhp_id,
    t.qty_keluar,
    t.satuan,
    t.created_at
FROM sarpras.pengeluaran_barang_detail t
INNER JOIN sarpras.pengeluaran_barang peng ON t.pengeluaran_id = peng.id
INNER JOIN sarpras.barang_habis_pakai bara ON t.barang_bhp_id = bara.id
WHERE t.id = $1
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: sarpras.stock_opname_gudang ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: sarpras.stock_opname_gudang
SELECT
    t.id,
    t.tenant_id,
    t.no_berita_acara_opname,
    t.tgl_opname,
    t.auditor_ptk_id,
    t.catatan_audit_opname,
    t.created_at
FROM sarpras.stock_opname_gudang t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
INNER JOIN kepegawaian.ptk_identitas ptk_ ON t.auditor_ptk_id = ptk_.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: sarpras.stock_opname_detail ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: sarpras.stock_opname_detail
SELECT
    t.id,
    t.opname_id,
    t.barang_bhp_id,
    t.stok_sistem,
    t.stok_fisik_hitung,
    t.selisih_stok,
    t.alasan_selisih,
    t.created_at
FROM sarpras.stock_opname_detail t
INNER JOIN sarpras.stock_opname_gudang stoc ON t.opname_id = stoc.id
INNER JOIN sarpras.barang_habis_pakai bara ON t.barang_bhp_id = bara.id
WHERE t.id = $1
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: sarpras.barang_modal ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: sarpras.barang_modal
SELECT
    t.id,
    t.tenant_id,
    t.kategori_id,
    t.kode_barang_pemda,
    t.no_register_aset,
    t.nama_barang,
    t.merk_tipe_spesifikasi,
    t.qty_unit
FROM sarpras.barang_modal t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
INNER JOIN sarpras.kategori_barang kate ON t.kategori_id = kate.id
INNER JOIN sarpras.vendor_supplier vend ON t.vendor_id = vend.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: sarpras.penempatan_barang_modal ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: sarpras.penempatan_barang_modal
SELECT
    t.id,
    t.tenant_id,
    t.barang_modal_id,
    t.ruang_id,
    t.tgl_pemasangan_penempatan,
    t.penanggung_jawab_ruang_id,
    t.petugas_pemasang_id,
    t.status_saat_ini
FROM sarpras.penempatan_barang_modal t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
INNER JOIN sarpras.barang_modal bara ON t.barang_modal_id = bara.id
INNER JOIN sarpras.ruang ruan ON t.ruang_id = ruan.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: sarpras.riwayat_perbaikan ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: sarpras.riwayat_perbaikan
SELECT
    t.id,
    t.tenant_id,
    t.kategori_objek,
    t.barang_modal_id,
    t.bangunan_id,
    t.ruang_id,
    t.no_spk_perbaikan,
    t.tgl_mulai_perbaikan
FROM sarpras.riwayat_perbaikan t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
INNER JOIN sarpras.barang_modal bara ON t.barang_modal_id = bara.id
INNER JOIN sarpras.sumber_dana sumb ON t.sumber_dana_id = sumb.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: sarpras.rencana_perbaikan ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: sarpras.rencana_perbaikan
SELECT
    t.id,
    t.tenant_id,
    t.tahun_anggaran,
    t.kategori_objek,
    t.barang_modal_id,
    t.bangunan_id,
    t.ruang_id,
    t.deskripsi_rencana_pekerjaan
FROM sarpras.rencana_perbaikan t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
INNER JOIN sarpras.sumber_dana sumb ON t.target_sumber_dana_id = sumb.id
INNER JOIN kepegawaian.ptk_identitas ptk_ ON t.usulan_oleh_ptk_id = ptk_.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;
