# Implementation Plans Harian — 2026-07-17

---
## Migrasi Menyeluruh Pemuatan Data Halaman ke Model AJAX Fetch Dinamis
**Waktu**: 19:00 WIB
**Status**: Dieksekusi

### Analisis & Tujuan Keamanan

Saat ini, beberapa menu utama menyuntikkan data PHP langsung ke JavaScript block:
```php
const data = <?= json_encode($data) ?>;
```

Masalah yang timbul:
1. **Kebocoran View Source**: Data sensitif (NIK, KK, alamat, email, profil sekolah) terbaca via Ctrl+U.
2. **Kelemahan XSS**: Menulis JSON mentah di dalam `<script>` berisiko tinggi jika data tidak ter-escape sempurna.

**Solusi:** Meniadakan semua pencetakan JSON mentah di 13 file view utama, diganti dengan pemanggilan AJAX terotorisasi saat inisialisasi Vue (`onMounted`).

### Proposed Changes

#### 1. Modul Siswa (`tambah_siswa.php` & `SiswaController.php`)
- **Masalah:** Menyuntikkan profil siswa (NIK, KK, nama ibu, password) dan data kesehatan.
- **Solusi:** Daftarkan action AJAX `get_siswa_detail` & `get_siswa_draft` di `SiswaController.php`. Hilangkan variabel JSON inline di `tambah_siswa.php`, panggil API via Axios saat Vue dimuat.

#### 2. Modul Dashboard (`dashboard_view.php` & `DashboardController.php`)
- **Masalah:** Menyuntikkan `siswaList`, `gtkList`, `recentChanges`, `schoolInfo`.
- **Solusi:** Daftarkan action AJAX `get_dashboard_stats`. Ambil seluruh statistik asinkronus setelah halaman dirender.

#### 3. Modul Profil & Identitas Sekolah (`sekolah_profil.php`, `identitas_sekolah.php`)
- **Masalah:** Menyuntikkan konfigurasi profil sekolah `$tenant`.
- **Solusi:** Sediakan API `get_profile_detail` di `SekolahController.php`. Panggil via Axios untuk mengisi form.

#### 4. Modul Bimbingan Konseling (`master_bk.php`, `bk/master_kampus_prodi_layout.php`)
- **Masalah:** Menyuntikkan data tahun ajaran, prodi list, dan detail user.
- **Solusi:** Migrasikan ke AJAX endpoint terotorisasi.

#### 5. Modul Agenda Terpadu (`agenda_terpadu.php` & `AgendaController.php`)
- **Masalah:** Menyuntikkan `$events` dan `$ganttTasks`.
- **Solusi:** Gunakan pemuatan asinkronus bawaan FullCalendar (URL sumber event) dan load tasks Gantt setelah mount.

#### 6. Modul Buku Induk (`buku_induk.php` & `BukuIndukController.php`)
- **Masalah:** Menyuntikkan listTenants, jenjangOptions, kelasOptions.
- **Solusi:** Ambil opsi dropdown via AJAX secara dinamis.

#### 7. Modul Log Aktivitas (`activity_logs.php` & `ActivityLogController.php`)
- **Masalah:** Menyuntikkan status admin log.
- **Solusi:** Muat log dan config awal secara dinamis.

### Contoh Pola Perubahan Kode

```html
<!-- SEBELUM (Insecure — bocor via View Source) -->
<script>
    const schoolData = ref(<?= json_encode($tenant) ?>);
</script>

<!-- SESUDAH (Secure — Zero Plaintext Data in Page Source) -->
<script>
    const schoolData = ref(null);
    onMounted(async () => {
        try {
            const response = await axios.get('/SINTA-SaaS/sekolah/profil?ajax=1&action=get_profile_detail');
            if (response.data && response.data.success) {
                schoolData.value = response.data.data;
            }
        } catch (err) {
            console.error("Gagal memuat profil sekolah:", err);
        }
    });
</script>
```

### Verification Plan
1. Buka seluruh menu (Dashboard, BK, Siswa, Sekolah, Agenda, Buku Induk).
2. Klik kanan → **View Page Source (Ctrl+U)**.
3. Pastikan **TIDAK ADA LAGI** teks NIK, KK, nama siswa, email, detail sekolah di dalam tag `<script>`.
4. Semua tabel, grafik, kalender agenda, dan form edit tetap termuat dengan data lengkap.

