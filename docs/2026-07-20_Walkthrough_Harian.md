# Walkthrough Harian — 2026-07-20


---
## Revisi Cetak Buku Induk & Modul Beasiswa Siswa
**Waktu**: 10:04 WIB
**Jenis**: Bug Fix / Feature

### File yang Diubah:
- `views/print_buku_induk.php` -- Singkat NOMOR INDUK SISWA NASIONAL menjadi NISN, dan sederhanakan poin 7 (Jumlah Saudara) menjadi 1 baris ringkas "Jumlah saudara kandung".
- `index.php` -- Daftarkan rute API baru `/api/v1/buku-induk/beasiswa`.
- `app/Controllers/BukuIndukController.php` -- Implementasikan method API: `getBeasiswaApi()`, `storeBeasiswaApi()`, dan `deleteBeasiswaApi()` dengan standardisasi JSON response, prepared statements, role checks, dan multi-tenant isolation.
- `views/buku_induk.php` -- Tambahkan tab ke-6 "Beasiswa" di modal detail siswa beserta panel list beasiswa, form input tambah beasiswa baru, serta binding state dan methods Vue 3 terkait.

### Hasil Pengujian:
Seluruh file lulus pengujian sintaksis (PHP Lint) tanpa ada error. Pengujian CRUD menggunakan skrip tes (`scratch/test_buku_induk_beasiswa.php`) berhasil melakukan simulasi INSERT, SELECT, dan DELETE pada tabel `riwayat_beasiswa` secara aman (rollback) dan berjalan 100% normal. Pengisian beasiswa dapat menampung berbagai tipe beasiswa (PIP, Prestasi, Yayasan, Pemda, dll.) secara dinamis.

---
## Penyempurnaan Cetak Asal Sekolah Buku Induk
**Waktu**: 10:20 WIB
**Jenis**: Bug Fix
Perbaikan tata letak asal sekolah pada cetak Buku Induk. Memisahkan level/tingkat sekolah ("SMP / MTs") untuk dicetak pada baris "1) Asal Sekolah" dan mencetak nama sekolah lengkap (seperti "SMPN 53 SURABAYA") pada baris "2) Nama Sekolah". File yang diubah: `views/print_buku_induk.php`.

---
## Invalidation Cache Otomatis Cetak Buku Induk & Rapor
**Waktu**: 11:15 WIB
**Jenis**: Feature / Bug Fix
Menambahkan mekanisme auto-invalidasi cache (pembersihan otomatis) ketika data siswa berubah. Jika data profil siswa, data kesehatan, beasiswa, atau prestasi diperbarui di database, file cache statis HTML (seperti `buku_induk.html`, `identitas_rapor.html`, `transkrip.html`, atau `rapor_*.html`) akan dihapus agar data cetak selalu sinkron secara real-time. File yang diubah/dibuat:
- `app/Helpers/CacheInvalidator.php` (baru)
- `app/Controllers/BukuIndukController.php` (tambah invalidasi beasiswa)
- `app/Controllers/SiswaController.php` (tambah invalidasi profil & kesehatan)
- `app/Controllers/BKController.php` (tambah invalidasi prestasi)

---
## Perbaikan Kueri Data Kesehatan Siswa Cetak Buku Induk (Super Admin)
**Waktu**: 11:20 WIB
**Jenis**: Bug Fix
Perbaikan kegagalan pencetakan data kesehatan siswa ketika diakses oleh Super Admin. Menyesuaikan pemanggilan `getKesehatanSiswa` di model `Siswa` agar mengabaikan filter `tenant_id` jika user yang masuk adalah Super Admin, serta mengeset tenant ID dinamis pada instansiasi model di `BukuIndukController`. Berkas yang diubah:
- `app/Models/Siswa.php`
- `app/Controllers/BukuIndukController.php`

---
## Dinamisasi Tahun Pelajaran Tabel Kesehatan Buku Induk
**Waktu**: 11:30 WIB
**Jenis**: Bug Fix
Mengganti placeholder statis "Thn Pelajaran ........ / ........" pada header tabel Tinggi & Berat Badan serta Kondisi Kesehatan dengan tahun pelajaran dinamis yang dihitung berdasarkan tahun masuk siswa dari database (misal: 2025/2026, 2026/2027, 2027/2028). Berkas yang diubah:
- `app/Controllers/BukuIndukController.php`
- `views/print_buku_induk.php`

