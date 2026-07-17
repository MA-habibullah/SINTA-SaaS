# Dokumentasi Pembaruan Modul Pengumuman (Juli 2026)

Dokumen ini berisi riwayat perbaikan dan penambahan fitur pada Modul Pengumuman di dalam sistem SINTA-SaaS. Pembaruan ini bertujuan untuk memberikan pengalaman pengguna yang lebih baik, fitur pengkategorian yang terstruktur, dan penanganan bug pada editor teks.

## 1. Penambahan Fitur Kategori Pengumuman
- **Database:** Menambahkan tabel baru `kategori_pengumuman` dan menambahkan kolom `kategori_id` pada tabel `pengumuman`.
- **Backend (MVC):** 
  - Membuat `KategoriPengumumanModel.php` untuk menangani CRUD data kategori menggunakan parameter UUID (`UUID()`).
  - Menambahkan metode `storeKategori`, `updateKategori`, dan `deleteKategori` ke dalam `PengumumanController.php`.
  - Memperbarui `PengumumanModel.php` agar melakukan `JOIN` dengan tabel kategori sehingga `nama_kategori` dapat ditampilkan di halaman depan.
- **Frontend / Antarmuka:** 
  - Mengubah struktur halaman manajemen pengumuman (`views/humas/pengumuman.php`) menggunakan konsep **Navigasi Tabs (NavTabs)** (berbentuk kapsul / *Pills*) yang memisahkan antara panel "Daftar Pengumuman" dan panel "Manajemen Kategori".
  - Menambahkan _Dropdown_ pilihan Kategori pada Modal form "Tambah/Edit Pengumuman".

## 2. Perbaikan Bug Editor Teks Ganda (CKEditor)
- **Masalah:** Ketika tombol "Edit" diklik berulang kali, area editor (*CKEditor*) menumpuk menjadi beberapa lapis karena pengaruh transisi *Turbo Load*.
- **Solusi yang Diterapkan:** Memodifikasi _script_ inisialisasi di `pengumuman.php`. Editor sekarang hanya akan dikerjakan (inisiasi) pada *event* `shown.bs.modal` dan instance editor akan dihancurkan (di-*destroy*) dari memori setiap kali modal ditutup melalui *event* `hidden.bs.modal`.

## 3. Pembaruan Logika Dashboard (Limitasi Tampilan)
- **Pembaruan:** Halaman Dashboard utama hanya dibatasi untuk merender maksimal **1 pengumuman terbaru**.
- **User Interface:** Jika jumlah pengumuman aktif melebihi angka 1, sistem secara otomatis merender sebuah tombol "Lihat Semua Pengumuman" di bagian terbawah daftar pengumuman. Tombol ini mengarahkan pengguna secara langsung ke halaman Arsip Pengumuman.

## 4. Optimalisasi Halaman Arsip Pengumuman
- **Pembaruan UI:** Formulir pencarian (Search Form) di halaman Arsip (`views/pengumuman_arsip_view.php`) telah didesain ulang menjadi bentuk melengkung (*rounded pill*) layaknya fitur pencarian tingkat lanjut (Advanced Search).
- **Penambahan Filter:** Selain filter kata kunci (teks judul), ditambahkan fitur filter berdasarkan **Kategori** (Dropdown) dan **Tanggal** (Input tipe tanggal/kalender).
- **Backend Support:** Logika metode `getActiveForUser` dan `countActiveForUser` dalam `PengumumanModel.php` dimodifikasi untuk menerima dan menerapkan 3 buah filter tersebut dari *request* `$_GET` menggunakan operator SQL `AND`.

## 5. Perbaikan Hak Akses Super Admin
- **Masalah Awal:** Terdapat *bug* saat *Super Admin* gagal mengedit atau menghapus data ("Pengumuman Tidak Ditemukan") karena parameter pencarian hanya mengizinkan modifikasi pengumuman "Global" (`tenant_id IS NULL`).
- **Penyelesaian:** Di dalam `PengumumanModel.php`, pada metode `findById`, `update`, dan `delete`, kondisi SQL telah dikendurkan bagi Super Admin dari `tenant_id IS NULL` menjadi `1=1` (berlaku untuk semua data). Dengan demikian, Super Admin bebas mencari, mengedit, dan menghapus seluruh pengumuman (Global maupun di sekolah spesifik tertentu).
