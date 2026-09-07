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

