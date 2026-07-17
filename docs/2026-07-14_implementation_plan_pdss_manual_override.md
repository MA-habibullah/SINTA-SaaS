# Rencana Implementasi: Tabel Mapel Semester, Pemilihan Manual, Penguncian Data & Audit Nilai Rinci

Rencana ini merevisi tab **Pangkalan Data (PDSS)** agar:
1. **Langkah 1 (Tabel Mapel):** Mengubah grid pilihan mapel menjadi tabel grid semester (Kelas 10 Sem 1-2, Kelas 11 Sem 1-2, Kelas 12 Sem 1-2) sesuai gambar referensi.
2. **Logika Khusus Siswa:**
   * **Siswa Tidak Naik Kelas:** Menggunakan nilai rapor terbaru (berdasarkan tahun ajaran tertinggi) untuk semester yang sama jika ada duplikasi tingkat kelas.
   * **Siswa Pindahan:** Menghitung rata-rata berdasarkan nilai yang *benar-benar terisi* saja agar nilai tidak jatuh ke 0 karena data semester awal kosong.
3. **Langkah 2 (Informasi Kuota Dinamis):** Menampilkan statistik total siswa kelas 12 aktif, persentase kuota dinamis (bisa diatur manual oleh BK hingga menyesuaikan tambahan kuota 5-10% Kemendikbud), serta jumlah siswa terpilih.
4. **Langkah 3 (Kelayakan Manual BK):** BK dapat memaksa siswa menjadi *Eligible* atau *Tidak Eligible* secara manual (meng-override status otomatis dari sistem) langsung melalui tabel Langkah 3.
5. **Penguncian Data (Lock Data) [NEW]:**
   * BK dapat mengunci data simulasi (Langkah 1 s.d Langkah 3).
   * Ketika terkunci, seluruh pengaturan (checkbox mapel, kuota eligible, dan override manual) dibekukan (disabled).
   * Menampilkan status terkunci beserta informasi pengunci dan waktu penguncian. BK yang memiliki hak akses tulis dapat membukanya kembali.
6. **Audit Nilai Rinci per Siswa [NEW]:**
   * Menambahkan tombol detil nilai (ikon mata) di samping nama siswa pada Langkah 3.
   * Saat diklik, akan membuka modal audit yang merinci nilai akhir beserta tahun ajaran untuk setiap mata pelajaran dan semester yang aktif. Hal ini memudahkan guru BK melakukan verifikasi sebelum data dikunci.

---

## Proposed Changes

### 1. Database Schema
Kita akan menghapus tabel `pdss_config_mapel` lama dan membuat struktur baru, membuat tabel `pdss_manual_eligible`, serta membuat tabel `pdss_lock` untuk status penguncian data.

```sql
-- Tabel konfigurasi mata pelajaran per semester
DROP TABLE IF EXISTS `pdss_config_mapel`;
CREATE TABLE `pdss_config_mapel` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` CHAR(36) NOT NULL,
    `mapel_id` INT UNSIGNED NOT NULL,
    `sem_1` TINYINT(1) DEFAULT 0, -- Kelas 10 Ganjil
    `sem_2` TINYINT(1) DEFAULT 0, -- Kelas 10 Genap
    `sem_3` TINYINT(1) DEFAULT 0, -- Kelas 11 Ganjil
    `sem_4` TINYINT(1) DEFAULT 0, -- Kelas 11 Genap
    `sem_5` TINYINT(1) DEFAULT 0, -- Kelas 12 Ganjil
    `sem_6` TINYINT(1) DEFAULT 0, -- Kelas 12 Genap
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_tenant_mapel` (`tenant_id`, `mapel_id`),
    CONSTRAINT `fk_pdss_mapel_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pdss_mapel_id` FOREIGN KEY (`mapel_id`) REFERENCES `mata_pelajaran` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabel status eligible manual pilihan BK
CREATE TABLE IF NOT EXISTS `pdss_manual_eligible` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` CHAR(36) NOT NULL,
    `siswa_id` CHAR(36) NOT NULL,
    `status_eligible` ENUM('auto', 'eligible', 'tidak_eligible') DEFAULT 'auto',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_tenant_siswa` (`tenant_id`, `siswa_id`),
    CONSTRAINT `fk_pdss_manual_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pdss_manual_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabel status penguncian data PDSS
