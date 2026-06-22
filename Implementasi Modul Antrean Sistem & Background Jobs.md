# Rencana Implementasi: Modul Antrean Sistem & Background Jobs

Rencana ini merinci langkah-langkah untuk mengimplementasikan sistem antrean terintegrasi (*Job Queue*) dan pemrosesan latar belakang (*Background Jobs*) di platform SaaS Dapodik & SPMB. Sistem ini berguna untuk memindahkan tugas komputasi berat (seperti sinkronisasi data massal, import ribuan siswa, atau pengiriman blast email) ke background thread agar performa web tetap responsif.

---

## User Review Required

> [!IMPORTANT]
> **Arsitektur Pemrosesan Latar Belakang (Worker & CLI)**
> - **Mekanisme Tabel Antrean (`system_jobs`)**: Kami akan membuat tabel baru `system_jobs` yang bertindak sebagai antrean tugas. Setiap baris mewakili satu pekerjaan (*job*) yang berstatus `pending`, `processing`, `completed`, atau `failed`.
> - **CLI Worker (`worker.php`)**: File ini akan berjalan via PHP CLI (Command Line) secara independen. Di lingkungan produksi, ini bisa dijalankan via Cron Job berkala atau daemon monitor (seperti Supervisor).
> - **Pemicu Worker via Web Dashboard**: Untuk keperluan lingkungan lokal/pengembangan di mana server CLI daemon tidak selalu aktif, kami akan menyediakan tombol khusus **"Jalankan Satu Antrean"** di halaman UI admin yang memanggil worker secara manual via HTTP request.
> - **Simulasi Demo Real-time**: Kami menyediakan fungsionalitas simulasi tugas (seperti "Simulasi Sinkronisasi Pusdatin" dan "Simulasi Email Blast Massal") agar pengguna dapat menguji antrean sistem secara interaktif.

---

## Proposed Changes

### [Component: Database & Migrations]

