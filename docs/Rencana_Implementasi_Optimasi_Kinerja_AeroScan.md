# Rencana Implementasi: Optimasi Kinerja & Pencegahan UI Blocking (<1ms) pada AeroScan

Dokumen ini menjelaskan rencana optimasi untuk mengatasi peringatan `[Violation]` di console browser pada modul **AeroScan - Pemindai Dokumen**. Kita akan mengubah alur penanganan event dari sinkronus (blocking) menjadi asinkronus (non-blocking) agar event handler dapat selesai dieksekusi dalam waktu **< 1ms**.

---

## Deskripsi Masalah
Saat ini, operasi pengolahan gambar (OpenCV.js) dan pembuatan PDF (jsPDF) dipanggil secara langsung (sinkronus) di dalam fungsi *event listener* tombol (misalnya klik tombol mode, tombol filter, tombol reset, dan pembuatan PDF). 

Karena operasi ini sangat berat bagi CPU, utas utama browser (*main thread*) terblokir selama **500ms hingga 1000ms**, mengakibatkan:
1. Peringatan `[Violation] 'click' handler took X ms` di console browser.
2. Tampilan antarmuka (UI) terlihat membeku sesaat (tidak responsif).
3. Indikator loading atau efek aktif tombol terlambat digambar ulang oleh browser karena antrean utas terblokir.

---

## Usulan Perbaikan (Proposed Changes)

Kita akan melakukan optimasi pada file [document_scanner.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/utility/document_scanner.php).

### 1. Asynchronous Yielding (Menggunakan `setTimeout`)
Semua pemrosesan OpenCV dan fungsi berat lainnya di dalam *event handler* akan dibungkus dengan `setTimeout(() => { ... }, 10)`. Ini akan memaksa browser untuk langsung menyelesaikan fungsi handler (dalam **< 1ms**) dan memperbarui tampilan UI (seperti status tombol aktif atau memutar *spinner* loading) terlebih dahulu, baru kemudian mengeksekusi pengolahan gambar di antrean *event loop* berikutnya.

### 2. Penyesuaian Status Loading UI
Menambahkan umpan balik visual (*loading state*) yang aktif seketika setelah tombol diklik, kemudian mengembalikannya ke status siap setelah pemrosesan asinkronus selesai.

---

## Rincian Perubahan Kode

### [Pemindai Dokumen]

#### [MODIFY] [document_scanner.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/utility/document_scanner.php)

Perubahan akan difokuskan pada bagian berikut:

1. **`initModeSelector()`**:
   * Membungkus pemanggilan `detectCorners()` dan `processAndCompress()` dalam `setTimeout` agar handler klik tombol `Halaman Tunggal`, `Buku (2 Hal)`, dan `Aktifkan Pembatas Buku` selesai dalam waktu <1ms.
   * Menampilkan status loading `"Memproses..."` secara instan sebelum pemrosesan dimulai.

2. **`initParametersListeners()`**:
   * Membungkus pengubahan resolusi (`resPresetSelect`) dan tombol filter warna (`filterBtns`) dalam `setTimeout` / `debouncedProcess` agar tidak memblokir antarmuka saat mengubah preset filter.
   * Mengoptimalkan tombol `Putar Gambar 90°` dan `Reset Posisi Sudut`.

3. **`addCurrentToPdfQueue()` & `generatePdf()`**:
   * Mengubah proses penambahan halaman ke antrean PDF dan pembuatan PDF menjadi asinkronus agar proses pembuatan PDF multi-halaman tidak membekukan browser.

---

## Rencana Verifikasi (Verification Plan)

### Verifikasi Manual
1. **Pengecekan Konsol Browser**:
   * Buka halaman `http://localhost/SINTA-SaaS/utility/document-scanner`.
   * Buka Chrome Developer Tools (F12) -> Console.
   * Klik tombol ubah mode, ganti filter warna, klik tombol unduh, dan buat PDF.
   * Pastikan tidak ada lagi peringatan merah/kuning `[Violation] 'click' handler took ...ms` (atau berkurang drastis menjadi < 50ms/1ms).
2. **Keresponsifan Antarmuka (Responsiveness)**:
   * Pastikan tombol memunculkan status aktif secara instan ketika diklik.
   * Pastikan status teks berubah menjadi `"Memproses..."` secara langsung tanpa jeda visual.
