# Walkthrough Harian — 2026-07-17

---
## Integrasi AeroScan (Pemindai Dokumen) ke SINTA-SaaS
**Waktu**: Pagi WIB
**Jenis**: Feature

Mengintegrasikan alat AeroScan (Pemindai & Kompresor Dokumen) ke platform SINTA-SaaS.

**File yang dibuat/diubah:**
- database/migrations/2026_07_16_03_add_document_scanner_menu.php — Mendaftarkan menu "Pemindai Dokumen" (ID 60) dan menyinkronkan ke semua tenant
- index.php — Menambahkan route /utility/document-scanner
- pp/Controllers/UtilityController.php [BARU] — Action documentScanner()
- iews/utility/document_scanner.php [BARU] — View pemindai, dibungkus IIFE untuk kompatibilitas Hotwire Turbo, penanganan race condition Lucide Icons & OpenCV.js, dan penutupan kamera otomatis saat navigasi

**Verifikasi:** php -l semua file baru = No syntax errors. Migrasi berhasil: Menu ID 60 tersinkron ke 3 sekolah.

---
## Audit & Perbaikan Keamanan Menyeluruh (Anti-XSS)
**Waktu**: Siang WIB
**Jenis**: Security Fix

Audit dan perbaikan keamanan data di 13 berkas view — pencegahan kebocoran data dan kerentanan XSS.

**Perubahan yang dilakukan:**
1. **Pembersihan hash password** di iews/tambah_siswa.php — unset() pada password sebelum diekspor ke JSON client-side
2. **Anti-XSS Script (Kategori A)** — Menambahkan flag JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT pada json_encode() di 13 view: 	ambah_siswa.php, sekolah_profil.php, identitas_sekolah.php, pdss_index.php, master_kelembagaan.php, master_bk.php, login_view.php, siswa_login_view.php, dashboard_view.php, uku_induk.php, k/master_kampus_prodi_layout.php, ctivity_logs.php, sekolah/agenda_terpadu.php
3. **Anti-XSS Atribut HTML (Kategori B)** — htmlspecialchars(..., ENT_QUOTES, 'UTF-8') di sekolah/agenda_terpadu.php dan humas/pengumuman.php

---
## Fix Error Migrasi Saat Deploy (Galat: fungsi 'up' tidak ada)
**Waktu**: Siang WIB
**Jenis**: Bug Fix

**Masalah:** 3 file migrasi menggunakan format skrip imperatif langsung sehingga migrate.php tidak bisa mendeteksi fungsi up dan migrasi diulang terus setiap deploy.

**Root Cause:** migrate.php menggunakan $migrationData = require  lalu mengecek isset(['up']). File dengan format skrip langsung tidak mengembalikan array, jadi selalu gagal dicatat ke tabel migrations.

**File yang diperbaiki** (konversi dari skrip imperatif ke format eturn [...]):
- database/migrations/2026_07_11_03_tambah_kode_portofolio_prodi.php
- database/migrations/2026_07_14_00_create_pdss_tables.php
- database/migrations/2026_07_14_01_alter_pdss_tables_for_multiyear.php

**Commit:** ix: konversi 3 file migrasi ke format return array agar didukung migrate.php (35d94c4)

---
## Migrasi Pemuatan Data ke Model AJAX Fetch Dinamis (Zero Data Leakage)
**Waktu**: Siang WIB
**Jenis**: Security Refactor

Menghapus seluruh injeksi data mentah via inline PHP json_encode() di tag <script> dan memigrasikannya ke pemuatan asinkronus Axios/fetch di Vue onMounted().

**Modul yang dimigrasi:**
1. **Edit & Tambah Siswa** — Endpoint get_siswa_detail & get_siswa_draft di SiswaController.php; data siswa sensitif (NIK, KK, password) tidak lagi tercetak di source page
2. **Dashboard** — Endpoint get_dashboard_stats di DashboardController.php; siswaList, gtkList, ecentChanges dimuat via AJAX
3. **Profil & Identitas Sekolah** — Endpoint get_profile_detail di SekolahController.php dan get_tenant_detail di TenantController.php
4. **BK (Master Kampus/Prodi)** — Sudah aman secara bawaan, tidak ada perubahan
5. **Buku Induk** — Endpoint get_options di BukuIndukController.php; filter dropdown dimuat via AJAX
6. **Agenda Terpadu & Gantt** — Endpoint get_agenda_data di AgendaController.php; FullCalendar & Frappe Gantt dimuat asinkronus
7. **Log Aktivitas** — Sudah aman secara bawaan, tidak ada perubahan

**Hasil:** View Page Source (Ctrl+U) tidak lagi menampilkan data pribadi siswa, profil sekolah, atau record agenda.

---
## Fix 404 AJAX /sekolah/profil — Profil Sekolah
**Waktu**: 19:27 WIB
**Jenis**: Bug Fix

**Masalah:** Halaman /sekolah/identitas gagal memuat data profil sekolah secara dinamis karena request AJAX mengarah ke route yang tidak terdaftar.

**Root Cause:** iews/sekolah_profil.php baris 511 mengirim request ke /sekolah/profil padahal route yang ada di index.php adalah /sekolah/identitas.

**File diubah:** iews/sekolah_profil.php baris 511
`diff
- axios.get('/SINTA-SaaS/sekolah/profil?ajax=1&action=get_profile_detail&tenant_id=...')
+ axios.get('/SINTA-SaaS/sekolah/identitas?ajax=1&action=get_profile_detail&tenant_id=...')
`

---
## Fix 404 AJAX /sekolah/agenda — Agenda Terpadu
**Waktu**: 19:31 WIB
**Jenis**: Bug Fix

