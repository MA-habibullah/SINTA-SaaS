# Rencana Implementasi: Rancang Bangun Dynamic Curriculum Engine pada Buku Induk

Rencana ini merancang arsitektur **Future-Proof (Tahan Masa Depan)** untuk Buku Induk, Seting Kurikulum, Input Nilai, dan Cetak Rapor. Dengan pendekatan ini, tipe kurikulum tidak lagi di-hardcode di database, melainkan dikelola secara dinamis lewat tabel referensi kurikulum, dan komponen nilai disimpan secara fleksibel menggunakan format **JSON Payload**.

---

## Proposed Changes

### 1. Database Schema Updates (Future-Proof Migration)

#### [NEW] [2026_07_16_00_create_dynamic_curriculum_tables.php](file:///c:/xampp/htdocs/SINTA-SaaS/database/migrations/2026_07_16_00_create_dynamic_curriculum_tables.php)
* **Tabel Baru `ref_kurikulum`** (Menyimpan daftar kurikulum nasional dan kustom):
  * `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  * `tenant_id` CHAR(36) NULL (Jika NULL berarti kurikulum nasional bawaan sistem, jika UUID tenant berarti kurikulum kustom buatan sekolah tersebut)
  * `nama_kurikulum` VARCHAR(100) NOT NULL (Contoh: "KBK", "KTSP", "Kurikulum 2013", "Kurikulum Merdeka", "Kurikulum Cambridge")
  * `tipe_penilaian` ENUM('klasik', 'kompleks', 'sederhana', 'custom') NOT NULL DEFAULT 'sederhana' (Menentukan arsitektur layout input/output rapor)
  * `is_active` TINYINT(1) DEFAULT 1
  * `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  * CONSTRAINT `fk_ref_kur_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
  * UNIQUE KEY `uq_ref_kur_name` (`tenant_id`, `nama_kurikulum`)

* **Tabel Baru `kelas_kurikulum`** (Pemetaan historis kurikulum pada kelas per tahun ajaran):
  * `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  * `tenant_id` CHAR(36) NOT NULL
  * `kelas_id` INT UNSIGNED NOT NULL
  * `tahun_ajaran` VARCHAR(50) NOT NULL
  * `kurikulum_id` INT UNSIGNED NOT NULL (Menunjuk ke `ref_kurikulum.id`)
  * `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  * CONSTRAINT `fk_kelas_kur_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
  * CONSTRAINT `fk_kelas_kur_kelas` FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`id`) ON DELETE CASCADE
  * CONSTRAINT `fk_kelas_kur_ref` FOREIGN KEY (`kurikulum_id`) REFERENCES `ref_kurikulum` (`id`) ON DELETE CASCADE
  * UNIQUE KEY `uq_kelas_kur_sync` (`tenant_id`, `kelas_id`, `tahun_ajaran`)

* **Kolom Baru pada Tabel `detail_nilai_rapor`**:
  * **`nilai_detail_json` JSON DEFAULT NULL**
    * Menyimpan komponen nilai secara fleksibel tanpa perlu mengubah struktur tabel di masa depan jika kurikulum nasional berubah lagi.
    * *Struktur JSON KTSP*: `{"kognitif": 85, "psikomotorik": 80, "afektif": "A"}`
    * *Struktur JSON K-13*: `{"pengetahuan_nilai": 82, "pengetahuan_predikat": "B", "pengetahuan_deskripsi": "...", "keterampilan_nilai": 85, "keterampilan_predikat": "A", "keterampilan_deskripsi": "..."}`
    * *Struktur JSON Merdeka*: `{"deskripsi_tertinggi": "...", "deskripsi_terendah": "..."}`
    * *Struktur JSON Cambridge*: `{"exam_score": 90, "coursework_score": 85, "grade_letter": "A*"}`
  * **`kkm` DECIMAL(5,2) DEFAULT NULL** (Standar KKM / KKTP kelulusan mapel)

