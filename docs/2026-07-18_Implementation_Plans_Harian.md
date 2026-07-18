# Implementation Plans Harian â€” 2026-07-18

---
## Zero Data Leakage Dashboard & Solusi Cetak Aman
**Waktu**: 15:00 WIB
**Status**: Dieksekusi

### Latar Belakang & Root Cause
Halaman Dashboard (`views/dashboard_view.php`) menyuntikkan data mentah statistik GTK, siswa, riwayat perubahan, dan informasi sekolah secara inline di dalam script PHP menggunakan `json_encode($data)`. Hal ini menyebabkan kebocoran data sensitif lewat Ctrl+U (View Page Source). Selain itu, pencetakan rapor dan transkrip nilai menggunakan link cetak dengan ID siswa mentah di parameter URL, yang rentan terhadap modifikasi parameter oleh user biasa (Insecure Direct Object Reference).

### Proposed Changes

#### 1. Migrasi Dashboard ke AJAX Fetch Dinamis
- **`app/Controllers/DashboardController.php`**: Buat method baru `apiGetStats()` yang mengembalikan JSON statistik dashboard terotorisasi.
- **`views/dashboard_view.php`**: Hilangkan inline JSON dump, tambahkan event `onMounted()` di Vue untuk melakukan fetch Axios secara asinkronus saat halaman selesai dimuat.

#### 2. Solusi Cetak Aman
- Enkripsi ID siswa menggunakan algoritma `AES-256-CBC` atau gunakan token one-time valid 5 menit sebelum membuka link print view.
- Pada `BukuIndukController.php`, verifikasi token session di backend sebelum melakukan render HTML/PDF rapor.

### Verification Plan
1. Buka dashboard â†’ Ctrl+U â†’ pastikan data siswaList dan gtkList tidak tercetak dalam plaintext.
2. Akses link cetak rapor dengan memanipulasi URL secara manual â†’ pastikan sistem melempar error 403/Forbidden jika tidak ada session/token yang valid.

---
## Perbaikan Modul BK Akademik & PDSS (Error 400 Bad Request)
**Waktu**: 15:12 WIB
**Status**: Dieksekusi

### Latar Belakang & Root Cause
Saat memuat data simulasi PDSS pada sub-menu BK Akademik, browser mengirimkan request POST ke `/api/v1/pdss/simulasi/setting` dengan payload `tenant_id` bernilai kosong atau tidak terdefinisi. Hal ini disebabkan karena balapan inisialisasi (`race condition`) pada Vue component saat memuat `tahun_ajaran_id` dan `tenant_id` dari global state. Backend merespons dengan HTTP 400 Bad Request karena parameter wajib `tenant_id` tidak terpenuhi.

### Proposed Changes

#### [MODIFY] [pdss_index.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/pdss_index.php)
- Tunda eksekusi `fetchSimulasiSettings()` dan `fetchSimulasi()` sampai properti global `currentTenantId` dan `tahunAjaranActive` benar-benar terinisialisasi.
- Gunakan `watch` terarah pada Vue state untuk mendeteksi perubahan `currentTenantId`:
```javascript
watch: {
    currentTenantId(newVal) {
        if (newVal) {
            this.fetchSimulasiSettings();
            this.fetchSimulasi();
        }
    }
}
```

### Verification Plan
1. Buka menu BK Akademik â†’ tab Simulasi Pilihan Kampus.
2. Buka Network DevTools. Pastikan request ke `/api/v1/pdss/simulasi/setting` tidak lagi menghasilkan error 400 Bad Request.
3. Pastikan toggle penguncian simulasi dapat dimuat dan disimpan dengan status 200 OK.

---
## Perbaikan Pemetaan Cohort Siswa Kelas 12 PDSS (Tahun Ajaran 2024/2025)
**Waktu**: 15:27 WIB
**Status**: Dieksekusi

### Latar Belakang & Root Cause
Siswa kelas 12 aktif di tahun ajaran evaluasi `2024/2025` tidak terdeteksi pada tab Kesiapan PDSS. Hal ini karena database menyimpan tahun masuk awal (Entry Year) siswa sebagai `tahun_ajaran_id` di tabel `siswa`, sedangkan status aktif kelas 12 siswa dicatat di tabel `riwayat_kenaikan_kelas`. Query penarikan data eligible hanya mencocokkan `siswa.tahun_ajaran_id` dengan tahun ajaran evaluasi, sehingga siswa kelas 12 tidak pernah muncul. Selain itu, filter semester 5 menggunakan angka `5` mentah padahal database e-Rapor menyimpan data semester menggunakan string format `Ganjil` / `Genap` tingkat kelas 12.

