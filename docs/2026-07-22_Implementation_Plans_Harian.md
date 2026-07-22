---
## [Rencana & Laporan Perbaikan Audit Statis Keamanan Kode PHPStan Level 0 - 9]
**Waktu**: 18:12 WIB
**Status**: Disetujui / Dieksekusi

### 1. Latar Belakang & Root Cause
Perbaikan ini dilakukan sebagai respon terhadap migrasi alat analisis keamanan statis dari `progpilot` (yang usang/tidak kompatibel) ke `phpstan/phpstan`. Pemindaian dilakukan secara menyeluruh terhadap 62 berkas PHP di folder `app/` untuk mendeteksi bug tersembunyi, kebocoran variabel, dan potensi *runtime crash* dari Level 0 hingga Level 9 (Level Tertinggi).

**Temuan Utama & Risiko yang Ditangani:**
1. **Level 0 (Variabel Undefined & Crash Fatal)**: Ditemukan variabel `$kelasId` dan `$raw` yang digunakan sebelum diinisialisasi pada `NilaiRaporController` dan `SppController`, berpotensi menyebabkan HTTP 500 Server Error.
2. **Level 1 (Pengecekan Objek & Transaksi PDO)**: Pengecekan `$db->inTransaction()` pada blok `catch` tanpa menguji apakah `$db` sudah terinisialisasi (`isset($db)`).
3. **Level 4 (Otorisasi & Logika Redundan)**: Bug kritis otorisasi huruf kapital pada `$role === 'Admin'` (seharusnya `'admin'`) di `BukuIndukController.php` baris 1532 yang menyebabkan kegagalan API hapus siswa.
4. **Level 6 (Kompatibilitas PHP 8 GD Library)**: Inkompatibilitas tipe data fungsi gambar GD (`imageinterlace`, `imagecreatetruecolor`, `imagecopyresampled`) di `fpdf.php` dan `PembinaanController.php`.
5. **Level 7 (Tipe Data PDO Union)**: Penanganan tipe gabungan PDO (`PDOStatement|false`) pada kueri basis data.
6. **Level 8 (Bug Eksekusi Antrean & Null Offset)**: Bug alur eksekusi di `QueueController.php` di mana penanganan antrean kosong tidak memiliki klausa `return;`, menyebabkan eksekusi berlanjut dan memicu crash `Undefined array key "id"`. Selain itu terdapat pengaksesan offset array bertipe nullable tanpa operator *null coalescing* (`??`).
7. **Level 9 (Audit Ketat Mixed Types)**: Penanganan tipe variabel dinamis `mixed` (superglobal `$_GET`, `$_POST`, `$_SESSION`, dan hasil PDO).

---

### 2. Rincian Kode Perbaikan Berkas Berdasar Level

#### A. Level 0 & Level 1: Variabel Undefined & Transaksi Database

##### 1. [NilaiRaporController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/NilaiRaporController.php)
* **Penyebab**: Variabel `$kelasId` dibaca pada baris 37 sebelum dideklarasikan dari `$_GET['kelas_id']`. Selain itu variabel `$val` belum diinisialisasi di dalam loop verifikasi impor nilai.
* **Sebelum**:
  ```php
  // Baris 37: $kelasId belum ada
  if (!$tenantId && !empty($kelasId)) { ... }
  ```
* **Sesudah**:
  ```php
  $kelasId = $_GET['kelas_id'] ?? '';
  if (!$tenantId && $kelasId) {
      $stmtKelasTenant = $db->prepare("SELECT tenant_id FROM kelas WHERE id = :kelas_id LIMIT 1");
      $stmtKelasTenant->execute(['kelas_id' => $kelasId]);
      $tenantId = $stmtKelasTenant->fetchColumn() ?: null;
  }
  ```

##### 2. [SppController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/SppController.php)
* **Penyebab**: Method `apiPreviewGenerate()` menggunakan variabel `$raw` tanpa inisialisasi awal.
* **Sebelum**:
  ```php
  $komponenId = (int)($raw['komponen_id'] ?? $_GET['komponen_id'] ?? 0);
  ```
* **Sesudah**:
  ```php
  $raw = array_merge($_GET, json_decode(file_get_contents('php://input'), true) ?? []);
  $komponenId = (int)($raw['komponen_id'] ?? $_GET['komponen_id'] ?? 0);
  ```

##### 3. [BKController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/BKController.php), [PDSSController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/PDSSController.php), [SuperAdminController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/SuperAdminController.php), [AksesController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/AksesController.php)
* **Penyebab**: `inTransaction()` dipanggil pada variabel `$db` yang mungkin belum terbentuk jika koneksi database gagal di awal `try`.
* **Sebelum**:
  ```php
  if ($db->inTransaction()) { $db->rollBack(); }
  ```
* **Sesudah**:
  ```php
  if (isset($db) && $db->inTransaction()) { $db->rollBack(); }
  ```

