# Implementation Plans Harian — 2026-07-11

---
## Manajemen Sidebar Bimbingan Konseling & PDSS (Revisi Final)
**Waktu**: 09:00 WIB
**Status**: Dieksekusi
**Deskripsi**: Restrukturisasi menu sidebar BK menjadi 1 Parent Menu dan 3 Sub-menu, disertai URL baru yang bersih, refactoring view modular, dan migrasi database menu RBAC.

### Proposed Changes

#### 1. Struktur Menu Sidebar Baru & Ikon

**BIMBINGAN KONSELING** *(Parent Menu, Icon: `bi-heart-pulse-fill`)*
1. **Layanan & Kedisiplinan** *(Sub-menu 1, Icon: `bi-person-badge`)* → `/bk/layanan`
   - Dashboard, Rekam Kasus & Jurnal, Prestasi Siswa, Kehadiran Semester, Tata Tertib & Poin
2. **Kesiapan Akademik & PDSS** *(Sub-menu 2, Icon: `bi-journal-check`)* → `/bk/akademik`
   - Penjurusan Mandiri, Kesiapan & Eligibilitas Siswa, Konfigurasi Target Kampus
3. **Alumni & Tracer Study** *(Sub-menu 3, Icon: `bi-mortarboard`)* → `/bk/alumni`
   - Tracer Study, Tracking Alumni & Rekam Kampus

#### 2. Restrukturisasi URL & Routing
- Sub-menu 1: `/SINTA-SaaS/bk/layanan`
- Sub-menu 2: `/SINTA-SaaS/bk/akademik`
- Sub-menu 3: `/SINTA-SaaS/bk/alumni`

#### 3. Refactoring View menjadi Modular
- Membuat satu *layout* utama untuk BK.
- Meng-`include` konten dari masing-masing tab secara dinamis berdasarkan URL.
- Memastikan navigasi tab responsif (bisa digeser di perangkat mobile).

#### 4. Database Migrations
- Mengubah susunan ID Menu (Parent ID 29, Sub ID 30, 31, 32).
- Menghapus menu PDSS lama (ID 40) dari top-level dan memindahkannya ke ID sub-menu baru.
- Menyinkronisasi tabel `role_menu_access` dan `tenant_menu_access`.

### Verification Plan
1. Menjalankan migrasi database menu secara otomatis.
2. Memperbarui aturan *Routing* di `index.php` dan `BKController`.
3. Memecah *Views* ke dalam komponen modular.
4. Login sebagai Guru BK/Admin untuk memverifikasi fungsionalitas tiap sub-menu.

---
## Refactoring UI/UX Halaman Alumni & Tracer Study
**Waktu**: 10:30 WIB
**Status**: Dieksekusi
**Deskripsi**: Menggabungkan dua header bertumpuk dan dua filter Super Admin menjadi satu Unified Layout untuk halaman Alumni & Tracer Study.

### Proposed Changes

#### [NEW] `views/bk/alumni_layout.php` — Unified Layout
- **Unified Header**: Satu judul besar **Alumni & Tracer Study**.
- **Unified Super Admin Filter**: Filter "Pilih Sekolah" digabung menjadi satu di paling atas halaman.
- **Unified Tab Navigation**:
  - **Tab 1: Input Portofolio Alumni** *(memuat antarmuka Tracer Study)*
  - **Tab 2: Tracking Data Alumni** *(memuat antarmuka Tracking PDSS)*

#### [MODIFY] `views/tracer_study.php`
- Membungkus area judul dan filter dengan kondisi PHP `if(empty($is_sub_module))` agar tersembunyi saat diakses melalui menu BK.

#### [MODIFY] `views/pdss_index.php`
- Membungkus area judul dan filter dengan kondisi PHP yang sama.

#### [MODIFY] `views/bk/hub.php`
- Mengubah alur pemanggilan pada rute `alumni` agar memanggil `alumni_layout.php`.

### Verification Plan
1. Menjalankan skrip *refactoring* untuk memodifikasi `tracer_study.php` dan `pdss_index.php`.
2. Membuat file `alumni_layout.php`.
3. Memperbarui `views/bk/hub.php`.
4. Merender hasilnya dan memverifikasi kedua *header* lama sudah hilang.

---
## Perbaikan Bug & Refactoring UI Halaman Kesiapan Akademik PDSS
**Waktu**: 11:45 WIB
**Status**: Dieksekusi
**Deskripsi**: Memperbaiki bug activeTab salah (alumni vs tracking) dan merombak total layout halaman /bk/akademik menjadi Unified Layout profesional.

