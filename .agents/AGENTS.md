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
Selalu simpan file percobaan, pengujian (*testing*), atau pengecekan (seperti file dengan awalan `test_`, `check_`, `grant_`, dsb.) HANYA ke dalam folder `C:\xampp\htdocs\SINTA-SaaS\scratch`. Jangan pernah menyimpan file-file sementara ini di *root directory* atau direktori inti aplikasi lainnya.

## Implementation Plans
Setiap kali ada rencana implementasi (*implementation plan*) yang telah diselesaikan atau dijalankan, ringkasan plan tersebut wajib **ditambahkan (append)** ke dalam **satu file gabungan per hari** di folder `C:\xampp\htdocs\SINTA-SaaS\docs` dengan format nama: `YYYY-MM-DD_Implementation_Plans_Harian.md` (contoh: `2026-07-17_Implementation_Plans_Harian.md`).

**Aturan file implementation plan harian:**
- **Satu file per hari** — jika file `YYYY-MM-DD_Implementation_Plans_Harian.md` sudah ada, tambahkan (*append*) entri baru di bawahnya. Jangan membuat file baru per plan.
- Setiap entri dipisahkan dengan garis pemisah `---` dan diberi subjudul `## [Nama Fitur/Plan]` beserta waktu penulisannya.
- Format setiap entri:
  ```
  ---
  ## [Nama Fitur atau Rencana]
  **Waktu**: HH:MM WIB
  **Status**: Draft / Disetujui / Dieksekusi
  **Deskripsi**: [Ringkasan singkat apa yang direncanakan, file yang akan diubah, dan pendekatan teknis utama]
  ```

## Walkthroughs
Setiap kali pekerjaan diselesaikan, dokumen penjelasan hasil akhir (*walkthrough*) wajib **ditambahkan (append)** ke dalam **satu file gabungan per hari** di folder `C:\xampp\htdocs\SINTA-SaaS\docs` dengan format nama: `YYYY-MM-DD_Walkthrough_Harian.md` (contoh: `2026-07-17_Walkthrough_Harian.md`).

**Aturan file walkthrough harian:**
- **Satu file per hari** — jika file `YYYY-MM-DD_Walkthrough_Harian.md` sudah ada, tambahkan (*append*) entri baru di bawahnya. Jangan membuat file baru per tugas.
- Setiap entri dipisahkan dengan garis pemisah `---` dan diberi subjudul `## [Nama Perbaikan/Tugas]` beserta waktu penyelesaiannya.
- Format setiap entri:
  ```
  ---
  ## [Nama Perbaikan atau Tugas]
  **Waktu**: HH:MM WIB
  **Jenis**: Bug Fix / Feature / Refactor / dll.
  [Deskripsi singkat apa yang dikerjakan, root cause (jika bug), dan file yang diubah]
  ```

## Git Commits and Pushing
Ketika melakukan push ke repositori GitHub, selalu kelompokkan dan distribusikan perubahan ke dalam commit-commit yang terpisah secara atomik berdasarkan modul, fitur, menu, atau perbaikan bug masing-masing (jangan menggabungkan seluruh perubahan besar ke dalam satu commit tunggal).

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
- **Gunakan** `IF NOT EXISTS` / `IF EXISTS` / `SHOW COLUMNS` untuk membuat migrasi idempotent (aman dijalankan berulang)
- **Gunakan** `SET FOREIGN_KEY_CHECKS = 0` di awal dan `= 1` di akhir jika ada operasi DROP/CREATE tabel dengan foreign key




