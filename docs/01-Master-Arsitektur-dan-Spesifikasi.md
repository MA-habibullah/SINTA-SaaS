# 01 Master Arsitektur dan Spesifikasi

# DOKUMENTASI TEKNIS FUNGSIONAL: ARSITEKTUR & SISTEM INTI SINTA-SaaS

**SINTA-SaaS** adalah aplikasi *Software as a Service* (SaaS) Multi-Tenant yang dirancang secara spesifik untuk mengintegrasikan Data Pokok Pendidikan (Dapodik) dan Sistem Penerimaan Mahasiswa/Siswa Baru (SPMB). Dokumentasi ini menjabarkan spesifikasi fungsional, arsitektur *database*, dan rekayasa perangkat lunak tingkat *enterprise* yang diimplementasikan pada ekosistem SINTA-SaaS.

---

## 1. EXECUTIVE SUMMARY & ARSITEKTUR CORE

### 1.1 Deskripsi Singkat Sistem
SINTA-SaaS menghilangkan batasan silos antar departemen di sekolah dengan menyatukan hulu (SPMB/Penerimaan), proses (Dapodik/Akademik/BK), dan hilir (Tracer Study/Alumni) dalam satu platform terpusat. Mengusung filosofi *Software as a Service*, aplikasi ini memungkinkan ribuan institusi pendidikan (*Tenant*) berjalan di atas satu mesin *server* (Shared Schema Database) tanpa saling berbenturan.

### 1.2 Keamanan Multi-Tenant (Isolasi Data Total)
Untuk memastikan data antar sekolah tidak mengalami *data leak* atau percampuran, SINTA-SaaS menerapkan kebijakan **Isolasi Logikal Multi-Tenant**:
- **Pendekatan Shared Schema:** Seluruh data sekolah disimpan dalam *database* yang sama.
- **Kunci Isolasi (Tenant ID):** Seluruh kueri basis data (Select, Insert, Update, Delete) wajib menyertakan `tenant_id` pada level *Query Builder* / *Prepared Statement* (contoh: `WHERE tenant_id = $_SESSION['tenant_id']`).
- **UUIDv4 (*Universally Unique Identifier*):** Tabel utama (seperti `users`, `siswa`, `tenants`) tidak menggunakan ID Auto-Increment (1,2,3...), melainkan UUID versi 4. Hal ini mencegah peretas menebak ID rekaman pengguna lain (*IDOR - Insecure Direct Object Reference Attack*).

### 1.3 Integrasi Turbo Drive & Vue Lifecycle
Arsitektur antarmuka pengguna mengkombinasikan kinerja PHP murni (*Server-Side Rendered*) dengan rasa Aplikasi Halaman Tunggal (SPA) menggunakan **Turbo Drive** dan **Vue.js/Alpine.js**:
- **Navigasi Tanpa *Reload*:** Turbo Drive mencegat setiap klik tautan dan *form submission*, mengambil HTML baru di latar belakang, dan hanya mengganti bagian `<body>`, memberikan kecepatan transisi sekelas SPA.
- **Penanganan *Memory Leak* Global:** Sistem menggunakan pendengar ajang (*Event Listener*) `turbo:before-cache` untuk menghancurkan ( *destroy* ) instansi *Vue* atau kalender dinamis sebelum halaman diganti, mencegah komponen menumpuk dan membocorkan memori peramban pengguna.

---

## 2. DAFTAR MODUL & FITUR FUNGSIONAL

### A. Modul Tenant & User Management (Super Admin Portal)
Modul ini adalah pusat komando bagi pengembang aplikasi untuk mengatur tata laksana pendaftaran sekolah ( *Tenants* ) secara global.
- **Fitur Kelola Sekolah:** Disajikan dengan *DataTables* berkinerja tinggi, menampilkan daftar sekolah lengkap beserta indikator status *(Aktif / Suspended / Inactive)*.
- **Gatekeeper Middleware:** Filter sekuriti (*Middleware*) diletakkan sebelum proses otentikasi selesai. Jika status `tenant` tempat pengguna bernaung diset menjadi `Inactive` atau `Suspended` oleh Super Admin, seluruh warga sekolah tersebut (Kepala Sekolah, Guru, Siswa) akan otomatis diblokir saat *Login* atau ditendang ke luar (*Force Logout*).
- **Alur Manajemen Pengguna:** Dirancang menggunakan *Horizontal Navigation* (Tab dinamis) yang memisahkan entitas pengguna (Guru, Karyawan, Siswa, dll). Pemisahan ini mempermudah operator saat melakukan pencarian berdasar kategori.

