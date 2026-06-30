# DOKUMENTASI TEKNIS: ARSITEKTUR MULTI-EVENT & SESI UJIAN UNIVERSAL

Dokumen ini disusun sebagai panduan arsitektur dan spesifikasi fitur terbaru aplikasi **SINTA-SaaS** terkait sistem ujian terpadu (*Computer Based Test* / CBT). Arsitektur ini diformulasikan untuk menangani kompleksitas multi-kegiatan ujian (seperti Try Out, PTS, PAS, hingga Ulangan Harian) secara dinamis, *real-time*, dan minim risiko kendala beban server.

---

## 1. RINGKASAN EKSEKUTIF & ARSITEKTUR UTAMA

Dalam merespons kebutuhan sekolah yang menuntut keleluasaan operasional jadwal yang kompleks, **SINTA-SaaS** kini bermigrasi dari sistem ujian yang *kaku* (statis per mapel) menuju konsep **Sesi Ujian sebagai Kontainer Universal** (*Universal Session Container*).

### Konsep Kontainer Universal
Konsep ini berarti bahwa sebuah "Sesi" tidak lagi mendefinisikan hanya 1 mata pelajaran, melainkan bertindak sebagai **wadah/ruangan logis** di dalam *database* yang mampu membungkus berbagai tipe ujian secara serentak (TO TKA, PTS, PAS, maupun Ulangan Harian). Dengan demikian, sekolah tidak perlu membuat sesi berulang-ulang untuk setiap ujian; satu kontainer dapat memuat 19 Mata Pelajaran Pilihan yang berjalan bersamaan.

### Data Collision Protection (Struktur Data Berjenjang)
Untuk menghindari insiden tabrakan data (*Data Collision*)—misalnya nilai siswa A tertukar dengan siswa B karena mereka *submit* nilai di detik yang persis sama pada ujian yang berbeda—sistem mengimplementasikan struktur data *Parent-Child*:
- **`Parent_Session_ID`**: ID utama yang mewakili payung kegiatan (Misal: "Try Out TKA Kelas XII 2026").
- **`sub_session_id`**: Identifikasi unik turunan untuk memecah kontainer (Misal: "Fisika Peminatan", "Sosiologi Lintas Minat").

Kombinasi kunci komposit (*Composite Keys*) antara `tenant_id`, `Parent_Session_ID`, `sub_session_id`, dan `siswa_id` pada level *database engine* (MySQL/InnoDB) memastikan isolasi penuh dan integritas data (ACID *Compliance*).

> **Skenario Kasus (Use-Case): Ujian Serentak Lintas Jurusan**
> *Pada pukul 08:00 WIB, 300 siswa Kelas XII IPA mengerjakan Ujian Fisika, sementara 250 siswa Kelas XII IPS mengerjakan Ujian Sosiologi dalam satu "Sesi Waktu" yang sama. Sistem Kontainer mendistribusikan lalu lintas data ke sub-sesi masing-masing secara terisolasi tanpa hambatan.*

---

## 2. FITUR 1: MANAJEMEN SESI & FILTER DINAMIS (ADMIN SIDE)

Fitur ini memberikan otonomi penuh kepada Operator / Panitia Ujian Sekolah untuk mengatur tata laksana sesi secara granular.

### Alur Pembuatan Sesi Utama
1. Admin menavigasi ke menu "Manajemen Sesi".
2. Admin mengisi Form Kontainer:
   - **Nama Kegiatan:** (Misal: "Penilaian Tengah Semester Ganjil").
   - **Kategori:** (TO, PTS, PAS, atau UH).
   - **Tahun Ajaran & Target Kelas:** Menentukan *scope* peserta.

### Pengaturan Tampilan Publik (Sidebar)
Pada menu *sidebar* khusus admin, terdapat panel kontrol visibilitas untuk mengelola Layar Tancap (Dashboard Publik):
- **Konfigurasi Key-Value & Custom Shortcode:** Admin dapat merangkai URL atau label dinamis (contoh `[KLS_X]` untuk *output* otomatis "Kelas X").
- **Scope Filter:** Mengatur apakah skor (*Leaderboard*) yang dimunculkan untuk Publik bersifat *Global* (semua jenjang), Per Jenjang, atau Per Kelas.
- **Top Limit & Data Masking:**
  - Admin dapat membatasi daftar peringkat: Top 3, Top 5, atau Top 10.
  - **Toggle Data Masking (On/Off):** Demi kepatuhan privasi (UU PDP), nama siswa dapat dimasker (Misal: `Budi Santoso` menjadi `B*** S******`).

> **Skenario Kasus (Use-Case): Menjaga Psikologis Siswa**
> *Panitia sedang menyelenggarakan TO Skala Provinsi. Agar siswa yang mendapat nilai rendah tidak malu, Admin menyalakan fitur "Data Masking = ON" dan membatasi Leaderboard publik hanya menampillkan "Top 10" saja.*

---

## 3. FITUR 2: BILIK KAWALAN TERPUSAT (LIVE CONTROL ROOM)

*Control Room* adalah pusat komando bagi Guru dan Panitia untuk mengawasi jalannya ujian secara *Real-Time*.