---
## Hardening Keamanan Halaman Verifikasi Transkrip
**Waktu**: 19:51 WIB
**Status**: Dieksekusi

### Proposed Changes

**File:** `app/Controllers/BukuIndukController.php` (method `verifyTranskrip`) + `views/verify_transkrip.php`

1. **Validasi Format UUID** pada parameter GET `id`:
   ```php
   if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id)) {
       http_response_code(400); die('Parameter tidak valid.');
   }
   ```

2. **HTTP Security Headers:**
   ```php
   header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'");
   header("X-Frame-Options: DENY");
   header("X-Content-Type-Options: nosniff");
   header("Referrer-Policy: no-referrer");
   ```

3. **Rate Limiting Sederhana** berbasis IP menggunakan file-based counter di `scratch/`:
   - Maks 10 request per IP per menit.
   - Jika melebihi → HTTP 429 Too Many Requests.

4. **Perbaiki `die()`** agar tidak expose detail stack trace atau HTML mentah ke pengguna.

### Verification Plan
1. Akses `/verify-transkrip?id=BUKAN-UUID` → HTTP 400.
2. Request 11 kali berturut-turut dalam 1 menit → HTTP 429.
3. Buka halaman dengan ID valid → Security headers muncul di DevTools → Network → Response Headers.

---
## Zero Data Leakage pada Halaman Verifikasi Transkrip
**Waktu**: 20:00 WIB
**Status**: Dieksekusi

### Proposed Changes

**Tujuan:** Data siswa tidak tampil di View Page Source maupun Inspect Element.

#### Alur Baru (Dynamic Client-Side Rendering):
1. `/verify-transkrip` hanya render **skeleton HTML** + terbitkan **secure one-time session token** valid 5 menit.
2. Endpoint API baru `/api/v1/verify-transkrip/data` melayani data siswa dalam JSON setelah memverifikasi token.
3. PHP `unset()` data sensitif di sisi server sebelum JSON dikirim ke client.
4. Script AJAX di `views/verify_transkrip.php` ambil data via `fetch()` secara asinkronus + manipulasi DOM dinamis.

#### [MODIFY] `app/Controllers/BukuIndukController.php`
```php
// verifyTranskrip() — set token ke session, tidak kirim ke view
$_SESSION['vt_siswa_id'] = $siswaId;
$_SESSION['vt_expires']  = time() + 300; // 5 menit

// verifyTranskripApi() — verifikasi session, hapus setelah digunakan
if (empty($_SESSION['vt_siswa_id']) || time() > $_SESSION['vt_expires']) {
    $this->jsonResponse(['error' => 'Token kedaluwarsa atau tidak valid.'], 403); return;
}
$siswaId = $_SESSION['vt_siswa_id'];
unset($_SESSION['vt_siswa_id'], $_SESSION['vt_expires']); // One-time access
// ... ambil data siswa, unset password, kirim JSON
```

#### [MODIFY] `views/verify_transkrip.php`
```javascript
// Fetch data tanpa token parameter — gunakan session cookie bawaan
fetch('/SINTA-SaaS/api/v1/verify-transkrip/data', {
    credentials: 'same-origin'
})
.then(r => r.json())
.then(data => {
    if (data.success) renderProfile(data.data);
    else showError(data.error);
});
```

### Verification Plan
1. Ctrl+U di `/verify-transkrip` → tidak ada NIK, nama, nilai di source code.
2. Buka Network DevTools → verifikasi data datang via XHR, bukan inline.
3. Akses API langsung 2x berturut-turut → request ke-2 mendapat HTTP 403 (one-time token).

---
## Eliminasi Token Leakage pada Verifikasi Transkrip
**Waktu**: 20:25 WIB
**Status**: Dieksekusi

### Latar Belakang & Root Cause
Sebelumnya, token akses one-time untuk verifikasi dicetak langsung di tag `<script>` views/verify_transkrip.php agar bisa dikirimkan ke parameter query AJAX. Hal ini berisiko membocorkan token tersebut kepada pengguna biasa melalui perintah "View Page Source" (Ctrl+U).

