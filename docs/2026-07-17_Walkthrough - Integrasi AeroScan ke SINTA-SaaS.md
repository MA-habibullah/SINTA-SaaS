# Walkthrough - Integrasi AeroScan ke SINTA-SaaS

Saya telah berhasil mengintegrasikan alat **AeroScan (Pemindai & Kompresor Dokumen)** ke dalam platform **SINTA-SaaS** sesuai dengan spesifikasi dan standar keamanan yang disepakati.

---

## Perubahan yang Dilakukan

### 1. Migrasi Database
*   **File Baru**: [2026_07_16_03_add_document_scanner_menu.php](file:///C:/xampp/htdocs/SINTA-SaaS/database/migrations/2026_07_16_03_add_document_scanner_menu.php)
*   **Tindakan**: Mendaftarkan menu "Pemindai Dokumen" (ID 60) di bawah menu induk "Sistem & Utilitas" (ID 13) dan menyinkronkannya dengan semua peran pengguna (`roles`) serta instansi sekolah aktif (`tenants`).

### 2. Routing Aplikasi
*   **Berkas Dimodifikasi**: [index.php](file:///C:/xampp/htdocs/SINTA-SaaS/index.php)
*   **Tindakan**: Menambahkan case route `/utility/document-scanner` ke router utama SINTA-SaaS dengan pelindung sesi aktif.

### 3. Controller
*   **File Baru**: [UtilityController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/UtilityController.php)
*   **Tindakan**: Menyediakan aksi `documentScanner()` untuk memvalidasi login pengguna dan merender tampilan pemindai yang dibungkus dengan layout master.

### 4. Tampilan (View) & Kompatibilitas Hotwire Turbo & Loading Race Condition
*   **File Baru**: [document_scanner.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/utility/document_scanner.php)
*   **Tindakan**: 
    *   Menghilangkan tag HTML/body global duplikat, dan mengintegrasikannya dengan layout Bootstrap 5 bawaan SINTA-SaaS.
    *   **Perbaikan Kompatibilitas Turbo**: Seluruh kode JavaScript dibungkus di dalam **IIFE (Immediately Invoked Function Expression)** untuk melokalisasi variabel dan menghindari bentrokan deklarasi variabel (`cornersRight`, dll.) saat halaman dimuat ulang oleh Hotwire Turbo.
    *   **Perbaikan Balapan Pemuatan Pustaka (Safe Lucide Icons)**: Membuat fungsi pembungkus aman `safeCreateIcons` yang mendeteksi ketersediaan pustaka `lucide` sebelum dieksekusi. Pustaka Lucide juga dikonfigurasi dengan callback `onload` untuk merender ikon segera setelah selesai diunduh. Hal ini mencegah terjadinya `ReferenceError: lucide is not defined` akibat perbedaan kecepatan muat CDN.
    *   **Pemuatan Ulang OpenCV.js**: Menambahkan pemeriksaan status OpenCV (`typeof cv !== 'undefined'`) agar layar loading otomatis tertutup apabila OpenCV telah dimuat sebelumnya.
    *   **Penutupan Kamera Otomatis**: Menambahkan event listener `turbo:before-cache` untuk mematikan kamera secara otomatis jika pengguna berpindah halaman ke menu lain di SINTA-SaaS.
    *   Menambahkan jaminan privasi data: **Pemrosesan berjalan 100% secara lokal (client-side) di browser pengguna. Tidak ada pengiriman data berkas ke database atau folder server.**

---

## Hasil Pengujian & Verifikasi

### 1. Verifikasi CLI
Syntax check pada semua file PHP baru/dimodifikasi berhasil tanpa error:
```bash
php -l index.php
php -l app/Controllers/UtilityController.php
php -l views/utility/document_scanner.php
```
Hasil: `No syntax errors detected`.

### 2. Jalannya Migrasi
Migrasi berhasil dieksekusi di database lokal:
```bash
php migrate.php up
```
Output:
`OK tenant_menu_access untuk Pemindai Dokumen berhasil disinkron ke 3 sekolah.`
`OK Menu 'Pemindai Dokumen' (ID 60) berhasil didaftarkan.`
