## Laravel 11 Modular Architecture & PostgreSQL 16 Multi-Schema
Saat merombak, membuat Model baru, membuat Controller, atau menambahkan fitur baru, agen WAJIB mematuhi standar arsitektur berikut:

**1. Struktur Direktori dan Namespace:**
- Modul mandiri berlokasi di `C:\laragon\www\sinta\Modules\[NamaModul]\`.
- 16 Modul terdaftar: `Core`, `Siswa`, `Akademik`, `Keuangan`, `Bk`, `Absensi`, `Kepegawaian`, `Perpustakaan`, `Persuratan`, `Sarpras`, `Kesiswaan`, `Pdss`, `Tracer`, `Cms`, `Sistem`, `Smk`.
- Setiap modul memiliki sub-struktur:
  - `Http/Controllers/`: Controller modul dengan namespace `Modules\[NamaModul]\Http\Controllers`.
  - `Entities/`: Eloquent Model dengan namespace `Modules\[NamaModul]\Entities`.
  - `Routes/`: Definisi routing `web.php` dan `api.php`.
  - `Providers/`: Service provider modul `Modules\[NamaModul]\Providers\[NamaModul]ServiceProvider.php`.

**2. Pendaftaran Modul, Routing & Kewajiban Middleware Group `'web'` (Anti-Session Drop & Dashboard Trap):**
- Di Laravel 11, Service Provider didaftarkan secara eksplisit di `bootstrap/providers.php` (bukan di `config/app.php`).
- Service Provider modul wajib me-load routing modul melalui `$this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');`.
- **Kewajiban Mutlak Middleware `'web'`**: Di Laravel 11, rute modular yang di-load via `loadRoutesFrom()` TIDAK otomatis mendapatkan middleware group `web`. Oleh karena itu, seluruh rute web modul di `Modules/[NamaModul]/Routes/web.php` **WAJIB** dibungkus dengan middleware group `'web'` (`Route::middleware(['web', 'auth', 'tenant.guard'])`). Kegagalan menyertakan `'web'` akan menyebabkan sesi hilang (`StartSession` tidak jalan) dan memicu pengguna terlempar ke Dashboard saat mutasi form POST/PUT/DELETE.

**3. Pendefinisian Model Eloquent PostgreSQL Multi-Schema:**
- Seluruh model modul wajib mewarisi `Modules\Core\Entities\BaseTenantModel` (kecuali tabel katalog master global).
- Tentukan tabel secara eksplisit dengan prefix skema: `protected $table = '[skema].[nama_tabel]';` (contoh: `protected $table = 'siswa.siswa';`).
- Model wajib menyertakan properti:
  - `protected $keyType = 'string';`
  - `public $incrementing = false;` (menggunakan UUID).
- Terapkan `$casts` ketat untuk tipe data `boolean`, `integer`, `datetime`, atau `array` (JSON).
- **Tenant Scope Bypass**: Untuk keperluan Super Admin tingkat platform (antar-sekolah), gunakan kueri `[NamaModel]::withoutTenant()`.

**4. Pendefinisian Controller:**
- Controller mewarisi `App\Http\Controllers\Controller`.
- Controller mendukung respon ganda:
  - **Inertia Response** untuk antarmuka web: `Inertia::render('[Modul]/[Page]', $data)`
  - **JSON Response** untuk request API/Axios: `response()->json(['success' => true, 'data' => $data], 200)`
- Gunakan `$request->validate([...])` untuk seluruh validasi data input.

**5. Standar Autentikasi Laravel 11 (`config/auth.php`):**
- Provider model pengguna wajib diarahkan ke model modular: `'model' => Modules\Core\Entities\User::class`.
- Proxy model `App\Models\User` yang meng-extend `Modules\Core\Entities\User` wajib dipertahankan untuk backward compatibility package pihak ketiga.
- Sistem login mendukung autentikasi terpusat (Unified Login Portal) untuk seluruh role pengguna (`super_admin`, `admin_sekolah`, `guru`, `keuangan`, `bk`, `sarpras`, `perpustakaan`, `siswa`).

**6. Pipeline Frontend Vite, PostCSS & Tailwind CSS:**
- File `postcss.config.js` wajib memuat plugin `tailwindcss` dan `autoprefixer`.
- Konfigurasi `tailwind.config.js` wajib memindai seluruh komponen Vue 3:
  ```js
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
    "./Modules/**/Resources/**/*.blade.php",
    "./Modules/**/Resources/**/*.vue",
    "./Modules/**/Resources/**/*.js",
  ]
  ```
- Template utama `resources/views/app.blade.php` memuat aset via: `@vite(['resources/css/app.css', 'resources/js/app.js'])`.


## Security Guidelines (Anti-XSS, CSRF & Multi-Tenant Data Protection)
Saat menulis, memodifikasi, atau membenahi program, agen wajib selalu menerapkan langkah-langkah keamanan data krusial:
- **Multi-Tenant Data Isolation**: Wajib terisolasi per `tenant_id` secara otomatis melalui `BaseTenantModel` dan `TenantRouteGuard`.
- **Tenant Storage Limit**: Seluruh fitur upload berkas wajib melewati middleware `TenantStorageGuard` untuk mencegah penyalahgunaan kuota penyimpanan.
- **Pencegahan Kebocoran Kredensial**: Hapus data sensitif (hash password, token, session keys, PIN, OTP, CVV, server secret keys) di sisi server via `SecurityPayloadService::sanitize()` sebelum dikirimkan ke frontend props/JSON.
- **Dukungan Password Hashing Multi-Format**: Gunakan `password_verify($password, $user->password_hash)` agar kompatibel secara transparan dengan hash lama (Argon2id) maupun baru (Bcrypt).
- **Anti-XSS & Anti-Injection**: Gunakan Eloquent Parameter Binding atau Prepared Statements. Dilarang menggabungkan raw SQL string secara langsung.
- **CSRF Protection**: Seluruh mutasi data via HTTP POST, PUT, DELETE wajib terlindungi oleh Laravel CSRF middleware.
- **Anti-IDOR & Tenant Session Isolation**: Isolasi tenant wajib mengacu pada sesi otentikasi server (`auth()->user()->tenant_id`), bukan mempercayai input `tenant_id` dari klien bebas (kecuali user adalah `super_admin`). Dilarang membiarkan user biasa memanipulasi `tenant_id` untuk membobol atau melihat data sekolah lain.


## Standardisasi Data Protection, Anti-Scraping, Payload Encryption & Anti-DOM Leakage (OWASP ASVS L3 & Zero-Trust)
Agen WAJIB mematuhi prinsip *Zero-Trust Data Protection* dan mitigasi inspeksi browser pada seluruh modul SINTA:

**1. Proteksi Penyembunyian ID Sekolah (`tenant_id`), Nilai Siswa, Rekam Medis & Data Sensitif:**
- **Penyembunyian ID Sekolah (`tenant_id`) & Metadata Internal**:
  - `tenant_id` (UUID sekolah) adalah pengenal arsitektur internal database multi-tenant dan **DILARANG KERAS** diekspos secara terbuka di view source, atribut DOM, atau props bagi non-superadmin.
  - Untuk pengguna tingkat sekolah (`admin_sekolah`, `guru`, `keuangan`, `siswa`, `bk`), server **TIDAK BOLEH** mengirimkan daftar seluruh tenant/sekolah lain (`tenants`) atau mengekspos identifier internal sekolah.
  - Seluruh isolasi data sekolah wajib dikunci otomatis di level ORM (`BaseTenantModel`) dan session guard.
- **Penyembunyian & Proteksi Nilai Rapor / Transkrip Akademik**:
  - Data nilai siswa, catatan akademik, dan transkrip rapor **DILARANG KERAS** dicetak ke dalam dokumen HTML statis (SSR View Source).
  - Pemuatan nilai rapor wajib menggunakan model *On-Demand Client Fetch* atau terenkripsi via `SecurityPayloadService::wrapEncryptedResponse()`.
  - Mutasi nilai wajib memvalidasi kepemilikan guru/kelas pengajar bersangkutan di server guna mencegah *Grade Tampering Injection*.
- **Proteksi Data Pribadi (PII), Rekam Medis BK & Keuangan**:
  - NIK, Nomor KK, Rekam Medis BK, Gaji Orang Tua, dan kontak pribadi siswa/guru tidak boleh dicetak mentah di SSR.

