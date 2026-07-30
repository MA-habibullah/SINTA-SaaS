# 🤖 AGENT GUIDE: Schema `kepegawaian`
> **Panduan Pembuatan Program** — Sinta SaaS PostgreSQL v21

---

## 1. Identitas Modul

| Property | Value |
|----------|-------|
| **Schema** | `kepegawaian` |
| **Icon** | 👔 |
| **Judul** | Kepegawaian — PTK, Dokumen Digital & Rekrutmen |
| **Stack** | PHP 8.2 | Laravel 11 | PostgreSQL | S3 Storage (berkas KTP/Ijazah) | Queue (notif pelamar) |
| **Jumlah Tabel** | 10 tabel |

## 2. Deskripsi Modul

Manajemen PTK/Guru: biodata, kepangkatan, sertifikasi, diklat, upload berkas digital, dan portal rekrutmen guru baru.

## 3. Service Classes yang Perlu Dibuat

- `PTKService`
- `DokumenPTKService`
- `KepangkatanService`
- `RekrutmenService`
- `SeleksiService`

## 4. Key Architectural Patterns

- Berkas digital guru (KTP, Ijazah) disimpan di S3 bucket private, URL presigned
- Portal rekrutmen publik: pelamar tidak perlu login untuk submit lamaran
- Status seleksi: terkirim → seleksi_berkas → wawancara → microteaching → keputusan
- Integrasi dengan core.users: PTK yang aktif punya akun user di sistem

## 5. Daftar Tabel (10 tabel)

| Nama Tabel | Deskripsi | Kolom Utama |
|------------|-----------|-------------|
| `ptk_identitas` | Master Identitas Pendidik & Tenaga Kependidikan (GTK / Guru / Staf) - Status Peg | `id, tenant_id, user_id, nik, nip...` |
| `riwayat_kepangkatan` | Historis Pangkat, Golongan, TMT & SK Kepegawaian PNS / PPPK Guru & Tendik. | `id, tenant_id, ptk_id, pangkat_golongan, nomor_sk...` |
| `sertifikasi_ptk` | Sertifikasi Pendidik (Serdik), NRG (Nomor Registrasi Guru), Bidang Studi & Lemba | `id, tenant_id, ptk_id, jenis_sertifikasi, nomor_sertifikat...` |
| `diklat_pelatihan` | Diklat, Bimtek, Workshop, Pelatihan Kurikulum, & Jam JP Pengembangan Diri Guru P | `id, tenant_id, ptk_id, nama_diklat, penyelenggara...` |
| `riwayat_pendidikan_ptk` | Riwayat Pendidikan Formal S1 / S2 / S3, Perguruan Tinggi, Gelar Akademik & IPK G | `id, tenant_id, ptk_id, jenjang_pendidikan, gelar_akademik...` |
| `riwayat_kepala_sekolah` | Historis SK Penugasan & Periode Jabatan Kepala Sekolah (Kepsek). | `id, tenant_id, ptk_id, nama_kepsek, nip_kepsek...` |
| `dokumen_ptk` | Arsip Dokumen & Berkas Digital PTK/Guru (KTP, KK, Ijazah Terakhir, Transkrip, SK | `id, tenant_id, ptk_id, jenis_dokumen, nama_dokumen...` |
| `lowongan_kerja` | Master Informasi Lowongan Kerja & Formasi Penerimaan Guru / Tenaga Kependidikan  | `id, tenant_id, judul_lowongan, kode_lowongan, posisi_jabatan...` |
| `pelamar_kerja` | Data Diri & Upload Berkas Pelamar Kerja Guru (NIK, KTP, KK, Ijazah, CV, Nilai IP | `id, tenant_id, lowongan_id, nik, nama_lengkap...` |
| `tahapan_seleksi_pelamar` | Jadwal & Penilaian Tahapan Seleksi Pelamar Guru (Administrasi, Wawancara, Microt | `id, tenant_id, pelamar_id, tahapan, jadwal_pelaksanaan...` |


## 6. Critical JOIN Relationships

| Source Table | References | Via Column | Keterangan |
|-------------|-----------|-----------|-----------|
| `kepegawaian.ptk_identitas` | `core.users` | `user_id` | AUTH: PTK → User akun sistem |
| `kepegawaian.ptk_identitas` | `core.wilayah` | `kab_id` | WILAYAH: PTK → Kabupaten domisili |
| `kepegawaian.dokumen_ptk` | `kepegawaian.ptk_identitas` | `ptk_id` | BERKAS: Dokumen → Data PTK |
| `kepegawaian.pelamar_kerja` | `kepegawaian.lowongan_kerja` | `lowongan_id` | REKRUT: Pelamar → Lowongan |


## 7. Panduan Anti-Pattern (WAJIB DIIKUTI)

### ❌ SELECT * — Dilarang Keras
```sql
-- ❌ DILARANG
SELECT * FROM kepegawaian.nama_tabel;

-- ✅ WAJIB: Pilih kolom secara eksplisit
SELECT t.id, t.tenant_id, t.kolom1, t.kolom2
FROM kepegawaian.nama_tabel t
WHERE t.tenant_id = $1;
```

### ❌ Query Tanpa tenant_id Filter
```sql
-- ❌ DILARANG: Cross-tenant data leak!
SELECT id, nama FROM kepegawaian.nama_tabel;

-- ✅ WAJIB: Selalu filter tenant_id
SELECT t.id, t.nama
FROM kepegawaian.nama_tabel t
WHERE t.tenant_id = $1;  -- $1 = ID sekolah yang sedang login
```

### ❌ N+1 Query Problem
Jangan melakukan query di dalam loop. Gunakan JOIN atau Eager Loading.

```php
// ❌ DILARANG — N+1 problem: query di dalam loop
// $items = Query(...)->get();
// foreach ($items as $item) { $detail = Query(item_id)->first(); }

// ✅ WAJIB — Gunakan JOIN atau Eager Loading:
// DB::table('kepegawaian.nama_tabel AS t')
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
