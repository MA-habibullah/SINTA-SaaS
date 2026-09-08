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

**2. Pendaftaran Modul & Service Provider di Laravel 11 (`bootstrap/providers.php`):**
- Di Laravel 11, Service Provider didaftarkan secara eksplisit di `bootstrap/providers.php` (bukan di `config/app.php`).
- Service Provider modul wajib me-load routing modul melalui `$this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');`.

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
- **Pencegahan Kebocoran Kredensial**: Hapus data sensitif (hash password, token, session keys) di sisi server sebelum dikirimkan ke frontend props/JSON.
- **Dukungan Password Hashing Multi-Format**: Gunakan `password_verify($password, $user->password_hash)` agar kompatibel secara transparan dengan hash lama (Argon2id) maupun baru (Bcrypt).
- **Anti-XSS & Anti-Injection**: Gunakan Eloquent Parameter Binding atau Prepared Statements. Dilarang menggabungkan raw SQL string secara langsung.
- **CSRF Protection**: Seluruh mutasi data via HTTP POST, PUT, DELETE wajib terlindungi oleh Laravel CSRF middleware.


## Modern Architecture & Zero Data Leakage (Inertia.js + Vue 3)
Saat merancang antarmuka pengguna atau memodifikasi modul yang ada, agen WAJIB menerapkan arsitektur *API-driven* dengan prinsip *Zero Data Leakage*:

**1. Larangan Mutlak Server-Side Data Injection:**
- **DILARANG KERAS** mencetak data mentah dari database langsung menggunakan PHP `json_encode` di dalam tag skrip HTML (`<script> const listData = <?= json_encode($data) ?>; </script>`).
- Seluruh transmisi data sensitif **WAJIB** melalui Inertia Props atau Axios API yang diproses secara asinkronus di sisi klien.

**2. Standarisasi Komponen Halaman Vue 3 (Inertia SFC):**
- Komponen halaman berlokasi di `resources/js/Pages/[Modul]/[SubFeature]/Index.vue`.
- Bungkus setiap halaman menggunakan layout standar `<AppLayout :title="...">`.
- Gunakan Composition API (`<script setup>`), Tailwind CSS v3 utilities, dan Bootstrap Icons (`bi-*`).
- Form handling menggunakan `@inertiajs/vue3` (`useForm()`) dan navigasi via `<Link :href="...">` atau `router.visit()`.

**3. Template Standar Komponen Vue 3:**
```vue
<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, useForm } from '@inertiajs/vue3'

defineProps({
    items: Object,
    filters: Object,
})
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

## Implementation Plans & Walkthroughs (Flexible Overhead Rule)
Setiap kali pekerjaan diselesaikan, dokumen rencana (*implementation plan*) dan dokumen hasil (*walkthrough*) wajib dicatat ke dalam **satu file harian** di `C:\laragon\www\sinta\scratch\docs` **beserta jam eksekusi detail (WIB)**:
- `YYYY-MM-DD_Implementation_Plans_Harian.md`
- `YYYY-MM-DD_Walkthrough_Harian.md`

**Ketentuan Format & Beban Dokumentasi:**
1. **Fitur Baru / Refactoring Arsitektur Besar**: Wajib menyertakan seluruh konten rencana & walkthrough secara lengkap verbatim (termasuk kueri SQL, kode PHP/JS, tabel, jam eksekusi, dan verification plan).
2. **Perbaikan Bug Kecil / Minor Tweaks**: Cukup gunakan format **Compact Log** (10-25 baris) yang mencakup: *Waktu (WIB) + Root Cause + Files Changed + Solution + Quick Verification Result*.

## Automatic Code Syntax Check Rule (WAJIB)
Setiap kali memodifikasi atau membuat berkas PHP baru, agen **WAJIB** secara otomatis menjalankan tes sintaks bebas error sebelum melaporkan pekerjaan selesai:
```powershell
php -l <path_file_php>
```
Pastikan output menunjukkan `No syntax errors detected`.

## Database Migration Rules (Laravel 11 Standard)
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
Saat membuat halaman baru atau merombak tata letak bilah navigasi tab (navtab / navpills) di seluruh modul SINTA SaaS, agen **WAJIB** menerapkan standar desain modern pill layout dan interaksi 3-way horizontal scroller di dalam `AppLayout.vue` dan Vue 3 components:

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
Saat membuat fitur ekspor data, unduh template, cetak laporan spreadsheet, atau impor massal di seluruh modul SINTA SaaS, agen **WAJIB** menerapkan standar format berikut tanpa pengecualian:

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


