# Rencana Implementasi (Revisi Final): Manajemen Sidebar Bimbingan Konseling & PDSS

Berdasarkan analisis arsitektur aplikasi secara menyeluruh, saya menambahkan beberapa **rekomendasi teknis penting** agar sistem lebih bersih (*clean code*), *scalable*, dan ramah pengguna (*user-friendly*).

## Proposed Changes (Termasuk Rekomendasi Tambahan)

### 1. Struktur Menu Sidebar Baru & Ikon
Selain membuat *Parent Menu* dan 3 *Sub-menu*, kita akan memberikan ikon (Bootstrap Icons) yang relevan untuk memperkuat estetika visual:

**BIMBINGAN KONSELING** *(Parent Menu, Icon: `bi-heart-pulse-fill`)*
1. **Layanan & Kedisiplinan** *(Sub-menu 1, Icon: `bi-person-badge`)*
   - Dashboard
   - Rekam Kasus & Jurnal
   - Prestasi Siswa
   - Kehadiran Semester
   - Tata Tertib & Poin
2. **Kesiapan Akademik & PDSS** *(Sub-menu 2, Icon: `bi-journal-check`)*
   - Penjurusan Mandiri
   - Kesiapan & Eligibilitas Siswa
   - Konfigurasi Target Kampus
3. **Alumni & Tracer Study** *(Sub-menu 3, Icon: `bi-mortarboard`)*
   - Tracer Study
   - Tracking Alumni & Rekam Kampus

### 2. Restrukturisasi URL & Routing *(Rekomendasi Tambahan)*
Untuk menjaga konsistensi dan mempermudah pelacakan (tracking) halaman, kita akan mengubah struktur URL di sistem *Routing* (`index.php` atau file rute Anda) menjadi lebih terpusat:
- Sub-menu 1: `/SINTA-SaaS/bk/layanan`
- Sub-menu 2: `/SINTA-SaaS/bk/akademik`
- Sub-menu 3: `/SINTA-SaaS/bk/alumni`

*Manfaat: URL menjadi lebih SEO/User-friendly, dan controller bisa mengetahui sub-menu mana yang sedang aktif dengan mudah.*

### 3. Refactoring View menjadi Modular *(Rekomendasi Tambahan)*
Saat ini, file seperti `master_bk.php` dan `pdss_index.php` mungkin sudah berisi ratusan atau ribuan baris kode karena menampung banyak *navtabs*. 
Kita akan merestrukturisasi halamannya menggunakan pendekatan modular:
- Membuat satu *layout* utama untuk BK.
- Meng-`include` konten dari masing-masing tab secara dinamis berdasarkan URL yang sedang diakses.
- Memastikan navigasi tab responsif (bisa digeser/ *swipe* di perangkat mobile).

### 4. Database Migrations
Kita akan membuat *script* PHP Migration yang aman untuk:
- Mengubah susunan ID Menu (Parent ID 29, Sub ID 30, 31, 32).
- Menghapus menu PDSS lama (ID 40) dari top-level (secara halus dengan migrasi database) dan memindahkannya ke ID sub-menu yang baru.
- Menyinkronisasi tabel `role_menu_access` dan `tenant_menu_access`.

## Open Questions

> [!TIP]
> **Persetujuan URL & Ikon**
> Apakah Anda setuju dengan tambahan ikon dan perapian struktur URL (`/bk/layanan`, `/bk/akademik`, `/bk/alumni`)?

> [!WARNING]
> **Refactoring Kode**
> Pendekatan modular akan merapikan struktur folder *views*. Apakah saya diizinkan untuk memecah/memodifikasi `master_bk.php` dan `pdss_index.php` yang sudah ada agar kodenya lebih terpisah dan mudah dikelola ke depannya?

## Verification Plan
1. Menjalankan migrasi database menu secara otomatis.
2. Memperbarui aturan *Routing* (kemungkinan di `index.php` dan `BKController`).
3. Memecah *Views* ke dalam komponen modular (jika disetujui).
4. Login sebagai Guru BK/Admin untuk memverifikasi fungsionalitas tiap sub-menu dan sub-tabnya.
5. Menyalin (*copy*) rencana implementasi akhir ini ke folder `C:\xampp\htdocs\SINTA-SaaS\docs` sesuai instruksi kustom Anda.
