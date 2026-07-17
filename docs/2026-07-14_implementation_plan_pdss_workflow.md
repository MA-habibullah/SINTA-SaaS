# Rencana Implementasi: Pengaturan Mapel PDSS & Simulasi Ranking 5 Semester

Rencana ini memodifikasi tab **Pangkalan Data (PDSS)** agar guru BK dapat menentukan mata pelajaran kurikulum mana yang digunakan untuk perhitungan PDSS, menghitung rata-rata nilai siswa kelas 12 selama 5 semester hanya untuk mapel terpilih tersebut, dan memfilter ranking paralel berdasarkan Kelas atau Jurusan secara terurut dan sederhana.

## User Review Required
> [!IMPORTANT]
> - Sistem akan secara otomatis mendeteksi semester 1-5 berdasarkan nama kelas yang diikuti siswa saat memperoleh nilai.
> - Rumus Semester:
>   * Semester 1 & 2: Kelas 10 (nama kelas mengandung `10` atau `X`).
>   * Semester 3 & 4: Kelas 11 (nama kelas mengandung `11` or `XI`).
>   * Semester 5: Kelas 12 (nama kelas mengandung `12` or `XII`) semester Ganjil.
> - Kategori "Eligible" dihitung secara dinamis di klien sesuai dengan persentase kuota sekolah (misal: top 40% per jurusan).

---

## Proposed Changes

### 1. Database Schema
Kita akan membuat tabel konfigurasi `pdss_config_mapel` secara otomatis di dalam konstruktor `PDSSController.php` jika belum ada.
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

### 2. Backend Routing & Controller

#### [MODIFY] [index.php](file:///c:/xampp/htdocs/SINTA-SaaS/index.php)
* Tambahkan rute API `/api/v1/pdss/config-mapel` untuk metode GET (mengambil mapel) dan POST (menyimpan pilihan mapel).

#### [MODIFY] [PDSSController.php](file:///c:/xampp/htdocs/SINTA-SaaS/app/Controllers/PDSSController.php)
1. **Fungsi Konstruktor (`__construct`):** Tambahkan eksekusi pembuatan tabel `pdss_config_mapel` otomatis.
2. **Fungsi `apiGetPdssMapels()` [NEW]:** Mengambil daftar semua mata pelajaran aktif di sekolah (tenant) beserta tanda centang (`is_selected`) jika sudah terdaftar di `pdss_config_mapel`.
3. **Fungsi `apiSavePdssMapels()` [NEW]:** Menyimpan pilihan mata pelajaran baru untuk PDSS dalam satu transaksi ACID.
4. **Fungsi `apiGetKesiapan()` [MODIFY]:**
   * Ambil daftar `mapel_id` yang terkonfigurasi. Jika kosong, kembalikan bendera `mapel_not_configured = true`.
   * Ubah kueri SQL pengambilan rata-rata nilai agar mengambil data `detail_nilai_rapor` selama 5 semester dan hanya untuk `mapel_id` terpilih.

---

### 3. Frontend UI Layout

#### [MODIFY] [pdss_index.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/pdss_index.php)
* **Penyempurnaan Struktur Layout Kesiapan:**
  Ubah tampilan tab Kesiapan menjadi alur kerja bernomor yang rapi:
  1. **Langkah 1:** Akordion/Card untuk memilih mata pelajaran kurikulum untuk PDSS (menggunakan grid checkbox).
  2. **Langkah 2:** Simulasi Kuota dan Filter (dilengkapi filter dropdown **Kelas** baru dan filter **Jurusan**).
  3. **Langkah 3:** Tabel Hasil Simulasi Kelayakan & Ranking Paralel.
* **Logika Vue.js:**
  * Hubungkan Vue data dengan endpoint `/api/v1/pdss/config-mapel`.
  * Tambahkan filter `filterClass` pada pencarian frontend.
  * Tampilkan kelengkapan nilai dengan format `X / Y Nilai` (misal: `25 / 30 Nilai` jika terpilih 6 mapel).

---

## Verification Plan

### Automated Tests
* Menjalankan PHP syntax linting untuk memastikan keabsahan file PHP yang dimodifikasi.
  ```powershell
  php -l index.php
  php -l app/Controllers/PDSSController.php
  php -l views/pdss_index.php
  ```

### Manual Verification
1. Masuk sebagai Super Admin atau Guru BK, buka halaman **Pangkalan Data (PDSS)**.
2. Buka **Langkah 1**, centang beberapa mata pelajaran (misal: Matematika Wajib, Bahasa Indonesia), lalu simpan.
3. Buka **Langkah 2**, verifikasi dropdown filter **Kelas** muncul secara otomatis berisi nama-nama kelas 12 yang ada di database.
4. Buka **Langkah 3**, verifikasi bahwa rata-rata nilai terhitung hanya berdasarkan mata pelajaran terpilih untuk 5 semester, dan indikator kelengkapan menampilkan total nilai terisi (misal: `10 / 10 Nilai`).