---
## Kemudahan Akses Beasiswa (Buku Induk & Bimbingan Konseling)
**Waktu**: 13:50 WIB
**Jenis**: Feature
Penambahan tombol pintasan aksi cepat "🎓 Beasiswa" pada daftar siswa Buku Induk dan tab baru "Beasiswa Siswa" pada modul Bimbingan Konseling -> Layanan & Kedisiplinan untuk mempermudah Super Admin dan Admin Sekolah mengelola data beasiswa. Berkas yang diubah:
- `views/buku_induk.php`
- `views/bk/hub.php`
- `views/master_bk.php`

---
## Perbaikan Error 500 Penghapusan Prestasi BK
**Waktu**: 13:58 WIB
**Jenis**: Bug Fix
Memperbaiki kesalahan referensi konstanta `PDO::FETCH_COLUMN` menjadi `\PDO::FETCH_COLUMN` pada method `apiDeletePrestasi` karena file controller berada di bawah namespace `App\Controllers`. Berkas yang diubah:
- `app/Controllers/BKController.php`

---
## Perbaikan Endpoint Pencarian Siswa pada Tab Beasiswa BK
**Waktu**: 14:00 WIB
**Jenis**: Bug Fix
Memperbaiki endpoint API pencarian siswa pada tab Beasiswa di modul Bimbingan Konseling yang awalnya memanggil `/api/v1/bk/jurnal/siswa` (mengembalikan error 404) menjadi `/api/v1/bk/siswa` (endpoint pencarian siswa yang valid). Berkas yang diubah:
- `views/master_bk.php`

---
## Daftar Semua Penerima Beasiswa & Ekspor Excel di BK
**Waktu**: 14:06 WIB
**Jenis**: Feature
Mengubah Panel Kanan tab Beasiswa Siswa di modul Bimbingan Konseling agar menampilkan daftar seluruh penerima beasiswa, dilengkapi filter Tahun Ajaran dan tombol ekspor Excel (.xlsx). Berkas yang diubah:
- `index.php`
- `app/Controllers/BKController.php`
- `views/master_bk.php`

---
## Perbaikan SQL Parameter Duplikat & Reference Error Toast di BK
**Waktu**: 14:09 WIB
**Jenis**: Bug Fix
Memperbaiki error SQLSTATE[HY093] (Invalid parameter number) akibat penggunaan parameter bernama `:tahun_ajaran_id` secara berulang pada prepared statements dengan cara menyusun klausa WHERE secara dinamis di `BKController`. Serta mendefinisikan Swal mixin `toast` di Vue setup `master_bk.php` untuk membenahi ReferenceError. Berkas yang diubah:
- `app/Controllers/BKController.php`
- `views/master_bk.php`

---
## Perbaikan Error 500 Hapus Beasiswa Super Admin & Nullable Cache Invalidator
**Waktu**: 14:10 WIB
**Jenis**: Bug Fix
Memperbaiki error 500 saat menghapus beasiswa sebagai Super Admin akibat `tenant_id` bernilai null yang memicu TypeError pada `CacheInvalidator::clearStudentCache` (mengharapkan string). Kami mengubah parameter `tenantId` pada helper `CacheInvalidator` menjadi nullable dengan resolusi otomatis dari database, serta memodifikasi `deleteBeasiswaApi` agar mengambil `tenant_id` asli dari record sebelum dihapus. Berkas yang diubah:
- `app/Helpers/CacheInvalidator.php`
- `app/Controllers/BukuIndukController.php`
- `views/master_bk.php`

---
## Perbaikan Error 500 Simpan Beasiswa Super Admin
**Waktu**: 14:14 WIB
**Jenis**: Bug Fix
Memperbaiki error 500 saat menyimpan riwayat beasiswa sebagai Super Admin akibat `tenant_id` session bernilai null yang melanggar batasan `NOT NULL` pada kolom `tenant_id` tabel `riwayat_beasiswa`. Kami menambahkan logika otomatis untuk mengambil `tenant_id` dari data siswa jika `tenantId` session kosong pada `storeBeasiswaApi`. Berkas yang diubah:
- `app/Controllers/BukuIndukController.php`