**Masalah:** Halaman Agenda Terpadu gagal memuat data kalender dan Gantt chart karena request AJAX mengarah ke route yang tidak terdaftar.

**Root Cause:** iews/sekolah/agenda_terpadu.php baris 924 mengirim request ke /sekolah/agenda padahal route yang ada di index.php adalah /informasi/agenda, dilayani oleh AgendaController::index() yang sudah memiliki handler ction=get_agenda_data.

**File diubah:** iews/sekolah/agenda_terpadu.php baris 924
`diff
- axios.get('/SINTA-SaaS/sekolah/agenda?ajax=1&action=get_agenda_data&filter_tenant_id=...')
+ axios.get('/SINTA-SaaS/informasi/agenda?ajax=1&action=get_agenda_data&filter_tenant_id=...')
`

---
## Update Aturan Walkthroughs di AGENTS.md
**Waktu**: 19:33 WIB
**Jenis**: Configuration

Aturan walkthrough di .agents/AGENTS.md diperbarui dari satu file per tugas menjadi satu file gabungan per hari (YYYY-MM-DD_Walkthrough_Harian.md). Setiap entri di-append ke file yang sama dengan format standar yang mencakup waktu, jenis pekerjaan, dan deskripsi singkat.

---
## Update Aturan Implementation Plans di AGENTS.md
**Waktu**: 19:43 WIB
**Jenis**: Configuration

Aturan Implementation Plans di .agents/AGENTS.md diperbarui dari satu file terpisah per plan menjadi satu file gabungan per hari (YYYY-MM-DD_Implementation_Plans_Harian.md). Format entri standar: Waktu, Status (Draft/Disetujui/Dieksekusi), dan Deskripsi ringkas. File lama yang sudah ada di docs/ tidak disentuh.

---
## Hardening Keamanan Halaman Verifikasi Transkrip
**Waktu**: 19:53 WIB
**Jenis**: Security Fix

**File diubah:** pp/Controllers/BukuIndukController.php method erifyTranskrip()

**Perlindungan yang ditambahkan:**
1. **Validasi UUID v4** — Parameter GET ?id= divalidasi dengan regex ketat /^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i. Request dengan string non-UUID langsung ditolak dengan HTTP 400.
2. **HTTP Security Headers** — 7 header keamanan: X-Content-Type-Options: nosniff, X-Frame-Options: DENY, X-XSS-Protection, Referrer-Policy, Content-Security-Policy (CSP), Cache-Control: no-store, Pragma: no-cache
3. **Rate Limiting berbasis IP** — Max 30 request/menit per IP. Counter disimpan di storage/app/rate_limit/vt_{md5_ip}.json. Request melebihi batas mendapat HTTP 429.
4. **Error Response Aman** — Ganti die() dengan http_response_code() + HTML response yang bersih tanpa mengekspos detail internal (400/404/429/500)
5. **Penghapusan Data Sensitif** — unset() kolom password, 	oken, pi_key, 
ik, 
o_kk, 
ama_ibu, 
ama_ayah dari array $siswa sebelum dikirim ke view
6. **Query SELECT lebih sempit** — Ganti SELECT d.* menjadi hanya kolom yang dibutuhkan di query transkrip nilai

---
## Zero Data Leakage Halaman Verifikasi Transkrip
**Waktu**: 20:01 WIB
**Jenis**: Security Fix

**Masalah:** Data siswa dan transkrip nilai tercetak secara statis di HTML sumber Halaman Verifikasi Transkrip, memungkinkan pihak ketiga mengakses informasi sensitif secara langsung tanpa autentikasi / rate limit yang memadai (masalah kebocoran data).

**Root Cause:** Halaman  iews/verify_transkrip.php sebelumnya menggunakan inline PHP echo untuk memunculkan data nama, NISN, TTL, sekolah penerbit, dan transkrip nilai langsung ke dalam markup HTML halaman.

**Perubahan yang dilakukan:**
1. Mengubah routing utama di index.php untuk mendukung /api/v1/verify-transkrip/data.
2. Mengubah logic verifyTranskrip() di app/Controllers/BukuIndukController.php untuk membatasi query awal, menerbitkan One-Time Token (OTT) di session, dan hanya merender skeleton HTML.
3. Menyediakan method API asinkron verifyTranskripApi() yang mengembalikan data siswa dan transkrip dalam format JSON setelah memverifikasi token OTT dan membersihkan data sensitif.
4. Menulis ulang views/verify_transkrip.php ke model AJAX Fetch menggunakan vanilla JavaScript fetch() agar data dirender secara dinamis di memori browser, menjaga HTML Page Source (Ctrl+U) dan tab Element Inspect tetap bersih dari kebocoran data database statis.

---
## Proteksi Token Leakage & Skeleton Exposure di Verifikasi Transkrip
**Waktu**: 20:25 WIB
**Jenis**: Security Hardening

**Perubahan yang dilakukan:**
1. **Mengeliminasi token di JavaScript:** AJAX request ke `/api/v1/verify-transkrip/data` kini tidak lagi membawa query parameter token, melainkan bergantung penuh pada session cookie bawaan browser secara aman.
2. **Merender markup secara dinamis:** Tag layout `#dataView` dipindahkan sepenuhnya ke dalam JavaScript string template di berkas `views/verify_transkrip.php`. 
3. **Mencegah exposure layout kosong:** Skeleton layout tidak lagi terpampang jelas di Ctrl+U atau Inspect Element sebelum status API response sukses. Markup layout hanya disuntikkan ke dalam `#dataViewContainer` setelah data sukses tervalidasi secara internal di server.
