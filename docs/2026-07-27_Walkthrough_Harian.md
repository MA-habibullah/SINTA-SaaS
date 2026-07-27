# 2026-07-27 Walkthrough Harian

---
## [Bug Fix: Menu Sidebar Double-Active (Dashboard & Sub-menu Aktif Bersamaan)]
**Waktu**: 15:41 WIB
**Jenis**: Bug Fix / Frontend Logic

### Deskripsi & Root Cause:
Ketika pengguna mengklik menu **Dashboard** perpustakaan lalu berpindah ke **Sirkulasi & Layanan**, kedua menu tetap tampil aktif (highlighted) secara bersamaan di sidebar.

**Root cause** ditemukan di fungsi `$isActive` dalam [sidebar.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/layout/sidebar.php). Fungsi tersebut menggunakan `str_contains($requestUri, $path)` — pemeriksaan substring mentah. Akibatnya, URL menu **Dashboard** (`/SINTA-SaaS/perpustakaan`) selalu dianggap **aktif** di semua halaman perpustakaan karena string tersebut memang merupakan substring dari `/SINTA-SaaS/perpustakaan/sirkulasi`, `/SINTA-SaaS/perpustakaan/katalog`, dan seterusnya.

### Solusi Implementasi:
**File yang diubah:** [views/layout/sidebar.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/layout/sidebar.php)

Fungsi `$isActive` direkonstruksi menggunakan logika **exact-boundary matching**:

```diff
-$isActive = function($paths) use ($requestUri) {
-    ...
-    if (str_contains($requestUri, $paths)) { return 'active'; }
-    ...
-};

+$isActive = function($paths) use ($requestUri) {
+    $currentPath = rtrim(parse_url($requestUri, PHP_URL_PATH), '/');
+    $checkPath = function(string $path) use ($currentPath): bool {
+        $menuPath = rtrim(strtok($path, '?'), '/');
+        // 1. Exact match
+        if ($currentPath === $menuPath) return true;
+        // 2. Prefix match + '/' boundary (sub-pages only)
+        if (str_starts_with($currentPath, $menuPath . '/')) return true;
+        return false;
+    };
+    ...
+};
```

**Tabel perbandingan perilaku:**

| URL Aktif | Sebelum (bug) | Sesudah (fix) |
|---|---|---|
| `/SINTA-SaaS/perpustakaan` | Dashboard ✅ | Dashboard ✅ |
| `/SINTA-SaaS/perpustakaan/sirkulasi` | Dashboard ✅ + Sirkulasi ✅ ❌ | Sirkulasi saja ✅ |
| `/SINTA-SaaS/perpustakaan/katalog` | Dashboard ✅ + Katalog ✅ ❌ | Katalog saja ✅ |
| `/SINTA-SaaS/perpustakaan/katalog/edit?id=x` | Dashboard ✅ + Katalog ✅ ❌ | Katalog saja ✅ |

### Verifikasi Hasil:
* **PHPStan Static Analysis Level 9:** Lulus 100% (`[OK] No errors`).
* **Automated Security Audit:** Lulus 100% (`Failed Checks: 0`).
* **Git Commit:** `fix(sidebar): replace str_contains with exact-boundary match in isActive to prevent double-active menu`

---
## [Bug Fix: Menu Dashboard Perpustakaan Tetap Aktif Meski di Halaman Lain (Round 2)]
**Waktu**: 15:44 WIB
**Jenis**: Bug Fix / Frontend Logic

### Root Cause (Iterasi 2):
Fix sebelumnya (`str_starts_with($currentPath, $menuPath . '/')`) masih tidak cukup. Logika **prefix match** tetap membuat Dashboard (`/SINTA-SaaS/perpustakaan`) aktif ketika berada di `/SINTA-SaaS/perpustakaan/sirkulasi`, karena URL tersebut memang diawali `/SINTA-SaaS/perpustakaan/`.

### Solusi Final:
**Hapus prefix match sepenuhnya.** Ganti ke **pure exact match** murni di [sidebar.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/layout/sidebar.php):

```php
// Pure exact match: path saat ini harus sama persis dengan path menu
return $currentPath === $menuPath;
```

Ini aman karena di aplikasi SINTA-SaaS, aksi sub-halaman (edit, detail, dsb.) selalu menggunakan **query string** (`?action=edit&id=...`) — bukan path segment baru — sehingga exact match sudah cukup untuk semua kasus navigasi.

### Perilaku Setelah Fix:

| URL yang Dibuka | Menu Aktif |
|---|---|
| `/SINTA-SaaS/perpustakaan` | ✅ Dashboard saja |
| `/SINTA-SaaS/perpustakaan/sirkulasi` | ✅ Sirkulasi & Layanan saja |
| `/SINTA-SaaS/perpustakaan/katalog` | ✅ Katalog & Inventori saja |
| `/SINTA-SaaS/perpustakaan/anggota` | ✅ Administrasi & Keanggotaan saja |

### Verifikasi Hasil:
* **PHP Syntax Check:** `No syntax errors detected`
* **PHPStan Static Analysis Level 9:** Lulus 100% (`[OK] No errors`).
* **Automated Security Audit:** Lulus 100% (`Failed Checks: 0`).
* **Git Commit:** `fix(sidebar): use pure exact-match in isActive - remove prefix match to stop Dashboard staying active on sub-pages`