**2. Proteksi Pelepasan Data di SSR / View Source (Ctrl + U & Save HTML):**
- **Mengapa Data Bisa Terbaca di View Source pada Inertia Standar?** Secara default, Inertia.js menyematkan seluruh props yang dikirim dari controller ke dalam atribut HTML `<div id="app" data-page="{...}">`. Akibatnya, saat pengguna melakukan *Right Click -> View Page Source* (`Ctrl + U`) atau *Save Page As HTML*, data mentah database tercetak dalam dokumen HTML statis.
- **Pilar Pencegahan Kebocoran Data SINTA:**
  1. **Pembersihan Otomatis Atribut DOM `data-page`**:
     Pada [resources/js/app.js](file:///C:/laragon/www/sinta/resources/js/app.js), elemen root `<div id="app">` **WAJIB** menghapus atribut `data-page` (`el.removeAttribute('data-page')` & `delete el.dataset.page`) seketika saat hidrasi Vue selesai terpasang (`.mount(el)`). Ini mencegah ekstraksi payload melalui inspeksi objek DOM browser (`document.getElementById('app').dataset.page`).
  2. **Model Pemuatan Data On-Demand (Zero-SSR Data Exposure)**:
     Untuk halaman dengan data sensitif (pengguna, buku induk, keuangan, master data, nilai rapor):
     - **Initial HTTP GET Route**: Controller HANYA me-render kerangka/shell UI dengan props minimal (master filter katalog & user context), TANPA memuat rekaman database langsung ke SSR props.
     - **Asynchronous Client Loading**: Komponen Vue memuat data bisnis aktual secara asinkronus pada hook `onMounted()` menggunakan Axios API terenkripsi / tersanitasi.
     - **Hasil**: Dokumen HTML mentah di View Source (`Ctrl + U`) 100% bersih dari data rekaman database, mencegah web scraping offline dan ekstraksi data via HTML snapshot.

**3. Enkripsi Payload API & Transmisi Data Sensitif (AES-256-CBC with HMAC):**
- Untuk transaksi dan endpoint bernilai tinggi / sensitif (Keuangan, Gaji/Honor, Rekam Medis BK, NIK/KK, Kredensial Pengguna), payload ditransmisikan menggunakan envelope enkripsi `SecurityPayloadService::wrapEncryptedResponse($data)` / `SecurityPayloadService::encrypt($data)` berbasis AES-256-CBC dan HMAC-SHA256.
- Dekripsi dilakukan secara instan di sisi klien melalui `decryptPayload()` di [resources/js/Utils/cryptoSecurity.js](file:///C:/laragon/www/sinta/resources/js/Utils/cryptoSecurity.js) menggunakan Web Crypto API (`crypto.subtle`) hanya di level *runtime memory*.
- **Larangan Persistensi**: Dilarang menyimpan payload yang didekripsi ke dalam objek global `window`, `localStorage`, atau `sessionStorage`.

**4. Anti-DOM Leakage & Memory Hygiene (useMemorySecurity):**
- **DILARANG KERAS** menyematkan data sensitif sebagai atribut HTML DOM (misal: `<tr data-nik="..." data-gaji="..." data-pin="..." data-tenant="...">`).
- Komponen Vue 3 yang menangani data sensitif wajib menerapkan composable `useMemorySecurity([stateRef1, stateRef2])` untuk mengosongkan (*garbage collect*) state memori browser seketika saat komponen dilepas (*unmounted*).
- Setiap halaman dilindungi oleh `installAntiInspectionGuard()` yang bekerja secara senyap (*silent protection*) untuk membersihkan variabel global `window.__INITIAL_STATE__` tanpa mencetak banner peringatan di konsol pengembang.

**5. Sanitasi Data Server-Side (SecurityPayloadService::sanitize):**
- Seluruh controller yang mengirimkan data ke frontend (baik via Inertia Props maupun JSON API) **WAJIB** menyaring data melalui `SecurityPayloadService::sanitize($data)` untuk memastikan field sensitif (password hash, secret token, session ID) tidak pernah keluar dari server.

**6. Standardisasi Clean URL In-Memory State & Larangan URL Query Bloat (`tenant_id` & Filter Leakage):**
- **Larangan Mutlak Menempelkan `tenant_id` pada URL Address Bar**:
  - `tenant_id` (UUID sekolah) **DILARANG KERAS** dimasukkan sebagai query parameter di URL browser (`?tenant_id=...`), routing web publik, ataupun link navigasi bagi pengguna tingkat sekolah (`admin_sekolah`, `guru`, `keuangan`, `siswa`, `bk`).
  - Isolasi data sekolah wajib sepenuhnya mengacu pada sesi otentikasi server (`auth()->user()->tenant_id`).
- **Standardisasi Clean URL In-Memory State**:
  - Filter pencarian, pagination, tab switching, dan parameter dropdown di halaman web harus dikelola murni di dalam *Client Reactive Memory State* via Axios API On-Demand (`axios.get('/endpoint?async=1', { params })`), BUKAN dengan me-reload seluruh parameter ke address bar browser via `router.get('/endpoint?param1=&param2=...')`.
  - Dilarang menumpuk query string kosong atau parameter berlebih di URL (misal: `&jenjang=&kelas=&search=&trash=0`).
  - Alamat URL yang ditampilkan di browser wajib tetap bersih, rapi, dan ringkas (contoh: `http://sinta.test:8080/pengguna` atau `http://sinta.test:8080/pengguna?tab=guru`).
- **Pencegahan Kebocoran Informasi (Privacy & Information Leakage Mitigation)**:
  - Mencegah kebocoran data sensitif yang tersimpan di *Browser Navigation History*, *Proxy / VPN Cache*, serta *Web Server Access Logs* (`access.log`).
  - Mencegah serangan *Insecure Direct Object Reference* (IDOR) dan *Parameter Tampering* di address bar.

**7. Pengujian Keamanan Mandiri via CLI (CLI Data Protection Runner):**
Setiap kali melakukan perubahan sistem keamanan atau refactoring data, agen **WAJIB** menjalankan pengujian keamanan data otomatis:
```powershell
php scratch/pengujian/test_data_protection_and_encryption.php
```
Standar kelulusan: **5/5 Tests PASS (100% Pass)**.


## Standardisasi UI/UX Searchable Select & Live Filter Dropdown (WAJIB DI SELURUH MODUL)
Dalam seluruh antarmuka SINTA (baik fitur yang sudah ada maupun modul baru yang akan dibangun), agen **DILARANG KERAS** menggunakan elemen dropdown bawaan HTML (`<select>`). Seluruh dropdown wajib diganti menggunakan komponen terstandarisasi [SearchableSelect.vue](file:///C:/laragon/www/sinta/resources/js/Components/SearchableSelect.vue):

**1. Fitur Mutlak SearchableSelect:**
- 🔍 **Live Keyword Search**: Kotak pencarian otomatis muncul saat dropdown dibuka, menyaring pilihan secara real-time berdasarkan label utama maupun sub-label (seperti NPSN, kode jurusan, email guru).
- ⌨️ **Navigasi Keyboard Penuh**: Mendukung tombol panah ↑ / ↓ untuk menyorot pilihan, tombol `Enter` untuk memilih, dan `Escape` untuk menutup popup dropdown.
- 🎯 **Auto-Focus Cerdas**: Kursor otomatis aktif pada kotak pencarian saat dropdown dibuka.
- 🏢 **Multi-Tenant & Filter Support**: Mendukung pemfilteran sekolah (*Tenant Switcher*) bagi Super Admin serta *Cascading Dropdown* (pilihan bertingkat seperti Provinsi ➔ Kota ➔ Kecamatan ➔ Kelurahan) dengan pembersihan nilai anak otomatis.

**2. Format Data Opsi Terstandarisasi:**
Komponen menerima properti `options` berupa array of objects:
```javascript
const options = [
  { id: 'uuid-atau-kode', nama: 'Label Utama', subLabel: 'Keterangan Tambahan (Opsional)' },
  // ...
]
```

**3. Contoh Penggunaan di Vue 3:**
```vue
<SearchableSelect 
  v-model="form.id_kelas" 
  :options="kelasOptions"
  placeholder="-- Pilih Rombel / Kelas --"
  search-placeholder="Cari kelas atau rombel..."
  @change="handleKelasChange"
/>
```


## Modern Architecture & Zero Data Leakage (Inertia.js + Vue 3)
Saat merancang antarmuka pengguna atau memodifikasi modul yang ada, agen WAJIB menerapkan arsitektur *API-driven* dengan prinsip *Zero Data Leakage*:

**1. Larangan Mutlak Server-Side Data Injection:**
- **DILARANG KERAS** mencetak data mentah dari database langsung menggunakan PHP `json_encode` di dalam tag skrip HTML (`<script> const listData = <?= json_encode($data) ?>; </script>`).
- Seluruh transmisi data sensitif **WAJIB** melalui Inertia Props yang disanitasi atau Axios API terenkripsi yang diproses di runtime memory sisi klien.

**2. Standarisasi Komponen Halaman Vue 3 (Inertia SFC):**
- Komponen halaman berlokasi di `resources/js/Pages/[Modul]/[SubFeature]/Index.vue`.
- Bungkus setiap halaman menggunakan layout standar `<AppLayout :title="...">`.
- Gunakan Composition API (`<script setup>`), Tailwind CSS v3 utilities, dan Bootstrap Icons (`bi-*`).
- Form handling menggunakan `@inertiajs/vue3` (`useForm()`) dan navigasi via `<Link :href="...">` atau `router.visit()`.

**3. Template Standar Komponen Vue 3:**
```vue
<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import SearchableSelect from '@/Components/SearchableSelect.vue'
import { useMemorySecurity } from '@/Utils/cryptoSecurity.js'
import { Link, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({
    items: Object,
    filters: Object,
})

const formData = ref({})
useMemorySecurity([formData])
</script>

<template>
    <AppLayout title="Nama Modul">
        <div class="space-y-6">
            <!-- Header Halaman -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">Judul Fitur</h1>
                    <p class="text-sm text-slate-500">Deskripsi singkat fungsi fitur modul</p>
                </div>
            </div>

            <!-- Tabel / Konten Data -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <!-- Konten Antarmuka -->
            </div>
        </div>
    </AppLayout>
</template>
```


## Fast-Track Migration Cheat Sheet (PostgreSQL 16 Multi-Schema)
Untuk menjaga kompatibilitas kueri tanpa runtime error, agen wajib mengacu pada padanan sintaks berikut:

| Kebutuhan SQL | PostgreSQL Syntax (Wajib Gunakan Ini) |
|---|---|
| **Paginasi Data** | `LIMIT count OFFSET offset` (atau Eloquent `paginate(15)`) |
| **Pencarian Case-Insensitive** | `WHERE column ILIKE '%abc%'` |
| **Dapatkan ID Baru** | `RETURNING id` (pada query INSERT) |
| **Tanggal / Waktu** | `CURRENT_TIMESTAMP` / `now()` |
| **Tipe Data Boolean** | `TRUE` / `FALSE` (strict boolean) |
| **Filter Data JSON** | `col->>'key'` (text) atau `col->'key'` (json) |
| **Upsert (Insert / Update)** | `ON CONFLICT (target_col) DO UPDATE SET ...` (atau `updateOrCreate()`) |
| **Inspeksi Struktur Tabel** | Kueri `information_schema.columns` |
| **Prefix Skema** | `skema.nama_tabel` (misal: `siswa.siswa`, `akademik.kelas`) |
| **Nilai Null Pengganti** | `COALESCE(kolom, 'default')` (bukan `IFNULL`) |
| **Penggabungan String Baris** | `STRING_AGG(kolom, ', ')` (bukan `GROUP_CONCAT`) |
| **Ekstrak Detik Unix** | `EXTRACT(EPOCH FROM kolom)` (bukan `UNIX_TIMESTAMP`) |


## Template Standar Fast-Development (Laravel 11 Modular Controller & Model)

### Template Controller: `Modules/[NamaModul]/Http/Controllers/[NamaModul]Controller.php`
```php
<?php
namespace Modules\[NamaModul]\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Modules\[NamaModul]\Entities\[NamaModel];

class [NamaModul]Controller extends Controller
{
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $items = [NamaModel]::orderBy('created_at', 'desc')->paginate(15);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data'    => $items,
            ]);
        }

        return Inertia::render('[NamaModul]/Index', [
            'items' => $items,
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'nama_field' => 'required|string|max:255',
            'is_active'  => 'boolean',
        ]);

        $item = [NamaModel]::create($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Data berhasil disimpan.', 'data' => $item], 201);
        }

        return back()->with('success', 'Data berhasil disimpan.');
    }
}
```

### Template Model Eloquent: `Modules/[NamaModul]/Entities/[NamaModel].php`
```php
<?php
namespace Modules\[NamaModul]\Entities;

use Modules\Core\Entities\BaseTenantModel;

class [NamaModel] extends BaseTenantModel
{
    protected $table = '[skema].[nama_tabel]';

    protected $fillable = [
        'id',
        'tenant_id',
        'nama_field',
        'kategori',
        'deskripsi',
        'is_active',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
```


## Standar Queue Jobs, Services & Integrasi Gateway

**1. Queue Jobs (Spatie Browsershot & Billing):**
- Seluruh tugas komputasi berat (seperti cetak rapor massal via Puppeteer/Browsershot atau generate tagihan SPP bulanan) wajib dibuat sebagai Queue Job yang mengimplementasikan `ShouldQueue`.
- Struktur: Menggunakan trait `Dispatchable, InteractsWithQueue, Queueable, SerializesModels`.

**2. Midtrans Payment Gateway Webhook:**
- Handler webhook (`PaymentWebhookController.php`) wajib memvalidasi *Signature Key* HMAC SHA-512 sebelum memproses *auto-settlement*:
  ```php
  $signature = hash('sha512', $orderId . $statusCode . $grossAmount . config('payment.midtrans.server_key'));
  ```

**3. WhatsApp Gateway Service:**
- Integrasi notifikasi pesan instan ke orang tua/siswa wajib diproses melalui `App\Services\WhatsAppGatewayService`.


## Environment CLI Laragon & Frontend Build Tools (WAJIB DIPATUHI)
Saat menjalankan perintah terminal atau skrip audit otomatis, agen wajib mengacu pada path executable berikut:
- **PHP CLI**: `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe`
- **Composer**: `C:\laragon\bin\composer\composer.phar`
- **Node.js & NPM**: `C:\laragon\bin\nodejs\node-v22\` (atau gunakan runner `scratch\pengujian\run_npm.bat`)


## Automated System Verification & Quality Audit (WAJIB DIJALANKAN)
Setiap kali membuat modul baru, mengubah controller/model, atau melakukan refactoring kueri database, agen **WAJIB** secara otomatis menjalankan rangkaian program audit verifikasi:

```powershell
# 1. Verifikasi Sintaks Seluruh Berkas PHP (Wajib 0 Syntax Error)
php scratch/pengujian/verify_all_syntax.php

# 2. Audit Relasi & Konektivitas 30 Model Eloquent terhadap 16 Skema PostgreSQL
php scratch/pengujian/audit_all_models.php

# 3. Simulasi HTTP Request End-to-End pada 19 Endpoint Modul
php scratch/pengujian/test_http_endpoints.php
```

**Standar Kelulusan Sistem:**
1. **Sintaks PHP**: Wajib `0 Syntax Error` pada seluruh berkas.
2. **Koneksi Database**: 100% Model Eloquent terhubung ke tabel riil PostgreSQL.
3. **Endpoint HTTP**: 19/19 Endpoint mengembalikan status `HTTP 200 OK`.


# Custom Rules
## Testing, Automated QA and Checking Files (WAJIB)
Seluruh file pengujian, percobaan (*testing*), skrip QA otomatis, atau pengecekan (seperti file dengan awalan `test_`, `check_`, `grant_`,`audit_`,`inspect_`, serta *Automated Feature Tests* seperti `TenantIsolationTest`, `PaymentConcurrencyTest`, `RbacSecurityTest`) **WAJIB** disimpan dan dijalankan HANYA di dalam folder:
- `C:\laragon\www\sinta\scratch\tests\` (untuk Automated QA & Feature Test Suite)
- `C:\laragon\www\sinta\scratch\pengujian\` (untuk skrip verifikasi dinamis & audit runner)

Dilarang keras menempatkan file pengujian sementara atau test suite di *root directory* atau direktori inti aplikasi (`app/`, `Modules/`, `routes/`, `database/`, `public/`). Seluruh kegiatan Quality Assurance (QA) wajib dipusatkan di folder `scratch/tests/` dan `scratch/pengujian/`.

## Implementation Plans & Walkthroughs (Wajib Otomatis Per Hari / Daily Automation Rule)
Setiap kali pekerjaan dimulai dan diselesaikan, agen **WAJIB SECARA OTOMATIS** mengelola dan mencatat progres ke dalam **dua file dokumentasi harian** di `C:\laragon\www\sinta\scratch\docs` berdasarkan tanggal lokal sistem yang sedang berjalan (`YYYY-MM-DD`):
- `C:\laragon\www\sinta\scratch\docs\YYYY-MM-DD_Implementation_Plans_Harian.md`
- `C:\laragon\www\sinta\scratch\docs\YYYY-MM-DD_Walkthrough_Harian.md`

**Prosedur Otomatisasi Harian Agen (MANDATORY WORKFLOW):**
1. **Deteksi Tanggal Lokal**: Agen mendeteksi tanggal lokal sesi saat ini dari sistem metadata (format `YYYY-MM-DD`, contoh: `2026-09-09`).
2. **Auto-Create File Baru**: Jika file harian untuk tanggal hari ini belum tersedia di `scratch/docs/`, agen **WAJIB LANGSUNG MEMBUAT** kedua file tersebut secara otomatis pada respons/tahapan pertama dengan header resmi harian.
3. **Pemisahan Ketat Antar-Hari (No Cross-Day Leakage)**: Dilarang keras mencampur atau menuliskan log pekerjaan tanggal hari ini ke file hari kemarin/sebelumnya. Setiap hari memiliki berkas mandiri.
4. **Append & Penomoran Tahap Real-Time**: Setiap pekerjaan yang dieksekusi wajib ditambahkan ke file harian dengan timestamp detail WIB (`## 🕒 [HH:MM - HH:MM WIB] Tahap X: ...`).
5. **Beban Dokumentasi**:
   - **Fitur Baru / Refactoring Besar**: Wajib menyertakan analisis, perubahan berkas, SQL/migrasi, test suite, dan hasil verifikasi secara lengkap verbatim.
   - **Perbaikan Bug Kecil / Minor Tweaks**: Cukup gunakan format **Compact Log** (10-25 baris): *Waktu (WIB) + Root Cause + Files Changed + Solution + Quick Verification Result*.

## Automatic Code Syntax Check Rule (WAJIB)
Setiap kali memodifikasi atau membuat berkas PHP baru, agen **WAJIB** secara otomatis menjalankan tes sintaks bebas error sebelum melaporkan pekerjaan selesai:
```powershell
php -l <path_file_php>
```
Pastikan output menunjukkan `No syntax errors detected`.

## Database Migration Rules & Pre-Migration Schema Inspection (WAJIB)

### 1. Mandatory Pre-Migration & Database Schema Inspection Protocol
Sebelum membuat migrasi baru, menambahkan kolom baru, membuat model Eloquent baru, atau memanggil kolom database pada kueri controller, agen **WAJIB** terlebih dahulu melakukan inspeksi struktur database riil:

**Prosedur Wajib Inspeksi Database:**
1. **Inspeksi Skema & Kolom Riil**: Selalu periksa keberadaan tabel dan kolom melalui `information_schema.columns` atau jalankan runner:
   ```powershell
   php scratch/pengujian/inspect_database_migrations_schema.php
   ```
2. **Dilarang Asumsi Kolom (Anti-Assumption Rule)**:
   - Dilarang menebak nama kolom (contoh: mengasumsikan kolom `tingkat` di `siswa.siswa` padahal yang tersedia adalah relasi kelas atau kolom lain).
   - Pastikan tipe data, batasan *nullability*, dan nilai default sesuai dengan skema PostgreSQL.
3. **Pencegahan Redundansi & Duplikasi Migrasi**:
   - Periksa daftar migrasi di `database/migrations/` dan tabel `migrations` untuk memastikan fitur/kolom tersebut belum pernah dibuat sebelumnya.
   - Jika tabel sudah ada, gunakan `Schema::table('skema.nama_tabel', function (Blueprint $table) { ... })` dengan pemeriksaan `if (!Schema::hasColumn('skema.nama_tabel', 'nama_kolom'))`.
4. **Sinkronisasi Model Eloquent ($fillable & $casts)**:
   - Pastikan setiap properti di `$fillable`, `$casts`, dan parameter `select(...)` di Controller 100% cocok dengan kolom riil di PostgreSQL.

### 2. Standar Format Migrasi Laravel 11 (Anonymous Class Migration)
Setiap kali membuat file migrasi baru di folder `database/migrations/`, wajib menggunakan format **Anonymous Class Migration** resmi Laravel 11:

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skema.nama_tabel', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('nama_kolom');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skema.nama_tabel');
    }
};
```


## Senior Security Auditor, OWASP ASVS L3 & Direct Remediation Protocol (WAJIB)
Ketika melakukan analisis, audit keamanan, atau saat pengguna meminta perbaikan dan verifikasi, agen **WAJIB** bertindak sebagai **Senior Programmer & Database Security Auditor** dengan mematuhi protokol terpadu:

**1. Fokus Area Evaluasi Keamanan (Critical & High):**
- **Multi-Tenant Data Isolation**: Wajib filter `tenant_id` pada seluruh skema selain tabel master katalog global via `BaseTenantModel` atau parameter binding.
- **Anti-SQL Injection (SQLi)**: Wajib Prepared Statements & Eloquent Parameter Binding untuk seluruh kueri dinamis. Dilarang konkatenasi string SQL mentah.
- **Anti-XSS & Anti-Script-Break**: Terapkan perlindungan Anti-Script-Break XSS (`JSON_HEX_*`), escaping karakter khusus HTML, dan hindari injeksi variabel mentah di skrip.
- **CSRF Protection**: Wajib Laravel CSRF token pada seluruh mutasi data via HTTP POST, PUT, PATCH, DELETE.
- **PostgreSQL 16 Multi-Schema Standards**: Bersihkan seluruh sintaks MySQL legacy (`LIMIT x, y`, `NOW()`, `1/0 boolean`, `IFNULL`, `GROUP_CONCAT`).
- **Session Security & Credential Protection**: Hapus data sensitif (hash password, token, session keys) sebelum dikirimkan ke props Inertia/JSON.

**2. Prosedur Investigasi & Kepatuhan:**
- **Root Cause Analysis**: Jelaskan akar masalah teknis secara ringkas, lugas, dan tepat sasaran.
- **OWASP ASVS L3 Compliance**: Pastikan seluruh solusi memenuhi standar Application Security Verification Standard Level 3.

**3. Standar Penulisan Kode & Eksekusi Perbaikan (Tuntas & Drop-In Replacement):**
- **KODE HARUS UTUH**: Dilarang keras menggunakan placeholder (seperti `// sisa kode di sini`, `/* ... */`, atau pemotongan blok logika). Seluruh fungsi, class, template, dan skrip harus dituliskan secara lengkap dan tuntas.
- **SIAP PAKAI (DROP-IN REPLACEMENT)**: Kode harus berupa pengganti utuh sehingga pengguna dapat langsung menggunakannya tanpa perlu merakit ulang secara manual.
- **LANGSUNG EKSEKUSI PERBAIKAN**: Begitu agen menemukan akar penyebab masalah dan sudah mendapatkan solusinya, agen WAJIB langsung menerapkan perbaikan (*refactored & patched code*) secara tuntas pada berkas proyek terkait.

