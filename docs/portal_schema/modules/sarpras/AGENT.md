# 🤖 AGENT GUIDE: Schema `sarpras`
> **Panduan Pembuatan Program** — Sinta SaaS PostgreSQL v21

---

## 1. Identitas Modul

| Property | Value |
|----------|-------|
| **Schema** | `sarpras` |
| **Icon** | 🏢 |
| **Judul** | Sarpras — Inventaris, Gudang & Maintenance |
| **Stack** | PHP 8.2 | Laravel 11 | PostgreSQL | QR Code Label | S3 (foto barang) |
| **Jumlah Tabel** | 17 tabel |

## 2. Deskripsi Modul

Manajemen Sarana & Prasarana: Inventaris KIB A/B/C, Gudang BHP, Mutasi Barang, dan Maintenance/Service.

## 3. Service Classes yang Perlu Dibuat

- `InventarisService`
- `GudangService`
- `MutasiService`
- `MaintenanceService`
- `KIBReportService`

## 4. Key Architectural Patterns

- KIB A (Tanah), KIB B (Peralatan), KIB C (Gedung): tiga tipe aset utama
- QR Code label ditempel pada setiap barang untuk scan saat audit inventaris
- Mutasi barang: dari gudang ke ruangan, antar ruangan, disposal/penghapusan
- Maintenance record: setiap kerusakan/service barang dicatat dengan foto bukti

## 5. Daftar Tabel (17 tabel)