### B. Modul Manajemen Siswa & Wizard Form (Multi-Step)
Sistem ini mentransformasi form administrasi yang kaku menjadi pengalaman pengisian bertahap ( *Wizard* ).
- **5-Tahap Reusable:** Form pendaftaran atau penyuntingan data pokok dipecah menjadi:
  1. Data Pokok (Identitas).
  2. Alamat & Kontak (Koordinat, *Dropdown* berjenjang).
  3. Fisik & Kesejahteraan (Jarak, Kendaraan).
  4. Orang Tua & Wali (Sistem UI *3-Card* untuk Ayah, Ibu, Wali).
  5. Registrasi & Dokumen (Data SMP & SKHUN).
- **Validasi Lokal Cerdas:** Proses pemindahan *Tab 1* ke *Tab 2* hanya menggunakan validasi HTML5 dan DOM Javascript murni. Data disimpan di sisi *client* dan baru dikirim utuh ke server *(Single Submit)* pada *Step 5* untuk meminimalkan latensi jaringan.
- **Validasi Kondisional KIP:** *Field* "Nomor KIP/No Rekening" otomatis menjadi "Wajib (*Required*)" di level DOM jika dan hanya jika pengguna memilih "YA" pada opsi "Apakah menerima PIP?".

### C. Modul Manajemen Berkas Terisolasi & Auto-Clean
Arsitektur penyimpanan dokumen (Pasfoto, Ijazah, dll) dirombak total dari metode pendaftaran tradisional.
- **Hierarki Storage Isolation:** Berkas fisik disimpan di *path* terisolasi: `/storage/uploads/{tenant_id}/{siswa_id}/`. 
- Nama *file* di-*hash* menjadi nilai acak yang rumit demi mencegah pembajakan *file* direktori, dan dibatasi maksimum 500 KB per unggahan.
- **Mekanisme Auto-Delete Orphan Files:** Saat pengguna mengunggah foto baru untuk menggantikan foto lama, *backend* secara *real-time* mendeteksi referensi nama *file* lama di tabel `siswa`, mencari lokasi berkas lama tersebut menggunakan perintah penghapusan disk lokal, baru menyimpan berkas yang baru. Logika ini mencegah pelonjakan ruang disk (*Disk Bloating*) pada mesin Virtual Private Server (VPS).

### D. Modul Bimbingan Konseling (BK) & Pelanggaran
Ekosistem pencatatan afektif/kepribadian siswa dilakukan melalui Modul BK yang terukur (Kuantitatif).
- **Master BK:** Tab Navigasi mencakup Statistik (Siswa bermasalah), Transaksi Pencatatan, Master Indikator, dan Buku Sanksi.
- **Skema Akumulasi Poin Cerdas:** Setiap pelanggaran memiliki poin. *Backend* akan melakukan penjumlahan kumulatif ( `SUM` ) untuk `siswa_id` setiap kali kasus ditambah.
  - Poin &ge; 25 : Penanda visual kuning (*Warning*).
  - Poin &ge; 50 : Otorisasi SP 1 (Surat Panggilan Orang Tua).
  - Poin &ge; 75 : Otorisasi SP 2 (Skorsing 3 hari).
  - Poin &ge; 100 : Bendera merah (*Red Flag*), otomatis mendorong kasus masuk ke meja *Sidang Pleno / Drop Out*.

