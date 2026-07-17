# Walkthrough Audit & Perbaikan Keamanan Menyeluruh

Saya telah menyelesaikan audit dan perbaikan keamanan data di **13 berkas views** yang teridentifikasi memiliki potensi celah kebocoran data dan kerentanan XSS. Berikut rincian tindakan pengamanan yang telah diambil:

---

## Perubahan yang Dilakukan

1. **Pembersihan Kebocoran Kata Sandi (views/tambah_siswa.php)**:
   * Menggunakan fungsi `unset()` pada `$siswaFullData['password']`, `$data['draft']['password']`, dan `$data['old']['password']` di PHP sebelum diekspor ke format JSON di client side.
   * Ini memastikan hash bcrypt kata sandi **sama sekali tidak tercetak** dalam kode sumber HTML halaman.

2. **Pengamanan Injeksi Script (Kategori A)**:
   * Menambahkan bendera `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT` pada fungsi `json_encode()` di dalam tag `<script>` pada berkas-berkas berikut:
     * [tambah_siswa.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/tambah_siswa.php)
     * [sekolah_profil.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/sekolah_profil.php)
     * [identitas_sekolah.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/identitas_sekolah.php)
     * [pdss_index.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/pdss_index.php)
     * [master_kelembagaan.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/master_kelembagaan.php)
     * [master_bk.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/master_bk.php)
     * [login_view.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/login_view.php)
     * [siswa_login_view.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/siswa_login_view.php)
     * [dashboard_view.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/dashboard_view.php)
     * [buku_induk.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/buku_induk.php)
     * [bk/master_kampus_prodi_layout.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/bk/master_kampus_prodi_layout.php)
     * [activity_logs.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/activity_logs.php)
     * [sekolah/agenda_terpadu.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/sekolah/agenda_terpadu.php)

3. **Pengamanan Injeksi Atribut HTML (Kategori B)**:
   * Menggunakan fungsi `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` pada data JSON yang dimasukkan ke dalam atribut event listener HTML (seperti `onclick='...'`) pada berkas berikut untuk mencegah *Attribute Break*:
     * [sekolah/agenda_terpadu.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/sekolah/agenda_terpadu.php) (tombol edit agenda & kategori)
     * [humas/pengumuman.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/humas/pengumuman.php) (tombol edit pengumuman & kategori)

---

## Hasil Pengujian & Validasi

1. **Hash Password Terproteksi**:
   * Memeriksa kode sumber halaman saat mengedit data siswa menunjukkan bahwa properti `"password"` telah hilang sepenuhnya dari objek JSON JavaScript.
2. **Kekebalan XSS**:
   * Injeksi karakter tag script (`</script><script>`) ke dalam input teks tidak lagi merusak parse parser HTML browser dan secara aman dibaca sebagai string unicode murni (`\u003C`).
   * Injeksi tanda kutip tunggal (`'`) ke dalam input teks agenda/kategori tidak lagi memecah atribut `onclick="..."` HTML, sehingga tombol edit berfungsi normal dan aman dari ancaman peretasan.
