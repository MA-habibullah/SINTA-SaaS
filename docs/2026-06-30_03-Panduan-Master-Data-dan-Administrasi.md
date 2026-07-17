# 03 Panduan Master Data dan Administrasi

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


---

# Dokumentasi Modul 03 - Master Data & Kelembagaan

## 1. Pendahuluan
Modul **Master Data & Kelembagaan** (`/master-data`) berfungsi sebagai fondasi dari seluruh operasional sistem. Data statis yang dikelola pada menu ini (seperti Tahun Ajaran dan Identitas Sekolah) menjadi referensi (Foreign Key / Relasi) yang mengikat data-data dinamis di modul lain (Rapor, Ekskul, BK, dll).

## 2. Alur Kerja (Workflow) Utama
Modul ini dipecah menjadi beberapa fungsi manajemen referensi:
1. **Identitas Sekolah:** Menyimpan profil sekolah yang berjalan di *tenant* bersangkutan. Jika diedit, data ini digunakan saat mencetak *header* PDF Rapor Siswa.
2. **Tahun Ajaran & Semester:** Entitas vital. Ketika salah satu tahun ajaran di-*set* sebagai **Aktif**, *flag* `is_active` pada *database* di-*toggle* menjadi 1 dan yang lama menjadi 0. Perubahan ini mengubah filter *default* di semua halaman untuk seluruh *user*.
3. **Data Jenjang & Kelas:** Pengelompokkan rombongan belajar. Kelas dihubungkan (*Joined*) dengan tabel `users` (sebagai Wali Kelas) berdasarkan `guru_id`.
4. **Master Mata Pelajaran:** Pengelompokkan mapel berdasarkan Muatan Nasional (A), Muatan Kewilayahan (B), dan Peminatan (C) mengikuti standar kurikulum nasional.

## 3. Komponen Backend
### Controller Terkait
- `App\Controllers\KelembagaanController.php`
- `App\Controllers\SekolahController.php` (Menangani *update* entitas *tenant/identitas*).
- **Rute Inti:**
  - `GET /api/v1/kelembagaan` (Fetch paginasi berdasar *tab*).
  - `POST /api/v1/kelembagaan/simpan` (Menangani *Create/Update*).
  - `POST /api/v1/kelembagaan/toggle-status` (Fungsi unik untuk *Toggle* Tahun Ajaran aktif).

### Struktur Database & Logika Relasional
- Tabel: `tahun_ajaran`, `kelas`, `mapel`, `tenants`.
- **Constraint tenant_id:** Setiap kueri pada model `KelembagaanModel` disaring menggunakan `WHERE tenant_id = ?`.
- **Logika Toggle (Tahun Ajaran Aktif):** 
  Di tingkat *database*, tidak boleh ada 2 Tahun Ajaran yang berstatus `is_active = 1` dalam 1 *tenant*. Saat `toggle-status` dipanggil, *backend* menjalankan:
  1. `BEGIN TRANSACTION`
  2. `UPDATE tahun_ajaran SET is_active = 0 WHERE tenant_id = ?` (Reset semua)
  3. `UPDATE tahun_ajaran SET is_active = 1 WHERE id = ?` (Set yang dipilih)
  4. `COMMIT`

## 4. Komponen Frontend
- **View File:** `views/kelembagaan_index.php` dan `views/sekolah/identitas.php`.
- **UI Components:** Menggunakan tata letak menu samping (*side-pills*) atau *Top Tabs* untuk navigasi antar pengaturan.
- Terdapat fitur **Pencarian Real-Time (Live Search)** menggunakan JS `debounce`. Setiap huruf yang diketik *user* tidak langsung menembak server, melainkan menunggu *delay* (300ms) untuk menghemat *resource server*.
- **Dropdown Relasional:** Saat menambah Kelas baru, kolom "Wali Kelas" akan menarik daftar secara asinkron dari API `/pengguna` khusus untuk *role* Guru.