### Proposed Changes

#### [MODIFY] [PDSSController.php](file:///c:/xampp/htdocs/SINTA-SaaS/app/Controllers/PDSSController.php)
- Ubah query SQL pada method `apiGetKesiapan()` agar melakukan join ke tabel `riwayat_kenaikan_kelas` dan filter berdasarkan tahun ajaran kelas XII aktif, bukan entry year siswa di tabel `siswa`.
- Sesuaikan logic deteksi semester 5 agar memetakan ke string `'Ganjil'` tingkat kelas 12 di tabel `detail_nilai_rapor`.

```sql
SELECT s.id, s.nama_lengkap, k.nama_kelas, rkk.tahun_ajaran_id
FROM siswa s
JOIN riwayat_kenaikan_kelas rkk ON s.id = rkk.siswa_id
JOIN kelas k ON rkk.kelas_id = k.id
WHERE rkk.tahun_ajaran_id = :tahun_ajaran_id
  AND (k.nama_kelas LIKE '%12%' OR k.nama_kelas LIKE '%XII%')
```

### Verification Plan
1. Buka tab Pangkalan Data (PDSS) â†’ pilih Tahun Ajaran Evaluasi `2024/2025`.
2. Pastikan daftar 30 siswa kelas 12 dummy (MIPA & IPS) tampil lengkap dengan status dan nilai.

---
## Perbaikan Kelayakan & Rata-rata Nilai Rapor Halaman Simulasi PDSS
**Waktu**: 15:42 WIB
**Status**: Dieksekusi

### Latar Belakang & Root Cause
Pada method `apiGetSimulasi()`, penentuan status *eligible* siswa dan perhitungan rata-rata nilai menghasilkan nilai `0` atau `tidak eligible`. Ini disebabkan karena sub-query pengambilan nilai di `PDSSController.php` mencari `dnr.semester IN (1,2,3,4,5)`. Namun, di database, kolom semester disimpan menggunakan string format `'Ganjil'` dan `'Genap'` yang dipetakan per tahun ajaran/kelas, bukan angka integer 1-5. Hal ini menyebabkan query gagal menarik nilai rapor siswa.

### Proposed Changes

#### [MODIFY] [PDSSController.php](file:///c:/xampp/htdocs/SINTA-SaaS/app/Controllers/PDSSController.php)
- Ubah query nilai pada method `apiGetSimulasi()` agar selaras dengan `apiGetKesiapan()` yang memetakan:
  - Semester 1 & 2: Kelas 10 (Ganjil & Genap)
  - Semester 3 & 4: Kelas 11 (Ganjil & Genap)
  - Semester 5: Kelas 12 (Ganjil)
- Pastikan formula rata-rata dihitung dari mata pelajaran PDSS yang dicentang di konfigurasi Langkah 1.

### Verification Plan
1. Buka menu BK Akademik â†’ Simulasi Pilihan Kampus.
2. Pastikan rata-rata nilai rapor siswa tampil (misal: 87.50, bukan 0.00).
3. Verifikasi ranking paralel terurut dengan benar dan status kelayakan ("Eligible" / "Tidak Eligible") tampil sesuai kuota akreditasi sekolah.

---
## Perbaikan Hak Akses & Keamanan Alumni & Tracer Study
**Waktu**: 16:08 WIB
**Status**: Dieksekusi

### Latar Belakang & Root Cause

Tiga masalah utama ditemukan pada halaman `http://localhost/SINTA-SaaS/bk/alumni`:

1. **Role `guru_bk` dan `operator_sekolah` mendapat `403 Forbidden`** saat menyimpan data tracer â€” whitelist role di `storeKuliah()` dan `storePekerjaan()` tidak menyertakan kedua role tersebut.
2. **Celah XSS** pada injeksi variabel PHP `$userRole` ke dalam tag `<script>` Vue yang tidak menggunakan `json_encode` dengan flag pelindung.
3. **Fitur Admin tidak ada** â€” tidak ada tombol Hapus, tidak ada kolom Nama Alumni, tidak ada live search siswa untuk admin input, tidak ada endpoint DELETE, tidak ada route search siswa.

