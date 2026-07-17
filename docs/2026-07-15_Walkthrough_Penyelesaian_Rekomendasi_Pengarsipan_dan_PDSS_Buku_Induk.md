# Walkthrough: Pengarsipan Buku Induk & PDSS SNBP

Berikut adalah ringkasan modifikasi dan fitur baru yang berhasil diimplementasikan untuk meningkatkan keamanan data dan fungsionalitas cetak digital massal pada sistem Buku Induk SINTA-SaaS:

---

## Fitur yang Diselesaikan

### 1. Sistem Penguncian Nilai Tingkat Rombel (Kelas)
* **Penjelasan**: Admin/operator sekolah kini dapat mengunci input nilai rapor per kelas per tahun ajaran untuk mencegah manipulasi data historis siswa yang tidak sengaja.
* **Keamanan**: Saat status rombel terkunci (`is_locked = 1`), backend akan otomatis menolak setiap request penyimpanan (`save`) dan penghapusan (`deleteSiswaGradesApi`) nilai rapor.

### 2. Audit Log Nilai Rapor
* **Penjelasan**: Setiap kali ada penambahan, perubahan, atau penghapusan nilai rapor, sistem secara otomatis merekam histori perubahan di tabel `log_nilai_rapor` (mencatat user pengubah, nilai lama, nilai baru, serta tipe aksi `INSERT`, `UPDATE`, atau `DELETE`).

### 3. Cetak Rapor Rombel Massal (Bulk Print)
* **Penjelasan**: Menambahkan fitur cetak rapor massal per semester dan cetak identitas massal bagi seluruh siswa di suatu kelas rombel dalam satu klik.
* **Layout**: Disediakan template cetak massal yang responsif dan rapi (menggunakan layout CSS `page-break-after: always;` untuk memisahkan halaman cetak antar siswa):
  * [print_rapot_bulk_merdeka.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/print_rapot_bulk_merdeka.php)
  * [print_rapot_bulk_k13.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/print_rapot_bulk_k13.php)
  * [print_rapot_bulk_ktsp.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/print_rapot_bulk_ktsp.php)

### 4. Ekspor Data Format PDSS SNBP
* **Penjelasan**: Memudahkan sekolah mengekspor data nilai rapor semester 1 s.d 5 untuk seluruh siswa kelas XII ke dalam satu file Excel (.xlsx) dengan sekali klik. File ini diformat khusus agar sesuai dengan kebutuhan pengisian portal SNBP Kemendikbudristek.

---

## Berkas yang Dimodifikasi & Baru

### Backend & Controller
* [MODIFY] [NilaiRaporController.php](file:///c:/xampp/htdocs/SINTA-SaaS/app/Controllers/NilaiRaporController.php) (Audit log & check status lock rombel pada API save/delete)
* [MODIFY] [BukuIndukController.php](file:///c:/xampp/htdocs/SINTA-SaaS/app/Controllers/BukuIndukController.php) (Endpoint API bulk print, toggle status lock kelas, & ekspor PDSS Excel)

### Frontend & Views
* [MODIFY] [buku_induk.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/buku_induk.php) (Penambahan tombol cetak massal, ekspor PDSS, & toogle lock rombel)
* [NEW] [print_rapot_bulk_merdeka.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/print_rapot_bulk_merdeka.php) (Template bulk print Kurikulum Merdeka)
* [NEW] [print_rapot_bulk_k13.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/print_rapot_bulk_k13.php) (Template bulk print Kurikulum K-13)
* [NEW] [print_rapot_bulk_ktsp.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/print_rapot_bulk_ktsp.php) (Template bulk print Kurikulum KTSP)

---

## Hasil Pengujian Fungsional
* Berkas pengujian [test_pengarsipan_dan_lock.php](file:///c:/xampp/htdocs/SINTA-SaaS/scratch/test_pengarsipan_dan_lock.php) berhasil memverifikasi pembacaan status lock rombel, penulisan log audit, dan integritas database relasional dengan status **SUKSES 100%**.