**4. Eksekusi Pengujian Keamanan (Security Audit Runner):**
```powershell
php scratch/tests/test_security_audit.php --target "C:\laragon\www\sinta" --mode full_audit --format terminal
```

## Standardisasi Desain UI/UX Horizontal NavTabs & Scroller Engine (WAJIB)
Saat membuat halaman baru atau merombak tata letak bilah navigasi tab (navtab / navpills) di seluruh modul SINTA, agen **WAJIB** menerapkan standar desain modern pill layout dan interaksi 3-way horizontal scroller di dalam `AppLayout.vue` dan Vue 3 components:

**1. Struktur Markup Standar (Modern Pill NavTab di Vue 3 / Inertia):**
```vue
<div class="bg-white rounded-2xl shadow-2xs border border-slate-200/80 p-2 mb-4 relative">
    <div class="flex items-center relative">
        <!-- 1 Tombol Panah Kiri -->
        <button type="button" 
                class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs me-1.5 hidden md:flex items-center justify-center shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition w-[34px] h-[34px] z-5" 
                onclick="document.getElementById('[idNavTabs]')?.scrollBy({ left: -220, behavior: 'smooth' })"
                title="Geser ke Kiri">
            <i class="bi bi-chevron-left"></i>
        </button>

        <!-- Container Deretan Tab -->
        <div class="nav-tabs-wrapper grow overflow-hidden relative">
            <ul class="flex border-0 flex-nowrap overflow-x-auto whitespace-nowrap scrollable-nav-tabs gap-1.5 px-1 select-none no-scrollbar" id="[idNavTabs]" role="tablist">
                <li class="nav-item">
                    <button class="border-0 font-semibold px-3.5 py-2 rounded-xl text-xs transition flex items-center" 
                            :class="activeTab === 'tab1' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                            @click="activeTab = 'tab1'">
                        <i class="bi bi-grid me-2 text-sm"></i> Tab Pertama
                    </button>
                </li>
            </ul>
        </div>

        <!-- 1 Tombol Panah Kanan -->
        <button type="button" 
                class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs ms-1.5 hidden md:flex items-center justify-center shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition w-[34px] h-[34px] z-5" 
                onclick="document.getElementById('[idNavTabs]')?.scrollBy({ left: 220, behavior: 'smooth' })"
                title="Geser ke Kanan">
            <i class="bi bi-chevron-right"></i>
        </button>
    </div>
</div>
```

**2. Ketentuan Interaktivitas NavTab:**
- **3-Way Interaction**: Deretan tab wajib mendukung:
  1. *Mouse Wheel to Horizontal Scroll*: Roda scroll mouse vertikal otomatis menggeser baris tab ke kiri/kanan.
  2. *Mouse Drag-to-Scroll (Swipe)*: Klik tahan dan seret dengan mouse untuk menggeser tab di desktop/mobile.
  3. *Tombol Panah Presisi*: Tombol chevron kiri dan kanan untuk menggeser sejauh $\pm 220\text{px}$.
- **Larangan Duplikasi Elemen (Anti-Double Buttons)**:
  - Tombol panah `<` dan `>` didefinisikan secara deklaratif tepat 1 pasang.
  - Skrip event listener scroll wheel dan drag HANYA menangani event gerak dan **DILARANG** melakukan injeksi DOM `createElement` tambahan yang memicu tombol ganda.

