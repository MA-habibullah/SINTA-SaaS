# Dokumentasi Modul 05 - Kesiswaan & Ekstrakurikuler

## 1. Pendahuluan
Modul **Ekstrakurikuler** (`/kesiswaan/ekskul`) merupakan tulang punggung pengembangan karakter siswa (*soft-skills*). Modul ini memberikan hak otonom kepada Guru Pembina Ekskul untuk mendata anggotanya dan memasukkan nilai secara mandiri, tanpa mengganggu pekerjaan Wali Kelas atau Operator Akademik.

## 2. Alur Kerja (Workflow)
1. **Setup Ekstrakurikuler (Admin/Kesiswaan):** Admin membuka Tab **Master Ekskul**, lalu membuat entitas kegiatan baru (misal: "Pramuka Inti"). Admin memilih salah satu *user* (berperan `guru_pembina`) untuk bertanggung jawab atas ekskul tersebut.
2. **Rekrutmen & Pemilihan Tahun Ajaran:** Guru Pembina *login* dan membuka Tab **Kelola Anggota**.
   - Sistem *default* ke **Tahun Ajaran Aktif**. Namun, terdapat fitur filter untuk mundur ke periode *Historis* (Tahun Ajaran lampau).
   - Guru Pembina memasukkan siswa ke dalam ekskul tersebut. Validasi menolak *input* jika siswa telah masuk ke ekskul yang sama di periode yang sama.
3. **Proteksi & Penguncian (Locking):** Kesiswaan atau Pembina dapat menekan tombol **"Kunci Keanggotaan"**. Hal ini akan mengunci manipulasi penambahan/pengurangan siswa. Tujuannya adalah memastikan buku absensi final (*freeze*).
4. **Input Nilai Akhir & Absensi:** Di akhir semester, Pembina mengakses Tab **Penilaian & Presensi**. Mereka memasukkan skor kuantitatif, nilai kualitatif (A, B, C, D), deskripsi sikap, serta total Sakit/Izin/Alfa khusus pada sesi ekskul tersebut. Nilai ini terkunci bila Administrator merilis *Lock Nilai*.

## 3. Komponen Backend
### Controller: `App\Controllers\EkskulController.php`
- Menangani seluruh logika relasi `master_ekskul`, `anggota_ekskul`, `nilai_ekskul`, dan `kunci_ekskul`.
- `index()`: Mengonstruksi variabel raksasa ke dalam View, mencakup list Ekskul, Pembina, Siswa (berdasarkan kelas_id jika difilter), serta mem- *bypass* aturan pembatasan akses (*role guard*) jika yang mengakses adalah `super_admin`.
- Memiliki dukungan filter *Tahun Ajaran Historis* (*Query Parameter Binding*) di semua operasi ekspor & impor.

### Model / Struktur Tabel Database
- `master_ekskul`: Tabel referensi, menyimpan nama ekskul, kategori, dan relasi `pembina_id`.
- `anggota_ekskul`: *Pivot table* relasi *Many-to-Many* antara `siswa.id`, `master_ekskul.id`, diikat kuat dengan `tahun_ajaran_id` dan `semester`.
- `nilai_ekskul`: Perluasan dari pivot tabel anggota. Menyimpan properti penilaian kognitif/afektif.
- `kunci_ekskul`: *State Management table* menyimpan Boolean (0 atau 1) untuk kunci anggota dan kunci nilai per Ekskul per Tahun Ajaran per Semester.

## 4. Komponen Frontend (View & UI)
- **View File:** `views/kesiswaan_ekskul.php`
- **Konsep Navigasi:** Menggunakan Bootstrap 5 *Tabs* untuk berpindah dari:
  1. Master Ekskul
  2. Kelola Anggota
  3. Penilaian & Presensi
- **Visual Feedback:**
  - Fitur **Alert Historis** (Warna Kuning) akan otomatis muncul secara reaktif jika *user* memanipulasi *dropdown* "Tahun Ajaran" ke nilai selain *Tahun Ajaran Aktif Global*. Ini mencegah *Human Error* saat pengisian nilai.
  - Tabel menggunakan *badge* hijau (Terbuka) dan merah (Terkunci). Tombol "Simpan Nilai" otomatis di- *disable* oleh *frontend* (dan dikunci oleh *backend*) jika properti `kunci_nilai` aktif.

## 5. Fitur Excel Ekspor/Impor Lanjutan
Menggunakan pustaka `PhpSpreadsheet`, *backend* melakukan *rendering*:
- `exportMembers()`: Menarik rincian siswa (NISN, Nama, TTL, Kelas, Nomor HP) menggunakan `LEFT JOIN` dari entitas `kontak` dan `kota`.
- `exportGrades()`: Men- *download* *template* kosong untuk diisi nilai.
- `importGrades()`: Mengurai (*Parse*) kolom C (Sakit), D (Izin), E (Alfa), F (Nilai), G (Deskripsi) secara massal ke *database*, menggunakan klausa *On Duplicate Key Update* (*Upsert*).
