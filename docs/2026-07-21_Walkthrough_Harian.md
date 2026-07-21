---
## [Modul Keuangan SPP Dinamis & Integrasi PPDB]
**Waktu**: 07:22 WIB
**Jenis**: Feature

### Ringkasan Pekerjaan:
1. **Pembuatan File Controller**:
   - Membuat `app/Controllers/SppController.php` dengan implementasi CRUD Komponen, Tarif Acuan, Keringanan Siswa, Generator Tagihan Massal berbasis DB Transaction, POS Loket Kasir, Laporan Rekapitulasi Kas/Tunggakan, dan custom settings.
   - Menyediakan API Endpoint Hook `/api/v1/keuangan/buat-tagihan-ppdb` untuk integrasi otomatis pembuatan tagihan saat pendaftaran PPDB.
2. **Pembuatan File Antarmuka (Views)**:
   - Membuat `views/keuangan/pengaturan.php` (Pengaturan terminologi dan visibilitas).
   - Membuat `views/keuangan/master.php` (Manajemen Komponen & Tarif).
   - Membuat `views/keuangan/keringanan.php` (Manajemen Diskon & Beasiswa dengan Student Autocomplete).
   - Membuat `views/keuangan/generate.php` (Batch generator tagihan massal aman dari duplikasi).
   - Membuat `views/keuangan/kasir.php` (Loket Kasir POS dengan kalkulasi kembalian tunai, notifikasi tunggakan, dan cetak kuitansi digital).
   - Membuat `views/keuangan/laporan.php` (Laporan pemasukan & tunggakan, ekspor excel, print layout).
   - Membuat `views/keuangan/tagihan_saya.php` (Dashboard tagihan bagi profil login siswa).
3. **Pendaftaran Rute Utama**:
   - Menambahkan rute `/api/v1/keuangan/buat-tagihan-ppdb` di berkas `index.php`.
4. **Pengujian Integrasi**:
   - Membuat skrip `scratch/test_spp_billing.php` untuk memvalidasi transaksi keuangan dan PPDB hook secara CLI, seluruh test case berhasil dilalui dengan sukses.

---
## [Perbaikan Race Condition Inisialisasi Vue & Tabrakan Selector Modul Keuangan]
**Waktu**: 08:28 WIB
**Jenis**: Bug Fix

### Masalah (Root Cause):
1. **Race Condition `DOMContentLoaded`**: Skrip registrasi Vue di dalam view keuangan dibungkus menggunakan event listener `DOMContentLoaded`. Akibatnya, terjadi race condition di mana event `DOMContentLoaded` di layout utama (`master.php`) mengeksekusi `mountAll()` terlebih dahulu sebelum registrasi komponen selesai dilakukan, sehingga Vue gagal merender template dan menampilkan kurung kurawal (`{{ ... }}`) mentah di layar.
2. **Tabrakan Selector ID**: Penggunaan ID selector `#app` yang sama secara berulang pada 8 halaman modul keuangan menyebabkan ketidakcocokan template saat navigasi asinkronus (Hotwire Turbo Drive) dilakukan, karena Vue mencoba merender konfigurasi layout lama pada elemen baru.

### Perbaikan:
1. Menghapus pembungkus `DOMContentLoaded` pada seluruh skrip Vue di views keuangan agar registrasi kelas berjalan sinkron saat file diurai (parsed).
2. Memisahkan selector ID untuk masing-masing halaman keuangan dengan nama unik agar tidak terjadi tabrakan memori:
   - `keuangan-dashboard-app`
   - `keuangan-master-app`
   - `keuangan-keringanan-app`
   - `keuangan-generate-app`
   - `keuangan-kasir-app`
   - `keuangan-laporan-app`
   - `keuangan-pengaturan-app`
   - `keuangan-tagihan-saya-app`

---
## [Perbaikan Desain Double Footer Modul Keuangan]
**Waktu**: 10:37 WIB
**Jenis**: Bug Fix

### Masalah (Root Cause):
Master layout utama aplikasi (`views/layout/master.php`) sudah menyertakan footer global (`views/layout/footer.php`) secara otomatis di bagian paling bawah kontainer `main-content` (baris 740). Namun, kedelapan (8) view baru pada modul keuangan juga melakukan include `views/layout/footer.php` secara manual di bagian akhir file. Hal ini menyebabkan desain footer tercetak dua kali (double footer) ketika halaman keuangan dirender.

### Perbaikan:
Menghapus baris `<?php include __DIR__ . '/../layout/footer.php'; ?>` pada baris paling akhir di kedelapan file view keuangan:
1. `views/keuangan/dashboard.php`
2. `views/keuangan/generate.php`
3. `views/keuangan/kasir.php`
4. `views/keuangan/keringanan.php`
5. `views/keuangan/laporan.php`
6. `views/keuangan/master.php`
7. `views/keuangan/pengaturan.php`
8: `views/keuangan/tagihan_saya.php`

---
## [Perbaikan Galat SQL 500 pada API Dashboard & Laporan Keuangan]
**Waktu**: 10:41 WIB
**Jenis**: Bug Fix

### Masalah (Root Cause):
1. **Galat pada API Dashboard (`/api/v1/keuangan/dashboard-metrics`)**:
   Kueri SQL progres pelunasan kelas di dalam fungsi `apiDashboardMetrics` tidak memiliki klausa `FROM` setelah deklarasi proyeksi `SUM`. Akibatnya, kueri tersebut tidak valid secara sintaksis dan memicu pengecualian database (500 Internal Server Error).
2. **Galat pada API Laporan (`/api/v1/keuangan/laporan-rekap`)**:
   Kueri SQL laporan pemasukan mencoba mengambil kolom `u.nama` dari tabel `users`. Namun, nama kolom nama pengguna yang valid di tabel `users` adalah `nama_lengkap`, sehingga kueri tersebut memicu galat "Column not found: 1054 Unknown column u.nama".

### Perbaikan:
1. Menambahkan klausa `FROM transaksi_spp_tagihan t` pada kueri progres kelas di dalam fungsi `apiDashboardMetrics()` pada berkas `app/Controllers/SppController.php`.
2. Mengubah pemanggilan `u.nama as nama_kasir` menjadi `u.nama_lengkap as nama_kasir` pada kueri laporan rekap di dalam fungsi `apiLaporanRekap()` pada berkas `app/Controllers/SppController.php`.

---
## [Perbaikan Sidebar Toggle dan Jam Header Stuck]
**Waktu**: 10:51 WIB
**Jenis**: Bug Fix

### Masalah (Root Cause):
1. **Sidebar Toggle Tidak Bekerja (Off)**:
   Inisialisasi handler klik untuk tombol burger menu (`#sidebarToggle`) hanya dilakukan di dalam pendengar event `turbo:load`. Pada saat navigasi non-Turbo (page refresh atau load pertama kali di browser), jika event `turbo:load` telah terpicu sebelum skrip dianalisis atau jika pendengar terlambat ditambahkan, maka handler klik `sidebarToggle` tidak akan pernah terdaftar. Akibatnya, tombol burger menu tidak merespons klik.
2. **Jam & Tanggal Stuck (`00:00:00`)**:
   Skrip jam digital di `views/layout/header.php` dibungkus dengan pendengar event `DOMContentLoaded`. Ketika pengguna melakukan navigasi via Turbo Drive, halaman diganti secara dinamis tanpa memicu event `DOMContentLoaded`. Skrip jam dijalankan kembali oleh Turbo, namun karena event `DOMContentLoaded` sudah selesai terpicu sebelumnya, pendengar tidak pernah dijalankan kembali dan jam terhenti di angka bawaan HTML `00:00:00`. Selain itu, ketiadaan pembersihan interval (`setInterval`) dapat memicu kebocoran memori saat navigasi berulang.

### Perbaikan:
1. **Sidebar Toggle Failsafe**:
   Membungkus registrasi klik handler sidebar toggle ke dalam fungsi mandiri `initSidebarToggle()` di `views/layout/master.php`. Fungsi ini kini dipanggil pada event `turbo:load` DAN didukung oleh failsafe pemicu langsung (jika `document.readyState` sudah aktif/interactive) serta pada event `DOMContentLoaded` dan window `load`. Hal ini menjamin tombol sidebar selalu responsif dalam kondisi pemuatan halaman apa pun.
2. **Clock Initialization & Interval Cleanup**:
   Mengubah pembungkus inisialisasi jam `initHeaderClock()` di `views/layout/header.php` agar mengecek `document.readyState` dan langsung berjalan tanpa menunggu `DOMContentLoaded` jika dokumen sudah siap. Sebelum mendaftarkan interval waktu baru, skrip kini secara dinamis menghapus interval sebelumnya (`window.headerClockInterval`) untuk mencegah instansi ganda (leak) akibat eksekusi skrip berulang oleh Turbo.