## Standardisasi Desain UI/UX Popup Modal & Full-Screen Dark Backdrop (WAJIB DIPATUHI)
Setiap kali membuat fitur baru, halaman baru, atau merombak komponen yang memuat jendela popup (modal dialog, modal form, modal konfirmasi, atau image/pdf viewer) di seluruh modul SINTA, agen **WAJIB** menerapkan standar arsitektur dan styling berikut agar tidak terjadi pemotongan layout atau kebocoran visual (*bleed-through*):

**1. Kewajiban Mutlak Vue 3 `<Teleport to="body">`:**
- **DILARANG KERAS** merender modal langsung di dalam pohon DOM anak halaman (`<main>` atau container fitur) tanpa teleportasi.
- Seluruh elemen popup modal **WAJIB** dibungkus ke dalam tag `<Teleport to="body">` agar dirender langsung di level `document.body` dan bebas dari batasan stacking context maupun `overflow: hidden` container induk.

**2. Stacking Context & Z-Index Standard (`AppLayout.vue` & Modal Backdrop):**
- Di `resources/js/Layouts/AppLayout.vue`:
  - Elemen header utama `<header>` wajib menggunakan kelas `relative z-10`.
  - Kontainer `<main>` wajib menggunakan kelas `relative z-20`.
  - Dilarang menaikkan `header` ke `z-30` / `z-50` bersamaan dengan `backdrop-blur` karena CSS Stacking Context akan memutus urutan rendering modal anak.
- Seluruh backdrop overlay modal **WAJIB** menggunakan `z-[9999]`:
  - `class="fixed inset-0 z-[9999] overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"`
- Lapisan gelap backdrop **WAJIB** menutupi 100% viewport monitor (`100vw x 100vh`) mencakup header atas (*SISTEM INTI AKADEMIK*, active tenant indicator, tombol logout), sidebar navigasi kiri, dan floating buttons tanpa ada bagian yang tembus/terpotong (*zero bleed-through*).

**3. Struktur Template Baku Popup Modal (Vue 3 / Inertia):**
```vue
<!-- STANDAR BAKU MODAL VUE 3 TELEPORT SINTA -->
<Teleport to="body">
  <div v-if="showModal" class="fixed inset-0 z-[9999] overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
    
    <!-- Kontainer Card Modal (Wajib relative z-10) -->
    <div class="relative z-10 bg-white rounded-3xl shadow-2xl border border-slate-200 w-full max-w-lg overflow-hidden animate-in fade-in zoom-in-95 duration-200">
      
      <!-- 1. Modal Header -->
      <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4 flex items-center justify-between text-white">
        <div>
          <h3 class="text-base font-bold flex items-center gap-2 m-0 text-white">
            <i class="bi bi-pencil-square"></i>
            Judul Modal
          </h3>
          <p class="text-xs text-blue-100 mb-0 mt-0.5">Deskripsi singkat fungsi form atau aksi modal.</p>
        </div>
        <button type="button" @click="showModal = false" class="text-white/80 hover:text-white text-xl cursor-pointer">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>

      <!-- 2. Modal Body Form -->
      <form @submit.prevent="handleSubmit" class="p-6 space-y-4">
        <!-- Field input form -->
        
        <!-- 3. Modal Actions Footer -->
        <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
          <button type="button" @click="showModal = false" class="h-9 px-4 border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 rounded-xl text-xs font-bold transition shadow-2xs cursor-pointer">
            Batal
          </button>
          <button type="submit" class="h-9 px-5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white rounded-xl text-xs font-bold transition shadow-xs flex items-center gap-1.5 cursor-pointer">
            <i class="bi bi-check2-circle"></i>
            <span>Simpan Data</span>
          </button>
        </div>
      </form>

    </div>
  </div>
</Teleport>
```

## Strict Prohibition on Folder Deletion in Scratch (PERMANENT RULE)
DILARANG KERAS menghapus atau mengosongkan folder-folder berikut beserta seluruh isi berkas dan sub-foldernya dalam kondisi apa pun:
1. `C:\laragon\www\sinta\scratch\docs`
2. `C:\laragon\www\sinta\scratch\folder legacy`
3. `C:\laragon\www\sinta\scratch\tests`

Setiap kali pembersihan berkas dilakukan, ketiga direktori di atas WAJIB tetap aman, utuh, dan terlindungi dari segala bentuk perintah penghapusan.


## Git & GitHub Version Control Standards (WAJIB DIPATUHI)
Setiap kali melakukan commit atau sinkronisasi ke repositori GitHub, agen **WAJIB** mematuhi protokol version control enterprise berikut:

**1. Standar Pesan Commit (Conventional Commits v1.0.0):**
Gunakan format terstruktur: `<type>(<scope>): <deskripsi singkat>`
- `feat`: Penambahan fitur baru, controller, model, atau modul baru (contoh: `feat(keuangan): implement payment webhook auto-settlement`).
- `fix`: Perbaikan bug, syntax error, atau celah keamanan (contoh: `fix(auth): support multi-hash password verification`).
- `refactor`: Perombakan struktur kode tanpa mengubah fungsionalitas (contoh: `refactor(models): align table prefix to postgresql 16 schemas`).
- `docs`: Pembaruan dokumentasi harian, walkthrough, dan pedoman agen (contoh: `docs: update daily walkthrough and agents rules`).
- `style`: Penyesuaian antarmuka, CSS, atau layout Vue/Tailwind (contoh: `style(layout): implement 3-way horizontal scroller navtabs`).
- `test`: Penambahan atau pembaruan Automated Feature Tests di `scratch/tests/` (contoh: `test(security): add tenant isolation feature test`).
- `chore`: Pemeliharaan dependensi, composer, package.json, atau build tools (contoh: `chore(deps): update composer dependencies and vite pipeline`).

**2. Prinsip Commit Atomik (Atomic Commit Per Module):**
- Dilarang keras menggabungkan perubahan lintas modul yang berbeda dalam satu commit tunggal raksasa.
- Pecah commit secara modular berdasarkan area perubahan: `Modules/Core`, `Modules/Siswa`, `Modules/Akademik`, `Modules/Keuangan`, `resources/js`, dll.

**3. Perlindungan Kredensial & Pencegahan Kebocoran Data (Zero Secret Leakage):**
- **DILARANG KERAS** melakukan commit pada berkas `.env`, kredensial database lokal/live, Midtrans Server Key, WhatsApp Token, SSL Private Keys, maupun session tokens.
- Pastikan `.gitignore` secara ketat mengecualikan: `.env`, `vendor/`, `node_modules/`, `storage/framework/`, `storage/logs/`, dan `.gemini/`.

**4. Protokol Sebelum Push (`git push origin main`):**
- Wajib menjalankan `php scratch/pengujian/verify_all_syntax.php` dan memastikan `0 Syntax Error`.
- Pastikan tidak ada konflik merge atau unstaged file yang tertinggal (`git status -s`).


## Standar Desain Tabel Data, Filter Bar & Layout Sidebar (WAJIB DIPATUHI)
Saat membuat atau merombak halaman yang menampilkan data tabular (daftar siswa, pengguna, karyawan, dll.), agen **WAJIB** mengikuti standar desain berikut tanpa pengecualian:

### 1. Struktur 3-Bagian Tabel (Standar Baku)
Setiap halaman data tabular wajib terdiri dari **3 bagian** yang menyatu dalam satu box card:
```
┌──────────────────────────────────────────────────────────────────┐
│  [FILTER BAR ATAS]  bg-white border-b                            │
│  Jenjang | Kelas/Rombel | Status | Pencarian | [Cari] [Reset]   │
├──────────────────────────────────────────────────────────────────┤
│  [TABEL DATA]  overflow-x-auto                                   │
│  Header kolom + baris data                                       │
├──────────────────────────────────────────────────────────────────┤
│  [FOOTER PAGINATION]  bg-slate-50/50 border-t                    │
│  Tampilkan [15▾] baris per halaman | 1 s.d. 15 dari N | ◀1 2 3▶ │
└──────────────────────────────────────────────────────────────────┘
```

### 2. Aturan Filter Bar Atas (WAJIB)
- Filter bar atas **HANYA** boleh berisi: filter kontekstual (Jenjang, Kelas, Status, dll.) + input Pencarian + tombol **[Cari]** dan **[Reset]**.
- **DILARANG KERAS** menaruh dropdown jumlah baris (`per_page` / label "BARIS") di filter bar atas.
- Container Filter Bar wajib menggunakan: `p-3.5 bg-slate-50/70 border-b border-slate-200/80 overflow-x-auto no-scrollbar` dengan form `flex flex-row items-end gap-2.5 sm:gap-3 min-w-max`.
- Label filter: `text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider whitespace-nowrap`.
- Input & Select filter: tinggi seragam `h-9`, `rounded-xl`, `border border-slate-200 hover:border-slate-300`, `focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition`.
- **Dimensi Proposional & Tidak Boleh Melebar Berlebihan**:
  - Dropdown filter: `w-36 sm:w-40 shrink-0` (Jenjang, Kelas), `w-32 sm:w-36 shrink-0` (Status).
  - Input Pencarian: `w-64 sm:w-72 md:w-80 shrink-0` (Dilarang keras memakai `grow` tanpa batas yang menyebabkan kolom pencarian molor/stretched).
  - Tombol clear `(x)` di sisi kanan dalam input pencarian (`v-if="searchQuery"`) untuk reset cepat kata kunci.
  - Tombol `[Cari]` (`bg-blue-600 hover:bg-blue-700 text-white`) dan `[Reset]` (`bg-white border border-slate-200`) ditempatkan tepat di samping kanan kolom pencarian.

```vue
<!-- Standar Baku: Filter Bar Atas Proposional & Responsif -->
<div class="p-3.5 bg-slate-50/70 border-b border-slate-200/80 overflow-x-auto no-scrollbar">
  <form @submit.prevent="applyFilters" class="flex flex-row items-end gap-2.5 sm:gap-3 min-w-max">
    
    <!-- Filter Kontekstual (Contoh: Jenjang & Kelas) -->
    <div class="w-36 sm:w-40 shrink-0" v-if="filterJenjang !== undefined">
      <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider whitespace-nowrap">Tingkat Jenjang</label>
      <select v-model="filterJenjang" @change="applyFilters" class="w-full h-9 px-3 rounded-xl border border-slate-200 hover:border-slate-300 bg-white text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition">
        <option value="">-- Semua Jenjang --</option>
        <option v-for="j in jenjangList" :key="j.id" :value="j.id">{{ j.nama }}</option>
      </select>
    </div>

    <!-- Search Input (Proposional w-64 s.d. w-80) -->
    <div class="w-64 sm:w-72 md:w-80 shrink-0">
      <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider whitespace-nowrap">Pencarian</label>
      <div class="relative">
        <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
        <input type="text" 
               v-model="searchQuery" 
               @input="handleSearchDebounce"
               placeholder="Cari data..." 
               class="w-full h-9 pl-8 pr-8 bg-white border border-slate-200 hover:border-slate-300 rounded-xl text-xs font-medium text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition shadow-2xs" />
        <button v-if="searchQuery" 
                @click="searchQuery = ''; applyFilters()" 
                type="button" 
                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition"
                title="Hapus Pencarian">
          <i class="bi bi-x-circle-fill text-xs"></i>
        </button>
      </div>
    </div>

    <!-- Tombol Cari & Reset -->
    <div class="flex items-center gap-1.5 shrink-0">
      <button type="submit" class="h-9 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-2xs transition flex items-center gap-1.5 whitespace-nowrap">
        <i class="bi bi-search text-xs"></i> <span>Cari</span>
      </button>
      <button type="button" @click="resetFilters" class="h-9 px-3.5 rounded-xl bg-white border border-slate-200 hover:bg-slate-100 text-slate-600 font-bold text-xs transition shadow-2xs whitespace-nowrap">
        Reset
      </button>
    </div>
  </form>
</div>
```

