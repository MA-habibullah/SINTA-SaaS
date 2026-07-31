---
## [Perbaikan Bug Lanjutan - TypeError dan Route Mati]
**Waktu**: 23:53 WIB
**Status**: Dieksekusi

**Latar Belakang & Root Cause:**
1. Terdapat pesan error [PHP BACKEND ERROR] Error: Class "App\Controllers\ErrorMonitorController" not found dan SppController not found. Hal ini terjadi karena rute-rute eksplisit untuk controller yang belum dibuat masih terdaftar aktif di index.php, sehingga memotong alur fallback UnderConstructionController (404) yang sudah dibuat sebelumnya.
2. Terdapat pesan error TypeError: App\Core\BaseController::jsonResponse() yang memicu status 500 API. Root cause-nya adalah inkonsistensi tipe data parameter: BaseController::jsonResponse() secara ketat meminta argumen pertama bertipe ool , namun banyak modul (seperti PenggunaModuleController dan BukuIndukModuleController) langsung melempar array sebagai argumen pertama (misal $this->jsonResponse(['error' => '...'])), yang menyebabkan TypeError di PHP 8.

**Proposed Changes:**
1. **index.php**: Comment out rute /keuangan (SppController) dan /error-monitor (ErrorMonitorController) secara dinamis agar otomatis fallback ke halaman *Under Construction*.
2. **pp/Core/BaseController.php**: Refactor metode jsonResponse agar dinamis dan bisa menerima *payload array* secara langsung pada argumen pertamanya (mixed ). Sistem akan otomatis mendeteksi apakah *payload* tersebut berisi error, success, atau raw data data, lalu memetakan ulang format JSON agar tetap kompatibel dengan front-end Axios secara elegan.

**Verification Plan:**
- Buka halaman Dashboard atau modul lainnya. Pastikan tidak ada log error konsol mengenai API 500 TypeError.
- Klik navigasi ke modul Keuangan atau Error Monitor. URL tersebut sekarang harus menampilkan antarmuka *Under Construction* dan tidak menghasilkan *Fatal Error Class Not Found*.

---
## [Perbaikan Halaman Pengguna API 500 & Skema PostgreSQL]
**Waktu**: 00:30 WIB
**Status**: Dieksekusi

# Perbaikan Halaman Pengguna (API 500)

Error yang terjadi pada endpoint `/api/v1/pengguna` disebabkan oleh migrasi database dari MySQL ke PostgreSQL yang membawa banyak perubahan arsitektur tabel secara mendasar. `PenggunaModel.php` saat ini masih menggunakan skema query gaya lama (MySQL) yang memanggil tabel dan kolom yang sudah tidak ada di versi PostgreSQL.

## Root Cause Analysis
1. **Error Mutasi & Siswa (`SQLSTATE[42703]: Undefined column / Undefined table`)**:
   Query lama menggabungkan (JOIN) tabel `siswa.siswa` dengan banyak tabel eksternal (`core.kota`, `siswa.rincian_alamat`, `siswa.kontak`, `siswa.orang_tua`, `sistem.kelas`, dll). Di arsitektur PostgreSQL yang baru, tabel `siswa.siswa` telah dipipihkan (*flattened*) dan tabel-tabel rincian tersebut **telah ditiadakan**. Kolom seperti `id_kelas` berubah menjadi `kelas_saat_ini` bertipe *string*, dan `tempat_lahir` yang dulu ID referensi ke `kota`, sekarang hanya berupa *string*.
2. **Error Aksi Kelas (`Gagal mengambil data kelas`)**:
   Query untuk `getKelasForAction()` mencari tabel `sistem.kelas` dan `sistem.jenjang`. Faktanya, tabel kelas dipindahkan ke skema `akademik.kelas` dan `jenjang` dipindahkan ke `core.jenjang`. Selain itu, `akademik.kelas` tidak lagi memiliki relasi *foreign key* `id_jenjang` di dalamnya.

## User Review Required
> [!IMPORTANT]
> Karena skema PostgreSQL telah berubah secara drastis (hilangnya tabel `rincian_alamat`, `kontak`, dll), query *getAllUser* untuk Siswa akan jauh lebih sederhana dan hanya menampilkan kolom-kolom yang tersedia di tabel `siswa.siswa` yang baru. 
> Untuk filter Mutasi, kita tidak bisa lagi mencari `reg.keluar_karena` karena tabel `registrasi` yang baru tidak memiliki kolom tersebut. Filter akan difokuskan hanya pada `status_siswa` di tabel utama `siswa.siswa`.

## Proposed Changes

### App\Modules\Sistem\Models\PenggunaModel.php
- **[MODIFY] `getPaginated()` Tab Mutasi**: 
  - Hapus seluruh LEFT JOIN yang tidak relevan (seperti `core.kota`, `sistem.kelas`, `sistem.jenjang`, `core.users`).
  - Filter `WHERE` menggunakan `s.status_siswa != 'Aktif'` alih-alih `s.status = 'Pindah' OR reg.keluar_karena`.
  - Hapus referensi `kl_lahir.nama_kota`, `u.email`, `kel.nama_kelas` dll dari blok `SELECT`.
- **[MODIFY] `getPaginated()` Tab Siswa**:
  - Hapus semua JOIN ke `rincian_alamat`, `kontak`, `orang_tua`, `rincian_pelajar`.
  - Sesuaikan list `SELECT` hanya untuk membaca kolom `alamat`, `email`, `no_hp`, `kelas_saat_ini` yang sudah tergabung di `siswa.siswa`.
