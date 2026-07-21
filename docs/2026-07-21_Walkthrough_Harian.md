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



