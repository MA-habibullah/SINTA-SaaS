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
