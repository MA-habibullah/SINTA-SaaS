# 2026-07-27 Implementation Plans Harian

---
## [Fitur Audit, Audit Perolehan, Tracking Peminjaman, & Manajemen Penghapusan/Afkir Buku Perpustakaan]
**Waktu**: 16:16 WIB
**Status**: Draft / Menunggu Persetujuan User

# Implementation Plan: Fitur Audit, Audit Perolehan, Tracking Peminjaman, & Manajemen Penghapusan/Afkir Buku Perpustakaan

Fitur ini menyediakan pelacakan (*traceability*) dan transparansi lengkap untuk setiap fisik eksemplar buku di perpustakaan. Pengguna/Pustakawan dapat mengetahui dengan presisi:
- **Perolehan**: Tanggal beli/terima, jumlah unit, harga perolehan, dan sumber perolehan (Dana BOS, Dana BPOPP, Sumbangan Siswa, Sumbangan Alumni, Hibah Pemerintah, Pembelian Mandiri, dll.) beserta nama penyumbang/vendor.
- **Peminjaman Real-time**: Siapa yang sedang meminjam fisik buku tersebut, tanggal pinjam, dan estimasi tanggal kembali.
- **Riwayat Terakhir Dibaca/Dipinjam**: Kapan terakhir kali eksemplar tersebut dipinjam/dibaca dan oleh siapa.
- **Lokasi Fisik**: Posisi presisi buku (Gedung, Ruangan, Nama Rak, Baris) atau posisi di Gudang / Ruang Perbaikan / Penghapusan.
- **Audit Penghapusan / Afkir**: Rekapitulasi buku rusak/dibuang (jumlah, asal perolehan, tanggal perolehan, alasan penghapusan, dan posisi barang saat ini).

---

## Proposed Changes

### Database Migration

#### [NEW] [2026_07_27_01_enhance_perpus_eksemplar_audit.php](file:///c:/xampp/htdocs/SINTA-SaaS/database/migrations/2026_07_27_01_enhance_perpus_eksemplar_audit.php)
- Meng-update tabel `perpus_eksemplar` untuk mendukung audit perolehan & penghapusan buku:
  - Perluasan ENUM `sumber_buku`: menambahkan `'Dana BPOPP'`, `'Hibah Pemda'`, `'Sumbangan Perorangan'`, `'Bantuan Lainnya'`.
  - Penambahan kolom `sumber_pemberi` (`VARCHAR(255) NULL`): Menyimpan nama vendor/pihak alumni/pemberi hibah.
  - Perluasan ENUM `kondisi`: menambahkan `'Afkir/Dihapuskan'`.
  - Perluasan ENUM `status`: menambahkan `'Dihapuskan/Afkir'`, `'Di Gudang'`.
  - Penambahan kolom `tanggal_penghapusan` (`DATE NULL`) & `alasan_penghapusan` (`TEXT NULL`).
  - Mengikuti standar migration SaaS `return ['up' => ..., 'down' => ...]` dengan `IF NOT EXISTS` / `SHOW COLUMNS`.

---

### Backend Models & Controllers

#### [MODIFY] [Perpustakaan.php](file:///c:/xampp/htdocs/SINTA-SaaS/app/Models/Perpustakaan.php)
- Menambahkan method `getBibliografiTraceability(string $tenantId, string $bibliografiId): array`:
  - Mengambil seluruh eksemplar dari suatu judul buku.
  - Menghubungkan ke `perpus_sirkulasi` (left join) untuk mengambil status peminjam aktif (nama anggota, kelas/role, tanggal pinjam, tenggat kembali).
  - Menghubungkan ke riwayat peminjaman terakhir (*last borrow history*) untuk mengetahui kapan terakhir kali buku fisik tersebut dipinjam/dibaca.
  - Menghubungkan ke `perpus_lokasi_rak` untuk rincian lokasi fisik (Gedung, Lantai, Ruangan, Rak, Baris).
  - Rekapitulasi statistik perolehan (Total Eksemplar, Total Dibeli, Total BOS/BPOPP/Sumbangan, Total Dipinjam, Total di Rak, Total Rusak, Total Afkir/Dihapuskan).
- Menambahkan method `saveEksemplar()` / update handling untuk kolom `sumber_pemberi`, `tanggal_penghapusan`, dan `alasan_penghapusan`.

#### [MODIFY] [PerpustakaanController.php](file:///c:/xampp/htdocs/SINTA-SaaS/app/Controllers/PerpustakaanController.php)
- Menambahkan API Endpoint `/api/v1/perpustakaan/katalog/traceability?id={bibliografi_id}`:
  - Merespons data audit eksemplar dalam format JSON yang terstandardisasi.
- Memperbarui handler pencatatan eksemplar & penghapusan buku.

---

### Frontend Views & Modal Interface

#### [MODIFY] [katalog.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/perpustakaan/katalog.php)
- Menambahkan Tombol & Modal **"🔍 Audit Lifecycle & Tracking Eksemplar"** di setiap baris tabel katalog buku:
  - **Header Card Summary**: Total Unit, Tersedia di Rak, sedang Dipinjam, Rusak, & Afkir/Dibuang.
  - **Tabel Tracking Eksemplar (Per Barcode / No. Induk)**:
    - *Barcode / No. Induk*
    - *Sumber Perolehan*: Dana BOS, BPOPP, Sumbangan Alumni (beserta Pemberi/Vendor & Tgl Beli).
    - *Lokasi Fisik*: Rak A-01 / Gudang / Ruang Perbaikan.
    - *Status Peminjaman*: Siapa peminjam aktif saat ini, tgl pinjam & tgl kembali (jika sedang dipinjam).
    - *Terakhir Dipinjam/Dibaca*: Nama peminjam & tgl peminjaman terakhir.
    - *Kondisi & Status*: Baik / Rusak / Dihapuskan (afkir) beserta alasan & tanggal penghapusan.
- Penyesuaian Modal **Tambah / Edit Eksemplar**:
  - Pilihan Sumber Perolehan (Dana BOS, BPOPP, Sumbangan Alumni, Hibah Pemda, dsb.).
  - Input Nama Pemberi / Vendor.
  - Form Alasan & Tanggal Penghapusan jika status diubah menjadi `Afkir/Dihapuskan`.

---

## Verification Plan

### Automated Tests
1. Execution DB Migration:
   ```powershell
   php migrate.php up
   ```
2. PHPStan Static Analysis (Level 9):
   ```powershell
   vendor/bin/phpstan analyse app/Controllers/PerpustakaanController.php app/Models/Perpustakaan.php views/perpustakaan/katalog.php --level=9
   ```
3. Automated Security Audit Script:
   ```powershell
   php scratch/tests/test_security_audit.php
   ```

### Manual Verification
1. Uji penambahan eksemplar dengan sumber dana bervariasi (BOS, BPOPP, Alumni).
2. Klik tombol **Audit Lifecycle** pada salah satu judul buku:
   - Verifikasi apakah jumlah buku, tanggal beli, sumber perolehan, peminjam saat ini, dan terakhir dipinjam tampil dengan akurat.
3. Ubah kondisi eksemplar menjadi **Afkir/Dihapuskan**:
   - Verifikasi apakah status afkir, tanggal penghapusan, dan asal perolehan terekam dengan jelas.