---

### Component 1: [TracerController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/TracerController.php)

#### A. Fix storeKuliah() â€” baris 119

```php
// SEBELUM
} elseif (!in_array($roleName, ['admin', 'operator', 'super_admin'], true)) {

// SESUDAH
} elseif (!in_array($roleName, ['admin', 'operator', 'super_admin', 'operator_sekolah', 'guru_bk'], true)) {
```

#### B. Fix storePekerjaan() â€” baris 222

```php
// SEBELUM
} elseif (!in_array($roleName, ['admin', 'operator', 'super_admin'], true)) {

// SESUDAH
} elseif (!in_array($roleName, ['admin', 'operator', 'super_admin', 'operator_sekolah', 'guru_bk'], true)) {
```

#### C. [NEW] Method deleteKuliah()

```php
// DELETE /api/v1/tracer/kuliah/delete?id={id}
public function deleteKuliah(): void {
    $roleName = $_SESSION['role_name'] ?? '';
    $tenantId = SessionManager::getTenantId();
    if (empty($tenantId) && !empty($_GET['tenant_id'])) {
        $tenantId = trim($_GET['tenant_id']);
    }
    if (!in_array($roleName, ['super_admin', 'admin', 'operator', 'operator_sekolah', 'guru_bk'], true)) {
        $this->jsonResponse(['error' => 'Akses ditolak.'], 403); return;
    }
    $id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
    if (!$id) { $this->jsonResponse(['error' => 'ID tidak valid.'], 400); return; }
    $db = \App\Config\Database::getConnection();
    if ($roleName !== 'super_admin') {
        $stmt = $db->prepare("SELECT id FROM riwayat_kuliah WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$id, $tenantId]);
        if (!$stmt->fetch()) { $this->jsonResponse(['error' => 'Data tidak ditemukan atau akses ditolak.'], 404); return; }
    }
    $stmt = $db->prepare("DELETE FROM riwayat_kuliah WHERE id = ?");
    $stmt->execute([$id]);
    if ($stmt->rowCount() > 0) {
        $this->jsonResponse(['success' => true, 'message' => 'Riwayat kuliah berhasil dihapus.']);
    } else {
        $this->jsonResponse(['error' => 'Data tidak ditemukan.'], 404);
    }
}
```

#### D. [NEW] Method deletePekerjaan()

```php
// DELETE /api/v1/tracer/pekerjaan/delete?id={id}
public function deletePekerjaan(): void {
    $roleName = $_SESSION['role_name'] ?? '';
    $tenantId = SessionManager::getTenantId();
    if (empty($tenantId) && !empty($_GET['tenant_id'])) {
        $tenantId = trim($_GET['tenant_id']);
    }
    if (!in_array($roleName, ['super_admin', 'admin', 'operator', 'operator_sekolah', 'guru_bk'], true)) {
        $this->jsonResponse(['error' => 'Akses ditolak.'], 403); return;
    }
    $id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
    if (!$id) { $this->jsonResponse(['error' => 'ID tidak valid.'], 400); return; }
    $db = \App\Config\Database::getConnection();
    if ($roleName !== 'super_admin') {
        $stmt = $db->prepare("SELECT id FROM riwayat_pekerjaan WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$id, $tenantId]);
        if (!$stmt->fetch()) { $this->jsonResponse(['error' => 'Data tidak ditemukan atau akses ditolak.'], 404); return; }
    }
    $stmt = $db->prepare("DELETE FROM riwayat_pekerjaan WHERE id = ?");
    $stmt->execute([$id]);
    if ($stmt->rowCount() > 0) {
        $this->jsonResponse(['success' => true, 'message' => 'Riwayat pekerjaan berhasil dihapus.']);
    } else {
        $this->jsonResponse(['error' => 'Data tidak ditemukan.'], 404);
    }
}
```

---

### Component 2: [index.php](file:///C:/xampp/htdocs/SINTA-SaaS/index.php) â€” Routes Baru

```php
case '/api/v1/tracer/kuliah/delete':
    $controller = new App\Controllers\TracerController();
    $controller->deleteKuliah();
    break;

case '/api/v1/tracer/pekerjaan/delete':
    $controller = new App\Controllers\TracerController();
    $controller->deletePekerjaan();
    break;

case '/api/v1/pdss/students/search':
    // Method apiSearchStudents() sudah ada di PDSSController baris 887
    $controller = new App\Controllers\PDSSController();
    $controller->apiSearchStudents();
    break;
```

