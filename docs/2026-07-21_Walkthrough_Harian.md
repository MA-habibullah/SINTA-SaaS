---
## [Modul Keuangan SPP Dinamis & Integrasi PPDB]
**Waktu**: 07:22 WIB
**Jenis**: Feature

### Ringkasan Pekerjaan:
1. **Pembuatan File Controller**:
   - Membuat `app/Controllers/SppController.php` dengan implementasi CRUD Komponen, Tarif Acuan, Keringanan Siswa, Generator Tagihan Massal berbasis DB Transaction, POS Loket Kasir, Laporan Rekapitulasi Kas/Tunggakan, dan custom settings.
   - Menyediakan API Endpoint Hook `/api/v1/keuangan/buat-tagihan-ppdb` untuk integrasi otomatis pembuatan tagihan saat pendaftaran PPDB.
2. **Pembuatan File Antarmuka (Views)**:
   - Membuat `views/keuangan/pengaturan.php` (Pengaturan terminologi dan visibilitas).
   - Membuat `views/keuangan/master.php` (Manajemen Komponen & Tarif).
   - Membuat `views/keuangan/keringanan.php` (Manajemen Diskon & Beasiswa dengan Student Autocomplete).
   - Membuat `views/keuangan/generate.php` (Batch generator tagihan massal aman dari duplikasi).
   - Membuat `views/keuangan/kasir.php` (Loket Kasir POS dengan kalkulasi kembalian tunai, notifikasi tunggakan, dan cetak kuitansi digital).
   - Membuat `views/keuangan/laporan.php` (Laporan pemasukan & tunggakan, ekspor excel, print layout).
   - Membuat `views/keuangan/tagihan_saya.php` (Dashboard tagihan bagi profil login siswa).
3. **Pendaftaran Rute Utama**:
   - Menambahkan rute `/api/v1/keuangan/buat-tagihan-ppdb` di berkas `index.php`.
4. **Pengujian Integrasi**:
   - Membuat skrip `scratch/test_spp_billing.php` untuk memvalidasi transaksi keuangan dan PPDB hook secara CLI, seluruh test case berhasil dilalui dengan sukses.