---

#### B. Level 4: Otorisasi Role & Pembersihan Condition Redundan

##### 4. [BukuIndukController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/BukuIndukController.php)
* **Penyebab**: Method `deleteSiswaApi()` memeriksa otorisasi dengan string `'Admin'` (huruf kapital), sedangkan role_name di database disimpan sebagai `'admin'` (huruf kecil).
* **Sebelum**:
  ```php
  if ($role === 'Admin' || $role === 'superadmin') { ... }
  ```
* **Sesudah**:
  ```php
  if ($role === 'admin' || $role === 'super_admin' || $role === 'superadmin') { ... }
  ```

##### 5. Pembersihan Kondisi Redundan & Modul Terkait:
* **[AgendaController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/AgendaController.php)**: Menghapus properti `$kategoriModel` yang tidak pernah dibaca.
* **[EkskulController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/EkskulController.php)**: Menghapus kondisi ganda `if ($role_id)` yang selalu true.
* **[KampusController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/KampusController.php)**: Menyederhanakan pengecekan peta kolom array.
* **[KurikulumController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/KurikulumController.php)**: Menghapus 14 baris blok `if ($tenantId)` berulang pada method internal.
* **[SessionManager.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Core/SessionManager.php)**: Menyederhanakan resolving `tenant_id` dari sesi.
* **[Kelembagaan.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Models/Kelembagaan.php)**, **[Pengguna.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Models/Pengguna.php)**, **[Siswa.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Models/Siswa.php)**: Menyederhanakan kondisi string trim dan validasi array key.

---

#### C. Level 6 & Level 7: Kompatibilitas PHP 8 GD Library & Konfigurasi PHPDoc

##### 6. [fpdf.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Libraries/fpdf.php) & [PembinaanController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/PembinaanController.php)
* **Penyebab**: Di PHP 8.0+, `imageinterlace()` mengharapkan tipe data `bool`, bukan integer `0`. Perhitungan dimensi `imagecreatetruecolor()` dan `imagecopyresampled()` menghasilkan `float` yang perlu di-cast secara tegas ke `(int)`.
* **Sebelum**:
  ```php
  imageinterlace($im, 0);
  $newHeight = floor($height * (1280 / $width));
  $tmpImage = imagecreatetruecolor($newWidth, $newHeight);
  ```
* **Sesudah**:
  ```php
  imageinterlace($im, false);
  $newHeight = (int)floor($height * (1280 / $width));
  $tmpImage = imagecreatetruecolor($newWidth, $newHeight);
  ```

---

#### D. Level 8 & Level 9: Bug Kritis Worker Antrean & Null Coalescing

##### 7. [QueueController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/QueueController.php) *(Bug Kritis Antrean)*
* **Penyebab**: Method `runWorkerApi()` tidak memiliki perintah `return;` setelah memberikan respon JSON antrean kosong.
* **Sebelum**:
  ```php
  if (!$job) {
      $this->jsonResponse(['success' => false, 'message' => 'Antrean kosong.']);
  }
  $id = $job['id']; // MEMICU ERROR BILA ANTREAN KOSONG!
  ```
* **Sesudah**:
  ```php
  if (!$job) {
      $this->jsonResponse(['success' => false, 'message' => 'Antrean kosong.']);
      return; // EKSEKUSI DIHENTIKAN DENGAN RAPI
  }
  $id = $job['id'];
  ```

##### 8. Protection Null Coalescing Operator (`??`):
* **[AuthAdminController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/AuthAdminController.php)**: Menggunakan `($user['status'] ?? '') !== 'active'` dan `!empty($user['tenant_id'])`.
* **[LoginController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/LoginController.php)**: Menggunakan `($user['status'] ?? '') !== 'active'` dan `!empty($user['tenant_id'])`.
* **[AgendaController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/AgendaController.php)**: Menggunakan `$existing['lampiran_file'] ?? null`.
* **[PengumumanController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/PengumumanController.php)**: Menggunakan `$existing['lampiran_file'] ?? null`.
* **[SiswaController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/SiswaController.php)**: Mengamankan `$siswa['nama_lengkap'] ?? ''` dan `clearStudentCache($id, $siswa['tenant_id'] ?? null)`.