---

### Component 3: [tracer_study.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/tracer_study.php) â€” Refactor Total

#### A. Anti-XSS â€” Injeksi variabel ke script

```php
// SEBELUM (rentan XSS)
const userRole = ref('<?= $userRole ?>');

// SESUDAH (aman)
const userRole = ref(<?= json_encode($userRole, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);
const isAdmin  = ref(<?= json_encode($isAdmin) ?>);
```

#### B. Banner Role-Aware

```php
// Hanya untuk siswa alumni
<?php if ($userRole === 'siswa'): ?>
<div class="alert..." style="background:linear-gradient(135deg,#eff6ff,#ecfdf5);">
    <h5>âœ… Status: Alumni Lulus</h5>
    <p>Anda dapat menambah riwayat kuliah dan pekerjaan di bawah ini.</p>
</div>
<?php endif; ?>

// Hanya untuk admin/guru_bk/operator
<?php if ($isAdmin): ?>
<div class="alert..." style="background:linear-gradient(135deg,#f5f3ff,#ede9fe);">
    <h6>Mode Admin â€” <?= htmlspecialchars(ucwords(str_replace('_',' ',$userRole))) ?></h6>
    <p>Anda dapat menambah dan mengelola data tracer alumni.
       Siswa hanya dapat melihat dan mengedit data milik dirinya sendiri.</p>
</div>
<?php endif; ?>
```

#### C. Kolom Nama Alumni di Tabel (Admin Only)

```html
<th v-if="isAdmin">Nama Alumni</th>
...
<td v-if="isAdmin" class="fw-semibold text-truncate" style="max-width:140px;">
    {{ item.nama_lengkap || item.nama_alumni || 'â€”' }}
</td>
```

#### D. Tombol Hapus di Tabel (Admin Only)

```html
<th v-if="isAdmin" class="text-center">Aksi</th>
...
<td v-if="isAdmin" class="text-center">
    <button class="btn btn-sm btn-outline-danger rounded-2 px-2 py-1"
            @click="hapusKuliah(item.id)" title="Hapus riwayat kuliah ini">
        <i class="bi bi-trash3 fs-7"></i>
    </button>
</td>
```

#### E. Live Search Siswa Alumni (Admin Form)

```html
<div class="col-md-12" v-if="isAdmin">
    <div class="d-flex justify-content-between align-items-center mb-1">
        <label class="form-label fw-semibold fs-7 mb-0">Nama Alumni *</label>
        <div class="form-check form-switch m-0">
            <input class="form-check-input" type="checkbox" id="manualInputKuliah"
                   v-model="formKuliah.is_manual" @change="resetKuliah()">
            <label for="manualInputKuliah">Input Alumni Luar Sistem</label>
        </div>
    </div>
    <div v-if="!formKuliah.is_manual">
        <input type="text" class="form-control" v-model="formKuliah.nama_alumni"
               @input="searchStudents('kuliah')"
               @focus="showSearchDropdown=true; activeForm='kuliah'"
               placeholder="Ketik nama atau NISN siswa lulus...">
        <div v-if="showSearchDropdown && activeForm==='kuliah' && searchResults.length > 0"
             class="dropdown-menu show w-100 position-absolute overflow-auto shadow-sm"
             style="max-height:200px; z-index:999;">
            <button type="button" class="dropdown-item py-2 border-bottom"
                    v-for="s in searchResults" :key="s.id"
                    @mousedown.prevent="selectStudent(s, 'kuliah')">
                <div class="fw-bold">{{ s.nama_lengkap }}</div>
                <small class="text-muted">NISN: {{ s.nisn }} | NIS: {{ s.nis }}</small>
            </button>
        </div>
    </div>
    <div v-else>
        <input type="text" class="form-control" v-model="formKuliah.nama_alumni"
               placeholder="Ketik nama alumni secara manual...">
        <small class="text-muted">Menambahkan data alumni lawas yang tidak terdaftar di sistem.</small>
    </div>
</div>
```

#### F. Fungsi JavaScript Baru di Vue Setup

