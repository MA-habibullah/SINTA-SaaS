# Dokumentasi Modul 10 - Konfigurasi & Utilitas

## 1. Pendahuluan
Modul **Konfigurasi & Utilitas** ditujukan semata-mata untuk Staf IT / Administrator teknis sekolah (*Operator Sekolah* tertinggi). Terdapat peralatan tingkat mahir (*Advanced Tools*) untuk mengatur keamanan, audit, dan jalannya proses di *background*.

## 2. Fitur Inti & Alur Kerja

### A. Hak Akses (Role Based Access Control - RBAC)
- **Logika Sistem:** Setiap entri pada menu *sidebar* sebelah kiri diikat ke dalam suatu `route/URL` unik (contoh: `/kesiswaan/ekskul`).
- **Alur Penyimpanan:** Tabel `menu_permissions` mencatat *pivot* antara `menu_id` dengan `role_id` dan `tenant_id`.
- **Implementasi (Middleware Level):** Saat *user* meminta navigasi (contoh: `GET /kesiswaan`), *BaseController* (via metode *GuardRole*) akan membaca *session* `roles` pengguna. Ia mencegat kueri tabel `menu_permissions`. Jika peran *user* tidak terdaftar, maka eksekusi dihentikan dengan pesan *403 Forbidden* atau pengalihan rute.

### B. Sesi Aktif (*Session Manager*)
- Fitur ini membaca tabel sesi (jika memakai *DB Sessions*) atau berkas penyimpanan *server* (Redis/File) untuk menginspeksi perangkat apa saja yang melakukan koneksi dengan status terotentikasi.
- **Logika *Force Logout*:** *Backend* akan menyuntikkan *flag* basi (*expired*) ke sesi ID terkait. Saat peramban korban (yang dibajak/di-*revoke*) menembakkan API berikutnya, *middleware Auth* menendangnya kembali ke halaman `/login`.

### C. Log Aktivitas (*Audit Trail*)
- Bertindak sebagai pengintai (CCTV) dalam sistem.
- **Logika *Trigger*:** *ActivityLogger* adalah pustaka utilitas yang selalu disisipkan di dalam fungsi CRUD (contoh: pasca eksekusi `PengumumanModel->insert()`). Pustaka ini mem- *parsing* objek JSON tentang status objek sebelum (*old_data*) dan objek sesudah (*new_data*) diedit, mencatat IP *Address* (`$_SERVER['REMOTE_ADDR']`), dan *User Agent* (`$_SERVER['HTTP_USER_AGENT']`).
- **Antarmuka (UI):** Menampilkan tabel historis yang bisa dicari. Ini sangat mempermudah pelacakan (contoh: "Siapa yang menghapus nilai siswa ini jam 3 pagi?").

### D. Antrean (*Jobs Queue*)
- Arsitektur pengolahan asinkron (mirip konsep *Redis Queue* atau *RabbitMQ* sederhana berbasis Database).
- Tugas-tugas berat (seperti mem- *parsing* 3.000 entri nilai dari format Excel) akan sangat berbahaya bagi memori server jika ditangani secara sinkron (*HTTP blocking request*).
- **Proses:** Sistem menaruh tugas tersebut di tabel `jobs_queue` dengan status `pending`. *Client* segera mendapat respon HTTP 202 ("Tugas Diterima"). Sebuah proses *cron-job* atau *background worker* kemudian mengambil tugas tersebut, mengeksekusinya, dan memperbarui status menjadi `completed` atau `failed`. Menu UI ini digunakan Admin untuk memonitor progresnya.
