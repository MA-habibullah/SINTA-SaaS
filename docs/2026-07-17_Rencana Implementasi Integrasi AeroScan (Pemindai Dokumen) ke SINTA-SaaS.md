# Rencana Implementasi Integrasi AeroScan (Pemindai Dokumen) ke SINTA-SaaS

Dokumen ini menjelaskan rencana analisis dan langkah-langkah implementasi untuk mengintegrasikan alat pemindai & kompresor dokumen web-based (**AeroScan**) dari `C:\xampp\htdocs\SINTA-SaaS\scratch\index.html` ke dalam ekosistem **SINTA-SaaS** yang sudah berjalan.

---

## Analisis Kelayakan & Desain Integrasi
Integrasi ini **sangat layak (feasible)** dan **aman** karena:
1. **Sifat Client-Side Penuh (Tanpa Penyimpanan Server)**: AeroScan memproses pemindaian (OpenCV.js), kompresi (canvas/quality adjustments), ekstraksi teks (Tesseract.js), dan penggabungan PDF (jsPDF) langsung di browser pengguna (client-side).
   - **PENTING**: Sesuai instruksi, file yang diproses/dikompres **tidak akan diunggah ke server, disimpan ke folder repositori, maupun disimpan ke database**. Semua pemrosesan bersifat temporer di memori lokal browser, dan hasil akhirnya langsung diunduh oleh pengguna ke perangkat mereka sendiri.
2. **Kesesuaian Fitur**: Pemindai dokumen sangat bermanfaat di lingkungan sekolah (misal: bagi siswa untuk men-scan berkas PPDB secara mandiri sebelum diunggah secara resmi, guru untuk memindai berkas, dan operator untuk digitalisasi dokumen).
3. **Pemisahan Sumber Daya**: Pustaka eksternal (OpenCV, Tesseract, jsPDF) hanya akan dimuat saat pengguna membuka halaman pemindai. Ini menjaga performa halaman-halaman SINTA-SaaS lainnya agar tetap ringan.

---

## Rencana Perubahan (Proposed Changes)

### 1. Database & Menu Sidebar
Kita akan membuat file migrasi baru untuk mendaftarkan menu **Pemindai Dokumen** di bawah menu induk **Sistem & Utilitas** (parent_id = 13).

#### [NEW] [2026_07_16_03_add_document_scanner_menu.php](file:///C:/xampp/htdocs/SINTA-SaaS/database/migrations/2026_07_16_03_add_document_scanner_menu.php)
Migration ini akan:
- Menambahkan menu baru "Pemindai Dokumen" dengan URL `/SINTA-SaaS/utility/document-scanner` ke tabel `menus`.
- Memberikan hak akses (tabel `role_menu_access`) ke seluruh peran pengguna (Super Admin `1`, Operator Sekolah `2`, Guru `3`, Siswa `4`, dan Guru BK `20`, Kepala Sekolah jika ada).
- Mendaftarkan akses menu ini ke seluruh tenant aktif di tabel `tenant_menu_access`.

---

### 2. Routing Aplikasi
Kita perlu mendaftarkan path URL baru di router utama.

#### [MODIFY] [index.php](file:///C:/xampp/htdocs/SINTA-SaaS/index.php)
Menambahkan `case` routing baru:
```php
        case '/utility/document-scanner':
            if (!isset($_SESSION['user_id'])) {
                header('Location: /SINTA-SaaS/login');
                exit;
            }
            $controller = new App\Controllers\UtilityController();
            $controller->documentScanner();
            break;
```

---

### 3. Controller Baru
Kita akan membuat controller utilitas untuk menangani request render halaman pemindai.

#### [NEW] [UtilityController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/UtilityController.php)
Controller ini bertugas memverifikasi sesi aktif pengguna dan me-render view pemindai dokumen yang dibungkus dalam `master.php` layout.
```php
<?php

namespace App\Controllers;

use App\Core\SessionManager;

class UtilityController extends BaseController {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Render Halaman Pemindai Dokumen (AeroScan)
     */
    public function documentScanner(): void {
        SessionManager::start();
        if (!isset($_SESSION['user_id'])) {
            header('Location: /SINTA-SaaS/login');
            exit;
        }

        $data = [
            'title' => 'Pemindai & Kompresor Dokumen (AeroScan)',
            'user_nama' => $_SESSION['nama_lengkap'] ?? '',
            'user_role' => $_SESSION['role_name'] ?? ''
        ];

        $this->render('utility/document_scanner', $data);
    }
}
```

---

### 4. Tampilan Halaman (View)
Kita akan mengadaptasi tampilan HTML/CSS/JS dari file scratch ke format view SINTA-SaaS.

#### [NEW] [document_scanner.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/utility/document_scanner.php)
Kita akan menyalin komponen workspace AeroScan ke file ini dengan penyesuaian:
- Menghilangkan tag global HTML (`<html>`, `<head>`, `<body>`) dan sidebar aplikasi bawaan AeroScan, karena layout utama sudah disediakan oleh `views/layout/master.php`.
- Mengemas komponen control card (resolusi, brightness, kontras, filter) dan canvas workspace (original image, scanned result, OCR, PDF queue) ke dalam layout Bootstrap grid yang elegan agar menyatu dengan dashboard SINTA-SaaS.
- Memasukkan inisialisasi script OpenCV.js, Tesseract.js, dan jsPDF secara dinamis di dalam view ini.

---

## Rencana Verifikasi (Verification Plan)

### Verifikasi Otomatis / CLI
1. Menjalankan migrasi database via CLI:
   ```bash
   php migrate.php up
   ```
2. Memeriksa file log error (`system_errors` atau file log scratch) untuk memastikan tidak ada konflik penulisan syntax.

### Verifikasi Manual
1. **Pemeriksaan Menu**: Login ke SINTA-SaaS menggunakan akun Admin/Operator/Guru/Siswa, lalu pastikan menu "Pemindai Dokumen" muncul di bawah menu induk "Sistem & Utilitas" di sidebar.
2. **Pemuatan Pustaka**: Buka halaman `/SINTA-SaaS/utility/document-scanner` dan perhatikan transisi overlay loading "Memuat OpenCV.js...".
3. **Uji Fungsionalitas Scan**:
   - Unggah gambar contoh/demo.
   - Gerakkan handle penanda sudut cyan untuk memastikan OpenCV memotong dan meluruskan dokumen secara presisi.
   - Ubah slider kecerahan, kontras, dan penajaman untuk melihat perubahan real-time.
   - Uji OCR dengan menekan tombol "Jalankan OCR" dan pastikan teks terekstraksi dengan benar.
   - Coba ekspor halaman hasil scan ke dokumen PDF.
