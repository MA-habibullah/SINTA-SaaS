# Implementation Plan: Profil / Identitas Sekolah (Tenant Profile)

Rencana ini merinci langkah-langkah untuk membangun fitur manajemen profil lengkap sekolah: menambahkan kolom profil ke database, membuat `SekolahController`, dan membuat view interaktif berbasis Tailwind CSS yang terintegrasi dengan Bootstrap master layout.

## User Review Required

> [!IMPORTANT]
> **Skema Database & Integrasi Tailwind CSS**
> - **Alami Perubahan Skema (`tenants`)**: Tabel `tenants` akan di-alter untuk menambahkan kolom-kolom baru seperti alamat sekolah, kontak, detail kepala sekolah, operator, logo, dan berkas akreditasi.
> - **Integrasi Tailwind CSS Tanpa Konflik**: Karena template utama aplikasi (`master.php`) menggunakan Bootstrap 5, pemuatan Tailwind CSS via CDN pada view baru akan dikonfigurasi dengan `corePlugins: { preflight: false }`. Ini akan mencegah Tailwind merusak/mereset styling bawaan Bootstrap di sidebar, navbar, dan layout global.
> - **Pembersihan File Fisik (Data Retention)**: Saat mengunggah logo atau sertifikat baru, berkas fisik lama yang tercatat di database akan dihapus dari server (`unlink()`) untuk mencegah disk bloat.

## Proposed Changes

---

### [Component: Database & Migrations]

#### [NEW] [2026_06_21_alter_tenants_add_profile_fields.php](file:///c:/xampp/htdocs/dapodik-spmb/database/migrations/2026_06_21_alter_tenants_add_profile_fields.php)
* Menambahkan kolom-kolom baru pada tabel `tenants`:
  - `bentuk_pendidikan` (VARCHAR(50) DEFAULT 'SMA')
  - `status_sekolah` (VARCHAR(50) DEFAULT 'Negeri')
  - `kurikulum` (VARCHAR(100) DEFAULT 'Merdeka')
  - `alamat_sekolah` (TEXT)
  - `rt_rw` (VARCHAR(20))
  - `kode_pos` (VARCHAR(10))
  - `kelurahan` (VARCHAR(100))
  - `kecamatan` (VARCHAR(100) DEFAULT 'Kec. Tandes')
  - `kabupaten_kota` (VARCHAR(100) DEFAULT 'Kota Surabaya')
  - `provinsi` (VARCHAR(100) DEFAULT 'Prov. Jawa Timur')
  - `no_telp` (VARCHAR(20))
  - `email_sekolah` (VARCHAR(100))
  - `website` (VARCHAR(255))
  - `nama_kepsek` (VARCHAR(255) DEFAULT 'Nana Petty Puspitasari')
  - `nip_kepsek` (VARCHAR(50))
  - `nama_operator` (VARCHAR(255) DEFAULT 'Edi Sugiarto')
  - `email_operator` (VARCHAR(100) DEFAULT 'aidasugiarto@gmail.com')
  - `logo` (VARCHAR(255))
  - `sertifikat_akreditasi` (VARCHAR(255))

---

### [Component: Routing]

#### [MODIFY] [index.php](file:///c:/xampp/htdocs/dapodik-spmb/index.php)
* Mengubah route `/sekolah/identitas` untuk memanggil `App\Controllers\SekolahController->showProfile()`.
* Mendaftarkan route baru `/api/v1/sekolah/update` untuk memanggil `App\Controllers\SekolahController->updateProfile()`.

---

### [Component: Controllers]

#### [NEW] [SekolahController.php](file:///c:/xampp/htdocs/dapodik-spmb/app/Controllers/SekolahController.php)
* Membuat controller baru `App\Controllers\SekolahController` dengan metode:
  - `showProfile()`: Mengambil data profil sekolah berdasarkan `tenant_id` session dan merender view `sekolah_profil`.
  - `updateProfile()`: Memproses update profil (Prepared Statements PDO), melakukan validasi input, membatasi ukuran unggahan file maksimal 500 KB, dan secara otomatis menghapus file fisik lama di disk (`unlink()`) jika ada file baru diunggah.

---

### [Component: Views]

#### [NEW] [sekolah_profil.php](file:///c:/xampp/htdocs/dapodik-spmb/views/sekolah_profil.php)
* Membuat antarmuka profil menggunakan arsitektur CSS Grid (2-3 kolom) dengan Tailwind CSS.
* Menyusun input dalam 4 Grup data (Identitas Pokok, Wilayah & Kontak, Manajemen SDM, Legalitas/Upload).
* Menyediakan drag & drop area modern untuk Logo (dengan image preview) dan Sertifikat Akreditasi (khusus PDF/Gambar, max 500 KB).
* Menggunakan Vue 3 reaktif & Axios untuk validasi interaktif dan SweetAlert2 untuk status notifikasi.

---

## Verification Plan

### Automated Tests
* Jalankan linter sintaks PHP:
  ```bash
  php -l index.php
  php -l app/Controllers/SekolahController.php
  php -l views/sekolah_profil.php
  ```

### Database Migration
* Jalankan perintah CLI migrasi untuk menambahkan kolom profil ke database:
  ```bash
  php migrate.php
  ```

### Manual Verification
1. Login sebagai `operator_sekolah` atau `super_admin`.
2. Buka menu **Sistem & Utilitas -> Identitas Sekolah** (akses route `/sekolah/identitas`).
3. Verifikasi tata letak 2-3 kolom grid Tailwind dan pastikan menu navigasi samping (Bootstrap) tidak berubah bentuk (no conflicts).
4. Lakukan pengeditan data dan unggah foto logo sekolah baru.
5. Verifikasi proses simpan berhasil, berkas tersimpan di folder `storage/uploads/`, berkas lama terhapus secara fisik, dan aktivitas terekam di **Log Aktivitas**.
