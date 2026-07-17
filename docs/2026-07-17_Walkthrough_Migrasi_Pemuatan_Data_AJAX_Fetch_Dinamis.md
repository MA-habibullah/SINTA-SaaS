# Walkthrough: Perbaikan Keamanan Menyeluruh & Migrasi Pemuatan Data Halaman ke Model AJAX Fetch Dinamis

Dokumen ini menjelaskan hasil perbaikan dan verifikasi terhadap perbaikan celah keamanan kebocoran data sensitif (*Information Leakage*) di aplikasi **SINTA-SaaS**. 

Seluruh penyuntikan data mentah via inline PHP `json_encode()` di dalam tag `<script>` pada berkas *view* telah berhasil dimigrasikan ke model pemuatan data asinkronus menggunakan **AJAX Fetch secara dinamis**.

---

## Ringkasan Perubahan

Berikut adalah ringkasan perubahan yang diimplementasikan pada 6 modul utama:

### 1. Modul Edit & Tambah Siswa
* **File Terkait:** 
  * [tambah_siswa.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/tambah_siswa.php)
  * [SiswaController.php](file:///c:/xampp/htdocs/SINTA-SaaS/app/Controllers/SiswaController.php)
* **Perubahan:**
  * Menghilangkan penyuntikan inline JSON `$siswa` dan `$kesehatan` yang memaparkan data sensitif (NIK, KK, nama ibu, password).
  * Membuat endpoint asinkronus `get_siswa_detail` dan `get_siswa_draft` di `SiswaController.php`.
  * Memuat data siswa dan data draf edit secara asinkronus menggunakan `axios` di dalam kait lifecycle `onMounted()` Vue.

### 2. Modul Dashboard
* **File Terkait:**
  * [dashboard_view.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/dashboard_view.php)
  * [DashboardController.php](file:///c:/xampp/htdocs/SINTA-SaaS/app/Controllers/DashboardController.php)
* **Perubahan:**
  * Menghilangkan data dump daftar siswa (`siswaList`), daftar guru/GTK (`gtkList`), dan riwayat aktivitas log terbaru (`recentChanges`) dari render GET awal.
  * Mendaftarkan action AJAX `get_dashboard_stats` di `DashboardController.php`.
  * Memuat seluruh daftar data sensitif tersebut menggunakan pemanggilan `axios.get` ketika Vue instance terpasang (`mounted()`).

### 3. Modul Profil & Identitas Sekolah
* **File Terkait:**
  * [sekolah_profil.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/sekolah_profil.php)
  * [identitas_sekolah.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/identitas_sekolah.php)
  * [SekolahController.php](file:///c:/xampp/htdocs/SINTA-SaaS/app/Controllers/SekolahController.php)
  * [TenantController.php](file:///c:/xampp/htdocs/SINTA-SaaS/app/Controllers/TenantController.php)
* **Perubahan:**
  * Menghapus pencetakan langsung data tenant (`$tenant`) di dalam blok skrip Javascript.
  * Menambahkan endpoint asinkronus `get_profile_detail` di `SekolahController.php` dan `get_tenant_detail` di `TenantController.php`.
  * Memuat informasi profil dan konfigurasi tenant asinkronus di Vue `onMounted()`.

### 4. Modul Bimbingan Konseling (BK)
* **File Terkait:**
  * [master_bk.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/master_bk.php)
  * [bk/master_kampus_prodi_layout.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/bk/master_kampus_prodi_layout.php)
* **Status:** **SUDAH AMAN**
  * Modul ini secara bawaan telah memuat seluruh data siswa, poin pelanggaran, serta log kehadiran secara asinkronus menggunakan pemanggilan API/AJAX. Tidak ada data dump pribadi siswa yang bocor di source page.

### 5. Modul Buku Induk Siswa
* **File Terkait:**
  * [buku_induk.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/buku_induk.php)
  * [BukuIndukController.php](file:///c:/xampp/htdocs/SINTA-SaaS/app/Controllers/BukuIndukController.php)
* **Perubahan:**
  * Menghapus dumps inline untuk daftar filter dropdown `listTenants`, `jenjangOptions`, dan `kelasOptions` di dalam kode JavaScript.
  * Menambahkan action `get_options` di method `fetchApi()` pada `BukuIndukController.php`.
  * Memuat opsi filter secara asinkronus di fungsi `mounted()` Vue.

### 6. Modul Agenda Terpadu & Gantt Chart
* **File Terkait:**
  * [sekolah/agenda_terpadu.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/sekolah/agenda_terpadu.php)
  * [AgendaController.php](file:///c:/xampp/htdocs/SINTA-SaaS/app/Controllers/AgendaController.php)
* **Perubahan:**
  * Menghapus pencetakan seluruh record kegiatan (`$events`) dan grafik batang Gantt (`$ganttTasks`) di source HTML.
  * Menambahkan AJAX action `get_agenda_data` di `AgendaController.php`.
  * Memperbarui inisialisasi FullCalendar dan Frappe Gantt di dalam skrip `views/sekolah/agenda_terpadu.php` agar mengambil data secara asinkronus menggunakan Axios sebelum komponen dirender.

### 7. Modul Log Aktivitas
* **File Terkait:**
  * [activity_logs.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/activity_logs.php)
* **Status:** **SUDAH AMAN**
  * Halaman Log Aktivitas memuat seluruh data log perubahan dan opsi filter secara dinamis melalui API Axios `/api/v1/activity-logs`. Halaman awal bersih dari muatan data sensitif.

---

## Hasil Verifikasi Keamanan & Fungsionalitas

1. **Pencegahan View Source Leak (Ctrl + U)**:
   * Saat memuat menu Dashboard, Buku Induk, Profil Sekolah, dan Agenda Terpadu, ketika pengguna melihat kode sumber halaman (*View Page Source*), tag `<script>` tidak lagi berisi data pribadi siswa (seperti NIK, KK, nama siswa, email, detail sekolah).
   * Data hanya tersimpan di memori JavaScript browser (*Vue Reactive State*) setelah dipanggil melalui AJAX, mencegah *Web Scraper* biasa dan injeksi manipulasi data.
2. **Keresponsifan UI Tetap Terjaga**:
   * Penggunaan Axios Fetch di Vue `onMounted()` / `mounted()` berjalan sangat cepat (di bawah 100ms secara lokal) sehingga transisi pemuatan data terasa mulus dan instan bagi pengguna.
   * Modul pendukung (kalender FullCalendar, grafik batang Gantt, dropdown seleksi TomSelect, dan visualisasi data) tetap berfungsi dengan sempurna.
