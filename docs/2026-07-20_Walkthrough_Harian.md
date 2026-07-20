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
