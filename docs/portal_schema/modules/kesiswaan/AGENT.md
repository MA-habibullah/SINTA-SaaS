# 🤖 AGENT GUIDE: Schema `kesiswaan`
> **Panduan Pembuatan Program** — Sinta SaaS PostgreSQL v21

---

## 1. Identitas Modul

| Property | Value |
|----------|-------|
| **Schema** | `kesiswaan` |
| **Icon** | 🤸 |
| **Judul** | Kesiswaan — Ekskul, Prestasi, PPDB & Kedisiplinan |
| **Stack** | PHP 8.2 | Laravel 11 | PostgreSQL | Queue (notifikasi PPDB) |
| **Jumlah Tabel** | 9 tabel |

## 2. Deskripsi Modul

Manajemen kegiatan kesiswaan: Ekstrakurikuler, Prestasi, Pelanggaran, dan Penerimaan Peserta Didik Baru (PPDB).

## 3. Service Classes yang Perlu Dibuat

- `EkskulService`
- `PrestasiService`
- `PelanggaranService`
- `PPDBService`
- `SeleksiService`

## 4. Key Architectural Patterns

- PPDB: Pendaftar → Seleksi → Diterima → Onboarding jadi siswa baru
- Point system pelanggaran: akumulasi point → trigger notifikasi wali
- Ekskul: bisa multi-join per siswa, max kuota per ekskul
- Prestasi: tingkat lokal/regional/nasional/internasional

## 5. Daftar Tabel (9 tabel)

| Nama Tabel | Deskripsi | Kolom Utama |
|------------|-----------|-------------|
| `master_ekskul` | Master ekskul. | `id, tenant_id, nama_ekskul, kategori, pembina_id...` |
| `anggota_ekskul` | Anggota ekskul. | `id, tenant_id, ekskul_id, siswa_id, tahun_ajaran_id...` |
| `nilai_ekskul` | UNIFIED NILAI EKSKUL | `id, tenant_id, ekskul_id, siswa_id, tahun_ajaran_id...` |
| `kunci_ekskul` | Lock nilai ekskul. | `id, tenant_id, ekskul_id, tahun_ajaran_id, semester...` |
| `jadwal_ekskul` | Jadwal ekskul. | `id, tenant_id, ekskul_id, hari, waktu_mulai...` |
| `jurnal_ekskul` | Jurnal ekskul. | `id, tenant_id, ekskul_id, tanggal_kegiatan, materi...` |
| `data_pembina` | Pembina ekskul. | `id, tenant_id, user_id, no_hp, alamat...` |
| `prestasi_siswa` | UNIFIED PRESTASI | `id, tenant_id, tahun_ajaran_id, semester, bidang_lomba...` |
| `pendaftaran_spmb` | PPDB. | `id, tenant_id, siswa_id, nomor_pendaftaran, jalur_pendaftaran...` |


## 6. Critical JOIN Relationships

| Source Table | References | Via Column | Keterangan |
|-------------|-----------|-----------|-----------|
| `kesiswaan.ppdb_pendaftar` | `kesiswaan.gelombang_ppdb` | `gelombang_id` | PPDB: Pendaftar → Gelombang |
| `kesiswaan.ekskul_anggota` | `siswa.siswa` | `siswa_id` | EKSKUL: Anggota → Siswa |
| `kesiswaan.pelanggaran_siswa` | `akademik.rombel` | `rombel_id` | DISIPLIN: Pelanggaran → Rombel |


## 7. Panduan Anti-Pattern (WAJIB DIIKUTI)

### ❌ SELECT * — Dilarang Keras
```sql
-- ❌ DILARANG
SELECT * FROM kesiswaan.nama_tabel;

-- ✅ WAJIB: Pilih kolom secara eksplisit
SELECT t.id, t.tenant_id, t.kolom1, t.kolom2
FROM kesiswaan.nama_tabel t
WHERE t.tenant_id = $1;
```

### ❌ Query Tanpa tenant_id Filter
```sql
-- ❌ DILARANG: Cross-tenant data leak!
SELECT id, nama FROM kesiswaan.nama_tabel;

-- ✅ WAJIB: Selalu filter tenant_id
SELECT t.id, t.nama
FROM kesiswaan.nama_tabel t
WHERE t.tenant_id = $1;  -- $1 = ID sekolah yang sedang login
```

### ❌ N+1 Query Problem
Jangan melakukan query di dalam loop. Gunakan JOIN atau Eager Loading.

```php
// ❌ DILARANG — N+1 problem: query di dalam loop
// $items = Query(...)->get();
// foreach ($items as $item) { $detail = Query(item_id)->first(); }

// ✅ WAJIB — Gunakan JOIN atau Eager Loading:
// DB::table('kesiswaan.nama_tabel AS t')
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
