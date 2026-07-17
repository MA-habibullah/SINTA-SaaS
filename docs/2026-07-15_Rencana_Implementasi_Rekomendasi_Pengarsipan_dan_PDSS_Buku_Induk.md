# Rencana Implementasi: Rekomendasi Pengarsipan Buku Induk & Ekspor PDSS SNBP

Rencana kerja ini mengusulkan peningkatan sistem Buku Induk agar selaras dengan aturan pemerintah terkait keamanan dokumen negara, integrasi Dapodik/PDSS SNBP, dan kemudahan pengarsipan digital massal.

---

## Proposed Recommendations & Features

### 1. Sistem Penguncian Nilai (Lock Grades) & Audit Log
* **Tujuan**: Mencegah manipulasi nilai rapor historis secara tidak sengaja/tidak sah (memenuhi kepatuhan hukum buku induk sebagai dokumen negara).
* **Rencana Perubahan**:
  * Tambahkan kolom `is_locked` (tinyint) pada tabel `detail_nilai_rapor` (atau `kelas_kurikulum`).
  * Di halaman Input Nilai Rapor, buat tombol **"Kunci Nilai Kelas"** bagi Admin/Kepala Sekolah. Nilai yang dikunci tidak dapat diedit/dihapus oleh guru/operator biasa kecuali dibuka kunci (unlock) dengan autentikasi berwenang.
  * Buat tabel logging `log_nilai_rapor` untuk mencatat siapa, kapan, mata pelajaran apa, serta perubahan nilai sebelum dan sesudah edit.

### 2. Fitur Ekspor Massal PDF Rapor (ZIP Archive)
* **Tujuan**: Mempermudah pengarsipan digital. Operator sekolah dapat mendownload seluruh PDF buku induk/rapor siswa satu kelas dalam satu file ZIP sekali klik.
* **Rencana Perubahan**:
  * Tambahkan tombol **"Unduh ZIP Rapor Rombel"** di menu Cetak Buku Induk.
  * Di backend, buat endpoint `BukuIndukController::exportZipRombel()` yang meng-generate PDF dinamis per siswa dan membundelnya ke dalam kompresi ZIP menggunakan library PHP `ZipArchive`.

### 3. Ekspor Formatted Excel untuk PDSS SNBP
* **Tujuan**: Membantu siswa tingkat akhir mendaftar SNBP dengan menyediakan format data nilai semester 1-5 yang 100% kompatibel dengan portal nasional SNBP.
* **Rencana Perubahan**:
  * Tambahkan tombol **"Ekspor Format PDSS SNBP"** di menu Buku Induk.
  * Format ekspor berupa sheet Excel per mata pelajaran (sesuai template portal SNBP) dengan baris NISN siswa dan kolom Nilai Rapor Semester 1 s.d 5.

---

## Proposed Code Changes

### [NEW] [log_nilai_rapor migration](file:///c:/xampp/htdocs/SINTA-SaaS/database/migrations/2026_07_16_01_create_grade_audit_logs.php)
* Migrasi skema database untuk tabel audit log nilai dan penambahan kolom status penguncian nilai.

### [MODIFY] [BukuIndukController.php](file:///c:/xampp/htdocs/SINTA-SaaS/app/Controllers/BukuIndukController.php)
* Tambahkan method `exportZipRombel()` untuk mengumpulkan HTML PDF siswa satu rombel dan membundelnya ke file `.zip`.
* Tambahkan method `exportPdssSnbp()` untuk mengekspor data spreadsheet berformat SNBP.
* Tambahkan pengecekan `is_locked` pada operasi simpan/update nilai rapor.

### [MODIFY] [buku_induk.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/buku_induk.php)
* Tambahkan tombol **Kunci Nilai**, **Unduh ZIP Rombel**, and **Ekspor PDSS** pada visual UI.

---

## Verification Plan

### Automated & Manual Verification
1. **Pengecekan Lock Grades**:
   * Masuk sebagai operator biasa, coba simpan nilai pada kelas yang berstatus terkunci. Pastikan sistem menolak dengan pesan error yang ramah.
2. **Uji Coba Ekspor ZIP**:
   * Klik tombol "Unduh ZIP Rombel", pastikan file zip terunduh dengan sukses dan berisi file PDF masing-masing siswa yang valid dan tidak korup.
3. **Uji Coba Ekspor PDSS**:
   * Ekspor data PDSS kelas XII, pastikan format sheet Excel tersusun rapi sesuai regulasi portal SNBP.