```javascript
// Cari siswa alumni secara live (debounce input)
async function searchStudents(formType) {
    activeForm.value = formType;
    selectedStudent.value = null;
    const query = formType === 'kuliah' ? formKuliah.value.nama_alumni
                                        : formPekerjaan.value.nama_alumni;
    if (query.trim().length < 2) {
        searchResults.value = []; showSearchDropdown.value = false; return;
    }
    const res = await fetch(
        `/SINTA-SaaS/api/v1/pdss/students/search?q=${encodeURIComponent(query)}&tenant_id=${tenantId}`
    );
    const data = await res.json();
    if (data.success) { searchResults.value = data.data || []; showSearchDropdown.value = true; }
}

// Pilih siswa dari dropdown
function selectStudent(student, formType) {
    selectedStudent.value = student;
    if (formType === 'kuliah') {
        formKuliah.value.nama_alumni = student.nama_lengkap;
        formKuliah.value.siswa_id = student.id;
    } else {
        formPekerjaan.value.nama_alumni = student.nama_lengkap;
        formPekerjaan.value.siswa_id = student.id;
    }
    showSearchDropdown.value = false; searchResults.value = [];
}

// Hapus riwayat kuliah (admin)
async function hapusKuliah(id) {
    if (!confirm('Hapus riwayat kuliah ini? Tindakan tidak dapat dibatalkan.')) return;
    const res = await fetch(
        `/SINTA-SaaS/api/v1/tracer/kuliah/delete?id=${id}&tenant_id=${tenantId}`,
        { method: 'DELETE', headers: { 'X-Requested-With': 'XMLHttpRequest' } }
    );
    const data = await res.json();
    if (res.ok && data.success) {
        riwayatKuliah.value = riwayatKuliah.value.filter(k => k.id !== id);
        alertKuliah.value = { msg: 'âœ… ' + data.message, type: 'success' };
    } else {
        alert(data.error || 'Gagal menghapus data.');
    }
}

// Hapus riwayat pekerjaan (admin) â€” pola sama dengan hapusKuliah
async function hapusPekerjaan(id) {
    if (!confirm('Hapus riwayat pekerjaan ini? Tindakan tidak dapat dibatalkan.')) return;
    const res = await fetch(
        `/SINTA-SaaS/api/v1/tracer/pekerjaan/delete?id=${id}&tenant_id=${tenantId}`,
        { method: 'DELETE', headers: { 'X-Requested-With': 'XMLHttpRequest' } }
    );
    const data = await res.json();
    if (res.ok && data.success) {
        riwayatPekerjaan.value = riwayatPekerjaan.value.filter(p => p.id !== id);
        alertPekerjaan.value = { msg: 'âœ… ' + data.message, type: 'success' };
    } else {
        alert(data.error || 'Gagal menghapus data.');
    }
}
```

---

### Matriks Hak Akses Per Role (Final)

| Fitur | super_admin | admin / operator_sekolah | guru_bk | siswa |
|---|:---:|:---:|:---:|:---:|
| Lihat tab Tracking Alumni (PDSS) | âœ… | âœ… | âœ… | âŒ |
| Lihat riwayat kuliah milik sendiri | âŒ | âŒ | âŒ | âœ… |
| Lihat riwayat kuliah semua alumni sekolah | âœ… | âœ… (tenant) | âœ… (tenant) | âŒ |
| Input riwayat kuliah (milik sendiri) | âŒ | âŒ | âŒ | âœ… (hanya jika status=Lulus) |
| Input riwayat kuliah (untuk alumni lain) | âœ… | âœ… | âœ… | âŒ |
| Hapus riwayat kuliah / pekerjaan | âœ… | âœ… | âœ… | âŒ |
| Live search siswa alumni | âœ… | âœ… | âœ… | âŒ |
| Kolom "Nama Alumni" di tabel | âœ… | âœ… | âœ… | âŒ |
| Banner "Status: Alumni Lulus" | âŒ | âŒ | âŒ | âœ… |
| Banner "Mode Admin" | âœ… | âœ… | âœ… | âŒ |

---

### Verification Plan

```bash
# Syntax check semua file
php -l app/Controllers/TracerController.php    # â†’ No syntax errors
php -l views/tracer_study.php                  # â†’ No syntax errors
php -l app/Controllers/PDSSController.php      # â†’ No syntax errors
php -l index.php                               # â†’ No syntax errors
```

