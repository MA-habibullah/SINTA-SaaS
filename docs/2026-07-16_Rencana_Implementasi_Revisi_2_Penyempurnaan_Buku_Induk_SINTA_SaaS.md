# Rencana Implementasi (Revisi 2): Penyempurnaan Buku Induk SINTA-SaaS

Berdasarkan masukan terbaru Anda, rencana implementasi disesuaikan agar pencetakan QR Code bersifat opsional (dapat diaktifkan/dinonaktifkan secara manual oleh operator sebelum mencetak).

---

## Proposed Features & Rincian Teknis

### 1. Time-Travel Signatures (Tanda Tangan Masa Jabatan Kepsek)
* **Tujuan**: Memastikan nama Kepala Sekolah yang tercetak di dokumen sejarah (rapor/transkrip alumni) sesuai dengan pejabat yang bertugas saat itu, menggunakan tabel `riwayat_kepala_sekolah` yang sudah ada di database.
* **Rencana Perubahan**:
  * **Backend (`BukuIndukController.php`)**: Buat helper `getKepsekAtDate($tenantId, $tanggalCetak)`. Query akan mencari data dari `riwayat_kepala_sekolah` di mana `tanggal_mulai <= $tanggalCetak` dan `(tanggal_selesai >= $tanggalCetak OR tanggal_selesai IS NULL)`. Jika kosong, lakukan fallback ke kepala sekolah default di tabel `tenants`.
  * **Print Views (`print_transkrip_standar.php`, `print_rapot_merdeka.php`, dsb)**: Menggunakan data kepala sekolah dinamis hasil query tanggal cetak.
  * **Frontend (`views/buku_induk.php`)**: Menambahkan input **"Tanggal Tertera pada Dokumen"** (default: hari ini) di modal pilihan cetak sebelum meluncurkan URL cetak.

### 2. QR Code Verifikasi Opsional dengan Domain Dinamis
* **Tujuan**: Mencegah pemalsuan dokumen cetak dengan QR Code verifikasi.
* **Konfigurasi Fleksibel (Pilihan Manual/QR Code)**: Karena tidak semua sekolah menghendaki penggunaan QR Code untuk legalitas dokumen resmi, operator sekolah akan diberikan pilihan sebelum melakukan cetak:
  * **Tampilan UI**: Menambahkan checkbox **"Tampilkan QR Code Verifikasi"** pada modal opsi cetak.
  * **Parameter URL**: Mengirimkan parameter `show_qrcode=1` atau `show_qrcode=0` ke berkas cetak.
  * **Kondisional Render**: Jika parameter bernilai `0` (atau checkbox tidak dicentang), dokumen akan dirender secara manual (tanpa QR Code) seperti sedia kala. Jika bernilai `1`, QR Code dengan domain dinamis akan dimunculkan di samping tanda tangan.
* **Dynamic Domain**: URL verifikasi dibangun secara dinamis di backend: `$_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/SINTA-SaaS/verify/transkrip/' . $siswaUuid`.

### 3. [DIBATALKAN] Kustomisasi Formula Nilai Sekolah (NS)
* **Status**: **DIBATALKAN** atas permintaan user. 
* **Penjelasan**: Nilai yang diimpor dari Waka Kurikulum sudah berupa nilai jadi. Sistem Buku Induk hanya bertugas menampilkan rekap nilai tersebut tanpa melakukan kalkulasi rumus tambahan.

### 4. Pengarsipan Rapor PDF Statis & Fleksibel (Re-Generate)
* **Tujuan**: Rapor yang telah disetujui akan di-render sebagai berkas PDF/A statis dan disimpan di `/storage/archive/` agar layout dan nilai tidak bergeser di masa depan akibat pemutakhiran sistem.
* **Logika Fleksibilitas**: Sistem **tidak boleh kaku**. Jika terdapat kesalahan data atau nilai, operator dapat mengedit nilai di database, lalu mengklik tombol **"Perbarui Arsip Rapor (Re-Generate)"** pada UI untuk me-render ulang berkas PDF statis terbaru.