### 3. Aturan Footer Pagination (WAJIB)
- Footer pagination **WAJIB** mengandung dropdown jumlah baris per halaman (pilihan: 10, 15, 25, 50, 100).
- Dropdown `per_page` **HANYA** ditempatkan di footer bawah tabel, tidak di tempat lain.
- Format info pagination: *"Tampilkan **[N▾]** baris per halaman | Menampilkan **X** s.d. **Y** dari **Z** baris"*
- **Smart Windowed Pagination Helper (`getSmartPaginationLinks`)**: Wajib menampilkan maksimal 5-7 tombol nomor halaman terpusat (`1 ... 4 5 6 ... 9`) dan tombol panah chevron kompak (`<i class="bi bi-chevron-left text-xs"></i>` & `<i class="bi bi-chevron-right text-xs"></i>`) guna mencegah pagination melebar atau terpotong (*clipped*).
- Dimensi tombol navigasi: `min-w-[32px] h-8 px-2.5 rounded-xl`, halaman aktif = `bg-blue-600 text-white shadow-xs`.
- Footer layout: `flex flex-col md:flex-row justify-between items-center gap-3.5 p-4 bg-slate-50/50 border-t border-slate-200/80`.

```vue
<!-- Standar Baku: Footer Pagination Smart Windowing & Responsif -->
<div v-if="items?.total > 0" 
     class="flex flex-col md:flex-row justify-between items-center gap-3.5 p-4 bg-slate-50/50 border-t border-slate-200/80">
  <!-- Info Tampilkan Baris -->
  <div class="flex flex-wrap items-center justify-center md:justify-start gap-2 text-xs text-slate-500 font-medium shrink-0">
    <span>Tampilkan</span>
    <select v-model="perPage" @change="applyFilters" class="h-8 px-2 rounded-lg border border-slate-200 bg-white text-xs font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
      <option :value="10">10</option>
      <option :value="15">15</option>
      <option :value="25">25</option>
      <option :value="50">50</option>
      <option :value="100">100</option>
    </select>
    <span class="whitespace-nowrap">baris per halaman</span>
    <span class="text-slate-300 hidden sm:inline">|</span>
    <span class="whitespace-nowrap">
      Menampilkan <span class="font-bold text-slate-800">{{ items.from || 1 }}</span> s.d. <span class="font-bold text-slate-800">{{ items.to || items.total }}</span> dari <span class="font-bold text-slate-800">{{ items.total }}</span> baris
    </span>
  </div>

  <!-- Pagination Links (Smart Windowing & Compact Chevrons) -->
  <div class="flex items-center justify-center md:justify-end gap-1 shrink-0 flex-wrap">
    <template v-for="(link, i) in getSmartPaginationLinks(items)" :key="i">
      <button v-if="link.url && !link.active" 
              type="button"
              @click="goToPage(link.url)"
              class="min-w-[32px] h-8 px-2.5 rounded-xl text-xs font-bold transition flex items-center justify-center bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 shadow-2xs"
              :title="link.isPrev ? 'Halaman Sebelumnya' : (link.isNext ? 'Halaman Berikutnya' : 'Halaman ' + link.label)">
        <i v-if="link.isPrev" class="bi bi-chevron-left text-xs"></i>
        <i v-else-if="link.isNext" class="bi bi-chevron-right text-xs"></i>
        <span v-else>{{ link.label }}</span>
      </button>
      <span v-else-if="link.active"
            class="min-w-[32px] h-8 px-2.5 rounded-xl text-xs font-bold flex items-center justify-center bg-blue-600 text-white shadow-xs">
        <i v-if="link.isPrev" class="bi bi-chevron-left text-xs"></i>
        <i v-else-if="link.isNext" class="bi bi-chevron-right text-xs"></i>
        <span v-else>{{ link.label }}</span>
      </span>
      <span v-else 
            class="min-w-[32px] h-8 px-2 text-xs font-bold flex items-center justify-center text-slate-400">
        <i v-if="link.isPrev" class="bi bi-chevron-left text-xs text-slate-300"></i>
        <i v-else-if="link.isNext" class="bi bi-chevron-right text-xs text-slate-300"></i>
        <span v-else>{{ link.label }}</span>
      </span>
    </template>
  </div>
</div>
```

**Ketentuan Script Helper Pagination:**
```javascript
const getSmartPaginationLinks = (pagination) => {
  if (!pagination?.links || pagination.links.length === 0) return [];
  const rawLinks = pagination.links;
  const prevLink = rawLinks[0];
  const nextLink = rawLinks[rawLinks.length - 1];
  const pageLinks = rawLinks.slice(1, -1);
  const current = pagination.current_page || 1;
  const last = pagination.last_page || (pageLinks.length ? Number(pageLinks[pageLinks.length - 1].label) || 1 : 1);

  const result = [];
  result.push({ ...prevLink, isPrev: true, isNext: false, label: prevLink.label });

  if (last <= 7) {
    pageLinks.forEach(l => result.push({ ...l, isPrev: false, isNext: false, label: l.label }));
  } else {
    const pagesToShow = new Set([1, last]);
    for (let p = current - 1; p <= current + 1; p++) {
      if (p >= 1 && p <= last) pagesToShow.add(p);
    }
    const sortedPages = Array.from(pagesToShow).sort((a, b) => a - b);
    let prevPage = null;
    sortedPages.forEach(p => {
      if (prevPage !== null && p - prevPage > 1) {
        result.push({ label: '...', url: null, active: false, isPrev: false, isNext: false });
      }
      const foundRaw = pageLinks.find(l => l.label == p.toString());
      result.push({
        label: p.toString(),
        url: foundRaw ? foundRaw.url : null,
        active: p === current,
        isPrev: false,
        isNext: false,
      });
      prevPage = p;
    });
  }

  result.push({ ...nextLink, isPrev: false, isNext: true, label: nextLink.label });
  return result;
};
```

### 4. Standar Kolom Tabel (WAJIB)
- **Header kolom**: `text-[10px] font-black text-slate-500 uppercase tracking-wider bg-slate-50 border-b border-slate-200 px-4 py-3`.
- **Baris data**: `text-xs`, hover `hover:bg-blue-50/40 transition`, border bawah `border-b border-slate-100`.
- **Kolom NO**: lebar `w-10`, center-aligned, font-mono, isi = `(current_page-1)*per_page + idx + 1`.
- **Kolom Sekolah/Tenant**: wajib tampil untuk Super Admin dengan badge ikon berwarna per-modul.
- **Kolom Aksi**: sticky kanan (`sticky right-0 bg-white shadow-[-4px_0_6px_rgba(15,23,42,0.04)]`), berisi tombol Edit (biru) & Delete (merah).
- Seluruh tabel dibungkus `overflow-x-auto` agar bisa horizontal scroll di layar kecil.

### 5. Standar Layout Sidebar & Main Content (WAJIB)
- Root container wajib: `h-screen bg-slate-50 flex overflow-hidden` — **BUKAN** `min-h-screen`.
- **Sidebar** bersifat `h-screen flex-shrink-0` dengan scroll independen.
- Area navigasi sidebar: `flex-grow overflow-y-auto overscroll-contain` + `style="scrollbar-width: thin; scrollbar-color: #e2e8f0 transparent;"`.
- **Pemisah sidebar & konten**: `border-r-2 border-slate-200` + `shadow-[2px_0_8px_rgba(15,23,42,0.06)]`.
- **Main content area**: `flex-grow overflow-y-auto` — scroll sendiri, tidak mempengaruhi sidebar.
- Sidebar header brand & footer user wajib `shrink-0` agar tidak ikut discroll.
- Sidebar dan konten utama **DILARANG** saling mempengaruhi scroll satu sama lain.

```vue
<!-- Standar baku: layout sidebar + main scroll terpisah -->
<div class="h-screen bg-slate-50 flex overflow-hidden">

  <!-- Sidebar: scroll independen -->
  <aside class="h-screen flex-shrink-0 bg-white border-r-2 border-slate-200
                shadow-[2px_0_8px_rgba(15,23,42,0.06)] flex flex-col w-[270px]">
    <div class="h-16 shrink-0 border-b border-slate-100 ..."><!-- Brand --></div>
    <div class="flex-grow overflow-y-auto overscroll-contain px-3 py-4"
         style="scrollbar-width: thin; scrollbar-color: #e2e8f0 transparent;">
      <!-- Menu items -->
    </div>
    <div class="shrink-0 border-t border-slate-100 ..."><!-- Footer user --></div>
  </aside>

  <!-- Main: scroll independen dari sidebar -->
  <div class="flex-grow flex flex-col min-w-0 overflow-hidden">
    <header class="h-16 shrink-0 sticky top-0 z-30 bg-white border-b ..."></header>
    <main class="flex-grow overflow-y-auto p-4 sm:p-6 lg:p-8">
      <div class="max-w-7xl mx-auto space-y-6">
        <slot />
      </div>
    </main>
  </div>

</div>
```

## Standardisasi Desain Filter Sekolah / Tenant Banner (Khusus Super Admin - WAJIB)
Saat membuat halaman baru atau merombak halaman modul apa pun yang memiliki filter instansi sekolah khusus Super Admin, agen **WAJIB** menerapkan struktur desain baku banner Filter Sekolah persis seperti pada modul Manajemen Pengguna:

```vue
<!-- Filter Sekolah Banner (Legacy & Unified Design Standard) -->
<div v-if="isSuperAdmin" class="p-4 sm:px-5 rounded-2xl shadow-xs border border-blue-100 bg-gradient-to-r from-blue-50/90 to-slate-50 border-l-4 border-l-blue-600 flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
  <div class="flex flex-wrap items-center gap-2.5">
    <i class="bi bi-building text-blue-600 text-lg"></i>
    <span class="font-bold text-slate-800 text-sm">Filter Sekolah</span>
    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-2xs font-extrabold bg-blue-100 text-blue-700 border border-blue-200">
      <i class="bi bi-funnel-fill me-1"></i> Aktif
    </span>

    <!-- Dropdown Filter Sekolah (Khusus Super Admin) - Otomatis Terfilter Saat Dipilih (@change) -->
    <div class="my-1 md:my-0">
      <select v-model="selectedTenant" 
              @change="applyTenantFilter"
              class="h-9 px-3 bg-white border border-blue-200 rounded-xl text-xs font-semibold text-slate-800 shadow-2xs focus:outline-none focus:ring-2 focus:ring-blue-500/20 min-w-[220px]">
        <option value="">-- Semua Sekolah (Global) --</option>
        <option v-for="t in tenants" :key="t.id" :value="t.id">{{ t.nama_sekolah }}</option>
      </select>
    </div>
  </div>

  <!-- Informational Text -->
  <div class="text-xs text-slate-500 font-medium">
    Menampilkan data milik: 
    <strong class="text-blue-700 font-bold ml-1">
      {{ getSelectedTenantName() }}
    </strong>
  </div>
</div>
```

**Ketentuan Script Helper:**
```javascript
const getSelectedTenantName = () => {
    if (!selectedTenant.value) return 'Semua Sekolah Terdaftar (Super Admin)'
    const tenant = props.tenants?.find(t => t.id === selectedTenant.value)
    return tenant ? tenant.nama_sekolah : 'Semua Sekolah Terdaftar (Super Admin)'
}
```

## Standardisasi Desain Form Filter Card 2-Baris & Anti-Overflow (WAJIB DIPATUHI)
Saat merancang formulir filter atau parameter pencarian di dalam kartu (*card container*), agen **WAJIB** menerapkan struktur 2-baris responsif untuk mencegah elemen tombol terdesak keluar dari batas kontainer (*zero card overflow*):

