# Dokumentasi Modul 01 - Dashboard

## 1. Pendahuluan
Dashboard adalah halaman beranda utama sistem SINTA-SaaS. Halaman ini bertindak sebagai pusat informasi dan kontrol (*Command Center*). Arsitektur halaman ini sangat dinamis karena apa yang ditampilkan sangat bergantung pada peran pengguna (*Role*) dan institusi asal pengguna (*Tenant*).

## 2. Alur Kerja (Workflow)
1. **Autentikasi & Otorisasi:** Saat *user* mengakses `/dashboard`, sistem pertama kali memanggil `SessionManager::start()` dan memverifikasi eksistensi `$_SESSION['user_id']`. Jika gagal, *user* diarahkan kembali ke `/login`.
2. **Pengecekan Role & Tenant:** 
   - Sistem memisahkan logika menjadi dua cabang besar: **Global (Super Admin)** dan **Sekolah/Tenant (Admin, Guru, Siswa, dll)**.
   - Jika *user* adalah **Non-Super Admin** namun memiliki `tenant_id` yang bernilai `null` (atau status *tenant* sedang dibekukan), sistem akan memaksa *logout* dengan melempar parameter `error=tenant_suspended`.
3. **Kompilasi Data Metrik:**
   - **Super Admin:** Melakukan *query* menghitung total *tenant* (sekolah), total *tenant* aktif, persentase sinkronisasi, dan total siswa di seluruh platform.
   - **Tenant Biasa:** Melakukan *query* menghitung jumlah siswa, status paket langganan, dan status sinkronisasi khusus untuk `tenant_id` milik *user* tersebut.
4. **Log Aktivitas:** Sistem memuat *query* 50 log aktivitas terakhir dari tabel `activity_logs`, secara spesifik melacak aktivitas perubahan tabel `siswa` untuk ditampilkan pada *widget* Riwayat Perubahan.
5. **Agenda & Pengumuman:** Memanggil `PengumumanModel->getActiveForUser($role_id)` dan `AgendaModel->getActiveForUser($role_id)` untuk merender kalender interaktif dan *marquee* (pengumuman berjalan).

## 3. Komponen Backend (Controller & Model)
- **Controller:** `App\Controllers\DashboardController.php`
- **Method Utama:** `index()`
- **Model Terkait:**
  - `App\Models\PengumumanModel` (Mengambil pengumuman aktif berdasar visibilitas dan *tenant*).
  - `App\Models\AgendaModel` (Mengambil jadwal kegiatan sekolah yang sedang atau akan berlangsung).
- **Logika Resolusi Log (Resolve IDs):** `DashboardController` memiliki *helper method* bernama `resolveLogDataIds()` yang bertugas men- *decode* JSON dari *payload* `activity_logs`, lalu mengubah nilai numerik (seperti `id_kelas = 1`) menjadi *string* yang mudah dibaca (seperti `Kelas X-A`). Hal ini membuat riwayat perubahan (*Audit Trail*) mudah dipahami oleh pengguna awam.

## 4. Komponen Frontend (View & UI)
- **View File:** `views/dashboard_view.php`
- **Komponen UI yang di-render:**
  1. **Top Metric Cards:** 4 kartu ringkasan (Total Siswa, Status Aplikasi, dll) dengan *icon Bootstrap*.
  2. **Audit Trail Table:** Tabel "Aktivitas Terakhir" (History Log).
  3. **Data List (Siswa & Guru):** Dua tab berdampingan untuk *preview* data cepat siswa (menampilkan NISN, Nama, L/P) dan daftar guru.
  4. **Widget Pengumuman (Alert Banner):** Pengumuman disajikan dalam bentuk *alert box* (dengan *styling* dinamis menyesuaikan tingkat urgensi) atau *marquee* di *header*.
  5. **Widget Agenda / Kalender:** Menampilkan agenda yang sedang aktif menggunakan kartu kalender visual (tergantung durasi tanggal mulai hingga tanggal selesai).

## 5. Ringkasan Hak Akses (Visibilitas Komponen)
| Komponen | Super Admin | Operator / Admin Sekolah | Guru / Staf | Siswa |
|---|---|---|---|---|
| **Metrik Siswa** | Total Global | Total Per Sekolah | - | - |
| **Audit Trail** | Global Log | Tenant Log | - | - |
| **Daftar Guru** | Global List | Tenant List | - | - |
| **Agenda Sekolah**| Ya | Ya | Ya | Ya |
| **Kelengkapan Profil**| - | - | - | Ya (Siswa Saja) |

> [!NOTE]
> Jika terjadi kejanggalan di *dashboard* (misal: data jumlah siswa = 0 padahal di buku induk ada data), langkah *troubleshooting* pertama adalah memastikan kolom `deleted_at IS NULL` dan nilai `tenant_id` tidak meleset (*corrupted*).
