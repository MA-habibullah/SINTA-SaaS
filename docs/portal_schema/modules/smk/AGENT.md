# 🤖 AGENT GUIDE: Schema `smk`
> **Panduan Pembuatan Program** — Sinta SaaS PostgreSQL v21

---

## 1. Identitas Modul

| Property | Value |
|----------|-------|
| **Schema** | `smk` |
| **Icon** | 🛠️ |
| **Judul** | SMK — PKL, UKK & Tracer BKK |
| **Stack** | PHP 8.2 | Laravel 11 | PostgreSQL | Mobile App (logbook PKL) | Queue (reminder laporan) |
| **Jumlah Tabel** | 5 tabel |

## 2. Deskripsi Modul

Modul khusus SMK: Mitra Industri DUDI, Penempatan PKL/Prakerin, Logbook Harian, dan Uji Kompetensi Keahlian (UKK).

## 3. Service Classes yang Perlu Dibuat

- `DUDIService`
- `PKLService`
- `LogbookService`
- `UKKService`
- `BKKService`

## 4. Key Architectural Patterns

- PKL: siswa ditempatkan di DUDI, guru pembimbing memantau via logbook digital
- Logbook harian PKL diisi siswa via mobile app, dikonfirmasi pembimbing industri
- UKK BNSP: nilai uji kompetensi per elemen skema sertifikasi
- BKK: tracking penempatan kerja alumni SMK di database DUDI partner

## 5. Daftar Tabel (5 tabel)

| Nama Tabel | Deskripsi | Kolom Utama |
|------------|-----------|-------------|
| `mitra_dudi` | Master Mitra DUDI / Dunia Kerja / Industri - Perusahaan Tempat PKL/Prakerin & Ke | `id, tenant_id, nama_perusahaan_dudi, bidang_usaha, alamat_lengkap...` |
| `pkl_penempatan` | Tabel Penempatan PKL / Prakerin Siswa SMK di Mitra DUDI - Guru Pembimbing Sekola | `id, tenant_id, siswa_id, mitra_dudi_id, tahun_ajaran_id...` |
| `pkl_jurnal_harian` | Logbook Jurnal Kegiatan Harian PKL Siswa di Industri - Tanggal, Uraian Pekerjaan | `id, tenant_id, pkl_penempatan_id, tgl_jurnal, jam_mulai...` |
| `pkl_penilaian` | Penilaian Akhir PKL & Sertifikat Prakerin DUDI - Nilai Keahlian Teknis, Softskil | `id, tenant_id, pkl_penempatan_id, nilai_aspek_teknis_keahlian, nilai_aspek_softskill_disiplin...` |
| `ukk_penilaian` | Uji Kompetensi Keahlian (UKK) / Sertifikasi LSP-P1/P2 BNSP SMK - Skema Keahlian, | `id, tenant_id, siswa_id, tahun_ajaran_id, skema_sertifikasi_nama...` |


## 6. Critical JOIN Relationships

| Source Table | References | Via Column | Keterangan |
|-------------|-----------|-----------|-----------|
| `smk.pkl_penempatan` | `smk.mitra_industri` | `dudi_id` | PKL: Penempatan → DUDI/Industri |
| `smk.pkl_penempatan` | `siswa.siswa` | `siswa_id` | PKL: Penempatan → Siswa SMK |
| `smk.pkl_logbook` | `smk.pkl_penempatan` | `penempatan_id` | LOGBOOK: Entri → Penempatan PKL |
| `smk.ukk_hasil` | `siswa.siswa` | `siswa_id` | UKK: Nilai Kompetensi → Siswa |


## 7. Panduan Anti-Pattern (WAJIB DIIKUTI)

### ❌ SELECT * — Dilarang Keras
```sql
-- ❌ DILARANG
SELECT * FROM smk.nama_tabel;

-- ✅ WAJIB: Pilih kolom secara eksplisit
SELECT t.id, t.tenant_id, t.kolom1, t.kolom2
FROM smk.nama_tabel t
WHERE t.tenant_id = $1;
```

### ❌ Query Tanpa tenant_id Filter
```sql
-- ❌ DILARANG: Cross-tenant data leak!
SELECT id, nama FROM smk.nama_tabel;

-- ✅ WAJIB: Selalu filter tenant_id
SELECT t.id, t.nama
FROM smk.nama_tabel t
WHERE t.tenant_id = $1;  -- $1 = ID sekolah yang sedang login
```

### ❌ N+1 Query Problem
Jangan melakukan query di dalam loop. Gunakan JOIN atau Eager Loading.

```php
// ❌ DILARANG — N+1 problem: query di dalam loop
// $items = Query(...)->get();
// foreach ($items as $item) { $detail = Query(item_id)->first(); }

// ✅ WAJIB — Gunakan JOIN atau Eager Loading:
// DB::table('smk.nama_tabel AS t')
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