**1. Larangan Mutlak Grid Overcrowding:**
- **DILARANG KERAS** memaksakan input pencarian panjang beserta multiple tombol aksi (`[Cari]`, `[Reset]`, `[Export]`) ke dalam satu baris grid sempit (misalnya `lg:grid-cols-6` dengan tombol ditaruh di `col-span-1`). Hal ini memicu tombol `[Reset]` terdorong keluar melewati batas kanan kontainer kartu.

**2. Standar Struktur Form Filter 2-Baris:**
```vue
<form @submit.prevent="applyFilters" class="space-y-3.5 pt-2">
  <!-- Baris 1: Parameter Dropdown / Filter (Grid Terstruktur 4 Kolom) -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
    <div>
      <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider">Parameter 1 <span class="text-rose-500">*</span></label>
      <select v-model="filter1" @change="applyFilters" class="w-full h-9 px-3 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
        <option value="">-- Pilih Parameter --</option>
        <option v-for="item in options" :key="item.id" :value="item.id">{{ item.nama }}</option>
      </select>
    </div>
    <!-- Field parameter lainnya... -->
  </div>

  <!-- Baris 2: Pencarian & Tombol Aksi (100% Contained & Responsive) -->
  <div class="flex flex-col sm:flex-row items-stretch sm:items-end gap-2.5">
    <!-- Input Pencarian (Mengisi Ruang Tersedia) -->
    <div class="grow">
      <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider">Pencarian Data</label>
      <div class="relative">
        <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
        <input type="text" 
               v-model="searchQuery" 
               @input="handleSearchDebounce"
               placeholder="Cari kata kunci..." 
               class="w-full h-9 pl-8 pr-8 bg-white border border-slate-200 rounded-xl text-xs font-medium text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition" />
        <button v-if="searchQuery" 
                type="button" 
                @click="searchQuery = ''; applyFilters()" 
                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition"
                title="Hapus pencarian">
          <i class="bi bi-x-circle-fill text-xs"></i>
        </button>
      </div>
    </div>

    <!-- Tombol Aksi (Cari & Reset dengan Lebar Minimum Pasti) -->
    <div class="flex items-center gap-2 shrink-0">
      <button type="submit" class="h-9 px-5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-2xs transition flex items-center justify-center gap-1.5 min-w-[80px]">
        <i class="bi bi-search"></i> Cari
      </button>
      <button type="button" @click="resetFilters" class="h-9 px-4 rounded-xl bg-white border border-slate-200 hover:bg-slate-100 text-slate-600 font-bold text-xs shadow-2xs transition min-w-[70px]">
        Reset
      </button>
    </div>
  </div>
</form>
```


## Larangan Redundansi Dropdown Sekolah & Tenant Isolation Scoping (WAJIB DIPATUHI)

**1. Larangan Dropdown Sekolah Ganda:**
- Jika pada bagian atas halaman sudah tersedia **Banner Filter Sekolah Global** (khusus Super Admin), sub-panel, sub-kartu, atau formulir di bawahnya **DILARANG KERAS** menambahkan dropdown pilihan `Instansi Sekolah` duplikat.

**2. Dynamic Tenant Scoping pada Fitur Operasional:**
- Seluruh opsi dropdown relasional (seperti *Rombel / Kelas Asal*, *Rombel / Kelas Tujuan*, *Tahun Ajaran Target*, dan *Daftar Siswa*) pada fitur operasional (seperti Kenaikan Kelas, Mutasi, Buku Induk, Profile Rapot, PDSS) **WAJIB** tersaring secara dinamis mengikuti tenant sekolah yang sedang aktif/dipilih (`tenant_id`).
- Super Admin yang mengganti pilihan sekolah pada banner global wajib secara otomatis memperbarui (*re-scope*) opsi kelas dan siswa yang tersedia tanpa kebocoran data sekolah lain (*Zero Cross-Tenant Leakage*).

## Standardisasi Unduhan & Ekspor Berkas Excel Wajib Format Murni .XLSX (WAJIB DIPATUHI)
Saat membuat fitur ekspor data, unduh template, cetak laporan spreadsheet, atau impor massal di seluruh modul SINTA, agen **WAJIB** menerapkan standar format berikut tanpa pengecualian:

**1. Format Berkas & Ekstensi Resmi (.xlsx):**
- Seluruh unduhan berkas yang berorientasi Excel **WAJIB** berformat biner murni **`.xlsx`** (*Office Open XML Spreadsheet*). Dilarang keras mengeluarkan file `.csv` mentah sebagai output utama ekspor Excel.
- Gunakan generator spreadsheet berkecepatan tinggi `\Shuchkin\SimpleXLSXGen::fromArray($dataRows)` atau PhpSpreadsheet.

**2. Standar Respon Controller & Header HTTP:**
- Controller wajib mengembalikan respon biner `.xlsx` dengan header resmi:
  ```php
  $xlsx = \Shuchkin\SimpleXLSXGen::fromArray($dataRows);
  return response((string) $xlsx, 200, [
      'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      'Content-Disposition' => "attachment; filename=\"{$filename}.xlsx\"",
      'Cache-Control'       => 'max-age=0',
  ]);
  ```

**3. Standar Parser Impor Massal (.xlsx & .csv Fallback):**
- Handler impor berkas wajib membaca format `.xlsx` secara native menggunakan `\Shuchkin\SimpleXLSX::parse($filePath)` dengan toleransi fallback otomatis ke `.csv`:
  ```php
  $rows = [];
  if (in_array($ext, ['xlsx', 'xls']) || ($parsed = \Shuchkin\SimpleXLSX::parse($realPath))) {
      if (isset($parsed) && $parsed) {
          $rows = $parsed->rows();
      }
  }
  // Fallback to CSV parser if not XLSX
  ```

**4. Standar Antarmuka Pengguna (Vue 3 / Inertia):**
- Tombol aksi ekspor dan unduh template pada antarmuka wajib mencantumkan ekstensi `(.XLSX)` secara transparan (contoh: `Ekspor Excel (.XLSX)`, `Unduh Format Nilai (.XLSX)`).
- Input unggah berkas wajib menyertakan atribut: `accept=".xlsx, .xls, .csv"`.

## Standar Modal Tabel Riwayat & Status Badge Anti-Wrapping (WAJIB DIPATUHI)
Saat menampilkan tabel riwayat, log aktivitas, atau mutasi di dalam komponen dialog/modal, agen **WAJIB** menerapkan standar antarmuka berikut:
- **Lebar Modal**: Gunakan minimal `max-w-3xl` atau `max-w-4xl` untuk modal yang memuat lebih dari 5 kolom data.
- **Kontainer Tabel**: Wajib dibungkus dengan `<div class="overflow-x-auto border border-slate-200/80 rounded-2xl shadow-2xs">` dan `<table class="w-full text-left text-xs whitespace-nowrap min-w-[650px]">`.
- **Kolom Status & Badge**:
  - Kolom status wajib `whitespace-nowrap min-w-[120px] text-center`.
  - Format markup badge status:
    ```vue
    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-2xs font-extrabold whitespace-nowrap"
          :class="item.status === 'naik' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200'">
      <i class="bi bi-arrow-up-circle-fill"></i> Naik Kelas
    </span>
    ```
- Mencegah teks badge terhimpit (*squeezed*), turun baris (*wrapped*), atau terpotong pada viewport desktop maupun tablet.


## Standarisasi Routing Modular & Anti-Dashboard Bouncing Trap (WAJIB DIPATUHI)
Saat membuat modul baru, memodifikasi rute controller, atau menangani aksi mutasi data (POST / PUT / PATCH / DELETE) di seluruh modul SINTA, agen **WAJIB** menerapkan standar routing dan penanganan redirect berikut:

### 1. Struktur Standar File `Modules/[NamaModul]/Routes/web.php`
Seluruh rute web modul **WAJIB** dibungkus menggunakan middleware group `'web'` di tingkat paling luar sebelum middleware autentikasi dan isolasi tenant:

```php
<?php
use Illuminate\Support\Facades\Route;
use Modules\[NamaModul]\Http\Controllers\[NamaModul]Controller;

Route::middleware(['web', 'auth', 'tenant.guard'])->group(function () {
    // 1. Rute Navigasi View Halaman (Inertia)
    Route::get('/[modul-path]', [[NamaModul]Controller::class, 'index'])->name('[modul].index');
    
    // 2. Rute Mutasi Data (POST / PUT / DELETE)
    Route::post('/[modul-path]/simpan', [[NamaModul]Controller::class, 'store'])->name('[modul].store');
    Route::put('/[modul-path]/{id}', [[NamaModul]Controller::class, 'update'])->name('[modul].update');
    Route::delete('/[modul-path]/{id}', [[NamaModul]Controller::class, 'destroy'])->name('[modul].destroy');
});
```

### 2. Mekanisme Anti-Session Drop & Anti-Dashboard Redirect Trap
- **Akar Masalah di Laravel 11**: Rute modular yang di-load via `$this->loadRoutesFrom()` tidak otomatis mewarisi middleware `'web'`. Jika middleware `'web'` absen, `StartSession` tidak aktif sehingga request mutasi dianggap unauthenticated (`Auth::user() = null`), memicu lemparan `AuthenticationException` $\rightarrow$ redirect `/login` $\rightarrow$ ditangkap oleh middleware `guest` $\rightarrow$ memantul ke `/dashboard`.
- **Standar Solusi**: Pastikan `'web'` selalu hadir dalam array middleware setiap rute modul.

### 3. Standar Redirection Fallback di Controller
- Hindari pemanggilan bare `return back()` tanpa jaminan ketersediaan header Referer.
- Gunakan redirect eksplisit dengan fallback route/path yang aman:

```php
/**
 * Helper redirect aman untuk menjaga navigasi tetap di halaman modul asal.
 */
protected function redirectTarget(Request $request, string $status, string $message, string $fallbackPath): RedirectResponse
{
    $referer = $request->header('referer');
    if (!empty($referer) && filter_var($referer, FILTER_VALIDATE_URL)) {
        return back()->with($status, $message);
    }
    return redirect($fallbackPath)->with($status, $message);
}
```


## Standardisasi Desain UI/UX Reusable SearchableSelect & Live Filter Dropdown (WAJIB UNIVERSAL DIPATUHI)
Saat membuat fitur baru, halaman baru, modal dialog, formulir input, atau filter tabel di **SELURUH MODUL APLIKASI SINTA (baik 16 modul yang sudah ada maupun SELURUH modul dan fitur baru yang akan dibangun di masa mendatang)**, agen **WAJIB MUTLAK** menggunakan komponen **`SearchableSelect.vue`** (`resources/js/Components/SearchableSelect.vue`) pada setiap dropdown pilihan.

**🚫 LARANGAN MUTLAK (ANTI-NATIVE SELECT RULE):**
- **DILARANG KERAS** menggunakan tag HTML native `<select>` standar tanpa fitur pencarian.
- Setiap elemen dropdown **WAJIB** memiliki kotak pencarian instan (*live keyword search*), navigasi keyboard, dan auto-focus agar memudahkan pengguna dalam mencari dan memilih opsi ribuan data dengan cepat.

### 1. Fitur & Keunggulan Komponen `SearchableSelect.vue`:
1. **Live Keyword Search (Pencarian Instan)**:
   - Kotak input pencarian otomatis muncul di bagian atas daftar pilihan saat dropdown dibuka.
   - Menyaring data secara real-time saat pengguna mengetik, baik mencocokkan **Label Utama** (nama kategori, nama kelas, nama mapel, role, nama siswa, dll.) maupun **Sub-Label / Keterangan** (SLA, jenjang, kode jurusan, wali kelas, NISN, dll.).
2. **Dukungan Penuh Navigasi Keyboard**:
   - **Panah Atas / Bawah (`↑` / `↓`)**: Menyorot (*highlight*) pilihan berikutnya/sebelumnya.
   - **`Enter`**: Memilih opsi yang sedang disorot.
   - **`Escape (Esc)`**: Menutup popup dropdown seketika.
