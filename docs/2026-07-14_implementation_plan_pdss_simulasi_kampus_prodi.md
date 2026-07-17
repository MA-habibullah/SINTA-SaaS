# Rencana Implementasi: Fitur Simulasi Pemilihan Kampus & Prodi PDSS

## Latar Belakang

Fitur ini menambahkan **Tab Simulasi** baru di dalam modul PDSS yang memungkinkan:
1. **Siswa** mengisi pilihan kampus dan prodi yang diminati (Simulasi 1, 2, dan 3)
2. **BK** memantau, mengelola, menganalisis distribusi pilihan, dan mendeteksi konflik antar siswa
3. Setiap tahun ajaran memiliki data simulasi **terpisah** sehingga simulasi tahun lalu tidak tertimpa

---

## Open Questions (Perlu Konfirmasi Anda)

> [!IMPORTANT]
> **Q1 — Siapa yang boleh input Simulasi?**
> Apakah siswa dapat mengisi sendiri melalui akun siswa, atau **hanya BK yang menginput** atas nama siswa? Saya rekomendasikan **keduanya**: siswa isi sendiri, BK bisa edit/override.

> [!IMPORTANT]
> **Q2 — Batas waktu pengisian tiap simulasi?**
> Apakah BK perlu mengatur batas tanggal buka/tutup untuk tiap simulasi? Atau pengisian bebas kapan saja selama tidak dikunci?

> [!IMPORTANT]
> **Q3 — Jumlah pilihan per simulasi**
> Tiap siswa mengisi berapa pilihan kampus+prodi per simulasi? Saya rekomendasikan **maksimal 2 pilihan** (Pilihan 1 dan Pilihan 2) per simulasi, sesuai aturan SNBP.

> [!NOTE]
> **Q4 — Konflik Pilihan Sama (SUDAH DIKONFIRMASI)** ✅
> **Aturan final:** Setiap kombinasi Kampus + Prodi idealnya hanya dimiliki **satu pemilih**. Jika siswa B (peringkat lebih rendah) memilih prodi + kampus yang **sudah dipilih siswa A** (peringkat lebih tinggi), maka:
> - Sistem **menampilkan peringatan** yang jelas: *"Kampus & Prodi ini sudah dipilih oleh: Siswa A — Peringkat #10 — Kelas 12.3.1"*
> - Siswa B **tetap bisa melanjutkan** dan menyimpan pilihannya (tidak diblokir)
> - Baris Siswa B di tabel BK mendapat badge `⚠ Konflik` berwarna merah
> - BK bisa melihat siapa yang konflik, siapa yang peringkatnya lebih tinggi, dan memberikan saran kepada siswa

---

## Rekomendasi Sistem (Tambahan dari Saya)

> [!TIP]
> **Rekomendasi 1 — Sistem Kuota Prodi per Angkatan**
> Tambahkan kolom `daya_tampung` dari `kampus_prodi_riwayat`. Sistem otomatis menghitung berapa persen daya tampung prodi yang "terisi" jika semua eligible siswa yang memilih prodi tersebut benar-benar diterima. Visualisasikan dengan progress bar.

> [!TIP]
> **Rekomendasi 2 — Badge Konflik Merah + Tooltip Detail**
> Baris siswa yang memilih prodi + kampus **identik** dengan siswa lain (yang peringkatnya lebih tinggi) diberi badge merah `⚠ Konflik`. Saat BK hover/klik badge tersebut, muncul **tooltip/popover** berisi:
> - *"Sudah dipilih oleh: [Nama Siswa A]"*
> - *"Peringkat Eligible: #10"*
> - *"Kelas: 12.3.1"*
> Siswa dengan peringkat lebih rendah yang memilih sama ditandai sebagai pihak **berisiko konflik**, sedangkan siswa peringkat lebih tinggi tetap aman (tidak ada badge di barisnya).

> [!TIP]
> **Rekomendasi 3 — Lock Simulasi 1 Sebelum Buka Simulasi 2**
> Terapkan sistem penguncian berurutan (sequential lock): Simulasi 1 harus dikunci dulu sebelum Simulasi 2 bisa dibuka, begitu pula Simulasi 2 → Simulasi 3. Ini menjaga integritas historis data.

