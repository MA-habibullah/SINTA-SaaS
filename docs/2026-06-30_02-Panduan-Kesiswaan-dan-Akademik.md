# 02 Panduan Kesiswaan dan Akademik

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


---

# Dokumentasi Modul 04 - Buku Induk Siswa

## 1. Pendahuluan
Modul **Buku Induk Siswa** (`/buku-induk`) dirancang untuk menjadi gudang penyimpanan data pokok setiap siswa secara komprehensif, sesuai dengan regulasi pendataan Kemdikbudristek (Dapodik / PDSS). Akses ke modul ini terbatas untuk Operator Sekolah (Bisa mengubah seluruh data) dan Siswa (Hanya bisa membaca & mengelola datanya sendiri via profil).

## 2. Alur Kerja (Workflow)
1. **Pendaftaran (Entry Data):** Data induk masuk ke dalam tabel `siswa` melalui 3 cara: *Quick Add* dari Manajemen Pengguna, *Import Excel* massal, atau *Push* data dari sistem PMB.
2. **Penyajian Data List:** Operator sekolah melihat daftar siswa lengkap di antarmuka tabel interaktif.
3. **Penyuntingan Buku Induk:** Saat tombol "Detail / Edit" diklik, layar memuat *Tabbed Form* berukuran penuh. Pengguna menelusuri ratusan kolom isian yang telah dikelompokkan (Identitas, Ortu, Kontak, dll).
4. **Validasi (*Data Completeness*):** Setiap penyuntingan akan memicu metode kalkulasi kelengkapan. Jika *field* penting telah terisi, *progress bar* siswa meningkat.
5. **Pencetakan / Export:** Data siap diekspor untuk dilaporkan ke sistem provinsi atau pusat (Berupa *spreadsheet* rekapitulasi data induk lengkap).

## 3. Komponen Backend
### Controller: `App\Controllers\BukuIndukController.php`
- `index()`: Memanggil kerangka halaman UI utama.
- `fetchApi()`: Menangani filter rumit seperti pencarian berdasar Nama, NIK, NISN, atau bahkan filter berdasarkan status beasiswa/KIP.
- `detailApi($id)`: Menarik rekaman spesifik dari DB dan menyajikannya dalam format JSON yang siap dimuat ke dalam struktur form *front-end*.
- `saveApi()`: Memproses *submit* dari form buku induk.

### Model: `App\Models\Pengguna.php` (Fungsi Kelengkapan)
- Karena tabel buku induk menggunakan tabel yang sama dengan `siswa`, fungsi perhitungan probabilitas kelengkapan `fieldsToCheck` dijalankan di sisi model saat terjadi operasi baca (jika dihitung *on-the-fly*) atau *update* statis ke kolom `persentase_kelengkapan`.

## 4. Struktur Database (Schema)
Tabel utama adalah `siswa`, namun memiliki ekstensi ke tabel relasional lainnya:
1. `rincian_pelajar`: Menyimpan ukuran seragam, tinggi badan, berat badan, jarak tempuh ke sekolah.
2. `registrasi`: Menampung tanggal masuk, jenis pendaftaran (Siswa Baru / Pindahan), No. SKHUN, asal SMP.
3. `kip`: Menyimpan data spesifik Kartu Indonesia Pintar, No. KPS, Layak PIP beserta alasannya.

Saat kueri *fetch*, *backend* mengeksekusi `LEFT JOIN` dari tabel `siswa` ke `rincian_pelajar`, `registrasi`, dan `kip` menggunakan relasi `id_siswa = siswa.id`.

## 5. Komponen Frontend (View & UI)
- **View File:** `views/buku_induk/index.php` dan `views/buku_induk/detail_modal.php`.
- **Navigasi Form:** *Tab-nav* digunakan di dalam *modal* agar form sepanjang 50+ *input* tidak memberatkan layar *scroll*. Bagian dibagi menjadi:
  1. Identitas Utama (NISN, Nama, TTL)
  2. Data Ayah & Ibu (NIK, Pekerjaan, Gaji)
  3. Data Wali (Opsional)
  4. Kontak (Alamat, RT/RW, Dusun, Koordinat)
  5. Rincian Fisik & Periodik
  6. Beasiswa / KIP
