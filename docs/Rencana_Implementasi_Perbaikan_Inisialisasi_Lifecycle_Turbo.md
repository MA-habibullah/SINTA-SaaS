# Rencana Implementasi: Perbaikan Inisialisasi Lifecycle pada Navigasi Turbo/SPA

Dokumen ini menjelaskan rencana perbaikan untuk masalah di mana tombol pemindai dokumen (Upload & Kamera) tidak berfungsi saat diakses dari Dashboard melalui navigasi SPA (Turbo), tetapi berfungsi normal setelah halaman di-refresh.

---

## Deskripsi Masalah
SINTA-SaaS menggunakan **Turbo** (Hotwire) untuk melakukan navigasi halaman secara cepat tanpa memuat ulang seluruh halaman (full reload). 

Ketika berpindah halaman dari Dashboard ke Pemindai Dokumen:
1. Turbo hanya mengganti konten `<body>` menggunakan AJAX, sehingga event browser **`DOMContentLoaded` tidak akan dipicu kembali**.
2. Berkas JavaScript pada `document_scanner.php` dibungkus di dalam listener `DOMContentLoaded` sehingga seluruh inisialisasi tombol (`initDragCorners`, `initModeSelector`, `initCameraScanner`, dll.) **tidak pernah dijalankan**.
3. Selain itu, elemen-elemen DOM yang diambil secara global di awal script masih merujuk ke elemen lama yang sudah dihapus dari DOM.
4. Apabila halaman di-refresh manual (F5), event `DOMContentLoaded` dipicu ulang oleh browser sehingga fitur dapat berfungsi kembali.
5. Terjadi juga potensi penumpukan event listener (*memory leak*) pada objek `window` (seperti event `resize`, `mousemove`, dan `mouseup` untuk zoom/pan) jika halaman dikunjungi berkali-kali tanpa dibersihkan.

---

## Usulan Perbaikan (Proposed Changes)

Kita akan melakukan modifikasi pada file [document_scanner.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/utility/document_scanner.php).

### 1. Inisialisasi Instan (Instant Execution)
Kita akan menghapus pembungkus `DOMContentLoaded` dan menjalankan fungsi inisialisasi **seketika berkas JavaScript dimuat**. Karena tag `<script>` terletak di bagian bawah berkas (setelah semua elemen HTML didefinisikan), semua elemen DOM dijamin sudah siap ketika script dieksekusi baik pada muatan pertama maupun navigasi Turbo.

### 2. Mekanisme Pembersihan Global (`window._aeroScanCleanup`)
Kita akan membuat sistem pembersihan untuk menghapus semua event listener global (`resize`, `mousemove`, `mouseup` pada `window` & `document`) sebelum inisialisasi baru berjalan atau saat pengguna meninggalkan halaman:
* Menyimpan fungsi pembersihan ke dalam variabel global `window._aeroScanCleanup`.
* Memanggil `window._aeroScanCleanup()` sesaat sebelum inisialisasi baru dimulai untuk menghapus listener dari kunjungan sebelumnya.
* Memanggil fungsi pembersihan pada event `turbo:before-cache` untuk mencegah kebocoran memori saat beralih halaman.

---

## Rincian Perubahan Kode

### [Pemindai Dokumen]

#### [MODIFY] [document_scanner.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/utility/document_scanner.php)

Perubahan akan difokuskan pada bagian berikut:

1. **Deklarasi Variabel Handler di Lingkup IIFE**:
   ```javascript
   let resizeHandler = null;
   let panMouseMoveHandler = null;
   let panMouseUpHandler = null;
   ```

2. **Membuat Fungsi `initAeroScan()`**:
   * Menjalankan pembersihan `window._aeroScanCleanup()` jika ada.
   * Menjalankan semua fungsi `init...()`.
   * Mendaftarkan listener `resize`, `mousemove`, dan `mouseup` ke variabel handler.
   * Mendefinisikan fungsi pembersihan `window._aeroScanCleanup` yang akan memanggil `stopCameraScan()` dan menghapus semua listener global.

3. **Inisialisasi & Lifecycle Cleanup (di bagian bawah script)**:
   * Menjalankan `initAeroScan()` langsung.
   * Mengatur event `turbo:before-cache` agar memicu pembersihan instans saat pengguna meninggalkan halaman.

---

## Rencana Verifikasi (Verification Plan)

### Verifikasi Manual
1. **Pengecekan Konsol Browser**:
   * Buka Dashboard SINTA-SaaS (`http://localhost/SINTA-SaaS/dashboard`).
   * Klik menu **Pemindai Dokumen** (tanpa melakukan refresh manual).
   * Pastikan tombol **Unggah Foto** dan **Ambil Foto** dapat diklik dan berfungsi secara langsung.
2. **Navigasi Bolak-Balik**:
   * Buka Dashboard -> Buka Pemindai Dokumen -> Kembali ke Dashboard -> Buka kembali Pemindai Dokumen.
   * Verifikasi bahwa fitur zoom/pan dan kontrol sudut tetap berfungsi normal tanpa adanya duplikasi event atau perlambatan kinerja.
