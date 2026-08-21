## Modular Architecture & PostgreSQL Multi-Schema
Saat merombak, membuat file Model, atau membuat Controller baru, agen WAJIB mematuhi standar arsitektur berikut:

**1. Struktur Direktori dan Namespace:**
- Modul berlokasi di `C:\laragon\www\sinta\app\Modules\`.
- Setiap modul (misal: `Sistem`, `Core`, `Akademik`, `Kesiswaan`, `Siswa`, `Bk`, `Keuangan`, `Perpustakaan`, `Persuratan`, `Sarpras`, `Kepegawaian`, `Alumni`, `Pdss`, `Tracer`, `Cms`, `Smk`) harus menempati sub-folder `Controllers/` dan `Models/`.
- File Model wajib menggunakan namespace `App\Modules\[NamaModul]\Models`.
- File Controller wajib menggunakan namespace `App\Modules\[NamaModul]\Controllers`.

**2. Pendefinisian Model PostgreSQL:**
- Model harus secara eksplisit mendefinisikan koneksi ke skema PostgreSQL yang tepat (misalnya: `protected $table = 'sistem.nama_tabel';` atau selalu menyertakan prefix skema pada string SQL).
- Sesuaikan konfigurasi *primary key* dan *auto-increment* (terutama jika menggunakan `UUID` via fungsi PostgreSQL `gen_random_uuid()`).
- Terapkan *casts* atau konversi tipe data yang sangat ketat untuk nilai pengembalian, terutama untuk tipe data `Boolean`, `Integer`, atau kolom `JSON` (karena PostgreSQL jauh lebih *strict* (*type-safe*) dibanding MySQL).

**3. Pendefinisian Controller:**
- Controller digunakan untuk menangani operasi CRUD atau logika bisnis terpusat yang memanggil instansiasi Model.
- Pastikan Controller menjaga batasan arsitektur (menggunakan `BaseController` & `jsonResponse()` tanpa mencampuradukkan logika manipulasi database kotor secara langsung tanpa melalui PDO parameterized statement).


## Security Guidelines (Anti-XSS & Data Protection)
Saat menulis, memodifikasi, atau membenahi program, agen wajib selalu menerapkan langkah-langkah keamanan data krusial:
- **Pencegahan Kebocoran Kredensial**: Hapus data sensitif (seperti hash password, token, api_key) menggunakan `unset()` di PHP/sisi server sebelum mengirim data tersebut ke client-side JavaScript.
- **Anti-XSS pada Script**: Selalu gunakan bendera `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT` saat menggunakan `json_encode()` di dalam tag `<script>` untuk mencegah *Script Break XSS*.
- **Anti-XSS pada Atribut HTML**: Jika data JSON disuntikkan ke dalam atribut HTML (seperti atribut `onclick="..."` atau `data-*`), wajib dibungkus dengan `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` guna mencegah *Attribute Break XSS*.
- **Pencegahan SQL Injection (SQLi)**: Selalu gunakan Prepared Statements dengan Parameter Binding (menggunakan PDO/bindValue/execute) untuk setiap kueri database yang memproses input dari pengguna. Jangan pernah menggabungkan variabel langsung ke dalam string SQL (seperti `"WHERE id = " . $id`).
- **Validasi & Sanitasi Input Sisi Server**: Setiap input dari request client (GET, POST, COOKIE) wajib divalidasi tipe datanya dan disanitasi menggunakan fungsi seperti `strip_tags()`, `htmlspecialchars()`, `filter_var()`, atau regex sebelum digunakan dalam proses logika bisnis aplikasi.


## Modern Architecture & Zero Data Leakage Development (Wajib Pola Asinkronus)
Saat merancang fitur baru atau memodifikasi modul yang ada, agen WAJIB menerapkan arsitektur asinkronus modern (*API-driven*) dengan prinsip *Zero Data Leakage*:

**1. Larangan Mutlak Server-Side Data Injection (Zero Data Leakage):**
- **DILARANG KERAS** mencetak data mentah dari database langsung menggunakan PHP `json_encode` di dalam blok skrip HTML (`<script>`) seperti `const listData = <?= json_encode($data) ?>;`.
- Seluruh pemuatan data sensitif (data siswa, guru, tagihan, nilai, buku, sirkulasi, agenda, profil sekolah, dsb.) **WAJIB** dialihkan menggunakan arsitektur dynamic fetch asinkronus (Axios / Fetch API) pada saat komponen terpasang di sisi klien (`onMounted` di Vue 3).
- **Tujuan**: Memastikan kode sumber halaman saat ditekan `Ctrl + U` (View Page Source) 100% bersih dari kebocoran data rahasia/sensitif.

**2. Standar Lifecycle & Re-Mounting Asinkronus (Anti-Blank Screen):**
- Inisialisasi state reaktif Vue selalu dimulai dalam keadaan kosong (`ref([])`, `ref(null)`, `ref(false)`) dengan skeleton loading / spinner indicator.
- Setiap aplikasi Vue pada view wajib didaftarkan melalui `window.VueAppRegistry` atau dilengkapi handler terhadap event `turbo:load`, `turbo:render`, dan `popstate` agar ketika pengguna menavigasi Back/Forward di browser, aplikasi di-mount ulang secara instan dan atribut `[v-cloak]` dilepas otomatis tanpa menyebabkan layar putih/kosong.

**3. Standardisasi Endpoint API & JSON Response:**
- Controller penyedia data asinkronus wajib mengembalikan format standar:
  ```json
  {
    "success": true,
    "data": [ ... ],
    "message": "Data berhasil dimuat",
    "error": null
  }
  ```
- Gunakan HTTP Status Code yang tepat: `200 OK` (sukses), `400 Bad Request` (input salah), `403 Forbidden` (akses ditolak), `422 Unprocessable Entity` (validasi gagal), `500 Internal Server Error` (gangguan server).

**4. Template Standar Pola Asinkronus di Sisi Klien (Vue 3 View):**
```html
<div id="app-container" v-cloak>
    <!-- Skeleton / Spinner Loading -->
    <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="text-muted mt-2">Memuat data secara asinkron...</p>
    </div>

    <!-- Data Render -->
    <div v-else>
        <!-- Konten Halaman -->
    </div>
