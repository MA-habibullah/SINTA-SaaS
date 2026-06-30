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