### Root Cause Bug
`activeTab` di `pdss_index.php` salah diset dari `'alumni'` menjadi `'tracking'`:
```php
// SEBELUM (salah — ID tab tidak dikenal Vue)
const activeTab = ref('<?= ($active_group ?? "akademik") === "alumni" ? "alumni" : "kesiapan" ?>');

// SESUDAH (benar — ID tab sesuai yang didefinisikan Vue)
const activeTab = ref('<?= ($active_group ?? "akademik") === "alumni" ? "tracking" : "kesiapan" ?>');
```

### Desain Tata Letak Baru (`views/bk/akademik_layout.php`)
- **Unified Header**: Judul tunggal "Kesiapan Akademik & PDSS".
- **Unified Super Admin Filter**: Filter "Pilih Sekolah" dengan desain gradient.
- **Pills Navigation**:
  - Tab 1: **Penjurusan Mandiri** (merender fitur penjurusan dari modul BK)
  - Tab 2: **Pangkalan Data & Kesiapan SNBP** (merender modul PDSS dengan sub-tab bawaan)

### Verification Plan
1. Klik "Tracking Data Alumni" → harus langsung buka tabel data alumni, bukan Simulasi Kelayakan.
2. Buka `/bk/akademik` → UI bersih (1 judul + 1 filter + tab navigasi elegan).

---
## Solusi Terpadu Input Alumni Lampau & Pangkalan Data Kampus (PDSS)
**Waktu**: 13:00 WIB
**Status**: Dieksekusi
**Deskripsi**: Menambahkan struktur database Master Kampus, Prodi, dan Jalur Masuk, serta memperbarui TracerController untuk mendukung input alumni tahun lampau tanpa akun aktif. Seluruh perubahan bersifat NON-DESTRUKTIF.

### 1. Database — Migration `2026_07_11_02_master_kampus_dan_alumni.php`

**A. Modifikasi Tabel Lama (Non-Destructive):**
- `riwayat_pekerjaan`: Ubah `id_siswa` menjadi `DEFAULT NULL`, tambah kolom `nama_alumni VARCHAR(255)`.
- `riwayat_kuliah`: Tambah kolom `kampus_prodi_id` (referensi Master Prodi) dan `jalur_masuk_id` (referensi Master Jalur Masuk). Kolom teks lama `nama_kampus` dipertahankan sebagai fallback.

**B. Tabel Baru:**

```sql
-- Master Kampus
CREATE TABLE `master_kampus` (
    `id` UUID PRIMARY KEY, `tenant_id` CHAR(36),
    `nama_kampus`, `kota_kampus`, `alamat_kampus`,
    `jenis_kampus` ENUM('Negeri','Swasta','Kedinasan')
);

-- Master Prodi
CREATE TABLE `master_kampus_prodi` (
    `id` UUID PRIMARY KEY, `kampus_id` UUID,
    `fakultas`, `program_studi`,
    `jenjang` ENUM('D1','D2','D3','D4','S1','S2','S3','Profesi')
);

-- Riwayat Keketatan (Daya Tampung vs Pendaftar per Tahun)
CREATE TABLE `kampus_prodi_riwayat` (
    `id` INT AUTO_INCREMENT PRIMARY KEY, `prodi_id` UUID,
    `tahun`, `daya_tampung`, `jumlah_pendaftar`
);

-- Master Jalur Masuk (Dinamis, Menggantikan ENUM kaku)
CREATE TABLE `master_jalur_masuk` (
    `id` INT AUTO_INCREMENT PRIMARY KEY, `tenant_id` CHAR(36),
    `nama_jalur` -- SNBP, SNBT, Mandiri, KIP-K, dll.
);
```

### 2. Backend API

#### [MODIFY] `app/Controllers/TracerController.php`
- Menghapus kewajiban `siswa_id` (mengizinkan input alumni lawas).
- Menyesuaikan `storeKuliah()` agar menerima ID dari `master_kampus_prodi` dan `master_jalur_masuk`.

#### [NEW] `app/Controllers/KampusController.php`
- CRUD lengkap: `master_kampus`, `master_kampus_prodi` beserta histori daya tampung, `master_jalur_masuk`.

### 3. Frontend

#### [MODIFY] `views/pdss_index.php` (Tab Konfigurasi Target Kampus)
- Merombak halaman menjadi antarmuka manajemen **Master Kampus & Program Studi**.
- Input riwayat tahunan (Daya Tampung vs Pendaftar) dalam modal per prodi.
- Tab atau modal untuk kelola **Master Jalur Masuk** secara dinamis.

#### [MODIFY] `views/tracer_study.php`
- Dropdown "Jalur Masuk" menjadi dinamis dari `master_jalur_masuk`.
- "Nama Kampus", "Fakultas", "Jurusan" bisa dipilih dari `master_kampus_prodi` via autocomplete.
- Checkbox "Input Alumni Eksternal (Luar Sistem)" untuk input tanpa akun aktif.

