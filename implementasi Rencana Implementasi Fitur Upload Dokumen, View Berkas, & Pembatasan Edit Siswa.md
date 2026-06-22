# Rencana Implementasi: Fitur Upload Dokumen, View Berkas, & Pembatasan Edit Siswa

Rencana ini merinci penambahan kolom database baru, pembatasan unggahan berkas (hanya untuk Admin), implementasi inline document/photo viewer, penguncian edit data bagi siswa Lulus/Pindah, serta penguncian kolom Nama, NISN, dan NIS untuk role siswa.

## User Review Required

> [!IMPORTANT]
> - **Migrasi Database**: Kami akan membuat file migrasi baru untuk menambahkan kolom `berkas_pernyataan_baru` dan `berkas_pernyataan_tka` pada tabel `dokumen`.
> - **Pembatasan Status Lulus / Pindah**: Jika siswa berstatus **Lulus** atau **Pindah**, mereka tidak dapat melakukan edit data diri mandiri. Hanya Admin dan Super Admin yang dapat mengubah data mereka.
> - **Penguncian Nama, NISN, & NIS**: Pengguna dengan role `siswa` tidak diizinkan mengubah kolom **Nama Lengkap**, **NISN**, dan **NIS** (diberi atribut `readonly` di frontend dan diabaikan/di-overwrite ke nilai asli DB di backend).
> - **Inline Document Viewer**: Kami akan menyediakan modal pop-up interaktif berbasis Vue 3 di Wizard 5 yang dapat menampilkan gambar secara langsung atau merender PDF menggunakan `<iframe>`.

---

## Open Questions

> [!NOTE]
> Tidak ada pertanyaan terbuka saat ini. Implementasi akan mengikuti spesifikasi yang diberikan.

---

## Proposed Changes

### Database Migration
#### [NEW] [2026_06_21_add_pernyataan_fields_to_dokumen.php](file:///c:/xampp/htdocs/dapodik-spmb/database/migrations/2026_06_21_add_pernyataan_fields_to_dokumen.php)
- Membuat file migrasi database baru untuk menambahkan kolom `berkas_pernyataan_baru` (VARCHAR(255), NULL) dan `berkas_pernyataan_tka` (VARCHAR(255), NULL) ke tabel `dokumen`.

### Backend Model
#### [MODIFY] [Siswa.php](file:///c:/xampp/htdocs/dapodik-spmb/app/Models/Siswa.php)
- Tambahkan kolom `berkas_pernyataan_baru` dan `berkas_pernyataan_tka` ke pemetaan `columns` sub-tabel `dokumen` di metode `saveOrUpdateSubTables()`.

### Backend Controller
#### [MODIFY] [SiswaController.php](file:///c:/xampp/htdocs/dapodik-spmb/app/Controllers/SiswaController.php)
- **Upload Berkas**: Tambahkan `berkas_pernyataan_baru` dan `berkas_pernyataan_tka` ke daftar `$fileKeys` di `validateUploadedFiles()` dan `uploadFiles()`.
- **Proteksi Upload Siswa**: Pada `validateUploadedFiles()`, jika `role_name` pengguna adalah `siswa`, cegah proses unggah untuk berkas surat pernyataan.
- **Proteksi Edit Siswa Lulus/Pindah**: Di fungsi `update()` dan `edit()`, perbarui gatekeeper agar menolak akses edit jika `role_name === 'siswa'` dan status di database adalah `Lulus` atau `Pindah`.
- **Proteksi Nama, NISN, NIS**: Di fungsi `update()`, jika `role_name === 'siswa'`, timpa input `nama_lengkap`, `nisn`, dan `nis` dengan nilai asli yang tersimpan di DB sebelum validasi & penyimpanan dilakukan.
- **Update Query**: Pastikan query `SELECT` dokumen di `update()` mengambil kolom `berkas_pernyataan_baru` dan `berkas_pernyataan_tka`.

### Frontend View
#### [MODIFY] [tambah_siswa.php](file:///c:/xampp/htdocs/dapodik-spmb/views/tambah_siswa.php)
- **Wizard 5 UI (Unggah Berkas)**:
  - Tambahkan area unggah untuk **Surat Pernyataan Siswa Baru & Orang Tua** (Hanya Admin).
  - Tambahkan area unggah untuk **Surat Pernyataan TKA** (Hanya Admin) dengan keterangan "Hanya diisi ketika sudah kelas 12".
  - Sembunyikan/nonaktifkan input file unggahan jika `userRole === 'siswa'`.
- **Locking Lulus/Pindah**:
  - Perbarui variabel `$isLocked` agar bernilai `true` jika `userRole === 'siswa'` dan status siswa adalah `Lulus` atau `Pindah`.
  - Tampilkan banner penguncian data yang informatif.
- **Locking Nama, NISN, NIS**:
  - Tambahkan atribut `:readonly="userRole === 'siswa'"` ke field input `nama_lengkap`, `nisn`, dan `nis`.
- **Inline Document Viewer**:
  - Tambahkan modal preview di bagian akhir view.
  - Tambahkan tombol "Lihat Berkas" pada setiap dokumen yang sudah terunggah untuk memicu modal.
- **Vue State & Logic**:
  - Daftarkan property baru ke `form`, `filesSelected`, dan `filePreviews`.
  - Tambahkan method `openDocumentViewer(path, title)` untuk memproses dynamic URL dan memicu modal preview.

---

## Verification Plan

### Automated Tests
- Menjalankan migrasi database via CLI:
  ```bash
  php migrate.php
  ```

### Manual Verification
1. **Migrasi Database**: Pastikan kolom baru berhasil ditambahkan pada tabel `dokumen`.
2. **Sebagai Admin**:
   - Pastikan dapat mengedit data siswa yang berstatus Lulus/Pindah.
   - Pastikan dapat mengunggah file Surat Pernyataan Baru & Orang Tua serta Surat Pernyataan TKA.
3. **Sebagai Siswa**:
   - Jika status adalah Lulus atau Pindah, pastikan sistem menolak masuk ke halaman edit (menampilkan halaman 403 Data Dikunci).
   - Jika status adalah Aktif, pastikan dapat masuk ke halaman edit, namun input **Nama Lengkap**, **NISN**, dan **NIS** berstatus `readonly`.
   - Pastikan tidak dapat mengunggah berkas surat pernyataan (input dinonaktifkan).
4. **Lihat Berkas (Document Viewer)**:
   - Klik tombol "Lihat Berkas" pada dokumen yang terunggah, pastikan modal pop-up tampil memperlihatkan PDF / Gambar dengan benar.
