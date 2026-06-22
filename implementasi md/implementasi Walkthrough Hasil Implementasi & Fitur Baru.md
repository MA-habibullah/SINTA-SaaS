# Walkthrough: Hasil Implementasi & Fitur Baru

Dokumen ini merangkum seluruh hasil implementasi dan perbaikan sistem yang dikerjakan pada sesi ini:
1. **Modul 1: Profil / Identitas Sekolah (Tenant Profile)**
2. **Modul 2: Antrean Sistem & Background Jobs**
3. **Modul 3: Migrasi Aset Offline & Online (Aset Lokal)**
4. **Modul 4: Perbaikan Kompatibilitas Turbo Drive & Bugfix Konsol**
5. **Modul 5: Penambahan Horizontal Scroll Bar di Tabel Dashboard**
6. **Modul 6: Optimasi Lifecycle Vue + Turbo Drive (Anti-Thrashing)**
7. **Modul 7: Perbaikan Pelanggaran Kinerja Handler Klik ([Violation] 'click' handler)**

---

## 🚀 MODUL 1: Profil & Identitas Sekolah

Kami telah sukses membangun dan mengintegrasikan fitur pengolahan profil lengkap sekolah untuk mempermudah instansi SaaS mengelola identitas pokok mereka secara mandiri.