* **Tabel Baru `nilai_sikap_k13`** (Nilai Karakter KI-1/KI-2 global per semester):
  * `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  * `tenant_id` CHAR(36) NOT NULL
  * `siswa_id` CHAR(36) NOT NULL
  * `tahun_ajaran` VARCHAR(50) NOT NULL
  * `semester` VARCHAR(20) NOT NULL
  * `predikat_spiritual` VARCHAR(10) DEFAULT NULL
  * `deskripsi_spiritual` TEXT DEFAULT NULL
  * `predikat_sosial` VARCHAR(10) DEFAULT NULL
  * `deskripsi_sosial` TEXT DEFAULT NULL
  * CONSTRAINT `fk_sikap_k13_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
  * CONSTRAINT `fk_sikap_k13_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE
  * UNIQUE KEY `uq_sikap_k13_sync` (`tenant_id`, `siswa_id`, `tahun_ajaran`, `semester`)

---

## Backend Logic (Controllers)

### 1. [KurikulumController.php](file:///c:/xampp/htdocs/SINTA-SaaS/app/Controllers/KurikulumController.php)
* **GET `/api/v1/kurikulum`**:
  * Mengambil list pilihan kurikulum dinamis dari `ref_kurikulum` (baik global maupun kustom milik tenant tersebut).
  * Mengambil kurikulum yang saat ini aktif untuk kelas terpilih dari `kelas_kurikulum`.
* **POST `/api/v1/kurikulum`**:
  * Menyimpan pemetaan mapel sekaligus relasi `kurikulum_id` kelas pada tahun ajaran tersebut di tabel `kelas_kurikulum`.

### 2. [NilaiRaporController.php](file:///c:/xampp/htdocs/SINTA-SaaS/app/Controllers/NilaiRaporController.php)
* **GET `/api/v1/nilai-rapor/grid`**:
  * Membaca `tipe_penilaian` dari kurikulum yang terikat pada kelas.
  * Mengurai data di kolom JSON `nilai_detail_json` menjadi key-value flat agar siap dirender secara dinamis di form input frontend.
* **POST `/api/v1/nilai-rapor/save`**:
  * Menerima payload komponen nilai, mengemasnya menjadi string JSON, dan menyimpannya ke kolom `nilai_detail_json` pada tabel `detail_nilai_rapor` bersamaan dengan `nilai_akhir` utama.

---

## Frontend Views (`views/buku_induk.php`)

### 1. [buku_induk.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/buku_induk.php)
* **Tab 2: Seting Kurikulum**:
  * Mengubah dropdown kurikulum statis menjadi dinamis (di-render dari `jenjangOptions`/`kurikulumList` hasil request ke `/api/v1/kurikulum` yang memuat data `ref_kurikulum`).
* **Tab 3: Input Nilai Rapor**:
  * Form input render dinamis berdasarkan properti `tipe_penilaian` kurikulum:
    * Jika `klasik`: Menampilkan input KKM, Kognitif, Psikomotorik, Afektif.
    * Jika `kompleks`: Menampilkan input KKM, Pengetahuan (Nilai & Deskripsi), Keterampilan (Nilai & Deskripsi).
    * Jika `sederhana`: Menampilkan input Nilai Akhir, Deskripsi Capaian Tertinggi, Deskripsi Capaian Terendah.
* **Tab 4: Cetak Buku Induk**:
  * Sistem cetak membaca `tipe_penilaian` dan memanggil sub-template PDF yang sesuai secara dinamis.

---

## Verification Plan

### Manual Verification
1. **Uji Penambahan Kurikulum Baru (Custom)**:
   * Masukkan record kurikulum baru di tabel `ref_kurikulum` (misal: "Kurikulum Cambridge", tipe: "klasik").
   * Buka Seting Kurikulum, pastikan "Kurikulum Cambridge" muncul di pilihan dropdown.
2. **Uji Reaktivitas Form**:
   * Petakan kelas 10 ke "Kurikulum Cambridge", lalu buka menu Input Rapor. Pastikan layout otomatis menyesuaikan dengan format `klasik` (Kognitif, Psikomotorik, Afektif).
3. **Uji Penyimpanan JSON**:
   * Isi nilai siswa, simpan, lalu periksa isi kolom `nilai_detail_json` di database untuk memastikan format data terenkapsulasi secara sempurna dalam skema JSON.