**Manual Verification:**
- Login `guru_bk` â†’ `/bk/alumni` â†’ tab "Input Portofolio Alumni" â†’ tambah kuliah â†’ **harus berhasil (bukan 403)**
- Login `operator_sekolah` â†’ ulangi â†’ **harus berhasil**
- Login `admin` â†’ cek kolom "Nama Alumni" muncul, tombol Hapus tersedia, live search berfungsi
- Login `siswa` (Lulus) â†’ banner "Alumni Lulus" muncul, tidak ada tombol Hapus
- Login `siswa` (Aktif) â†’ halaman menampilkan pesan 403
- DevTools: `DELETE /api/v1/tracer/kuliah/delete?id=X&tenant_id=Y` â†’ `{"success":true}`
- DevTools: `GET /api/v1/pdss/students/search?q=andi&tenant_id=Y` â†’ hanya siswa berstatus Lulus


---
## Restrukturisasi & Integrasi Folder Uploads PDSS
**Waktu**: 16:50 WIB
**Status**: Dieksekusi
**Deskripsi**: Memindahkan lokasi penyimpanan berkas bukti simulasi PDSS dari `/uploads/pdss/` ke `/storage/uploads/pdss/` agar selaras dengan arsitektur isolasi file SINTA-SaaS (Standard Storage Isolation). Melakukan update record database path dan memindahkan file fisik.

### Proposed Changes

#### 1. Modifikasi Path di Database (SQL Update)
Mengupdate record relative path file bukti simulasi yang sudah terlanjur tersimpan di database:
```sql
UPDATE pdss_simulasi 
SET bukti_file = REPLACE(bukti_file, 'uploads/pdss/', 'storage/uploads/pdss/') 
WHERE bukti_file LIKE 'uploads/pdss/%';
```

#### 2. Modifikasi Controller — [PDSSController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/PDSSController.php)
Mengubah rujukan path penyimpanan target dan relative path di method `apiUploadBuktiSimulasi`:

*Sebelum:*
```php
$uploadDir = __DIR__ . "/../../uploads/pdss/simulasi/{$tenantId}/{$tahunAjaranId}/";
$relativePath = "uploads/pdss/simulasi/{$tenantId}/{$tahunAjaranId}/{$newFilename}";
```

*Sesudah:*
```php
$uploadDir = __DIR__ . "/../../storage/uploads/pdss/simulasi/{$tenantId}/{$tahunAjaranId}/";
$relativePath = "storage/uploads/pdss/simulasi/{$tenantId}/{$tahunAjaranId}/{$newFilename}";
```

#### 3. Pemindahan Berkas Fisik
Memindahkan folder `/uploads/pdss` secara rekursif ke `/storage/uploads/pdss` menggunakan script penanganan di `scratch/refactor_pdss_uploads_path.php`.

### Verification Plan
1. Jalankan script refactoring `scratch/refactor_pdss_uploads_path.php` ? verifikasi database ter-update dan folder fisik berpindah dengan benar.
2. Buka menu BK Akademik ? Simulasi Kampus ? tab Simulasi 3.
3. Unggah file bukti baru ? verifikasi data disimpan di folder `/storage/uploads/pdss/simulasi/` dan file dapat diakses melalui link `/storage/uploads/pdss/simulasi/...` tanpa broken link.

---
## Perbaikan Blank Tab Tracking Data Alumni pada Halaman BK Alumni
**Waktu**: 17:05 WIB
**Status**: Dieksekusi
**Deskripsi**: Mengatasi masalah tab "Tracking Data Alumni" yang kosong melompong (blank) akibat ketidaksesuaian ID tab filter `$allowed_pdss_tabs` di layout pembungkus dengan template utama `pdss_index.php`.

### Proposed Changes

#### [MODIFY] [views/bk/alumni_layout.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/bk/alumni_layout.php)
- Mengubah nilai filter tab dari `'alumni'` menjadi `'tracking'`.

*Sebelum:*
```php
$allowed_pdss_tabs = ['alumni'];
include __DIR__ . '/../pdss_index.php';
```

*Sesudah:*
```php
$allowed_pdss_tabs = ['tracking'];
include __DIR__ . '/../pdss_index.php';
```

### Root Cause
Di `pdss_index.php` baris 1056, Vue `activeTab` diinisialisasi dari `$allowed_pdss_tabs[0]`. Karena nilainya `'alumni'`, state Vue diset ke `'alumni'`. Namun, elemen kontainer tabel tracking di `pdss_index.php` baris 676 dibatasi dengan `<div v-show="activeTab === 'tracking'"`, dan menu navigasi tab baris 164 dibatasi dengan `in_array('tracking', $allowed_pdss_tabs)`. Ketidakcocokan ID tab (`alumni` vs `tracking`) ini menyebabkan HTML kontainer disembunyikan secara visual dan tab bar tidak dirender, menghasilkan halaman kosong.

