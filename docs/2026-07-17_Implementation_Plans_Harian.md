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