## 5. Sinkronisasi Data Lintas Tabel
Setiap kali entitas dihapus (contoh: menghapus Kelas X-A), metode penghapusan adalah **Soft Delete**. Hal ini sangat krusial karena apabila kita menghapus data kelas secara *Hard Delete*, seluruh tabel `siswa` yang berelasi ke kelas tersebut akan kehilangan integrasi datanya, dan rapor tidak bisa dicetak. Karena itulah SINTA-SaaS menggunakan arsitektur `deleted_at IS NULL`.


---

# Dokumentasi Modul 06 - Informasi & Agenda

## 1. Pendahuluan
Modul **Informasi & Agenda** (sering disebut modul Humas) bertanggung jawab atas penyebaran informasi satu arah (Pengumuman) dan pemetaan jadwal lintas periode (*Timeline* Sekolah). Modul ini sangat vital dalam menggantikan fungsi "Mading" (Majalah Dinding) secara digital di era *paperless*.

## 2. Alur Kerja (Workflow)
1. **Pembuatan Pengumuman / Agenda:** 
   - Humas atau Super Admin masuk ke menu **Manajemen Pengumuman**.
   - Mereka mengisi Form yang memuat Judul, Teks (melalui *Rich Text Editor*), dan mengatur **Visibilitas**.
2. **Targeting (Visibilitas):** 
   Aplikasi menyediakan 4 lapisan visibilitas (akses kontrol):
   - **Publik:** Tampil ke seluruh _role_ (Siswa, Karyawan, Guru, Admin).
   - **Guru:** Hanya muncul di _dashboard_ pengguna yang masuk dalam rumpun *role* Pendidik.
   - **Siswa:** Pengumuman eksklusif bagi *role* Siswa.
   - **Spesifik Role:** Memilih *role* dari basis data secara presisi (misalnya hanya diumumkan kepada "Waka Kurikulum" atau "Guru BK"). Pilihan `super_admin` dihilangkan agar pengumuman tidak tereskalasi keliru.
3. **Penyajian Data (Rendering):** 
   - Di sisi pengguna akhir (beranda/dashboard), kueri model secara pintar membaca `role_id` milik *user* dan mencari irisan (intersection) dengan array `target_roles` milik pengumuman/agenda.
   - Jika beririsan dan berstatus `is_active = 1`, data akan ter- *render*.

## 3. Komponen Backend
### Controllers
- `App\Controllers\PengumumanController.php`
- `App\Controllers\AgendaController.php`
Sangat mirip dalam struktur CRUD dasar. Perbedaannya terletak di penanganan *file upload*.
- Pada `PengumumanController->store()`, *backend* mencegat `$_FILES['lampiran']`. Ia membuat direktori unik (jika belum ada) di `storage/app/public/uploads/` lalu menggeser *file* asli, serta memproteksi rentan serangan XSS/Webshell melalui ekstensi yang diperbolehkan (`pdf`, `jpg`, `png`). 

### Models & Resolusi Tenant
- `App\Models\PengumumanModel.php`
- `App\Models\AgendaModel.php`
- Logika **Isolasi Tenant**: Pengumuman yang dibuat oleh Admin Sekolah (Operator) akan mengunci *field* `tenant_id` ke sekolah mereka. Namun, jika Super Admin membuat pengumuman, *field* `tenant_id` menjadi `NULL`. Konsekuensinya: Kueri `getActiveForUser()` di tingkat pengguna membaca logika `WHERE tenant_id = ? OR tenant_id IS NULL`, memungkinkan pengumuman *Global* dari platform terbaca oleh setiap institusi.

## 4. Komponen Frontend (View & UI)
- **Editor Teks Kaya (Rich Text):** Mengintegrasikan `Quill.js` *Snow Theme*. Input yang dikirim ke *backend* sudah berupa format HTML murni. 
- **Tabel Manajemen (DataTables / Standard Table):** Menampilkan daftar informasi yang berumur panjang dengan opsi Edit dan Delete. Opsi *Toggle Switch* digunakan untuk menonaktifkan Pengumuman secara instan tanpa menghapus rekaman di *database*.
- **Modal Add/Edit:** Mencegah perpindahan halaman, UI *form* dilakukan secara _pop-up_ menggunakan modal Bootstrap 5.


