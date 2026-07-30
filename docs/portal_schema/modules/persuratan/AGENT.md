# 🤖 AGENT GUIDE: Schema `persuratan`
> **Panduan Pembuatan Program** — Sinta SaaS PostgreSQL v21

---

## 1. Identitas Modul

| Property | Value |
|----------|-------|
| **Schema** | `persuratan` |
| **Icon** | 📨 |
| **Judul** | Persuratan — E-Office & Tata Naskah Dinas |
| **Stack** | PHP 8.2 | Laravel 11 | PostgreSQL | S3 (scan surat) | PDF Generator (surat tugas) |
| **Jumlah Tabel** | 9 tabel |

## 2. Deskripsi Modul

Sistem surat menyurat digital: Surat Masuk, Surat Keluar, Disposisi, Surat Tugas, dan Agenda Surat.

## 3. Service Classes yang Perlu Dibuat

- `SuratMasukService`
- `SuratKeluarService`
- `DisposisiService`
- `SuratTugasService`
- `AgendaService`

## 4. Key Architectural Patterns

- Nomor surat otomatis: format [Kode]/[No.Urut]/[Kode-Kab]/[Bulan-Romawi]/[Tahun]
- Kodefikasi surat mengacu pada Permendagri (sudah di-seed dari kodefikasi.xlsx)
- Disposisi: Kepsek → Wakasek → Guru terkait (chain disposition)
- Tanda tangan digital pada surat keluar (e-sign sederhana berbasis image/cert)

## 5. Daftar Tabel (9 tabel)

| Nama Tabel | Deskripsi | Kolom Utama |
|------------|-----------|-------------|
| `kop_surat` | Pengaturan Header Kop Surat Resmi SaaS per Tenant - Instansi Atas, Nama Sekolah, | `id, tenant_id, nama_instansi_atas, nama_sekolah, akreditasi...` |
| `kode_klasifikasi_surat` | Master Kode Klasifikasi Surat & Arsip Standar Dinas Pendidikan / Permendagri / A | `id, tenant_id, parent_id, kode_klasifikasi, nama_klasifikasi...` |
| `jenis_surat` | Master Jenis Naskah Dinas / Surat Resmi - Surat Tugas, Surat Keterangan, Surat U | `id, tenant_id, kode_jenis, nama_jenis, singkatan...` |
| `template_surat` | Master Template HTML E-Surat Builder & Dynamic Variables - {{NAMA_SISWA}}, {{NIP | `id, tenant_id, jenis_surat_id, nama_template, subjek_default...` |
| `surat_masuk` | Registrasi & Agenda Surat Masuk Eksternal - Pencatatan Asal Instansi, Pengirim,  | `id, tenant_id, no_agenda_masuk, nomor_surat_asal, tgl_surat_asal...` |
| `surat_keluar` | Pembuatan & Registrasi Surat Keluar - Mendukung Dual Mode: 1) Digital Generator  | `id, tenant_id, jenis_surat_id, kode_klasifikasi_id, metode_pembuatan...` |
| `disposisi_surat` | Alur Disposisi, Lembar Instruksi & Tracking Surat Masuk - Instruksi Kepala Sekol | `id, tenant_id, surat_masuk_id, pemberi_disposisi_id, penerima_disposisi_id...` |
| `riwayat_paraf_persetujuan` | Verifikasi Paraf Hirarki Draf Surat Keluar (Kasubag TU -> Waka -> Kepala Sekolah | `id, tenant_id, surat_keluar_id, pemeriksa_user_id, urutan_paraf...` |
| `tte_qr_validation` | Keamanan Tanda Tangan Elektronik (TTE) & Verifikasi QR Code Public - Token Hash  | `id, tenant_id, surat_keluar_id, token_verifikasi, qr_code_image_url...` |


## 6. Critical JOIN Relationships

| Source Table | References | Via Column | Keterangan |
|-------------|-----------|-----------|-----------|
| `persuratan.surat_masuk` | `persuratan.kodefikasi_surat` | `kode_klasifikasi` | KODE: Surat → Kodefikasi |
| `persuratan.disposisi_surat` | `persuratan.surat_masuk` | `surat_masuk_id` | DISP: Disposisi → Surat Masuk |
| `persuratan.surat_tugas` | `kepegawaian.ptk_identitas` | `ptk_id` | TUGAS: Surat Tugas → PTK |


## 7. Panduan Anti-Pattern (WAJIB DIIKUTI)

### ❌ SELECT * — Dilarang Keras
```sql
-- ❌ DILARANG
SELECT * FROM persuratan.nama_tabel;

-- ✅ WAJIB: Pilih kolom secara eksplisit
SELECT t.id, t.tenant_id, t.kolom1, t.kolom2
FROM persuratan.nama_tabel t
WHERE t.tenant_id = $1;
```

### ❌ Query Tanpa tenant_id Filter
```sql
-- ❌ DILARANG: Cross-tenant data leak!
SELECT id, nama FROM persuratan.nama_tabel;

-- ✅ WAJIB: Selalu filter tenant_id
SELECT t.id, t.nama
FROM persuratan.nama_tabel t
WHERE t.tenant_id = $1;  -- $1 = ID sekolah yang sedang login
```

### ❌ N+1 Query Problem
Jangan melakukan query di dalam loop. Gunakan JOIN atau Eager Loading.

```php
// ❌ DILARANG — N+1 problem: query di dalam loop
// $items = Query(...)->get();
// foreach ($items as $item) { $detail = Query(item_id)->first(); }

// ✅ WAJIB — Gunakan JOIN atau Eager Loading:
// DB::table('persuratan.nama_tabel AS t')
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
