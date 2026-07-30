# 🤖 AGENT GUIDE: Schema `perpustakaan`
> **Panduan Pembuatan Program** — Sinta SaaS PostgreSQL v21

---

## 1. Identitas Modul

| Property | Value |
|----------|-------|
| **Schema** | `perpustakaan` |
| **Icon** | 📖 |
| **Judul** | Perpustakaan — Library Digital & Sirkulasi |
| **Stack** | PHP 8.2 | Laravel 11 | PostgreSQL | Barcode Scanner Integration | Queue (reminder keterlambatan) |
| **Jumlah Tabel** | 18 tabel |

## 2. Deskripsi Modul

Sistem perpustakaan digital: Katalog buku, Peminjaman, Pengembalian, Denda, Pengunjung, dan Event Literasi.

## 3. Service Classes yang Perlu Dibuat

- `KatalogService`
- `SirkulasiService`
- `DendaService`
- `PengunjungService`
- `LaporanPerpusService`

## 4. Key Architectural Patterns

- ISBN lookup API untuk auto-fill data buku dari Open Library / Google Books
- Barcode/QR Code untuk scan peminjaman & pengembalian buku
- Denda otomatis dihitung: (tgl_kembali - tgl_jatuh_tempo) × tarif_denda/hari
- Integrasi dengan keuangan: denda buku bisa masuk tagihan sekolah siswa

## 5. Daftar Tabel (18 tabel)

| Nama Tabel | Deskripsi | Kolom Utama |
|------------|-----------|-------------|
| `perpus_bibliografi` | Katalog buku DDC. | `id, tenant_id, isbn, judul, judul_seri...` |
| `perpus_eksemplar` | Fisik buku barcode. | `id, tenant_id, bibliografi_id, barcode, nomor_induk...` |
| `perpus_sirkulasi` | Sirkulasi pinjam kembali. | `id, tenant_id, no_transaksi, anggota_id, eksemplar_id...` |
| `perpus_anggota` | Anggota perpus. | `id, tenant_id, tipe_anggota, user_id, siswa_id...` |
| `perpus_denda` | Denda perpus. | `id, tenant_id, sumber_transaksi, sirkulasi_id, distribusi_id...` |
| `perpus_reservasi` | Booking buku. | `id, tenant_id, bibliografi_id, anggota_id, tanggal_reservasi...` |
| `perpus_opname` | Stock opname perpus. | `id, tenant_id, nama_sesi, tanggal_mulai, tanggal_selesai...` |
| `perpus_kategori_ddc` | DDC. | `kode, nama, induk_kode, tingkat...` |
| `perpus_lokasi_rak` | Lokasi rak. | `id, tenant_id, kode, nama, gedung...` |
| `perpus_paket_buku` | Paket buku. | `id, tenant_id, nama_paket, kelas_id, jenjang...` |
| `perpus_event` | Event pinjam. | `id, tenant_id, nama_event, penyelenggara, tgl_mulai...` |
| `perpus_serial_berkala` | Majalah/jurnal. | `id, tenant_id, nama_media, jenis, frekuensi...` |
| `perpus_staf_kompetensi` | Staf perpus. | `id, tenant_id, nama_staf, jabatan, nama_kegiatan...` |
| `perpus_notifikasi` | Notifikasi perpus. | `id, tenant_id, anggota_id, tipe, pesan...` |
| `perpus_buku_tamu` | Buku tamu. | `id, tenant_id, nisn, nama_pengunjung, kelas...` |
| `perpus_ulasan` | Ulasan buku. | `id, tenant_id, bibliografi_id, anggota_id, rating...` |
| `perpus_usulan_buku` | Usulan buku. | `id, tenant_id, judul, pengarang, penerbit...` |
| `perpus_pengaturan` | Config perpus. | `id, tenant_id, nama_perpustakaan, nomor_pokok, kepala_perpustakaan...` |


## 6. Critical JOIN Relationships

| Source Table | References | Via Column | Keterangan |
|-------------|-----------|-----------|-----------|
| `perpustakaan.peminjaman_buku` | `perpustakaan.koleksi_buku` | `buku_id` | PINJAM: Peminjaman → Buku |
| `perpustakaan.peminjaman_buku` | `siswa.siswa` | `peminjam_siswa_id` | PINJAM: Peminjaman → Siswa |
| `perpustakaan.denda_peminjaman` | `perpustakaan.peminjaman_buku` | `peminjaman_id` | DENDA: Denda → Record Peminjaman |


## 7. Panduan Anti-Pattern (WAJIB DIIKUTI)

### ❌ SELECT * — Dilarang Keras
```sql
-- ❌ DILARANG
SELECT * FROM perpustakaan.nama_tabel;

-- ✅ WAJIB: Pilih kolom secara eksplisit
SELECT t.id, t.tenant_id, t.kolom1, t.kolom2
FROM perpustakaan.nama_tabel t
WHERE t.tenant_id = $1;
```

### ❌ Query Tanpa tenant_id Filter
```sql
-- ❌ DILARANG: Cross-tenant data leak!
SELECT id, nama FROM perpustakaan.nama_tabel;

-- ✅ WAJIB: Selalu filter tenant_id
SELECT t.id, t.nama
FROM perpustakaan.nama_tabel t
WHERE t.tenant_id = $1;  -- $1 = ID sekolah yang sedang login
```

### ❌ N+1 Query Problem
Jangan melakukan query di dalam loop. Gunakan JOIN atau Eager Loading.

```php
// ❌ DILARANG — N+1 problem: query di dalam loop
// $items = Query(...)->get();
// foreach ($items as $item) { $detail = Query(item_id)->first(); }

// ✅ WAJIB — Gunakan JOIN atau Eager Loading:
// DB::table('perpustakaan.nama_tabel AS t')
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
