# Implementation Plans Harian — 2026-07-14

---
## Pengaturan Mapel PDSS & Simulasi Ranking 5 Semester
**Waktu**: 09:00 WIB
**Status**: Dieksekusi
**Deskripsi**: Memodifikasi tab Pangkalan Data (PDSS) agar BK dapat menentukan mata pelajaran kurikulum untuk perhitungan PDSS, menghitung rata-rata nilai 5 semester, dan memfilter ranking paralel berdasarkan Kelas/Jurusan.

### 1. Database Schema

```sql
CREATE TABLE IF NOT EXISTS `pdss_config_mapel` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` CHAR(36) NOT NULL,
    `mapel_id` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_tenant_mapel` (`tenant_id`, `mapel_id`),
    CONSTRAINT `fk_pdss_mapel_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pdss_mapel_id` FOREIGN KEY (`mapel_id`) REFERENCES `mata_pelajaran` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;
```

### 2. Backend

#### [MODIFY] `index.php`
- Tambah route `/api/v1/pdss/config-mapel` (GET = ambil mapel, POST = simpan pilihan).

#### [MODIFY] `app/Controllers/PDSSController.php`
- **`__construct`**: Auto-create tabel `pdss_config_mapel`.
- **`apiGetPdssMapels()` [NEW]**: Ambil semua mapel aktif + tanda centang `is_selected`.
- **`apiSavePdssMapels()` [NEW]**: Simpan pilihan mapel dalam satu transaksi ACID.
- **`apiGetKesiapan()` [MODIFY]**: Filter rata-rata nilai 5 semester hanya pada mapel terpilih.

### 3. Frontend — `views/pdss_index.php`

Ubah tab Kesiapan menjadi alur kerja bernomor:
1. **Langkah 1**: Grid checkbox pemilihan mata pelajaran kurikulum untuk PDSS.
2. **Langkah 2**: Simulasi Kuota dan Filter (dropdown Kelas + Jurusan).
3. **Langkah 3**: Tabel Hasil Simulasi Kelayakan & Ranking Paralel.

**Logika Rumus Semester:**
- Semester 1 & 2: Kelas 10 (nama kelas mengandung `10` atau `X`)
- Semester 3 & 4: Kelas 11 (nama kelas mengandung `11` atau `XI`)
- Semester 5: Kelas 12 (nama kelas mengandung `12` atau `XII`) semester Ganjil

### Verification Plan
```powershell
php -l index.php
php -l app/Controllers/PDSSController.php
php -l views/pdss_index.php
```
1. Centang Matematika Wajib, Bahasa Indonesia → Simpan.
2. Verifikasi rata-rata nilai terhitung hanya dari mapel terpilih.
3. Indikator kelengkapan menampilkan `10 / 10 Nilai`.

---
## Filter Cohort Kelas 12, Mapel Kurikulum Terpetakan, & Opsi Bonus Kuota e-Rapor
**Waktu**: 10:30 WIB
**Status**: Dieksekusi
**Deskripsi**: Merevisi modul PDSS agar filter siswa kelas 12 berbasis cohort aktif, mapel dibatasi hanya yang terpetakan di kurikulum, dan menambahkan opsi bonus kuota e-Rapor +5%.

### Backend: [PDSSController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/PDSSController.php)

#### `apiGetPdssMapels()` — Query mapel terpetakan kurikulum
```sql
SELECT DISTINCT mp.id, mp.kode_mapel, mp.nama_mapel 
FROM mata_pelajaran mp
JOIN pemetaan_mapel pm ON mp.id = pm.mapel_id
JOIN kelas k ON pm.kelas_id = k.id
WHERE mp.tenant_id = ? 
  AND mp.is_active = 1 
  AND mp.deleted_at IS NULL
  AND pm.tahun_ajaran = ?
  AND (k.nama_kelas LIKE '%10%' OR k.nama_kelas LIKE '%X%'
    OR k.nama_kelas LIKE '%11%' OR k.nama_kelas LIKE '%XI%'
    OR k.nama_kelas LIKE '%12%' OR k.nama_kelas LIKE '%XII%')
ORDER BY mp.nama_mapel ASC
```

#### `apiDownloadLeger()` — Filter mapel kurikulum pada Excel
- Perbarui query agar kolom mapel di Excel steril dari mapel yang tidak digunakan di kurikulum aktif.

### Frontend: [pdss_index.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/pdss_index.php)

#### State baru Vue
```javascript
useERapor: false  // checkbox bonus kuota e-Rapor
```