---
## Perbaikan Data Beasiswa Kosong di Modal Buku Induk Super Admin
**Waktu**: 14:17 WIB
**Jenis**: Bug Fix
Memperbaiki kendala riwayat beasiswa yang tampil kosong pada modal "Kartu Buku Induk Siswa Lengkap" saat diakses oleh akun Super Admin. Hal ini disebabkan oleh session `tenant_id` Super Admin yang bernilai null sehingga query `WHERE tenant_id = NULL` bernilai FALSE. Kami menambahkan resolusi `tenant_id` otomatis dari data siswa pada method `getBeasiswaApi`. Berkas yang diubah:
- `app/Controllers/BukuIndukController.php`

---
## Penyesuaian Tata Letak Cetak Buku Induk (Posisi Barcode & Hapus Tanda Tangan Kepsek)
**Waktu**: 14:26 WIB
**Jenis**: UI/UX Refactoring
Menghapus blok tanda tangan Kepala Sekolah pada bagian bawah halaman cetak Buku Induk (`print_buku_induk.php`) dan memindahkan posisi QR Code/Barcode verifikasi ke pojok kanan atas lembar cetak. Berkas yang diubah:
- `views/print_buku_induk.php`

---
## Presisi Posisi QR Code Verifikasi di Samping Tabel Informasi Header Cetak Buku Induk
**Waktu**: 14:31 WIB
**Jenis**: UI/UX Refactoring
Memposisikan kontainer QR Code / Barcode secara presisi di samping kanan tabel informasi header (sejajar vertikal dengan kolom KECAMATAN / KAB/KOTA / PROVINSI) sesuai contoh acuan gambar pengguna. Berkas yang diubah:
- `views/print_buku_induk.php`

---
## Pembaruan Margin Cetak Halaman Buku Induk (Top 1cm, Right 0.8cm, Bottom 1cm, Left 2.5cm)
**Waktu**: 14:36 WIB
**Jenis**: UI/UX Refactoring
Mengubah definisi `@page` margin pada `print_buku_induk.php` menjadi Top: 1cm, Right: 0.8cm, Bottom: 1cm, Left: 2.5cm sesuai instruksi pengguna. Berkas yang diubah:
- `views/print_buku_induk.php`

---
## Otomatisasi Pembersihan (Garbage Collection) File Rate Limit Kedaluwarsa
**Waktu**: 14:50 WIB
**Jenis**: Feature / Optimization
Menambahkan fungsi `cleanStaleRateLimitFiles` pada `BukuIndukController` yang secara otomatis menghapus file cache `.json` di folder `storage/app/rate_limit/` yang sudah berusia lebih dari 1 jam (3600 detik) menggunakan mekanisme probabilistik 10% request agar tidak membebani IO server. Berkas yang diubah:
- `app/Controllers/BukuIndukController.php`

---
## Pengelompokan Mata Pelajaran, Cetak Rapor Kurikulum, dan Seeding Data Dummy 12 Semester
**Waktu**: 15:38 WIB
**Jenis**: Feature / Database Seeder / UI Refactoring
Mengelompokkan mata pelajaran ke dalam 5 kelompok (Umum, Pilihan, Peminatan, Lintas Minat, Mulok), menyesuaikan format kolom cetak rapor berdasarkan kurikulum (Merdeka & K-13), dan membenihkan data dummy 12 semester lengkap dengan 25 mapel & nilai sikap. Berkas yang diubah:
- `database/migrations/2026_07_20_01_seed_dummy_12_semester_grades.php` [BARU]
- `app/Controllers/BukuIndukController.php`
- `views/print_rapot_merdeka.php`
- `views/print_rapot_k13.php`

