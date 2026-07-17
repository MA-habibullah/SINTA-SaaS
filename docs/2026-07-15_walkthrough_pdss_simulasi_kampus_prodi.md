# Walkthrough: Fitur Simulasi Pemilihan Kampus & Prodi PDSS

Saya telah mengimplementasikan seluruh kebutuhan fitur Simulasi Pemilihan Kampus & Prodi untuk modul PDSS sesuai dengan rencana implementasi yang disetujui.

## Perubahan yang Dilakukan

### 1. Database Migration
- **[NEW] [2026_07_15_00_create_pdss_simulasi_tables.php](file:///c:/xampp/htdocs/SINTA-SaaS/database/migrations/2026_07_15_00_create_pdss_simulasi_tables.php)**:
  - Membuat tabel `pdss_simulasi` untuk mencatat pilihan 1 & 2 kampus-prodi siswa, catatan, status pengisian, dan path file bukti.
  - Membuat tabel `pdss_simulasi_setting` untuk mencatat status buka/tutup/kunci dari tiap fase simulasi (1, 2, atau 3) secara multi-year (per tahun ajaran).

### 2. Backend (API Controller)
- **[MODIFY] [PDSSController.php](file:///c:/xampp/htdocs/SINTA-SaaS/app/Controllers/PDSSController.php)**:
  - `apiGetSimulasiSetting()`: Mengambil konfigurasi aktif (buka/tutup/kunci) fase simulasi 1, 2, 3.
  - `apiToggleSimulasiSetting()`: Mengubah status fase simulasi oleh Guru BK/Operator (termasuk validasi berurutan/sequential lock).
  - `apiGetSimulasi()`: Mengambil daftar siswa eligible, rata-rata nilai, peringkat eligible per jurusan, pilihan prodi/kampus, dan **analisis deteksi konflik prodi secara real-time**.
  - `apiSaveSimulasi()`: Menyimpan pilihan simulasi (pilihan 1 & 2) dengan live check konflik. Jika ada konflik prodi dengan siswa berperingkat lebih tinggi, warning dikembalikan tetapi penyimpanan tetap dilanjutkan.
  - `apiDeleteSimulasi()`: Menghapus pilihan simulasi siswa.
  - `apiUploadBuktiSimulasi()`: Mengunggah file bukti PDF/PNG/JPG (maksimal 2MB) untuk Simulasi 3.
  - `apiExportSimulasi()`: Mengekspor rekap simulasi aktif ke file CSV dengan encoding UTF-8 BOM agar mudah dibuka di Microsoft Excel.

### 3. Routing
- **[MODIFY] [index.php](file:///c:/xampp/htdocs/SINTA-SaaS/index.php)**:
  - Mendaftarkan endpoint API baru di `/api/v1/pdss/simulasi/*` ke method controller yang sesuai.

### 4. Frontend & User Interface
- **[MODIFY] [pdss_index.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/pdss_index.php)**:
  - Menambahkan tab navigasi "Simulasi Pilihan Kampus".
  - Mengintegrasikan data Vue reaktif dan watcher `activeTab` untuk memuat data simulasi secara lazy-load.
- **[NEW] [pdss_simulasi_ui.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/bk/pdss_simulasi_ui.php)**:
  - Antarmuka modern dengan stats cards (Total, Sudah Isi, Belum Isi, Konflik).
  - Selektor Tahun Ajaran Evaluasi untuk isolasi data multi-year.
  - Kontrol fase simulasi (Buka Pengisian, Tutup, Kunci) untuk BK.
  - Tabel interaktif dengan badge `⚠ Konflik` (dilengkapi tooltip info penemu pertama yang berperingkat lebih tinggi) dan tautan file bukti pendaftaran.
  - Modal form interaktif untuk pengisian pilihan kampus & prodi secara cepat.
  - Modal file uploader dengan validasi tipe dan batas ukuran file bukti.

---

## Hasil Pengujian & Verifikasi Sintaks
- Semua file PHP lulus verifikasi sintaks (`php -l` bebas dari error).
- Proses migrasi database dijalankan sukses dan tabel telah terbuat di database lokal.