### 5. Validasi Unggahan Excel Nilai (*Pre-Upload Validator*)
* **Tujuan**: Mencegah data korup masuk ke database akibat kesalahan pengisian Excel oleh guru.
* **Rencana Perubahan**:
  * Setelah file Excel diunggah, backend akan memproses baris secara temporer di memori.
  * Sistem akan menampilkan halaman konfirmasi pratinjau (*validation preview screen*) berupa tabel:
    * **Hijau**: Nilai valid dan siswa ditemukan.
    * **Merah**: Pesan eror (misal: "NISN tidak cocok", "Nilai tidak valid", "Mata Pelajaran tidak sesuai kurikulum kelas").
  * Pengguna hanya bisa menyimpan jika seluruh data telah tervalidasi atau mengonfirmasi rekonsiliasi data eror.

### 6. Peringatan Toleransi Agama Reaktif pada Grid Nilai
* **Tujuan**: Mencegah Wali Kelas/Guru memasukkan nilai agama yang salah (tidak sesuai keyakinan siswa) pada saat pengisian grid nilai.
* **Rencana Perubahan**:
  * Pada Vue.js grid input (`tab_input_nilai_rapor`), sistem membaca data agama masing-masing siswa.
  * Kolom input mata pelajaran agama yang tidak sesuai dengan agama siswa bersangkutan akan diberi warna sorotan kuning/merah atau diberi tooltip peringatan agar guru langsung menyadari kesalahan sebelum menyimpan.

---

## Proposed Code Changes

### [MODIFY] [BukuIndukController.php](file:///c:/xampp/htdocs/SINTA-SaaS/app/Controllers/BukuIndukController.php)
* Implementasi helper `getKepsekAtDate()`.
* Endpoint untuk rendering dynamic domain QR Code.
* Logika pembacaan dan penyimpanan file PDF statis di `/storage/archive/` dengan opsi overwrite saat re-generate.
* Endpoint JSON pre-validator untuk upload Excel.

### [MODIFY] [views/buku_induk.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/buku_induk.php)
* Tambahkan picker `tanggal_cetak` dan checkbox `Tampilkan QR Code Verifikasi` pada modal cetak.
* Tambahkan tombol "Re-Generate Rapor" pada daftar siswa yang rapornya telah difinalisasi.
* Integrasikan state warning agama langsung ke komponen grid input nilai Vue.

### [MODIFY] Berkas Cetak Rapor & Transkrip
* [print_transkrip_standar.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/print_transkrip_standar.php)
* [print_rapot_merdeka.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/print_rapot_merdeka.php)
* [print_rapot_k13.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/print_rapot_k13.php)
* [print_rapot_ktsp.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/print_rapot_ktsp.php)
  * Menampilkan kepala sekolah yang tepat berdasarkan `tanggal_cetak`.
  * Merender QR Code dengan domain dinamis di samping tanda tangan **hanya jika** parameter URL `show_qrcode` bernilai `1` / `true`.

### [NEW] [verify_transkrip.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/verify_transkrip.php)
* View mandiri publik untuk verifikasi tanda tangan digital & keaslian nilai siswa.

---

## Verification Plan

1. **Uji Coba Ganti Domain & Opsi QR Code:**
   * Lakukan cetak dokumen dengan checkbox QR Code **tidak tercentang** -> Pastikan area tanda tangan kosong/manual tanpa QR Code.
   * Lakukan cetak dokumen dengan checkbox QR Code **tercentang** -> Pastikan QR Code muncul di samping tanda tangan dan link mengarah ke domain dinamis host aktif.
2. **Uji Coba Re-Generate Rapor:**
   * Finalisasi rapor siswa A (PDF tersimpan di server).
   * Ubah salah satu nilai siswa A di database.
   * Klik "Perbarui Arsip Rapor (Re-Generate)", pastikan file PDF di `/storage/archive/` terupdate dengan nilai baru yang benar.
3. **Uji Coba Pre-Upload Excel:**
   * Upload file Excel yang sengaja diisi format nilai yang salah atau NISN acak. Pastikan validator menampilkan baris merah berisi petunjuk kesalahan sebelum data masuk database.
