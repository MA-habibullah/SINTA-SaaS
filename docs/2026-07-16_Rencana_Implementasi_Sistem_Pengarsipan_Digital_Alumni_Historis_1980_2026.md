# Rencana Implementasi: Sistem Pengarsipan Digital Alumni Historis (1980 - 2026)

Dokumen ini berisi rancangan solusi terbaik untuk mengarsipkan data siswa, nilai, serta berkas fisik ijazah/SKHUN/Buku Induk alumni dari tahun 1980 hingga 2026 ke dalam database SINTA-SaaS secara aman, ringan, dan ringkas dalam format PDF.

---

## Masalah Lapangan & Solusi Rekomendasi Terarah

Melihat kendala riil di sekolah di mana:
1. **Buku Induk Sangat Tebal & Banyak Halaman**: Setiap siswa bisa memiliki **lebih dari 5 halaman** Buku Induk fisik, ditambah halaman depan-belakang ijazah/SKHUN.
2. **Foto HP Terpisah Sangat Merepotkan**: Jika disimpan sebagai gambar-gambar terpisah, database akan sangat kotor dan operator kesulitan mengelola file satu per satu.

Kami merancang solusi spesifik berikut untuk menangani berkas multi-halaman ini secara otomatis:

### 1. Auto-Convert Images to Single PDF (Penyusunan Gambar Menjadi PDF Otomatis)
* **Alur Penggunaan**:
  1. Operator membuka halaman unggah di HP.
  2. Operator menekan tombol kamera untuk memfoto halaman 1, halaman 2, halaman 3, dst. secara berurutan. Gambar otomatis dikompresi ke WebP/JPEG ringan (~200KB) di sisi HP.
  3. Setelah selesai memfoto semua halaman, operator menekan tombol **"Simpan & Gabungkan Sebagai PDF"**.
  4. Server menerima kumpulan gambar tersebut, lalu menggunakan pustaka PHP (e.g., `FPDF` atau `TCPDF`) untuk **menggabungkan seluruh gambar secara otomatis menjadi satu file dokumen PDF tunggal**.
* **Keuntungan**:
  * Penyimpanan sangat rapi (1 siswa = 1 file PDF per jenis dokumen).
  * Ukuran file sangat efisien (kombinasi 5 halaman foto terkompresi hanya berkisar **1MB - 1.5MB** dalam satu PDF).

### 2. Direct PDF Upload (Unggah File PDF Langsung)
* Jika operator sudah memiliki file PDF hasil pindaian dari aplikasi HP eksternal (seperti CamScanner, Adobe Scan, atau mesin scan besar), UI juga menyediakan opsi unggah langsung berkas format `.pdf`.

### 3. PDF Document Viewer Premium di Browser
* Di halaman profil alumni, tab **"Brankas Berkas"** akan menampilkan peninjau (viewer) PDF terintegrasi. Operator dapat membaca, melakukan zoom, serta menggulir (*scroll*) seluruh halaman dokumen Buku Induk (5+ halaman) secara lancar langsung di dalam web browser tanpa perlu mengunduhnya terlebih dahulu.

### 4. Brankas Digital Terenkripsi (Secure Document Vault)
* Semua PDF disimpan di direktori aman `/storage/archive/` (terkunci dari akses luar). Berkas hanya bisa dibuka melalui controller pengontrol izin: `/SINTA-SaaS/api/v1/buku-induk/view-document?id=...`.

---

## Rencana Perubahan Database (Database Enhancements)

Struktur tabel disederhanakan kembali karena 1 dokumen multi-halaman cukup disimpan dalam satu kolom `file_path` berekstensi `.pdf`:

### [NEW] Tabel `arsip_dokumen_alumni`
```sql
CREATE TABLE `arsip_dokumen_alumni` (
  `id` CHAR(36) NOT NULL PRIMARY KEY,
  `siswa_id` CHAR(36) NOT NULL,
  `tenant_id` CHAR(36) NOT NULL,
  `jenis_dokumen` ENUM('Buku Induk', 'Ijazah', 'SKHUN', 'Sertifikat/SKL', 'Lainnya') NOT NULL,
  `file_path` VARCHAR(255) NOT NULL, -- Path mengarah ke file .pdf hasil kompilasi
  `file_size` INT UNSIGNED DEFAULT 0,
  `keterangan` TEXT DEFAULT NULL,
  `uploaded_by` CHAR(36) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`siswa_id`) REFERENCES `siswa`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### [NEW] Tabel `log_akses_arsip`
```sql
CREATE TABLE `log_akses_arsip` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` CHAR(36) NOT NULL,
  `tenant_id` CHAR(36) NOT NULL,
  `siswa_id` CHAR(36) NOT NULL,
  `aktivitas` VARCHAR(100) NOT NULL, -- e.g., 'View PDF Buku Induk', 'Download PDF Ijazah'
  `ip_address` VARCHAR(45) NOT NULL,
  `user_agent` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## Proposed Code Changes (Rencana Perubahan Program)

### [NEW] [arsip_alumni.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/arsip_alumni.php)
* Membuat halaman khusus manajemen Arsip Alumni. Halaman ini berisi:
  * Filter pencarian alumni berdasarkan **Tahun Kelulusan (Angkatan)** dari tahun 1980 s.d. 2026.
  * Form pengunggahan berurutan (multi-page image list) untuk kamera HP dan drag-and-drop file PDF langsung.
  * Embed PDF Viewer menggunakan tag `<iframe class="w-100 h-100">` atau PDF.js.

### [MODIFY] [BukuIndukController.php](file:///c:/xampp/htdocs/SINTA-SaaS/app/Controllers/BukuIndukController.php)
* Menambahkan fungsi `compileImagesToPdf()` menggunakan pustaka PDF untuk menggabungkan temporary images menjadi satu PDF permanen di server.
* Menambahkan fungsi `viewDocumentApi()` yang memvalidasi izin akses sebelum melakukan streaming file rahasia dari `/storage/archive/` ke browser.
* Menambahkan log penulisan ke tabel `log_akses_arsip` setiap kali berkas diunduh atau dilihat.

---

## Verification Plan

### Manual Verification
1. **Uji Coba Penggabungan Foto HP ke PDF**:
   * Ambil foto halaman 1, halaman 2, hingga halaman 6 Buku Induk melalui kamera HP di sistem. Tekan **"Simpan & Gabungkan PDF"**. Pastikan berkas yang terbuat di folder server adalah berkas berformat **PDF tunggal** dan semua halaman berurutan dengan benar.
2. **Uji Coba Unggah PDF Langsung**:
   * Unggah berkas `.pdf` ijazah langsung dari komputer/HP. Pastikan sistem menerimanya tanpa proses kompilasi ulang dan langsung menyimpannya.
3. **Uji Keamanan Akses Langsung (Lockdown Direct URL)**:
   * Coba akses file secara langsung melalui URL path browser. Pastikan server mengembalikan pesan eror **403 Forbidden** atau **404 Not Found**.
