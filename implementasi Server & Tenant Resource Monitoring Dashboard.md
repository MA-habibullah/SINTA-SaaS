# Server & Tenant Resource Monitoring Dashboard

Fitur ini akan memberikan Super Admin visibilitas real-time terhadap kesehatan server (CPU, RAM, Disk) serta penggunaan resource oleh masing-masing sekolah (tenants) seperti alokasi penyimpanan berkas fisik, sesi aktif pengguna, dan total entri data.

## User Review Required

> [!IMPORTANT]
> **Metrik Sistem Operasi (OS)**
> Fungsi PHP native untuk membaca Load Average, RAM, dan Uptime berbeda antara Linux (Production) dan Windows (Local Development/XAMPP). Saya akan mengutamakan perintah Linux (misal: `sys_getloadavg()`, `shell_exec('free -m')`, `shell_exec('df -m')`) sesuai permintaan Anda, namun akan menyertakan fallback aman untuk Windows agar script tidak *crash* saat Anda menjalankannya di localhost (XAMPP).
> 
> Apakah Anda setuju dengan pendekatan "Linux-first with Windows fallback" ini?

## Proposed Changes

### 1. Routing & Menu (index.php & Migrations)
- Mendaftarkan menu baru "Server Monitor" di sidebar khusus untuk Super Admin (di bawah grup "Sistem & Utilitas").
- Menambahkan route ke `index.php`:
  - `GET /super-admin/server-monitor` (Halaman UI)
  - `GET /api/v1/super-admin/server-monitor/fetch` (Endpoint JSON)

### 2. Controller (Backend Logic)
#### [NEW] `app/Controllers/ServerMonitorController.php`
- **RBAC Guard**: Hanya dapat diakses oleh sesi dengan `role_name === 'super_admin'`.
- **Global Metrics**:
  - CPU: Menggunakan `sys_getloadavg()` (jika tersedia) atau fallback ke nilai dummy di Windows.
  - RAM & Disk: Menggunakan `disk_total_space()` / `disk_free_space()` serta ekstraksi perintah shell Linux.
  - Uptime: Menggunakan pembacaan `/proc/uptime` atau `shell_exec('uptime -p')`.
- **Tenant Aggregation**:
  - Melakukan query gabungan untuk mendapatkan daftar sekolah.
  - Mengkalkulasi ukuran folder secara fisik di `storage/app/public/uploads/{tenant_id}/` ke dalam satuan MB.
  - Menghitung jumlah user yang sedang online saat ini per tenant dengan men-query tabel `active_sessions`.
  - Menghitung total entri data (user aktif) per sekolah.

### 3. Frontend & Lifecycle (Vue 3 + Tailwind CSS)
#### [NEW] `views/server_monitor.php`
- **Desain UI/UX (Enterprise Grade)**:
  - Bagian atas: Widget minimalis untuk Metrik Global (CPU, RAM, Storage) dengan warna Progress Bar yang reaktif:
    - Hijau (< 60%)
    - Kuning (60% - 80%)
    - Merah (> 80%)
  - Bagian bawah: Data Table untuk "Daftar Penggunaan Resource Sekolah" dengan kolom: Nama Sekolah, Sesi Aktif, Penyimpanan Berkas, Total Pengguna.
- **Vue 3 Polling & Cleanup**:
  - Implementasi AJAX Polling dengan `setInterval()` memanggil API setiap 5 detik.
  - Menerapkan fungsi pencarian (search) dan pengurutan (sort) pada tabel di sisi *client* menggunakan *computed properties*.
  - **SANGAT PENTING**: Membersihkan interval pada `unmounted()` hook agar tidak terjadi *memory leak* dan penumpukan request saat berpindah tab via Turbo Drive.

## Verification Plan
1. Menguji fungsi `fetchApi()` memastikan JSON payload terekstrak dengan metrik global & tenant yang valid tanpa error, baik di OS Windows (fallback) maupun Linux.
2. Membuka halaman Server Monitor dan mengamati animasi transisi progress bar serta tabel tenant.
3. Berpindah halaman via Turbo Drive dan memverifikasi di Network Tab bahwa request AJAX Polling setiap 5 detik telah berhenti secara otomatis (memastikan tidak ada *memory leak*).