### E. Modul Penjurusan Mandiri & DSS (Decision Support System) Kelas 10
Sebuah modul interaktif pembantu pengambilan keputusan bagi kelas peminatan secara adil.
- **Aktivasi Dinamis:** Kode sumber mengecek kolom `id_jenjang` di *session* siswa. Modul penjurusan secara eksklusif hanya muncul (*render*) di akun Siswa Kelas 10, dan akan sirna (disembunyikan) ketika mereka naik ke Kelas 11 atau 12.
- **Sinkronisasi Parameter (DSS):** Logika *backend* menarik skor Tes Minat Bakat (RIASEC), nilai psikotes (IQ), serta Rata-Rata Buku Induk Rapor Kelas 10, lalu mengkomparasikannya dengan persentase standar untuk memunculkan Label Rekomendasi Jurusan (IPA/IPS/Bahasa) secara otomatis.
- **Mitigasi Race Condition (Kuota Bocor):** Saat siswa berbondong-bondong memilih jurusan A yang sisa kuotanya tinggal 1 kursi, sistem memberlakukan penguncian (*Database Locking*) menggunakan sintaks SQL `SELECT ... FOR UPDATE` (atau *Redis Atomic Counter* jika diaktifkan). 
- **Silent Waiting List:** Jika kuota jurusan A penuh, siswa otomatis dilempar ke daftar antrean cadangan (Waiting List) berdasarkan pengurutan skor prestasi. Daftar ini dikelola secara asinkron (tertutup di *backend*) tanpa merusak mental siswa di peramban *frontend*.

### F. Modul Buku Induk Digital
Buku Induk adalah jantung administrasi pendidikan yang dirancang agar sangat komprehensif.
- **Single View Ledger:** Menampilkan ratusan variabel dari 8 tabel kesiswaan melalui mekanisme *Accordion Vertical Scroll*. Pengguna dapat melipat/membentangkan bagian tertentu tanpa mengokupasi ruang visual berlebih.
- **Integrasi Kurikulum:** Buku induk merekam jejak akademis lintas semester berbekal fitur Seting Kurikulum (Sinkronisasi *Pivot* antara `kelas` dan `mapel`). 
- **Penanganan Edge Case (Tinggal Kelas):** Jika siswa mengulang tahun, arsitektur basis data menerapkan **Composite Key (siswa_id, semester, tahun_ajaran_id)** untuk nilai rapor, sehingga rekam jejak nilai tahun sebelumnya tidak tergantikan/tertimpa (*overwritten*), melainkan mencatat baris matriks baru yang paralel.
- **Format Ekspor:**
  - Cetak Rapor Nasional via PDF berformat A3 Landscape atau Folio (menggunakan parameter DOM to PDF).
  - Ekspor Excel diproteksi format *Explicit Text* (menambahkan tanda petik khusus atau format *String Cell*) agar angka penting seperti NIK/NISN yang berawalan "0" (Nol) tidak terpotong oleh Excel kalkulator otomatis.

### G. Sesi Aktif, Observabilitas, & Tracer Study Alumni
Ekosistem pelacakan jejak rekam historis pengguna untuk keamanan dan mutu kelulusan.
- **State Machine Siklus Alumni:** Begitu operator menjalankan rute `/pengguna/aksi/luluskan`, sistem memicu mekanisme internal (*State Switch*): Form isian Buku Induk Siswa dikunci selamanya (*Read-Only/Immutable*). Di waktu bersamaan, sistem membukakan *Gate API* khusus (`/api/v1/tracer`) di *dashboard* akun mereka untuk melacak riwayat perkuliahan atau pekerjaan alumni.
- **Observabilitas & Audit Trail Log:** Menggunakan fungsi *Helper PHP Native* (`ActivityLogger`). Fungsi ini mencegat data larik asosiatif (*Array*) `sebelum` operasi `UPDATE/DELETE` dan menyimpannya di kolom JSON (`old_data`), lalu membandingkannya dengan `sesudah` (*new_data*). 
- **Live Monitor Sesi:** Dasbor membedah entitas *Active Sessions* berdasarkan pengidentifikasi Alamat IP publik (*IP Address*) dan *User Agent* (Browser, OS), memberikan wewenang penuh bagi administrator untuk memutus paksa sesi ( *Revoke Token* ) yang terlihat ganjil.

---

## 3. SPESIFIKASI INFRASTRUKTUR SERVER & KEAMANAN

Sebagai aplikasi *SaaS Enterprise*, konfigurasi perangkat lunak dan arsitektur *Deployment* mutlak mematuhi standar *Production-Ready*.

### 3.1 Standar Produksi Server (Web Engine)
SINTA-SaaS dilarang keras di-*deploy* menggunakan server GUI lokal abal-abal (seperti XAMPP, Laragon) di *Virtual Private Server* (VPS) publik. Infrastruktur diwajibkan menggunakan **Nginx** (via arsitektur LNMP / *Linux, Nginx, MySQL, PHP-FPM*), diorkestrasikan oleh panel server profesional (contoh: CloudPanel, Forge, atau Ploi).

