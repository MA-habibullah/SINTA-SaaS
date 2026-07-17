# Rencana Implementasi: Kunci Akademik & Tab Cetak Buku Induk

Dokumen ini merangkum rencana perubahan pada sistem SINTA-SaaS untuk mengunci pengaturan kurikulum dan nilai rapor berdasarkan Tahun Ajaran & Semester, serta penambahan tab "Cetak Buku Induk" yang sangat dinamis.

## Rekomendasi Kasus "Lulus Cepat" & "Tidak Naik Kelas"
Berdasarkan gambar tabel yang Anda kirimkan, struktur kolom rapor per tahun (Kelas, Semester 1, Semester 2) dapat bervariasi per siswa.
Rekomendasi terbaik untuk menangani **Lulus Cepat (Akselerasi - 2 Tahun)** atau **Tidak Naik Kelas (4 Tahun)** adalah dengan membuat **Header Kolom Dinamis berbasis "Tahun Ke-N"**, bukan secara absolut mematok 3 tahun (Kelas 10, 11, 12).

**Konsep Antarmuka Tabel Cetak Buku Induk:**
- Sistem akan menghitung *maksimal jumlah tahun* dari seluruh siswa yang sedang ditampilkan di tabel saat itu.
- Kolom *Header* utama: `NO` | `NISN` | `Nama Siswa` | `Tahun Masuk` | `Identitas Siswa` | `Tahun Ke-1` | `Tahun Ke-2` | `Tahun Ke-3` | `Tahun Ke-N...`
- Sub-kolom di bawah setiap "Tahun Ke-X" akan berisi: `Kelas` | `Semester 1 (Cetak)` | `Semester 2 (Cetak)`.
- Jika siswa **Lulus Cepat**, sel di "Tahun Ke-3" miliknya akan kosong/strip (-).
- Jika ada siswa **Tidak Naik Kelas**, tabel akan otomatis memunculkan kolom "Tahun Ke-4" di ujung kanan, dan bagi siswa normal, kolom "Tahun Ke-4" mereka akan kosong/strip (-).
- Tombol cetak di bawah semester akan mencetak Rapor pada semester tersebut, sedangkan tombol "Cetak" di kolom "Identitas Siswa" mencetak lembar biodata Buku Induk halaman depan.

## Proposed Changes

### Database Layer
Akan dibuat file migrasi baru (misal: `2026_07_07_01_create_kunci_akademik_table.php`) yang berisi tabel `kunci_akademik`:
- `id` (INT, PK)
- `tenant_id` (CHAR 36)
- `tahun_ajaran` (VARCHAR 50)
- `semester` (VARCHAR 20)
- `is_locked_kurikulum` (TINYINT 1, default 0)
- `is_locked_nilai` (TINYINT 1, default 0)

### Backend Controllers (App/Controllers)
#### [NEW] `KunciAkademikController.php`
- Menambahkan API `getLockStatus()` untuk mengecek status kunci.
- Menambahkan API `toggleLock()` untuk mengubah status kunci dengan autorisasi `super_admin` atau `operator_sekolah`.

#### [MODIFY] `KurikulumController.php` & `NilaiRaporController.php`
- Pada proses *Simpan (store/save)*, sistem akan memblokir proses jika status `is_locked_kurikulum` atau `is_locked_nilai` pada Tahun Ajaran & Semester yang bersangkutan bernilai TRUE.

#### [MODIFY] `BukuIndukController.php`
- Menambahkan API `fetchCetakMatrixApi()` khusus untuk melayani tabel Tab Cetak. API ini akan menarik daftar siswa, digabung (JOIN) dengan data riwayat kelas/tahun ajaran mereka, kemudian mengelompokkan datanya menjadi array matriks `Tahun Ke-1`, `Tahun Ke-2`, dst. untuk di-render oleh *Vue.js*.

### Frontend Layer (views/buku_induk.php)
#### Form Kunci Akademik
- Menambahkan tombol "Gembok" di Seting Kurikulum & Input Nilai Rapor.
- Ketika Unlock ditekan, muncul peringatan: **"Pastikan Anda sudah melakukan koordinasi dengan kurikulum sebelum membuka kunci ini!"**

#### Tab Baru: `cetak_buku_induk`
- Membuat struktur tabel HTML bertingkat (menggunakan `rowspan` dan `colspan` pada `<thead>`).
- Me-*render* data matriks `Tahun Ke-N` dari API secara dinamis dengan arahan direktif `v-for` milik Vue.js.

## Verification Plan
1. Mengunci kurikulum dan tes menyimpan data.
2. Membuka kunci kurikulum dan memastikan *popup* peringatan muncul.
3. Simulasi siswa normal (3 tahun), lulus cepat (2 tahun), dan tinggal kelas (4 tahun) pada matriks tabel Cetak Buku Induk.
4. Menyalin dokumen draf ini ke `docs/` ketika selesai diimplementasi.
