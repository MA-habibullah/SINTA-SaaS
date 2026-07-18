---
## Pengamanan Halaman Dashboard & Proteksi Cetak Buku Induk (OTT)
**Waktu**: 15:05 WIB
**Jenis**: Security Hardening

**Deskripsi pekerjaan:**
1. **Dashboard Zero Data Leakage:** Menghapus rendering data database statis dari file dashboard view. Seluruh data dashboard (statistik, profil tenant, pengumuman, siswa/GTK) kini dimuat dinamis via AJAX fetch menggunakan Vue 3 & Axios saat ter-mount di browser client, mengamankan data dari Ctrl+U.
2. **One-Time Token Cetak:** Menerapkan otentikasi token jangka pendek (60 detik) untuk setiap akses URL cetak (`printRapot`, `printBukuInduk`, `printRapotSemester`, dll.). Token tersebut langsung dimusnahkan (`unset`) dari session server seketika setelah divalidasi pertama kali untuk mencegah replay attack.
3. **Penyuntingan Client Cetak:** Memodifikasi javascript client cetak agar meminta token cetak terlebih dahulu via API `/api/v1/cetak/request-token` sebelum membuka dialog print preview.

---
## Perbaikan Bug 400 Bad Request pada Modul BK Akademik
**Waktu**: 15:21 WIB
**Jenis**: Bug Fix

**Deskripsi pekerjaan:**
1. **Perbaikan Fallback Operator $tenantId**: Mengganti inisialisasi variabel `$tenantId` di bagian atas file `pdss_index.php` agar dapat mengambil data fallback dari parent layout (yaitu dari `akademik_layout.php`), mencegah overwriting data tenant_id menjadi kosong ketika file ini di-include.
2. **Penanganan Balapan Data (Race Condition)**: Menambahkan guard check di method `fetchPdssMapels()`, `fetchSimulasiSettings()`, dan `fetchSimulasi()`. Mengatur urutan method di `refreshAll()` agar `fetchKesiapan()` diselesaikan terlebih dahulu (await) sebelum sisa API paralel dipanggil, mencegah pengiriman parameter `tahun_ajaran_id=` kosong ke server.

---
## Perbaikan Pemetaan Cohort Siswa Kelas 12 PDSS
**Waktu**: 15:33 WIB
**Jenis**: Bug Fix

**Deskripsi pekerjaan:**
1. **Perbaikan Subquery Semester 5**: Mengubah filter subquery `dnr.semester = 5` menjadi `dnr.semester = 'Ganjil' AND kelas_dnr.nama_kelas LIKE '%12%'` karena data semester di database bertipe string ('Ganjil'/'Genap').
2. **Logika Pemetaan Adaptif Tahun Ajaran**: Menambahkan pencocokan ganda pada logic query SQL (`CONCAT` angkatan masuk OR pencocokan langsung `ta.tahun_ajaran`) agar siswa `Dummy Cohort` yang tahun ajaran aktifnya langsung diset ke `2024/2025` terpetakan dengan tepat di tahun ajaran `2024/2025` dan tidak meloncat ke `2026/2027`. File yang diubah: `app/Controllers/PDSSController.php`.

---
## Perbaikan Kelayakan Simulasi, Error Toggle Setting & Ekspor XLSX
**Waktu**: 15:47 WIB
**Jenis**: Bug Fix & Feature

**Deskripsi pekerjaan:**
1. **Perbaikan Hitungan Rata-rata Nilai Rapor**: Mengubah kalkulasi rata-rata nilai rapor 5 semester di `apiGetSimulasi()` agar menggunakan pemetaan dinamis `getSemesterLevel()` di PHP (seperti di Kesiapan), menyelesaikan kegagalan query `dnr.semester IN (1,2,3,4,5)` yang mengembalikan rata-rata 0.
2. **Sinkronisasi Quota Map**: Menambahkan filter Tahun Ajaran evaluasi saat ini ke dalam query `$stmtQuota` pada API Simulasi.
3. **Perbaikan Error 500 Toggle Setting**: Menambahkan method static `getUserNama()` di `app/Core/SessionManager.php` untuk menghindari error `Call to undefined method` ketika menyimpan data log pembukaan/penutupan simulasi.
4. **Format Ekspor Spreadsheet Modern (XLSX)**: Mengubah format output file ekspor simulasi pada `apiExportSimulasi()` di `app/Controllers/PDSSController.php` dari format file CSV menjadi format berkas spreadsheet (.xlsx) menggunakan `\Shuchkin\SimpleXLSXGen`.
5. **Pencatatan Analisis Log**: Menambahkan rangkuman root cause analysis di akhir file `scratch/00 error, log, console, inject, dll.txt`.