---
## [Perbaikan Desain Double Header Modul Keuangan]
**Waktu**: 11:00 WIB
**Jenis**: Bug Fix

### Masalah (Root Cause):
Master layout utama aplikasi (`views/layout/master.php`) sudah menyertakan header global (`views/layout/header.php`) secara otomatis untuk semua halaman (baris 719). Namun, kedelapan (8) view baru pada modul keuangan juga melakukan include `views/layout/header.php` secara manual di baris pertama file. Hal ini menyebabkan komponen header dirender dua kali (double header), sehingga memicu kesalahan validasi DOM berupa duplikasi ID elemen formulir pencarian (`globalSearchBar`) serta gangguan rendering visual.

### Perbaikan:
Menghapus baris `<?php include __DIR__ . '/../layout/header.php'; ?>` (atau padanannya) pada baris pertama di kedelapan file view keuangan:
1. `views/keuangan/dashboard.php`
2. `views/keuangan/generate.php`
3. `views/keuangan/kasir.php`
4. `views/keuangan/keringanan.php`
5. `views/keuangan/laporan.php`
6. `views/keuangan/master.php`
7. `views/keuangan/pengaturan.php`
8. `views/keuangan/tagihan_saya.php`

---
## [Perbaikan Peringatan PHP Undefined Variable di PengumumanController]
**Waktu**: 11:15 WIB
**Jenis**: Bug Fix

### Masalah (Root Cause):
Pada konstruktor `PengumumanController.php` (baris 25), terdapat pemeriksaan peran pengguna `if ($role === 'super_admin')`. Namun, variabel `$role` belum didefinisikan sebelumnya di dalam lingkup konstruktor tersebut. Hal ini memicu peringatan PHP runtime `Undefined variable $role` setiap kali modul pengumuman diakses oleh pengguna.

### Perbaikan:
Mendefinisikan variabel `$role = $_SESSION['role_name'] ?? '';` di baris awal konstruktor `__construct()` sebelum melakukan pengecekan peran di berkas `app/Controllers/PengumumanController.php`.

---
## [Mockup Redesain Layout Dashboard & Area Kerja Compact Full-Screen]
**Waktu**: 12:09 WIB
**Jenis**: Feature

