# Proposal Desain: Rekomendasi Fitur Buku Induk, Kurikulum Kustom, & Manajemen Nilai Ujian Sekolah/Ijazah

Dokumen ini merinci rekomendasi lanjutan dan cetak biru arsitektur untuk pengembangan fitur Buku Induk, pembuatan **Kurikulum Kustom (Custom Curriculum)**, serta penanganan khusus **Nilai Ujian Sekolah (US) & Nilai Ijazah** untuk siswa tingkat akhir (Kelas 12).

---

## 1. Rekomendasi Lanjutan untuk Alur Buku Induk

### A. Seting Kurikulum (Tab 2)
* **Mass Copy (Salin Kurikulum Massal)**: Tombol di header untuk menyalin seluruh pemetaan kelompok dan mata pelajaran dari satu kelas contoh (misal: 10 IPS 1) ke kelas-kelas paralel lainnya (10 IPS 2, 10 IPS 3) dalam satu tahun ajaran dengan sekali klik.
* **Master Kurikulum Validator**: Peringatan reaktif jika ada mata pelajaran wajib nasional yang belum dipetakan di kelas tersebut berdasarkan database referensi kurikulum.

### B. Input Nilai Rapor (Tab 3)
* **Auto-Calculate Predikat**: Integrasi formula konversi otomatis dari nilai angka ke predikat huruf (A, B, C, D) berdasarkan interval KKM yang aktif di kelas tersebut, mengurangi beban kerja guru mengetik predikat manual di modal.
* **Template Impor Excel Adaptif**: File Excel unduhan template impor otomatis menghasilkan kolom yang dinamis (misal, memuat kolom KI-3/KI-4 untuk K-13, atau kompetensi tertinggi/terendah untuk Merdeka) sesuai dengan tipe kurikulum aktif kelas bersangkutan.

### C. Cetak Buku Induk (Tab 4)
* **Transkrip Konsolidasi Multi-Semester**: Halaman khusus untuk mencetak rangkuman/transkrip nilai siswa dari semester 1 s.d 6 secara komparatif dalam satu lembar folio landscape. Fitur ini sangat penting saat siswa kelas 12 lulus dan ingin mendaftar ke perguruan tinggi.

---

## 2. Rencana Arsitektur & Alur Kurikulum Kustom

Untuk mengakomodasi kebutuhan sekolah yang menggunakan kurikulum di luar standar nasional (misalnya: *Cambridge Curriculum*, *IB (International Baccalaureate)*, atau kurikulum keagamaan lokal pesantren), sistem didesain secara multi-tenant menggunakan data referensi dinamis.

### A. Struktur Skema Database
Sistem menggunakan tabel `ref_kurikulum` yang sudah dimigrasikan:
* Kolom `tenant_id` bernilai `NULL` untuk kurikulum nasional (tersedia untuk semua sekolah).
* Kolom `tenant_id` diisi dengan `UUID` tenant/sekolah bersangkutan untuk kurikulum kustom (hanya tampak dan bisa dipilih oleh sekolah pembuat).

### B. Alur Pembuatan Kurikulum Kustom di Aplikasi (Flow & UI)
1. **Menu Baru**: Membuat halaman **"Master Kurikulum Sekolah"** (di bawah menu Seting Kelembagaan / Referensi).
2. **Form Pembuatan**:
   * Nama Kurikulum: (Contoh: *"Cambridge IGCSE Science"*)
   * Tipe Penilaian:
     * `sederhana` (Nilai Akhir + Deskripsi Capaian)
     * `klasik` (Kognitif + Psikomotorik)
     * `kompleks` (Pengetahuan + Keterampilan + Sikap)
     * `kustom` (Mendefinisikan komponen dan bobot sendiri, misal: *Exam (60%)*, *Coursework (40%)*)
3. **Penyimpanan**: Sistem menyimpan data ke database tabel `ref_kurikulum` dengan `tenant_id` aktif dari session operator sekolah.
4. **Penerapan**: Pada halaman **Buku Induk -> Tab Seting Kurikulum**, kurikulum kustom ini akan otomatis muncul pada dropdown pilihan kurikulum kelas.