### 3.2 Keamanan Direktori Uploads (Anti Web-Shell)
Salah satu serangan siber tersering adalah eksploitasi unggahan gambar yang disisipi skrip PHP. Arsitektur mencegah hal ini melalui lapis *server rules*:
- Nginx dikonfigurasi untuk mematikan interpretasi PHP di dalam ruang penyimpanan unggahan publik.
- Konfigurasi `location ^~ /storage/uploads/ { php_admin_flag engine off; }` (atau aturan limitasi setara) diterapkan secara tegas, sehingga meskipun peretas berhasil mengunggah skrip `shell.php`, *server* hanya akan menampilkannya sebagai teks mati ( *plain text* ) dan tidak mengeksekusinya.

---
*Dokumentasi disusun dan disertifikasi oleh Tim Arsitektur Sistem SINTA-SaaS.*


---

# DOKUMENTASI SISTEM & FITUR KOMPREHENSIF: SINTA-SaaS
*(Sistem Inti Akademik & Dapodik/SPMB - Multi-Tenant)*

Dokumen ini disusun sebagai cetak biru (*Blueprint*) arsitektur perangkat lunak dan spesifikasi fungsionalitas tingkat *Enterprise* untuk aplikasi **SINTA-SaaS**. Dokumen menjabarkan alur logika bisnis, aturan isolasi, serta interaksi basis data tanpa merincikan baris kode mentah, ditujukan bagi pengembang, auditor keamanan, dan analis sistem.

---

## 1. ARSITEKTUR CORE MULTI-TENANT & KEAMANAN

### A. Deskripsi Fitur & Tujuan Bisnis
SINTA-SaaS beroperasi dengan model B2B (*Business-to-Business*), melayani ribuan institusi pendidikan secara mandiri dalam satu instalasi *server*. Tujuan arsitektur ini adalah menjamin skalabilitas tinggi dan biaya operasional rendah (SaaS) tanpa mengorbankan keamanan data klien.

### B. Spesifikasi UI/UX & Interaktivitas Frontend
Pada level antarmuka, *multi-tenancy* tidak terlihat oleh pengguna (*seamless*). Setiap pengguna yang *login* akan disuguhkan *dashboard*, logo, dan nama sekolah (*tenant*) mereka masing-masing, memberikan ilusi bahwa mereka memiliki *server* dan aplikasi yang berdiri sendiri (*White-labeling*).
Transisi halaman menggunakan kombinasi **Vue.js / Alpine.js** bersama **Turbo Drive**. Turbo mencegat kueri halaman dan hanya me- *render* ulang blok `<body>` (SPA Experience). *Memory leak* dicegah dengan mengeksekusi pendengar (`turbo:before-cache`) untuk menghancurkan ( *destroy* ) siklus hidup komponen sebelum transisi usai.

### C. Alur Logika Sistem (Workflow) & Skema Keamanan Data (Tenant-Aware)
- **Pola Isolasi Data (Shared Schema):** Aplikasi menggunakan satu *database* besar yang memuat data seluruh sekolah. Isolasi dilakukan secara ketat di level *Query*. *Backend* mencegat *Header Request* atau *Session* server untuk mengekstrak `tenant_id`. Setiap operasi DML (*Data Manipulation Language*) wajib diinjeksi parameter `WHERE tenant_id = ?` melalui *PDO Prepared Statements*.
- **Prinsip Keamanan (Secure by Design):** 
  - Seluruh *primary key* tabel rawan (pengguna, sekolah, profil) menggunakan **UUID versi 4 global** untuk membendung eksploitasi IDOR (*Insecure Direct Object Reference*).
  - **Route Guard / Middleware Gatekeeper:** Memvalidasi silang antara hak akses menu (*Role*) pengguna dan *Tenant* saat merutekan HTTP Request. Jika kueri terdeteksi ilegal atau memanipulasi *tenant_id* milik institusi lain, sistem langsung mengembalikan status *403 Forbidden*.

### D. Relasi Tabel Database Terkait
- `tenants` (Menyimpan profil sekolah).
- `users` (Berisi kolom `tenant_id` dan `role_id` sebagai FK).

---

