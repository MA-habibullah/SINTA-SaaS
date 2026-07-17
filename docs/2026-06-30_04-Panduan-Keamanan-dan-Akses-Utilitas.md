# 04 Panduan Keamanan dan Akses Utilitas

# Dokumentasi Modul 12 - Autentikasi & Keamanan (Login)

## 1. Pendahuluan
Modul **Autentikasi** adalah gerbang utama dari aplikasi SINTA-SaaS. Modul ini bertanggung jawab memvalidasi identitas pengguna, mencegah serangan *brute-force*, dan mengalokasikan hak akses (Sesi) ke *tenant* (sekolah) yang tepat.

## 2. Alur Kerja (Workflow)
1. **Akses Halaman Login:** Pengguna mengakses rute `/login`. Jika mereka sudah memiliki sesi aktif, *middleware* secara otomatis akan me- *redirect* mereka ke `/dashboard` tanpa perlu *login* ulang.
2. **Submit Kredensial:** Pengguna memasukkan *Email/Username* dan *Password*. Form menembak *endpoint* `POST /api/v1/auth/login`.
3. **Verifikasi Backend:**
   - Cek keberadaan *username* di tabel `users`.
   - Menggunakan algoritma *hashing* (seperti `Bcrypt` atau `Argon2` via `password_verify()`) untuk memvalidasi *password*. SINTA-SaaS tidak pernah menyimpan kata sandi dalam bentuk *plain text*.
   - Mengecek *field* `deleted_at` dan `status`. Jika akun dinonaktifkan atau dihapus (*soft-delete*), *login* ditolak.
   - Mengecek status `tenant_id`. Jika *tenant* / sekolah tempat *user* bernaung sedang diblokir oleh *Super Admin*, *login* ditolak dengan pesan "Akses Sekolah Dibekukan".
4. **Pembuatan Sesi:**
   - Jika berhasil, server meregenerasi ID Sesi (`session_regenerate_id(true)`) untuk mencegah serangan *Session Fixation*.
   - Variabel `$_SESSION` diisi dengan `user_id`, `role_name`, `tenant_id`, dan *array* `roles` (untuk mekanisme *Multiple Roles*).
5. **Logout:**
   - Menghancurkan `$_SESSION` secara utuh menggunakan `session_destroy()` dan menghapus *cookie* sesi di sisi peramban.

## 3. Komponen Backend
- **Controller:** `App\Controllers\AuthController.php`
- **Core Helper:** `App\Core\SessionManager.php`
  - Kelas `SessionManager` ini disisipkan di konstruktor hampir semua *Controller* lain (melalui `SessionManager::requireLogin()`).
  - Menangani proteksi IDOR dan CSRF secara *global*.

## 4. Komponen Frontend
- **View File:** `views/auth/login.php`
- **Keamanan UI:**
  - *Password Toggle* (Ikon mata untuk melihat/menyembunyikan kata sandi).
  - Penanganan pesan *Error* yang elegan tanpa me- *reload* halaman menggunakan *SweetAlert* atau blok peringatan *Bootstrap*.


---

# Dokumentasi Modul 02 - Manajemen Pengguna

## 1. Pendahuluan
Modul **Manajemen Pengguna** (`/pengguna`) berfungsi sebagai sistem sentral pengelolaan data aktor (Siswa, Guru, Karyawan, Operator) di dalam SINTA-SaaS. Modul ini diakses utamanya oleh Operator Sekolah dan Super Admin, namun `role_name = siswa` juga dapat mengakses sebagian kecil dari modul ini (hanya sebatas mode *read-only* atau pembaruan *Profil Data Diri*).

## 2. Alur Kerja (Workflow) Utama
1. **Navigasi Halaman:** Ketika user membuka rute `/pengguna`, `PenggunaController::index()` akan memuat *View* utama. Judul halaman otomatis berubah menjadi "Profil Data Diri" jika diakses oleh siswa, dan "Manajemen Pengguna" jika diakses oleh admin.
2. **Fetch API:** Halaman dirender menggunakan Ajax/Fetch secara asinkron (*Single Page Application style* pada data tabel).
   - *Client-side* memanggil rute `/api/v1/pengguna?tab=siswa&page=1...`
   - *Backend* (`PenggunaController::fetchApi()`) melakukan filter terhadap URL parameter (`tab`, `search`, `page`, `trash`, `id_kelas`).
   - Apabila sesi saat ini adalah **Siswa**, *backend* memaksakan injeksi filter `siswa_id = $_SESSION['user_id']` untuk mencegah eksploitasi data siswa lain (IDOR *protection*).
3. **Penyimpanan/Pembaruan (Store):**
   - Rute `/api/v1/pengguna/simpan` akan menerima form *multipart/form-data*.
   - *Backend* mengekstrak data JSON dari properti `payload` dan mengecek apakah terdapat *file upload* untuk pas foto (`foto`).
   - Eksekusi model dilakukan dengan *Transaction* PDO (`beginTransaction()` dan `commit()`).

## 3. Komponen Backend
### Controller: `App\Controllers\PenggunaController.php`
- `index()`: Menyajikan *View*. Mengambil profil NPSN dan Nama user dari `SessionManager`.
- `fetchApi()`: Menangkap filter API dan merespon dalam bentuk JSON (termasuk pagination).
- `storeApi()`: Menyimpan entitas pengguna (Baik Guru, Siswa, maupun Karyawan). Tergantung nilai *tab*, sistem akan melakukan validasi unik (NISN untuk siswa, Email untuk non-siswa). Jika ada duplikasi, *rollback* dilakukan.
- `bulkActionKelas()`, `bulkActionLuluskan()`: Fungsi utilitas massal (mengubah relasi tabel kelas untuk banyak ID secara bersamaan).