---
## Perbaikan Halaman BK Alumni & Tracer Study
**Waktu**: 16:17 WIB
**Jenis**: Bug Fix / Feature / Refactor

### File yang Diubah:
- pp/Controllers/TracerController.php — Tambah guru_bk dan operator_sekolah ke role guard storeKuliah/storePekerjaan. Tambah method deleteKuliah() dan deletePekerjaan().
- iews/tracer_study.php — Refactor total: XSS-safe via json_encode flag, banner role-aware (siswa vs admin), tabel dengan kolom Nama Alumni dan tombol Hapus untuk admin, live search siswa alumni, toggle input alumni luar sistem.
- index.php — Tambah 3 route baru: DELETE tracer kuliah/pekerjaan dan search siswa alumni.
- pp/Controllers/PDSSController.php — Hapus duplikat method apiSearchStudents (sudah ada di baris 887).

### Root Cause:
- Role guru_bk dan operator_sekolah mendapat 403 karena tidak terdaftar di whitelist storeKuliah/storePekerjaan.
- XSS risk: $userRole di-echo langsung ke <script> tanpa json_encode.
- Tidak ada tombol hapus dan kolom nama alumni untuk tampilan admin.
- Endpoint DELETE dan search siswa belum ada.

---
## Restrukturisasi & Integrasi Folder Uploads PDSS
**Waktu**: 16:55 WIB
**Jenis**: Refactor / Security Storage Integration

### File yang Diubah:
- `app/Controllers/PDSSController.php` - Mengubah target direktori upload dan relative path penyimpanan file bukti dari `uploads/pdss/` menjadi `storage/uploads/pdss/`.
- `scratch/refactor_pdss_uploads_path.php` [BARU] - Script otomatis untuk migrasi data path di database dan memindahkan folder fisik.

### Root Cause:
- Folder uploads untuk PDSS sebelumnya terpisah di root directory (`/uploads/pdss/`), yang tidak konsisten dengan standar *Storage Isolation* SINTA-SaaS yang menggunakan `/storage/uploads/`.
- Dilakukan konsolidasi agar semua berkas upload berada di bawah subdirektori `/storage/uploads/` demi ketertiban struktur dan pengamanan terpusat berkas.

---
## Perbaikan Blank Tab Tracking Data Alumni pada Halaman BK Alumni
**Waktu**: 17:05 WIB
**Jenis**: Bug Fix

### File yang Diubah:
- `views/bk/alumni_layout.php` - Mengubah filter `$allowed_pdss_tabs` dari `['alumni']` menjadi `['tracking']`.

### Root Cause:
- Terjadi mismatch/ketidaksesuaian ID tab filter di mana layout pembungkus mengirimkan ID `alumni` sedangkan module utama `pdss_index.php` mengharapkan ID `tracking` untuk merender dan menampilkan kontainer data alumni. Akibatnya, kontainer data disembunyikan (`v-show`) dan halaman terlihat kosong (blank).

---
## Perbaikan Alumni Luar Sistem Tidak Muncul di Riwayat Kuliah & Pekerjaan
**Waktu**: 17:10 WIB
**Jenis**: Bug Fix

### File yang Diubah:
- `app/Controllers/TracerController.php` -- Mengubah JOIN siswa s menjadi LEFT JOIN siswa s pada 4 query di method apiGetKuliah() dan apiGetPekerjaan().

### Root Cause:
- Data alumni input manual (luar sistem) disimpan dengan id_siswa = NULL di tabel riwayat_kuliah dan riwayat_pekerjaan.
- Query lama menggunakan INNER JOIN siswa s ON rk.id_siswa = s.id sehingga baris dengan id_siswa = NULL selalu tersaring keluar.
- Solusi: Mengubah ke LEFT JOIN agar baris dengan id_siswa = NULL tetap diikutsertakan. Kolom nama_lengkap akan berisi NULL untuk alumni luar sistem (ditangani di frontend dengan fallback nama).