## 2. SISTEM DYNAMIC SIDEBAR MENU BERBASIS RBAC & TENANT ACCESS CONTROL

### A. Deskripsi Fitur & Tujuan Bisnis
Mengendalikan navigasi sistem secara adaptif berdasarkan peran pengguna (*Role-Based Access Control* / RBAC) dan paket berlangganan sekolah (Tiering). Tujuannya adalah mencegah fitur premium diakses oleh sekolah dengan paket standar.

### B. Spesifikasi UI/UX & Interaktivitas Frontend
- **Mekanisme Rendering:** Navigasi disajikan menggunakan antarmuka vertikal di bilah kiri. *Dropdown* (*Parent-Child*) dikonstruksi menggunakan komponen `collapse` Bootstrap 5. 
- *Nested Loop* dinamis terjadi tanpa me- *reload* halaman.

### C. Alur Logika Sistem (Workflow) & Skema Keamanan Data (Tenant-Aware)
- **Matriks Hak Akses:** Sistem mengeksekusi perbandingan *Join Query* kompleks di *backend*.
- Jika suatu menu diizinkan oleh `role_menu_access` namun dinonaktifkan di `tenant_menu_access` (sekolah belum membayar fitur tersebut), maka menu otomatis lenyap.

### D. Relasi Tabel Database Terkait
- `menus`, `roles`, `role_menu_access`, `tenant_menu_access`.

---

## 3. MODUL MASTER DATA KELEMBAGAAN CENTRALIZED TAB

### A. Deskripsi Fitur & Tujuan Bisnis
Menyederhanakan pengelolaan data dasar sekolah dalam satu antarmuka terpusat. Mengeliminasi belasan halaman terpisah untuk mempercepat waktu implementasi oleh operator.

### B. Spesifikasi UI/UX & Interaktivitas Frontend
- **Layout Arsitektur:** Antarmuka disatukan dalam kanvas berbasis *Horizontal Scrollable Navigation Tabs* (Mobile-Friendly). Mencakup 8 entitas: Jenjang, Jurusan, Kelas, Mapel, Pendidikan, Program Pengajaran, Tahun Ajaran, dan Angkatan.
- Form entri menggunakan *Bootstrap Modal Popup* (Single Page Experience) bertenaga Axios. Evaluasi *feedback* (sukses/gagal/konfirmasi hapus) dikomunikasikan secara *real-time* via *SweetAlert2*.

### C. Alur Logika Sistem (Workflow) & Skema Keamanan Data (Tenant-Aware)
- Menggunakan pendekatan CRUD berbasis tab (Pemisahan URL parameter penanda). *Backend* secara otomatis mengikat entri *insert* atau *update* baru dengan `tenant_id` operator yang sedang aktif.

### D. Relasi Tabel Database Terkait
- `jenjang`, `jurusan`, `kelas`, `mapel`, `pendidikan`, `program_pengajaran`, `tahun_ajaran`, `angkatan`.

---

## 4. MODUL MANAJEMEN SISWA & WIZARD FORM BERLAPIS

### A. Deskripsi Fitur & Tujuan Bisnis
Mentransformasi ratusan kolom kependudukan Dapodik/SPMB menjadi formulir modular (*Multi-Step Wizard*) untuk menghilangkan beban kognitif pengguna saat pendataan.

### B. Spesifikasi UI/UX & Interaktivitas Frontend
- Form dipecah menjadi 5 Langkah (Data Pokok, Alamat/Kontak, Fisik/Kesejahteraan, 3-Card Orang Tua, Registrasi Dokumen).
- Pengisian bersifat reaktif (*State-preserved*). Siswa tidak akan kehilangan isian meskipun mundur ke langkah sebelumnya.
- **Searchable Select & Dependensi:** Fitur 'Tempat Lahir' menggunakan *Select2/Tom Select*, dan Dropdown Kelas otomatis tersaring mendeteksi Jurusan & Jenjang (*Chained Dropdown*).

