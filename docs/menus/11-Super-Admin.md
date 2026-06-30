# Dokumentasi Modul 11 - Super Admin

## 1. Pendahuluan
Modul **Super Admin Global** berada di ruang lingkup ( *scope* ) tertinggi dalam aplikasi berarsitektur *Multi-Tenant* SINTA-SaaS. Halaman ini adalah *God Mode* dari sistem. Hanya *user* yang secara keras ( *hard-coded* di basis data) memiliki `role_id = 1` (`super_admin`) yang bisa mengakses *controller* rute ini.

## 2. Alur Kerja & Arsitektur Utama

### A. Isolasi Kueri (*Tenant Bypass*)
Sistem SINTA-SaaS secara standar menyisipkan parameter pencegah kebocoran data di dalam *Query Builder* (Setiap *query* otomatis ditambahkan ekstensi `WHERE tenant_id = $_SESSION['tenant_id']`). 
- Namun, saat Modul Super Admin ini terbuka, lapisan perlindungan ini di-*bypass* agar dapat mencetak atau melakukan iterasi terhadap SELURUH sekolah (`tenants`) dari Sabang sampai Merauke yang terdaftar.

### B. Manajemen Tenants
1. **Penambahan Sekolah Baru:** Super Admin mendata profil singkat, menghasilkan *subdomain* khusus (jika diperlukan), dan mendaftarkan 1 akun pertama bertipe *Operator Sekolah* untuk institusi baru tersebut.
2. **Impersonasi (*Login As*):** Fitur krusial bagi Super Admin (biasanya *Tim Support* atau *Developer*). Tanpa menggunakan alat peretas *password*, sistem merubah *pointer* Sesi PHP seolah-olah Super Admin adalah Kepala Sekolah atau Admin dari *Tenant* yang dituju, untuk menguji (*troubleshoot*) laporan keluhan dari klien (sekolah). Saat selesai, mereka bisa memulihkan sesi kembali ke *God Mode*.
3. **Pembekuan / Terminasi Tenant:** Merubah _flag_ tabel `tenants` ke status *Suspended/Inactive*. Akibatnya, siapapun dari institusi tersebut yang berusaha _login_ akan ditolak oleh `SessionManager`.

### C. Server & Error Monitor
- Ini adalah implementasi modul diagnostik bawaan.
- **Server Monitor:** Di sisi *Backend* menjalankan kueri sistem operasi bawaan seperti `shell_exec('free -m')` (RAM) atau `shell_exec('df -h /')` (Disk Space) dan menerjemahkannya kembali menjadi visual *Gauge Chart* (mirip speedometer mobil) di antarmuka Web. Membantu developer menyadari potensi krisis tanpa perlu melakukan SSH terminal ke VPS.
- **Error Logs Viewer:** Sistem menembak pembaca file (*PHP filesystem reader*) langsung ke dalam fail `app/Logs/error.log` milik Apache/Nginx atau sistem lokal aplikasi. 

## 3. Aspek Keamanan Berlapis (Defense in Depth)
- Karena modul ini sangat riskan, perlindungannya dilakukan bukan hanya di sisi peramban (UI yang disembunyikan bagi guru/siswa).
- **Backend Guard:** Rute `/api/v1/super-admin/*` mutlak memeriksa apakah sesi yang mengirimkan muatan (*payload*) memiliki token `super_admin`. Bila terjadi upaya serangan *Bypass* dari pihak yang menyalahgunakan *token JWT / Session* palsu, mereka akan dihalau dan log insiden ini tercatat keras pada tabel sekuriti khusus.