- **Proteksi Input:** Terdapat validasi *JavaScript* untuk pembatasan jumlah digit NIK (16 karakter), format NISN (10 karakter), serta pembatasan tanggal lahir agar valid secara kalender.


---

# Dokumentasi Modul 13 - Manajemen Kurikulum

## 1. Pendahuluan
Modul **Kurikulum** (`/api/v1/kurikulum`) adalah perpanjangan dari Master Data yang berfokus pada penjadwalan, bobot SKS (Sistem Kredit Semester) atau Jam Pelajaran (JP), dan pemetaan Mata Pelajaran ke masing-masing kelas.

## 2. Alur Kerja (Workflow)
1. **Pemetaan Mata Pelajaran (Mapping):** Waka Kurikulum (atau admin) masuk ke menu pemetaan. Mereka mengalokasikan daftar *Master Mapel* ke rombongan belajar (Kelas) spesifik pada Tahun Ajaran yang sedang berjalan.
2. **Penugasan Guru (Teaching Assignment):** Kurikulum mendikte Guru siapa yang berhak mengajar mata pelajaran tertentu di kelas tertentu.
   - Hal ini berdampak langsung pada Modul Rapor. Seorang guru hanya bisa memberikan nilai di grid rapor untuk kelas dan mapel yang telah ditugaskan kepadanya di sini.
3. **Copy Kurikulum (Duplikasi):** Mengingat kurikulum jarang berubah secara radikal setiap semester, sistem menyediakan tombol *Clone* atau *Copy*. Fitur ini menyalin susunan guru dan mapel dari *Tahun Ajaran/Semester* sebelumnya ke periode saat ini, menghemat ratusan jam kerja operator.

## 3. Komponen Backend
- **Controller Terkait:** `App\Controllers\KurikulumController.php` (Jika digabung, kadang berada di bawah `MasterDataController` atau `KelembagaanController`).
- **Logika Relasional:**
  - Tabel `kurikulum_mapel`: Bertindak sebagai *pivot* yang mempertemukan `kelas_id`, `mapel_id`, `guru_id`, dan `tahun_ajaran_id`.
  - Sistem memiliki proteksi *Unique Index* pada *database* agar satu mapel di kelas yang sama tidak diisi oleh 2 guru yang berbeda pada semester yang sama (mencegah konflik *input* nilai ganda).

## 4. Komponen Frontend
- **Drag and Drop / Grid Mapping:** Pemetaan seringkali disajikan dalam bentuk antarmuka visual agar Waka Kurikulum mudah memantau apakah ada jadwal atau penugasan yang bentrok.
- Fitur *Copy* dilengkapi *progress bar* asinkron untuk mencegah *Time-out* jika data pemetaan sangat masif.


---

# Dokumentasi Modul 08 - Manajemen Rapot

## 1. Pendahuluan
Modul **Manajemen Rapot** (`/cetak-rapot` dan `/cetak-rapot-kelas`) merepresentasikan muara dari *Learning Management System* dalam entitas sekolah. Modul ini menghimpun nilai murni kognitif/pengetahuan dari Guru Mapel, nilai *soft-skill* dari Ekstrakurikuler, dan absensi kepribadian dari Buku Induk atau BK, lalu meramunya ke dalam format *PDF* resmi.

## 2. Alur Kerja (Workflow)
1. **Input Nilai (Guru Mapel / Wali Kelas):** 
   Bisa dilakukan dengan 2 *stream* kerja:
   - A. **Impor Excel:** Mengunduh *template* *.xlsx*, diisi saat *offline*, lalu di- *upload* kembali.
   - B. **Input via Grid (Online):** Memanfaatkan antarmuka mirip Excel bawaan web.
2. **Kalkulasi Predikat:** Berbasis KKM (*Kriteria Ketuntasan Minimal*), sistem mengkalkulasi skor mentah (contoh: 85) menjadi Predikat (B+) dan merajut "Deskripsi Kemampuan" (contoh: "Sangat baik dalam menganalisa...").
3. **Generate Rapor:** Wali kelas atau Admin menekan tombol "Cetak PDF". *Backend* mengambil seluruh tabel terpisah (Tabel *siswa*, *nilai*, *ekskul*, *absensi*) melalui satu *Query SQL (JOIN)* besar berdasar filter `tenant_id`, `tahun_ajaran_id`, dan `semester`.
4. **Rendering Dokumen:** Web merender HTML berformat cetak. Fungsi peramban (*window.print*) atau pustaka pihak ketiga kemudian menangkap struktur DOM dan menerjemahkannya sebagai file PDF A4/F4.

