# 🤖 AGENT GUIDE: Schema `pdss`
> **Panduan Pembuatan Program** — Sinta SaaS PostgreSQL v21

---

## 1. Identitas Modul

| Property | Value |
|----------|-------|
| **Schema** | `pdss` |
| **Icon** | 🏆 |
| **Judul** | PDSS — SNBP & Perguruan Tinggi |
| **Stack** | PHP 8.2 | Laravel 11 | PostgreSQL | Scheduled Lock (auto-lock sebelum batas SNPMB) |
| **Jumlah Tabel** | 7 tabel |

## 2. Deskripsi Modul

Pengelolaan Data Sekolah untuk Seleksi Nasional (SNBP/SNPMB). Konfigurasi Mapel, Lock Data, Siswa Eligible.

## 3. Service Classes yang Perlu Dibuat

- `PDSSConfigService`
- `EligibilityService`
- `DataLockService`
- `SNBPExportService`

## 4. Key Architectural Patterns

- Data PDSS di-LOCK sebelum deadline SNPMB — tidak bisa diubah setelah lock
- Eligible siswa: ranking nilai per mapel, min. nilai KKM, tidak tinggal kelas
- Export format XML/JSON sesuai spesifikasi SNPMB/Kemendikbud
- Mapel dikonfigurasi per sekolah berdasarkan kurikulum & peminatan

## 5. Daftar Tabel (7 tabel)

| Nama Tabel | Deskripsi | Kolom Utama |
|------------|-----------|-------------|
| `pdss_simulasi` | Simulasi SNBP. | `id, tenant_id, siswa_id, tahun_ajaran_id, no_simulasi...` |
| `target_kampus` | Target PTN. | `id, tenant_id, nama_kampus, jenis_kampus, kuota_target...` |
| `master_kampus_prodi` | UNIFIED MASTER KAMPUS | `id, kampus_id, fakultas, program_studi, jenjang...` |
| `pdss_config_mapel` | Konfigurasi Mata Pelajaran & Bobot Semester PDSS untuk Pengiriman SNPMB / SNBP. | `id, tenant_id, tahun_ajaran_id, mapel_id, sem_1...` |
| `pdss_lock` | Status Lock Pengisian & Finalisasi Nilai PDSS per Tahapan (Step 1-6). | `tenant_id, tahun_ajaran_id, step, is_locked, locked_by...` |
| `pdss_manual_eligible` | Daftar Siswa Kuota Pemeringkatan SNBP yang Diatur Manual (Override Eligible SNBP | `id, tenant_id, siswa_id, status_eligible, created_at...` |
| `pdss_simulasi_setting` | Pengaturan Parameter Simulasi Pemeringkatan PDSS (Bobot Rapor, Kuota Sesi & Wakt | `id, tenant_id, tahun_ajaran_id, no_simulasi, is_open...` |


## 6. Critical JOIN Relationships

| Source Table | References | Via Column | Keterangan |
|-------------|-----------|-----------|-----------|
| `pdss.pdss_siswa_eligible` | `siswa.siswa` | `siswa_id` | SNBP: Eligible → Siswa |
| `pdss.pdss_nilai_mapel` | `akademik.mata_pelajaran` | `mapel_id` | NILAI: PDSS → Mapel |
| `pdss.pdss_config` | `core.tenants` | `tenant_id` | CONFIG: PDSS → Sekolah |


## 7. Panduan Anti-Pattern (WAJIB DIIKUTI)

### ❌ SELECT * — Dilarang Keras
```sql
-- ❌ DILARANG
SELECT * FROM pdss.nama_tabel;

-- ✅ WAJIB: Pilih kolom secara eksplisit
SELECT t.id, t.tenant_id, t.kolom1, t.kolom2
FROM pdss.nama_tabel t
WHERE t.tenant_id = $1;
```

### ❌ Query Tanpa tenant_id Filter
```sql
-- ❌ DILARANG: Cross-tenant data leak!
SELECT id, nama FROM pdss.nama_tabel;

-- ✅ WAJIB: Selalu filter tenant_id
SELECT t.id, t.nama
FROM pdss.nama_tabel t
WHERE t.tenant_id = $1;  -- $1 = ID sekolah yang sedang login
```

### ❌ N+1 Query Problem
Jangan melakukan query di dalam loop. Gunakan JOIN atau Eager Loading.

```php
// ❌ DILARANG — N+1 problem: query di dalam loop
// $items = Query(...)->get();
// foreach ($items as $item) { $detail = Query(item_id)->first(); }

// ✅ WAJIB — Gunakan JOIN atau Eager Loading:
// DB::table('pdss.nama_tabel AS t')
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