### C. Alur Logika Sistem (Workflow) & Skema Keamanan Data (Tenant-Aware)
- **Logika Submit & Validasi Lokal:** Selama perpindahan (Step 1 hingga 4), validasi form murni dieksekusi secara lokal (DOM/Frontend). Pengiriman paket ( *submit tunggal* ) hanya ditembakkan pada Langkah ke-5 untuk optimasi server.
- **Validasi Kondisional KIP:** DOM mendeteksi jika Radio Button "Terima PIP" diset "Ya", maka form No KIP otomatis menjadi mutlak diisi (*Required*).
- **Integritas Transaksi:** Paket data dari *Frontend* dibongkar *Backend* menggunakan metode relasional `DB::beginTransaction()`. Delapan (8) blok tabel dipasok (*insert*). Jika satu blok gagal, metode `DB::rollBack()` dijalankan secara instan (*Atomic operation*).
- **Ekspor Context-Aware:** Modul cetak Excel otomatis mematuhi *state* filter yang aktif. Format kolom angka-angka kritis (seperti NIK dan NISN yang berawalan nol) secara programmatis dipaksa sebagai Teks Eksplisit *(Explicit Text)*, memastikan angka 0 di depan tidak terhapus.

### D. Relasi Tabel Database Terkait
- `siswa` (Induk), `siswa_alamat`, `siswa_ortu`, `siswa_fisik`, `siswa_registrasi`, `kota`, `kecamatan`.

---

## 5. MODUL MANAJEMEN KURIKULUM & AKADEMIK LANJUTAN

### A. Deskripsi Fitur & Tujuan Bisnis
Mengatur detak jantung akademik. Mengalokasikan relasi antara pendidik, peserta didik, struktur pelajaran per tahun ajaran, dan patokan kelulusan minimal (KKTP).

### B. Spesifikasi UI/UX & Interaktivitas Frontend
- **Matriks Input Nilai Rapor:** Menampilkan lembar bentang tabular dinamis *(Spreadsheet-like)* yang memperluas dan mengkerut secara otomatis bergantung pada jumlah mapel di kurikulum suatu kelas. 
- Diperlengkapi fitur cetak *template* kosong Excel, dan Unggah (*Import*) skor untuk kemudahan kolaborasi luring.

### C. Alur Logika Sistem (Workflow) & Skema Keamanan Data (Tenant-Aware)
- Mendefinisikan kelompok mapel global (A, B, C, dst) yang dapat dipisahkan aktivasinya berbasis Jenjang per Tahun Ajaran.
- Modul *Import/Input* nilai beroperasi di atas kerangka pikir *Upsert (Update or Insert)* di *Backend*. Pustaka PDO akan memperbarui nilai lama (jika id_siswa dan mapel terdeteksi kembar pada semester terkait) alih-alih melempar *error Duplicate Entry*.

### D. Relasi Tabel Database Terkait
- `kurikulum_mapel`, `kurikulum_kelompok`, `penugasan_guru`, `nilai_rapor`.

---

## 6. MODUL REKAM PRESTASI SISWA & KEBUTUHAN REGULASI

### A. Deskripsi Fitur & Tujuan Bisnis
Menjawab kebutuhan pelaporan administrasi dan sertifikasi (*Akreditasi Sekolah*) serta penyimpanan portofolio PDSS terpusat.

### B. Spesifikasi UI/UX & Interaktivitas Frontend
- Galeri riwayat dalam gaya urutan (*Timeline Layout*) interaktif yang mencantumkan detail Kategori, Tempat, dan Penyelenggara event, disokong tombol untuk pratinjau bukti dokumen digital (Piagam/Foto).

