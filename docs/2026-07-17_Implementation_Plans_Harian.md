# Implementation Plans Harian — 2026-07-17

---
## Hardening Keamanan Halaman Verifikasi Transkrip
**Waktu**: 19:51 WIB
**Status**: Dieksekusi
**Deskripsi**: Memperkuat keamanan halaman publik /verify-transkrip dari berbagai vektor serangan:
1. Validasi format UUID pada parameter GET id (reject non-UUID string)
2. Tambah HTTP Security Headers (CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy)
3. Rate limiting sederhana berbasis IP menggunakan file-based counter di scratch/
4. Perbaiki die() agar tidak expose detail stack trace / HTML mentah
5. File: app/Controllers/BukuIndukController.php (method verifyTranskrip) + views/verify_transkrip.php

---
## Zero Data Leakage pada Halaman Verifikasi Transkrip
**Waktu**: 20:00 WIB
**Status**: Dieksekusi
**Deskripsi**: Melindungi data siswa di halaman publik /verify-transkrip agar tidak terpapar pada View Page Source (Ctrl+U) maupun Inspect Element dengan melakukan migrasi ke model dynamic client-side rendering (AJAX Fetch):
1. Mengubah /verify-transkrip agar hanya merender skeleton HTML dan menerbitkan secure one-time session token valid 5 menit.
2. Menyediakan endpoint API baru /api/v1/verify-transkrip/data yang melayani data siswa dalam format JSON setelah memverifikasi one-time token.
3. Menghapus data sensitif di sisi server menggunakan PHP unset() sebelum data JSON dikirim ke client.
4. Menambahkan script AJAX di views/verify_transkrip.php untuk mengambil data via fetch() secara asinkron dan memanipulasi DOM secara dinamis.

---
## Eliminasi Token Leakage pada Verifikasi Transkrip
**Waktu**: 20:25 WIB
**Status**: Dieksekusi
**Deskripsi**: Menghapus pencetakan token di tag <script> views/verify_transkrip.php untuk mencegah pencurian token lewat Ctrl+U (Token Leakage):
1. Mengubah alur autentikasi API: AJAX fetch akan menggunakan session cookie bawaan secara otomatis tanpa membutuhkan query parameter token.
2. Di BukuIndukController::verifyTranskrip(), session t_siswa_id dan t_expires diset seperti biasa, tetapi tidak ada variabel token yang dikirim ke view.
3. Di BukuIndukController::verifyTranskripApi(), verifikasi didasarkan langsung pada session siswa_id dan waktu kedaluwarsa yang disimpan secara internal di server. Setelah data diambil sekali, session siswa_id langsung dihapus (one-time data access).
4. Di views/verify_transkrip.php, fetch diarahkan langsung ke /SINTA-SaaS/api/v1/verify-transkrip/data tanpa token parameter.
