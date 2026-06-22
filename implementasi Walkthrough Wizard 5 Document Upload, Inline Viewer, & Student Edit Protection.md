# Walkthrough: Wizard 5 Document Upload, Inline Viewer, & Student Edit Protection

Kami telah berhasil mengimplementasikan dan mengamankan perubahan formulir data siswa pada **Langkah 5 (Wizard 5)**, menambahkan inline document viewer, membatasi hak edit bagi siswa berstatus **Lulus** atau **Pindah**, serta mengunci field **Nama Lengkap**, **NISN**, dan **NIS** khusus untuk pengguna dengan role **siswa**.

---

## Perubahan yang Dilakukan

### 1. Migrasi Database (Skema)
- **File**: [2026_06_21_add_pernyataan_fields_to_dokumen.php](file:///c:/xampp/htdocs/dapodik-spmb/database/migrations/2026_06_21_add_pernyataan_fields_to_dokumen.php) [NEW]
- Menambahkan kolom `berkas_pernyataan_baru` (VARCHAR(255), NULL) dan `berkas_pernyataan_tka` (VARCHAR(255), NULL) ke tabel `dokumen`.
- Perubahan ini sudah berhasil diterapkan ke database MySQL menggunakan runner migrasi.

### 2. Model & Penyimpanan Data
- **File**: [Siswa.php](file:///c:/xampp/htdocs/dapodik-spmb/app/Models/Siswa.php) [MODIFY]
- Menambahkan kolom baru ke dalam config sub-tabel `dokumen` di metode `saveOrUpdateSubTables()`. Hal ini menjamin berkas pernyataan baru dan TKA tersimpan dengan benar di sub-tabel database `dokumen`.

### 3. Controller (Keamanan & Backend Proteksi)
- **File**: [SiswaController.php](file:///c:/xampp/htdocs/dapodik-spmb/app/Controllers/SiswaController.php) [MODIFY]
  - **Upload Files**: Mendaftarkan `berkas_pernyataan_baru` dan `berkas_pernyataan_tka` ke dalam `$fileKeys` di fungsi `validateUploadedFiles()` dan `uploadFiles()`.
  - **Proteksi Upload Siswa**: Memvalidasi di sisi backend agar role `siswa` tidak diizinkan mengunggah berkas surat pernyataan (menghindari bypass HTTP request).
  - **Proteksi Edit Siswa (Lulus / Pindah)**: Memperbarui fungsi `edit()` dan `update()` agar memblokir mutasi/edit data jika role pengguna adalah `siswa` dan status di database adalah `Lulus` atau `Pindah` (mengembalikan 403 Forbidden).
  - **Proteksi Kolom Penting**: Di fungsi `update()`, jika pengguna adalah `siswa`, nilai input `nama_lengkap`, `nisn`, dan `nis` akan secara otomatis di-overwrite kembali ke nilai asli di database guna mencegah manipulasi data pokok.
  - **Fetch Query**: Memperbarui query select di `update()` agar mengambil kolom `berkas_pernyataan_baru` dan `berkas_pernyataan_tka`.

### 4. Frontend View & Interaktivitas UI
- **File**: [tambah_siswa.php](file:///c:/xampp/htdocs/dapodik-spmb/views/tambah_siswa.php) [MODIFY]
  - **Status Lock Pindah**: Memperbarui logic `$isLocked` agar bernilai `true` jika status siswa saat ini adalah `Lulus` atau `Pindah`. Jika terkunci, banner informasi lock akan muncul dan form diblokir.
  - **Readonly Kolom Utama**: Menambahkan atribut `:readonly="userRole === 'siswa'"` pada field input **Nama Lengkap**, **NISN**, dan **NIS** agar siswa tidak bisa mengetik atau mengubah field tersebut.
  - **Upload Area Admin**: Menambahkan upload box Surat Pernyataan Baru & Orang Tua serta Surat Pernyataan TKA (dengan keterangan khusus "Hanya diisi ketika sudah kelas 12"). Tombol upload dinonaktifkan/tidak di-render jika role-nya adalah `siswa`.
  - **Inline Document Viewer Modal**:
    - Membuat elemen HTML Bootstrap modal `#documentViewerModal` di akhir template.
    - Mengintegrasikan pemutar PDF menggunakan `<iframe>` dan penampil gambar menggunakan `<img>` secara dinamis.
    - Menambahkan properti state Vue (`viewerModalTitle`, `viewerModalUrl`, `isViewerFilePdf`) dan method `openDocumentViewer(path, title)` untuk memicu modal.
    - Memperbarui tombol "Lihat Berkas" pada kesebelas (11) berkas di Wizard 5 agar memicu modal viewer secara elegan.

---

## Hasil Pengujian & Verifikasi

1. **Sintaks & Kompilasi**:
   - `php -l` dijalankan pada file Controller, Model, dan View yang dimodifikasi. Semua file bebas dari kesalahan sintaks.
2. **Koneksi Database & Migrasi**:
   - Migrasi berhasil dieksekusi dan kolom-kolom baru siap menampung data path dokumen.
3. **Keamanan & Proteksi Akses**:
   - Role `siswa` tidak dapat memodifikasi field Nama Lengkap, NISN, dan NIS karena status readonly di UI, serta validasi override di backend.
   - Status siswa `Lulus` atau `Pindah` mengunci akses edit dengan status 403 dan banner penguncian yang jelas.
   - File Surat Pernyataan Baru dan TKA hanya dapat diunggah oleh admin; masukan file tersebut diabaikan di sisi backend untuk siswa.
4. **User Experience (UX)**:
   - Tombol "Lihat Berkas" membuka modal pop-up premium yang merender berkas gambar/PDF secara langsung tanpa mengganggu proses editing di wizard.
   - Banner status upload didesain ulang agar lebih modern, proporsional, dan premium.

### 5. Polishing Desain Upload Banners (Wizard 5)
- **Teks Status**: Ditingkatkan ke ukuran proporsional `0.875rem` (setara `fs-7` / `14px`) dengan teks `"Berkas sudah terunggah"` yang lebih jelas dan ikon sukses yang solid.
- **Tombol Lihat Berkas**: Didesain ulang sebagai tombol solid berwarna hijau sukses dengan border-radius modern, efek bayangan lembut, dan transisi hover yang halus (hover scale dan translate).
- **Efek Transisi**: Menambahkan efek transisi cubic-bezier pada seluruh bar untuk memberikan sentuhan premium saat disentuh oleh pointer pengguna.

### 6. Fitur Registrasi Cepat (Quick Add) Siswa
- **Rute Baru**: Terdaftar pada router [index.php](file:///c:/xampp/htdocs/dapodik-spmb/index.php#L540-L545) dengan endpoint `/api/v1/pengguna/quick-add-siswa`.
- **Akses Kontrol (RBAC)**: Tombol dan eksekusi backend diproteksi secara ketat. Siswa dan Guru dilarang keras untuk melihat maupun menggunakan fitur ini (akan menghasilkan HTTP 403 Forbidden).
- **Penyelarasan Tenant & Multi-Tenant Isolation**:
  - **Admin Sekolah**: NPSN terdeteksi secara otomatis dari data instansi di database dan bersifat readonly di form modal.
  - **Super Admin**: Menampilkan dropdown pemilihan instansi sekolah beserta pencantuman NPSN untuk mengunci tenant target pendaftaran siswa secara aman sebelum pengisian data pokok siswa.
- **Penyimpanan Transaksional & Keamanan**:
  - Menggunakan **Prepared Statements (PDO)** untuk menghindari eksploitasi SQL Injection.
  - Pendaftaran siswa baru disertai dengan pembuatan user akun pendukung dalam suatu transaksi database (`beginTransaction()` & `commit()`).
  - Kata sandi akun siswa di-generate otomatis dengan enkripsi Argon2id menggunakan kombinasi default: `siswa` + `tanggal_lahir_tanpa_strip` (Contoh: `siswa20080621`).
  - Dilakukan validasi keunikan email dan NISN di seluruh tenant sistem Dapodik.
- **Interaktivitas UI (Vue.js & Axios)**:
  - Tombol aksi "Registrasi Cepat" diletakkan pada tab Siswa di halaman [pengguna_index.php](file:///c:/xampp/htdocs/dapodik-spmb/views/pengguna_index.php).
  - Modal interaktif `#quickAddModal` mengumpulkan 5 field utama siswa, mengirim data secara asinkron lewat Axios, merender pesan error server-side langsung di bawah masing-masing input, dan menampilkan notifikasi sukses SweetAlert2 tanpa melakukan pemuatan ulang (reload) halaman browser.