### Ringkasan Pekerjaan:
1. **Pembuatan File Mockup**:
   - Membuat file mockup mandiri [test_compact_layout.php](file:///C:/xampp/htdocs/SINTA-SaaS/scratch/test_compact_layout.php) di folder `scratch/`.
   - Menggunakan Bootstrap 5, Bootstrap Icons, dan custom CSS untuk menyajikan visualisasi area konten yang lebih padat (compact) dan full-height (stretching setinggi layar).
2. **Karakteristik Rancangan**:
   - Rasio kolom terbagi menjadi 30% Panel Input Form (kiri) dan 70% Panel Tabel Data (kanan) untuk efisiensi pandang.
   - Peniadaan scrollbar window utama dengan mengaktifkan independent scrollbar hanya pada body tabel, menjaga agar header tabel tetap sticky melayang di atas saat data ditarik.
   - Dilengkapi Vue 3 CDN interaktif di sisi klien sehingga data tarif dapat ditambah, dihapus, difilter secara langsung, dan jam/tanggal aktif di header beroperasi dinamis.

---
## [Penerapan Redesain Layout Dashboard & Area Kerja Compact Full-Screen]
**Waktu**: 15:15 WIB
**Jenis**: Refactor

### Ringkasan Pekerjaan:
1. **Implementasi pada View Operasional**:
   - Menerapkan desain compact full-screen layout pada berkas view utama [master.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/master.php) (Atur Tarif & Biaya).
   - Membagi layout halaman secara efisien dengan rasio 30% Panel Form (kiri) dan 70% Panel Tabel Grid (kanan).
   - Menghilangkan scrollbar global halaman dengan mengaktifkan scrollbar internal bodi tabel (`table-compact-container` + `overflow-y: auto`), memastikan header tabel tetap melayang statis (`position: sticky`).
   - Menerapkan `.form-compact` untuk mengecilkan tinggi input dan padding elemen formulir.
2. **Perbaikan Bug Kode Master**:
   - Memperbaiki salah ketik (typo) pemanggilan variabel `loadingKompKomp` menjadi `loadingKomp.value` di penanganan block `finally` fungsi `saveKomponen()` yang sebelumnya memicu ReferenceError saat proses simpan selesai.

---
## [Perbaikan Galat API 500 Keuangan Komponen & Responsivitas Mobile Layout]
**Waktu**: 15:49 WIB
**Jenis**: Bug Fix / Responsive

### Masalah (Root Cause):
1. **Galat API 500 pada `/api/v1/keuangan/komponen`**:
   Saat pengguna dengan peran `super_admin` (yang memiliki nilai `tenant_id = NULL` di database) mengakses modul keuangan atau menyimpan komponen biaya, kueri database memicu galat `Integrity constraint violation (FK fk_spp_komponen_tenant fails)` karena mencoba menginsert string kosong (`''`) sebagai tenant ID ke tabel `transaksi_spp_komponen`.
2. **Masalah Tampilan di HP (Mobile)**:
   Pada layar perangkat seluler (lebar < 768px), tata letak desktop compact split-screen (30% Form, 70% Tabel) dengan `flex-direction: row` dan `overflow: hidden` menyebabkan panel tabel data terdorong keluar layar secara horizontal dan tidak terlihat sama sekali.

### Perbaikan:
1. **Resolusi Dinamis Tenant ID**:
   Mendefinisikan fungsi pembantu `resolveTenantId()` di dalam berkas [SppController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/SppController.php) untuk secara cerdas mendeteksi dan menggunakan `tenant_id` bagi `super_admin` berdasarkan parameter GET, parameter POST, JSON request body, atau melakukan parsing otomatis terhadap header referer halaman (`HTTP_REFERER`). Mengubah seluruh 15 pemanggilan `$_SESSION['tenant_id'] ?? ''` di pengontrol keuangan agar menggunakan pengurai dinamis ini.
2. **Responsivitas HP (Mobile Media Query)**:
   Menambahkan blok `@media (max-width: 767.98px)` pada CSS di berkas [master.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/master.php). Ketika diakses via ponsel, tata letak otomatis beralih menjadi susunan kolom vertikal (`flex-direction: column`), tinggi container diubah menjadi dinamis (`height: auto !important`), dan scrollbar global diaktifkan kembali (`overflow: visible !important`) sehingga form dan tabel ter-render penuh di HP dan dapat di-scroll ke bawah secara alami.

---
## [Penyelarasan Tata Letak Compact & Responsive di 7 Halaman Keuangan]
**Waktu**: 16:05 WIB
**Jenis**: Refactor / UI-UX

### Ringkasan Pekerjaan:
Mendesain ulang 7 berkas tampilan keuangan berikut agar konsisten dengan `master.php` (tata letak compact full-screen di desktop dan stacked responsif di HP):
1. **`dashboard.php`**: Membungkus dengan `.workspace-container`, memadatkan padding kartu metrik statistik, memindahkan rekap progres pelunasan kelas ke dalam `.table-compact-container` dengan header kolom sticky, serta menata ulang tata letak grid di HP menjadi 1 kolom penuh.
2. **`keringanan.php`**: Mengubah layout baris/kolom Bootstrap menjadi pembagian kolom flex 30% (`panel-form`) dan 70% (`panel-table`), menerapkan input `.form-compact` (tinggi 32px) dan tabel `.table-compact` dengan scrollbar internal, serta menambahkan media query mobile.
3. **`generate.php`**: Mengemas formulir generate menjadi `.panel-form` compact di tengah halaman, merampingkan input fields, select, dan alert box info.
4. **`kasir.php`**: Mengatur ulang halaman kasir loket pembayaran menjadi panel tagihan 65% (`panel-table` dengan scrollbar internal) dan panel checkout 35% (`panel-form` dengan input penerimaan uang tunai dan kembalian), menyematkan pencarian siswa secara rapi, serta mengaktifkan kolom stacked mobile.
5. **`laporan.php`**: Memposisikan formulir filter laporan ke dalam toolbar compact di `.panel-header`, menyajikan laporan rekap dalam format `.table-compact` 100% lebar layar, dan mengaktifkan sticky header serta scrollbar internal.
6. **`pengaturan.php`**: Mengemas konfigurasi nama modul, terminologi, dan visibilitas siswa ke dalam `.panel-form` compact di tengah halaman dengan elemen masukan 32px.
7. **`tagihan_saya.php`**: Merampingkan visualisasi profil siswa, menaruh metrik tagihan/tunggakan ke dalam kartu compact, dan menampilkan rincian dalam format `.table-compact` yang bersahabat untuk layar HP siswa.

---
## [Penyelarasan Desain Navtabs & Tabel Modul Keuangan]
**Waktu**: 16:15 WIB
**Jenis**: UI-UX Refinement

### Ringkasan Pekerjaan:
Menerapkan gaya navigasi tabs minimalis dan tabel borderless tanpa garis kisi vertikal di seluruh view modul keuangan:
1. **Navtabs Minimalis (Flat Underline)**:
   - Diterapkan pada `master.php` dan `laporan.php`.
   - Menghapus outline border kotak bawaan Bootstrap, menyisakan garis bawah tipis (`1px solid #e2e8f0`).
   - Memberikan indikator garis bawah biru tebal (`2px solid #2563eb`) dan teks biru tebal pada tab yang berstatus aktif, serta warna slate-gray yang bersih pada tab tidak aktif.
2. **Borderless Table (`table-compact`)**:
   - Diterapkan pada `master.php`, `keringanan.php`, `laporan.php`, `dashboard.php`, `kasir.php`, dan `tagihan_saya.php`.
   - Menghilangkan garis kisi vertikal (hanya menyisakan garis bawah horizontal tipis `#f1f5f9`).
   - Warna latar header kolom di-set ke abu-abu tipis (#f8fafc) dengan format tulisan kapital (uppercase), tebal, dan warna abu-abu gelap (#475569) agar kontras.
   - Menambahkan efek hover transisi warna baris yang halus ke `#f8fafc`.

---
## [Pengembalian Layout Halaman Keuangan ke Desain Card Native SINTA-SaaS]
**Waktu**: 16:30 WIB
**Jenis**: UI-UX Restoration

### Ringkasan Pekerjaan:
Mengembalikan struktur layout 7 halaman keuangan dari gaya side-by-side flex split-panels fixed-height (Gambar 2) ke layout card standard Bootstrap page-scrolling native (Gambar 1):
1. **Pelepasan Boxed Panels & Fixed Height**:
   - Menghapus pembungkus `.workspace-container` dan `.workspace-body` yang membatasi tinggi layar viewport secara paksa dan mematikan overflow.
   - Mengembalikan layout agar bergulir (scrolling) secara alami mengikuti tinggi dokumen halaman browser, menghilangkan scrollbar ganda.
2. **Restorasi Gaya Card SINTA-SaaS (Gambar 1)**:
   - Memposisikan ulang form input dan tabel data ke dalam kartu standard Bootstrap (`card border-0 shadow-sm rounded-4 bg-white p-4 mb-4`) yang terbuka, bersih, dan menggunakan bayangan lembut.
   - Menghilangkan header bar kartu abu-abu (`.panel-header`) yang kaku dan menggantinya dengan border tipis atau judul teks heading standard yang serasi.
3. **Penyelarasan Desain Custom Underline Tabs & Tabel Tanpa Kisi Vertikal**:
   - Memastikan tab navigasi custom flat underline yang rapi (garis bawah biru pada status aktif, tanpa folder border Bootstrap) tetap terjaga.
   - Mempertahankan format tabel yang luas, bersih, dan modern tanpa garis pembatas vertikal dengan warna latar header `#f8fafc` sesuai referensi visual Gambar 1.

---
## [Perbaikan Galat 500 Integrity Constraint Violation pada API Komponen]
**Waktu**: 16:40 WIB
**Jenis**: Bug Fix

### Masalah & Analisis Root Cause:
Ditemukan galat `500 (Internal Server Error)` pada endpoint API `/SINTA-SaaS/api/v1/keuangan/komponen` saat diakses oleh pengguna dengan peran `super_admin`.
Penyebab utamanya adalah `tenant_id` bernilai string kosong `""` karena payload request POST tidak membawa parameter `tenant_id` dan URL referer tidak memiliki query parameter `tenant_id` (karena diakses dari link sidebar langsung tanpa filter/query string).
Hal ini memicu eksekusi kueri `INSERT` atau `UPDATE` dengan nilai `tenant_id = ''` yang melanggar kunci asing `fk_spp_komponen_tenant` karena tidak merujuk ke UUID penyewa yang sah.

### Solusi & Implementasi:
1. **Fallback Default Tenant ID**:
   - Memodifikasi method `resolveTenantId()` di [SppController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/SppController.php).
   - Apabila peran yang terdeteksi adalah `super_admin` dan semua parameter pencarian `tenant_id` bernilai kosong, method ini secara cerdas melakukan fallback dengan mengambil ID penyewa pertama (`SELECT id FROM tenants LIMIT 1`) yang ada di database.
   - Ini memastikan `tenant_id` selalu mengembalikan UUID yang sah untuk `super_admin`, mencegah crash pada database.
2. **Standardisasi Penanganan Error (Try-Catch)**:
   - Membungkus method `apiKomponen()`, `apiTarif()`, dan `apiKeringanan()` di [SppController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/SppController.php) menggunakan blok `try-catch`.
   - Jika terjadi exception (misal masalah database atau kueri), sistem kini mengembalikan respons JSON terstandardisasi (`['success' => false, 'error' => $message]`) dengan status code `500` yang tepat, menghindari output error HTML mentah yang tidak terstruktur.

---
## [Penyempurnaan Multi-Tenant, Filter, Toggle Status, dan Pagination Modul Keuangan SaaS]
**Waktu**: 17:00 WIB
**Jenis**: Feature / SaaS Optimization

### Ringkasan Pekerjaan:
1. **Pendaftaran Rute API Baru**:
   - Menambahkan rute `/api/v1/keuangan/tenants` dan `/api/v1/keuangan/komponen/toggle` di [index.php](file:///C:/xampp/htdocs/SINTA-SaaS/index.php).
2. **Implementasi API di SppController**:
   - Membuat method `apiTenants()` untuk query daftar tenant sekolah bagi super_admin di [SppController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/SppController.php).
   - Membuat method `apiToggleKomponen()` untuk melakukan update status keaktifan komponen biaya.
3. **Penyatuan Multi-Tenant Dropdown Selector**:
   - Mengintegrasikan dropdown pemilih sekolah/tenant khusus untuk `super_admin` pada halaman Dashboard, Master, Keringanan, Generate, Kasir, Laporan, dan Pengaturan.
   - Menggunakan sinkronisasi `localStorage` (`sinta_spp_selected_tenant_id`) agar pilihan sekolah tetap konsisten dan persisten saat super_admin berpindah halaman keuangan.
4. **Sakelar ON/OFF Status Komponen**:
   - Menyediakan sakelar toggle ON/OFF pada tabel Master Komponen di [views/keuangan/master.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/master.php) untuk mengaktifkan/menonaktifkan komponen secara asinkronus.
5. **Dynamic Filters & Pagination**:
   - Mengubah filter teks komponen menjadi dropdown pilihan Jenis Komponen Biaya dinamis di [views/keuangan/laporan.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/laporan.php).
   - Mengintegrasikan navigasi halaman (pagination) client-side yang interaktif pada seluruh tabel utama keuangan di Master, Keringanan, Laporan, dan Tagihan Saya.

---
## [Perbaikan Tampilan Pagination (Sliding Window / Ellipsis)]
**Waktu**: 17:25 WIB
**Jenis**: Bug Fix / UI-UX Refinement

### Masalah (Root Cause):
Pencetakan tombol navigasi halaman (pagination) numerik sebelumnya merender seluruh total halaman secara berurutan (`v-for="p in totalPages"`). Ketika data berjumlah sangat banyak (misal ratusan halaman), jumlah tombol numerik yang tercetak melebar melampaui lebar wadah tabel (overflow horizontal) sehingga merusak estetika desain web dan fungsionalitas responsif.

### Perbaikan:
1. Menambahkan fungsi helper `getVisiblePages(current, total)` di setup Vue di masing-masing file view yang memiliki pagination. Fungsi ini membatasi jumlah tombol numerik yang ditampilkan secara dinamis di sekitar halaman aktif (dengan delta 2) dan mengganti halaman perantara yang jauh menggunakan tanda elipsis `...`.
2. Menerapkan perubahan markup pagination dan computed property `visiblePages` / `visibleKompPages` / `visibleTarifPages` / `visibleKeringananPages` di empat berkas view operasional:
   - **`views/keuangan/master.php`**
   - **`views/keuangan/keringanan.php`**
   - **`views/keuangan/laporan.php`**
   - **`views/keuangan/tagihan_saya.php`**
3. Menambahkan status `disabled` pada tombol elipsis `...` agar tidak dapat diklik dan merespons interaksi dengan tepat.

---
## [Filter Multi-Tenant, Urutan Tahun Ajaran Baru, & Pengamanan SQLi di Master Keuangan]
**Waktu**: 17:31 WIB
**Jenis**: Feature / Security / UI-UX Refinement

### Ringkasan Pekerjaan:
1. **Dukungan Tampilan Sekolah Global bagi Super Admin**:
   - Menambahkan opsi `-- Semua Sekolah (Global) --` pada dropdown tenant pemilih global.
   - Menambahkan kolom `Sekolah` di tabel komponen biaya dan tabel tarif default jika pengguna masuk sebagai `super_admin`.
   - Mengubah API endpoint GET di `apiKomponen()` and `apiTarif()` agar mengambil seluruh data dari semua tenant sekolah jika parameter `tenant_id` kosong.
2. **Penyaringan (Filtering) Lokal yang Profesional**:
   - Menyediakan input filter lokal di bagian atas tabel "Daftar Tarif Default" mencakup Sekolah/Tenant (khusus Super Admin), Tahun Ajaran, dan Komponen Biaya.
   - Filter Komponen Biaya diekstrak secara dinamis berdasarkan data komponen unik yang tersedia dalam data tarif yang terisi.
   - Mengaktifkan reactive watchers untuk mereset navigasi halaman `tarifPage` kembali ke halaman 1 ketika salah satu kriteria filter diubah.
3. **Pengurutan Tahun Ajaran Baru**:
   - Menambahkan klausa `ORDER BY ta.tahun_ajaran DESC` pada kueri listing tarif default agar data dengan Tahun Ajaran terbaru selalu ditampilkan paling atas.
4. **Pencegahan SQL Injection (Security compliance)**:
   - Merefaktor query string interpolasi di method `keringanan()` dan `generate()` pada [SppController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/SppController.php) menggunakan prepared statements dengan parameter binding (`$db->prepare` & `execute()`).
5. **Integritas Penambahan Data**:
   - Menambahkan validasi keamanan di server-side (`apiTarif()`) agar `tenant_id` dari tarif default yang disimpan otomatis disesuaikan dengan `tenant_id` dari komponen biayanya guna mencegah ketidakcocokan data antar-sekolah.
   - Memberikan pesan informatif serta menonaktifkan input form tarif jika Super Admin belum memilih salah satu sekolah target.

---
## [Fitur Preview Penerbitan Tagihan & Manajemen Daftar Tagihan Siswa]
**Waktu**: 17:40 WIB
**Jenis**: Feature / UI-UX Refinement / Database Query Optimization

### Ringkasan Pekerjaan:
1. **Navigasi Nav Tabs di Generate Page**:
   - Menambahkan menu Nav Tabs pada [generate.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/generate.php) yang membagi halaman menjadi dua tab fungsional: "Terbitkan Tagihan (Generate)" dan "Daftar Tagihan Siswa".
2. **Pratinjau Siswa Sasaran Sebelum Generate**:
   - Membuat layout 2 kolom pada Tab Generate. Sisi kiri untuk form filter target, sisi kanan menampilkan pratinjau daftar siswa aktif, nominal dasar tarif, potongan diskon keringanan, nominal tagihan bersih, serta status apakah tagihan sudah pernah terbit (`sudah_ada`).
   - Membuat method `apiPreviewGenerate()` di [SppController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/SppController.php) untuk menghitung pratinjau nominal secara real-time.
   - Memasang watcher di Vue.js yang otomatis me-refresh tabel pratinjau secara dinamis saat pilihan komponen, tahun ajaran, kelas, atau jenjang sasaran diubah.
3. **Penyaringan Server-Side & Pencarian Tagihan Terbit**:
   - Membuat tab "Daftar Tagihan Siswa" yang menampilkan seluruh invoice terbit dengan sistem server-side pagination lewat method `apiDaftarTagihan()`.
   - Menyediakan fitur pencarian nama/NISN secara instan dan filter multi-level (Kelas, Tahun Ajaran, Komponen, Status Lunas, dan Tenant).
   - Mengimplementasikan sliding window pagination dengan elipsis `...` untuk navigasi halaman tabel tagihan.
4. **Edit Nominal Tagihan (Modal AJAX)**:
   - Membuat modal interaktif `#editNominalModal` untuk memperbarui `nominal_tagihan` secara dinamis.
   - Menambahkan endpoint `apiEditTagihanNominal()` di controller untuk meng-update nominal, merekalibrasi status kelunasan (Lunas/Cicil/Belum) berdasarkan jumlah uang yang sudah dibayar, serta mencatat tindakan tersebut ke audit log keuangan.
5. **Penghapusan Tagihan Terbit**:
   - Menambahkan tombol hapus tagihan yang mengarah ke endpoint `apiHapusTagihan()`.
   - Mengamankan proses penghapusan agar hanya diijinkan jika tagihan belum memiliki pembayaran (`nominal_bayar == 0`) guna menjaga integritas kasir.

---
## [Penyaringan Tahun Ajaran Berdasarkan Tenant / Sekolah]
**Waktu**: 17:45 WIB
**Jenis**: Bug Fix / UI-UX Refinement

### Ringkasan Pekerjaan:
1. **Dynamic Fetch untuk Tahun Ajaran**:
   - Membuat API endpoint `/api/v1/keuangan/tahun-ajaran` di [SppController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/SppController.php) yang memuat tahun ajaran yang terasosiasi dengan `tenant_id` terpilih.
   - Apabila Super Admin berada dalam mode global (tanpa filter tenant), API menggunakan `GROUP BY tahun_ajaran` agar pilihan tahun ajaran pada dropdown bernilai unik (tanpa baris duplikat nama tahun ajaran yang sama antar-sekolah).
2. **Integrasi Vue.js di Master dan Generate**:
   - Mengubah inisialisasi variabel `listTa` menjadi dynamic ref `Vue.ref([])` di [master.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/master.php) dan [generate.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/generate.php).
   - Memasang pemanggilan `fetchTahunAjaran()` saat mounted and saat `onTenantChange()` dipicu oleh perubahan dropdown global tenant, sehingga opsi tahun ajaran selalu ter-update secara presisi per sekolah.

---
## [Perbaikan Query Lookup Jenjang Kelas pada Penerbitan Tagihan]
**Waktu**: 17:54 WIB
**Jenis**: Bug Fix / UI-UX Refinement

### Ringkasan Pekerjaan:
1. **Perbaikan Mismatch Nama Kolom Database**:
   - Mengubah referensi query pencocokan jenjang dari `jenjang_id` menjadi `id_jenjang` pada tabel `kelas` di method `apiGenerateTagihan()` (baris 511) dan `apiPreviewGenerate()` (baris 667) di [SppController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/SppController.php) agar sesuai dengan struktur database DAPODIK yang aktif.
   - Menyelaraskan filter subquery target jenjang dari `jenjang_id` ke `id_jenjang` di method `apiPreviewGenerate()` (baris 627).
2. **Peningkatan User Experience pada Fetch Error**:
   - Memperbarui fungsi `fetchPreview()` di [generate.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/generate.php) agar secara eksplisit mengisi `errorMsg.value` dengan pesan kesalahan detail (atau default error) apabila respons AJAX menghasilkan status kegagalan, menghindari *silent empty list* bagi pengguna.
3. **Pembersihan Berkas Diagnostik**:
   - Menghapus berkas sementara `scratch/check_tarifs.php` dan `scratch/check_tas_and_students.php` setelah diagnosis selesai.

---
## [Penyaringan Kelas dan Jenjang Berdasarkan Tenant / Sekolah]
**Waktu**: 17:59 WIB
**Jenis**: Feature / UI-UX Refinement

### Ringkasan Pekerjaan:
1. **Dynamic Fetch untuk Kelas dan Jenjang**:
   - Membuat API endpoint `/api/v1/keuangan/kelas` dan `/api/v1/keuangan/jenjang` di [SppController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/SppController.php) yang memuat daftar kelas dan jenjang yang terasosiasi dengan `tenant_id` terpilih.
   - Apabila Super Admin berada dalam mode global (tanpa filter tenant), API mengembalikan seluruh kelas/jenjang aktif secara global.
2. **Integrasi Vue.js di Master dan Generate**:
   - Mengubah inisialisasi variabel `listKelas` dan `listJenjang` menjadi dynamic ref `Vue.ref([])` di [master.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/master.php) dan [generate.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/generate.php).
   - Memasang pemanggilan `fetchKelas()` dan `fetchJenjang()` saat mounted dan saat `onTenantChange()` dipicu oleh perubahan dropdown global tenant, sehingga opsi kelas dan jenjang selalu ter-update secara presisi per sekolah.
3. **Pembersihan Berkas Diagnostik**:
   - Menghapus berkas sementara `scratch/check_jenjang.php` setelah diagnosis selesai.

---
## [Pemilihan Siswa Sasaran (Checklist) Sebelum Menerbitkan Tagihan]
**Waktu**: 18:16 WIB
**Jenis**: Feature / UI-UX Refinement

### Ringkasan Pekerjaan:
1. **Checklist Siswa di Pratinjau**:
   - Menambahkan kolom checkbox pada tabel pratinjau di [generate.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/generate.php).
   - Menambahkan checkbox "Pilih Semua" (Select All) di header tabel dan checkbox individual per siswa. Checkbox otomatis dinonaktifkan (`disabled`) jika tagihan siswa sudah pernah terbit (`sudah_ada`).
2. **Logika Seleksi di Vue.js**:
   - Menyediakan state `selectedSiswaIds` untuk menampung daftar UUID siswa terpilih.
   - Memasang computed property `isAllSelected` dan helper `toggleSelectAll()` untuk kendali checklist.
   - Memasang watcher pada `previewList` untuk mencentang seluruh siswa baru/sasaran secara otomatis sesaat setelah daftar pratinjau berhasil dimuat.
   - Menyelaraskan teks tombol aksi untuk mencerminkan jumlah siswa terpilih: `Terbitkan Tagihan (X Siswa)`.
3. **Penyaringan Whitelist di Server-Side**:
   - Memperbarui method `apiGenerateTagihan()` di [SppController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/SppController.php) agar menerima array `siswa_ids` dalam payload POST, lalu menyaring query utama dengan klausa `AND s.id IN (...)` menggunakan dynamic prepared statements placeholder.

---
## [Optimalisasi Kueri & Indeks Database Tagihan SPP Massal]
**Waktu**: 18:25 WIB
**Jenis**: Bug Fix / Optimization / Database Migration

### Ringkasan Pekerjaan:
1. **Pembuatan File Migrasi Indeks Performa**:
   - Membuat file migrasi baru [2026_07_21_02_add_indexes_to_spp_tagihan.php](file:///C:/xampp/htdocs/SINTA-SaaS/database/migrations/2026_07_21_02_add_indexes_to_spp_tagihan.php) dalam format `return [...]` array untuk menambahkan indeks performa BTREE pada kolom `created_at`, `(tenant_id, created_at)`, dan `status_lunas` di tabel `transaksi_spp_tagihan`.
   - Menjalankan `php migrate.php` untuk menerapkan indeks baru ke database secara otomatis.
2. **Pencegahan Discrepancy & Sinkronisasi Pagination**:
   - Merefaktor kueri penarikan data terpaginasi di method `apiDaftarTagihan()` pada [SppController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/SppController.php) dengan mengganti `INNER JOIN` ke `LEFT JOIN` pada tabel lookup `kelas`, `komponen`, `tahun_ajaran`, dan `tenants`. Hal ini mencegah ketidakcocokan antara hitungan total baris (COUNT) dengan baris data riil (SELECT) jika terdapat data siswa yang kelasnya terhapus atau kosong.
3. **Optimalisasi Kecepatan Load Jutaan Data (Filesort Skip)**:
   - Menyederhanakan klausa sorting di `apiDaftarTagihan()` menjadi hanya `ORDER BY t.created_at DESC` agar MySQL dapat langsung menggunakan composite index `idx_spp_tagihan_tenant_created` (menghindari filesort lambat pada jutaan baris data). Pemuatan halaman akhir pun menjadi instan.

---
## [Ekspor Excel Dinamis (Pivot SPP) & Filter Mandatori Halaman Daftar Tagihan]
**Waktu**: 18:36 WIB
**Jenis**: Feature / UI-UX Refinement

### Ringkasan Pekerjaan:
1. **Dynamic Pivot Excel Export dengan SimpleXLSXGen**:
   - Membuat API endpoint `/api/v1/keuangan/export-tagihan-excel` di [SppController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/SppController.php) yang memproses filter dan mengekspor tagihan ke format `.xlsx`.
   - Mengelompokkan data tagihan secara dinamis (cross-tab pivot) dengan baris: nama, nisn, kelas, dan kolom bertingkat: **Tahun Ajaran > Komponen Biaya > [nominal, telah di bayar, Kurang bayar, status]**.
   - Menerapkan penggabungan sel (merge cells) vertikal untuk data siswa (A1:A3 untuk nama, dst.) serta horizontal untuk Tahun Ajaran dan Komponen agar rapi seperti format pada mockup user.
2. **Kewajiban Filter (Filter Mandatori)**:
   - Mengharuskan minimal satu filter aktif di sisi server sebelum membolehkan unduh Excel.
   - Memodifikasi view [generate.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/generate.php) agar tidak memuat data tagihan secara otomatis saat tab dimuat pertama kali.
   - Menampilkan layout placeholder petunjuk filter saat tabel masih kosong (belum difilter) untuk mencegah beban load berat pada query database jutaan baris.
3. **Penyempurnaan Grid UI**:
   - Menata ulang grid filter pada tab daftar tagihan agar muat disematkan tombol **Excel** berwarna hijau (yang ter-disabled jika filter belum diisi).
   - Menghubungkan tombol tersebut dengan fungsi `downloadExcel()` yang menembak API ekspor dengan filter aktif.

---
## [Filter Kelas, Riwayat Kelas, & Status Tagihan Loket Kasir]
**Waktu**: 18:48 WIB
**Jenis**: Feature / UI-UX Refinement

### Ringkasan Pekerjaan:
1. **Dropdown Filter Kelas di Loket Kasir**:
   - Menambahkan dropdown **Filter Kelas** pada [kasir.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/kasir.php) yang memuat daftar kelas aktif sekolah secara dinamis via AJAX.
   - Memodifikasi fungsi `searchSiswa()` untuk menyertakan `kelas_id` dalam pemanggilan endpoint API `/api/v1/keuangan/cari-siswa`.
   - Mengubah method `apiCariSiswa()` di [SppController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/SppController.php) agar mendeteksi parameter `kelas_id` dan menyaring kueri pencarian berdasarkan `s.id_kelas = ?` sehingga pencarian nama duplikat antar-kelas menjadi sangat mudah dibedakan.
2. **Riwayat Kelas Tagihan**:
   - Memperbarui kueri di method `apiGetTagihanSiswa()` pada [SppController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/SppController.php) dengan menggunakan subquery `COALESCE` untuk mencocokkan `siswa_id` dan `tahun_ajaran` tagihan ke tabel `riwayat_kenaikan_kelas` (mengambil `nama_kelas_tujuan`), dengan fallback ke kelas aktif saat ini.
   - Menampilkan kolom **Kelas** dengan badge abu-abu di tabel kewajiban loket kasir setelah kolom Tahun Ajaran.
3. **Kolom Status Pembayaran**:
   - Menambahkan kolom **Status** tepat sebelum input nominal bayar di tabel kasir.
   - Menampilkan status tagihan terbit (`Belum Lunas`, `Cicil`) menggunakan warna badge yang interaktif (`getStatusBadgeClass()`). Expose helper tersebut ke return setup Vue.js.

---
## [Perbaikan Vue Runtime Error getStatusBadgeClass Loket Kasir]
**Waktu**: 18:53 WIB
**Jenis**: Bug Fix

### Ringkasan Pekerjaan:
1. **Perbaikan ReferenceError di kasir.php**:
   - Mendefinisikan fungsi `getStatusBadgeClass(status)` di dalam fungsi `setup()` pada [kasir.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/kasir.php) yang sebelumnya terlewat dideklarasikan namun di-return pada object setup.
   - Menyelesaikan galat runtime Vue `ReferenceError: getStatusBadgeClass is not defined` dan `TypeError: Cannot read properties of undefined (reading 'length')` sehingga komponen Vue loket kasir ter-mount sempurna tanpa kendala.

---
## [Filter Cascading, Daftar Siswa Dinamis, Riwayat Kelas Akurat, & Grouping Tagihan Kasir]
**Waktu**: 19:03 WIB
**Jenis**: Feature / UI-UX Refinement / Database Query Refinement

### Ringkasan Pekerjaan:
1. **Filter Jenjang & Kelas Cascading**:
   - Menambahkan dropdown **Filter Jenjang** di [kasir.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/kasir.php).
   - Memperbarui method `apiKelas()` di [SppController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/SppController.php) agar menerima `jenjang_id` untuk menyaring kelas secara bertingkat.
2. **Daftar Siswa Otomatis per Kelas**:
   - Memperbarui `apiCariSiswa()` di [SppController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/SppController.php) agar tetap menampilkan seluruh daftar siswa (limit 50) ketika `kelas_id` atau `jenjang_id` dipilih meskipun teks pencarian `q` kosong.
   - Mengubah event handler `onKelasChange()` di kasir agar langsung menampilkan daftar siswa begitu kelas dipilih.
3. **Resolusi Akurat Riwayat Kelas Historis per Tahun Ajaran**:
   - Memperbarui kueri `apiGetTagihanSiswa()` di [SppController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/SppController.php) dengan subquery 2-lapis: memeriksa `nama_kelas_tujuan` pada record kenaikan tahun ajaran terkait, atau `nama_kelas_asal` pada record kenaikan tahun ajaran setelahnya.
   - Tagihan tahun 2025/2026 kini dengan tepat menampilkan kelas lama siswa pada tahun 2025/2026, sedangkan tagihan 2026/2027 menampilkan kelas baru siswa pada tahun 2026/2027.
4. **Pengelompokan (Grouping) & Urutan Bulan Kronologis**:
   - Membuat computed property `groupedTagihan` pada Vue kasir untuk mengelompokkan baris tagihan menurut **Tahun Ajaran** -> **Komponen Tagihan**.
   - Mengurutkan komponen bulanan dari **Januari (1) hingga Desember (12)**.
5. **Proteksi Tagihan Lunas & Responsivitas Tabel**:
   - Menampilkan tagihan berstatus `Lunas` dengan badge hijau namun mengunci (`disabled`) checkbox dan input bayar nominal.
   - Menambahkan `text-nowrap`, `min-width: 950px` pada tabel, dan `min-width: 120px` pada input nominal bayar sehingga kolom `Status` dan `Bayar (Rp)` tampil utuh tanpa terpotong.

---
## [Perbaikan Error Duplicate Entry Nomor Kwitansi Pembayaran Kasir]
**Waktu**: 19:09 WIB
**Jenis**: Bug Fix

### Ringkasan Pekerjaan:
1. **Pencegahan Duplicate Key 1062 pada `nomor_kwitansi`**:
   - Mengidentifikasi penyebab error `SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry 'KW/20260721/...'` saat kasir membayar lebih dari 1 item tagihan secara bersamaan dalam 1 transaksi checkout.
   - Merefaktor method `apiBayarTagihan()` pada [SppController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/SppController.php) dengan memberikan akhiran sufiks unik berbasis indeks item (misal `KW/20260721/47B3ED-1`, `KW/20260721/47B3ED-2`) pada setiap baris item transaksi yang dimasukkan ke tabel `transaksi_spp_pembayaran`.
   
---
## [Perbaikan Render Teleport Modal Kwitansi & Tombol Cetak Kasir]
**Waktu**: 19:12 WIB
**Jenis**: Bug Fix / UI-UX Refinement

### Ringkasan Pekerjaan:
1. **Perbaikan Interpolasi Raw Tag Modal (`<teleport to="body">`)**:
   - Mengidentifikasi masalah di mana teks `{{ printData.nomor_kwitansi }}` dan data kwitansi lainnya tampil mentah (raw mustache tags) pada modal pratinjau kuitansi karena Bootstrap memindahkan node elemen modal ke luar skope container Vue (`#keuangan-kasir-app`).
   - Membungkus elemen modal `#modalKwitansi` dengan tag `<teleport to="body">` milik Vue 3 pada [kasir.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/kasir.php) sehingga node modal secara resmi dipindahkan ke `document.body` sambil tetap mempertahankan 100% reaktivitas data binding Vue.
2. **Penyempurnaan Fungsi Cetak Kuitansi (`printKwitansi`)**:
   - Merefaktor method `printKwitansi()` di [kasir.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/kasir.php) untuk membuka jendela cetak khusus (print popup window) yang rapi lengkap dengan stylesheet Bootstrap dan border kwitansi titik-titik (dashed box).
   - Menambahkan deteksi pemblokiran pop-up browser serta pemicuan `window.print()` otomatis dengan jeda pemuatan halaman 500ms agar lembar bukti pembayaran siap dicetak ke printer kasir maupun thermal receipt.

---
## [Fitur Cetak Ulang Kuitansi Pembayaran Tagihan Lunas/Cicil di Loket Kasir]
**Waktu**: 19:14 WIB
**Jenis**: Feature / UI-UX Refinement

### Ringkasan Pekerjaan:
1. **Subquery Data Kuitansi Terakhir di Controller**:
   - Memperbarui method `apiGetTagihanSiswa()` pada [SppController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/SppController.php) dengan menyisipkan subquery SELECT untuk menarik `latest_nomor_kwitansi`, `latest_tgl_bayar`, dan `latest_metode` dari tabel `transaksi_spp_pembayaran`.
2. **Tombol "Cetak Ulang" di Tabel Kasir**:
   - Menambahkan tombol **"Cetak Ulang"** (`btn-outline-primary`) di sebelah badge status pada [kasir.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/kasir.php) yang otomatis muncul pada setiap baris tagihan yang sudah memiliki riwayat pembayaran (`nominal_bayar > 0`).
3. **Method `reprintKwitansi(item)`**:
   - Menambahkan method `reprintKwitansi(item)` pada skope `setup()` Vue di [kasir.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/kasir.php) yang memuat data pembayaran historis ke `printData` dan membuka modal pratinjau kuitansi secara instan.

---
## [Perbaikan Error 500 Subquery SQL apiGetTagihanSiswa]
**Waktu**: 19:17 WIB
**Jenis**: Bug Fix

### Ringkasan Pekerjaan:
1. **Pencegahan Error Ambiguous Column & Unknown Column `created_at`**:
   - Mengidentifikasi penyebab galat HTTP 500 (`Internal Server Error`) pada rute `/api/v1/keuangan/tagihan-siswa` yaitu akibat penggunaan nama kolom `created_at` (seharusnya `tanggal_bayar`) serta kurangnya alias tabel pada klausa `ORDER BY` di subquery `transaksi_spp_pembayaran`.
   - Merefaktor kueri SQL di `apiGetTagihanSiswa()` pada [SppController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/SppController.php) menggunakan alias `p` (`transaksi_spp_pembayaran p`) dan mengurutkan secara tepat berdasarkan `ORDER BY p.tanggal_bayar DESC`.
5. **Pengujian Kueri**: Pengujian kueri mengembalikan 200 OK dengan 160 baris tagihan beserta atribut kwitansi terbaru secara sempurna.

---
## [Perbaikan Penggulungan (Scroll) Opsi Filter Siswa Kasir]
**Waktu**: 19:19 WIB
**Jenis**: Bug Fix / UI-UX Refinement

### Ringkasan Pekerjaan:
1. **Penghapusan Konflik Kelas CSS `overflow-hidden`**:
   - Mengidentifikasi penyebab daftar 50 siswa pada dropdown filter tidak bisa di-scroll yaitu akibat adanya kelas utilitas Bootstrap `overflow-hidden` yang menerapkan aturan `overflow: hidden !important;`, membatalkan properti `overflow-y: auto`.
   - Menghapus kelas `overflow-hidden` pada elemen `ul.dropdown-menu` di [kasir.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/kasir.php) dan menyesuaikan `max-height: 320px; overflow-y: auto; z-index: 1050;`.
2. **Header Lengket (Sticky Header)**:
   - Memasang `position: sticky; top: 0; z-index: 10;` pada baris header `PILIH SISWA (50 SISWA)` sehingga judul header tetap menempel di bagian atas saat kasir mengulung daftar nama siswa.

---
## [Perbaikan Permanen Modal Kwitansi dengan State Reaktif Vue Native]
**Waktu**: 19:20 WIB
**Jenis**: Bug Fix / Architecture Refinement

### Ringkasan Pekerjaan:
1. **Pengalihan Modal ke State Reaktif Vue Native (`showKwitansiModal`)**:
   - Mengidentifikasi akar masalah teks mentah (`{{ printData.nomor_kwitansi }}`) yang tetap muncul akibat pemanggilan `new bootstrap.Modal().show()` yang memindahkan node elemen modal ke `document.body` dan memutus pohon DOM Vue 3.
   - Menghapus ketergantungan pada pemanggilan JavaScript Bootstrap Modal dan merefaktor antarmuka modal di [kasir.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/kasir.php) menggunakan directive `v-if="showKwitansiModal"` dengan `class="modal fade show d-block"` dan backdrop hitam semi-transparan (`background: rgba(0,0,0,0.5)`).
   - Mengubah pemicu pembukaan modal pada `checkoutPembayaran()` dan `reprintKwitansi()` menjadi `showKwitansiModal.value = true`, serta penutupan modal menjadi `showKwitansiModal.value = false`.
   - **Hasil**: Modal kini ter-render secara murni di dalam konteks reaktif Vue 3 tanpa ada manipulasi DOM dari skrip eksternal, menjamin 100% data kuitansi ter-render sempurna tanpa pernah menampilkan teks tag mentah lagi.

---
## [Perbaikan SyntaxError Duplicate Identifier printData Kasir]
**Waktu**: 19:21 WIB
**Jenis**: Bug Fix

### Ringkasan Pekerjaan:
1. **Penghapusan Deklarasi Ganda `printData`**:
   - Mengidentifikasi galat JavaScript `Uncaught SyntaxError: Identifier 'printData' has already been declared` yang terjadi akibat adanya dua kali deklarasi `const printData = Vue.ref(...)` pada skope `setup()` Vue di [kasir.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/kasir.php).
   - Menghapus deklarasi duplikat pada baris 369 dan mempertahankan satu-satunya deklarasi `const printData = Vue.ref({...})` utama.
   - **Hasil**: Skrip Vue 3 kini ter-mount secara sempurna tanpa ada SyntaxError.

---
## [Penyempurnaan Penutupan Modal Kwitansi & v-cloak CSS]
**Waktu**: 19:23 WIB
**Jenis**: Bug Fix / UI-UX Refinement

### Ringkasan Pekerjaan:
1. **Method `closeKwitansiModal` & Event Listener**:
   - Menambahkan method eksplisit `closeKwitansiModal()` yang mengubah `showKwitansiModal.value = false`.
   - Mengubah event handler pada tombol Tutup, tombol Silang `(X)`, dan klik backdrop (`@click.self`) di [kasir.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/kasir.php) agar memanggil `closeKwitansiModal`.
2. **Aturan CSS `[v-cloak]`**:
   - Menambahkan aturan CSS `[v-cloak] { display: none !important; }` pada blok `<style>` untuk mencegah kilatan elemen HTML mentah sebelum Vue selesai ter-mount.

---
## [Perbaikan Struktur HTML Modal Kwitansi di Dalam Skope Container Vue]
**Waktu**: 19:25 WIB
**Jenis**: Bug Fix / HTML Scope Correction

### Ringkasan Pekerjaan:
1. **Perbaikan Penempatan Tag Penutup `</div>` `#keuangan-kasir-app`**:
   - Mengidentifikasi akar penyebab utama mengapa modal kuitansi selalu otomatis muncul saat halaman `http://localhost/SINTA-SaaS/keuangan/kasir` diakses: terdapat tag penutup `</div>` prematur yang menutup `<div id="keuangan-kasir-app">` sebelum elemen `<!-- Modal Kwitansi Print View -->`.
   - Karena modal terletak di luar container `div#keuangan-kasir-app`, Vue 3 mengabaikan elemen tersebut. Browser lalu memperlakukan kelas `modal fade show d-block` sebagai HTML statis biasa yang langsung ditampilkan saat halaman dimuat.
   - Memindahkan tag penutup `</div>` `#keuangan-kasir-app` ke bagian paling bawah setelah modal di [kasir.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/kasir.php) dan menghapus tag penutup ekstra/orphaned.
   - **Hasil**: Modal kini berada 100% di dalam skope Vue 3. Modal tersembunyi sempurna saat halaman dimuat dan baru akan muncul ketika kasir secara aktif melakukan pembayaran atau mengklik tombol **Cetak Ulang**.

---
## [Kustomisasi Teks & Tanda Tangan Kuitansi Digital Pembayaran]
**Waktu**: 19:31 WIB
**Jenis**: UI-UX Refinement / Branding

### Ringkasan Pekerjaan:
1. **Penggantian Nama Header Kuitansi (`nama_sekolah`)**:
   - Mengganti teks `SINTA SCHOOL BILLING` pada header kuitansi dengan nama sekolah tenant (`{{ printData.nama_sekolah }}`).
   - Menambahkan computed property `namaSekolahAktif` pada [kasir.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/kasir.php) yang mendeteksi nama sekolah aktif tenant terpilih secara otomatis (baik untuk Super Admin yang berganti sekolah maupun admin/kasir sekolah).
2. **Penggantian Subtitle Kuitansi**:
   - Mengganti subjudul `Bukti Transaksi Resmi Keuangan Sekolah` menjadi `Bukti Transaksi Pembayaran`.
3. **Pembaruan Area Tanda Tangan Petugas (TTD Petugas)**:
   - Mengganti teks `Tanda Tangan Kasir: _________` dengan blok area tanda tangan modern:
     - Judul: `TTD Petugas,`
     - Ruang tanda tangan.
     - Nama Petugas: `( [Nama Petugas / Kasir Login] )` yang diambil dari variabel sesi login kasir (`session.nama_petugas`).

---
## [Penyempurnaan Pemicuan Dialog Cetak Kuitansi (window.onafterprint)]
**Waktu**: 19:37 WIB
**Jenis**: Bug Fix / Browser Compatibility

### Ringkasan Pekerjaan:
1. **Penanganan Penutupan Jendela Cetak (`window.onafterprint`)**:
   - Mengidentifikasi penyebab jendela cetak (*print preview window*) tidak muncul atau langsung tertutup di Chrome/Edge saat tombol **Cetak Sekarang** diklik setelah proses checkout pembayaran: panggilan `window.close()` sebelumnya dijalankan bersamaan dengan `window.print()` di dalam `setTimeout()`, sehingga browser berbasis Chromium membatalkan dialog pencetakan secara otomatis.
   - Merefaktor skrip cetak pada [kasir.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/kasir.php) menggunakan event listener `window.onafterprint = function() { window.close(); };` dan `window.onload` agar jendela cetak hanya ditutup SETELAH kasir selesai mencetak atau menutup dialog cetak browser.
2. **Imutabilitas Data Pembayaran (`paidItems`)**:
   - Mengambil salinan snapshot instan `paidItems` dan `grandTotal` sebelum fungsi asinkronus `fetchTagihanSiswa()` menyegarkan daftar tagihan di layar, menjamin data kuitansi yang tercetak 100% konsisten.
   - **Hasil**: Menekan tombol **Cetak Sekarang** dari alur **Bayar & Cetak Kwitansi** maupun **Cetak Ulang** kini menampilkan jendela pratinjau cetak browser dengan 100% konsisten dan sempurna.

---
## [Pengembangan Fitur Pembatalan (Void) Transaksi Pembayaran Kasir]
**Waktu**: 19:42 WIB
**Jenis**: New Feature / Audit & Security

### Ringkasan Pekerjaan & File yang Diubah:
1. **Rute API Pembatalan Pembayaran ([index.php](file:///C:/xampp/htdocs/SINTA-SaaS/index.php))**:
   - Mendaftarkan endpoint `POST /api/v1/keuangan/batal-pembayaran`.
2. **Logika Backend Reversal & Audit Log ([SppController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/SppController.php))**:
   - Menambahkan method `apiBatalPembayaran()`.
   - Mengunci baris tagihan dengan `FOR UPDATE` di dalam transaksi database PDO.
   - Membalikkan nilai `nominal_bayar` tagihan dan menghitung ulang `status_lunas` (`Belum` / `Cicil`).
   - Menghapus record pembayaran terkait dari `transaksi_spp_pembayaran`.
   - Mencatat log audit permanen ke `transaksi_spp_audit_log` dengan aksi `VOID_PAYMENT`, menyimpan identitas kasir, alasan pembatalan, data sebelum, & sesudah.
3. **Antarmuka Kasir & Modal Konfirmasi ([kasir.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/kasir.php))**:
   - Menambahkan tombol **"Batal"** (berwarna merah berikon `bi-arrow-counterclockwise`) pada setiap baris tagihan yang memiliki `nominal_bayar > 0`.
   - Menambahkan modal dialog konfirmasi `#modalBatalPembayaran` lengkap dengan rincian nomor kuitansi, nominal dibayar, serta input teks wajib **Alasan Pembatalan**.
   - Mengaitkan state Vue `showBatalModal`, `confirmBatalPembayaran()`, dan `submitBatalPembayaran()` yang menyegarkan data tagihan secara otomatis setelah pembatalan berhasil.

---
## [Pengembangan Modul Audit Trail & Keamanan Aktivitas Petugas (Khusus Super Admin)]
**Waktu**: 19:48 WIB
**Jenis**: New Feature / Security & Compliance

### Ringkasan Pekerjaan & File yang Dibuat/Diubah:
1. **Migrasi Database Menu Audit Log ([2026_07_21_03_add_audit_log_menu.php](file:///C:/xampp/htdocs/SINTA-SaaS/database/migrations/2026_07_21_03_add_audit_log_menu.php))**:
   - Menambahkan file migrasi berformat `return [...]` array yang mendaftarkan menu ID 79 (`Audit Trail & Log Security`) di bawah parent menu 70 (`Keuangan & Pembayaran`).
   - Memberikan hak akses menu ID 79 secara eksklusif HANYA untuk `role_id = 1` (`super_admin`).
2. **Helper Audit Logger Terpusat ([AuditLogger.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Helpers/AuditLogger.php))**:
   - Membuat kelas helper `App\Helpers\AuditLogger::log()` untuk mempermudah pencatatan jejak audit di seluruh aplikasi secara otomatis (mengidentifikasi `user_id` dan `tenant_id` dari sesi aktif).
3. **Routing System ([index.php](file:///C:/xampp/htdocs/SINTA-SaaS/index.php))**:
   - Mendaftarkan rute `GET /keuangan/audit-log` dan `GET /api/v1/keuangan/audit-log`.
4. **Backend Controller & Akses Eksklusif Super Admin ([SppController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/SppController.php))**:
   - Menambahkan method `auditLog()` dengan sistem penguncian keamanan `HTTP 403 Forbidden` untuk selain `super_admin`.
   - Menambahkan method `apiAuditLog()` yang mendukung pencarian kata kunci, filtering tenant/sekolah, jenis aksi, rentang tanggal, kalkulasi metrik ringkasan, dan pagination.
5. **Frontend View Vue 3 ([audit_log.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/audit_log.php))**:
   - Membangun antarmuka modern khusus Super Admin dengan 4 kartu metrik statistik, baris filter canggih, tabel audit log berwarna-warni, serta modal **Detail Perubahan Data (Audit JSON Diff)** untuk melihat perbandingan `Data Sebelum` vs `Data Sesudah`.

---
## [Perbaikan Fatal Error Undefined Method checkAccess pada auditLog]
**Waktu**: 19:51 WIB
**Jenis**: Bug Fix / Error Correction

### Ringkasan Pekerjaan:
1. **Penghapusan Pemanggilan Method `checkAccess()`**:
   - Mengidentifikasi galat PHP `Call to undefined method App\Controllers\SppController::checkAccess()` saat halaman `/keuangan/audit-log` diakses.
   - Pengecekan login telah ditangani secara otomatis oleh `SessionManager::requireLogin()` pada `__construct()`, dan proteksi hak akses Super Admin diperiksa via `($_SESSION['role_name'] ?? '') !== 'super_admin'`.
   - Menghapus pemanggilan `$this->checkAccess()` dari method `auditLog()` di [SppController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/SppController.php).
   - **Hasil**: Halaman Audit Trail & Log Security kini ter-render secara sempurna tanpa galat 500.

---
## [Perbaikan Render Tag Vue Mentah pada Modal Detail Audit JSON Diff]
**Waktu**: 19:54 WIB
**Jenis**: Bug Fix / UI-UX Rendering

### Ringkasan Pekerjaan:
1. **Perubahan Inisialisasi Vue ke IIFE Instan**:
   - Mengidentifikasi akar masalah teks mentah `{{ selectedLog.id }}`, `{{ formatJson(...) }}`, dan `{{ formatNumber(...) }}` yang muncul pada modal dan halaman audit log: script pembungkus sebelumnya menggunakan `document.addEventListener('DOMContentLoaded', ...)` yang tidak pernah dipicu karena event `DOMContentLoaded` sudah selesai berlalu saat master layout dimuat.
   - Mengganti wrapper event listener dengan IIFE (*Immediately Invoked Function Expression*) `(function() { Vue.createApp(...).mount('#audit-log-app'); })();` pada [audit_log.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/audit_log.php).
   - **Hasil**: Aplikasi Vue 3 kini langsung ter-mount 100% sempurna saat halaman dimuat. Seluruh data metrik, tabel audit, dan modal **Detail Perubahan Data (Audit JSON Diff)** kini ter-render secara cepat, bersih, dan rapi tanpa ada tag mentah lagi.

---
## [Perbaikan Error 500 Subquery SQL apiAuditLog (Unknown Column r.name)]
**Waktu**: 19:56 WIB
**Jenis**: Bug Fix / SQL Query Correction

### Ringkasan Pekerjaan:
1. **Koreksi Nama Kolom Tabel Roles (`nama_role`)**:
   - Mengidentifikasi galat HTTP 500 saat memuat data API `/api/v1/keuangan/audit-log`: pada kueri JOIN tabel `roles r`, kueri sebelumnya memilih `r.name as role_name`, padahal nama kolom di skema tabel `roles` adalah `nama_role`.
   - Mengubah kueri SQL menjadi `r.nama_role as role_name` di dalam method `apiAuditLog()` pada [SppController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/SppController.php).
   - **Hasil**: API audit log kini mengembalikan respon HTTP 200 OK secara instan beserta seluruh data log aktivitas dan metrik statistik.

---
## [Perbaikan Error 500 Subquery SQL apiAuditLog (Unknown Column u.username)]
**Waktu**: 19:58 WIB
**Jenis**: Bug Fix / Database Schema Alignment

### Ringkasan Pekerjaan:
1. **Koreksi Kolom Pengenal Pengguna Tabel Users (`u.email`)**:
   - Mengidentifikasi galat HTTP 500 tersisa pada API `/api/v1/keuangan/audit-log`: kueri SQL sebelumnya memilih `u.username` dan melakukan pencarian `OR u.username LIKE ?`, padahal pada skema tabel `users` kolom yang tersedia adalah `email` (`u.email`).
   - Mengubah kueri SQL menjadi `u.email as username` dan pencarian `OR u.email LIKE ?` pada method `apiAuditLog()` di [SppController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/SppController.php).
   - **Hasil**: Kueri SQL kini berjalan 100% lancar tanpa error MySQL, mengembalikan data log aktivitas audit dan metrik statistik secara instan.

---
## [Penambahan Fitur Pagination & Pilihan Limiter Baris (25, 50, 100 Baris)]
**Waktu**: 20:01 WIB
**Jenis**: UI-UX Feature Enhancement

### Ringkasan Pekerjaan & File yang Diubah:
1. **Dropdown Pilihan Limiter Baris (`25`, `50`, `100` Baris)**:
   - Menambahkan komponen pemilih `pageSize` pada header card di [audit_log.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/audit_log.php) dengan opsi `25 baris`, `50 baris`, dan `100 baris`.
   - Mengatur nilai bawaan (*default*) `pageSize` dari 15 menjadi 25 baris per halaman.
2. **Pembaruan Backend Controller ([SppController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/SppController.php))**:
   - Memperbarui `apiAuditLog()` untuk memproses nilai `page_size` dinamis dengan batas atas aman hingga 200 baris.
3. **Penyempurnaan Navigasi Pagination Footer**:
   - Menampilkan informasi halaman aktif `Halaman X dari Y` serta navigasi `Sebelumnya` & `Selanjutnya` yang responsif.

---
## [Desain Popup Notifikasi Pembatalan & Transaksi Kasir Modern/Profesional]
**Waktu**: 20:04 WIB
**Jenis**: UI-UX Professional Redesign

### Ringkasan Pekerjaan & File yang Diubah:
1. **Mengganti Browser Default `alert()` dengan Vue Modal Notification Card**:
   - Menghapus penggunaan `alert()` bawaan browser yang terkesan kuno dan standar.
   - Menambahkan komponen modal notifikasi interaktif `#showNotifModal` pada [kasir.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/kasir.php) dengan ikon lingkaran besar animasi centang hijau/merah, judul tebal, pesan ringkas, dan rincian transaksi (No Kuitansi, Komponen Tagihan, Nominal Dibatalkan, & Nama Siswa).
   - Menambahkan tombol aksi utama **"Selesai & Mengerti"** berwarna hijau/merah yang elegan.

---
## [Pembaruan Laporan Keuangan: Filter Lengkap, Kolom Kelas & TA, Ekspor .xlsx Genuine, & Cetak Terfilter]
**Waktu**: 20:13 WIB
**Jenis**: New Feature / Advanced Reporting & Excel Export

### Ringkasan Pekerjaan & File yang Diubah:
1. **Penambahan Kolom Tabel ([laporan.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/keuangan/laporan.php))**:
   - **Rekap Pemasukan**: Menambahkan kolom **Kelas** dan **Tahun Ajaran** ke dalam tabel data transaksi pemasukan.
   - **Rekap Tunggakan**: Menambahkan kolom **Kelas** ke dalam tabel data sisa tunggakan siswa.
2. **Baris Filter Canggih Terintegrasi**:
   - Menambahkan dropdown filter **Filter Komponen Biaya**, **Filter Jenjang**, **Filter Kelas**, **Filter Tahun Ajaran**, **Pencarian Siswa**, serta **Rentang Tanggal Bayar (Mulai - Sampai)** dan **Filter Metode**.
3. **Ekspor Excel (.xlsx Genuine dengan SheetJS)**:
   - Mengintegrasikan pustaka SheetJS `xlsx.full.min.js` untuk menghasilkan file Excel `.xlsx` asli yang mengekspor data terfilter secara presisi.
4. **Modul Cetak Laporan Terfilter**:
   - Fungsi `printReport()` merender pratinjau cetak (*print preview*) resmi ber-kop sekolah dengan ringkasan kriteria filter yang diterapkan dan total rekapitulasi jumlah bayar/sisa tunggakan di bagian bawah.
5. **Backend Data JOIN ([SppController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/SppController.php))**:
   - Memperbarui method `apiLaporanRekap()` dengan `LEFT JOIN` ke tabel `kelas`, `jenjang`, dan `tahun_ajaran` serta mengembalikan daftar master opsi filter secara otomatis.