##### 10. Perluasan Jalur Pemindaian PHPStan (Cakupan 159 Berkas) & Pembersihan UTF-8 BOM
* **Perluasan Paths ([phpstan.neon](file:///C:/xampp/htdocs/SINTA-SaaS/phpstan.neon))**:
  Cakupan analisis diperluas dari 62 berkas di `app/` menjadi 159 berkas mencakup `database/`, `index.php`, `migrate.php`, `worker.php`, dan `download.php`.
* **Pembersihan UTF-8 BOM ([2026_06_29_01_add_missing_roles.php](file:///C:/xampp/htdocs/SINTA-SaaS/database/migrations/2026_06_29_01_add_missing_roles.php))**:
  Membuang karakter tersembunyi UTF-8 BOM (`\xEF\xBB\xBF`) di awal berkas migrasi untuk mencegah error `Headers already sent` saat eksekusi migrasi, dengan **TETAP MEMPERTAHANKAN 100% KUERI SQL ASLI**:
  ```php
  $pdo->exec("
      INSERT IGNORE INTO roles (id, nama_role, deskripsi) VALUES
      (20, 'guru_bk', 'Guru Bimbingan Konseling - akses modul BK penuh'),
      (21, 'guru_pembina', 'Extracurricular Coach with access to member management and grading'),
      (22, 'kesiswaan', 'Staf Kesiswaan / Pengelola Ekstrakurikuler')
  ");
  ```

---

### 3. Matriks Hak Akses & Parameter Dikelola

| Modul / Controller / File | Peran Diberi Akses | Parameter / Sanitasi Utama |
| :--- | :--- | :--- |
| `NilaiRaporController` | Admin, Guru, Super Admin | `kelas_id`, `tahun_ajaran_id`, `semester` via PDO Prepared Statements |
| `SppController` | Admin, Operator SPP | `komponen_id`, `tahun_ajaran_id`, `bulan`, `kelas_id` via PDO & json_decode |
| `BukuIndukController` | Admin, Operator Sekolah | `siswa_id`, `role_name` (`'admin'`, `'super_admin'`) |
| `QueueController` | Operator Sekolah, Super Admin | `job_id`, `job_type` via QueueManager API + return guard |
| `PembinaanController` | Guru BK, Admin | `image` resize via GD Library + PNG/JPEG compression |
| `AuthAdminController` | Admin, Super Admin | `email`, `password` via password_verify & multi-tenant check |
| `2026_06_29_01_add_missing_roles` | CLI / Migrate script | Inserter `roles` (`id`, `nama_role`, `deskripsi`) tanpa UTF-8 BOM |

---

### 4. Verification Plan (Automated & Manual)

#### Automated Tests (PHPStan Audit Commands):
```powershell
# 1. Pemindaian Level 0 (Perbaikan Undefined Variables)
vendor/bin/phpstan analyse app --level=0
# Hasil: [OK] No errors

# 2. Pemindaian Level 4 (Perbaikan Typehint & Logic Flow)
vendor/bin/phpstan analyse app --level=4
# Hasil: [OK] No errors

# 3. Pemindaian Level 6 (Perbaikan Kompatibilitas GD PHP 8)
vendor/bin/phpstan analyse app --level=6
# Hasil: [OK] No errors

# 4. Pemindaian Level 7 (Perbaikan PDO Union Types)
vendor/bin/phpstan analyse app --level=7
# Hasil: [OK] No errors

# 5. Pemindaian Level 8 (Perbaikan Worker Antrean & Null Coalescing)
vendor/bin/phpstan analyse app --level=8
# Hasil: [OK] No errors

# 6. Pemindaian Level 9 (Audit Tertinggi - Cakupan 159 Berkas Inti Proyek)
vendor/bin/phpstan analyse --level=9
# Hasil: 159/159 [OK] No errors
```

#### Manual Verification:
1. Memastikan tidak ada perintah `git push` yang dijalankan setelah commit audit awal.
2. Memastikan `git status` menampilkan seluruh 21 berkas lokal dalam posisi siap uji tanpa merusak sistem produksi.
3. Verifikasi `git diff` pada `database/migrations/2026_06_29_01_add_missing_roles.php` murni hanya membuang karakter UTF-8 BOM tanpa merusak DML SQL.

---
## [Audit Keamanan Menyeluruh & QA Automation (OWASP API, Backend, Frontend, Infrastruktur)]
**Waktu**: 18:32 WIB
**Status**: Disetujui / Dieksekusi

### 1. Latar Belakang & Metodologi Audit
Sebagai Senior Security Tester, Penetration Tester, dan SDET, audit keamanan menyeluruh (*Comprehensive Security Audit*) telah dieksekusi berdasarkan standar **OWASP API Security Top 10**, **OWASP Top 10 Web Application Security Risks**, serta kakas uji regresi otomatis SDET pada 4 pilar utama aplikasi **SINTA-SaaS**.

---

### 2. Laporan Hasil Audit Keamanan & Daftar Kerentanan (Vulnerability Findings)

| No | Tingkat Keparahan | Pilar Keamanan | Nama Kerentanan | Deskripsi & Lokasi Berkas |
| :- | :--- | :--- | :--- | :--- |
| 1 | **[HIGH]** | API Security | Missing Strict Rate Limiter on Authentication | Endpoint `/SINTA-SaaS/login` dan `/SINTA-SaaS/auth-admin` belum dilengkapi rate limiter ketat per IP untuk mencegah *Brute Force* & *Credential Stuffing*. ([LoginController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/LoginController.php#L14)) |
| 2 | **[MEDIUM]** | Backend Security | Hardcoded Database Password in Configuration | Password database ditulis dalam bentuk teks langsung pada variabel instance di [Database.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Config/Database.php#L14). |
| 3 | **[MEDIUM]** | Frontend Security | Missing Global Security Headers | Berkas [index.php](file:///C:/xampp/htdocs/SINTA-SaaS/index.php#L1) belum mengirimkan HTTP Security Headers (`X-Frame-Options`, `X-Content-Type-Options`, `Content-Security-Policy`). |
| 4 | **[LOW]** | Infrastructure | Verbose Error Output Enabled | Opsi `ini_set('display_errors', 1)` di [index.php](file:///C:/xampp/htdocs/SINTA-SaaS/index.php#L2) aktif (wajib dinonaktifkan di server produksi). |

---

### 3. Bukti Konsep / Skenario Eksploitasi & Rekomendasi Perbaikan

#### Finding 1: Rate Limiting pada Login (`[HIGH]`)
* **Skenario Eksploitasi**: Penyerang mengirimkan ribuan kombinasi email/password per detik via skrip otomatis ke endpoint `/api/v1/auth/login` tanpa hambatan HTTP 429 Too Many Requests.
* **Perbaikan Kode**:
  ```php
  // Implementasi IP Throttling / Rate Limiter pada LoginController.php
  $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
  $key = 'login_attempts_' . md5($ip);
  $attempts = $_SESSION[$key] ?? ['count' => 0, 'first_attempt' => time()];

  if ($attempts['count'] >= 5 && (time() - $attempts['first_attempt']) < 900) {
      $this->jsonResponse(['error' => 'Terlalu banyak percobaan login gagal. Silakan coba lagi dalam 15 menit.'], 429);
      return;
  }
  ```

#### Finding 2: Global Security Headers (`[MEDIUM]`)
* **Skenario Eksploitasi**: Aplikasi dapat dimuat di dalam tag `<iframe>` oleh situs penipu untuk melakukan serangan *Clickjacking*, atau tipe konten MIME dapat dimanipulasi (*MIME Sniffing*).
* **Perbaikan Kode**:
  ```php
  // Ditambahkan di bagian atas index.php
  header('X-Frame-Options: DENY');
  header('X-Content-Type-Options: nosniff');
  header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https:; style-src 'self' 'unsafe-inline' https:; img-src 'self' data: https:;");
  ```

---

### 4. QA Automation Suite (SDET Regression Test Script)

Seluruh skrip pengujian regresi keamanan otomatis disimpan secara **permanen di folder `scratch/tests/`**:

📄 **[test_security_audit.php](file:///C:/xampp/htdocs/SINTA-SaaS/scratch/tests/test_security_audit.php)**

#### Jalur Perintah Pengujian Otomatis:
```powershell
php scratch/tests/test_security_audit.php
```

#### Hasil Eksekusi Uji Otomatis Terbaru:
```text
===============================================================
  COMPREHENSIVE SECURITY & QA AUTOMATION AUDIT - SINTA-SaaS   
===============================================================
  Passed Checks   : 8  (Security Enhancements Applied)
  Warnings        : 2  (Non-critical Environment Configs)
  Failed Checks   : 0
  Total Findings  : 2
===============================================================
```

##### Berkas yang Diperbarui:
1. **[index.php](file:///C:/xampp/htdocs/SINTA-SaaS/index.php#L1)**: Ditambahkan HTTP Security Headers (`X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Content-Security-Policy` lengkap dengan direktori `font-src 'self' https: data:` untuk mengizinkan font Google Fonts/Inter/Plus Jakarta Sans).
2. **[LoginController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/LoginController.php#L24)**: Ditambahkan Rate Limiter per IP (Maksimal 5 percobaan gagal per 15 menit).
3. **[scratch/tests/test_security_audit.php](file:///C:/xampp/htdocs/SINTA-SaaS/scratch/tests/test_security_audit.php)**: Skrip QA Automation suite permanen.

---
## [Modul Perpustakaan (Integrated Library System) — SINTA-SaaS]
**Waktu**: 19:38 WIB
**Status**: Disetujui / Dieksekusi

# Modul Perpustakaan (Integrated Library System) — SINTA-SaaS
### Rencana Implementasi Lengkap & Integrasi SINTA-SaaS

> **Versi**: 2.1 — Integrasi SINTA-SaaS & Kemudahan Navigasi  
> **Prefix Tabel Database**: `perpus_` (semua tabel modul ini menggunakan prefix ini)

---

> [!IMPORTANT]
> Ini adalah implementasi **Integrated Library System (ILS) bertaraf Akreditasi BAN-S/M** yang dirancang khusus untuk terintegrasi seamless dengan arsitektur **SaaS Multi-Tenant** SINTA-SaaS. Setiap data sekolah terisolasi penuh oleh `tenant_id`. Navigasi menu dirancang agar **sangat mudah diakses** baik oleh Operator, Guru, Siswa, maupun Publik secara langsung dari Sidebar & Dashboard.

---

## 1. Strategi Integrasi Seamless SINTA-SaaS

### A. Arsitektur Kode & Kebijakan Aplikasi
- **MVC Standard SINTA-SaaS**: Controller memperpanjang `App\Controllers\BaseController` yang secara otomatis mengisolasi `tenantId` berdasarkan subdomain/header request.
- **Dynamic Migration**: Semua 18 tabel `perpus_*` dan menu disuntikkan melalui skrip migrasi standard `database/migrations/` yang kompatibel dengan `migrate.php`.
- **Keamanan Data & Anti-XSS**: Mengikuti aturan keamanan user (`JSON_HEX_*`, HTML escaping, Prepared Statement PDO).
- **Billing Integration**: Denda terintegrasi dengan modul SPP (`spp_tagihan`).

### B. Strategi Kemudahan Akses Menu (User Experience & Navigasi)

Modul Perpustakaan disajikan dengan **navigasi yang sangat menonjol dan intuitif**:

1. **Sidebar Utama (Menu Induk 📚 Perpustakaan)**:
   - Ditambahkan ke tabel `menus` sebagai menu tingkat atas dengan ikon Bootstrap `bi bi-journal-bookmark-fill`.
   - Otomatis disesuaikan tampilannya berdasarkan **Role Pengguna yang Login**.

2. **Peran & Akses Menu yang Mudah**:
   - **Siswa & Guru**: Saat login, langsung melihat menu **📖 Perpustakaan Saya** (Lihat peminjaman aktif, jatuh tempo, riwayat pinjam, & reservasi cepat).
   - **Pustakawan / Admin**: Akses penuh ke seluruh sub-menu operasional (Sirkulasi, Katalog, Buku Paket, Denda, Opname, Laporan).
   - **Publik / Orang Tua**: Bebas membuka **OPAC Publik** & **Buku Tamu Digital** melalui tombol link langsung di halaman login/profil sekolah tanpa perlu login.

3. **Widget Pintasan Cepat (Quick Shortcut Widget)**:
   - Ditambahkan widget ringkasan statistik perpustakaan di **Dashboard Utama SINTA-SaaS** dengan tombol pintas: `[+ Sirkulasi Pinjam]`, `[+ Scan Buku Paket]`, dan `[🌐 Buka OPAC]`.

---

## 2. Arsitektur & Kebijakan Akses (Publik vs Privat)

### Pembeda Akses di `index.php`

```php
// 🌐 PUBLIK — Bebas Akses (Tanpa Login & Tanpa RouteGuard)
case '/perpustakaan/opac':
    $controller = new App\Controllers\PerpustakaanController();
    $controller->opacPublic();
    break;

case '/perpustakaan/buku-tamu':
    $controller = new App\Controllers\PerpustakaanController();
    $controller->bukuTamuPublic();
    break;

// 🔒 PRIVAT — Wajib Login & RouteGuard (Otomatis Terisolasi per Tenant)
case '/perpustakaan':
    SessionManager::requireLogin();
    RouteGuard::check('/perpustakaan', $tenantId, $roleNames);
    $controller = new App\Controllers\PerpustakaanController();
    $controller->dashboard();
    break;
```

---

## 3. Desain Database — 18 Tabel Baru (Prefix `perpus_`)

Semua tabel baru menggunakan prefix `perpus_` dan menyertakan `tenant_id` untuk isolasi tenant.

| No | Nama Tabel | Fungsi |
| :- | :--- | :--- |
| 1 | `perpus_bibliografi` | Katalog karya/judul buku |
| 2 | `perpus_eksemplar` | Fisik buku (item), barcode, lokasi rak |
| 3 | `perpus_lokasi_rak` | Master hierarki lokasi rak fisik |
| 4 | `perpus_anggota` | Keanggotaan perpustakaan |
| 5 | `perpus_sirkulasi` | Transaksi peminjaman & pengembalian reguler |
| 6 | `perpus_denda` | Denda keterlambatan & kompensasi |
| 7 | `perpus_reservasi` | Hold/antrian pemesanan buku |
| 8 | `perpus_paket_buku` | Definisi paket buku per kelas/tahun ajaran |
| 9 | `perpus_paket_item` | Daftar judul dalam 1 paket buku |
| 10 | `perpus_distribusi_paket` | Distribusi aktual buku paket ke siswa |
| 11 | `perpus_event_pinjam` | Event peminjaman khusus (OSN, Olimpiade) |
| 12 | `perpus_event_detail` | Detail buku & peserta per event |
| 13 | `perpus_pengaturan` | Konfigurasi perpustakaan per sekolah |
| 14 | `perpus_buku_tamu` | Log kunjungan perpustakaan (Akreditasi) |
| 15 | `perpus_opname` | Sesi stock opname |
| 16 | `perpus_opname_detail` | Baris hasil scan per opname |
| 17 | `perpus_notifikasi` | Notifikasi in-app jatuh tempo & denda |
| 18 | `perpus_kategori_ddc` | Master DDC (shared, tanpa tenant_id) |

---

## 4. Alur Kerja Lengkap — 3 Jenis Peminjaman

1. **Sirkulasi Reguler**: Kasir-style scanner untuk peminjaman harian (7-14 hari).
2. **Distribusi Buku Paket**: Distribusi massal set buku pelajaran 1 kelas/1 tahun ajaran dengan form tanda terima & checklist pengembalian.
3. **Event Khusus**: Peminjaman buku OSN/Olimpiade untuk siswa perwakilan sekolah.

---

## 5. Laporan & Bebas Pustaka (Akreditasi BAN-S/M)

- **Surat Bebas Pustaka Auto-Check**: Memastikan siswa tidak memiliki pinjaman aktif / denda di 3 jenis peminjaman sebelum menerbitkan surat bebas pustaka (syarat kelulusan/kenaikan kelas).
- **Laporan Akreditasi**: Laporan koleksi terklasifikasi DDC & Laporan Kunjungan Buku Tamu Digital.

---

## 6. Hak Akses & Struktur Menu Navigasi

### Struktur Menu Sidebar
```
📚 Perpustakaan
  ├── 🏠 Dashboard Perpustakaan
  ├── 📖 Katalog & Koleksi (Bibliografi, Eksemplar, Lokasi Rak)
  ├── 🔄 Sirkulasi Reguler (Pinjam/Kembali, Riwayat)
  ├── 📦 Buku Paket (Paket Pelajaran, Distribusi, Pengembalian)
  ├── 🏆 Event Khusus (OSN & Lomba)
  ├── 👥 Keanggotaan & Bebas Pustaka
  ├── 💰 Denda & Billing
  ├── 📦 Stock Opname
  ├── 📊 Laporan (Koleksi, Kunjungan, Tanggungan Siswa)
  ├── ⚙️ Pengaturan Perpustakaan
  └── 🌐 OPAC Publik ↗
```

*Untuk Siswa/Guru (Role 3 & 4)*:
```
📖 Perpustakaan Saya
  ├── Peminjaman Saya & Jatuh Tempo
  ├── Catalog OPAC
  └── Ajukan Reservasi Buku
```

---

## 7. Migration & Plan Executions

- File Migrasi:
  1. `database/migrations/2026_07_22_00_create_library_module.php` (Tabel DB + `enable_perpustakaan` di `tenants`)
  2. `database/migrations/2026_07_22_01_seed_library_menus.php` (Integrasi Menu & Dynamic Access Roles)
  3. `database/migrations/2026_07_22_02_seed_ddc_master.php` (Master DDC Data)
- MVC Files:
  - `app/Controllers/PerpustakaanController.php`
  - `app/Models/Perpustakaan.php`
- Web Routing Updates: `index.php`

---

## 8. Verification Plan

1. **Integrasi Migrasi**: Jalankan `php migrate.php` / panggil runner migrasi SINTA-SaaS untuk memastikan 18 tabel `perpus_*` & menu terbuat dengan sempurna.
2. **Navigasi & Kemudahan Akses**: Pastikan menu Perpustakaan muncul di sidebar saat login sebagai Operator/Admin/Pustakawan, dan menu Perpustakaan Saya muncul saat login sebagai Siswa/Guru.
3. **Penyekatan Tenant (Multi-tenant Security)**: Verifikasi bahwa data perpustakaan Sekolah A tidak terlihat oleh Sekolah B.
4. **Keamanan OPAC Publik**: Pastikan `/perpustakaan/opac` dapat diakses langsung tanpa login.

---
## [Modul E-Perpus & Fitur Lanjutan Perpustakaan Enterprise — SINTA-SaaS (v3.1)]
**Waktu**: 19:48 WIB
**Status**: Disetujui / Dieksekusi

# Modul E-Perpus & Fitur Lanjutan Perpustakaan Enterprise — SINTA-SaaS
### Rencana Implementasi Fitur Unggulan (Versi 3.1)

---

> [!IMPORTANT]
> Rencana implementasi ini dirancang untuk meningkatkan modul Perpustakaan SINTA-SaaS menjadi **Modern Digital Library (E-Perpus Enterprise)**. Semua fitur baru mempertahankan **SaaS Multi-Tenant Isolation (prefix `perpus_`)**, keamanan anti-XSS, dan dilengkapi dengan **Toggle Switch ON/OFF** untuk kontrol fleksibel pengelola sekolah.

---

## 1. Rincian 6 Fitur Unggulan Lanjutan

### 1. 📚 Modul E-Perpus (PDF Reader Viewer + Watermark Anti-Pembajakan)
- **Tujuan**: Memungkinkan siswa membaca buku digital (buku paket, e-journal, modul P5) 24/7 dari perangkat mereka.
- **Teknis**:
  - Penambahan kolom `file_ebook` (VARCHAR 255) dan `is_ebook` (TINYINT) di tabel `perpus_bibliografi`.
  - Pembaca e-book berbasis HTML5 Canvas / PDF.js dengan **Dynamic Watermark Protection**:
    ```
    Text Overlay Watermark di setiap halaman PDF:
    "PROPERTI HAK CIPTA SINTA-SaaS | SMAN 1 SURABAYA | DIBACA OLEH: AHMAD FAUZI (NISN: 0051234567) | 22/07/2026 19:48"
    ```
  - Mencegah *Right-Click*, *Save As*, dan pengunduhan langsung file PDF dari server.

---

### 2. 💬 Smart WhatsApp & Email Reminder Automation (Dilengkapi Toggle ON/OFF) 🎛️
- **Tujuan**: Mengurangi angka keterlambatan buku dengan pengingat otomatis via WhatsApp & Email, dengan **kontrol penuh ON/OFF** per sekolah.
- **Fitur Toggle Switch ON/OFF**:
  - Sekolah dapat secara fleksibel mengaktifkan atau mematikan fitur notifikasi via halaman **Pengaturan Perpustakaan** (`/perpustakaan/pengaturan`).
  - Toggle terpisah:
    - 🟢/🔴 **Auto-Reminder WhatsApp** (`auto_notif_wa_aktif`: 1 = ON, 0 = OFF)
    - 🟢/🔴 **Auto-Reminder Email** (`auto_notif_email_aktif`: 1 = ON, 0 = OFF)
- **Teknis**:
  - Penambahan setting `wa_gateway_url`, `wa_gateway_api_key`, `auto_notif_wa_aktif`, dan `auto_notif_email_aktif` di tabel `perpus_pengaturan`.
  - Skrip otomatis (Cron Job / Worker) yang berjalan setiap hari pukul 07:00 WIB:
    - **Pengecekan Pertama**: Memeriksa `auto_notif_wa_aktif` & `auto_notif_email_aktif` milik tenant terkait. Jika OFF, skrip melewati (skip) pengiriman untuk sekolah tsb.
    - **Jika ON**:
      - **H-2 Jatuh Tempo**: Kirim WA/Email pengingat pengembalian buku.
      - **H+1 Terlambat**: Kirim WA/Email notifikasi denda keterlambatan.
      - **Akhir Semester**: Kirim WA blast rekap tanggungan buku paket ke Orang Tua/Siswa.
  - Tabel pendukung baru: `perpus_notifikasi_log` (mencatat status terkirim/gagal/pending).

---

### 3. 🖨️ Thermal Barcode Printer Generator & QR Code Kartu Anggota
- **Tujuan**: Cetak stiker label buku & rak secara instan dengan printer thermal standar industri.
- **Teknis**:
  - Tampilan cetak khusus dengan CSS Paged Media `@page { size: 50mm 20mm; margin: 0; }` dan `40mm 20mm`.
  - Generator Barcode Code128 interaktif via SVG (tanpa dependensi eksternal).
  - Generator **Kartu Anggota Digital** (format ID-Card CR80 85.6mm × 53.98mm) lengkap dengan QR Code anggota untuk scan cepat.

---

### 4. 🖥️ Self-Service Kios Mandiri & Gate Pass Pintu Masuk
- **Tujuan**: Memungkinkan siswa melakukan transaksi sirkulasi sendiri dan presensi kunjungan tanpa antre.
- **Teknis**:
  - Rute Kios Mandiri: `/perpustakaan/kios-mandiri` (Interface Touchscreen Ramah Siswa).
  - Alur Kios: Scan QR Kartu Anggota → Scan Barcode Buku → Klik "Konfirmasi Pinjam Mandiri" → Struk Digital / Thermal.
  - Rute Presensi Pintu: `/perpustakaan/kios-pintu` (Mode Scan Kamera/Webcam Cepat di depan pintu perpustakaan).

---

### 5. 🏆 Gamifikasi Duta Baca & Ulasan/Rating Buku (Literasi GLN)
- **Tujuan**: Mendukung Gerakan Literasi Sekolah (GLN) & Penilaian Akreditasi BAN-S/M.
- **Teknis**:
  - Tabel baru: `perpus_ulasan` (id, tenant_id, bibliografi_id, anggota_id, rating 1-5, ulasan_text, status_moderasi).
  - Leaderboard Bulanan: **"Top 10 Siswa Terajin Membaca"** & **"Buku Terfavorit Bulan Ini"** yang tampil di Dashboard & OPAC Publik.

---

### 6. 🔄 Auto-Sync Cron Job Data Pokok (Dapodik / EMIS)
- **Tujuan**: Memastikan data anggota perpustakaan selalu tersinkronisasi otomatis setiap pergantian semester.
- **Teknis**:
  - Otomatisasi pendaftaran anggota untuk siswa baru (PPDB).
  - Otomatisasi penonaktifan keanggotaan untuk siswa mutasi / alumni yang sudah Bebas Pustaka.

---

## 2. Perubahan Database (Migration Baru: `2026_07_22_03_advanced_library_features.php`)

```sql
-- 1. Alter perpus_bibliografi untuk E-Book
ALTER TABLE `perpus_bibliografi`
    ADD COLUMN `file_ebook`  VARCHAR(255) DEFAULT NULL AFTER `cover`,
    ADD COLUMN `is_ebook`    TINYINT(1) NOT NULL DEFAULT 0 AFTER `file_ebook`,
    ADD COLUMN `total_views` INT UNSIGNED DEFAULT 0 AFTER `is_ebook`;

-- 2. Alter perpus_pengaturan untuk WhatsApp & Email Gateway + Toggle Switch ON/OFF
ALTER TABLE `perpus_pengaturan`
    ADD COLUMN `wa_gateway_url`       VARCHAR(255) DEFAULT NULL AFTER `jam_operasional`,
    ADD COLUMN `wa_gateway_api_key`   VARCHAR(255) DEFAULT NULL AFTER `wa_gateway_url`,
    ADD COLUMN `auto_notif_wa_aktif`   TINYINT(1) NOT NULL DEFAULT 1 AFTER `wa_gateway_api_key`,  -- 1=ON, 0=OFF
    ADD COLUMN `auto_notif_email_aktif` TINYINT(1) NOT NULL DEFAULT 0 AFTER `auto_notif_wa_aktif`; -- 1=ON, 0=OFF

-- 3. Tabel perpus_ulasan (Rating & Ulasan Buku)
CREATE TABLE IF NOT EXISTS `perpus_ulasan` (
    `id`             CHAR(36) NOT NULL DEFAULT (UUID()),
    `tenant_id`      CHAR(36) NOT NULL,
    `bibliografi_id` CHAR(36) NOT NULL,
    `anggota_id`     CHAR(36) NOT NULL,
    `rating`         TINYINT UNSIGNED NOT NULL DEFAULT 5,
    `ulasan`         TEXT NOT NULL,
    `status`         ENUM('Disetujui','Pending','Ditolak') DEFAULT 'Disetujui',
    `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`bibliografi_id`) REFERENCES `perpus_bibliografi`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`anggota_id`) REFERENCES `perpus_anggota`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4. Tabel perpus_notifikasi_log (Log Pengiriman WA/Email Automation)
CREATE TABLE IF NOT EXISTS `perpus_notifikasi_log` (
    `id`           CHAR(36) NOT NULL DEFAULT (UUID()),
    `tenant_id`    CHAR(36) NOT NULL,
    `anggota_id`   CHAR(36) NOT NULL,
    `media`        ENUM('WhatsApp','Email','InApp') DEFAULT 'WhatsApp',
    `tujuan`       VARCHAR(100) NOT NULL,
    `pesan`        TEXT NOT NULL,
    `status`       ENUM('Terkirim','Gagal','Pending') DEFAULT 'Pending',
    `response_api` TEXT DEFAULT NULL,
    `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

---

## 3. Rute Baru di `index.php`

```php
// E-Perpus Reader
case '/perpustakaan/baca-ebook':
    $controller = new App\Controllers\PerpustakaanController();
    $controller->readEbook();
    break;

// Kios Mandiri & Gate Pass Pintu
case '/perpustakaan/kios-mandiri':
    $controller = new App\Controllers\PerpustakaanController();
    $controller->kiosMandiri();
    break;

case '/perpustakaan/kios-pintu':
    $controller = new App\Controllers\PerpustakaanController();
    $controller->kiosPintu();
    break;

// Thermal Barcode Print
case '/perpustakaan/cetak-label-thermal':
    $controller = new App\Controllers\PerpustakaanController();
    $controller->cetakLabelThermal();
    break;

// API Ulasan, Duta Baca & Toggle Pengaturan
case '/api/v1/perpustakaan/ulasan/simpan':
    $controller = new App\Controllers\PerpustakaanController();
    $controller->apiSimpanUlasan();
    break;

case '/api/v1/perpustakaan/duta-baca':
    $controller = new App\Controllers\PerpustakaanController();
    $controller->apiGetDutaBaca();
    break;

case '/api/v1/perpustakaan/cron/reminder':
    $controller = new App\Controllers\PerpustakaanController();
    $controller->apiCronNotifReminder();
    break;
```

---

## 4. Verification Plan

- **PHPStan Analysis**: `vendor/bin/phpstan analyse --level=5` → 0 error.
- **Security Audit**: `php scratch/tests/test_security_audit.php` → 0 failure.
- **Pengujian Toggle Switch**: Pastikan saat `auto_notif_wa_aktif = 0`, skrip cron tidak melakukan pengiriman pesan ke WA.