#### UI Checkbox e-Rapor di Langkah 2
```html
<label class="flex items-center gap-1.5 text-xs font-semibold text-slate-600 cursor-pointer ml-3 select-none">
    <input type="checkbox" v-model="useERapor" :disabled="locks[2].is_locked" 
           class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
    Menggunakan e-Rapor (+5% Kuota SNBP)
</label>
```

#### Opsi Kuota Bonus
| Akreditasi | Normal | Bonus e-Rapor |
|---|---|---|
| A | 40% | 45% |
| B | 25% | 30% |
| C | 5% | 10% |

### Verification Plan
1. Pilih Tahun Ajaran Evaluasi → Langkah 1 hanya menampilkan mapel dari Setting Kurikulum.
2. Centang "Menggunakan e-Rapor" → Kuota bertambah 5% sesuai akreditasi.
3. Unduh Leger → kolom mapel bersih dari mapel tidak digunakan.

---
## Tabel Mapel Semester, Pemilihan Manual, Penguncian Data & Audit Nilai Rinci
**Waktu**: 13:00 WIB
**Status**: Dieksekusi
**Deskripsi**: Revisi besar PDSS: grid semester (6 kolom), penanganan siswa tidak naik kelas & pindahan, kuota dinamis, override manual BK, penguncian data, dan modal audit nilai per siswa.

### 1. Database Schema

```sql
-- Tabel konfigurasi mapel per semester (6 kolom semester)
DROP TABLE IF EXISTS `pdss_config_mapel`;
CREATE TABLE `pdss_config_mapel` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` CHAR(36) NOT NULL,
    `mapel_id` INT UNSIGNED NOT NULL,
    `sem_1` TINYINT(1) DEFAULT 0,  -- Kelas 10 Ganjil
    `sem_2` TINYINT(1) DEFAULT 0,  -- Kelas 10 Genap
    `sem_3` TINYINT(1) DEFAULT 0,  -- Kelas 11 Ganjil
    `sem_4` TINYINT(1) DEFAULT 0,  -- Kelas 11 Genap
    `sem_5` TINYINT(1) DEFAULT 0,  -- Kelas 12 Ganjil
    `sem_6` TINYINT(1) DEFAULT 0,  -- Kelas 12 Genap
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_tenant_mapel` (`tenant_id`, `mapel_id`),
    CONSTRAINT `fk_pdss_mapel_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pdss_mapel_id` FOREIGN KEY (`mapel_id`) REFERENCES `mata_pelajaran` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Override manual eligible per siswa
CREATE TABLE IF NOT EXISTS `pdss_manual_eligible` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` CHAR(36) NOT NULL,
    `siswa_id` CHAR(36) NOT NULL,
    `status_eligible` ENUM('auto', 'eligible', 'tidak_eligible') DEFAULT 'auto',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_tenant_siswa` (`tenant_id`, `siswa_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Status penguncian data PDSS
