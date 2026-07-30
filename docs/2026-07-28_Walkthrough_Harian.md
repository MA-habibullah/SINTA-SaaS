# Walkthrough Harian - 28 Juli 2026

---
## [Troubleshooting Wazuh Startup Timeout & Swap Space Configuration]
**Waktu**: 15:27 WIB
**Jenis**: Bug Fix / Troubleshooting / SysAdmin

### Deskripsi Perbaikan:
1. **Analisis Kendala**: Layanan `wazuh-indexer` and `wazuh-manager` mengalami kegagalan start (timeout) karena inisialisasi modul Java OpenSearch melebihi batas waktu default systemd (90 detik). Kendala diperparah karena VM Ubuntu memiliki memori **Swap Space sebesar 0 Bytes (tidak aktif)**, sehingga RAM 6 GB macet total saat meluncurkan seluruh layanan sekaligus. Ditambah lagi kendala delay inisialisasi yang memicu pesan *Wazuh dashboard server is not ready yet* di browser.
2. **Penyelesaian**:
   - Menambahkan konfigurasi *override* batas waktu systemd (`TimeoutStartSec=300`) untuk `wazuh-indexer` dan `wazuh-manager`.
   - Mengaktifkan memori Swap Space baru sebesar 4 GB pada berkas `/swapfile` untuk mencegah kehabisan RAM.
   - Menyediakan panduan langkah-demi-langkah mendalam untuk merestart layanan dashboard secara segar saat database indexer selesai diinisialisasi.
   - Melakukan pembaruan dokumen instalasi lengkap pada berkas `.md` dan `.html` di folder `scratch/wazuh/`.

### Berkas yang Diubah / Ditambahkan:
- `C:\xampp\htdocs\SINTA-SaaS\scratch\wazuh\dokumentasi_instalasi_wazuh.md` (Ditambahkan panduan setup server, monitoring status/auto-start, Tahap E penanganan eror "not ready", serta Bab 10 panduan eksplorasi visual UI)
- `C:\xampp\htdocs\SINTA-SaaS\scratch\wazuh\dokumentasi_instalasi_wazuh.html` (Redesign putih-biru muda & penambahan sanksi error serta petunjuk swap/timeout, Kendala 6, serta Seksi 7 panduan eksplorasi visual UI)

---
## [Pembuatan Dokumen Plan HTML SOP Instalasi Server 03 Payu Bersama]
**Waktu**: 15:38 WIB
**Jenis**: Feature / Documentation

### Deskripsi Perbaikan:
1. **Analisis Kendala**: User ingin mengubah file dokumen SOP biner `SOP_Instalasi_dan_Konfigurasi_Server_03.docx` menjadi berkas dokumentasi web rencana aksi/plan HTML yang berpenampilan premium, mudah dibaca, dan interaktif (putih dan biru muda).
2. **Penyelesaian**:
   - Membuat script ekstraktor PowerShell untuk mengekstrak isi XML paragraf dalam berkas Word `.docx`.
   - Menghasilkan berkas dokumentasi interaktif dengan sidebar navigasi, grid port modular, tabel SQL terisolasi, vhost Nginx ter-format, dan script GitHub Actions CI/CD.
   - Dilengkapi fungsi bar pencarian dinamis (Global Search) dan tombol klik-salin perintah terminal dengan efek toast.

### Berkas yang Diubah / Ditambahkan:
- `C:\xampp\htdocs\SINTA-SaaS\scratch\payu\read_docx.ps1` (Script ekstraktor paragraf Word DOCX)
- `C:\xampp\htdocs\SINTA-SaaS\scratch\payu\extracted_sop.txt` (Berkas teks mentah hasil ekstraksi)
- `C:\xampp\htdocs\SINTA-SaaS\scratch\payu\dokumentasi_instalasi_payu.html` (Berkas web HTML plan interaktif - Putih & Biru Muda)
- `C:\xampp\htdocs\SINTA-SaaS\scratch\payu\dokumentasi_instalasi_payu.md` (Berkas pelengkap teks Markdown)

---
## [Troubleshooting Akses Halaman Web Dashboard Wazuh Lemot/Lambat]
**Waktu**: 16:35 WIB
**Jenis**: Bug Fix / Optimization / SysAdmin

### Deskripsi Perbaikan:
1. **Analisis Kendala**: Akses halaman login dan dashboard Wazuh via IP Publik (`182.253.40.19`) melalui WAN sangat lambat saat pemuatan awal. Hal ini disebabkan oleh ukuran file aset JavaScript dashboard yang besar (~30MB) yang dikirim melalui kecepatan upload server (sekolah/vps) yang terbatas, diperlambat lagi oleh verifikasi sertifikat SSL *self-signed* dan pemindaian antivirus lokal.
2. **Penyelesaian**:
   - Menambahkan panduan optimasi performa dashboard (browsing caching, exclusion antivirus, penonaktifan pemeriksaan pencabutan sertifikat SSL Windows).
   - Memasukkan bab baru ke dalam dokumen panduan troubleshooting wazuh versi Markdown dan HTML.

