## Modular Architecture & PostgreSQL Multi-Schema
Saat merombak, membuat file Model, atau membuat Controller baru, agen WAJIB mematuhi standar arsitektur berikut:

**1. Struktur Direktori dan Namespace:**
- Modul berlokasi di `C:\laragon\www\sinta\app\Modules\`.
- Setiap modul (misal: `Sistem`, `Core`, `Akademik`) harus menempati sub-folder `Controllers/` dan `Models/`.
- File Model wajib menggunakan namespace `App\Modules\[NamaModul]\Models`.
- File Controller wajib menggunakan namespace `App\Modules\[NamaModul]\Controllers`.

**2. Pendefinisian Model PostgreSQL:**
- Model harus secara eksplisit mendefinisikan koneksi ke skema PostgreSQL yang tepat (misalnya: `protected $table = 'sistem.nama_tabel';` atau selalu menyertakan prefix skema pada string SQL).
- Sesuaikan konfigurasi *primary key* dan *auto-increment* (terutama jika menggunakan `UUID` via fungsi PostgreSQL `gen_random_uuid()`).
- Terapkan *casts* atau konversi tipe data yang sangat ketat untuk nilai pengembalian, terutama untuk tipe data `Boolean`, `Integer`, atau kolom `JSON` (karena PostgreSQL jauh lebih *strict* (*type-safe*) dibanding MySQL).

**3. Pendefinisian Controller:**
- Controller digunakan untuk menangani operasi CRUD atau logika bisnis terpusat yang memanggil instansiasi Model.
- Pastikan Controller menjaga batasan arsitektur (tidak mencampuradukkan logika manipulasi database kotor secara langsung tanpa melalui Model/PDO parameterized statement).


## Security Guidelines (Anti-XSS & Data Protection)
Saat menulis, memodifikasi, atau membenahi program, agen wajib selalu menerapkan langkah-langkah keamanan data krusial:
- **Pencegahan Kebocoran Kredensial**: Hapus data sensitif (seperti hash password, token, api_key) menggunakan `unset()` di PHP/sisi server sebelum mengirim data tersebut ke client-side JavaScript.
- **Anti-XSS pada Script**: Selalu gunakan bendera `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT` saat menggunakan `json_encode()` di dalam tag `<script>` untuk mencegah *Script Break XSS*.
- **Anti-XSS pada Atribut HTML**: Jika data JSON disuntikkan ke dalam atribut HTML (seperti atribut `onclick="..."` atau `data-*`), wajib dibungkus dengan `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` guna mencegah *Attribute Break XSS*.
- **Pencegahan SQL Injection (SQLi)**: Selalu gunakan Prepared Statements dengan Parameter Binding (menggunakan PDO/bindValue/execute) untuk setiap kueri database yang memproses input dari pengguna. Jangan pernah menggabungkan variabel langsung ke dalam string SQL (seperti `"WHERE id = " . $id`).
- **Validasi & Sanitasi Input Sisi Server**: Setiap input dari request client (GET, POST, COOKIE) wajib divalidasi tipe datanya dan disanitasi menggunakan fungsi seperti `strip_tags()`, `htmlspecialchars()`, `filter_var()`, atau regex sebelum digunakan dalam proses logika bisnis aplikasi.

## Modern Architecture & Zero Data Leakage Development
Saat merancang fitur baru atau memodifikasi modul yang ada, terapkan arsitektur modern dan aman:
- **Migrasi ke AJAX Fetch Dinamis**: Hindari mencetak data mentah dari database langsung menggunakan PHP `json_encode` di dalam blok skrip HTML (`<script>`). Seluruh pemuatan data sensitif (seperti data siswa, guru, profil sekolah, agenda, dsb.) wajib dialihkan menggunakan arsitektur dynamic fetch asinkronus (misal menggunakan Axios/fetch) pada saat komponen ter-mount di sisi klien (`onMounted`/`mounted` di Vue). Hal ini penting untuk memastikan tidak ada data rahasia yang bocor lewat perintah "View Page Source" (Ctrl+U).
- **Pengembangan dengan Ide Baru & Aman**: Setiap pembuatan fitur atau modul baru wajib dirancang menggunakan pola arsitektur modern (API-driven / dynamic rendering) dengan tetap mengutamakan keindahan estetika antarmuka (premium UI/UX) dan keamanan data yang ketat sejak fase awal perencanaan kode.
- **Standardisasi Respon API JSON**: Saat membuat API endpoint baru yang menghasilkan respon JSON, selalu gunakan format terstandardisasi: `['success' => true/false, 'data' => ..., 'error' => ...]` lengkap dengan HTTP status code yang tepat (contoh: 200 OK, 400 Bad Request, 403 Forbidden, 422 Unprocessable Entity untuk error validasi).

# Custom Rules
## Testing and Checking Files
Selalu simpan file percobaan, pengujian (*testing*), atau pengecekan (seperti file dengan awalan `test_`, `check_`, `grant_`, dsb.) HANYA ke dalam folder `C:\laragon\www\sinta\scratch`. Jangan pernah menyimpan file-file sementara ini di *root directory* atau direktori inti aplikasi lainnya.

## Implementation Plans
Setiap kali ada rencana implementasi (*implementation plan*) yang telah diselesaikan atau dijalankan, **SELURUH ISI plan** (bukan hanya ringkasan) wajib **ditambahkan (append)** ke dalam **satu file gabungan per hari** di folder `C:\laragon\www\sinta\scratch\docs` dengan format nama: `YYYY-MM-DD_Implementation_Plans_Harian.md` (contoh: `2026-07-17_Implementation_Plans_Harian.md`).

> ⚠️ **PERINGATAN KRITIS**: Jangan pernah hanya menyimpan rangkuman atau deskripsi singkat. Seluruh konten plan — termasuk semua kode SQL, kode PHP, kode JavaScript, tabel, diagram, dan verification plan — WAJIB disertakan secara lengkap verbatim (apa adanya) di dalam file harian. File harian adalah **satu-satunya sumber kebenaran** untuk semua rencana implementasi; file plan terpisah per fitur tidak boleh menjadi satu-satunya tempat penyimpanan.

**Aturan file implementation plan harian:**
- **Satu file per hari** — jika file `YYYY-MM-DD_Implementation_Plans_Harian.md` sudah ada, tambahkan (*append*) entri baru di bawahnya. Jangan membuat file baru per plan.
- Setiap entri dipisahkan dengan garis pemisah `---` dan diberi subjudul `## [Nama Fitur/Plan]` beserta waktu penulisannya.
- **Format setiap entri (WAJIB LENGKAP):**
  ```
  ---
  ## [Nama Fitur atau Rencana]
  **Waktu**: HH:MM WIB
  **Status**: Draft / Disetujui / Dieksekusi

  [SELURUH ISI IMPLEMENTATION PLAN DITEMPEL DI SINI — VERBATIM]
  Mencakup:
  - Latar belakang & root cause
  - Semua proposed changes lengkap per file (dengan kode sebelum/sesudah)
  - Semua query SQL, kode PHP, kode JavaScript yang direncanakan
  - Matriks hak akses (jika ada)
  - Verification plan (manual + automated)
  ```