</div>

<script>
(() => {
    const { createApp, ref, onMounted } = Vue;

    const appConfig = {
        setup() {
            const items = ref([]);
            const loading = ref(false);

            const fetchData = async () => {
                loading.value = true;
                try {
                    const res = await axios.get('<?= $this->getBaseUrl() ?>/[modul]/api/[endpoint]');
                    if (res.data && res.data.success) {
                        items.value = res.data.data;
                    }
                } catch (err) {
                    console.error('Gagal memuat data asinkron:', err);
                } finally {
                    loading.value = false;
                }
            };

            onMounted(() => {
                fetchData();
            });

            return { items, loading, fetchData };
        }
    };

    if (window.VueAppRegistry) {
        window.VueAppRegistry.register('#app-container', appConfig);
    } else {
        document.addEventListener('DOMContentLoaded', () => createApp(appConfig).mount('#app-container'));
    }
})();
</script>
```


## Fast-Track Migration Cheat Sheet (MySQL -> PostgreSQL Multi-Schema)
Untuk mempercepat migrasi dari MySQL ke PostgreSQL tanpa error runtime, agen wajib mengacu pada padanan sintaks berikut:

| Kebutuhan SQL | MySQL Syntax (Legacy) | PostgreSQL Syntax (Wajib Gunakan Ini) |
|---|---|---|
| **Paginasi Data** | `LIMIT offset, count` | `LIMIT count OFFSET offset` |
| **Pencarian Case-Insensitive** | `WHERE name LIKE '%abc%'` | `WHERE name ILIKE '%abc%'` |
| **Dapatkan ID Baru** | `LAST_INSERT_ID()` | `RETURNING id` (pada query INSERT) |
| **Tanggal / Waktu** | `NOW()` / `CURRENT_TIMESTAMP` | `CURRENT_TIMESTAMP` / `NOW()` |
| **Tipe Data Boolean** | `1` / `0` atau `'1'` / `'0'` | `TRUE` / `FALSE` (strict boolean) |
| **Filter Data JSON** | `JSON_EXTRACT(col, '$.key')` | `col->>'key'` (text) atau `col->'key'` (json) |
| **Upsert (Insert / Update)** | `ON DUPLICATE KEY UPDATE` | `ON CONFLICT (target_col) DO UPDATE SET ...` |
| **Inspeksi Struktur Tabel** | `SHOW COLUMNS FROM tbl` | Kueri `information_schema.columns` |
| **Prefix Skema** | `nama_tabel` | `skema.nama_tabel` (misal: `siswa.buku_induk`) |


## Template Standar Fast-Development (Modular Controller & Model)

### Template Controller: `app/Modules/[NamaModul]/Controllers/[NamaModul]Controller.php`
```php
<?php
namespace App\Modules\[NamaModul]\Controllers;

use App\Core\BaseController;
use App\Modules\[NamaModul]\Models\[NamaModul]Model;
use Exception;