### Model: `App\Models\Pengguna.php`
- Berperan menjembatani 2 tabel utama: `users` dan `siswa`.
- Khusus data siswa, relasi tabel sangat kompleks. Model `Pengguna.php` menghitung kolom `persentase_kelengkapan` secara *on-the-fly* atau menyimpan nilai statis dengan mengecek 40+ field (mulai dari `nisn`, `tinggi_badan`, `nik_ayah`, hingga `no_kps`).
- Mendukung mode **Soft Delete** (`deleted_at`).
- Mendukung konsep **Multiple Roles** dengan menyimpan *array* tambahan seperti `waka_kurikulum` dan `guru_bk`. Model akan menyelaraskan relasi tabel `roles` dan men- *update* session jika *user* yang bersangkutan merubah data dirinya sendiri.

## 4. Komponen Frontend
- **View File:** `views/pengguna_index.php`
- **Javascript Logic:** Semua interaksi dikontrol *vanilla JS* dengan pola desain *tab-navigation* (Terdapat Tab Guru, Karyawan, Siswa, Pendaftar, Mutasi).
- **Multiple Roles Checkbox:** Terdapat UI Form yang memberikan akses multi-peran secara visual:
  - `[] Juga bertindak sebagai Guru BK` (Bisa akses `/bk`)
  - `[] Juga bertindak sebagai Waka Kurikulum`
  - `[] Juga bertindak sebagai Pembina Ekskul`
- **Fitur Ekspor/Impor:** Memanggil fungsi PHPSpreadsheet di backend, namun *frontend* merender progres *loading spinner* ketika tombol di-klik.

## 5. Logika Keamanan (Security Constraints)
- **Tenant Scope Isolation:** Model *Pengguna* diinisialisasi dengan konstruktor `new Pengguna($tenantId)`. Seluruh kueri `SELECT`, `UPDATE`, `DELETE` secara otomatis menyematkan klausa `AND tenant_id = ?`. Kebocoran data lintas sekolah pada satu *server* adalah hal yang mustahil secara arsitektur.
- **Role Guard:** Endpoint `getTenantsApi()` hanya dapat di *hit* jika token milik *Super Admin*. Jika pengguna biasa memaksa menembak *endpoint* ini, *backend* mengembalikan HTTP 403 Forbidden.


---

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


---

# Dokumentasi Modul 11 - Super Admin

## 1. Pendahuluan
Modul **Super Admin Global** berada di ruang lingkup ( *scope* ) tertinggi dalam aplikasi berarsitektur *Multi-Tenant* SINTA-SaaS. Halaman ini adalah *God Mode* dari sistem. Hanya *user* yang secara keras ( *hard-coded* di basis data) memiliki `role_id = 1` (`super_admin`) yang bisa mengakses *controller* rute ini.

## 2. Alur Kerja & Arsitektur Utama

### A. Isolasi Kueri (*Tenant Bypass*)
Sistem SINTA-SaaS secara standar menyisipkan parameter pencegah kebocoran data di dalam *Query Builder* (Setiap *query* otomatis ditambahkan ekstensi `WHERE tenant_id = $_SESSION['tenant_id']`). 
- Namun, saat Modul Super Admin ini terbuka, lapisan perlindungan ini di-*bypass* agar dapat mencetak atau melakukan iterasi terhadap SELURUH sekolah (`tenants`) dari Sabang sampai Merauke yang terdaftar.

### B. Manajemen Tenants
1. **Penambahan Sekolah Baru:** Super Admin mendata profil singkat, menghasilkan *subdomain* khusus (jika diperlukan), dan mendaftarkan 1 akun pertama bertipe *Operator Sekolah* untuk institusi baru tersebut.
2. **Impersonasi (*Login As*):** Fitur krusial bagi Super Admin (biasanya *Tim Support* atau *Developer*). Tanpa menggunakan alat peretas *password*, sistem merubah *pointer* Sesi PHP seolah-olah Super Admin adalah Kepala Sekolah atau Admin dari *Tenant* yang dituju, untuk menguji (*troubleshoot*) laporan keluhan dari klien (sekolah). Saat selesai, mereka bisa memulihkan sesi kembali ke *God Mode*.
3. **Pembekuan / Terminasi Tenant:** Merubah _flag_ tabel `tenants` ke status *Suspended/Inactive*. Akibatnya, siapapun dari institusi tersebut yang berusaha _login_ akan ditolak oleh `SessionManager`.

### C. Server & Error Monitor
- Ini adalah implementasi modul diagnostik bawaan.
- **Server Monitor:** Di sisi *Backend* menjalankan kueri sistem operasi bawaan seperti `shell_exec('free -m')` (RAM) atau `shell_exec('df -h /')` (Disk Space) dan menerjemahkannya kembali menjadi visual *Gauge Chart* (mirip speedometer mobil) di antarmuka Web. Membantu developer menyadari potensi krisis tanpa perlu melakukan SSH terminal ke VPS.
- **Error Logs Viewer:** Sistem menembak pembaca file (*PHP filesystem reader*) langsung ke dalam fail `app/Logs/error.log` milik Apache/Nginx atau sistem lokal aplikasi. 

## 3. Aspek Keamanan Berlapis (Defense in Depth)
- Karena modul ini sangat riskan, perlindungannya dilakukan bukan hanya di sisi peramban (UI yang disembunyikan bagi guru/siswa).
- **Backend Guard:** Rute `/api/v1/super-admin/*` mutlak memeriksa apakah sesi yang mengirimkan muatan (*payload*) memiliki token `super_admin`. Bila terjadi upaya serangan *Bypass* dari pihak yang menyalahgunakan *token JWT / Session* palsu, mereka akan dihalau dan log insiden ini tercatat keras pada tabel sekuriti khusus.


---

