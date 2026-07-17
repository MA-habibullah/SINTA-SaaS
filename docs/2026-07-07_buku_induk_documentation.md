# Dokumentasi Modul Buku Induk (SINTA-SaaS)

Modul **Buku Induk** merupakan pusat rekaman data pokok dan historis seluruh siswa yang terdaftar di sekolah. Modul ini diakses melalui *endpoint* `/SINTA-SaaS/buku-induk`.

## 📌 Deskripsi Umum
Modul ini mengintegrasikan seluruh data siswa yang tersebar di berbagai fitur lain (seperti Pendaftaran, Bimbingan Konseling, Prestasi, dan Tracer Study) menjadi satu tampilan terpadu (*Single Source of Truth*). Selain menampilkan profil, modul ini juga dilengkapi dengan manajemen kurikulum untuk mengatur mata pelajaran dan penginputan nilai rapor siswa secara berkala.

## 🔐 Hak Akses
Modul ini dibatasi hanya untuk *role* berikut:
1. **Super Admin**: Dapat melihat dan mengelola buku induk dari **seluruh sekolah** (tersedia filter tenant/sekolah).
2. **Operator Sekolah**: Mengelola buku induk khusus untuk sekolahnya sendiri.
3. **Guru**: Melihat buku induk siswa, mengatur kurikulum kelas, dan menginput nilai rapor.

---

## 📑 Struktur Tab & Fitur Utama

Modul ini dibagi menjadi tiga tab fungsional utama:

### 1. Buku Induk Siswa (`buku_induk_siswa`)
Tab ini menampilkan daftar lengkap seluruh siswa beserta status mereka (Aktif, Lulus, Pindah, Keluar).

**Fitur yang tersedia:**
- **Pencarian & Filter**: Mencari berdasarkan Nama, NISN, NIS. Serta memfilter berdasarkan Kelas dan Status Siswa. Filter "Sekolah" secara khusus muncul untuk Super Admin.
- **Simulasi Ekspor Data**: Terdapat tombol Ekspor Excel yang saat ini disiapkan sebagai antarmuka (simulasi UI) untuk pengembangan *download reporting* ke depannya.
- **Detail Komprehensif (Modal Detail)**: Mengklik tombol "Detail" pada siswa akan memunculkan informasi lengkap berupa:
  - **Biodata Pribadi & Orang Tua**: Data dasar, NIK, alamat lengkap (sampai tingkat provinsi).
  - **Status Kelengkapan Dokumen**: Menampilkan rincian ukuran *file* (Ijazah, KK, Akta, dsb) yang telah diunggah siswa.
  - **Riwayat Kelas**: Historis perpindahan atau kenaikan kelas siswa dari tahun ke tahun.
  - **Nilai Rapor**: Rekapan nilai akademik berdasarkan semester dan tahun ajaran. Terdapat fitur pintar **Peringatan Toleransi Agama** yang menyoroti jika siswa mendapatkan nilai pada mata pelajaran agama yang tidak sesuai dengan keyakinan yang dianutnya.
  - **Prestasi Akademik/Non-Akademik**: Riwayat lomba dan penghargaan.
  - **Catatan Bimbingan Konseling (BK)**: Rekam jejak kedisiplinan dan pelanggaran siswa (terintegrasi dengan modul BK).
  - **Tracer Study**: Riwayat kelanjutan studi (Kuliah) atau dunia kerja (Pekerjaan) bagi siswa yang telah lulus/alumni.
- **Cetak Buku Induk (Rapot)**:
  - Tersedia opsi untuk mencetak rekam buku induk secara perorangan (`printRapot`).
  - Tersedia fitur cetak massal (*Bulk Print*) untuk seluruh siswa dalam satu kelas tertentu (`printRapotKelas`).
- **Simulasi Cetak Kartu Pelajar**: Tombol cetak kartu siswa perorangan disiapkan di modal detail (saat ini sebagai antarmuka simulasi ke printer ID card).

### 2. Seting Kurikulum (`seting_kurikulum`)
Tab ini digunakan untuk mendefinisikan mata pelajaran apa saja yang diajarkan pada suatu kelas di semester dan tahun ajaran tertentu.

**Fitur yang tersedia:**
- **Pemilihan Konteks**: Menentukan Tahun Ajaran, Semester (Ganjil/Genap), dan Kelas Fisik.
- **Manajemen Mata Pelajaran (Mapel)**:
  - Menambah, mengubah, atau menghapus relasi (pemetaan) mata pelajaran ke dalam kelas yang dipilih.
  - Menentukan Kriteria Ketuntasan Minimal (KKM) untuk setiap mapel pada kelas tersebut.
- **Salin Kurikulum (*Copy from...*)**: Fitur pintasan untuk menyalin seluruh daftar mata pelajaran dari kelas lain atau dari semester sebelumnya, sehingga mempermudah guru/operator agar tidak perlu menginput ulang secara manual.

### 3. Input Nilai Rapor (`input_nilai_rapor`)
Tab ini adalah tempat guru atau wali kelas memasukkan nilai akhir rapor siswa berdasarkan kurikulum yang telah diset di tab sebelumnya.

**Fitur yang tersedia:**
- **Tabel Penginputan Interaktif**: Setelah memilih Tahun Ajaran, Semester, dan Kelas, sistem akan menampilkan matriks siswa (baris) dan mata pelajaran (kolom).
- **Simpan Massal**: Nilai yang dimasukkan dapat disimpan secara bersamaan (AJAX), sehingga mempercepat proses *data entry*.
- **Otomatisasi**: Kolom mata pelajaran yang muncul akan menyesuaikan secara dinamis dengan Seting Kurikulum pada kelas tersebut.

---

## ⚙️ Integrasi Sistem (Under the Hood)
- **Controller**: `BukuIndukController.php` bertindak sebagai API *provider*. Data-data besar diambil secara asinkron (AJAX) untuk mempercepat muat halaman (*load time*).
- **View**: `views/buku_induk.php` dibangun menggunakan **Vue.js** (berbasis *Single Page Application*) yang mengandalkan state management internal untuk perpindahan antar-tab dan form tanpa harus me-refresh halaman.
- **Datatables Custom**: Tabel siswa tidak menggunakan *library* berat seperti jQuery DataTables, melainkan *custom pagination* yang ditarik langsung melalui endpoint `fetchApi()` di *backend* demi optimalisasi performa.

---
*Dokumentasi ini dibuat secara otomatis untuk memenuhi aturan pendokumentasian sistem jangka panjang.*
