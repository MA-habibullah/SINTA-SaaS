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

---
## [Feature & Enhancement: Dropdown Klasifikasi DDC & Upload E-Book Digital + Cover Buku]
**Waktu**: 16:02 WIB
**Jenis**: Feature / UI & Backend Enhancement

### Fitur & Perbaikan:
1. **Dropdown Klasifikasi DDC (Searchable & Filterable)**:
   - Mengubah input teks biasa Kode DDC pada modal Tambah/Edit Judul Buku menjadi `<select>` dropdown interaktif yang dimuat dinamis dari data `perpus_kategori_ddc` database.
   - Dilengkapi filter pencarian instan (*client-side live search*) untuk mencari kode DDC atau deskripsi nama kategori DDC.
   - Menyediakan indikator badge DDC terpilih beserta tombol hapus (*clear button*).

2. **Upload Cover Buku**:
   - Menambahkan input upload berkas cover gambar (`JPG`, `PNG`, `WebP`, maks 2MB).
   - Dilengkapi preview gambar langsung (*FileReader preview*) sebelum disimpan.
   - Menampilkan thumbnail cover gambar yang sudah tersimpan saat mengedit katalog.

3. **Upload E-Book Digital (PDF / EPUB)**:
   - Menambahkan dropdown status media buku: `📚 Buku Fisik Saja`, `💻 E-Book Digital`, `📚💻 Fisik + E-Book Digital`.
   - Menampilkan card upload berkas e-book secara dinamis (*conditional rendering*) hanya ketika status E-Book Digital dipilih.
   - Mendukung upload berkas e-book berformat `.pdf` atau `.epub` hingga kapasitas 50MB.

4. **Penanganan Backend & Model (Security Audit Compliant)**:
   - Mengubah form modal menjadi `enctype="multipart/form-data"`.
   - Di `PerpustakaanController::apiSaveBibliografi()`, dilakukan penanganan upload dengan validasi MIME type aman menggunakan PHP `finfo` (mencegah *File Upload Vulnerability*).
   - Berkas fisik disimpan terenkapsulasi di `storage/perpustakaan/covers/` dan `storage/perpustakaan/ebooks/`.
   - Di `Perpustakaan::saveBibliografi()`, kolom `cover`, `file_ebook`, dan `is_ebook` disimpan ke database dengan parameterized PDO statement.

### Berkas yang Diubah:
- **[views/perpustakaan/katalog.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/perpustakaan/katalog.php)**: Penyesuaian modal form, DDC searchable dropdown, upload cover & ebook, JavaScript handlers.
- **[app/Controllers/PerpustakaanController.php](file:///c:/xampp/htdocs/SINTA-SaaS/app/Controllers/PerpustakaanController.php)**: Pemrosesan upload berkas cover dan ebook dengan validasi MIME & ukuran.
- **[app/Models/Perpustakaan.php](file:///c:/xampp/htdocs/SINTA-SaaS/app/Models/Perpustakaan.php)**: Update query SQL `INSERT` & `UPDATE` untuk menyimpan path cover & file ebook.

### Verifikasi Hasil:
* **PHPStan Static Analysis Level 9:** Lulus 100% (`[OK] No errors`, 3 files checked).
* **Automated Security Audit:** Lulus 100% (`Failed Checks: 0`).
* **Git Commit:** `feat(perpustakaan): add DDC dropdown search & ebook/cover upload handling in book catalog modal`