```
+--------------------------------------------------------+
|              MENU: MASTER KURIKULUM                    |
+--------------------------------------------------------+
|                                                        |
|  [+] Tambah Kurikulum Kustom                           |
|                                                        |
|  Nama Kurikulum: [ Cambridge Primary Math ]           |
|  Tipe Penilaian: (o) Sederhana  ( ) Klasik  ( ) Kompleks|
|                                                        |
|  [ SIMPAN KURIKULUM ]                                  |
+--------------------------------------------------------+
                           |
                           v
Dropdown pilihan kurikulum pada kelas otomatis ter-update!
```

---

## 3. Solusi Manajemen Nilai Ujian Sekolah & Nilai Ijazah (Kelas 12)

Siswa kelas 12 (tingkat akhir) memiliki 3 jenis penilaian utama sebelum kelulusan:
1. **Nilai Rapor Semester Ganjil (Semester 5)**
2. **Nilai Rapor Semester Genap (Semester 6)**
3. **Nilai Ujian Sekolah (US)** (untuk komponen ijazah)

Sesuai regulasi nasional, nilai yang tertulis di Lembar Belakang Ijazah adalah **Nilai Sekolah** yang dihitung dari persentase formulasi nilai rapor rata-rata semester 1-5 dengan Nilai Ujian Sekolah (US).

### A. Desain Database Baru (`nilai_ujian_sekolah`)
Kita tambahkan tabel baru untuk menyimpan nilai US dan hasil akhir nilai ijazah per mapel siswa:

```sql
CREATE TABLE `nilai_ujian_sekolah` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` CHAR(36) NOT NULL,
    `siswa_id` CHAR(36) NOT NULL,
    `mapel_id` INT UNSIGNED NOT NULL,
    `tahun_ajaran` VARCHAR(50) NOT NULL,
    `nilai_rapor_rata` DECIMAL(5,2) DEFAULT NULL, -- Rata-rata Nilai Rapor Sem 1-5
    `nilai_ujian_sekolah` DECIMAL(5,2) DEFAULT NULL, -- Nilai Ujian Sekolah (US)
    `nilai_ijazah` DECIMAL(5,2) DEFAULT NULL, -- Nilai Akhir Ijazah (kalkulasi formula)
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_nus_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_nus_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_nus_mapel` FOREIGN KEY (`mapel_id`) REFERENCES `mata_pelajaran` (`id`) ON DELETE CASCADE,
    UNIQUE KEY `uq_nus_siswa_mapel` (`tenant_id`, `siswa_id`, `mapel_id`, `tahun_ajaran`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### B. Konfigurasi Formula Nilai Ijazah (Setting Tenant)
Di halaman Seting Sekolah, kita tambahkan input konfigurasi bobot kontribusi nilai:
* **Bobot Rata-rata Rapor (Smt 1-5)**: (Contoh: `60%`)
* **Bobot Ujian Sekolah (US)**: (Contoh: `40%`)
* *Formula Kalkulasi otomatis di backend/frontend*:
  $$\text{Nilai Ijazah} = (\text{Bobot Rapor} \times \text{Nilai Rapor Rata-rata}) + (\text{Bobot US} \times \text{Nilai US})$$

### C. Alur Kerja (Workflow) Guru & Operator Sekolah
1. **Menu Input Nilai Kelulusan**: Menyediakan halaman khusus **"Input Nilai Kelulusan & Ijazah"** yang hanya muncul bagi kelas tingkat akhir (Kelas 12).
2. **Auto-Pull Rata-rata Rapor**: Saat halaman dimuat, sistem secara otomatis menghitung rata-rata nilai rapor semester 1 s.d 5 dari tabel `detail_nilai_rapor` untuk setiap mata pelajaran siswa yang bersangkutan.
3. **Input Nilai US**: Guru/Operator cukup menginputkan angka Nilai Ujian Sekolah pada kolom yang tersedia.
4. **Kalkulasi & Simpan**: Sistem langsung menghitung **Nilai Ijazah** berdasarkan persentase formula dan menyimpannya ke tabel `nilai_ujian_sekolah`.
5. **Cetak Lampiran Ijazah**: Menambahkan opsi cetak **"Transkrip Nilai Kelulusan (Lembar Belakang Ijazah)"** yang siap ditempel/dicetak langsung di balik blanko ijazah fisik.