#### [NEW] [2026_06_21_create_system_jobs_table.php](file:///c:/xampp/htdocs/dapodik-spmb/database/migrations/2026_06_21_create_system_jobs_table.php)
* Menambahkan tabel `system_jobs` dengan skema berikut:
  - `id` (INT UNSIGNED AUTO_INCREMENT PRIMARY KEY)
  - `tenant_id` (CHAR(36) NULL, FK ke `tenants`)
  - `job_type` (VARCHAR(100), e.g. `DEMO_SYNC`, `DEMO_EMAIL`, `CLEANUP_SESSIONS`)
  - `payload` (JSON, argumen pekerjaan)
  - `status` (ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending')
  - `attempts` (INT DEFAULT 0)
  - `error_message` (TEXT NULL)
  - `reserved_at` (DATETIME NULL)
  - `completed_at` (DATETIME NULL)
  - `created_at` (TIMESTAMP DEFAULT CURRENT_TIMESTAMP)
  - `updated_at` (TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)

#### [NEW] [2026_06_21_update_menu_antrean_url.php](file:///c:/xampp/htdocs/dapodik-spmb/database/migrations/2026_06_21_update_menu_antrean_url.php)
* Mengubah kolom `url` menu ID 17 ("Antrean Sistem & Background Jobs") dari `#` menjadi `/dapodik-spmb/utilitas/antrean` agar menu di sidebar aktif.

---

### [Component: Queue Helpers]

#### [NEW] [QueueManager.php](file:///c:/xampp/htdocs/dapodik-spmb/app/Helpers/QueueManager.php)
* Membuat kelas helper untuk mengelola antrean:
  - `push(string $jobType, array $payload, ?string $tenantId = null): bool`: Menambahkan *job* baru ke tabel `system_jobs`.
  - `pop(): ?array`: Mengambil *job* berstatus `pending` tertua, menandainya sebagai `processing` dan mengisi `reserved_at` (menggunakan database transaction lock).
  - `markCompleted(int $jobId): void`: Menandai *job* sukses.
  - `markFailed(int $jobId, string $error): void`: Menandai *job* gagal dan mencatat alasannya.

#### [NEW] [JobDispatcher.php](file:///c:/xampp/htdocs/dapodik-spmb/app/Jobs/JobDispatcher.php)
* Kelas pemroses *job* aktual di backend. Menangani logika berdasarkan `job_type`:
  - `DEMO_SYNC`: Mensimulasikan pemrosesan sinkronisasi dengan jeda 5 detik (`sleep(5)`) lalu mencatat status.
  - `DEMO_EMAIL`: Mensimulasikan pengiriman mass mailer blast.
  - `CLEANUP_SESSIONS`: Menjalankan pembersihan sesi lama otomatis.

---

### [Component: CLI Worker]

#### [NEW] [worker.php](file:///c:/xampp/htdocs/dapodik-spmb/worker.php)
* File CLI independen untuk menjalankan background task secara manual di terminal:
  - Sintaks: `php worker.php --run` (memproses seluruh antrean) atau `php worker.php --once` (memproses satu antrean).

---

### [Component: Routing]

#### [MODIFY] [index.php](file:///c:/xampp/htdocs/dapodik-spmb/index.php)
* Menambahkan case routing:
  - `/utilitas/antrean` -> `QueueController->index()` (Halaman Dashboard Monitoring)
  - `/api/v1/queue/data` -> `QueueController->fetchDataApi()` (Mengambil list job & metrik)
  - `/api/v1/queue/dispatch` -> `QueueController->dispatchDemoJobApi()` (Mengirim demo job baru)
  - `/api/v1/queue/retry` -> `QueueController->retryJobApi()` (Memproses ulang job gagal)
  - `/api/v1/queue/delete` -> `QueueController->deleteJobApi()` (Menghapus job dari antrean)
  - `/api/v1/queue/run-worker` -> `QueueController->runWorkerApi()` (Menjalankan satu antrean via HTTP request)

---

### [Component: Controllers]

#### [NEW] [QueueController.php](file:///c:/xampp/htdocs/dapodik-spmb/app/Controllers/QueueController.php)
* Membuat controller untuk menyajikan dashboard monitoring dan API antrean:
  - Mengamankan akses hanya untuk `super_admin` dan `operator_sekolah`.
  - `index()`: Merender view `queue_monitoring`.
  - `fetchDataApi()`: Mengembalikan metrik jumlah (pending, processing, completed, failed) beserta daftar pekerjaan terpaginasi.
  - `dispatchDemoJobApi()`: Menambah pekerjaan simulasi ke database.
  - `retryJobApi()`: Mengembalikan status pekerjaan `failed` menjadi `pending` agar di-run kembali.
  - `deleteJobApi()`: Menghapus pekerjaan dari antrean.
  - `runWorkerApi()`: Memanggil `QueueManager` & `JobDispatcher` secara lokal untuk memproses satu pekerjaan dan memberikan response hasilnya ke UI.

---

### [Component: Views]

#### [NEW] [queue_monitoring.php](file:///c:/xampp/htdocs/dapodik-spmb/views/queue_monitoring.php)
* Dashboard visual premium berbasis CSS Grid dan Tailwind CSS:
  - **Panel Metrik (KPIs)**: Total antrean Pending, Processing, Completed, dan Failed dengan ikon modern dan animasi interaktif.
  - **Quick Action Panel**:
    - Tombol "Jalankan Satu Pekerjaan" (memungkinkan testing pemrosesan langsung dari browser).
    - Tombol "Simulasi Tambah Tugas (Pusdatin)" dan "Simulasi Tambah Tugas (Email Blast)".
  - **Tabel Monitoring Pekerjaan**: Daftar detail nama pekerjaan, tenant pemilik, payload parameter, status (dengan badge Tailwind berwarna), durasi, dan waktu pengerjaan.
  - **Tombol Aksi**: Ulangi Pekerjaan Gagal (Retry) dan Hapus Pekerjaan (Delete) menggunakan integrasi Axios & SweetAlert2.

---

## Verification Plan

### Automated Tests
* Jalankan sintaks PHP linter:
  ```bash
  php -l index.php
  php -l app/Controllers/QueueController.php
  php -l app/Helpers/QueueManager.php
  php -l app/Jobs/JobDispatcher.php
  php -l views/queue_monitoring.php
  php -l worker.php
  ```

### Database Migration
* Jalankan script migrasi database:
  ```bash
  php migrate.php
  ```

### Manual Verification
1. Login sebagai `super_admin` atau `operator_sekolah`.
2. Buka menu **Sistem & Utilitas -> Antrean Sistem & Background Jobs**.
3. Tambahkan beberapa pekerjaan simulasi via panel klik.
4. Klik tombol **"Jalankan Satu Pekerjaan"** dari dashboard.
5. Verifikasi status pekerjaan berubah dari `pending` -> `processing` -> `completed` secara real-time.
6. Cek rekaman audit pada halaman **Log Aktivitas** (audit trail mencatat penambahan dan pengerjaan tugas).
7. Uji jalan di terminal lokal dengan menjalankan perintah:
   ```bash
   php worker.php --once
   ```