### Verification Plan
1. Buka halaman `/bk/alumni` → pilih sekolah (misal: SMAN 1 Jakarta).
2. Verifikasi tab "Tracking Data Alumni" memuat tabel data alumni dengan benar (tidak kosong).

---
## Perbaikan Alumni Luar Sistem Tidak Muncul di Riwayat Kuliah & Pekerjaan
**Waktu**: 17:10 WIB
**Status**: Dieksekusi

### Latar Belakang & Root Cause

Alumni yang diinput secara manual via menu **"Input Alumni Luar Sistem"** (tanpa menunjuk akun siswa aktif) disimpan ke tabel `riwayat_kuliah` dan `riwayat_pekerjaan` dengan kolom `id_siswa = NULL`.

Method `apiGetKuliah()` dan `apiGetPekerjaan()` di `app/Controllers/TracerController.php` menggunakan `INNER JOIN siswa s ON rk.id_siswa = s.id`. Karena `id_siswa = NULL` tidak cocok dengan satu pun baris di tabel `siswa`, seluruh data alumni luar sistem otomatis **tersaring keluar** (tidak pernah muncul di hasil query) meskipun data sudah tersimpan dengan benar di database.

### Proposed Changes

#### File: `app/Controllers/TracerController.php`

**Method `apiGetKuliah()` - Sebelum:**
`php
// list per tenant
SELECT rk.*, s.nama_lengkap FROM riwayat_kuliah rk JOIN siswa s ON rk.id_siswa = s.id WHERE rk.tenant_id = ?
// list semua tenant (superadmin)
SELECT rk.*, s.nama_lengkap, t.nama_sekolah FROM riwayat_kuliah rk JOIN siswa s ON rk.id_siswa = s.id JOIN tenants t ...
`

**Method `apiGetKuliah()` - Sesudah:**
`php
// list per tenant
SELECT rk.*, s.nama_lengkap FROM riwayat_kuliah rk LEFT JOIN siswa s ON rk.id_siswa = s.id WHERE rk.tenant_id = ?
// list semua tenant (superadmin)
SELECT rk.*, s.nama_lengkap, t.nama_sekolah FROM riwayat_kuliah rk LEFT JOIN siswa s ON rk.id_siswa = s.id JOIN tenants t ...
`

**Method `apiGetPekerjaan()` - Sebelum:**
`php
// list per tenant
SELECT rp.*, s.nama_lengkap FROM riwayat_pekerjaan rp JOIN siswa s ON rp.id_siswa = s.id WHERE rp.tenant_id = ?
// list semua tenant (superadmin)
SELECT rp.*, s.nama_lengkap, t.nama_sekolah FROM riwayat_pekerjaan rp JOIN siswa s ON rp.id_siswa = s.id JOIN tenants t ...
`

**Method `apiGetPekerjaan()` - Sesudah:**
`php
// list per tenant
SELECT rp.*, s.nama_lengkap FROM riwayat_pekerjaan rp LEFT JOIN siswa s ON rp.id_siswa = s.id WHERE rp.tenant_id = ?
// list semua tenant (superadmin)
SELECT rp.*, s.nama_lengkap, t.nama_sekolah FROM riwayat_pekerjaan rp LEFT JOIN siswa s ON rp.id_siswa = s.id JOIN tenants t ...
`

### Penjelasan Teknis
INNER JOIN mensyaratkan baris ada di kedua tabel. Karena alumni luar sistem memiliki id_siswa = NULL, join gagal dan baris dibuang. LEFT JOIN mengambil semua baris dari tabel kiri (riwayat_kuliah/pekerjaan) meskipun tidak ada pasangan di tabel siswa, sehingga data alumni luar sistem kini ikut muncul.

### Verification Plan
1. Login ke sekolah yang memiliki data input alumni luar sistem (misal: SMAN 11).
2. Buka halaman BK > Alumni > tab Riwayat Kuliah.
3. Verifikasi data alumni luar sistem muncul di tabel.
4. Pastikan data alumni reguler (id_siswa valid) juga masih tampil normal.
5. Ulangi verifikasi di tab Riwayat Pekerjaan.