---
## Proteksi Keamanan Produksi (Production Safeguard) Pada Seeder Data Dummy 12 Semester
**Waktu**: 15:41 WIB
**Jenis**: Security / Governance
Menambahkan blok proteksi `PRODUCTION SAFEGUARD` pada file migrasi seeder data dummy `2026_07_20_01_seed_dummy_12_semester_grades.php` yang secara otomatis memeriksa variabel lingkungan (`APP_ENV`, `APP_DEBUG`) dan nama host domain. Jika terdeteksi berjalan di server produksi (`APP_ENV=production` atau live host), proses pembenihan data dummy akan **otomatis ditolak/dibatalkan (`[SAFETY BLOCKED]`)** sehingga database produksi dijamin tetap bersih dan aman dari data pengujian. Berkas yang diubah:
- `database/migrations/2026_07_20_01_seed_dummy_12_semester_grades.php`

---
## Perbaikan Query Rapor Semester (Pencocokan Semester Ganjil/Genap & Hapus Kolom Non-Eksis m.kelompok)
**Waktu**: 15:46 WIB
**Jenis**: Bug Fix
Memperbaiki kendala data nilai rapor semester yang tampil kosong pada halaman cetak. Penyebab utama: (1) kueri SQL memuat kolom `m.kelompok` yang tidak ada pada tabel `mata_pelajaran` sehingga memicu SQL error dan mengembalikan data kosong, serta (2) pencocokan nama semester (`Ganjil`/`Genap`) belum mendukung pemetaan ke angka semester (`1` s.d. `12`). Kami menambahkan *flexible semester mapping* dan memperbaiki klausa `SELECT` pada `BukuIndukController`. Berkas yang diubah:
- `app/Controllers/BukuIndukController.php`

---
## Perbaikan Filter Tahun Ajaran Halaman Buku Induk (Pencegahan Siswa Angkatan Masa Depan Tampil)
**Waktu**: 15:50 WIB
**Jenis**: Bug Fix / Logic Correction
Memperbaiki kendala siswa angkatan baru (misal: Tahun Masuk 2026/2027) yang ikut muncul ketika pengguna memfilter Tahun Ajaran terdahulu (misal: 2022/2023). Root cause: Kueri pencarian `fetchCetakMatrixApi` belum menyaring `tahun_masuk` (siswa angkatan `2026/2027` belum masuk sekolah pada `2022/2023`) dan seeder sempat memasukkan nilai dummy pada siswa sebelum tahun masuknya. Solusi: (1) Menambahkan kriteria `WHERE (ta.tahun_ajaran IS NULL OR ta.tahun_ajaran <= :filter_ta_max)` pada `BukuIndukController.php`, dan (2) Memperbarui `id_tahun_ajaran` (Tahun Masuk) siswa seeder ke `2020/2021` agar sinkron dengan histori 12 semester. Berkas yang diubah:
- `app/Controllers/BukuIndukController.php`
- `database/migrations/2026_07_20_01_seed_dummy_12_semester_grades.php`

---
## Perbaikan Tampilan Transkrip Nilai Kelulusan (Hapus Kolom m.kelompok & Dukungan Parsing Semester Angka 1-6)
**Waktu**: 15:58 WIB
**Jenis**: Bug Fix
Memperbaiki kendala lembar cetak Transkrip Nilai Kelulusan yang menampilkan pesan `Belum ada data nilai tercatat.`. Root cause: (1) Kueri SQL pada `printTranskripNilai()` memanggil kolom `m.kelompok` yang tidak ada pada tabel `mata_pelajaran` sehingga memicu *silent SQL error*, dan (2) Logika pemetaan semester di `print_transkrip_merdeka.php` & `print_transkrip_standar.php` belum membaca format semester angka (`1` s.d. `6`). Solusi: (1) Memperbaiki kueri SQL menggunakan `COALESCE(pm.kelompok_id, ...)`, (2) Memperbarui parsing semester untuk mendukung format angka, dan (3) Membersihkan cache arsip cetak dokumen siswa. Berkas yang diubah:
- `app/Controllers/BukuIndukController.php`
- `views/print_transkrip_merdeka.php`
- `views/print_transkrip_standar.php`

