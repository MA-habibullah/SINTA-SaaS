# Walkthrough Harian — 2026-07-15

---
## Pengarsipan Buku Induk & PDSS SNBP
**Jenis**: Feature

Implementasi fitur penguncian nilai, audit log, cetak rapor massal, dan ekspor PDSS SNBP.

**Fitur yang diselesaikan:**
1. **Sistem Penguncian Nilai Tingkat Rombel** — Admin/operator dapat mengunci input nilai rapor per kelas per tahun ajaran. Backend menolak otomatis request save/delete saat `is_locked = 1`
2. **Audit Log Nilai Rapor** — Setiap perubahan nilai merekam histori di tabel `log_nilai_rapor` (user pengubah, nilai lama, nilai baru, tipe aksi INSERT/UPDATE/DELETE)
3. **Cetak Rapor Rombel Massal (Bulk Print)** — Cetak massal per semester + identitas seluruh siswa satu rombel dalam satu klik: `views/print_rapot_bulk_merdeka.php`, `print_rapot_bulk_k13.php`, `print_rapot_bulk_ktsp.php` [SEMUA BARU]
4. **Ekspor Data Format PDSS SNBP** — Nilai rapor semester 1-5 kelas XII ke file Excel (.xlsx) sesuai format portal SNBP Kemendikbudristek

**File dimodifikasi:** `app/Controllers/NilaiRaporController.php`, `app/Controllers/BukuIndukController.php`, `views/buku_induk.php`

**Verifikasi:** `scratch/test_pengarsipan_dan_lock.php` — SUKSES 100%

---
## Fitur Simulasi Pemilihan Kampus & Prodi PDSS
**Jenis**: Feature

Implementasi fitur Simulasi Pemilihan Kampus & Prodi untuk modul PDSS dengan deteksi konflik real-time.

**Perubahan:**
- `database/migrations/2026_07_15_00_create_pdss_simulasi_tables.php` [BARU] — Tabel `pdss_simulasi` dan `pdss_simulasi_setting` (multi-year)
- `app/Controllers/PDSSController.php` [MODIFY] — 7 endpoint API: apiGetSimulasiSetting, apiToggleSimulasiSetting, apiGetSimulasi, apiSaveSimulasi, apiDeleteSimulasi, apiUploadBuktiSimulasi, apiExportSimulasi
- `index.php` [MODIFY] — Route `/api/v1/pdss/simulasi/`
- `views/pdss_index.php` [MODIFY] — Tab "Simulasi Pilihan Kampus" + lazy-load Vue watcher
- `views/bk/pdss_simulasi_ui.php` [BARU] — UI modern: stats cards, selektor tahun ajaran, kontrol fase simulasi, tabel dengan badge Konflik + tooltip, modal form & uploader bukti

**Verifikasi:** php -l semua file = No syntax errors. Migrasi database sukses.

---
## Scraper Daya Tampung & Historis Peminat PTN SNBP (snpmb.id)
**Jenis**: Feature / Data Seeding

Scraper CLI untuk menarik data PTN, Prodi, dan Historis Peminat dari SNPMB sebagai dataset awal SINTA-SaaS.

**Perubahan:**
- `scratch/scrape_snpmb_data.php` [BARU] — Script scraper dengan rate limit 200ms. Hasil: 146 PTN, 5.142 Prodi, 21.456 historis peminat
- File CSV dipindahkan ke `database/seeds/` (snpmb_snbp_ptn.csv, snpmb_snbp_prodi.csv, snpmb_snbp_historis_peminat.csv)
- `database/migrations/2026_07_15_01_seed_snpmb_snbp_data.php` [BARU] — Seeder batch-insert ke database untuk semua tenant aktif, dijalankan otomatis saat `php migrate.php`
