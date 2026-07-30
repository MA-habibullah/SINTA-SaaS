# 🤖 AGENT GUIDE: Schema `akademik`
> **Panduan Pembuatan Program** — Sinta SaaS PostgreSQL v21

---

## 1. Identitas Modul

| Property | Value |
|----------|-------|
| **Schema** | `akademik` |
| **Icon** | 📚 |
| **Judul** | Akademik — Kurikulum, Rombel & Penilaian |
| **Stack** | PHP 8.2 | Laravel 11 | PostgreSQL | Queue (PDF rapor) | Redis (cache nilai) |
| **Jumlah Tabel** | 20 tabel |

## 2. Deskripsi Modul

Schema inti akademik: Tahun Ajaran, Rombel, Mata Pelajaran, Penugasan Guru, Nilai Rapor K13/Merdeka.

## 3. Service Classes yang Perlu Dibuat

- `TahunAjaranService`
- `RombelService`
- `MataPelajaranService`
- `PenugasanGuruService`
- `NilaiRaporService`
- `RaporPrinter`

## 4. Key Architectural Patterns

- Composite PK pada nilai_rapor: (siswa_id, mapel_id, rombel_id, semester)
- Cache Redis untuk nilai yang sudah dicetak (immutable)
- Lock rapor per tahun ajaran (is_locked) mencegah edit setelah cetak
- Kurikulum Merdeka: value 0–10 float vs K13: 0–100 integer

## 5. Daftar Tabel (20 tabel)

| Nama Tabel | Deskripsi | Kolom Utama |
|------------|-----------|-------------|
| `tahun_ajaran` | Tahun ajaran. | `id, tenant_id, tahun_ajaran, is_active, created_at...` |
| `angkatan` | Angkatan. | `id, tenant_id, tahun_angkatan, is_active, created_at...` |
| `pendidikan` | Pendidikan. | `id, tenant_id, kode_pendidikan, nama_pendidikan, is_active...` |
| `ref_kurikulum` | Kurikulum. | `id, tenant_id, nama_kurikulum, tipe_penilaian, is_active...` |
| `jurusan` | Jurusan. | `id, tenant_id, kode_jurusan, nama_jurusan, is_active...` |
| `kelas` | Kelas. | `id, tenant_id, id_jenjang, id_jurusan, kode_kelas...` |
| `kelas_kurikulum` | Kelas Kurikulum. | `id, tenant_id, kelas_id, tahun_ajaran, kurikulum_id...` |
| `mata_pelajaran` | Mapel. | `id, tenant_id, kode_mapel, nama_mapel, is_active...` |
| `pemetaan_mapel` | Pemetaan Mapel | `id, tenant_id, tahun_ajaran, semester, kelas_id...` |
| `kunci_akademik` | Lock Nilai. | `id, tenant_id, tahun_ajaran, semester, is_locked_kurikulum...` |
| `detail_nilai_rapor` | Nilai Rapor. | `id, tenant_id, siswa_id, kelas_id, tahun_ajaran...` |
| `log_nilai_rapor` | Log Nilai. | `id, tenant_id, user_id, siswa_id, mapel_id...` |
| `nilai_sikap_k13` | Sikap K13. | `id, tenant_id, siswa_id, tahun_ajaran, semester...` |
| `nilai_p5` | P5 Merdeka. | `id, tenant_id, siswa_id, tahun_ajaran_id, semester...` |
| `nilai_ujian_sekolah` | Ujian Sekolah. | `id, tenant_id, siswa_id, mata_pelajaran_id, nilai_ujian...` |
| `catatan_wali_kelas` | Catatan Wali Kelas. | `id, tenant_id, siswa_id, tahun_ajaran_id, semester...` |
| `semester` | Master Semester Akademik Dapodik & e-Rapor SP - Mengatur periode semester aktif  | `id, tenant_id, tahun_ajaran_id, nama_semester, semester...` |
| `penugasan_mengajar` | Pembagian Tugas Mengajar Guru PTK per Rombel & Mapel (Dapodik Pembelajaran) - Me | `id, tenant_id, semester_id, guru_ptk_id, rombongan_belajar_id...` |
| `nilai_sumatif` | Tabel Nilai Sumatif e-Rapor SP (Kurikulum Merdeka) - Nilai Sumatif Lingkup Mater | `id, tenant_id, semester_id, rombongan_belajar_id, mata_pelajaran_id...` |
| `target_capaian_tp` | Tujuan Pembelajaran (TP) / Alur TP Kurikulum Merdeka e-Rapor SP - Menjadi dasar  | `id, tenant_id, mata_pelajaran_id, tingkat_kelas, kode_tp...` |


## 6. Critical JOIN Relationships

| Source Table | References | Via Column | Keterangan |
|-------------|-----------|-----------|-----------|
| `akademik.nilai_rapor` | `siswa.siswa` | `siswa_id` | CORE: Nilai → Siswa |
| `akademik.nilai_rapor` | `rombel` | `rombel_id` | CORE: Nilai → Rombel (kelas) |
| `akademik.penugasan_guru` | `kepegawaian.ptk` | `ptk_id` | GURU: Penugasan → PTK/Guru |
| `akademik.rombel` | `tahun_ajaran` | `tahun_ajaran_id` | TAHUN: Rombel → Tahun Ajaran aktif |


## 7. Panduan Anti-Pattern (WAJIB DIIKUTI)

### ❌ SELECT * — Dilarang Keras
```sql
-- ❌ DILARANG
SELECT * FROM akademik.nama_tabel;

-- ✅ WAJIB: Pilih kolom secara eksplisit
SELECT t.id, t.tenant_id, t.kolom1, t.kolom2
FROM akademik.nama_tabel t
WHERE t.tenant_id = $1;
```

### ❌ Query Tanpa tenant_id Filter
```sql
-- ❌ DILARANG: Cross-tenant data leak!
SELECT id, nama FROM akademik.nama_tabel;

-- ✅ WAJIB: Selalu filter tenant_id
SELECT t.id, t.nama
FROM akademik.nama_tabel t
WHERE t.tenant_id = $1;  -- $1 = ID sekolah yang sedang login
```

### ❌ N+1 Query Problem
Jangan melakukan query di dalam loop. Gunakan JOIN atau Eager Loading.

```php
// ❌ DILARANG — N+1 problem: query di dalam loop
// $items = Query(...)->get();
// foreach ($items as $item) { $detail = Query(item_id)->first(); }

// ✅ WAJIB — Gunakan JOIN atau Eager Loading:
// DB::table('akademik.nama_tabel AS t')
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