### Berkas yang Diubah / Ditambahkan:
- `C:\xampp\htdocs\SINTA-SaaS\scratch\wazuh\dokumentasi_instalasi_wazuh.md` (Tahap F - Panduan Mengatasi Akses Halaman Web Dashboard yang Sangat Lambat)
- `C:\xampp\htdocs\SINTA-SaaS\scratch\wazuh\dokumentasi_instalasi_wazuh.html` (Kendala 7 - Akses Halaman Web Dashboard Sangat Lambat)

---
## [Integrasi Konfigurasi Riil & Setup PM2 Auto-Start pada Dokumen Payu]
**Waktu**: 17:21 WIB
**Jenis**: Enhancement / Documentation

### Deskripsi Perbaikan:
1. **Analisis Kendala**: Menyelaraskan berkas dokumentasi Payu Bersama dengan berkas konfigurasi riil (`file nano dokumentasi`) yang memuat data-source postgres_exporter, scrape target Mikrotik SNMP/UniFi pada Prometheus, serta target pembacaan logs terpusat Promtail. Ditambah menambahkan panduan setup PM2 Auto-Start agar aplikasi revive setelah restart server.
2. **Penyelesaian**:
   - Memperbarui file konfigurasi di dalam dokumentasi HTML dan Markdown Payu Bersama agar identik 100% dengan setelan riil di server.
   - Menambahkan sub-bab dan modul panduan PM2 Auto-Start (`pm2 startup` dan `pm2 save`).

### Berkas yang Diubah / Ditambahkan:
- `C:\xampp\htdocs\SINTA-SaaS\scratch\payu\dokumentasi_instalasi_payu.html` (Diperbarui dengan service postgres_exporter riil, konfigurasi prometheus.yml riil, promtail log targets riil, dan kartu auto-start PM2).
- `C:\xampp\htdocs\SINTA-SaaS\scratch\payu\dokumentasi_instalasi_payu.md` (Penyelarasan konten teks markdown).

---
## [Aktivasi Collapsible Sidebar & Perbaikan Bug Layout Overlapping Search]
**Waktu**: 17:24 WIB
**Jenis**: UI/UX Enhancement / Bug Fix

### Deskripsi Perbaikan:
1. **Analisis Kendala**: Layout dashboard pada berkas HTML Payu Bersama terlihat tumpang tindih (*overlapping* atau terbagi dua) saat menggunakan fungsi bar pencarian karena bug JavaScript `globalSearch()`. Perintah search memaksa *semua* modul dari seluruh menu yang tersembunyi untuk muncul bersamaan (mengubah inline display menjadi block). Ditambah lagi user meminta agar sidebar memiliki tombol buka/tutup (*collapsible*).
2. **Penyelesaian**:
   - Membuat tombol menu burger melayang (`toggle-btn`) dengan ikon SVG minimalis di kiri atas.
   - Menambahkan class `.collapsed` pada sidebar and `.expanded` pada main content lengkap dengan animasi transisi CSS (`cubic-bezier`).
   - Memperbaiki logika JS `switchSection` dan `globalSearch` agar secara dinamis mereset display style inline (`style.display = ''`) sehingga antar-menu tidak saling tumpang tindih.

### Berkas yang Diubah / Ditambahkan:
- `C:\xampp\htdocs\SINTA-SaaS\scratch\payu\dokumentasi_instalasi_payu.html` (Menambahkan CSS Toggle, floating button, dan perbaikan logika Javascript globalSearch).

---
## [Perbaikan Tag Penutup HTML Mismatched Div pada postgres_exporter]
**Waktu**: 17:26 WIB
**Jenis**: Bug Fix / Layout Repair

### Deskripsi Perbaikan:
1. **Analisis Kendala**: Layout halaman terpotong-potong secara horizontal menjadi beberapa kolom terpisah (sidebar, konten utama, prometheus card, dan footer berjejer ke samping) seperti yang ditunjukkan pada tangkapan layar user.
2. **Penyelesaian**:
   - Menemukan dan memperbaiki tag penutup ekstra `</div>` pada baris berkas unit `postgres_exporter.service` (line 1642: `WantedBy=multi-user.target</pre></div>`). Tag ekstra ini menutup secara prematur pembungkus utama `.main-content` sehingga elemen-elemen di bawahnya keluar dan menjadi sibling flexbox dari main content, lalu tersebar mendatar ke samping.
   - Menghapus tag ekstra tersebut sehingga seluruh konten berada kembali dalam satu kolom vertikal yang teratur.

### Berkas yang Diubah / Ditambahkan:
- `C:\xampp\htdocs\SINTA-SaaS\scratch\payu\dokumentasi_instalasi_payu.html` (Menghapus tag penutup div ilegal pada pre service).