* **Alter Tenants ([2026_06_21_alter_tenants_add_profile_fields.php](file:///c:/xampp/htdocs/dapodik-spmb/database/migrations/2026_06_21_alter_tenants_add_profile_fields.php))**:
  * Menambahkan 19 kolom baru untuk melengkapi profile (alamat, RT/RW, kelurahan, kecamatan, kota, provinsi, kode pos, email resmi, website, detail pimpinan/kepsek, operator, logo, dan berkas akreditasi).
* **Akreditasi Dinamis ([2026_06_21_alter_tenants_add_akreditasi.php](file:///c:/xampp/htdocs/dapodik-spmb/database/migrations/2026_06_21_alter_tenants_add_akreditasi.php))**:
  * Menambahkan kolom `akreditasi` (`VARCHAR(50)`) agar nilai status akreditasi dapat disimpan ke database secara dinamis.
* **Controller ([SekolahController.php](file:///c:/xampp/htdocs/dapodik-spmb/app/Controllers/SekolahController.php))**:
  * **Otorisasi**: Membatasi akses menu hanya untuk `super_admin` dan `operator_sekolah`.
  * **Dropdown Filter Super Admin**: Menyediakan parameter `GET/POST` `tenant_id` khusus bagi `super_admin` untuk memfilter dan mengedit profil sekolah mana pun yang terdaftar.
  * **Unggah Berkas**: Memvalidasi ekstensi dan membatasi ukuran maksimal file (Logo: JPG/PNG, Sertifikat: JPG/PNG/PDF) maksimal **500 KB**.
  * **Disk Housekeeping**: Secara aman menghapus file fisik lama di disk menggunakan `unlink()` apabila ada file baru diunggah untuk menghindari pemborosan disk space.
* **View ([sekolah_profil.php](file:///c:/xampp/htdocs/dapodik-spmb/views/sekolah_profil.php))**:
  * Menggunakan grid Tailwind CSS 2-3 kolom dengan menonaktifkan Preflight Tailwind agar tidak merusak tema Bootstrap 5 utama.
  * Dilengkapi drag-and-drop area interaktif untuk berkas serta preview gambar secara *real-time*.
  * Kolom akreditasi diubah menjadi input teks biasa agar pengguna bebas menulis status legalitasnya.

---

## ⚙️ MODUL 2: Antrean Sistem & Background Jobs

Kami telah membangun arsitektur antrean pekerjaan latar belakang (*Background Jobs Queue*) berbasis database MySQL dan CLI worker.

* **Tabel Antrean ([2026_06_21_create_system_jobs_table.php](file:///c:/xampp/htdocs/dapodik-spmb/database/migrations/2026_06_21_create_system_jobs_table.php))**:
  * Membuat tabel `system_jobs` untuk menampung tugas pending, processing, completed, atau failed beserta payload JSON dan pencatatan error jika gagal.
* **Aktivasi Menu ([2026_06_21_update_menu_antrean_url.php](file:///c:/xampp/htdocs/dapodik-spmb/database/migrations/2026_06_21_update_menu_antrean_url.php))**:
  * Mengubah URL menu ID 17 dari `#` menjadi `/dapodik-spmb/utilitas/antrean` agar aktif di sidebar navigasi.
* **Queue Manager ([QueueManager.php](file:///c:/xampp/htdocs/dapodik-spmb/app/Helpers/QueueManager.php))**:
  * Kelas re-usable untuk menjejalkan job (`push`), menarik job dengan proteksi row-locking (`pop`), serta menandai status selesai/gagal.
* **Job Dispatcher ([JobDispatcher.php](file:///c:/xampp/htdocs/dapodik-spmb/app/Jobs/JobDispatcher.php))**:
  * Menangani eksekusi pekerjaan berdasarkan `job_type`:
    * `DEMO_SYNC`: Simulasi sinkronisasi data Pusdatin (mendukung simulasi gagal dengan melempar exception).
    * `DEMO_EMAIL`: Simulasi pengiriman email massal blast.
    * `CLEANUP_SESSIONS`: Tugas sistem membersihkan log sesi aktif lama di database.
* **CLI Entrypoint ([worker.php](file:///c:/xampp/htdocs/dapodik-spmb/worker.php))**:
  * Skrip CLI mandiri untuk memproses pekerjaan di latar belakang.
  * Berjalan dengan perintah:
    * `php worker.php --run` (memproses seluruh pekerjaan di antrean).
    * `php worker.php --once` (memproses satu pekerjaan saja).
* **Controller ([QueueController.php](file:///c:/xampp/htdocs/dapodik-spmb/app/Controllers/QueueController.php))**:
  * Mengembalikan data metrik KPI dan list logs antrean, melayani simulasi dispatch tugas baru, retry pekerjaan gagal, dan penghapusan log antrean.
  * **Web Runner (`runWorkerApi`)**: Menyediakan endpoint untuk menjalankan satu antrean pending langsung melalui tombol dari browser.
* **View ([queue_monitoring.php](file:///c:/xampp/htdocs/dapodik-spmb/views/queue_monitoring.php))**:
  * Menampilkan ringkasan metrik status (Pending, Processing, Completed, Failed).
  * Panel kontrol simulasi penambahan tugas dan pemicu jalan manual antrean (*Web Runner*).
  * Tabel riwayat antrean terperinci dengan payload dan error debugging log.

---

## 🔌 MODUL 3: Migrasi Aset Offline & Online (Aset Lokal)

Untuk memastikan aplikasi dapat berjalan 100% secara offline tanpa internet, kami telah mengunduh seluruh pustaka JS/CSS dan mengganti pemanggilan CDN eksternal dengan file lokal yang disimpan di direktori `/dapodik-spmb/assets/`:

1. **Aset CSS**:
   - `bootstrap.min.css` (Bootstrap 5.3)
   - `bootstrap-icons.css` (Bootstrap Icons beserta file fonts `.woff`/`.woff2` di direktori `assets/css/fonts/`)
2. **Aset JavaScript**:
   - `vue.global.prod.js` (Vue 3 Production Build)
   - `axios.min.js` (HTTP Client)
   - `chart.umd.js` (Chart.js UMD Build)
   - `sweetalert2.all.min.js` (SweetAlert2)
   - `tailwindcss.js` (Tailwind CSS Play CDN lokal)
   - `turbo.es2017-umd.js` (Hotwire Turbo Drive)

*Seluruh link CDN eksternal di halaman Login Admin, Login Siswa, Ganti Password Siswa, Dashboard, Profil Sekolah, Antrean, Sesi Aktif, Log Aktivitas, Manajemen Pengguna, Tenant Management, dan Kelembagaan telah dimigrasikan sepenuhnya.*

---

## 🛠️ MODUL 4: Perbaikan Kompatibilitas Turbo Drive & Bugfix Konsol

Kami menyelesaikan empat masalah krusial terkait transisi halaman AJAX menggunakan **Hotwire Turbo Drive**:

### 1. Perbaikan Sidebar Toggle & Status Persisten
* **Masalah**: Sidebar toggle tidak dapat diklik setelah pengguna berpindah halaman. Hal ini dikarenakan event listener diikat pada `DOMContentLoaded` yang tidak terpicu ulang oleh Turbo.
* **Solusi**: Di [master.php](file:///c:/xampp/htdocs/dapodik-spmb/views/layout/master.php), kami mengubah pengikatan event menjadi `turbo:load` dan menggunakan `.onclick` langsung untuk mencegah pengikatan ganda. Status collapse juga disinkronkan dari `localStorage` pada setiap load.

### 2. Perbaikan Menu Klik Sidebar (Collapse Menus)
* **Masalah**: Menu sub-kategori (collapse) di sidebar tidak merespons klik setelah navigasi halaman karena Bootstrap kehilangan instansi DOM permanent.
* **Solusi**: Kami menghapus atribut `data-turbo-permanent` pada tag `<aside id="sidebar">` di [sidebar.php](file:///c:/xampp/htdocs/dapodik-spmb/views/layout/sidebar.php). Ini memaksa Turbo meregenerasi sidebar baru sehingga menu collapse Bootstrap diinisialisasi ulang dengan sempurna.

### 3. Solusi Race Condition Pemuatan Tailwind (`tailwind is not defined`)
* **Masalah**: Konfigurasi `tailwind.config = { ... }` memicu error karena dieksekusi sebelum script `tailwindcss.js` selesai dimuat secara asinkronus oleh browser.
* **Solusi**: Kami memindahkan konfigurasi Tailwind agar dideklarasikan di dalam objek `window.tailwind` **sebelum** script `tailwindcss.js` dipanggil. Tailwind secara otomatis membaca config bawaan ini saat selesai termuat.

### 4. Solusi Konflik Cakupan Variabel (`ref has already been declared`)
* **Masalah**: Deklarasi composition API Vue (`const { ref, reactive } = Vue;`) di tingkat atas `<script>` subview memicu crash saat script tersebut dievaluasi kembali oleh Turbo pada halaman yang sama.
* **Solusi**: Kami membungkus seluruh blok JavaScript Vue subview ke dalam block scope `{ ... }` untuk melokalisasi variabel-variabel tersebut agar tidak mencemari namespace global window.

### 5. Penanganan Bypass API Bimbingan Konseling (BK) `super_admin`
* **Masalah**: Akses awal ke halaman `/bk` oleh `super_admin` memicu error `GET 400 Bad Request` dari Axios karena backend membutuhkan `tenant_id` sekolah yang valid, sementara super admin belum memfilter sekolah.
* **Solusi**: Kami menambahkan *early return* pada fungsi-fungsi penembakan API otomatis (`loadDashboard`, `loadPenjurusan`, `loadTracer`, `loadPdss`, `loadKasus`, dan `loadKelasList`) di [master_bk.php](file:///c:/xampp/htdocs/dapodik-spmb/views/master_bk.php) jika pengguna masuk sebagai `super_admin` dan `currentTenantId` belum terpilih. Hal ini meniadakan error 400 di konsol browser.

---

## 📊 Hasil Pengujian & Verifikasi

1. **Linter Sintaksis**:
   - Seluruh file PHP di dalam direktori `views/` telah di-lint menggunakan `Get-ChildItem ... | ForEach-Object { php -l $_ }`.
   - **Hasil**: **21 file PHP lolos verifikasi dengan status sukses (No syntax errors detected).**
2. **Koneksi Jaringan (Offline Mode)**:
   - Verifikasi melalui DevTools menunjukkan bahwa semua request pustaka (Bootstrap, Vue, Tailwind, Axios, SweetAlert2, Chart.js, Turbo) berhasil dilayani secara lokal dari `/dapodik-spmb/assets/` tanpa ada request keluar ke CDN eksternal.

---

## 📱 MODUL 5: Penambahan Horizontal Scroll Bar di Tabel Dashboard

Kami telah menambahkan fungsionalitas dan memperindah tampilan scrollbar horizontal untuk seluruh tabel di halaman Dashboard ([dashboard_view.php](file:///c:/xampp/htdocs/dapodik-spmb/views/dashboard_view.php)):

1. **Perbaikan Blokir Scrollbar**:
   - Menghapus kelas `overflow-hidden` pada container pembungkus tabel **Log Perubahan Siswa**. Sebelumnya, kombinasi kelas `table-responsive` dan `overflow-hidden` menonaktifkan kemampuan scrollbar horizontal karena menyembunyikan overflow secara absolut.
2. **Kustomisasi Scrollbar Horizontal Premium**:
   - Menambahkan blok gaya CSS kustom untuk `.table-responsive` di dalam lingkup `#dashboardApp`.
   - Scrollbar dihias menggunakan palet warna harmonis (Slate 100 untuk track, Slate 300 untuk thumb, dan Slate 400 saat di-hover) dengan tinggi minimalis `8px` agar terlihat modern dan elegan di seluruh browser desktop (Chrome, Edge, Opera, Firefox) serta responsif di perangkat layar sentuh mobile.

---

## 🛡️ MODUL 6: Optimasi Lifecycle Vue + Turbo Drive (Anti-Thrashing)

Kami telah merombak arsitektur inisialisasi Vue di layout utama [master.php](file:///c:/xampp/htdocs/dapodik-spmb/views/layout/master.php) untuk menanggulangi masalah **Vue Lifecycle Thrashing** (mounting & unmounting berulang dalam hitungan milidetik):

1. **Singleton Mount Pattern (`window.vueApps`)**:
   - Memperkenalkan penampung global `window.vueApps` untuk melacak status instansi Vue yang aktif.
   - Sebelum memanggil `Vue.createApp().mount()`, sistem mencocokkan referensi elemen DOM target (`active.el === el`). Jika elemen tersebut masih ada di DOM dan merupakan node yang sama (misal saat berganti tab internal CSS/v-show), remount diabaikan.
2. **Lazy/Selective Unmount (`cleanupOrphanedApps`)**:
   - Fungsi unmount hanya dipanggil jika elemen DOM target benar-benar hilang dari layar atau digantikan oleh node baru.
   - Selama transisi tab atau cache, elemen yang masih menetap di dokumen tidak akan dilepas instansinya, menjaga state data Vue tetap aman.
3. **Migrasi Sub-Halaman Utama**:
    - Mengubah mekanisme inisialisasi pada sub-view penting: [master_bk.php](file:///c:/xampp/htdocs/dapodik-spmb/views/master_bk.php), [tracer_study.php](file:///c:/xampp/htdocs/dapodik-spmb/views/tracer_study.php), dan [tenant_menus.php](file:///c:/xampp/htdocs/dapodik-spmb/views/tenant_menus.php) dari yang sebelumnya menggunakan `Vue.createApp(...).mount(...)` langsung (yang merusak siklus hidup karena luput dari pendeteksian unmount) menjadi pendaftaran aman lewat `window.VueAppRegistry.register` dan diisolasi di dalam block scope `{ ... }`.

---

## ⚡ MODUL 7: Perbaikan Pelanggaran Kinerja Handler Klik ([Violation] 'click' handler)

Kami menyelesaikan masalah peringatan kinerja browser `[Violation] 'click' handler took ...ms` di konsol dashboard:

1. **Penyebab Pelanggaran Kinerja**:
   - Panggilan fungsi `alert()` bawaan browser bersifat sinkronus dan memblokir *main thread* JavaScript sepenuhnya hingga dialog diklik tutup oleh pengguna. Selama dialog aktif, browser menghitung waktu penanganan event klik tersebut. Jika pengguna menutup dialog setelah beberapa detik, browser menandai handler klik tersebut lambat (misalnya `took 1756ms`).
2. **Solusi Non-Blocking (`showSimulationAlert` Helper)**:
   - Menambahkan fungsi helper asinkronus `window.showSimulationAlert` di [master.php](file:///c:/xampp/htdocs/dapodik-spmb/views/layout/master.php) menggunakan pustaka **SweetAlert2** (`Swal.fire`). Karena Swal bersifat non-blocking, modal ditampilkan secara asinkronus dan handler event klik langsung selesai dijalankan (< 1ms).
3. **Penggantian Fungsi di Seluruh Kode**:
   - Mengubah handler klik di [sidebar.php](file:///c:/xampp/htdocs/dapodik-spmb/views/layout/sidebar.php) dan [header.php](file:///c:/xampp/htdocs/dapodik-spmb/views/layout/header.php) yang memicu menu simulasi dari `onclick="alert(...)"` menjadi `onclick="showSimulationAlert(...)"`.
   - Mengubah validasi manual bermodel pemblokiran di [master_bk.php](file:///c:/xampp/htdocs/dapodik-spmb/views/master_bk.php) dan [tambah_siswa.php](file:///c:/xampp/htdocs/dapodik-spmb/views/tambah_siswa.php) menggunakan `Swal.fire` asinkronus agar bebas dari warning pemblokiran utas utama.