3. **Auto-Focus Cerdas**:
   - Kursor otomatis fokus pada kotak pencarian saat dropdown dibuka, sehingga pengguna dapat langsung mengetik tanpa klik kedua kali.
4. **Tombol Reset / Clear Selection (`allowClear`)**:
   - Dilengkapi tombol silang `(X)` untuk mereset pilihan kembali ke kondisi default/kosong.
5. **Penutupan Otomatis (*Click Outside Handler*)**:
   - Popup otomatis tertutup jika pengguna mengklik area di luar kontrol dropdown.

### 2. Standar Penggunaan Komponen di Halaman Vue 3 (Inertia SFC):
```vue
<script setup>
import SearchableSelect from '@/Components/SearchableSelect.vue'
import { ref } from 'vue'

// Data opsi bisa berupa Array of Objects atau Array Primitif (String/Number)
const opsiKelas = [
  { id: 'X-IPA-1', label: 'Kelas X IPA 1', subLabel: 'Wali Kelas: Budi Santoso, S.Pd' },
  { id: 'X-IPA-2', label: 'Kelas X IPA 2', subLabel: 'Wali Kelas: Siti Aminah, M.Pd' },
  { id: 'XI-IPS-1', label: 'Kelas XI IPS 1', subLabel: 'Wali Kelas: Bambang, S.Kom' },
]

const kelasTerpilih = ref('')
</script>

<template>
  <div>
    <label class="block text-xs font-bold text-slate-700 mb-1">Pilih Kelas / Rombel</label>
    <SearchableSelect
      v-model="kelasTerpilih"
      :options="opsiKelas"
      placeholder="-- Pilih Kelas Siswa --"
      search-placeholder="Ketik nama kelas atau nama wali kelas..."
      :allow-clear="true"
    />
  </div>
</template>
```

### 3. Matriks Kewajiban Universal di Seluruh Modul (Eksisting & Fitur Masa Depan):
Seluruh dropdown di bawah ini dan seluruh fitur baru yang akan dikembangkan **WAJIB** menerapkan `SearchableSelect`:
- **Modul Keuangan**: Pemilihan Siswa, Kelas/Rombel, Pos Pembayaran, Jenis Tarif, Rekening Kas/Bank, Keringanan/Beasiswa, Metode Pembayaran, Filter Bulan/Tahun.
- **Modul Siswa / Buku Induk**: Pemilihan Kelas (Rombel), Jurusan/Program Keahlian, Agama, Jalur Masuk, Status Siswa, Filter Angkatan.
- **Modul Akademik**: Pemilihan Tahun Ajaran, Semester, Mata Pelajaran, Guru Pengampu, Kurikulum, Jenis Penilaian, Kategori Ekskul.
- **Modul Perpustakaan**: Pemilihan Kategori DDC, Lokasi Rak Buku, Jenis Koleksi, Status Sirkulasi, Anggota Peminjam.
- **Modul Bimbingan Konseling (BK)**: Pemilihan Siswa, Jenis Pelanggaran, Bentuk Pembinaan, Guru BK, Status Penanganan.
- **Modul Absensi / Presensi**: Pemilihan Kelas, Sesi Jadwal, Status Kehadiran (*Hadir, Izin, Sakit, Alpa*).
- **Modul Kepegawaian & GTK**: Pemilihan Jabatan, Status Kepegawaian, Unit Kerja, Jenis Supervisi.
- **Modul Persuratan**: Pemilihan Kode Klasifikasi Surat, Sifat Surat, Instansi Pengirim, Penerima Disposisi.
- **Modul Sarana & Prasarana (Sarpras)**: Pemilihan Ruangan/Gedung, Kategori Barang Modal, Kondisi Aset, Penanggung Jawab.
- **Modul Kesiswaan**: Pemilihan Ekskul, Pembina, Siswa Anggota, Tingkat Prestasi.
- **Modul PDSS & SNPMB**: Pemilihan Kampus/PTN, Program Studi SNBP/SNBT, Jalur Masuk.
- **Modul Tracer Study**: Pemilihan Status Alumni, Kampus Kuliah, Bidang Industri Kerja.
- **Modul SMK & PKL**: Pemilihan Mitra DUDI, Guru Pembimbing PKL, Skema Sertifikasi UKK.
- **Modul CMS Portal Sekolah**: Pemilihan Kategori Berita/Artikel, Visibilitas Galeri, Banner Section.
- **Modul Sistem & Keamanan**: Pemilihan User Role, Tenant/Sekolah, Scope Permission, Filter Log.
- **Modul Core & Bantuan**: Pemilihan Kategori Tiket SLA, Modul Terkait (16 Modul), Urgensi Bisnis, Template Canned Responses.
- **SELURUH MODUL & FITUR BARU LAINNYA**: Wajib menggunakan `SearchableSelect` tanpa pengecualian!


## Standardisasi Struktur Menu Sidebar & RBAC Multi-Context (Berbasis Tupoksi Divisi Sekolah & SaaS Platform)
Saat membangun, memodifikasi, atau melakukan seeding menu sidebar dan hak akses role, agen **WAJIB** mematuhi pembagian 3 Kelompok Konteks berikut secara konsisten:

### 1. Context A: Super Admin (Platform Owner & SaaS Master Overlord)
Super Admin memiliki akses 'God-Mode' yang mencakup manajemen platform SaaS di posisi teratas serta inspeksi operasional sekolah:
- **Manajemen Platform SaaS**:
  - `Dashboard Platform` (`/super-admin/dashboard`)
  - `Kelola Sekolah & Tenant`: `Daftar Sekolah & Paket` (`/super-admin/tenants`), `Akses Fitur Sekolah` (`/super-admin/tenant-menus`)
  - `CMS Landing & Promosi` (`/super-admin/cms-promosi`)
- **Server, Keamanan & Health Monitor**:
  - `Server Monitor` (`/super-admin/server-monitor`)
  - `Error & Exception Log` (`/super-admin/error-monitor`)
  - `Antrean Queue Worker` (`/utilitas/antrean`)
  - `Sesi Aktif Real-Time` (`/utilitas/sesi-aktif`)
  - `Audit Trail & Log Global` (`/utilitas/log-aktivitas`)
- **Inspeksi Operasional Sekolah**: Memiliki hak akses penuh ke modul-modul Context B untuk supervisi teknis.

### 2. Context B: Operasional Sekolah (Tupoksi Divisi & Unit Kerja Riil)
Struktur menu sekolah dikelompokkan secara presisi berdasarkan tanggung jawab unit kerja:
- **Dashboard & Informasi**:
  - `Dashboard Sekolah` (`/admin/dashboard` atau `/guru/dashboard`)
  - `Pengumuman Sekolah` (`/informasi/pengumuman`)
  - `Agenda & Timeline` (`/informasi/agenda`)
- **Tata Usaha & Administrasi (TU)** *(Menangani Data Pokok, Persuratan & Kepegawaian)*:
  - `Calon Siswa PPDB` (`/ppdb/calon-siswa`)
  - `Verifikasi PPDB` (`/ppdb/verifikasi`)
  - `Buku Induk Siswa` (`/buku-induk`)
  - `Data GTK & Kepegawaian` (`/kepegawaian`)
  - `Surat Masuk` (`/persuratan/surat-masuk`)
  - `Surat Keluar` (`/persuratan/surat-keluar`)
  - `Pemindai Dokumen (AeroScan)` (`/utilitas/pemindai-dokumen`)
  - `Profil & Identitas Sekolah` (`/sekolah/identitas`)
- **Akademik & Kurikulum** *(Fokus KBM & Capaian Belajar)*:
  - `Master Data Akademik` (`/master-data`)
  - `Jadwal Pelajaran` (`/akademik/jadwal`)
  - `Presensi & Kehadiran` (`/absensi`)
  - `Rapor & Penilaian` (`/akademik/rapor`)
- **Bimbingan Konseling (BK) & Karir** *(Menangani Konseling, Disiplin & Studi Lanjut PTN)*:
  - `Layanan Konseling Siswa` (`/bk/layanan`)
  - `Kedisiplinan & Pelanggaran` (`/bk/kedisiplinan`)
  - `PDSS & Peluang PTN` (`/akademik/pdss`)
  - `Tracer Study Alumni` (`/alumni/tracer-study`)
- **Kesiswaan & Pembinaan**:
  - `Ekstrakurikuler & Prestasi` (`/kesiswaan/ekskul`)
- **Keuangan & Billing SPP**:
  - `Dashboard Keuangan` (`/keuangan/dashboard`)
  - `Kasir & Pembayaran SPP` (`/keuangan/kasir`)
  - `Tagihan & Billing Siswa` (`/keuangan/tagihan`)
  - `Pos Tarif & Master Biaya` (`/keuangan/master`)
  - `Laporan Keuangan` (`/keuangan/laporan`)
  - `Audit Log Transaksi` (`/keuangan/audit-log`)
- **Sarana, Prasarana & Perpustakaan**:
  - `Sarana & Prasarana (Sarpras)` (`/sarpras`)
  - `Katalog Buku (INLISLite)` (`/perpustakaan/katalog`)
  - `Sirkulasi Peminjaman` (`/perpustakaan/sirkulasi`)
  - `Anggota Perpustakaan` (`/perpustakaan/anggota`)
  - `OPAC Perpustakaan` (`/perpustakaan/opac`)
- **Supervisi & Manajemen Sistem**:
  - `Pembinaan Guru (Kepsek)` (`/kepala-sekolah/pembinaan`)
  - `Survei Kinerja Guru` (`/kepala-sekolah/survei-guru`)
  - `Manajemen Pengguna` (`/pengguna`)
  - `Konfigurasi Hak Akses` (`/konfigurasi/akses`)
  - `Pusat Bantuan` (`/bantuan`)
  - `Langganan & Billing SaaS` (`/sekolah/billing`)
- **Khusus SMK / Vokasi** *(Opsional)*:
  - `Vokasi & Kemitraan SMK` (`/smk`)

### 3. Context C: Portal Mandiri (Siswa & Orang Tua)
Terisolasi murni hanya untuk kebutuhan self-service peserta didik dan wali:
- `Dashboard Siswa` (`/siswa/dashboard`)
- `Presensi Mandiri GPS` (`/absensi/mandiri`)
- `Tagihan & SPP Saya` (`/keuangan/tagihan-saya`)
- `Rapor & Nilai Saya` (`/akademik/rapor-saya`)
- `Perpustakaan Saya` (`/perpustakaan/riwayat-saya`)
- `Konseling Saya` (`/bk/konseling-saya`)


## 🏢 Matriks Struktur Hirarki Menu Aktif (Tree Hierarchy - 63 Menus)
Struktur hirarki resmi menu sidebar SINTA terdiri dari **19 Parent Nodes** dan **44 Submenu Items** (Total 63 items) tanpa redundansi:

