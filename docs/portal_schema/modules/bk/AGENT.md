# 🤖 AGENT GUIDE: Schema `bk`
> **Panduan Pembuatan Program** — Sinta SaaS PostgreSQL v21

---

## 1. Identitas Modul

| Property | Value |
|----------|-------|
| **Schema** | `bk` |
| **Icon** | 💬 |
| **Judul** | BK — Bimbingan Konseling |
| **Stack** | PHP 8.2 | Laravel 11 | PostgreSQL | Encryption (data konseling sensitif) |
| **Jumlah Tabel** | 8 tabel |

## 2. Deskripsi Modul

Layanan Bimbingan Konseling siswa: sesi konseling, pemantauan risiko, log penanganan, dan laporan pembinaan.

## 3. Service Classes yang Perlu Dibuat

- `KonselingService`
- `MonitoringRisikoService`
- `LaporanBKService`

## 4. Key Architectural Patterns

- Data konseling SANGAT SENSITIF: enkripsi kolom catatan_konseling di DB
- Role-based: hanya Guru BK yang assign ke siswa bisa lihat record
- Monitoring risiko: scoring kerawanan (akademik, sosial, ekonomi)
- Audit log wajib pada setiap akses record konseling (GDPR-like)

## 5. Daftar Tabel (8 tabel)

| Nama Tabel | Deskripsi | Kolom Utama |
|------------|-----------|-------------|
| `pilihan_penjurusan` | Penjurusan | `id, siswa_id, tenant_id, id_jurusan, id_tahun_ajaran...` |
| `master_jalur_masuk` | Jalur PPDB. | `id, tenant_id, nama_jalur, kategori, created_at...` |
| `catatan_bk` | UNIFIED CATATAN BK | `id, siswa_id, tenant_id, id_guru_bk, tanggal_konseling...` |
| `master_pelanggaran` | Pelanggaran. | `id, tenant_id, kategori, nama_pelanggaran, bobot_poin...` |
| `pelanggaran_siswa` | Catatan Pelanggaran Kedisiplinan Siswa & Poin Sanksi BK - Tanggal, Jenis Pelangg | `id, tenant_id, siswa_id, tgl_pelanggaran, master_pelanggaran_id...` |
| `tindak_lanjut_sanksi` | Sanksi. | `id, tenant_id, siswa_id, tahun_ajaran_id, tanggal_tindakan...` |
| `pembinaan_monitoring` | UNIFIED PEMBINAAN | `id, tenant_id, siswa_id, guru_bk_id, tanggal_bimbingan...` |
| `sesi_mentoring` | Mentoring. | `id, tenant_id, monitoring_id, kepsek_id, tanggal_sesi...` |


## 6. Critical JOIN Relationships

| Source Table | References | Via Column | Keterangan |
|-------------|-----------|-----------|-----------|
| `bk.sesi_konseling` | `siswa.siswa` | `siswa_id` | BK: Sesi → Siswa |
| `bk.sesi_konseling` | `kepegawaian.ptk` | `guru_bk_id` | BK: Sesi → Guru BK |
| `bk.monitoring_risiko` | `bk.kategorisasi_masalah` | `kategori_id` | RISIKO: Monitor → Kategori |


## 7. Panduan Anti-Pattern (WAJIB DIIKUTI)

### ❌ SELECT * — Dilarang Keras
```sql
-- ❌ DILARANG
SELECT * FROM bk.nama_tabel;

-- ✅ WAJIB: Pilih kolom secara eksplisit
SELECT t.id, t.tenant_id, t.kolom1, t.kolom2
FROM bk.nama_tabel t
WHERE t.tenant_id = $1;
```

### ❌ Query Tanpa tenant_id Filter
```sql
-- ❌ DILARANG: Cross-tenant data leak!
SELECT id, nama FROM bk.nama_tabel;

-- ✅ WAJIB: Selalu filter tenant_id
SELECT t.id, t.nama
FROM bk.nama_tabel t
WHERE t.tenant_id = $1;  -- $1 = ID sekolah yang sedang login
```

### ❌ N+1 Query Problem
Jangan melakukan query di dalam loop. Gunakan JOIN atau Eager Loading.

```php
// ❌ DILARANG — N+1 problem: query di dalam loop
// $items = Query(...)->get();
// foreach ($items as $item) { $detail = Query(item_id)->first(); }

// ✅ WAJIB — Gunakan JOIN atau Eager Loading:
// DB::table('bk.nama_tabel AS t')
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
