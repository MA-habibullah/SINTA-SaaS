# Implementation Plans Harian — 2026-07-20


---
## Revisi Cetak Buku Induk & Modul Beasiswa Siswa
**Waktu**: 10:00 WIB
**Status**: Dieksekusi

### 1. Analisis Status Data & Jawaban Pertanyaan User

#### A. Apakah data Seksi C, D, dan E sudah diambil langsung dari Database?
**JAWABAN: YA, SUDAH 100% TERINTEGRASI DATABASE.**
- **Seksi C (Perkembangan Peserta Didik)**: Mengambil data dari tabel `siswa` (kolom `sekolah_asal`, `tanggal_ijazah_sebelumnya`, `no_ijazah_sebelumnya`) dan tabel `registrasi` (kolom `sekolah_asal_mutasi`, `pindah_dari_tingkat`, `tanggal_masuk`, `pindah_no_surat`).
- **Seksi D (Meninggalkan Sekolah)**: Mengambil data dari tabel `siswa` (`tanggal_lulus`, `nomor_ijazah_kelulusan`, `keterangan_setelah_lulus`) dan tabel `registrasi` (`keluar_karena`, `tanggal_keluar`, `alasan_keluar`, `sekolah_tujuan`, `tingkat_ditinggalkan`, `diterima_di_tingkat`).
- **Seksi E (Lain-lain)**:
  - **1 & 2. Tinggi/Berat Badan & Kesehatan**: Mengambil data dari tabel `kesehatan_siswa` per semester (Semester 1 s.d. 6).
  - **3. Prestasi Peserta Didik**: Mengambil data dari tabel `prestasi_siswa` & `prestasi_siswa_anggota`.
  - **4. Beasiswa Peserta Didik**: Mengambil data dari tabel `riwayat_beasiswa`.

#### B. Apakah sudah ada Menu yang mengakomodir Nomor 4 (BEASISWA PESERTA DIDIK)?
**JAWABAN: BELUM ADA UI/MENU MANAGEMENT.**
- Tabel database `riwayat_beasiswa` **sudah ada** di database (memiliki kolom `jenis_beasiswa`, `sumber`, `tahun_menerima`, `nominal`), dan script cetak `print_buku_induk.php` sudah memiliki query `SELECT * FROM riwayat_beasiswa`.
- Namun, **belum ada menu UI / API endpoint** untuk menginput, mengedit, atau menghapus data riwayat beasiswa siswa tersebut.
- Oleh karena itu, kita akan buatkan **Modul & Tab CRUD Beasiswa Siswa** secara lengkap dan fleksibel (dapat diisi jenis beasiswa apa saja: PIP, Beasiswa Prestasi, Beasiswa Daerah/Pemda, Beasiswa Yayasan, dll.).

---

### 2. Rencana Perubahan (Proposed Changes)

#### A. Perbaikan Tampilan Cetak (`views/print_buku_induk.php`)

##### [MODIFY] print_buku_induk.php

1. **Penyingkatan Label NISN**:
   - Mengubah label header `NOMOR INDUK SISWA NASIONAL` pada baris 246 menjadi `NISN`.

2. **Revisi Nomor 7 (Jumlah Saudara)**:
   - Mengubah format lama 3 sub-baris (Kandung, Tiri, Angkat) pada baris 289–292 menjadi 1 baris ringkas:
     `html
     <tr>
         <td class="col-no">7.</td>
         <td class="col-label">Jumlah saudara kandung</td>
         <td class="col-colon">:</td>
         <td class="col-val"><?= htmlspecialchars() ?></td>
     </tr>
     `

---

#### B. Backend API CRUD Beasiswa (`app/Controllers/BukuIndukController.php`)

##### [MODIFY] BukuIndukController.php

Menambahkan endpoint API JSON untuk mengelola riwayat beasiswa siswa:
1. `storeBeasiswaApi()`: Menyimpan / menambah data beasiswa siswa baru ke tabel `riwayat_beasiswa`.
   - Param: `siswa_id`, `jenis_beasiswa` (e.g. PIP, Beasiswa Prestasi, Beasiswa KIP, Beasiswa Pemda), `sumber`, `tahun_menerima`, `nominal`.
2. `deleteBeasiswaApi()`: Menghapus baris beasiswa berdasarkan `id`.

---

#### C. Frontend Menu & UI Beasiswa (`views/buku_induk.php`)

##### [MODIFY] buku_induk.php

1. **Navigasi Tab Detail Siswa**:
   - Menambahkan Tab ke-6: `<i class="bi bi-gift"></i> Beasiswa` di modal detail Buku Induk Siswa.
2. **Panel Konten Tab Beasiswa**:
   - Menampilkan tabel riwayat beasiswa yang pernah diterima siswa.
   - Menyediakan form input tambah beasiswa (Jenis Beasiswa teks bebas agar fleksibel untuk PIP maupun beasiswa non-PIP, Sumber/Penyelenggara, Tahun Menerima, Nominal).
   - Menambahkan tombol aksi hapus untuk setiap entri beasiswa.
3. **Reaktivitas Vue 3**:
   - Menambahkan `formBeasiswa` state, `fetchBeasiswa()`, `submitBeasiswa()`, dan `deleteBeasiswa()`.

---

### 3. Verification Plan

#### Manual Verification
1. Buka halaman Cetak Buku Induk (`/cetak-buku-induk?id=...`).
2. Verifikasi header halaman cetak: label `NOMOR INDUK SISWA NASIONAL` kini tertulis `NISN`.
3. Verifikasi Poin 7 (Jumlah Saudara): kini tertulis `7. Jumlah saudara kandung : [Jumlah]` secara ringkas 1 baris.
4. Buka halaman Buku Induk (`/buku-induk`), klik **Lihat Detail** pada salah satu siswa.
5. Klik tab **Beasiswa**:
   - Tambahkan beasiswa jenis "PIP / KIP" -> Simpan.
   - Tambahkan beasiswa jenis "Beasiswa Prestasi Pemda" -> Simpan.
   - Tambahkan beasiswa jenis "Beasiswa Yayasan / Alumni" -> Simpan.
6. Kembali ke cetak Buku Induk (`/cetak-buku-induk?id=...`), pastikan tabel **4. BEASISWA PESERTA DIDIK** terisi otomatis dengan data beasiswa yang baru diinput.
