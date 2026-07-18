# Implementation Plans Harian — 2026-07-16

---
## Penyempurnaan Buku Induk SINTA-SaaS (Revisi 2)
**Waktu**: 09:30 WIB
**Status**: Dieksekusi
**Deskripsi**: Revisi fitur Buku Induk mencakup Time-Travel Signatures (tanda tangan Kepsek masa jabatan), QR Code verifikasi opsional, pengarsipan rapor PDF statis, validasi pre-upload Excel, dan peringatan agama reaktif di grid nilai.

### 1. Time-Travel Signatures (Tanda Tangan Masa Jabatan Kepsek)

**Tujuan:** Dokumen sejarah mencetak nama Kepsek yang bertugas saat itu.

#### [MODIFY] `app/Controllers/BukuIndukController.php`
Helper `getKepsekAtDate($tenantId, $tanggalCetak)`:
```php
// Query: riwayat_kepala_sekolah di mana
// tanggal_mulai <= $tanggalCetak AND (tanggal_selesai >= $tanggalCetak OR tanggal_selesai IS NULL)
// Fallback ke kepala sekolah default di tabel tenants jika kosong
```

#### [MODIFY] Print Views
- `print_transkrip_standar.php`, `print_rapot_merdeka.php`, `print_rapot_k13.php`, `print_rapot_ktsp.php`
- Gunakan data kepala sekolah dinamis berdasarkan tanggal cetak.

#### [MODIFY] `views/buku_induk.php`
- Tambah input **"Tanggal Tertera pada Dokumen"** (default: hari ini) di modal pilihan cetak.

---

### 2. QR Code Verifikasi Opsional dengan Domain Dinamis

**Konfigurasi:** Checkbox "Tampilkan QR Code Verifikasi" pada modal cetak.

- Jika dicentang → kirim parameter `show_qrcode=1` → render QR Code dengan URL dinamis.
- Jika tidak → render dokumen manual tanpa QR Code.

**Dynamic Domain URL:**
```php
$verifyUrl = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST']
           . '/SINTA-SaaS/verify/transkrip/' . $siswaUuid;
```

#### [NEW] `views/verify_transkrip.php`
- View publik mandiri untuk verifikasi tanda tangan digital & keaslian nilai siswa.

---

### 3. [DIBATALKAN] Kustomisasi Formula Nilai Sekolah
- **Status: DIBATALKAN** atas permintaan user.
- Nilai dari Waka Kurikulum sudah berupa nilai jadi. Buku Induk hanya menampilkan rekap tanpa kalkulasi rumus tambahan.

---

### 4. Pengarsipan Rapor PDF Statis & Fleksibel (Re-Generate)

**Tujuan:** Rapor yang disetujui di-render sebagai PDF/A statis di `/storage/archive/`.

#### [MODIFY] `app/Controllers/BukuIndukController.php`
- Simpan file PDF di `/storage/archive/` saat rapor difinalisasi.
- Endpoint Re-Generate: klik "Perbarui Arsip Rapor" → overwrite file PDF statis terbaru.

---

### 5. Validasi Unggahan Excel Nilai (Pre-Upload Validator)

**Alur:**
1. Backend proses baris Excel secara temporer di memori.
2. Tampilkan halaman konfirmasi pratinjau (validation preview):
   - **Hijau**: Nilai valid, siswa ditemukan.
   - **Merah**: Error (NISN tidak cocok, nilai tidak valid, mapel tidak sesuai kurikulum).
3. User hanya bisa simpan jika semua baris valid atau konfirmasi rekonsiliasi.

---

### 6. Peringatan Toleransi Agama Reaktif pada Grid Nilai

**Tujuan:** Mencegah input nilai agama yang salah (tidak sesuai keyakinan siswa).

#### [MODIFY] `views/buku_induk.php` (Vue.js grid)
- Baca data agama masing-masing siswa.
- Kolom mapel agama yang tidak sesuai agama siswa → sorotan kuning/merah atau tooltip peringatan.

### Verification Plan
1. Cetak tanpa centang QR Code → area tanda tangan kosong (tanpa QR).
2. Cetak dengan centang QR Code → QR Code muncul, link mengarah ke domain host aktif.
3. Finalisasi rapor siswa A → ubah nilai → klik Re-Generate → PDF di server terupdate.
4. Upload Excel salah format → validator menampilkan baris merah dengan keterangan error.

---
## Sistem Pengarsipan Digital Alumni Historis (1980 – 2026)
**Waktu**: 11:00 WIB
**Status**: Dieksekusi
**Deskripsi**: Merancang sistem pengarsipan digital dokumen alumni historis (Buku Induk, Ijazah, SKHUN) dengan fitur: auto-convert multi-foto HP ke PDF tunggal, upload PDF langsung, PDF viewer premium, dan brankas digital terenkripsi.

### 1. Database