### Dashboard Guru (My Exams)
- Setiap guru memiliki dasbor otonom untuk mengatur **Ulangan Harian (UH)**.
- **Isolasi Kelas Otomatis:** Saat guru membuat UH, *scope* sesi langsung dikunci (*hardcoded* di level *backend*) hanya untuk kelas yang diajarnya saja (berdasarkan pemetaan Kurikulum). 
- **Master Toggle:** Guru dapat menekan tombol publikasi nilai (*On/Off*) untuk menyiarkan skor langsung ke dasbor kelas atau menyembunyikannya.

### Master Dashboard (Admin) & Server Guard
- Admin dapat melihat *Global View* dari 19 Mapel yang berjalan serentak melalui *Global Master Toggle*.
- **Mekanisasi "Server Guard / Priority Queue":** Menghadapi potensi *DDoS-like traffic* dari ribuan siswa yang me- *refresh* halaman secara bersamaan, SINTA-SaaS menggunakan *Caching Redis*. Kueri nilai tidak langsung memukul (*hit*) database MySQL, melainkan diproses oleh Redis *Cache* dan di-*update* dalam siklus per 30 detik (*Tick Rate*). Ini menjamin CPU server tetap rileks di bawah beban ekstrem.

> **Skenario Kasus (Use-Case): Mitigasi Server Down**
> *Saat waktu ujian berakhir (09:30 WIB), 1000 siswa menekan tombol "Submit" bersamaan. Alih-alih menyebabkan *Database Lock*, sistem antrean "Server Guard" menyerap *traffic* tersebut di memori Redis, memprosesnya perlahan di *background*, lalu memperbarui Leaderboard publik 30 detik kemudian tanpa hambatan.*

---

## 4. FITUR 3: DASHBOARD INTERNAL SISWA (BENTO GRID)

Pengalaman pengguna (*User Experience* / UX) bagi siswa didesain ulang agar sangat presisi, anteng, dan anti-sasar.

### Logika Deteksi Profil Cerdas
Ketika siswa berhasil *Login*, sistem membaca `id_jurusan`, `id_kelas`, dan *history* Peminatan. Sistem otomatis menyeleksi modul ujian mana yang menjadi hak siswa tersebut.

### Tampilan Bento Grid Otomatis
- Dasbor siswa menggunakan *layout* **Bento Grid** (kotak-kotak modern asimetris yang responsif).
- **Penguncian Mapel:** Grid secara otomatis merender 3 Mapel Wajib (Misal: Matematika, B. Indonesia, B. Inggris) di sel-sel besar.
- Sementara itu, 1 sel terkunci secara eksklusif hanya untuk **Mapel Pilihan** spesifik milik siswa tersebut (contoh: Siswa Peminatan Biologi **tidak akan pernah** melihat tautan untuk masuk ke ujian Geografi).

> **Skenario Kasus (Use-Case): Ujian Lintas Minat yang Rawan Tertukar**
> *Siswa A dan Siswa B berada di Kelas XII MIPA 1. Siswa A memilih lintas minat Ekonomi, Siswa B Sastra Jepang. Saat mengakses dashboard Bento Grid, Siswa A hanya melihat kartu "Ujian Ekonomi", mencegahnya membuang token ujian masuk ke ruangan Sastra Jepang.*

---

## 5. FITUR 4: LAYAR DASHBOARD PUBLIK (MULTI-EVENT LAYAR TANCAP)

Fitur ini didesain secara spesifik untuk di-*cast* (diproyeksikan) pada proyektor aula sekolah, TV Pintar lobi, atau layar raksasa selama penyelenggaraan acara besar (seperti *Event* Try Out Akbar).

### Layout Splitter Sistem
Tampilan layar secara dinamis membelah diri menjadi dua zona utama:
- **Zona Utama (Hero Section - 70%):** Mendominasi layar dengan menayangkan peringkat terkini untuk *Event* Utama (seperti TO TKA Global).
- **Zona Sekunder (30%):** Sebuah panel di sisi kanan/bawah yang mendedikasikan ruang bagi Ulangan Harian (UH) Guru yang kebetulan sedang *Live* di hari yang sama, tanpa mengganggu layar utama.

### Mekanisme Rollover Tampilan (Carousel Otomatis)
Menayangkan 19 Mapel Pilihan dalam satu layar fisik adalah mustahil.
Oleh karena itu, sistem membagi mapel ke dalam **Rumpun/Kluster** (Soshum, Saintek, Bahasa). Layar akan melakukan transisi geser (*Carousel slide*) secara otomatis setiap 15 detik, memutar papan peringkat untuk tiap kluster tanpa henti.

### Fitur QR Code Kios Mandiri (On-Demand Filter)
- Di sudut layar tancap, terdapat sebuah QR Code statis yang menautkan pengunjung/orang tua ke modul *Leaderboard Web App*.
- Orang tua yang menonton di aula dapat melakukan *scan* QR Code melalui HP mereka, dan mem- *filter* nama anak mereka secara mandiri, langsung mencari tahu peringkat putranya tanpa harus menunggu giliran rotasi layar di panggung.

> **Skenario Kasus (Use-Case): Hari H TO Akbar & UH Biasa**
> *Sekolah sedang menggelar Try Out Akbar se-Provinsi yang ditayangkan di Proyektor Aula (70% Layar). Namun, seorang guru di Kelas X-A diam-diam sedang menyelenggarakan Ulangan Harian Sejarah. Nilai UH kelas X-A tersebut tetap dapat dimonitor secara diskret di panel samping (30% Layar) tanpa merusak kemeriahan acara TO Akbar.*

---
*Dokumentasi ini disahkan oleh Tim Arsitektur Sistem SINTA-SaaS.*
