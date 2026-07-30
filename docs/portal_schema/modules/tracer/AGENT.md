# 🤖 AGENT GUIDE: Schema `tracer`
> **Panduan Pembuatan Program** — Sinta SaaS PostgreSQL v21

---

## 1. Identitas Modul

| Property | Value |
|----------|-------|
| **Schema** | `tracer` |
| **Icon** | 🎯 |
| **Judul** | Tracer Study — Alumni & Karir |
| **Stack** | PHP 8.2 | Laravel 11 | PostgreSQL | Email/WA Gateway (survey reminder) |
| **Jumlah Tabel** | 5 tabel |

## 2. Deskripsi Modul

Penelusuran Karir Alumni pasca lulus. Riwayat kuliah, pekerjaan, wirausaha, dan survey BMW (Bekerja/Melanjutkan/Wirausaha).

## 3. Service Classes yang Perlu Dibuat

- `AlumniService`
- `TracerSurveyService`
- `BMWReportService`
- `CareerTrackingService`

## 4. Key Architectural Patterns

- BMW Classification: Bekerja | Melanjutkan Kuliah | Wirausaha
- Survey dikirim via WA/Email otomatis 6 bulan setelah kelulusan
- Alumni bisa update status karir mandiri via portal publik
- Laporan BKK (Bursa Kerja Khusus) untuk SMK: DUDI & penempatan kerja

## 5. Daftar Tabel (5 tabel)

| Nama Tabel | Deskripsi | Kolom Utama |
|------------|-----------|-------------|
| `tracer_study_alumni` | Tracer Study Alumni & BKK (Bursa Kerja Khusus) SMA/SMK - Penelusuran Keterserapa | `id, tenant_id, siswa_id, tahun_lulus, status_alumni_bmw...` |
| `arsip_dokumen_alumni` | Arsip alumni. | `id, siswa_id, tenant_id, jenis_dokumen, file_path...` |
| `log_akses_arsip` | Log akses. | `id, user_id, tenant_id, siswa_id, aktivitas...` |
| `riwayat_kuliah` | Detail Riwayat Penelusuran Alumni yang Melanjutkan Studi di Perguruan Tinggi / K | `id, tenant_id, siswa_id, nama_alumni, nama_kampus...` |
| `riwayat_pekerjaan` | Detail Riwayat Pekerjaan, Perusahaan & Jabatan Karir Alumni BKK (Bekerja & Wirau | `id, tenant_id, siswa_id, nama_perusahaan, posisi_jabatan...` |


## 6. Critical JOIN Relationships

| Source Table | References | Via Column | Keterangan |
|-------------|-----------|-----------|-----------|
| `tracer.tracer_alumni` | `siswa.siswa` | `siswa_id` | ALUMNI: Tracer → Siswa (yang sudah lulus) |
| `tracer.riwayat_kuliah` | `tracer.tracer_alumni` | `alumni_id` | KULIAH: Riwayat → Alumni |
| `tracer.riwayat_pekerjaan` | `tracer.tracer_alumni` | `alumni_id` | KERJA: Riwayat → Alumni |


## 7. Panduan Anti-Pattern (WAJIB DIIKUTI)

### ❌ SELECT * — Dilarang Keras
```sql
-- ❌ DILARANG
SELECT * FROM tracer.nama_tabel;

-- ✅ WAJIB: Pilih kolom secara eksplisit
SELECT t.id, t.tenant_id, t.kolom1, t.kolom2
FROM tracer.nama_tabel t
WHERE t.tenant_id = $1;
```

### ❌ Query Tanpa tenant_id Filter
```sql
-- ❌ DILARANG: Cross-tenant data leak!
SELECT id, nama FROM tracer.nama_tabel;

-- ✅ WAJIB: Selalu filter tenant_id
SELECT t.id, t.nama
FROM tracer.nama_tabel t
WHERE t.tenant_id = $1;  -- $1 = ID sekolah yang sedang login
```

### ❌ N+1 Query Problem
Jangan melakukan query di dalam loop. Gunakan JOIN atau Eager Loading.

```php
// ❌ DILARANG — N+1 problem: query di dalam loop
// $items = Query(...)->get();
// foreach ($items as $item) { $detail = Query(item_id)->first(); }

// ✅ WAJIB — Gunakan JOIN atau Eager Loading:
// DB::table('tracer.nama_tabel AS t')
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