| Nama Tabel | Deskripsi | Kolom Utama |
|------------|-----------|-------------|
| `tanah` | Master Aset Tetap Tanah Sekolah (KIB A Permendagri 108 & Dapodik) - Kode Pemda 1 | `id, tenant_id, kode_tanah_pemda, nama_lokasi_tanah, luas_m2...` |
| `bangunan` | Master Aset Tetap Gedung/Bangunan (KIB C Permendagri 108 & Dapodik) - Kode Pemda | `id, tenant_id, tanah_id, kode_bangunan_pemda, nama_bangunan...` |
| `ruang` | Master Ruangan Sekolah (KIR Kartu Inventaris Ruangan & Dapodik) - Kode Pemda 14  | `id, tenant_id, bangunan_id, kode_ruang_pemda, nama_ruang...` |
| `kategori_barang` | Master Klasifikasi Barang & Jasa (Permendagri 108) - Kelompok Utama (Barang Moda | `id, tenant_id, kode_kategori, nama_kategori, kelompok_utama...` |
| `sumber_dana` | Master Sumber Dana Belanja Sarpras (BOS Reguler, BOS Kinerja, BPOPP, Komite Seko | `id, tenant_id, nama_sumber_dana, kode_sumber_dana, tahun_anggaran...` |
| `vendor_supplier` | Master Rekanan / Toko Supplier Penyedia Barang & Jasa - Nama Toko/CV, Contact Pe | `id, tenant_id, nama_vendor, nama_pemilik_contact, no_telepon_hp...` |
| `barang_habis_pakai` | Master Katalog Persediaan Barang Habis Pakai (BHP / ATK / Kebersihan) - Kode Pem | `id, tenant_id, kategori_id, kode_barang_pemda, nama_barang...` |
| `penerimaan_barang` | Header Nota Pembelian BHP Masuk (Stock In) - No. Faktur/Kwitansi, Tanggal Beli,  | `id, tenant_id, no_faktur_nota, tgl_penerimaan_beli, vendor_id...` |
| `penerimaan_barang_detail` | Rincian Item Barang Pembelian BHP Masuk - Qty Masuk, Satuan, Harga Satuan Beli,  | `id, penerimaan_id, barang_bhp_id, qty_masuk, satuan...` |
| `pengeluaran_barang` | Header Bon Pemakaian BHP Keluar (Stock Out) - No. Bon, Tanggal, Pemohon PTK, Per | `id, tenant_id, no_bon_pengeluaran, tgl_pengeluaran, pemohon_ptk_id...` |
| `pengeluaran_barang_detail` | Rincian Item Barang Pengeluaran BHP Keluar - Qty Keluar & Satuan. | `id, pengeluaran_id, barang_bhp_id, qty_keluar, satuan...` |
| `stock_opname_gudang` | Berita Acara Stock Opname Gudang (BASO) - No BA Opname, Tanggal, Auditor PTK, &  | `id, tenant_id, no_berita_acara_opname, tgl_opname, auditor_ptk_id...` |
| `stock_opname_detail` | Detail Selisih Hitung Fisik vs Sistem Opname - Stok Sistem, Stok Fisik, Selisih, | `id, opname_id, barang_bhp_id, stok_sistem, stok_fisik_hitung...` |
| `barang_modal` | Master Aset Tetap Modal KIB B (Peralatan & Mesin + Merge alat_inventaris Dapodik | `id, tenant_id, kategori_id, kode_barang_pemda, no_register_aset...` |
| `penempatan_barang_modal` | Penempatan Aset Modal di Ruangan (KIR Kartu Inventaris Ruangan) - Tgl Pasang, Pe | `id, tenant_id, barang_modal_id, ruang_id, tgl_pemasangan_penempatan...` |
| `riwayat_perbaikan` | Log Service & Maintenance Terlaksana (Gedung, Mesin, AC, Jaringan) - SPK, Kerusa | `id, tenant_id, kategori_objek, barang_modal_id, bangunan_id...` |
| `rencana_perbaikan` | Perencanaan Perbaikan & Maintenance 1 Tahun (RKAS & Akreditasi BAN-S/M) - Deskri | `id, tenant_id, tahun_anggaran, kategori_objek, barang_modal_id...` |


## 6. Critical JOIN Relationships

| Source Table | References | Via Column | Keterangan |
|-------------|-----------|-----------|-----------|
| `sarpras.barang_inventaris` | `sarpras.kategori_barang` | `kategori_id` | INVENTARIS: Barang → Kategori |
| `sarpras.barang_inventaris` | `sarpras.ruangan` | `lokasi_ruangan_id` | LOKASI: Barang → Ruangan |
| `sarpras.stock_gudang` | `sarpras.barang_inventaris` | `barang_id` | STOK: Gudang → Barang |
| `sarpras.maintenance_record` | `sarpras.barang_inventaris` | `barang_id` | SERVIS: Maintenance → Barang |


## 7. Panduan Anti-Pattern (WAJIB DIIKUTI)

### ❌ SELECT * — Dilarang Keras
```sql
-- ❌ DILARANG
SELECT * FROM sarpras.nama_tabel;

-- ✅ WAJIB: Pilih kolom secara eksplisit
SELECT t.id, t.tenant_id, t.kolom1, t.kolom2
FROM sarpras.nama_tabel t
WHERE t.tenant_id = $1;
```

### ❌ Query Tanpa tenant_id Filter
```sql
-- ❌ DILARANG: Cross-tenant data leak!
SELECT id, nama FROM sarpras.nama_tabel;

-- ✅ WAJIB: Selalu filter tenant_id
SELECT t.id, t.nama
FROM sarpras.nama_tabel t
WHERE t.tenant_id = $1;  -- $1 = ID sekolah yang sedang login
```

### ❌ N+1 Query Problem
Jangan melakukan query di dalam loop. Gunakan JOIN atau Eager Loading.

```php
// ❌ DILARANG — N+1 problem: query di dalam loop
// $items = Query(...)->get();
// foreach ($items as $item) { $detail = Query(item_id)->first(); }

// ✅ WAJIB — Gunakan JOIN atau Eager Loading:
// DB::table('sarpras.nama_tabel AS t')
//   ->join('schema2.detail AS d', 't.id', '=', 'd.item_id')
//   ->select(['t.id', 't.nama', 'd.detail_kolom'])
//   ->where('t.tenant_id', $tenantId)->get();
```

## 8. Security Rules

- **tenant_id WAJIB** ada di setiap WHERE clause query
- **Prepared Statement** (PDO/Eloquent) untuk semua query dengan input user
- **Audit Log** wajib di setiap operasi CUD (Create/Update/Delete) via `sistem.audit_log`
- **Input validation** sebelum masuk query: `strip_tags()`, `htmlspecialchars()`, `filter_var()`

## 9. File Referensi Modul Ini

| File | Isi |
|------|-----|
| [`index_guide.sql`](./index_guide.sql) | DDL Index definitions + rekomendasi tambahan |
| [`join_patterns.sql`](./join_patterns.sql) | Contoh JOIN patterns per tabel |
| [`query_examples.sql`](./query_examples.sql) | Contoh SELECT eksplisit per tabel (no SELECT *) |
| [`no_select_star.sql`](./no_select_star.sql) | Perbandingan ❌ SELECT * vs ✅ Explicit columns |

---
*Generated: 2026-07-30 | Sinta SaaS PostgreSQL v21 | 164 Tabel | 16 Schema*