CREATE TABLE IF NOT EXISTS `pdss_lock` (
    `tenant_id` CHAR(36) PRIMARY KEY,
    `is_locked` TINYINT(1) DEFAULT 0,
    `locked_by` VARCHAR(255) DEFAULT NULL,
    `locked_at` TIMESTAMP NULL DEFAULT NULL,
    CONSTRAINT `fk_pdss_lock_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

---

## Proposed Changes

### 2. Backend Routing & Controller

#### [MODIFY] [index.php](file:///c:/xampp/htdocs/SINTA-SaaS/index.php)
* Daftarkan API endpoints baru:
  * `POST /api/v1/pdss/manual-eligible`
  * `POST /api/v1/pdss/lock`
  * `GET /api/v1/pdss/student-grades`

#### [MODIFY] [PDSSController.php](file:///c:/xampp/htdocs/SINTA-SaaS/app/Controllers/PDSSController.php)
1. **Fungsi Konstruktor (`__construct`):** Tambahkan eksekusi pembuatan tabel `pdss_lock` otomatis beserta perbaikan collation.
2. **Fungsi `apiGetPdssMapels()`:** Ambil konfigurasi semester lengkap untuk masing-masing mapel (`sem_1` s.d `sem_6`).
3. **Fungsi `apiSavePdssMapels()`:** Cegah penyimpanan jika data dalam kondisi terkunci (`is_locked = 1`).
4. **Fungsi `apiGetKesiapan()`:**
   * Ambil data `pdss_lock` untuk memeriksa status terkunci.
   * Ambil nilai siswa kelas 12 aktif.
   * Urutkan nilai per siswa di PHP untuk menangani **siswa tidak naik kelas** (nilai dengan `tahun_ajaran` terbaru terpilih) dan **siswa pindahan** (hitung rata-rata hanya dari nilai terisi).
   * Kembalikan flag `is_locked` dan informasi pengunci.
5. **Fungsi `apiSaveManualEligible()` [NEW]:** Cegah perubahan jika data terkunci. Simpan override pilihan BK.
6. **Fungsi `apiToggleLock()` [NEW]:** Mengunci atau membuka kunci simulasi PDSS. Mencatat nama user (`SessionManager::getUserNama()`) dan waktu penguncian.
7. **Fungsi `apiGetStudentGrades()` [NEW]:**
   * Menerima parameter `siswa_id`.
   * Mengambil detail nilai akhir dan `tahun_ajaran` dari database untuk mata pelajaran yang dikonfigurasi di Langkah 1 untuk semua semester aktif.
   * Menyusun data dengan struktur bersih untuk memudahkan penampilan tabel audit rapor per semester.

---

### 3. Frontend UI Layout

#### [MODIFY] [pdss_index.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/pdss_index.php)
* **Status Bar Lock di Paling Atas:**
  Tampilkan baris alert info:
  * Jika Terkunci: Badge merah/kuning **TERKUNCI** dengan rincian *"Dikunci oleh [Nama] pada [Waktu]"*. Sediakan tombol **"Buka Kunci Data"** untuk BK.
  * Jika Terbuka: Badge hijau **DRAFT/TERBUKA** dan tombol **"Kunci Seluruh Data"** (dengan konfirmasi SweetAlert).
* **Langkah 1 (Tabel Grid Semester):**
  Ubah checkbox list menjadi table layout dengan kolom semester. Jika `isLocked` bernilai `true`, beri atribut `disabled` pada seluruh checkbox.
* **Langkah 2 (Kuota Dinamis):**
  Bekukan selector kuota jika data terkunci. Tampilkan statistik kelas 12.
* **Langkah 3 (Override Manual & Audit Nilai):**
  * Tampilkan tombol audit (ikon mata `bi-eye-fill`) di sebelah nama siswa.
  * Saat tombol audit diklik, tampilkan modal **Audit Detail Nilai Rapor Siswa**.
  * Jika data terkunci, tombol aksi override manual (Set Eligible / Tidak Eligible) akan dinonaktifkan (`disabled`).
* **Modal Audit Detail Nilai Rapor (HTML & Vue):**
  * Buat modal overlay yang menampilkan tabel dinamis berisi mata pelajaran pilihan dan kolom Semester 1 s.d Semester 5.
  * Tampilkan nilai beserta tahun ajaran (misal: `88 (2024/2025)`) pada masing-masing sel yang terisi.

---

## Verification Plan

### Automated Tests
* Jalankan sintaks check untuk menjamin validitas file PHP:
  ```powershell
  php -l index.php
  php -l app/Controllers/PDSSController.php
  php -l views/pdss_index.php
  ```

### Manual Verification
1. Buka halaman PDSS.
2. Centang beberapa semester mapel di Langkah 1. Klik simpan.
3. Klik tombol mata (Audit) di Langkah 3 untuk salah satu siswa. Pastikan modal menampilkan tabel nilai rinci per mapel & semester dengan tahun ajaran yang tepat.
4. Klik **Kunci Seluruh Data** di bagian atas. Pastikan seluruh input di Langkah 1, 2, dan 3 menjadi terkunci (disabled) dan muncul badge **TERKUNCI**.
5. Klik **Buka Kunci Data**, verifikasi bahwa input dapat diubah kembali.
