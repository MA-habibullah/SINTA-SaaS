# Dokumentasi Modul 14 - Manajemen Data Siswa (CRUD)

## 1. Pendahuluan
Modul **Manajemen Data Siswa** (yang mencakup fitur Edit Data Siswa dan Tambah Siswa Baru) bukan sekadar formulir *input* biasa. Modul ini merupakan mesin pengolah data (*Data Engine*) paling besar di aplikasi SINTA-SaaS. Halaman ini diakses melalui rute `/siswa/tambah` atau `/siswa/edit?id=...` dan memanipulasi lebih dari 5 tabel relasional sekaligus.

## 2. Alur Kerja (Workflow) Utama
1. **Inisialisasi Data (Get API):** 
   Saat membuka halaman Edit Data Siswa, peramban memanggil AJAX `/api/v1/buku-induk/detail?id=...`. Backend mengembalikan objek raksasa berformat JSON yang berisi:
   - Identitas Diri
   - Kontak (termasuk referensi ke `provinsi`, `kota`, `kecamatan`, `kelurahan`)
   - Registrasi & Rincian Fisik
   - KIP & Beasiswa
2. **Form Interaktif (Wizard / Tabbed Form):**
   Pengguna dihadapkan pada antarmuka *Tabs*. Jika salah satu isian form wajib (*required field*) belum diisi pada tab tertentu, dan pengguna mencoba melakukan *Submit*, halaman akan mencegahnya dan memberi peringatan spesifik (misal: "Harap lengkapi No HP di Tab Kontak").
3. **Penyimpanan Draft Sementara (Auto-Save):**
   Jika terjadi interupsi jaringan atau pengguna memuat ulang halaman sebelum memencet simpan, sistem SINTA-SaaS memiliki rute `/siswa/save-draft` yang secara otomatis mengirimkan *payload form* secara berkala (*Debounced Ajax*) ke *Session PHP*. Sehingga, tidak ada inputan yang hilang.
4. **Penyimpanan Permanen (Submit):** 
   Seluruh kolom disatukan dalam satu muatan JSON/Form-Data ke `/siswa/simpan` atau `/siswa/update`.

## 3. Rincian Data yang Diinputkan
Berikut adalah rincian variabel yang diisi melalui modul ini, dikelompokkan berdasarkan tabel *database* yang dituju:

### A. Tabel `siswa`
- **Identitas Personal:** NIS, NISN (Primary Logic), NIK Siswa (16 Digit), Nama Lengkap, Jenis Kelamin, Tempat Lahir, Tanggal Lahir (Format `YYYY-MM-DD`), Agama.
- **Data Ayah & Ibu:** NIK, Nama Lengkap, Tempat/Tanggal Lahir, Status Hidup, Pendidikan Terakhir, Pekerjaan, dan Rentang Penghasilan Bulanan.
- **Relasi Kelembagaan:** *Dropdown* untuk memetakan `id_angkatan`, `id_tahun_ajaran`, `id_jenjang`, `id_jurusan`, dan `id_kelas`.

### B. Tabel `kontak`
- **Alamat Geografis:** Memanfaatkan sistem *Dropdown* Berjenjang (*Cascading Dropdown*). Pemilihan Provinsi akan memuat data Kota; pemilihan Kota memuat data Kecamatan, hingga Kelurahan.
- **Spesifik Alamat:** Nama Jalan, RT, RW, Dusun, Kode Pos, Nomor Telepon / HP Anak.
- **Titik Koordinat (Opsional):** *Latitude* dan *Longitude* rumah siswa (disiapkan untuk pemetaan rute zonasi).

### C. Tabel `rincian_pelajar`
- **Fisik & Kesehatan:** Tinggi Badan (cm), Berat Badan (kg), Lingkar Kepala (cm), Riwayat Penyakit Khusus.
- **Atribut Logistik:** Ukuran Baju/Seragam.
- **Jarak & Transportasi:** Jarak Rumah ke Sekolah (km), Waktu Tempuh, dan Jenis Moda Transportasi (Jalan kaki, Angkutan Umum, Motor Pribadi).

### D. Tabel `registrasi`
- **Jejak Pendaftaran:** Nomor Pendaftaran (SKHUN / UN), Tanggal Masuk Sekolah, Jenis Pendaftaran (Siswa Baru / Mutasi / Pindahan).
- **Asal Sekolah:** Nama SMP / MTs sebelumnya.
- **Wali (Jika Ada):** NIK Wali, Nama Wali, Pekerjaan, Pendidikan.

### E. Tabel `kip` (Kesejahteraan & Bantuan)
- **Program Indonesia Pintar (PIP):** Status Kelayakan PIP, Alasan Layak PIP, Nomor Rekening Bank.
- **Kartu Bantuan:** Nomor Kartu Keluarga Sejahtera (KKS), Nomor Kartu Perlindungan Sosial (KPS), Nomor PKH.

## 4. Komponen Backend & Logika Eksekusi
- **Controller:** `App\Controllers\SiswaController.php`
- **Database Transaction (Atomicity):**
  Untuk menghindari insiden data masuk separuh, logika di `SiswaController::simpan()` dibungkus dalam `BEGIN TRANSACTION`. 
  1. *Backend* melakukan `INSERT INTO siswa` dan menangkap `lastInsertId()`.
  2. ID tersebut didistribusikan sebagai *Foreign Key* ke kueri `INSERT INTO kontak`, `INSERT INTO rincian_pelajar`, dll.
  3. Apabila terjadi kegagalan/error pada tabel ketiga, seluruh perintah `INSERT` sebelumnya akan di- *Rollback* (Batal), dan mengembalikan pesan *Error 500* ke pengguna.
- **Manipulasi File/Foto:** Gambar pasfoto (jika ada) dilewatkan melalui `App\Core\FileCompressor`. Sistem tidak sekadar menyimpan, melainkan me- *resize* dan mengompres *file* hingga di bawah *200KB* untuk menghemat penyimpanan VPS.

## 5. Ringkasan Modul Ini vs Buku Induk
Perbedaannya adalah: **Buku Induk** adalah etalase/pajangan untuk membaca, memfilter (berdasarkan KIP/Pekerjaan Ortu), dan mengunduh (*Report Viewer*). Sedangkan Modul **Manajemen Data Siswa** ini adalah "Dapur" tempat seluruh *input*, validasi formulir berlapis (*Cascading*), kompresi gambar, dan penyelarasan relasi kelembagaan dilakukan.