### C. Alur Logika Sistem (Workflow) & Skema Keamanan Data (Tenant-Aware)
- Mendata seluruh metrik *(UUID relasional, Jenis Lomba, Tingkat Kebersinggungan)*.
- Ekstraksi keamanan diimplementasikan saat penarikan berkas. File bukti tak dapat dipanggil melaui tautan langsung absolut (misal: *https://domain.com/uploads/...*) guna menangkal bocornya privasi siswa lintas sekolah, melainkan melalui rute API otorisasi tersembunyi ber- *tenant_id*.

### D. Relasi Tabel Database Terkait
- `siswa_prestasi`, `dokumen_digital`.

---

## 7. MODUL BUKU INDUK DIGITAL

### A. Deskripsi Fitur & Tujuan Bisnis
Buku Induk bertindak sebagai pusat intelijen data ( *Agregator* ). Menggabungkan profil pasif (*Biodata*) dan profil aktif (*Rapor Akademik dan Riwayat Pelanggaran*).

### B. Spesifikasi UI/UX & Interaktivitas Frontend
- **Single View Ledger:** Halaman yang disajikan bagaikan satu akordion raksasa bergulir vertikal (*Vertical Scroll*). Tab dapat dibuka-tutup untuk merangkum seluruh rekam jejak tanpa mengotori ruang pandang (*clutter-free*).
- Opsi untuk melakukan Cetak PDF Standar Nasional *(A3/Folio Landscape)* dibekali kapabilitas peramban penyesuai format kertas.

### C. Alur Logika Sistem (Workflow) & Skema Keamanan Data (Tenant-Aware)
- *Backend* me-*render* keseluruhan halaman dengan metode kueri SQL cerdas (Penyatuan profil kesiswaan dan *Left Join* kurikulum, nilai, pelanggaran).
- **Edge Case Siswa Tinggal Kelas:** Matriks rapor memiliki kapabilitas menampung data redundan *Composite Key* unik antara `siswa_id`, `semester`, dan `tahun_ajaran_id`, agar jika anak mengulang kelas, nilai lamanya di kelas X tahun 2024 tidak hancur tertimpa nilainya di kelas X tahun 2025.

### D. Relasi Tabel Database Terkait
- `siswa` bergabung dengan seluruh tabel pendukung, `nilai_rapor`, dan `kurikulum_mapel`.

---

## 8. SPESIFIKASI INFRASTRUKTUR SERVER & KEAMANAN SIBER EKSTRA

Dalam beroperasi sebagai arsitektur berskala Enterprise, lapisan penunjang non-fungsional turut distandarisasi untuk melindungi aset informasi sekolah.

### Modul Manajemen Berkas Terisolasi & Auto-Clean
- **Hierarki Storage Isolation:** Berkas secara fisik (seperti Pasfoto/KTP) difragmentasi kedalam folder spesifik (misal: `/storage/uploads/{tenant_id}/{siswa_id}/`) dan nama fail dimutasi *(Hashed UUID)* untuk mencegah eksploitasi peramban berkedok pencarian nomor urut gambar. *Backend* menekan batas ukuran file (Maks 500 KB per *file*).
- **Auto-Delete Orphan Files:** Logika *auto-cleaner*. Saat pengguna merubah foto, algoritma *Backend* akan memastikan berkas foto lama dihapus secara *real-time* di tingkat memori piringan VPS sebelum fail baru ditimpa. Metode ini mengeliminasi masalah *Disk Bloating* akibat penumpukan file "yatim piatu".

### Modul Sesi Aktif & Observabilitas
- **State Machine Siklus Alumni:** Otomatis mengubah seluruh antarmuka Formulir Data Pokok dan Buku Induk menjadi modus *Read-Only* (*Immutable*) begitu *status* siswa dialihkan ke `'Lulus'`. Bersamaan dengan ini, rute gerbang pelacakan API (*Tracer Study Alumni*) dibuka otomatis bagi lulusan untuk memutakhirkan data perguruan tinggi / status bekerja mereka.
- **Active Sessions & Audit Trail Log:** Menggunakan fungsi bantu alami PHP untuk memantau perangkat yang *online* secara langsung. *Audit Trail* mencatat format JSON kondisi objek data `Before` dan `After` (mutasi rekaman), mendeteksi IP Address peretas, dan melaporkan sidik jari *User Agent* peramban demi visibilitas sistemik.

### Standar Deployment & Karantina Eksekusi Server
- **Web Engine Produksi:** Sistem **diharamkan** beroperasi di *environment* lokal (seperti XAMPP, Laragon, FlyEnv) pada skema peluncuran. Infrastruktur produksi mutlak menggunakan peladen **Nginx** (via CloudPanel/Ploi/Forge) untuk menyeimbangkan beban lalu lintas tinggi *(Load Balancing)*.
- **Isolasi Folder Eksekusi (Anti-Webshell):** Konfigurasi web server merantai ruang lingkup unggahan (*Uploads Folder*). Sistem proteksi mencegah eksekusi skrip .php atau .js asing yang nekat diunggah ke *server* *(Location directives: engine off)*. Fail berbahaya akan dianggap benda mati oleh peramban, melumpuhkan kemungkinan peretasan berbasis *shell*.

---
*Dokumentasi disahkan oleh Enterprise Software Architect SINTA-SaaS.*


---