---

# Dokumentasi Modul 15 - Fitur Utilitas Massal (Bulk Actions)

## 1. Pendahuluan
Selain operasi tunggal (satu-per-satu), SINTA-SaaS dilengkapi dengan modul khusus penanganan data berskala besar (*Bulk Actions*). Modul ini sangat berguna di awal tahun ajaran baru ketika sekolah harus memasukkan ribuan data siswa dan foto sekaligus.

## 2. Modul Import Data Excel (`ImportController.php`)
Modul ini bertindak sebagai mesin penerjemah (*Parser*) global yang mengubah lembar kerja Microsoft Excel (`.xlsx`) menjadi baris *database*.

### A. Alur Kerja Impor
1. **Unduh Template:** Pengguna (Admin) menekan tombol "Download Template". *Backend* menghasilkan *file* Excel kosong dengan *header* yang dikunci agar strukturnya tidak diubah-ubah oleh pengguna.
2. **Unggah File:** Pengguna mengunggah *file* yang sudah diisi. File diproses secara asinkron (atau dikirim ke *Background Jobs/Queue* jika datanya > 5000 baris).
3. **Validasi & Transaksi:** 
   - *Backend* membaca baris demi baris menggunakan pustaka `PhpSpreadsheet`.
   - Melakukan validasi relasional. Misalnya: Jika mengimpor Nilai Rapor, *backend* mengecek apakah NISN tersebut benar-benar ada di `tenant_id` bersangkutan.
   - Menggunakan mekanisme *Rollback* PDO jika satu sel saja bernilai *Error* (*Atomic Transaction*).

## 3. Modul Bulk Photo Upload (`BulkPhotoController.php`)
Modul ini merupakan *controller* terpisah untuk menangani unggahan foto profil siswa secara massal menggunakan format `.zip`.

### A. Alur Kerja (Workflow)
1. **Persiapan di Komputer Pengguna:**
   Operator sekolah menyiapkan ratusan foto. Foto-foto tersebut wajib diberi nama sesuai dengan **NISN** siswa (misalnya: `0051234567.jpg`).
2. **Zipping & Upload:** 
   Semua foto tersebut disatukan dalam 1 buah arsip `.zip`. Operator mengunggah file `.zip` tersebut ke menu *Bulk Photo*.
3. **Eksekusi Backend:**
   - *Backend* menerima file `.zip` dan memindahkannya ke *temporary directory* (`/storage/app/tmp`).
   - PHP mengekstrak `.zip` menggunakan modul `ZipArchive`.
   - Terjadi proses perulangan (*loop*): Untuk setiap *file* `.jpg/.png` yang diekstrak, *backend* menghapus ekstensi file, menganggap sisanya sebagai NISN, lalu mencari ID siswa berdasarkan NISN tersebut di tabel `siswa`.
   - Jika cocok, foto dilewatkan ke fungsi kompresi (mengurangi resolusi agar tidak lebih dari 200KB).
   - *File* hasil kompresi dipindahkan ke `/public/uploads/profiles/`, dan nama *file*-nya ditulis ke kolom `foto` di tabel `siswa`.
4. **Laporan (Feedback):**
   *Backend* mengembalikan JSON yang berisi laporan: "Berhasil: 250 foto. Gagal: 3 foto (NISN tidak ditemukan di database)".

## 4. Analisis Keamanan Ekstra
Kedua modul *Bulk Action* ini memiliki pengawasan keamanan tinggi:
- Limit ukuran *file upload* (`upload_max_filesize`) disesuaikan.
- Pencegahan Serangan *Zip Bomb*: Backend membatasi jumlah ekstrak maksimum dari dalam file `.zip` untuk mencegah *server crash*.
- Validasi *MIME type*: File disahkan berdasarkan MIME, bukan sekadar ekstensi (untuk menghindari *Webshell Upload* berkedok gambar).


---

