# Checklist Implementasi Fitur Wizard 5 & Proteksi Edit Siswa

- [x] **Langkah 1: Database Migration**
  - [x] Buat file migrasi baru `database/migrations/2026_06_21_add_pernyataan_fields_to_dokumen.php`
  - [x] Jalankan migrasi `php migrate.php` untuk memperbarui tabel `dokumen`
- [x] **Langkah 2: Update Model Siswa**
  - [x] Tambahkan kolom baru ke `saveOrUpdateSubTables()` di `app/Models/Siswa.php`
- [x] **Langkah 3: Update Controller Siswa**
  - [x] Tambahkan file keys baru ke `validateUploadedFiles()` dan `uploadFiles()`
  - [x] Tambahkan proteksi upload untuk role `siswa`
  - [x] Perbarui select query di `update()` agar mengambil kolom baru
  - [x] Perbarui gatekeeper status edit di `edit()` dan `update()` (tambahkan cek status `Pindah` selain `Lulus`)
  - [x] Tambahkan proteksi backend agar `nama_lengkap`, `nisn`, dan `nis` tidak bisa diubah oleh role `siswa`
- [x] **Langkah 4: Update View Tambah/Edit Siswa**
  - [x] Perbarui status lock `$isLocked` di `views/tambah_siswa.php` untuk menampung status `Pindah`
  - [x] Tambahkan form input upload untuk `berkas_pernyataan_baru` dan `berkas_pernyataan_tka`
  - [x] Tambahkan keterangan "Hanya diisi ketika sudah kelas 12" pada berkas TKA
  - [x] Batasi input agar `readonly` untuk `nama_lengkap`, `nisn`, dan `nis` jika `userRole === 'siswa'`
  - [x] Implementasikan Modal Document/Photo Viewer di Bootstrap
  - [x] Tambahkan properti Vue state (`showViewerModal`, dsb.) dan method `openDocumentViewer`
  - [x] Sambungkan link "Lihat Berkas" agar memicu modal viewer
- [x] **Langkah 5: Verifikasi & Uji Coba**
  - [x] Jalankan migrasi database
  - [x] Uji fungsionalitas upload admin & viewer berkas
  - [x] Uji fungsionalitas status lock Lulus/Pindah
  - [x] Uji readonly field untuk role siswa
- [x] **Langkah 6: Polishing Desain Upload Banners**
  - [x] Perbesar dan perbarui tampilan teks "Berkas sudah terunggah: Lihat Berkas" pada Wizard 5 agar modern, proporsional, dan premium menggunakan custom CSS transition, solid button, dan hover scaling.
- [x] **Langkah 7: Fitur Registrasi Cepat (Quick Add) Siswa**
  - [x] Update model `app/Models/Pengguna.php` untuk mengambil `npsn` pada metode `getTenants()`.
  - [x] Update `app/Controllers/PenggunaController.php` index() untuk melampirkan NPSN sekolah.
  - [x] Buat endpoint `quickStoreSiswaApi()` di `app/Controllers/PenggunaController.php` dengan transaksi DB, Prepared Statements, enkripsi kata sandi otomatis (siswa + tgl lahir), dan verifikasi keunikan email & NISN.
  - [x] Tambahkan rute `/api/v1/pengguna/quick-add-siswa` ke router `index.php`.
  - [x] Tambahkan tombol aksi "Registrasi Cepat" pada toolbar `views/pengguna_index.php` (dilindungi oleh hak akses RBAC admin/superadmin).
  - [x] Tambahkan Modal Popup `#quickAddModal` di `views/pengguna_index.php` berisi 5 field pokok dengan dropdown instansi (Super Admin) & auto-fill terkunci (Admin Sekolah).
  - [x] Implementasikan integrasi Vue.js & Axios untuk pemrosesan asinkron form modal registrasi cepat tanpa reload.
- [x] **Langkah 8: Pengamanan File Sensitif & Gatekeeper download.php**
  - [x] Buat file gatekeeper `download.php` di root direktori dengan proteksi RBAC & Tenant-Lock.
  - [x] Buat file proteksi eksekusi `/storage/app/public/uploads/.htaccess` untuk mematikan eksekusi script PHP.
  - [x] Modifikasi helper `getFileUrl` di `views/tambah_siswa.php` agar menggunakan gatekeeper.
  - [x] Lakukan verifikasi sintaks PHP dan pengujian akses berkas (Siswa, Admin Sekolah, Super Admin).