- **[MODIFY] `getKelasForAction()`**:
  - Ubah FROM table dari `sistem.kelas` menjadi `akademik.kelas`.
  - Hapus JOIN ke `jenjang` (karena tidak ada kolom `id_jenjang` di `akademik.kelas` baru) dan urutkan hanya berdasarkan `nama_kelas`.
- **[MODIFY] `getSiswaByKelas()`**:
  - Ganti `id_kelas = :id_kelas` menjadi filter relasional yang tepat (jika menggunakan ID) atau filter *string* `kelas_saat_ini` tergantung data aktual (saat ini kita akan asumsikan filter dinonaktifkan atau disesuaikan).

## Verification Plan
### Manual Verification
1. Masuk ke halaman `/pengguna` (Tab Operator, Guru, Siswa, dan Mutasi).
2. Pastikan tabel *data table* bisa dimuat dengan sukses tanpa muncul notifikasi error Axios 500.
3. Klik tombol *Action* untuk memunculkan modal 'Aksi Kelas' dan pastikan data *dropdown* kelas termuat dengan benar (tidak ada error *Internal Server*).

---
## [Perbaikan Error Tab Pengguna & Modal Siswa]
**Waktu**: 19:54 WIB
**Status**: Dieksekusi

# Perbaikan Error Tab Pengguna (Guru/Karyawan) & Routing Edit Siswa

Berdasarkan analisis *log* dan struktur *source code*, terdapat dua akar masalah yang menyebabkan sisa error pada modul Pengguna:

1. **Tab Guru, Karyawan, Operator Error 500**: Query database di `PenggunaModel.php` masih menggunakan *integer role mapping* MySQL lawas (contoh: Role Guru = `3`). Padahal pada arsitektur PostgreSQL saat ini, kolom `role_id` bertipe UUID. Ini menyebabkan galat *SQLSTATE[22P02]: invalid input syntax for type uuid*.
2. **Tombol Tambah/Edit Siswa Mengarah ke Halaman Kosong (SiswaController not found)**: File view frontend `pengguna_index.php` secara sengaja melempar (redirect) *user* ke URL lawas `/siswa/edit` dan `/siswa/tambah`. Saat ini kontroler lawas tersebut telah dihapus dalam transisi arsitektur *Modular*, sehingga terjadi *fatal error* di `index.php`. Modal edit siswa (berbasis pop-up AJAX) sebenarnya sudah tersedia dan dapat memproses data, namun sengaja dimatikan sementara oleh *script JS*.

## Proposed Changes

### 1. `app/Modules/Sistem/Models/PenggunaModel.php`
- **[MODIFY]** Properti `$roleMap` akan diubah fungsinya untuk merepresentasikan `nama_role` (string) yang ada di tabel `core.roles`, BUKAN integer statis.
  ```php
  private array $roleMap = [
      'operator' => 'operator_sekolah',
      'guru' => 'guru',
      'karyawan' => 'karyawan'
  ];
  ```
- **[MODIFY]** Query `getPaginated` pada bagian *staff* (`else` blok). Saya akan menambahkan `JOIN core.roles r ON u.role_id = r.id` dan mengubah kondisi klausa pencarian peran dari `WHERE u.role_id = :role_id` menjadi `WHERE r.nama_role = :role_name`. Hal ini lebih dinamis dan anti-gagal, karena kita tidak perlu menebak UUID dari seeder roles.

### 2. `views/pengguna_index.php`
- **[MODIFY]** Metode Vue `openCreateModal()`: Menghapus kondisi `if (this.activeTab === 'siswa') { window.location.href = ... }`. Form *modal pop-up* akan digunakan sepenuhnya untuk pendaftaran siswa baru.
- **[MODIFY]** Metode Vue `openEditModal()`: Menghapus kondisi *redirect*. Mengisi objek `this.form` secara reaktif dengan properti siswa: `nisn`, `nis`, `tanggal_lahir`, `tempat_lahir`, `jenis_kelamin`, `alamat`, `nama_wali`, `kontak_wali`.
- **[MODIFY]** Metode Vue `resetForm()`: Menginisialisasi *state* form kosong yang mendukung field lengkap khusus untuk siswa.

> [!WARNING]
> Dengan perubahan ini, fitur Tambah dan Edit Siswa akan sepenuhnya menggunakan *Modal Pop-Up AJAX*, tidak lagi berpindah halaman (refresh). Hal ini sejalan dengan arsitektur UI/UX modern kita. 

## Verification Plan

### Automated Tests
- Menjalankan `php scratch/tests/test_security_audit.php` untuk memastikan perbaikan PHP tidak merusak parameter *prepared statement* keamanan.
- Menjalankan PHPStan (Level 5) pada file `PenggunaModel.php`.

### Manual Verification
1. Buka `http://localhost/sinta/pengguna`.
2. Klik tab Guru, Karyawan, dan Operator -> Pastikan tabel memuat dan tidak muncul peringatan 500 Axios Error.
3. Klik tab Siswa -> Klik "Edit" pada salah satu siswa -> Pastikan Modal Pop-up terbuka dan formulir terisi dengan data riwayat siswa tanpa ter-redirect ke `id=undefined`.