CREATE TABLE IF NOT EXISTS `pdss_lock` (
    `tenant_id` CHAR(36) PRIMARY KEY,
    `is_locked` TINYINT(1) DEFAULT 0,
    `locked_by` VARCHAR(255) DEFAULT NULL,
    `locked_at` TIMESTAMP NULL DEFAULT NULL,
    CONSTRAINT `fk_pdss_lock_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### 2. Backend — [PDSSController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/PDSSController.php)

| Method | Deskripsi |
|---|---|
| `apiGetPdssMapels()` | Ambil konfigurasi semester lengkap (sem_1 s.d sem_6) per mapel |
| `apiSavePdssMapels()` | Cegah penyimpanan jika `is_locked = 1` |
| `apiGetKesiapan()` | Ambil data pdss_lock, nilai siswa kelas 12, tangani siswa tidak naik kelas & pindahan |
| `apiSaveManualEligible()` [NEW] | Cegah perubahan jika terkunci. Simpan override BK |
| `apiToggleLock()` [NEW] | Kunci/buka kunci simulasi + catat nama user & waktu |
| `apiGetStudentGrades()` [NEW] | Detail nilai akhir per mapel per semester untuk modal audit |

### 3. Frontend — [pdss_index.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/pdss_index.php)

**Status Bar Lock di Paling Atas:**
- Terkunci: Badge merah **TERKUNCI** + info pengunci + tombol "Buka Kunci Data".
- Terbuka: Badge hijau **DRAFT/TERBUKA** + tombol "Kunci Seluruh Data" (konfirmasi SweetAlert).

**Langkah 1 (Grid Tabel Semester):**
- Tabel dengan kolom: Mata Pelajaran | Sem 1 | Sem 2 | Sem 3 | Sem 4 | Sem 5 | Sem 6
- Semua checkbox `disabled` jika `isLocked = true`.

**Langkah 3 (Override Manual & Audit Nilai):**
- Tombol audit `bi-eye-fill` di sebelah nama siswa → modal audit.
- Tombol Set Eligible/Tidak Eligible `disabled` jika data terkunci.

**Modal Audit Detail Nilai Rapor:**
- Tabel dinamis: Mata Pelajaran × Kolom Semester 1-5
- Tampilkan nilai + tahun ajaran: `88 (2024/2025)` per sel.

### Verification Plan
```powershell
php -l index.php
php -l app/Controllers/PDSSController.php
php -l views/pdss_index.php
```
1. Centang semester mapel Langkah 1 → Klik Simpan.
2. Klik tombol mata audit Langkah 3 → modal menampilkan nilai rinci per semester.
3. Klik "Kunci Seluruh Data" → seluruh input disabled, badge TERKUNCI muncul.
4. Klik "Buka Kunci Data" → input dapat diubah kembali.

---
## Fitur Simulasi Pemilihan Kampus & Prodi PDSS
**Waktu**: 15:00 WIB
**Status**: Dieksekusi
**Deskripsi**: Menambahkan Tab Simulasi baru di PDSS. Siswa mengisi pilihan kampus+prodi per simulasi (1/2/3). BK memantau, mengelola konflik real-time, dan mengekspor hasil. Sistem penguncian berurutan menjaga integritas historis.

### 1. Database — `2026_07_15_00_create_pdss_simulasi_tables.php`

```sql
-- Pilihan simulasi per siswa per tahun ajaran
CREATE TABLE `pdss_simulasi` (
    `id`              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`       CHAR(36) NOT NULL,
    `siswa_id`        CHAR(36) NOT NULL,
    `tahun_ajaran_id` INT UNSIGNED NOT NULL,
    `no_simulasi`     TINYINT(1) NOT NULL,       -- 1, 2, atau 3
    `kampus_id_1`     CHAR(36) DEFAULT NULL,
    `prodi_id_1`      CHAR(36) DEFAULT NULL,
    `kampus_id_2`     CHAR(36) DEFAULT NULL,
    `prodi_id_2`      CHAR(36) DEFAULT NULL,
    `bukti_file`      VARCHAR(512) DEFAULT NULL, -- hanya sim 3
    `bukti_filename`  VARCHAR(255) DEFAULT NULL,
    `bukti_uploaded_at` TIMESTAMP NULL DEFAULT NULL,
    `catatan_siswa`   TEXT DEFAULT NULL,
    `catatan_bk`      TEXT DEFAULT NULL,
    `diisi_oleh`      ENUM('Siswa','Guru BK','Admin') NOT NULL DEFAULT 'Siswa',
    `status`          ENUM('draft','submitted','dikunci') NOT NULL DEFAULT 'draft',
    `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`      TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY `uk_sim_siswa_ta_no` (`tenant_id`, `siswa_id`, `tahun_ajaran_id`, `no_simulasi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Setting buka/tutup/kunci tiap fase simulasi
CREATE TABLE `pdss_simulasi_setting` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`       CHAR(36) NOT NULL,
    `tahun_ajaran_id` INT UNSIGNED NOT NULL,
    `no_simulasi`     TINYINT(1) NOT NULL,
    `is_open`         TINYINT(1) NOT NULL DEFAULT 0,
    `is_locked`       TINYINT(1) NOT NULL DEFAULT 0,
    `dibuka_oleh`     VARCHAR(255) DEFAULT NULL,
    `dibuka_at`       TIMESTAMP NULL DEFAULT NULL,
    `dikunci_oleh`    VARCHAR(255) DEFAULT NULL,
    `dikunci_at`      TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY `uk_sss_tenant_ta_no` (`tenant_id`, `tahun_ajaran_id`, `no_simulasi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 2. Backend — [PDSSController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/PDSSController.php)

| Method | Endpoint | Keterangan |
|---|---|---|
| `apiGetSimulasi()` | `GET /api/v1/pdss/simulasi` | Daftar siswa + pilihan + rank + status konflik |
| `apiSaveSimulasi()` | `POST /api/v1/pdss/simulasi` | Simpan/update pilihan (siswa atau BK) + real-time conflict check |
| `apiDeleteSimulasi()` | `POST /api/v1/pdss/simulasi/delete` | Hapus pilihan seorang siswa |
| `apiUploadBuktiSimulasi()` | `POST /api/v1/pdss/simulasi/upload-bukti` | Upload file bukti (Sim 3, PDF/JPG/PNG max 2MB) |
| `apiGetSimulasiSetting()` | `GET /api/v1/pdss/simulasi/setting` | Status buka/tutup/kunci tiap fase |
| `apiToggleSimulasiSetting()` | `POST /api/v1/pdss/simulasi/setting` | Buka/tutup/kunci fase (khusus BK/admin) |
| `apiExportSimulasi()` | `GET /api/v1/pdss/simulasi/export` | Download Excel hasil simulasi |

**Logika Deteksi Konflik (apiGetSimulasi):**
```
LANGKAH 1 — Hitung Peringkat Eligible (urutan rata_rata DESC)
LANGKAH 2 — Ambil pilihan simulasi (LEFT JOIN master_kampus, master_kampus_prodi)
LANGKAH 3 — Bangun peta konflik:
    key = "{kampus_id}_{prodi_id}"
    $prodiMap[key][] = [siswa_id, nama, kelas, rank, jurusan]
    Urutkan per key berdasarkan rank ASC
    entry[0] = pemilih tertinggi (AMAN)
    entry[1+] = konflik (BERISIKO)
LANGKAH 4 — Tandai is_konflik_1 / is_konflik_2 di output
    konflik_info = { nama_penemu, rank_penemu, kelas_penemu }
```

**Logika Sequential Lock:**
- Simulasi 2 tidak bisa dibuka sebelum Simulasi 1 dikunci.
- Simulasi 3 tidak bisa dibuka sebelum Simulasi 2 dikunci.
- HTTP 400 jika aturan dilanggar, dengan pesan jelas.

### 3. Route Baru — `index.php`
```php
case '/api/v1/pdss/simulasi':
    $ctrl = new App\Controllers\PDSSController();
    $_SERVER['REQUEST_METHOD'] === 'POST' ? $ctrl->apiSaveSimulasi() : $ctrl->apiGetSimulasi();
    break;
case '/api/v1/pdss/simulasi/delete':
    (new App\Controllers\PDSSController())->apiDeleteSimulasi(); break;
case '/api/v1/pdss/simulasi/upload-bukti':
    (new App\Controllers\PDSSController())->apiUploadBuktiSimulasi(); break;
case '/api/v1/pdss/simulasi/setting':
    $ctrl = new App\Controllers\PDSSController();
    $_SERVER['REQUEST_METHOD'] === 'POST' ? $ctrl->apiToggleSimulasiSetting() : $ctrl->apiGetSimulasiSetting();
    break;
case '/api/v1/pdss/simulasi/export':
    (new App\Controllers\PDSSController())->apiExportSimulasi(); break;
```

### 4. Frontend — [NEW] `views/bk/pdss_simulasi_ui.php`

Struktur tampilan:
```
┌─────────────────────────────────────────────────────────┐
│  FILTER: Tahun Ajaran [dropdown] [Terapkan]             │
├─────────────────────────────────────────────────────────┤
│  [Simulasi 1]  [Simulasi 2]  [Simulasi 3]               │
│  Status: TERBUKA ✅ / DITUTUP 🔒                        │
│  [Buka Pengisian]  [Kunci Simulasi]  [Export Excel ↓]   │
├─────────────────────────────────────────────────────────┤
│  Stats Card: [Total Eligible] [Sudah Isi] [Belum Isi]   │
│              [Konflik Prodi: X pasang]                  │
├─────────────────────────────────────────────────────────┤
│  FILTER: [Cari Siswa] [Filter Jurusan] [Konflik Saja]  │
├─────────────────────────────────────────────────────────┤
│  TABEL:                                                 │
│  No | Nama | Kelas | Rank | Jur | Kampus 1 | Prodi 1 │
│       | Kampus 2 | Prodi 2 | Konflik | Bukti | Aksi  │
└─────────────────────────────────────────────────────────┘
```

**Badge Konflik:** `⚠ Konflik` (merah) jika prodi sama dengan siswa lain yang peringkatnya lebih tinggi.
**Upload Bukti Sim 3:** Drag-and-drop PDF/JPG/PNG max 2MB, path disimpan di `uploads/pdss/simulasi/{tenant_id}/{tahun_ajaran_id}/`.

### Alur Kerja Lengkap
```
BK:  [Buka Simulasi 1]
         ↓
Siswa:   Login → PDSS → Tab Simulasi → Isi Kampus & Prodi
         ↓
BK:  [Pantau] → [Lihat konflik] → [Kunci Simulasi 1]
         ↓
BK:  [Buka Simulasi 2] (harus kunci Sim 1 dulu)
         ↓
Siswa:   Perbaiki pilihan di Simulasi 2
         ↓
BK:  [Kunci Simulasi 2] → [Buka Simulasi 3]
         ↓
Siswa:   Isi pilihan final + Upload Bukti
         ↓
BK:  [Kunci Simulasi 3 = Final] → [Export Excel]
```

### Verification Plan
```powershell
php -l app/Controllers/PDSSController.php
php -l index.php
php -l views/pdss_index.php
php -l views/bk/pdss_simulasi_ui.php
php database/run_migrations.php
```
1. Login Guru BK → PDSS → tab "Simulasi Pilihan Kampus" muncul.
2. Buka Sim 1 → isi pilihan 2 siswa dengan prodi sama → badge konflik muncul.
3. Kunci Sim 1 → Buka Sim 2 → data Sim 1 tidak bisa diubah.
4. Sim 3 → Upload PDF → file tersimpan, bisa dilihat kembali.
5. Export Excel → verifikasi kolom: Nama, Kelas, Rank, Kampus 1, Prodi 1, Kampus 2, Prodi 2, Konflik, Bukti.

---
## Kode Prodi Wajib & Pencocokan Duplikat Berbasis Kode Prodi
**Waktu**: 11:00 WIB
**Status**: Dieksekusi
**Deskripsi**: Membuat kolom Kode Prodi wajib diisi (mandatory) saat upload Excel maupun input manual, serta menyempurnakan mekanisme pencocokan duplikat agar eksklusif berbasis Kode Prodi + Kampus ID (bukan nama prodi).

### [MODIFY] `app/Controllers/KampusController.php`

#### Fungsi `apiImportExcel`
- Verifikasi kolom `KODE` di pemetaan awal; jika tidak ada ? throw exception.
- Jika `kodeProdi` baris kosong ? throw exception (user tahu baris mana tidak lengkap).
- Pencocokan duplikat eksklusif via `kode_prodi` + `kampus_id`:
```php
$stmtProdi = $db->prepare("
    SELECT id FROM master_kampus_prodi 
    WHERE kode_prodi = ? AND kampus_id = ?
    LIMIT 1
");
```
- Jika Kode Prodi beda (meski nama sama) ? buat **Prodi Baru** (S1 vs D3 dengan kode berbeda tidak saling menimpa).
- Jika Kode Prodi cocok ? update `program_studi`, `jenjang`, `jenis_portofolio`.

#### Fungsi `apiImportKampusProdi`
- `kode_prodi` masuk ke validasi kolom wajib.
- Query pencocokan prodi ganda:
```php
$stmtFindProdi = $db->prepare("
    SELECT p.id FROM master_kampus_prodi p
    WHERE p.kode_prodi = ? AND p.kampus_id = ?
    LIMIT 1
");
```

#### Fungsi `apiSaveProdi`
- Tambah pengecekan `empty($kode_prodi)` ? kembalikan error `422`.

### [MODIFY] `views/bk/kampus_config_ui.php`
- Tambah tanda bintang `*` pada label input **Kode Prodi**.
- Tambah atribut `required` pada `<input>` Kode Prodi di modal kelola prodi.

### Klarifikasi Menu Hapus di Aplikasi
| Tombol Hapus | Lokasi | Cakupan |
|---|---|---|
| `deleteKampus` | Kolom Aksi halaman Master Kampus | Kampus + seluruh Prodi + Riwayat Kuota (CASCADE) |
| `deleteProdi` | Modal "Kelola Program Studi" | Prodi + riwayat kuotanya |
| `deleteRiwayat` | Sub-tabel riwayat keketatan | Satu baris tahun tertentu |
| `executeBulkDelete` | Header flat-list | Riwayat massal berdasarkan filter tahun/kampus |

### Verification Plan
1. Upload Excel tanpa kolom `KODE` ? sistem menolak dengan pesan error.
2. Upload 2 prodi nama sama, Kode Prodi berbeda ? keduanya terbuat (tidak saling menimpa).
3. Upload ulang dengan Kode Prodi sama, nama berbeda ? data ter-update, tidak terduplikasi.
4. Form simpan prodi kosong Kode ? validasi browser menolak.
