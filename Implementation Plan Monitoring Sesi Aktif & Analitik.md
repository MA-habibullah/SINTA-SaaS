# Implementation Plan: Monitoring Sesi Aktif & Analitik

Rencana ini merinci langkah-langkah untuk mengimplementasikan sistem **Monitoring Sesi Aktif & Analitik** untuk memantau aktivitas login harian, melacak pengguna online real-time, dan melakukan pembersihan log riwayat data retention.

## User Review Required

> [!IMPORTANT]
> **Desain Skema Tanpa Foreign Key Berlebih**
> - Kolom `user_id` pada tabel `active_sessions` tidak akan diikat foreign key ke tabel `users` secara kaku. Hal ini karena user login dapat berupa Admin/Staff (tabel `users`) maupun Siswa (tabel `siswa`). Sebagai gantinya, join relasi dilakukan secara dinamis melalui query level server (`COALESCE` dari `users` dan `siswa`).
> - Pembersihan riwayat sesi (data retention) dibatasi oleh filter **tenant_id** masing-masing sekolah, kecuali Super Admin yang berwenang menghapus riwayat log platform secara global.

## Proposed Changes

---

### [Component: Database & Migrations]

#### [NEW] [2026_06_21_create_active_sessions_table.php](file:///c:/xampp/htdocs/dapodik-spmb/database/migrations/2026_06_21_create_active_sessions_table.php)
* Membuat tabel `active_sessions` dengan primary key UUID, indeks unik gabungan `(user_id, tanggal_login)` untuk keperluan upsert, serta foreign key ke tabel `tenants`.
* Memperbarui URL menu ID 16 ("Monitoring Sesi Aktif") di tabel `menus` menjadi `/dapodik-spmb/utilitas/sesi-aktif`.

---

### [Component: Core Session Tracking Helper]

#### [NEW] [SessionTracker.php](file:///c:/xampp/htdocs/dapodik-spmb/app/Helpers/SessionTracker.php)
* Membuat helper statis `SessionTracker::track()` untuk mencatat/memperbarui sesi login harian pengguna secara efisien (Upsert via MySQL `ON DUPLICATE KEY UPDATE`).

#### [MODIFY] [index.php](file:///c:/xampp/htdocs/dapodik-spmb/index.php)
* Daftarkan route baru:
  - `/utilitas/sesi-aktif` -> Render halaman view sesi aktif.
  - `/api/v1/sessions/data` -> Endpoint data online real-time & grafik tren harian.
  - `/api/v1/sessions/retention` -> Endpoint hapus riwayat data retention.
* Masukkan pemanggilan helper tracker `App\Helpers\SessionTracker::track()` tepat setelah pemuatan session login terdeteksi pada baris awal entry point.

---

### [Component: Controller]

#### [NEW] [ActiveSessionController.php](file:///c:/xampp/htdocs/dapodik-spmb/app/Controllers/ActiveSessionController.php)
* Membuat controller untuk mengelola visualisasi analitik dan retensi:
  - `index()`: Memvalidasi otorisasi dan merender view `active_sessions`.
  - `fetchDataApi()`: Mengambil list user online (aktivitas < 15 menit) dan agregasi login harian (30 hari terakhir) dengan query filter tenant yang aman.
  - `deleteRetentionApi()`: Menjalankan penghapusan log sesi sebelum tanggal yang dipilih, dan mencatatkan aksi ini ke dalam `activity_logs`.

---

### [Component: Views]

#### [NEW] [active_sessions.php](file:///c:/xampp/htdocs/dapodik-spmb/views/active_sessions.php)
* Membuat view halaman monitoring sesi:
  - Grafik visual interaktif (menggunakan **Chart.js** via CDN) untuk memetakan tren login unik harian.
  - Tabel reaktif daftar pengguna online (Nama, Peran, IP Address, Browser/Agent, Waktu).
  - Form pembersihan log berbasis tanggal (retention utility) dengan konfirmasi SweetAlert2.

---

## Verification Plan

### Automated Tests
* Jalankan verifikasi linting PHP CLI:
  ```bash
  php -l index.php
  php -l app/Helpers/SessionTracker.php
  php -l app/Controllers/ActiveSessionController.php
  php -l views/active_sessions.php
  ```

### Database Migration
* Jalankan perintah CLI migrasi untuk membuat tabel `active_sessions` dan mendaftarkan URL menu:
  ```bash
  php migrate.php
  ```

### Manual Verification
1. Login ke aplikasi, muat beberapa halaman.
2. Buka menu **Sistem & Utilitas -> Monitoring Sesi Aktif**.
3. Verifikasi grafik tren login termuat dengan benar (menunjukkan login unik hari ini).
4. Verifikasi nama akun Anda terdaftar pada tabel pengguna online di bawah grafik.
5. Coba lakukan pembersihan retensi log dengan memilih tanggal lampau dan verifikasi log sesi berhasil dibersihkan serta tercatat di **Log Aktivitas**.