- **DILARANG** hanya mengisi bagian `**Deskripsi**` dengan satu baris ringkasan. Jika plan memiliki 200 baris konten, maka semua 200 baris tersebut harus ada di file harian.


## Walkthroughs
Setiap kali pekerjaan diselesaikan, dokumen penjelasan hasil akhir (*walkthrough*) wajib **ditambahkan (append)** ke dalam **satu file gabungan per hari** di folder `C:\laragon\www\sinta\scratch\docs` dengan format nama: `YYYY-MM-DD_Walkthrough_Harian.md` (contoh: `2026-07-17_Walkthrough_Harian.md`).

> ⚠️ **PERINGATAN KRITIS**: Jangan pernah hanya menyimpan rangkuman atau deskripsi singkat. Seluruh konten penjelasan hasil akhir (walkthrough.md) — termasuk semua file yang diubah/dibuat, hasil pengujian static analysis, dan regresi keamanan — WAJIB disertakan secara lengkap verbatim (apa adanya) di dalam file harian. File harian adalah **satu-satunya sumber kebenaran** untuk semua penjelasan hasil akhir; file walkthrough terpisah per fitur tidak boleh menjadi satu-satunya tempat penyimpanan.

**Aturan file walkthrough harian:**
- **Satu file per hari** — jika file `YYYY-MM-DD_Walkthrough_Harian.md` sudah ada, tambahkan (*append*) entri baru di bawahnya. Jangan membuat file baru per tugas.
- Setiap entri dipisahkan dengan garis pemisah `---` dan diberi subjudul `## [Nama Perbaikan/Tugas]` beserta waktu penyelesaiannya.
- **Format setiap entri (WAJIB LENGKAP):**
  ```
  ---
  ## [Nama Perbaikan atau Tugas]
  **Waktu**: HH:MM WIB
  **Jenis**: Bug Fix / Feature / Refactor / dll.

  [SELURUH ISI WALKTHROUGH ARTIFACT DITEMPEL DI SINI — VERBATIM]
  Mencakup:
  - Deskripsi dan root cause (jika bug)
  - Semua file yang diubah / dibuat
  - Seluruh visualisasi / screenshot (jika ada)
  - Hasil pengujian & verifikasi lengkap (PHPStan + security audit)
  ```