### Proposed Changes

#### 1. Penghapusan Token pada Script View
Mengubah alur autentikasi API: AJAX fetch akan menggunakan session cookie bawaan secara otomatis tanpa memerlukan token parameter di URL.

#### 2. Modifikasi Controller (`BukuIndukController.php`)
- Di `BukuIndukController::verifyTranskrip()`, session `vt_siswa_id` dan `vt_expires` diset seperti biasa, tetapi tidak ada variabel token yang dikirim ke view.
- Di `BukuIndukController::verifyTranskripApi()`, verifikasi didasarkan langsung pada session `vt_siswa_id` dan `vt_expires` yang disimpan secara internal di server. Setelah data diambil sekali, session siswa_id langsung dihapus (`unset()`) untuk menjamin one-time data access.

#### 3. Modifikasi AJAX pada View (`views/verify_transkrip.php`)
Mengubah pemanggilan fetch agar mengarah langsung ke `/SINTA-SaaS/api/v1/verify-transkrip/data` tanpa token parameter:
```javascript
// SEBELUM (token bocor di view source)
fetch('/SINTA-SaaS/api/v1/verify-transkrip/data?token=' + secretToken)

// SESUDAH (tidak ada token di JS, autentikasi via session cookie)
fetch('/SINTA-SaaS/api/v1/verify-transkrip/data', { credentials: 'same-origin' })
```

### Verification Plan
- **Manual Verification**:
  1. Akses halaman verifikasi transkrip yang valid.
  2. Lakukan Ctrl+U (View Page Source). Pastikan tidak ada token rahasia yang tercetak di tag `<script>`.
  3. Pastikan data profil siswa tetap termuat dengan benar melalui AJAX.
  4. Lakukan reload halaman, pastikan data tidak bisa dimuat kembali (HTTP 403) karena session one-time telah dihapus setelah akses pertama.

---
## Audit & Perbaikan Keamanan Menyeluruh (Anti-XSS & Pencegahan Kebocoran Data)
**Waktu**: 10:00 WIB
**Status**: Dieksekusi
**Deskripsi**: Audit menyeluruh 13 file view yang menyuntikkan data PHP ke JavaScript/HTML tanpa flag keamanan. Dua kategori celah: (A) Script Break XSS via json_encode tanpa flag hex, (B) Attribute Break XSS via onclick dengan kutip tunggal.

### Kategori A — Proteksi Injeksi `<script>` & Pembersihan Hash Password

**Solusi:** Tambahkan `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT` pada semua `json_encode` dalam tag `<script>`.

| File | Perubahan |
|---|---|
| `views/tambah_siswa.php` | unset password sebelum encode + flag hex pada siswaFullData, kesehatanData, draft, old |
| `views/sekolah_profil.php` | flag hex pada `json_encode($tenant)` |
| `views/identitas_sekolah.php` | flag hex pada `json_encode($tenant)` |
| `views/pdss_index.php` | flag hex pada `_userRole`, `_canWrite`, `_currentTenantId` |
| `views/master_kelembagaan.php` | flag hex pada `listTenants` |
| `views/master_bk.php` | flag hex pada `_tenantId`, `_userRole`, `_userId`, `_tahunAjaranList` |
| `views/login_view.php` & `siswa_login_view.php` | flag hex pada `INITIAL_TENANTS` |
| `views/dashboard_view.php` | flag hex pada userRole, schoolInfo, siswaList, gtkList, recentChanges |
| `views/buku_induk.php` | flag hex pada listTenants, jenjangOptions, kelasOptions |
| `views/bk/master_kampus_prodi_layout.php` | flag hex pada `canWrite` |
| `views/activity_logs.php` | flag hex pada `isSuperAdmin` |
| `views/sekolah/agenda_terpadu.php` | flag hex pada `events` dan `tasks` di dalam `<script>` |

### Kategori B — Proteksi Injeksi Atribut HTML (Anti Attribute Break)

**Solusi:** Bungkus `json_encode($row)` dengan `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` pada atribut HTML.

```php
// SEBELUM (rentan)
onclick='editAgenda(<?= json_encode($row) ?>)'

// SESUDAH (aman)
onclick="editAgenda(<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>)"
```