> [!TIP]
> **Rekomendasi 4 — Export Excel Hasil Simulasi**
> BK dapat mengunduh Excel berisi: Nama Siswa, Kelas, Peringkat Eligible, Jurusan, Kampus Pilihan 1 & 2, Prodi Pilihan 1 & 2, Status Konflik.

> [!TIP]
> **Rekomendasi 5 — Notif Siswa Belum Isi**
> Tampilkan counter di header tab: berapa siswa eligible yang belum mengisi simulasi aktif saat ini.

---

## Proposed Changes

---

### 1. Database — Migration Baru

#### [NEW] `2026_07_15_00_create_pdss_simulasi_tables.php`

**Tabel baru yang akan dibuat:**

```sql
-- Tabel utama: pilihan simulasi per siswa per tahun ajaran
CREATE TABLE `pdss_simulasi` (
    `id`              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`       CHAR(36) NOT NULL,
    `siswa_id`        CHAR(36) NOT NULL,
    `tahun_ajaran_id` INT UNSIGNED NOT NULL,
    `no_simulasi`     TINYINT(1) NOT NULL,       -- 1, 2, atau 3
    -- Pilihan 1
    `kampus_id_1`     CHAR(36) DEFAULT NULL,     -- FK ke master_kampus
    `prodi_id_1`      CHAR(36) DEFAULT NULL,     -- FK ke master_kampus_prodi
    -- Pilihan 2
    `kampus_id_2`     CHAR(36) DEFAULT NULL,
    `prodi_id_2`      CHAR(36) DEFAULT NULL,
    -- Bukti upload (hanya untuk simulasi 3)
    `bukti_file`      VARCHAR(512) DEFAULT NULL,
    `bukti_filename`  VARCHAR(255) DEFAULT NULL,
    `bukti_uploaded_at` TIMESTAMP NULL DEFAULT NULL,
    -- Metadata
    `catatan_siswa`   TEXT DEFAULT NULL,
    `catatan_bk`      TEXT DEFAULT NULL,
    `diisi_oleh`      ENUM('Siswa','Guru BK','Admin') NOT NULL DEFAULT 'Siswa',
    `status`          ENUM('draft','submitted','dikunci') NOT NULL DEFAULT 'draft',
    `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`      TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY `uk_sim_siswa_ta_no` (`tenant_id`, `siswa_id`, `tahun_ajaran_id`, `no_simulasi`),
    INDEX `idx_sim_tenant_ta`       (`tenant_id`, `tahun_ajaran_id`),
    INDEX `idx_sim_siswa`           (`siswa_id`),
    INDEX `idx_sim_no_simulasi`     (`no_simulasi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Simulasi Pilihan Kampus & Prodi Siswa per Tahun Ajaran';

-- Tabel setting: kontrol buka/tutup tiap simulasi
CREATE TABLE `pdss_simulasi_setting` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`       CHAR(36) NOT NULL,
    `tahun_ajaran_id` INT UNSIGNED NOT NULL,
    `no_simulasi`     TINYINT(1) NOT NULL,       -- 1, 2, atau 3
    `is_open`         TINYINT(1) NOT NULL DEFAULT 0,
    `is_locked`       TINYINT(1) NOT NULL DEFAULT 0,
    `dibuka_oleh`     VARCHAR(255) DEFAULT NULL,
    `dibuka_at`       TIMESTAMP NULL DEFAULT NULL,
    `dikunci_oleh`    VARCHAR(255) DEFAULT NULL,
    `dikunci_at`      TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY `uk_sss_tenant_ta_no` (`tenant_id`, `tahun_ajaran_id`, `no_simulasi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Setting buka/tutup/kunci tiap fase simulasi PDSS';
```

---

### 2. Backend — PDSSController.php

#### [MODIFY] [PDSSController.php](file:///c:/xampp/htdocs/SINTA-SaaS/app/Controllers/PDSSController.php)

**Method baru yang akan ditambahkan:**

| Method | Endpoint | Keterangan |
|--------|----------|-----------|
| `apiGetSimulasi()` | `GET /api/v1/pdss/simulasi` | Ambil semua pilihan siswa + peringkat eligible + status konflik untuk satu tahun ajaran + nomor simulasi |
| `apiSaveSimulasi()` | `POST /api/v1/pdss/simulasi` | Simpan/update pilihan kampus+prodi seorang siswa (oleh siswa sendiri atau BK) |
| `apiDeleteSimulasi()` | `POST /api/v1/pdss/simulasi/delete` | Hapus pilihan seorang siswa |
| `apiUploadBuktiSimulasi()` | `POST /api/v1/pdss/simulasi/upload-bukti` | Upload file bukti (khusus Simulasi 3, hanya PDF/JPG/PNG, max 2MB) |
| `apiGetSimulasiSetting()` | `GET /api/v1/pdss/simulasi/setting` | Ambil status buka/tutup/kunci tiap fase simulasi |
| `apiToggleSimulasiSetting()` | `POST /api/v1/pdss/simulasi/setting` | Buka/tutup/kunci fase simulasi (khusus BK/admin) |
| `apiExportSimulasi()` | `GET /api/v1/pdss/simulasi/export` | Download Excel hasil simulasi |

**Logika inti `apiGetSimulasi()` — Termasuk Deteksi Konflik:**
```
LANGKAH 1 — Hitung Peringkat Eligible
  - Ambil semua siswa eligible cohort tahun ajaran terpilih
    (sama seperti apiGetKesiapan, sudah terurut rata_rata DESC)
  - Beri rank_paralel per jurusan (Rank #1, #2, #3, ...)

LANGKAH 2 — Ambil Pilihan Simulasi
  - LEFT JOIN pdss_simulasi ON siswa_id + tahun_ajaran_id + no_simulasi
  - LEFT JOIN master_kampus untuk nama_kampus pilihan 1 & 2
  - LEFT JOIN master_kampus_prodi untuk nama_prodi pilihan 1 & 2

LANGKAH 3 — Bangun Peta Konflik (KUNCI UTAMA)
  - Buat array $prodiMap = []
  - Loop setiap siswa, untuk tiap pilihannya:
      key = "{kampus_id}_{prodi_id}"
      $prodiMap[key][] = [
          'siswa_id'    => ...,
          'nama'        => ...,
          'kelas'       => ...,
          'rank'        => ...,    ← peringkat eligible
          'jurusan'     => ...,
      ]
  - Urutkan setiap entry $prodiMap[key] berdasarkan rank ASC
    → entry [0] = pemilih peringkat tertinggi (aman)
    → entry [1+] = pemilih konflik (berisiko)

LANGKAH 4 — Tandai Konflik di Output
  - Loop lagi setiap siswa, cek apakah ia ada di [1+] dalam $prodiMap
  - Jika ya, tandai:
      is_konflik_1 = true
      konflik_info_1 = {
          nama_penemu: 'Siswa A',
          rank_penemu: 10,
          kelas_penemu: '12.3.1'
      }
  - Jika tidak (rank tertinggi), tidak ada badge konflik

CONTOH NYATA:
  Siswa A (Rank #10) → UNAIR / Teknik Informatika → AMAN (pilih pertama)
  Siswa B (Rank #20) → UNAIR / Teknik Informatika → ⚠ KONFLIK
      Pesan: "Sudah dipilih oleh: Siswa A — Peringkat #10 — Kelas 12.3.1"

LANGKAH 5 — Return
  [{
      siswa_id, nama, kelas, jurusan, rank_eligible,
      kampus_1, prodi_1, is_konflik_1, konflik_info_1: {nama, rank, kelas},
      kampus_2, prodi_2, is_konflik_2, konflik_info_2: {nama, rank, kelas},
      bukti_file (jika sim 3), status
  }]
```

**Logika inti `apiSaveSimulasi()` — Real-time Conflict Check:**
```
1. Saat siswa/BK submit pilihan baru, sistem langsung cek apakah
   kombinas kampus+prodi tersebut sudah diambil siswa lain
2. Jika sudah diambil oleh siswa peringkat lebih tinggi:
   → Simpan tetap berhasil (tidak diblokir)
   → Response menyertakan warning:
      {
        success: true,
        warning: true,
        conflict_message: "Perhatian: Prodi ini sudah dipilih oleh
                          ADINDA NAYLA SYAHRANI (Peringkat #10, Kelas 12.3.1).
                          Pilihan Anda tetap tersimpan."
      }
3. Frontend menampilkan SweetAlert tipe 'warning' dengan pesan tersebut
```


---

### 3. Backend — Route baru di index.php

#### [MODIFY] [index.php](file:///c:/xampp/htdocs/SINTA-SaaS/index.php)

Tambahkan 7 route baru di blok `switch($path)`:

```php
case '/api/v1/pdss/simulasi':
    $ctrl = new App\Controllers\PDSSController();
    $_SERVER['REQUEST_METHOD'] === 'POST'
        ? $ctrl->apiSaveSimulasi()
        : $ctrl->apiGetSimulasi();
    break;

case '/api/v1/pdss/simulasi/delete':
    (new App\Controllers\PDSSController())->apiDeleteSimulasi();
    break;

case '/api/v1/pdss/simulasi/upload-bukti':
    (new App\Controllers\PDSSController())->apiUploadBuktiSimulasi();
    break;

case '/api/v1/pdss/simulasi/setting':
    $ctrl = new App\Controllers\PDSSController();
    $_SERVER['REQUEST_METHOD'] === 'POST'
        ? $ctrl->apiToggleSimulasiSetting()
        : $ctrl->apiGetSimulasiSetting();
    break;

case '/api/v1/pdss/simulasi/export':
    (new App\Controllers\PDSSController())->apiExportSimulasi();
    break;
```

---

### 4. Frontend — pdss_index.php

#### [MODIFY] [pdss_index.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/pdss_index.php)

**A. Tab baru di navigasi:**
```html
<!-- Tambah setelah tab master_jalur -->
<li class="nav-item">
    <button class="nav-link ..." @click="activeTab = 'simulasi'">
        <i class="bi bi-mortarboard me-2"></i> Simulasi Pilihan Kampus
    </button>
</li>
```

**B. Konten tab Simulasi (include file terpisah):**
```html
<template v-if="activeTab === 'simulasi'">
    <?php include __DIR__ . '/bk/pdss_simulasi_ui.php'; ?>
</template>
```

**C. Vue data baru:**
```javascript
// Simulasi state
activeSimulasi: 1,           // Tab simulasi aktif: 1, 2, atau 3
simulasiData: [],            // Daftar siswa + pilihan + peringkat
simulasiSettings: {},        // { 1: {is_open, is_locked}, 2: {...}, 3: {...} }
loadingSimulasi: false,
filterSimulasi: {
    search: '',
    jurusan_id: '',
    status_konflik: '',       // '' | 'konflik' | 'aman'
    sudah_isi: '',            // '' | 'sudah' | 'belum'
},
modalSimulasi: {
    show: false,
    siswa: null,
    form: {
        kampus_id_1: '', prodi_id_1: '',
        kampus_id_2: '', prodi_id_2: '',
        catatan_siswa: ''
    }
},
```

**D. Watcher baru di `activeTab`:**
```javascript
} else if (newVal === 'simulasi') {
    this.fetchSimulasi();
    this.fetchSimulasiSettings();
}
```

---

### 5. Frontend — View Baru

#### [NEW] `views/bk/pdss_simulasi_ui.php`

Struktur tampilan:

```
┌─────────────────────────────────────────────────────────┐
│  FILTER: Tahun Ajaran [dropdown] [Terapkan]             │
├─────────────────────────────────────────────────────────┤
│  [Simulasi 1]  [Simulasi 2]  [Simulasi 3]               │
│  Status: TERBUKA ✅ / DITUTUP 🔒                        │
│  [Buka Pengisian]  [Kunci Simulasi]  [Export Excel ↓]   │
├─────────────────────────────────────────────────────────┤
│  Stats Card:                                            │
│  [Total Eligible: 120] [Sudah Isi: 80] [Belum Isi: 40] │
│  [Konflik Prodi: 5 pasang]                              │
├─────────────────────────────────────────────────────────┤
│  FILTER: [Cari Siswa] [Filter Jurusan] [Konflik Saja]  │
├─────────────────────────────────────────────────────────┤
│  TABEL:                                                 │
│  No | Nama Siswa | Kelas | Rank Eligible | Jur |       │
│     | Kampus Pilih 1 | Prodi 1 | Kampus 2 | Prodi 2   │
│     | Status Konflik | Bukti (Sim 3) | Aksi           │
└─────────────────────────────────────────────────────────┘
```

**Fitur Detail Tab:**

**Simulasi 1 & 2:**
- Tabel daftar siswa eligible + pilihan kampus+prodi
- Tombol "Isi/Edit Pilihan" per baris → modal form
- Badge `⚠ Konflik` jika pilihan prodi sama dengan siswa lain
- Badge `✅ Aman` jika tidak ada konflik

**Simulasi 3 (Khusus — Upload Bukti):**
- Semua fitur Simulasi 1 & 2 +
- Kolom tambahan "Bukti Upload" berisi:
  - Icon file PDF/gambar jika sudah upload
  - Tombol "Upload Bukti" jika belum
  - Tombol "Lihat Bukti" jika sudah ada
- Upload melalui modal dengan drag-and-drop area
- Format yang diizinkan: PDF, JPG, PNG (max 2MB)

**Modal Isi Pilihan:**
```
┌──────────────────────────────────┐
│  Pilihan Kampus & Prodi Siswa   │
│  Nama: [ADINDA NAYLA SYAHRANI]  │
│  Rank Eligible: #3 (IPA)        │
├──────────────────────────────────┤
│  PILIHAN 1                      │
│  Kampus: [dropdown kampus]      │
│  Prodi:  [dropdown prodi]       │
│  [⚠ Konflik: 3 siswa lain pilih ini] │
├──────────────────────────────────┤
│  PILIHAN 2 (Opsional)           │
│  Kampus: [dropdown kampus]      │
│  Prodi:  [dropdown prodi]       │
├──────────────────────────────────┤
│  Catatan: [textarea]            │
│                  [Batal] [Simpan]│
└──────────────────────────────────┘
```

---

### 6. Upload File — Storage

#### [NEW] Folder `uploads/pdss/simulasi/`

```
uploads/
  pdss/
    simulasi/
      {tenant_id}/
        {tahun_ajaran_id}/
          sim3_{siswa_id}_{timestamp}.pdf
```

- Nama file di-generate ulang (bukan original filename) untuk keamanan
- Kolom `bukti_file` di tabel `pdss_simulasi` menyimpan path relatif
- Akses file melalui endpoint khusus (bukan akses langsung ke folder) untuk otorisasi

---

## Alur Kerja Lengkap

```
BK:  [Buka Simulasi 1]
         ↓
Siswa:   Login → Masuk PDSS → Tab Simulasi → Isi Kampus & Prodi
         ↓
BK:  [Pantau] → [Lihat konflik] → [Kunci Simulasi 1]
         ↓
BK:  [Buka Simulasi 2] (harus kunci Sim 1 dulu)
         ↓
Siswa:   Isi ulang / perbaiki pilihan di Simulasi 2
         ↓
BK:  [Kunci Simulasi 2] → [Buka Simulasi 3]
         ↓
Siswa:   Isi pilihan final + Upload Bukti Dokumen
         ↓
BK:  [Verifikasi Bukti Upload] → [Kunci Simulasi 3 = Final]
         ↓
Output:  Export Excel Final untuk keperluan PDSS resmi
```

---

## Verification Plan

### Automated Tests
```powershell
# Syntax check semua file yang dimodifikasi
php -l app/Controllers/PDSSController.php
php -l index.php
php -l views/pdss_index.php
php -l views/bk/pdss_simulasi_ui.php

# Jalankan migrasi
php database/run_migrations.php
```

### Manual Verification
1. Login sebagai **Guru BK** → buka PDSS → pastikan tab "Simulasi Pilihan Kampus" muncul
2. Buka Simulasi 1 → isi pilihan untuk beberapa siswa → pastikan data tersimpan
3. Isi 2 siswa dengan prodi yang sama → pastikan badge konflik `⚠` muncul di keduanya
4. Kunci Simulasi 1 → buka Simulasi 2 → pastikan data Sim 1 tidak bisa diubah lagi
5. Masuk ke Simulasi 3 → upload file PDF → pastikan file tersimpan dan dapat dilihat kembali
6. Ganti tahun ajaran via filter → pastikan data simulasi berbeda (tidak tercampur)
7. Export Excel → buka file → verifikasi kolom: Nama, Kelas, Rank, Kampus 1, Prodi 1, Kampus 2, Prodi 2, Konflik, Bukti