- **DILARANG** hanya mengisi dengan satu baris ringkasan. Jika walkthrough memiliki 100 baris konten, maka semua 100 baris tersebut harus ada di file harian.

## Git Commits and Pushing (OPSIONAL)
*(Tidak wajib dieksekusi. Hanya lakukan proses git commit dan push jika ada instruksi eksplisit dari pengguna).*
Ketika pengguna menginstruksikan untuk melakukan push ke repositori GitHub, selalu kelompokkan dan distribusikan perubahan ke dalam commit-commit yang terpisah secara atomik berdasarkan modul, fitur, menu, atau perbaikan bug masing-masing (jangan menggabungkan seluruh perubahan besar ke dalam satu commit tunggal).

## Database Migration Rules (WAJIB)
Setiap kali membuat file migrasi baru di folder `database/migrations/`, wajib menggunakan **format `return [...]` array** — BUKAN format skrip imperatif langsung. Ini adalah persyaratan teknis mutlak dari `migrate.php` agar migrasi dapat dideteksi, dieksekusi, dan dicatat ke tabel `migrations` dengan benar.

**❌ FORMAT SALAH — Jangan pernah dibuat:**
```php
<?php
require_once __DIR__ . '/../../app/Config/Database.php';
use App\Config\Database;
try {
    $pdo = Database::getConnection();
    $pdo->exec("ALTER TABLE ...");
} catch (\Exception $e) {
    exit(1);
}
```
Format di atas akan menyebabkan error `Galat: File migrasi tidak menyediakan fungsi 'up'` setiap kali deploy, dan migrasi tidak akan dicatat ke tabel `migrations` sehingga diulang terus.

**✅ FORMAT BENAR — Selalu gunakan ini:**
```php
<?php
return [
    'up' => function (PDO $pdo): void {
        // Logika DDL/DML di sini. $pdo sudah di-inject dari migrate.php.
        // Jangan tambahkan require_once Database atau session_start di sini.
        $pdo->exec("ALTER TABLE `nama_tabel` ADD COLUMN ...");
        echo "- Kolom berhasil ditambahkan.\n";
    },
    'down' => function (PDO $pdo): void {
        // Logika rollback di sini
        $pdo->exec("ALTER TABLE `nama_tabel` DROP COLUMN ...");
    },
];
```

**Aturan tambahan untuk file migrasi:**
- **Nama file**: Gunakan format `YYYY_MM_DD_NN_deskripsi_singkat.php` (contoh: `2026_07_17_00_add_kolom_baru.php`)
- **Jangan** memanggil `require_once` Database di dalam file migrasi — `$pdo` sudah di-inject otomatis
- **Jangan** memanggil `exit()` di dalam closure `up`/`down` — lempar `Exception` jika ada error fatal
- **Selalu** sertakan fungsi `down` untuk rollback, meskipun isinya hanya komentar
- **Gunakan** `IF NOT EXISTS` / `IF EXISTS` untuk operasi tabel/kolom agar migrasi bersifat *idempotent* (aman dijalankan berulang kali).
- **Gunakan** `CASCADE` pada operasi `DROP TABLE` jika tabel tersebut memiliki *foreign key* yang saling terikat (PostgreSQL tidak mendukung perintah `SET FOREIGN_KEY_CHECKS = 0` seperti di MySQL).

## Static Analysis & Security Audit Verification (OPSIONAL)
*(Tidak wajib dieksekusi di setiap akhir sesi. Hanya jalankan langkah verifikasi ini jika pengguna secara spesifik memintanya).*
Ketika pengguna meminta verifikasi, agen wajib secara otomatis menjalankan 2 langkah berikut:
1. **PHPStan Static Analysis**: Jalankan analisis statis PHPStan pada berkas yang diubah atau direktori aplikasi:
   ```powershell
   vendor/bin/phpstan analyse <path-file-atau-folder> --level=5
   ```
   Pastikan tidak ada error atau warning yang tersisa (`[OK] No errors`).
2. **Automated Security Audit Script**: Jalankan skrip uji regresi keamanan otomatis suite:
   ```powershell
   php scratch/tests/test_security_audit.php
   ```
   Pastikan hasilnya menunjukkan `Failed Checks: 0`.