File yang diubah:
- `views/sekolah/agenda_terpadu.php` — tombol editAgenda, editKategori
- `views/humas/pengumuman.php` — tombol editPengumuman, editKategori

### Verification Plan
1. Buka edit siswa → lihat sumber halaman → kolom password tidak ada.
2. Input nama dengan `'` atau `</script><script>alert(1)</script>` → buka halaman → tidak ada alert/error JS.

---
## Optimasi Kinerja & Pencegahan UI Blocking (<1ms) pada AeroScan
**Waktu**: 11:30 WIB
**Status**: Dieksekusi
**Deskripsi**: Mengubah penanganan event dari sinkronus (blocking 500-1000ms) menjadi asinkronus (non-blocking <1ms) di modul AeroScan menggunakan setTimeout wrapping untuk semua operasi berat OpenCV/jsPDF.

### [MODIFY] `views/utility/document_scanner.php`

**Pola perbaikan:** Semua operasi berat dibungkus `setTimeout(() => { ... }, 10)`:

1. **`initModeSelector()`**: Bungkus `detectCorners()` + `processAndCompress()` dalam setTimeout.
2. **`initParametersListeners()`**: Bungkus perubahan resolusi + tombol filter warna dalam setTimeout/debouncedProcess.
3. **`addCurrentToPdfQueue()` & `generatePdf()`**: Ubah jadi asinkronus agar pembuatan PDF multi-halaman tidak membekukan browser.
4. Tampilkan status `"Memproses..."` secara instan sebelum pemrosesan dimulai.

### Verification Plan
1. Buka Developer Tools → Console.
2. Klik tombol ubah mode, filter warna, download, buat PDF.
3. Tidak ada lagi `[Violation] 'click' handler took ...ms`.
4. Tombol memunculkan status aktif secara instan saat diklik.

---
## Perbaikan Inisialisasi Lifecycle pada Navigasi Turbo/SPA
**Waktu**: 13:00 WIB
**Status**: Dieksekusi
**Deskripsi**: Memperbaiki bug di mana tombol AeroScan tidak berfungsi saat diakses dari Dashboard via navigasi Turbo (SPA) karena DOMContentLoaded tidak terpicu ulang. Solusi: inisialisasi instan + sistem cleanup global.

### Root Cause
- Turbo hanya mengganti konten `<body>` via AJAX → `DOMContentLoaded` tidak dipicu ulang.
- Script AeroScan dibungkus `DOMContentLoaded` → tidak pernah dijalankan saat navigasi Turbo.
- Potensi memory leak: event listener `resize`, `mousemove`, `mouseup` tidak dihapus antar kunjungan.

### [MODIFY] `views/utility/document_scanner.php`

#### 1. Hapus pembungkus `DOMContentLoaded`
- Jalankan inisialisasi langsung (script sudah di bawah semua elemen HTML).

#### 2. Sistem Cleanup Global
```javascript
let resizeHandler = null;
let panMouseMoveHandler = null;
let panMouseUpHandler = null;

function initAeroScan() {
    // Jalankan cleanup kunjungan sebelumnya
    if (typeof window._aeroScanCleanup === 'function') window._aeroScanCleanup();

    // Inisialisasi semua modul
    initDragCorners(); initModeSelector(); initCameraScanner(); ...

    // Daftarkan cleanup untuk kunjungan berikutnya
    window._aeroScanCleanup = function() {
        stopCameraScan();
        window.removeEventListener('resize', resizeHandler);
        document.removeEventListener('mousemove', panMouseMoveHandler);
        document.removeEventListener('mouseup', panMouseUpHandler);
    };
}

// Jalankan langsung
initAeroScan();

// Cleanup saat Turbo berpindah halaman
document.addEventListener('turbo:before-cache', () => {
    if (typeof window._aeroScanCleanup === 'function') window._aeroScanCleanup();
});
```

### Verification Plan
1. Buka Dashboard → klik menu Pemindai Dokumen (tanpa refresh) → tombol Upload/Kamera langsung berfungsi.
2. Buka Pemindai → kembali ke Dashboard → buka Pemindai lagi → zoom/pan dan sudut masih normal, tidak ada duplikasi event.