### Verification Plan
1. `php migrate.php up` — tidak ada record lama yang terhapus.
2. Tambah kampus Universitas Indonesia (S1 Ilmu Komputer, Daya Tampung 60, Pendaftar 1200).
3. Tambah Jalur "Bidik Misi (KIP-K)".
4. Input alumni tahun 2018 (nama manual, tanpa ID siswa) → berhasil dikaitkan ke prodi dari database.

---
## Restrukturisasi UI Master Kampus & Prodi
**Waktu**: 14:30 WIB
**Status**: Dieksekusi
**Deskripsi**: Mengeluarkan tab "Master Kampus & Prodi" dari dalam Pangkalan Data PDSS menjadi Menu Navtab Utama sejajar dengan PDSS, serta menambahkan fitur Import/Export Excel untuk data daya tampung.

### Proposed Changes

#### [MODIFY] `views/bk/akademik_layout.php`
- Tambah tab utama baru `Master Kampus & Prodi` sejajar `Pangkalan Data (PDSS)`.
- Muat `master_kampus_prodi_layout.php` ke dalam tab tersebut.

#### [MODIFY] `views/pdss_index.php`
- Hapus tab internal "Master Kampus & Prodi" karena sudah dipindahkan ke luar.

#### [NEW] `views/bk/master_kampus_prodi_layout.php`
- Tabel flat: Kampus, Prodi, kolom dinamis Riwayat Daya Tampung (3 tahun terakhir) langsung di tabel.
- Tombol: **Export Excel**, **Import Excel**, **Hapus Kolektif**.

#### [MODIFY] `app/Controllers/KampusController.php`
- `apiGetMasterKampusProdiFlat()` — tabel kampus+prodi+riwayat dalam format array.
- `apiExportDayaTampung()` — file Excel (.xlsx) format: `KODE_PRODI | NAMA_PRODI | KAMPUS | TAHUN | DAYA_TAMPUNG | PENDAFTAR`.
- `apiImportDayaTampung()` — baca Excel, perbarui `kampus_prodi_riwayat`.
- `apiBulkDeleteRiwayat()` — hapus kolektif berdasarkan Tahun atau Kampus.

### Verification Plan
1. Tab "Master Kampus & Prodi" muncul di navigasi utama halaman Akademik.
2. Tabel menampilkan data riwayat kuota (tanpa klik tambahan).
3. Export Excel → ubah daya tampung → Import Excel → verifikasi data berubah.
4. Bulk Delete berdasarkan tahun → verifikasi data hilang dari database.

---
## Simplifikasi UI Navtab & Fitur Import Excel Master Kampus
**Waktu**: 15:30 WIB
**Status**: Dieksekusi
**Deskripsi**: Menyederhanakan navtab yang bertingkat dan menambahkan fitur import dari Excel untuk Master Kampus, Prodi, dan Riwayat Daya Tampung.

### Proposed Changes

#### [MODIFY] `views/bk/akademik_layout.php`
- Mengubah alokasi tab dari `['kesiapan', 'config']` menjadi langsung `['kesiapan', 'master_kampus', 'master_jalur']`.

#### [MODIFY] `views/pdss_index.php`
- Hapus tab "Konfigurasi Target Kampus", tampilkan langsung:
  - 🎓 **Kesiapan & Eligibilitas Siswa**
  - 🏛️ **Master Kampus & Prodi**
  - 🛤️ **Master Jalur Masuk**

#### [MODIFY] `app/Controllers/KampusController.php`
- Endpoint `GET /api/v1/kampus/template` — download template Excel standar.
- Endpoint `POST /api/v1/kampus/import` — baca file upload Excel dan simpan data.

**Logika Import:**
- Baca setiap baris Excel.
- Cari/buat **Kampus Baru** berdasarkan Nama Kampus.
- Cari/buat **Program Studi Baru** berdasarkan Nama Kampus + Nama Prodi.
- Catat **Riwayat Keketatan** untuk tahun yang diisikan.

**Format Kolom Excel (Template):**

| # | Kolom | Wajib | Contoh |
|---|---|---|---|
| 1 | Nama Kampus | ✅ | Universitas Indonesia |
| 2 | Kota | ❌ | Depok |
| 3 | Jenis Kampus | ✅ | Negeri / Swasta / Kedinasan |
| 4 | Fakultas | ❌ | Fasilkom |
| 5 | Program Studi | ✅ | Sistem Informasi |
| 6 | Jenjang | ✅ | D3, D4, S1 |
| 7 | Tahun Keketatan | ✅ | 2024 |
| 8 | Daya Tampung | ✅ | 50 |
| 9 | Jumlah Pendaftar | ✅ | 1200 |

### Verification Plan
1. Tab *Master Kampus & Prodi* dan *Master Jalur Masuk* tampil sebagai tab utama.
2. Download `template_kampus.xlsx` → kolom sesuai format di atas.
3. Upload Excel 3 baris → data kampus, prodi, dan riwayat muncul di tabel.
