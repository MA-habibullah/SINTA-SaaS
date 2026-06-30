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