```text
├── 1. [SINGLE] Dashboard Platform                -> /super-admin/dashboard (Ikon: bi bi-speedometer2)
├── 2. [SINGLE] Dashboard Siswa                   -> /siswa/dashboard (Ikon: bi bi-speedometer2)
├── 3. [GROUP ] Kelola Sekolah & Tenant           (Ikon: bi bi-buildings)
│   ├── 3.1 Daftar Sekolah & Paket                -> /super-admin/tenants (Ikon: bi bi-building-gear)
│   └── 3.2 Akses Fitur Sekolah                   -> /super-admin/tenant-menus (Ikon: bi bi-toggles)
├── 4. [SINGLE] Presensi Mandiri GPS              -> /absensi/mandiri (Ikon: bi bi-geo-alt-fill)
├── 5. [SINGLE] CMS Landing & Promosi             -> /super-admin/cms-promosi (Ikon: bi bi-layout-text-window-reverse)
├── 6. [SINGLE] Tagihan & SPP Saya                -> /keuangan/tagihan-saya (Ikon: bi bi-wallet2)
├── 7. [GROUP ] Server & Health Monitor           (Ikon: bi bi-hdd-network)
│   ├── 7.1 Server Monitor                        -> /super-admin/server-monitor (Ikon: bi bi-cpu)
│   ├── 7.2 Error & Exception Log                 -> /super-admin/error-monitor (Ikon: bi bi-shield-exclamation)
│   ├── 7.3 Antrean Queue Worker                  -> /utilitas/antrean (Ikon: bi bi-arrow-repeat)
│   ├── 7.4 Sesi Aktif Real-Time                  -> /utilitas/sesi-aktif (Ikon: bi bi-activity)
│   └── 7.5 Audit Trail & Log Global              -> /utilitas/log-aktivitas (Ikon: bi bi-journal-text)
├── 8. [SINGLE] Rapor & Nilai Saya                -> /akademik/rapor-saya (Ikon: bi bi-award-fill)
├── 9. [SINGLE] Perpustakaan Saya                 -> /perpustakaan/riwayat-saya (Ikon: bi bi-book)
├── 10. [SINGLE] Konseling Saya                   -> /bk/konseling-saya (Ikon: bi bi-chat-heart)
├── 11. [GROUP ] Dashboard & Informasi            (Ikon: bi bi-grid-1x2-fill)
│   ├── 11.1 Dashboard Sekolah                    -> /admin/dashboard (Ikon: bi bi-speedometer2)
│   ├── 11.2 Pengumuman Sekolah                   -> /informasi/pengumuman (Ikon: bi bi-megaphone)
│   └── 11.3 Agenda & Timeline                    -> /informasi/agenda (Ikon: bi bi-calendar-event)
├── 12. [GROUP ] Tata Usaha & Administrasi        (Ikon: bi bi-folder2-open)
│   ├── 12.1 Calon Siswa PPDB                     -> /ppdb/calon-siswa (Ikon: bi bi-person-plus)
│   ├── 12.2 Verifikasi PPDB                      -> /ppdb/verifikasi (Ikon: bi bi-patch-check)
│   ├── 12.3 Buku Induk Siswa                     -> /buku-induk (Ikon: bi bi-journal-text)
│   ├── 12.4 Data GTK & Kepegawaian               -> /kepegawaian (Ikon: bi bi-person-workspace)
│   ├── 12.5 Surat Masuk                          -> /persuratan/surat-masuk (Ikon: bi bi-inbox)
│   ├── 12.6 Surat Keluar                         -> /persuratan/surat-keluar (Ikon: bi bi-send)
│   ├── 12.7 Pemindai Dokumen (AeroScan)          -> /utilitas/pemindai-dokumen (Ikon: bi bi-scanner)
│   └── 12.8 Profil & Identitas Sekolah           -> /sekolah/identitas (Ikon: bi bi-building)
├── 13. [GROUP ] Akademik & Kurikulum             (Ikon: bi bi-mortarboard)
│   ├── 13.1 Master Data Akademik                 -> /master-data (Ikon: bi bi-diagram-3)
│   ├── 13.2 Jadwal Pelajaran                     -> /akademik/jadwal (Ikon: bi bi-calendar3)
│   ├── 13.3 Presensi & Kehadiran                 -> /absensi (Ikon: bi bi-calendar-check)
│   └── 13.4 Rapor & Penilaian                    -> /akademik/rapor (Ikon: bi bi-file-earmark-spreadsheet)
├── 14. [GROUP ] Bimbingan Konseling & Karir      (Ikon: bi bi-shield-heart)
│   ├── 14.1 Layanan Konseling Siswa              -> /bk/layanan (Ikon: bi bi-chat-heart)
│   ├── 14.2 Kedisiplinan & Pelanggaran           -> /bk/kedisiplinan (Ikon: bi bi-shield-slash)
│   ├── 14.3 PDSS & Peluang PTN                   -> /akademik/pdss (Ikon: bi bi-graph-up-arrow)
│   └── 14.4 Tracer Study Alumni                  -> /alumni/tracer-study (Ikon: bi bi-mortarboard)
├── 15. [GROUP ] Kesiswaan & Pembinaan            (Ikon: bi bi-trophy)
│   └── 15.1 Ekstrakurikuler & Prestasi           -> /kesiswaan/ekskul (Ikon: bi bi-award)
├── 16. [GROUP ] Keuangan & Billing SPP           (Ikon: bi bi-cash-stack)
│   ├── 16.1 Dashboard Keuangan                   -> /keuangan/dashboard (Ikon: bi bi-pie-chart)
│   ├── 16.2 Kasir & Pembayaran SPP               -> /keuangan/kasir (Ikon: bi bi-calculator)
│   ├── 16.3 Tagihan & Billing Siswa              -> /keuangan/tagihan (Ikon: bi bi-receipt-cutoff)
│   ├── 16.4 Pos Tarif & Master Biaya             -> /keuangan/master (Ikon: bi bi-tags)
│   ├── 16.5 Laporan Keuangan                     -> /keuangan/laporan (Ikon: bi bi-file-earmark-bar-graph)
│   └── 16.6 Audit Log Transaksi                  -> /keuangan/audit-log (Ikon: bi bi-shield-check)
├── 17. [GROUP ] Sarana, Prasarana & Perpus       (Ikon: bi bi-box-seam)
│   ├── 17.1 Sarana & Prasarana (Sarpras)         -> /sarpras (Ikon: bi bi-buildings)
│   ├── 17.2 Katalog Buku (INLISLite)             -> /perpustakaan/katalog (Ikon: bi bi-journal-album)
│   ├── 17.3 Sirkulasi Peminjaman                 -> /perpustakaan/sirkulasi (Ikon: bi bi-arrow-left-right)
│   ├── 17.4 Anggota Perpustakaan                 -> /perpustakaan/anggota (Ikon: bi bi-person-badge)
│   └── 17.5 OPAC Perpustakaan                    -> /perpustakaan/opac (Ikon: bi bi-search)
├── 18. [GROUP ] Supervisi & Manajemen Sistem     (Ikon: bi bi-gear-wide-connected)
│   ├── 18.1 Pembinaan Guru (Kepsek)              -> /kepala-sekolah/pembinaan (Ikon: bi bi-person-lines-fill)
│   ├── 18.2 Survei Kinerja Guru                  -> /kepala-sekolah/survei-guru (Ikon: bi bi-clipboard2-pulse)
│   ├── 18.3 Manajemen Pengguna                   -> /pengguna (Ikon: bi bi-people)
│   ├── 18.4 Konfigurasi Hak Akses                -> /konfigurasi/akses (Ikon: bi bi-shield-lock)
│   ├── 18.5 Pusat Bantuan                        -> /bantuan (Ikon: bi bi-question-circle)
│   └── 18.6 Langganan & Billing SaaS             -> /sekolah/billing (Ikon: bi bi-credit-card-2-front)
└── 19. [SINGLE] Vokasi & Kemitraan SMK           -> /smk (Ikon: bi bi-wrench-adjustable)
```


## 🛡️ Distribusi Menu Berdasarkan 21 Peran Pengguna (RBAC Matrix)

| No | Peran Pengguna (*Role*) | Total Menu Aktif | Deskripsi Tupoksi & Konteks |
|:--:|---|:---:|---|
| 1 | `super_admin` | **47 menu** | **Platform Overlord**: Seluruh menu platform SaaS master & modul operasional sekolah |
| 2 | `admin_sekolah` / `admin` | **38 menu** | **Admin Sekolah**: Seluruh manajemen operasional sekolah, tata usaha & sistem |
| 3 | `kepala_sekolah` | **37 menu** | **Kepala Sekolah**: Supervisi akademik, pembinaan GTK & seluruh divisi sekolah |
| 4 | `operator_sekolah` | **23 menu** | **Operator**: Data pokok PPDB, Buku Induk, Dapodik, GTK, Sarpras & Perpus |
| 5 | `staf_tu` | **15 menu** | **Tata Usaha**: Buku Induk, PPDB, GTK, Persuratan, AeroScan, Profil Sekolah |
| 6 | `guru` | **12 menu** | **Tenaga Pengajar**: Jadwal KBM, Presensi, Nilai Rapor, Sarpras, Perpus, Survei |
| 7 | `wali_kelas` | **11 menu** | **Wali Kelas**: Presensi rombel, Rapor, Buku Induk, Disiplin Siswa, BK |
| 8 | `keuangan` | **10 menu** | **Bendahara**: Dashboard Keuangan, Kasir POS, Tagihan, Pos Tarif, Laporan, Audit Log |
| 9 | `kurikulum` | **10 menu** | **Kurikulum**: Master Data Akademik, Jadwal Pelajaran, Rapor, PDSS PTN |
| 10 | `kesiswaan` | **9 menu** | **Kesiswaan**: Ekstrakurikuler, Prestasi Siswa, PPDB, Tracer Study Alumni |
| 11 | `staf_keuangan` | **9 menu** | **Kasir Keuangan**: Kasir POS SPP, Tagihan Siswa, Pos Tarif, Laporan Keuangan |
| 12 | `bk` / `guru_bk` | **7 menu** | **Bimbingan Konseling**: Layanan Konseling, Kedisiplinan, PDSS SNBP, Tracer Study |
| 13 | `perpustakaan` | **7 menu** | **Pengelola Perpus**: Katalog MARC21, Sirkulasi Barcode, Anggota, OPAC |
| 14 | `sarpras` | **4 menu** | **Pengelola Sarpras**: Aset Inventaris Ruangan, KIR, Pemeliharaan |
| 15 | `pembina_ekskul` | **4 menu** | **Pembina Ekskul**: Manajemen Ekstrakurikuler, Anggota & Prestasi Lomba |
| 16 | `humas` | **3 menu** | **Humas**: Dashboard Sekolah, Pengumuman, Agenda & Timeline |
| 17 | `karyawan` | **3 menu** | **Karyawan/Staf**: Dashboard Sekolah, Pengumuman, Agenda & Timeline |
| 18 | `siswa` | **6 menu** | **Portal Siswa**: Dashboard Siswa, Presensi GPS, SPP Saya, Rapor, Perpus, BK |
| 19 | `orang_tua` | **6 menu** | **Portal Wali**: Dashboard Siswa, Presensi GPS, SPP Saya, Rapor, Perpus, BK |


## 💳 Sistem Langganan SaaS, Real-Time Countdown Timer & Lockout Automations
Agen **WAJIB** menerapkan ketentuan berikut pada modul Billing & Langganan SaaS:
1. **Widget Countdown Real-Time**:
   - Tampil pada Dashboard Admin Sekolah (`/admin/dashboard` & `/dashboard`).
   - Menghitung mundur sisa masa aktif per detik (`Hari`, `Jam`, `Menit`, `Detik`).
   - 3 Indikator warna dinamis:
     * 🟢 **Hijau**: Sisa waktu $> 30$ hari.
     * 🟡 **Kuning/Orange**: Sisa waktu $\le 30$ hari (Peringatan masa tenggang).
     * 🔴 **Merah**: Sisa waktu $\le 10$ hari (Kritis & Tagihan Terbit).
2. **Auto-Invoice Generation H-10**:
   - Tagihan perpanjangan otomatis terbit saat sisa masa aktif $\le 10$ hari.
   - Sediakan tombol download invoice PDF resmi (`/sekolah/billing/invoice/{id}/download`).
3. **Lockout Guard & Penguncian Massal**:
   - Middleware `TenantSubscriptionLockoutGuard` otomatis aktif saat `subscription_expires_at < now()`.
   - Mengalihkan seluruh user sekolah ke halaman lockout khusus (`/subscription-expired`) tanpa menampilkan menu sidebar atau navbar operasional sekolah.
   - Hak akses Super Admin (`super_admin`) **TETAP MEMILIKI BYPASS MUTLAK** untuk pengelolaan maintenance atau perpanjangan manual.
4. **Daily Console Automation**:
   - Jalankan schedule harian `sinta:check-subscriptions` pada `routes/console.php` pukul 00:05 WIB.





