# 🤖 AGENT GUIDE: Schema `siswa`
> **Panduan Pembuatan Program** — Sinta SaaS PostgreSQL v21

---

## 1. Identitas Modul

| Property | Value |
|----------|-------|
| **Schema** | `siswa` |
| **Icon** | 🎓 |
| **Judul** | Siswa — Data Peserta Didik (Consolidated) |
| **Stack** | PHP 8.2 | Laravel 11 | PostgreSQL | Dapodik API Sync |
| **Jumlah Tabel** | 9 tabel |

## 2. Deskripsi Modul

Master identitas siswa yang dikonsolidasi dari Dapodik & e-Rapor. Meliputi biodata, orang tua/wali, riwayat sekolah.

## 3. Service Classes yang Perlu Dibuat

- `SiswaService`
- `OrangTuaService`
- `RiwayatSekolahService`
- `DapodikSyncService`

## 4. Key Architectural Patterns

- NIS + NISN sebagai identifier utama (UNIQUE per tenant)
- Sinkronisasi dua arah dengan Dapodik via API scheduled job
- Foto profil siswa disimpan di S3, referensi path di DB
- Soft delete siswa (kolom deleted_at) untuk riwayat kelulusan

## 5. Daftar Tabel (9 tabel)

| Nama Tabel | Deskripsi | Kolom Utama |
|------------|-----------|-------------|
| `siswa` | UNIFIED MASTER SISWA | `id, tenant_id, user_id, nisn, nis...` |
| `fisik_kesehatan_siswa` | Data Fisik & Kesehatan Periodik Siswa (Dapodik) - Tinggi badan, berat badan, lin | `id, tenant_id, siswa_id, tinggi_badan_cm, berat_badan_kg...` |
| `orang_tua` | Data Ortu. | `id_orang_tua, siswa_id, id_tempat_lahir_ayah, nik_ayah, nama_ayah...` |
| `registrasi` | Registrasi Siswa. | `id_registrasi, siswa_id, jalur_diterima, jenis_pendaftaran, tanggal_masuk...` |
| `absensi_semester` | Absensi. | `id, tenant_id, siswa_id, tahun_ajaran_id, semester...` |
| `riwayat_kenaikan_kelas` | Riwayat Kenaikan. | `id, tenant_id, siswa_id, jenis_aksi, id_kelas_asal...` |
| `riwayat_beasiswa` | Beasiswa. | `id, tenant_id, siswa_id, jenis_beasiswa, sumber...` |
| `anggota_kelas` | Anggota Rombongan Belajar (Rombel / Kelas) Dapodik & e-Rapor SP - Menghubungkan  | `id, tenant_id, semester_id, rombongan_belajar_id, siswa_id...` |
| `dokumen` | Dokumen. | `id_dokumen, siswa_id, berkas_kk, berkas_akta, berkas_ijazah_sd...` |


## 6. Critical JOIN Relationships

| Source Table | References | Via Column | Keterangan |
|-------------|-----------|-----------|-----------|
| `siswa.siswa` | `tenants` | `tenant_id` | TENANT: Siswa milik sekolah mana |
| `siswa.siswa` | `orang_tua_wali` | `id` | RELASI: Siswa ↔ Orang Tua/Wali |
| `siswa.siswa` | `rombel_siswa` | `siswa_id` | AKADEMIK: Siswa → Rombel aktif |


## 7. Panduan Anti-Pattern (WAJIB DIIKUTI)

### ❌ SELECT * — Dilarang Keras
```sql
-- ❌ DILARANG
SELECT * FROM siswa.nama_tabel;

-- ✅ WAJIB: Pilih kolom secara eksplisit
SELECT t.id, t.tenant_id, t.kolom1, t.kolom2
FROM siswa.nama_tabel t
WHERE t.tenant_id = $1;
```

### ❌ Query Tanpa tenant_id Filter
```sql
-- ❌ DILARANG: Cross-tenant data leak!
SELECT id, nama FROM siswa.nama_tabel;

-- ✅ WAJIB: Selalu filter tenant_id
SELECT t.id, t.nama
FROM siswa.nama_tabel t
WHERE t.tenant_id = $1;  -- $1 = ID sekolah yang sedang login
```

### ❌ N+1 Query Problem
Jangan melakukan query di dalam loop. Gunakan JOIN atau Eager Loading.

```php
// ❌ DILARANG — N+1 problem: query di dalam loop
// $items = Query(...)->get();
// foreach ($items as $item) { $detail = Query(item_id)->first(); }

// ✅ WAJIB — Gunakan JOIN atau Eager Loading:
// DB::table('siswa.nama_tabel AS t')
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
