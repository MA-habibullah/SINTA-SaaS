# Rencana Implementasi: Restrukturisasi UI Master Kampus & Prodi

Rencana ini merangkum langkah-langkah untuk menyederhanakan antarmuka pengguna (UI) bagi Guru BK dalam mengelola Master Kampus, Program Studi, dan Riwayat Keketatan (Daya Tampung), sesuai dengan saran yang diberikan.

## User Review Required

> [!IMPORTANT]
> **Perubahan Tata Letak (Layout)**
> Tab "Master Kampus & Prodi" yang sebelumnya berada **di dalam** Pangkalan Data (PDSS) akan dikeluarkan menjadi **Menu Navtab Utama** (sejajar dengan Penjurusan Mandiri dan PDSS) di halaman Kesiapan Akademik. Apakah struktur ini sudah sesuai dengan yang Anda harapkan?

## Open Questions

> [!WARNING]
> **Format Excel untuk Import Daya Tampung**
> Untuk mempermudah update kuota, saya akan membuat format excel khusus yang berfokus pada daya tampung. Contoh kolomnya: `KODE_PRODI` | `NAMA_PRODI` | `KAMPUS` | `TAHUN` | `DAYA_TAMPUNG` | `PENDAFTAR`. 
> Jika `KODE_PRODI` kosong, sistem akan mencocokkan berdasarkan kombinasi `NAMA_PRODI` dan `KAMPUS`. Apakah Anda setuju dengan skema pencocokan ini?

## Proposed Changes

---

### 1. Navigasi Utama & Layout
Mengubah kerangka utama halaman akademik untuk mengeluarkan menu Master Kampus.

#### [MODIFY] `views/bk/akademik_layout.php`
- Menambahkan tab utama baru `Master Kampus & Prodi` di sebelah `Pangkalan Data (PDSS)`.
- Memuat file komponen `master_kampus_prodi_layout.php` secara langsung ke dalam tab tersebut.

#### [MODIFY] `views/pdss_index.php`
- Menghapus tab internal "Master Kampus & Prodi" dari antarmuka Vue PDSS karena sudah dipindahkan ke luar.

#### [NEW] `views/bk/master_kampus_prodi_layout.php`
- Membuat file UI mandiri untuk mengelola Kampus dan Prodi.
- **Desain UI Baru**: Menampilkan tabel tunggal yang **lebih datar (flat)** yang menampilkan Kampus, Prodi, serta kolom dinamis untuk Riwayat Daya Tampung (misalnya 3 tahun terakhir) secara langsung di tabel tanpa harus membuka banyak pop-up (modal).
- Menyediakan tombol **Export Excel**, **Import Excel**, dan **Hapus Kolektif**.

---

### 2. Backend API (KampusController)
Menambahkan titik akhir (endpoint) baru untuk mendukung operasi manajemen massal (bulk) pada riwayat keketatan (daya tampung).

#### [MODIFY] `app/Controllers/KampusController.php`
- `apiGetMasterKampusProdiFlat()`: Mengambil daftar kampus, prodi, dan 3 riwayat tahun terakhirnya dalam format array yang mudah di-render tabel.
- `apiExportDayaTampung()`: Menghasilkan file Excel (.xlsx) yang berisi daftar prodi beserta format tahun, daya tampung, dan pendaftar untuk diubah oleh BK.
- `apiImportDayaTampung()`: Membaca file Excel yang diunggah dan memperbarui record `kampus_prodi_riwayat`.
- `apiBulkDeleteRiwayat()`: Menghapus data riwayat keketatan secara kolektif berdasarkan kriteria "Tahun" atau "Kampus".

## Verification Plan

### Automated/Manual Verification
1. Mengakses halaman Kesiapan Akademik, memverifikasi munculnya tab "Master Kampus & Prodi" di navigasi utama.
2. Membuka tab tersebut dan memastikan tabel menampilkan data riwayat kuota (jika ada) tanpa klik tambahan.
3. Mencoba **Export Excel**, mengubah nilai daya tampung di file, lalu melakukan **Import Excel** untuk memastikan data berubah sesuai dokumen.
4. Mencoba fitur **Hapus Kolektif** (Bulk Delete) berdasarkan tahun tertentu, lalu memastikan data di tahun tersebut hilang dari database.