class [NamaModul]Controller extends BaseController
{
    private $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new [NamaModul]Model();
    }

    public function index()
    {
        try {
            $tenantId = $this->getSecureTenantId();
            $data = $this->model->getAllByTenant($tenantId);
            return $this->jsonResponse(['success' => true, 'data' => $data], 200);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
```

### Template Model: `app/Modules/[NamaModul]/Models/[NamaModul]Model.php`
```php
<?php
namespace App\Modules\[NamaModul]\Models;

use App\Config\Database;
use PDO;

class [NamaModul]Model
{
    protected $db;
    protected $table = '[skema].[nama_tabel]';

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getAllByTenant(string $tenantId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE tenant_id = :tenant_id AND deleted_at IS NULL ORDER BY created_at DESC");
        $stmt->bindValue(':tenant_id', $tenantId, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
```


## Automated Dynamic System Auditor & Fast-Track Runner (WAJIB DIJALANKAN)
Setiap kali membuat modul baru, mengubah controller/model, atau melakukan refactoring kueri database, agen **WAJIB** secara otomatis menjalankan program audit dinamis untuk memverifikasi 16 skema PostgreSQL, namespace modul, dan sintaks kueri:

```powershell
php scratch/pengujian/sinta_dynamic_auditor_and_runner.php
```

**Standar Kelulusan Sistem:**
1. **Status Aplikasi**: Harus menunjukkan `100% DINAMIS, MODULAR, DAN SESUAI STANDAR POSTGRESQL!`.
2. **Peringatan MySQL Legacy**: Harus `0`.
3. **Kesalahan Namespace**: Harus `0`.
4. **Skema Terhubung**: Seluruh 16 skema (`sistem`, `core`, `akademik`, `kesiswaan`, `bk`, `absensi`, `kepegawaian`, `keuangan`, `perpustakaan`, `persuratan`, `sarpras`, `siswa`, `pdss`, `tracer`, `cms`, `smk`) harus dalam status `[OK]`.


# Custom Rules
## Testing and Checking Files
Selalu simpan file percobaan, pengujian (*testing*), atau pengecekan (seperti file dengan awalan `test_`, `check_`, `grant_`,`audit_`,`inspect_`, dsb.) HANYA ke dalam folder `C:\laragon\www\sinta\scratch\pengujian`. Jangan pernah menyimpan file-file sementara ini di *root directory* atau direktori inti aplikasi lainnya.

## Implementation Plans & Walkthroughs (Flexible Overhead Rule)
Setiap kali pekerjaan diselesaikan, dokumen rencana (*implementation plan*) dan dokumen hasil (*walkthrough*) wajib dicatat ke dalam **satu file harian** di `C:\laragon\www\sinta\scratch\docs`:
- `YYYY-MM-DD_Implementation_Plans_Harian.md`
- `YYYY-MM-DD_Walkthrough_Harian.md`

**Ketentuan Format & Beban Dokumentasi:**
1. **Fitur Baru / Refactoring Arsitektur Besar**: Wajib menyertakan seluruh konten rencana & walkthrough secara lengkap verbatim (termasuk kueri SQL, kode PHP/JS, tabel, dan verification plan).
2. **Perbaikan Bug Kecil / Minor Tweaks**: Cukup gunakan format **Compact Log** (10-25 baris) yang mencakup: *Root Cause + Files Changed + Solution + Quick Verification Result*. Hal ini bertujuan untuk menjaga kecepatan alur kerja pengembangan (*fast-track development*).

## Automatic Code Syntax Check Rule (WAJIB)
Setiap kali memodifikasi atau membuat berkas PHP baru, agen **WAJIB** secara otomatis menjalankan tes sintaks bebas error sebelum melaporkan pekerjaan selesai:
```powershell
php -l <path_file_php>
```
Pastikan output menunjukkan `No syntax errors detected`.

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
*(Tidak wajib dieksekusi di meingkatkan efisiensi kerja).*
Ketika pengguna meminta verifikasi, agen wajib secara otomatis menjalankan 2 langkah berikut:
1. **PHPStan Static Analysis**:
   ```powershell
   vendor/bin/phpstan analyse <path-file-atau-folder> --level=5
   ```
2. **Automated Security Audit Script**:
   ```powershell
   php scratch/tests/test_security_audit.php
   ```

## Strict Prohibition on Folder Deletion in Scratch (PERMANENT RULE)
DILARANG KERAS menghapus atau mengosongkan folder-folder berikut beserta seluruh isi berkas dan sub-foldernya dalam kondisi apa pun:
1. `C:\laragon\www\sinta\scratch\docs`
2. `C:\laragon\www\sinta\scratch\folder legacy`
3. `C:\laragon\www\sinta\scratch\tests`

Setiap kali pembersihan berkas dilakukan, ketiga direktori di atas WAJIB tetap aman, utuh, dan terlindungi dari segala bentuk perintah penghapusan.
