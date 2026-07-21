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
8. `views/keuangan/tagihan_saya.php`
