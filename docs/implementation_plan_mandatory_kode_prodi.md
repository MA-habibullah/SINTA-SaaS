# Rencana Implementasi: Kode Prodi Wajib & Update Duplikat

Dokumen ini menjelaskan rencana perubahan teknis untuk membuat kolom **Kode Prodi** wajib diisi (*mandatory*) baik saat *upload* via Excel maupun *input* manual, serta menyempurnakan mekanisme pembaruan (*update*) jika terjadi duplikasi.

## Rencana Perubahan

### 1. Backend Controller

#### [MODIFY] [KampusController.php](file:///c:/xampp/htdocs/SINTA-SaaS/app/Controllers/KampusController.php)

1. **Fungsi `apiImportExcel` (Upload Excel SNBP/Master Kampus):**
   * Tambahkan verifikasi kolom `KODE` pada pemetaan awal. Jika tidak ditemukan, lempar pengecualian (*exception*).
   * Pada saat memproses setiap baris, jika `kodeProdi` kosong, lempar pengecualian (*exception*) agar pengguna tahu baris mana yang datanya tidak lengkap.
   * Modifikasi pencarian duplikat prodi agar mencocokkan berdasarkan `kode_prodi` atau kombinasi (`kampus_id`, `program_studi`, `jenjang`).
   * Jika duplikat ditemukan, lakukan pembaruan data secara menyeluruh (`kode_prodi`, `program_studi`, `jenjang`, `jenis_portofolio`).

2. **Fungsi `apiImportKampusProdi` (Upload Template Kampus & Prodi):**
   * Masukkan `kode_prodi` ke dalam validasi kolom wajib (`colKodeProdi !== false`).
   * Jika pada baris Excel kolom Kode Prodi kosong, lempar pengecualian (*exception*).
   * Perbarui query pencarian prodi ganda agar mencakup `kode_prodi` dan perbarui seluruh kolom prodi jika ditemukan duplikat.

3. **Fungsi `apiSaveProdi` (Simpan/Tambah Prodi dari Form):**
   * Tambahkan pengecekan `empty($kode_prodi)` ke dalam validasi agar mengembalikan error `422` jika kosong.

---

### 2. Frontend / Views

#### [MODIFY] [kampus_config_ui.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/bk/kampus_config_ui.php)

* Tambahkan tanda bintang merah `*` pada label input **Kode Prodi**.
* Tambahkan atribut `required` pada elemen `<input>` Kode Prodi di dalam modal kelola prodi untuk memvalidasi input di browser sebelum dikirim.

---

## Klarifikasi Menu Hapus di Aplikasi

Berdasarkan analisis kode, berikut adalah fungsionalitas tombol/menu hapus yang ada saat ini:

1. **Hapus Kampus (`deleteKampus`):**
   * Terletak pada halaman konfigurasi Master Kampus di bawah kolom **Aksi** (ikon tempat sampah merah).
   * Menghapus instansi kampus secara permanen. Karena ada pengaturan database `ON DELETE CASCADE`, aksi ini **otomatis menghapus seluruh Prodi dan Riwayat Kuota/Pendaftar** di bawah kampus tersebut.

2. **Hapus Program Studi (`deleteProdi`):**
   * Terletak di dalam modal "Kelola Program Studi" (setelah mengeklik jumlah prodi pada kampus tertentu) di samping masing-masing prodi.
   * Menghapus prodi tersebut beserta riwayat kuota/pendaftarnya.

3. **Hapus Riwayat/Daya Tampung per Tahun (`deleteRiwayat`):**
   * Terletak di dalam sub-tabel riwayat keketatan setelah mengeklik tombol "Riwayat Keketatan" di bawah nama prodi.
   * Hanya menghapus satu baris data daya tampung/peminat pada tahun tertentu.

4. **Hapus Kolektif Riwayat (`executeBulkDelete`):**
   * Terletak pada halaman utama flat-list di bagian atas kanan.
   * Digunakan untuk menghapus data riwayat daya tampung & peminat secara massal berdasarkan filter tahun dan/atau kampus.

---

## Rencana Verifikasi

### Pengujian Manual
1. **Pengecekan Upload Excel:**
   * Coba upload file Excel tanpa kolom `KODE` atau dengan kode yang kosong. Pastikan sistem memunculkan pesan error/validasi.
   * Coba upload data dengan kode prodi yang sama tetapi dengan nama prodi/jenjang/portofolio yang berbeda. Pastikan data di database ter-update dan tidak terduplikasi.
2. **Pengecekan Form Simpan Prodi:**
   * Buka modal kelola prodi, kosongkan input Kode Prodi, dan coba simpan. Pastikan form memvalidasi input tersebut.
