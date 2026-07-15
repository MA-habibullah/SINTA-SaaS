# Rencana Implementasi: Scraper Daya Tampung & Historis Peminat PTN SNBP (snpmb.id)

## Latar Belakang

Pihak sekolah membutuhkan data daya tampung dan historis peminat seluruh Perguruan Tinggi Negeri (PTN) di Indonesia dari website resmi SNPMB pada halaman SNBP (`https://snpmb.id/snbp/daya-tampung-snbp`). Data ini sangat krusial untuk melengkapi basis data modul Master Kampus & Prodi serta memberikan rekomendasi akurat kepada Guru BK/Siswa dalam menentukan simulasi pemilihan prodi SNBP.

---

## Analisis Arsitektur Website SNPMB (SNBP)

Halaman daya tampung SNBP memanggil API proxy internal untuk merender datanya secara asinkron:
1. **Daftar PTN (SNBP):** `https://snpmb.id/proxy-ptn-sn.php`
2. **Daftar Prodi & Historis per PTN (SNBP):** `https://snpmb.id/proxy-prodi-sn.php?ptn={id_ptn}`

Setiap record program studi dari API tersebut sudah memuat array nested `history_daya_tampung` (peminat, daya tampung, dan jumlah yang diterima per tahun untuk 5 tahun terakhir).

### Keuntungan Pendekatan API Scraper:
- **100% Akurat:** Data diambil langsung dari JSON terstruktur, bebas dari risiko parsing HTML/DOM yang ringkih.
- **Sangat Cepat:** Tidak memerlukan rendering browser (seperti Selenium/Puppeteer) atau click loop, sehingga proses scrape dapat selesai dalam hitungan detik.
- **Relasional secara Alami:** Data JSON yang dikembalikan sudah berelasi berdasarkan ID PTN dan Kode Prodi.

---

## Proposed Changes

### 1. Script Scraper (PHP CLI)

#### [NEW] [scrape_snpmb_data.php](file:///c:/xampp/htdocs/SINTA-SaaS/scratch/scrape_snpmb_data.php)

Saya akan membuat script scraper mandiri dalam bahasa PHP (menyesuaikan dengan ekosistem SINTA-SaaS) di dalam direktori `scratch/` agar sesuai dengan aturan `AGENTS.md`.

**Spesifikasi Alur Kerja Scraper:**
1. **Fetch PTN List:** Memanggil `https://snpmb.id/proxy-ptn-sn.php` untuk mendapatkan daftar 146+ PTN (Akademik, Vokasi, dan PTKIN).
2. **Fetch Prodi per PTN:** Melakukan perulangan (loop) untuk setiap PTN secara berurutan dengan jeda kecil (rate-limiting ~200ms) untuk mencegah pemblokiran IP oleh Cloudflare/WAF SNPMB.
3. **Parse & Structure:**
   - Menghasilkan 3 tabel berelasi:
     - **Tabel PTN:** `id_ptn`, `kode_ptn`, `nama_ptn`, `web`, `jenis` (Akademik/Vokasi/PTKIN), `alamat`, `provinsi`.
     - **Tabel Prodi:** `id_prodi`, `kode_prodi`, `id_ptn`, `nama_prodi`, `jenjang` (S1/D3/D4), `daya_tampung_sekarang`, `jenis_portofolio`.
     - **Tabel Historis:** `id_historis` (auto), `id_prodi`, `kode_prodi`, `tahun`, `daya_tampung`, `peminat`, `diterima`, `keketatan`.
4. **Format Output:**
   - Menyimpan data dalam format **JSON Bersarang (PTN -> Prodi -> Historis)** di file `scratch/snpmb_snbp_data_nested.json`.
   - Mengekspor data dalam 3 file **CSV Relasional** agar mudah diimpor ke database SQL:
     - `scratch/snpmb_snbp_ptn.csv`
     - `scratch/snpmb_snbp_prodi.csv`
     - `scratch/snpmb_snbp_historis_peminat.csv`

---

## Verification Plan

### Run Scraper
Jalankan script scraper melalui command line:
```powershell
php scratch/scrape_snpmb_data.php
```

### Manual Verification
1. Periksa file `scratch/snpmb_snbp_data_nested.json` dan pastikan data bersarang terisi lengkap.
2. Buka file CSV `scratch/snpmb_snbp_ptn.csv`, `scratch/snpmb_snbp_prodi.csv`, dan `scratch/snpmb_snbp_historis_peminat.csv` menggunakan Microsoft Excel atau text editor.
3. Verifikasi jumlah PTN yang terekstrak (seharusnya berkisar di angka 146+ PTN).
4. Verifikasi keberadaan kolom daya tampung, jumlah peminat, dan jumlah diterima pada data historis.
