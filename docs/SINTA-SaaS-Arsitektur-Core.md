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