## 3. Komponen Backend
- **Controller Inti:** `App\Controllers\RaporController.php`
- Modul ini menggunakan prinsip **EAV (Entity-Attribute-Value)** atau penyederhanaan pivot untuk menyimpan triliunan celah nilai (karena 1 Anak * 15 Mapel * 1 Rapor = 15 Baris *Database*, yang berarti eksponensial dalam skala besar).
- **Transaction Rollback:** Seluruh proses Impor Excel dibungkus dalam blok `try...catch` PDO. Jika terdapat 1 baris *corrupt* (misal: *String* di- *input* ke kolom Numerik), **semua** *insert* dibatalkan (*Rollback*), sehingga menghindari ketimpangan data *(Partial Writes)*.
- **Data Integrasi Eksternal:** Pada *method* cetak rapor, *controller* memanggil *helper query* ke modul Ekskul (`SELECT ... FROM nilai_ekskul WHERE siswa_id = ?`) dan disisipkan di blok Ekstrakurikuler secara terpisah.

## 4. Komponen Frontend
- **ag-Grid Implementation:** Modul Rapor menggunakan *library external Javascript* paling cepat dan canggih untuk memanipulasi baris/kolom. Guru dapat menggunakan navigasi *Keyboard* (Panah Atas/Bawah/Kiri/Kanan, Tab, dan Enter) sama persis dengan sensasi memencet tombol *keyboard* di Microsoft Excel. Fitur *Copy-Paste* massal dari Excel *Desktop* ke Web Grid juga di- *support* secara *native*.
- **Cetak Massal (Bulk Print):** Tombol di antarmuka mampu merender 40 lembar rapor sekaligus di latar belakang, menggabungkannya ke dalam satu elemen *Iframe* tersembunyi, lalu men-*trigger* mesin cetak *(Print Dialog)* tanpa *lag*.


---

# Dokumentasi Modul 07 - Bimbingan Konseling (BK)

## 1. Pendahuluan
Modul **Bimbingan Konseling (BK)** merupakan sistem informasi spesifik di bawah kendali *Role* Guru BK. Modul ini menjadi repositori dari 3 pilar: Pelanggaran (Kedisiplinan), Prestasi (Capaian), dan Penjurusan (Akademik Lanjut).

## 2. Alur Kerja (Workflow) Utama
### A. Poin Pelanggaran & Tata Tertib
1. **Master Pelanggaran:** Guru BK mendaftarkan jenis pelanggaran beserta bobot pinaltinya (misal: "Merokok" -> 50 poin).
2. **Catat Kasus:** Guru BK menautkan `siswa_id` kepada pelanggaran tertentu di tanggal kejadian. *Backend* secara reaktif menjumlahkan total poin siswa tersebut.
3. **Pemberian Sanksi:** Jika siswa mencapai limit poin (misal: > 30 = SP 1, > 100 = Dikeluarkan), sistem merekomendasikan *Tindak Lanjut / Sanksi*. Rekaman sanksi disimpan di tabel `sanksi_bk`.

### B. Absensi Bulanan/Semester
Digunakan manakala sekolah tidak menggunakan alat *fingerprint* harian. BK bertugas memasukkan kompilasi absensi (Sakit, Izin, Alfa). Data ini kemudian ditembakkan (*merged*) otomatis saat Guru Kelas / Kurikulum akan mencetak Lembar Rapor Siswa. (Mencegah input ganda absensi di fitur rapor).

### C. Penjurusan & PDSS (Pangkalan Data Sekolah dan Siswa)
Modul untuk menyiapkan siswa eligible menuju SNBP / Seleksi Perguruan Tinggi.
- BK dapat menandai daftar Siswa yang telah masuk kuota Eligible (misal Top 40% terbaik).
- Terdapat tab khusus di profil Siswa yang mengizinkan mereka memetakan PTN dan Program Studi Impian mereka. Guru BK bisa meninjau apakah pemilihan prodi *overlap* dengan siswa lain (yang bisa menyebabkan persaingan internal) dan membimbing siswa secara tepat.