---
## Perbaikan Vue Runtime Error (`Cannot read properties of undefined (reading '10')`)
**Waktu**: 16:04 WIB
**Jenis**: Bug Fix / Vue Error Prevention
Memperbaiki `[VUE RUNTIME ERROR] TypeError: Cannot read properties of undefined (reading '10')` pada halaman `buku-induk`. Root cause: (1) Pengaksesan properti objek `this.nilaiRapor.grades[studentId][subjectId]` dan `getAverageGrade()` belum dibungkus dengan pemeriksaan *null-guard* sehingga memicu error saat `mapel_id` atau `studentId` belum terinisialisasi lengkap, (2) Pengelompokan semester pada `formattedGrades` belum membaca semester angka, dan (3) Terdapat tag penutup `</td>` yang belum tertutup pada tabel matriks cetak. Solusi: Menambahkan *null-guard check* pada `getAverageGrade()` & `saveNilaiRapor()`, menyempurnakan `formattedGrades`, dan merapikan struktur HTML matriks. Berkas yang diubah:
- `views/buku_induk.php`

---
## Pembersihan Berkas Seeder Data Dummy dari Direktori Migrasi
**Waktu**: 16:07 WIB
**Jenis**: Cleanup / Security
Menghapus file seeder data pengujian `2026_07_20_01_seed_dummy_12_semester_grades.php` dari direktori `database/migrations/` untuk memastikan berkas pengujian tersebut tidak akan terunggah atau dieksekusi di server produksi. Berkas yang dihapus:
- `database/migrations/2026_07_20_01_seed_dummy_12_semester_grades.php` [DIHAPUS]

---
## Pengurutan Tahun Ajaran & Angkatan Berdasarkan Tahun Terbaru pada Master Data (`/master-data`)
**Waktu**: 16:13 WIB
**Jenis**: UI/UX Enhancement / Sorting Improvement
Memperbarui logika pengurutan (*sorting*) untuk data Tahun Ajaran dan Tahun Angkatan pada halaman Master Data Kelembagaan (`http://localhost/SINTA-SaaS/master-data`). Sebelumnya data diurutkan berdasarkan `id DESC` yang kurang presisi secara kronologis. Solusi: Mengubah klausa pengurutan di `app/Models/Kelembagaan.php` pada fungsi `getPaginated()` dan `getOptions()` menjadi `ORDER BY k.tahun_ajaran DESC` dan `ORDER BY k.tahun_angkatan DESC` sehingga Tahun Ajaran terbaru (contoh: 2026/2027, 2025/2026, 2024/2025...) tampil di urutan paling atas. Berkas yang diubah:
- `app/Models/Kelembagaan.php`

---
## Pembersihan & Reset Data Inputan Nilai Rapor & Setingan Kurikulum Localhost
**Waktu**: 16:20 WIB
**Jenis**: Localhost Cleanup / Data Reset
Menjalankan perintah skrip pembersihan data di lingkungan local `scratch/reset_buku_induk_nilai_kurikulum.php` untuk mengosongkan seluruh baris inputan nilai rapor siswa (`detail_nilai_rapor`, `nilai_sikap_k13`, `absensi_semester`, `kesehatan_siswa`, `log_nilai_rapor`, `nilai_ujian_sekolah`) dan setingan kurikulum (`kelas_kurikulum`, `pemetaan_mapel`). Berkas yang dibuat:
- `scratch/reset_buku_induk_nilai_kurikulum.php`

---
## Penambahan Informasi Kurikulum Aktif pada Header Kelompok Mata Pelajaran
**Waktu**: 16:23 WIB
**Jenis**: UI/UX Enhancement
Menambahkan informasi nama Kurikulum Kelas yang sedang aktif (contoh: *Kurikulum Merdeka*, *Kurikulum 2013 (K-13)*) dalam bentuk *badge pill* langsung di judul header **Kelompok Mata Pelajaran (Kelas: 10-Baru [Kurikulum Merdeka])** pada tab Setingan Kurikulum. Solusi: Menambahkan metode helper Vue `getKurikulumName(kurikulumId)` dan menyunting elemen header di `views/buku_induk.php`. Berkas yang diubah:
- `views/buku_induk.php`
