---
## [Lanjutan Perbaikan Bug & Refactoring Skema Database]
**Waktu**: 00:07 WIB
**Status**: Dieksekusi

**Latar Belakang & Root Cause:**
1. **Crash API Kelembagaan & Buku Induk (Undefined Table)**: Controller dan Model ini menggunakan *raw query* tanpa *prefix schema* PostgreSQL yang tepat (misal: hanya kelas atau 	enants alih-alih sistem.kelas atau core.tenants). Ini memicu error 500 yang merusak integrasi dengan *frontend* Axios/Turbo.
2. **Kekacauan Penamaan Kolom (Scaffolded Code Smell)**: File migrasi tabel dari modul 03 hingga 16 dihasilkan dengan *generator* otomatis yang memberikan nama kolom sangat buruk dan repetitif, yaitu 
ama_{nama_tabel} (contoh: 
ama_activity_logs, 
ama_agenda_sekolah, 
ama_cms_banners). Ini adalah *technical debt* yang harus segera distandarisasi.

**Proposed Changes:**
1. **PenggunaModel.php, KelembagaanModel.php, BukuIndukModuleController.php**: Menyuntikkan prefix core., sistem., siswa., dan kademik. ke seluruh query raw yang sebelumnya terlewat. Menghapus juga MySQL sintaks spesifik seperti INSERT IGNORE menjadi valid PostgreSQL ON CONFLICT DO NOTHING.
2. **Database Migrations (03-16)**: Menjalankan skrip *Regex Replace* untuk mendeteksi 
ama_{table_name} dan menggantinya dengan 
ama, judul, pertanyaan, atau error_message yang jauh lebih masuk akal secara arsitektur basis data relasional.

**Verification Plan:**
- Verifikasi Sintaks: Menjalankan php -l pada semua model dan file migrasi yang telah diubah.
- Validasi Log: Memastikan log 500 Undefined table dari KelembagaanModel tidak lagi muncul.
