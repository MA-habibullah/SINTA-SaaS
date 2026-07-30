# 🤖 AGENT GUIDE: Schema `absensi`
> **Panduan Pembuatan Program** — Sinta SaaS PostgreSQL v21

---

## 1. Identitas Modul

| Property | Value |
|----------|-------|
| **Schema** | `absensi` |
| **Icon** | ⏰ |
| **Judul** | Absensi — Presensi PTK, Siswa & Geofencing GPS |
| **Stack** | PHP 8.2 | Laravel 11 | PostgreSQL | Mobile App (GPS check-in) | RFID Reader Integration |
| **Jumlah Tabel** | 8 tabel |

## 2. Deskripsi Modul

Sistem presensi digital berbasis RFID, Biometrik, dan Geofencing GPS mobile untuk siswa dan guru/PTK.

## 3. Service Classes yang Perlu Dibuat

- `PresensiSiswaService`
- `PresensiPTKService`
- `GeofencingService`
- `RekapAbsensiService`
- `IzinService`

## 4. Key Architectural Patterns

- Geofencing: validasi GPS harus dalam radius (default 100m) dari koordinat sekolah
- RFID mode: scan kartu → auto insert presensi real-time via WebSocket
- Rekap bulanan otomatis via Queue job: hitung Alpha/Izin/Sakit/Hadir per siswa
- Izin/sakit butuh upload bukti (foto surat dokter/izin orang tua)

## 5. Daftar Tabel (8 tabel)

| Nama Tabel | Deskripsi | Kolom Utama |
|------------|-----------|-------------|
| `lokasi_absensi_setting` | Master Geofencing & Setting Radius Lokasi Kantor / Sekolah - Koordinat Lat/Long  | `id, tenant_id, nama_lokasi, latitude, longitude...` |
| `jam_kerja_shift` | Master Jam Kerja & Shift PTK / Pegawai (Guru & Staf) - Jam Masuk, Jam Pulang, Ja | `id, tenant_id, nama_shift, jam_masuk, jam_pulang...` |
| `kebijakan_absensi_ptk` | Master Setting Mode Kerja PTK (WFO / WFH / WFA & Rule Validation GPS Swafoto) -  | `id, tenant_id, ptk_id, mode_kerja_default, lokasi_absensi_id...` |
| `presensi_ptk_harian` | Record Log Presensi Real-Time Harian Pegawai / Guru / Staf - Koordinat GPS Lat/L | `id, tenant_id, ptk_id, tgl_presensi, jam_kerja_shift_id...` |
| `aturan_absensi_siswa` | Master Setting Rules Absensi Siswa per Tenant (Gerbang & Notifikasi WA) - Jam Ma | `id, tenant_id, jam_masuk_gerbang, jam_pulang_gerbang, jam_akhir_tap_masuk...` |
| `presensi_siswa_harian` | Record Log Presensi Gerbang / Harian Siswa - RFID Smart Card Tap, QR Scan, Mobil | `id, tenant_id, siswa_id, rombongan_belajar_id, tgl_presensi...` |
| `presensi_siswa_kbm` | Record Presensi Siswa per Jam Pelajaran / Matapelajaran di Kelas (Jurnal KBM Kel | `id, tenant_id, jadwal_pelajaran_id, rombongan_belajar_id, mata_pelajaran_id...` |
| `pengajuan_izin_cuti` | Workflow Pengajuan Izin Harian, Izin Setengah Hari, Sakit, Cuti & Tugas Dinas Lu | `id, tenant_id, kategori_pemohon, ptk_id, siswa_id...` |


## 6. Critical JOIN Relationships

| Source Table | References | Via Column | Keterangan |
|-------------|-----------|-----------|-----------|
| `absensi.presensi_siswa` | `siswa.siswa` | `siswa_id` | PRESENSI: Absensi Siswa → Data Siswa |
| `absensi.presensi_siswa` | `absensi.lokasi_absensi_setting` | `lokasi_id` | GPS: Presensi → Setting Lokasi |
| `absensi.presensi_ptk` | `kepegawaian.ptk_identitas` | `ptk_id` | PRESENSI: Absensi Guru → PTK |
| `absensi.izin_sakit_siswa` | `absensi.presensi_siswa` | `presensi_id` | IZIN: Surat Izin → Record Presensi |


## 7. Panduan Anti-Pattern (WAJIB DIIKUTI)

### ❌ SELECT * — Dilarang Keras
```sql
-- ❌ DILARANG
SELECT * FROM absensi.nama_tabel;

-- ✅ WAJIB: Pilih kolom secara eksplisit
SELECT t.id, t.tenant_id, t.kolom1, t.kolom2
FROM absensi.nama_tabel t
WHERE t.tenant_id = $1;
```

### ❌ Query Tanpa tenant_id Filter
```sql
-- ❌ DILARANG: Cross-tenant data leak!
SELECT id, nama FROM absensi.nama_tabel;

-- ✅ WAJIB: Selalu filter tenant_id
SELECT t.id, t.nama
FROM absensi.nama_tabel t
WHERE t.tenant_id = $1;  -- $1 = ID sekolah yang sedang login
```

### ❌ N+1 Query Problem
Jangan melakukan query di dalam loop. Gunakan JOIN atau Eager Loading.

```php
// ❌ DILARANG — N+1 problem: query di dalam loop
// $items = Query(...)->get();
// foreach ($items as $item) { $detail = Query(item_id)->first(); }

// ✅ WAJIB — Gunakan JOIN atau Eager Loading:
// DB::table('absensi.nama_tabel AS t')
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