---
## [Penyisipan Kredensial Lengkap Berkas .env ke Modul Aplikasi Payu]
**Waktu**: 17:29 WIB
**Jenis**: Enhancement / Documentation

### Deskripsi Perbaikan:
1. **Analisis Kendala**: Menambahkan salinan lengkap isi berkas `.env` asli untuk 6 modul utama dari berkas sumber `file nano dokumentasi` guna memastikan backup data konfigurasi riil tersimpan sempurna di dalam berkas dokumentasi Payu Bersama.
2. **Penyelesaian**:
   - Memasukkan isi berkas `.env` verbatim lengkap untuk `data-design`, `backoffice-backend`, `backoffice-dashboard`, `merchant-backend`, `merchant-dashboard`, dan `payment-page`.
   - Membersihkan tag pembuka `<pre>` ganda agar format blok salin teks bersih dan presisi.

### Berkas yang Diubah / Ditambahkan:
- `C:\xampp\htdocs\SINTA-SaaS\scratch\payu\dokumentasi_instalasi_payu.html` (Integrasi teks utuh .env dan pembersihan tag pre ganda).
- `C:\xampp\htdocs\SINTA-SaaS\scratch\payu\dokumentasi_instalasi_payu.md` (Menyelaraskan salinan berkas .env versi Markdown).

---
## [Integrasi Peringatan Wajib dan Tautan Google reCAPTCHA v2]
**Waktu**: 17:34 WIB
**Jenis**: Security / Documentation

### Deskripsi Perbaikan:
1. **Analisis Kendala**: Menambahkan pengumuman instruksi penting di dalam panduan agar pengguna membuat reCAPTCHA v2 dari Google console untuk mengamankan login dashboard backend/frontend dari spam dan bruteforce.
2. **Penyelesaian**:
   - Menambahkan kotak alert peringatan `alert-warning` di bagian inisialisasi modul .env dengan tautan langsung menuju URL admin pendaftaran situs google reCAPTCHA (ID Situs: 753180163).
   - Menyelaraskan teks penting ini ke berkas Markdown dokumentasi.

### Berkas yang Diubah / Ditambahkan:
- `C:\xampp\htdocs\SINTA-SaaS\scratch\payu\dokumentasi_instalasi_payu.html` (Menyisipkan modul peringatan reCAPTCHA dan link console admin).
- `C:\xampp\htdocs\SINTA-SaaS\scratch\payu\dokumentasi_instalasi_payu.md` (Menyisipkan anotasi penanda IMPORTANT reCAPTCHA).

---
## [Perbaikan Tampilan Alert Box reCAPTCHA Terbagi Kolom (Flex Wrap)]
**Waktu**: 17:35 WIB
**Jenis**: Bug Fix / CSS Layout Repair

### Deskripsi Perbaikan:
1. **Analisis Kendala**: Teks instruksi di dalam alert reCAPTCHA yang baru saja ditambahkan terbagi-bagi secara horizontal menjadi kolom-kolom vertikal kecil (seperti screenshot yang dikirim user).
2. **Penyelesaian**:
   - Hal ini disebabkan oleh CSS global `.alert` yang memiliki atribut `display: flex;` tanpa pembungkus internal tunggal, sehingga flexbox menganggap semua tag inline (`<strong>`, `<a>`, `<code>`) dan text-nodes di dalamnya sebagai elemen kolom baris baru (*flex items*).
   - Memperbaiki dengan menambahkan inline style override `display: block;` pada alert box tersebut untuk memulihkan alur pembacaan teks normal (block flow layout).

### Berkas yang Diubah / Ditambahkan:
- `C:\xampp\htdocs\SINTA-SaaS\scratch\payu\dokumentasi_instalasi_payu.html` (Menambahkan display: block inline override pada elemen alert-warning).

---
## [Pembersihan Berkas Sementara (Intermediate/Scratch Files)]
**Waktu**: 17:37 WIB
**Jenis**: Clean Up

### Deskripsi Perbaikan:
1. **Analisis Kendala**: Menghapus seluruh berkas sementara dan draf yang tidak lagi digunakan setelah seluruh data SOP sukses terintegrasi penuh ke dalam berkas dokumentasi final.
2. **Penyelesaian**:
   - Mengeksekusi penghapusan aman untuk 5 berkas: `SOP_Instalasi_dan_Konfigurasi_Server_03.docx`, `read_docx.ps1`, `extracted_sop.txt`, `file nano dokumentasi`, dan `riwayat_fisik_asli.txt`.
   - Hanya menyisahkan berkas final blueprint HTML (`dokumentasi_instalasi_payu.html`) dan Markdown (`dokumentasi_instalasi_payu.md`) di dalam folder `scratch/payu/`.

### Berkas yang Diubah / Ditambahkan:
- `C:\xampp\htdocs\SINTA-SaaS\scratch\payu\` (Folder dibersihkan dari berkas-berkas intermediate).
