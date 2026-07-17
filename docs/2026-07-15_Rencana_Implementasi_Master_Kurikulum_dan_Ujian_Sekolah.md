# Rencana Implementasi: Integrasi Menu Kurikulum Kelembagaan & Ujian Sekolah Dinamis Kelas 12

Rencana kerja ini mengintegrasikan dua perbaikan utama pada modul kelembagaan dan buku induk:
1. **Master Kurikulum pada Navtab Master Data**:
   * Menambahkan tab "Kurikulum" di halaman Master Data Kelembagaan.
   * Menghubungkannya ke tabel referensi `ref_kurikulum`.
   * Menjamin kurikulum nasional standar pemerintah (KBK, KTSP, K-13, Merdeka) bersifat statis/bawaan, sementara kurikulum kustom dapat ditambahkan secara dinamis.
2. **Ujian Sekolah (US) Dinamis Tanpa Tabel Baru**:
   * Dropdown Semester untuk kelas XII (12) dinamis memunculkan opsi **"Ujian Sekolah"**.
   * Integrasi input nilai rapor dan cetak transkrip untuk semester Ujian Sekolah secara dinamis.

---

## Proposed Changes

### 1. Database & Master Data CRUD (Kelembagaan)

#### [MODIFY] [Kelembagaan.php](file:///c:/xampp/htdocs/SINTA-SaaS/app/Models/Kelembagaan.php)
* Tambahkan `'kurikulum'` ke daftar `$allowedTables` dengan mapping target fisik ke tabel `ref_kurikulum`.
* Sesuaikan logic `getPaginated()` and query count agar membaca `ref_kurikulum` saat tabel `'kurikulum'` dipilih.
* Pada query where clause, pastikan tenant sekolah dapat melihat kurikulum nasional (`tenant_id IS NULL`) dan kurikulum kustom miliknya sendiri.
* Tambahkan validasi & data mapper saat insert/update kurikulum (kolom `nama_kurikulum`, `tipe_penilaian`, `is_active`).

#### [MODIFY] [KelembagaanController.php](file:///c:/xampp/htdocs/SINTA-SaaS/app/Controllers/KelembagaanController.php)
* Integrasikan validasi request data untuk tipe modul `'kurikulum'`.

#### [MODIFY] [master_kelembagaan.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/master_kelembagaan.php)
* Tambahkan opsi tab baru: `{ id: 'kurikulum', name: 'Kurikulum', icon: 'bi bi-gear-wide-connected' }` pada daftar navigasi tab master data.
* Buat view rendering kolom tabel data kurikulum: ID/No, Nama Kurikulum, Tipe Penilaian (Badge warna), Status Aktif, Aksi (Edit/Delete).
* Buat markup form input (tambah/edit) kurikulum: Nama Kurikulum (text input) dan Tipe Penilaian (select dropdown: `sederhana`, `klasik`, `kompleks`).
* Batasi aksi edit/hapus pada kurikulum sistem/nasional (read-only untuk tenant).

---

### 2. Ujian Sekolah Dinamis (Buku Induk & Cetak Rapor)

#### [MODIFY] [buku_induk.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/buku_induk.php)
* Pada dropdown filter **Semester** di **Tab 2: Seting Kurikulum**, **Tab 3: Input Nilai Rapor**, dan **Tab 4: Cetak Buku Induk**:
  * Ganti opsi static genap/ganjil menjadi dinamis/reaktif.
  * Tambahkan method/computed property untuk mengecek apakah kelas terpilih berawalan "12" atau "XII" (kelas akhir).
  * Jika ya, munculkan opsi tambahan semester: **"Ujian Sekolah"**.
* Simpan konfigurasi kurikulum mapel dan nilai Ujian Sekolah ke tabel mapping & detail nilai rapor dengan semester bernilai string `'Ujian Sekolah'`.

#### [MODIFY] [BukuIndukController.php](file:///c:/xampp/htdocs/SINTA-SaaS/app/Controllers/BukuIndukController.php)
* Modifikasi logic pengambilan histori transkrip akhir.
* Jika mencetak transkrip kelulusan/SKL/lembar belakang ijazah, ambil rata-rata nilai rapor (semester 1-5) dari database, ambil nilai semester `'Ujian Sekolah'`, dan lakukan kalkulasi dengan formula persentase (misal 60% Rata-rata Rapor + 40% Ujian Sekolah) secara *on-the-fly* di memori.

---

## Verification Plan

### Automated & Manual Verification
1. **Verifikasi CRUD Kurikulum**:
   * Buka menu Master Data Kelembagaan, buka tab Kurikulum.
   * Pastikan KBK, KTSP, K-13, dan Merdeka muncul secara statis sebagai data bawaan.
   * Tambahkan kurikulum kustom (misal: "Kurikulum Al-Azhar Pesantren", tipe: "sederhana"). Pastikan tersimpan dengan aman ke `ref_kurikulum`.
2. **Verifikasi Ujian Sekolah**:
   * Pilih kelas XII di Buku Induk.
   * Atur pemetaan kurikulum mapel untuk kelas XII pada semester "Ujian Sekolah".
   * Masuk ke Tab Input Rapor, pilih semester "Ujian Sekolah", isi nilai ujian sekolah siswa, lalu simpan.
   * Lakukan print/preview dan verifikasi nilai berhasil dihitung ke dalam transkrip nilai kelulusan.