#### [NEW] Tabel `arsip_dokumen_alumni`
```sql
CREATE TABLE `arsip_dokumen_alumni` (
    `id` CHAR(36) NOT NULL PRIMARY KEY,
    `siswa_id` CHAR(36) NOT NULL,
    `tenant_id` CHAR(36) NOT NULL,
    `jenis_dokumen` ENUM('Buku Induk','Ijazah','SKHUN','Sertifikat/SKL','Lainnya') NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,   -- Path ke file .pdf hasil kompilasi
    `file_size` INT UNSIGNED DEFAULT 0,
    `keterangan` TEXT DEFAULT NULL,
    `uploaded_by` CHAR(36) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`siswa_id`) REFERENCES `siswa`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### [NEW] Tabel `log_akses_arsip`
```sql
CREATE TABLE `log_akses_arsip` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` CHAR(36) NOT NULL,
    `tenant_id` CHAR(36) NOT NULL,
    `siswa_id` CHAR(36) NOT NULL,
    `aktivitas` VARCHAR(100) NOT NULL,   -- e.g., 'View PDF Buku Induk', 'Download PDF Ijazah'
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 2. Frontend

#### [NEW] `views/arsip_alumni.php`
- Filter alumni berdasarkan **Tahun Kelulusan (Angkatan)** dari 1980 s.d. 2026.
- Form upload berurutan (multi-page image list) untuk kamera HP.
- Drag-and-drop upload file PDF langsung.
- Embed PDF Viewer (`<iframe>` atau PDF.js).

### 3. Backend

#### [MODIFY] `app/Controllers/BukuIndukController.php`
- `compileImagesToPdf()` — gabungkan temporary images → satu file PDF permanen di server.
- `viewDocumentApi()` — validasi izin akses → streaming file rahasia dari `/storage/archive/`.
- Log penulisan ke `log_akses_arsip` setiap kali berkas diunduh atau dilihat.

### Alur Upload Multi-Foto HP → PDF
1. Operator buka halaman unggah di HP.
2. Foto halaman 1, 2, 3, dst. berurutan. Gambar otomatis dikompresi ke WebP/JPEG (~200KB).
3. Tekan "Simpan & Gabungkan Sebagai PDF".
4. Server menerima kumpulan gambar → gabungkan jadi 1 PDF (via FPDF/TCPDF) → simpan di `/storage/archive/`.
5. 1 siswa = 1 file PDF per jenis dokumen.

### Verification Plan
1. Foto 6 halaman Buku Induk via HP → Simpan & Gabungkan → File PDF tunggal, halaman berurutan benar.
2. Upload PDF ijazah langsung → tersimpan tanpa proses kompilasi ulang.
3. Akses file langsung via URL → **403 Forbidden** atau **404 Not Found** (brankas terkunci).

---
## Integrasi AeroScan (Pemindai Dokumen) ke SINTA-SaaS
**Waktu**: 13:30 WIB
**Status**: Dieksekusi
**Deskripsi**: Mengintegrasikan alat pemindai & kompresor dokumen web-based (AeroScan) dari scratch/index.html ke dalam ekosistem SINTA-SaaS. Semua pemrosesan bersifat client-side penuh (tanpa upload ke server).

### Analisis Kelayakan
- **Client-Side Penuh**: OpenCV.js, Tesseract.js, jsPDF berjalan di browser pengguna.
- **Tidak ada data yang diunggah ke server** — semua pemrosesan temporer di memori lokal.
- **Pemisahan Sumber Daya**: Library hanya dimuat saat halaman pemindai dibuka.

### Proposed Changes

#### [NEW] `database/migrations/2026_07_16_03_add_document_scanner_menu.php`
- Tambah menu "Pemindai Dokumen" (URL: `/SINTA-SaaS/utility/document-scanner`) ke tabel `menus`.
- Hak akses ke semua role: Super Admin, Operator Sekolah, Guru, Siswa, Guru BK.
- Daftarkan ke seluruh tenant aktif di `tenant_menu_access`.

#### [MODIFY] `index.php`
```php
case '/utility/document-scanner':
    if (!isset($_SESSION['user_id'])) {
        header('Location: /SINTA-SaaS/login'); exit;
    }
    $controller = new App\Controllers\UtilityController();
    $controller->documentScanner();
    break;
```

#### [NEW] `app/Controllers/UtilityController.php`
```php
namespace App\Controllers;
use App\Core\SessionManager;

class UtilityController extends BaseController {
    public function documentScanner(): void {
        SessionManager::start();
        if (!isset($_SESSION['user_id'])) {
            header('Location: /SINTA-SaaS/login'); exit;
        }
        $data = [
            'title'     => 'Pemindai & Kompresor Dokumen (AeroScan)',
            'user_nama' => $_SESSION['nama_lengkap'] ?? '',
            'user_role' => $_SESSION['role_name'] ?? ''
        ];
        $this->render('utility/document_scanner', $data);
    }
}
```

#### [NEW] `views/utility/document_scanner.php`
- Adaptasi komponen AeroScan dari `scratch/index.html`.
- Hilangkan tag HTML global (sudah di-handle `master.php`).
- Kemasan Bootstrap grid: control card (resolusi, brightness, kontras, filter) + canvas workspace.
- Inisialisasi script OpenCV.js, Tesseract.js, jsPDF secara dinamis.

### Verification Plan
```bash
php migrate.php up
```
1. Login → menu "Pemindai Dokumen" muncul di sidebar "Sistem & Utilitas".
2. Buka `/utility/document-scanner` → overlay loading "Memuat OpenCV.js..." tampil.
3. Upload gambar → geser handle sudut cyan → OpenCV crop & luruskan dokumen.
4. Ubah slider kecerahan/kontras → perubahan real-time.
5. Klik "Jalankan OCR" → teks terekstraksi dengan benar.
6. Ekspor halaman ke PDF → file terunduh di perangkat pengguna.