## 3. Komponen Backend
### Rantai Controller & Routing (`/api/v1/bk/*`)
Logika *Controller* sangat dimodulasi (terdapat `BkController`, `KasusController`, `PdssController`).
- Pengamanan ekstensif: Rute selalu divalidasi dengan `role = guru_bk` atau `operator_sekolah`. Jika `guru_biasa` menembak *endpoint* absensi BK, sistem membalas *HTTP 403 Forbidden*.
- **Poin Kalkulasi:** Saat API penambahan kasus dipanggil, *trigger* model menjalankan `SUM(poin)` di tabel kasus dan mem- *feedback* UI dengan total poin terkini dari si Anak tanpa perlu melakukan *full page reload*.

## 4. Komponen Frontend (View & UI)
- **Live Search & Auto-Complete:** Karena data siswa sangat masif (bisa >1000 anak), *input form* pencatatan kasus tidak menggunakan `<select>` statis, melainkan komponen *Searchable Dropdown* (AJAX). Saat Guru BK mengetikkan huruf awal nama, API merespon 5 kandidat teratas.
- **Tabel Peringatan Dini (Early Warning System):** *Dashboard* BK mengurutkan secara *descending* siswa dengan angka pinalti tertinggi. Warna baris berubah menjadi merah jika poin melewati batas kritis.


---

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


---

# Dokumentasi Modul 09 - Tracer Study

## 1. Pendahuluan
Modul **Tracer Study** dirancang sebagai alat pendataan bagi alumni. Sistem ini menjamin bahwa SINTA-SaaS memiliki siklus hidup yang lengkap: mulai dari Pendaftaran Siswa (PMB), Pembelajaran Aktif, hingga status Pasca Kelulusan, membantu meningkatkan poin akreditasi sekolah terkait daya serap alumni.

## 2. Alur Kerja (Workflow)
1. **Luluskan Siswa (Trigger Awal):** Admin menekan tombol "Luluskan" pada Modul Manajemen Pengguna.
   - Operasi ini memindahkan flag referensi kelas siswa, dan men- *switch* role-nya agar ia berubah menjadi Alumni.
2. **Kuesioner Alumni:** Mantan siswa (*Alumni*) akan terus memiliki akun SINTA-SaaS (kecuali di- *suspend*). Saat mereka *login*, sistem akan mendorong survei (*Tracer form*).
3. **Pendataan Kategorikal:** Alumni dipandu ke dalam alur logika terstruktur:
   - Apakah Kuliah? (Tarik basis data PTN/PTS se-Indonesia).
   - Apakah Bekerja? (Input jenis instansi / perusahaan).
   - Apakah Keduanya? / Wirausaha / Mencari Kerja?
4. **Analisis Laporan:** Pihak Sekolah (Admin / Kepala Sekolah) dapat mengunduh grafik agregat dan rincian tabulasi dari seluruh lulusan di suatu tahun kelulusan.

## 3. Komponen Backend
- Controller: Menangani Endpoint API `/api/v1/tracer/*`.
- *Controller* ini bekerja di atas dua tabel khusus:
  1. `alumni_study`: Menyimpan riwayat edukasi (*University*, *Faculty*, *Entry Year*).
  2. `alumni_work`: Menyimpan rekam jejak pekerjaan (*Company*, *Position*, *Salary Range*).
- **Relasi Database:** *Foreign Key* merujuk ke tabel `users` milik alumni tersebut, menggunakan konvensi `id_user`. Hal ini mencegah data *tracer* hilang manakala data primer `siswa` di- *archived*.

## 4. Komponen Frontend
- **Form Kuesioner (Wizard UI):** *Client-Side* menggunakan logika navigasi selangkah demi selangkah (*Step-by-step Wizard*). Alumni tidak ditodong dengan form panjang, melainkan ditanya satu persatu dengan animasi transisi yang mulus.
- **Validasi Sinkron (Live Form Check):** Ketika alumni memilih prodi kuliah, *Javascript* melakukan validasi untuk memastikan bahwa kolom teks tidak mengandung karakter tidak valid (*Regex Injection Filter*).
- **Grafik Chart.js:** Laporan daya serap bagi Kepala Sekolah dan Guru BK dimuat menggunakan pustaka *Chart.js*, menghasilkan grafik donat (Pie Chart) atau batang (Bar Chart) interaktif (bisa di- *hover* untuk melihat persen nilai agregat).


---

