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

---
## Penyempurnaan Tampilan Cetak Buku Induk dan Audit Asal Sekolah (Seksi C, D, E)
**Waktu**: 10:20 WIB
**Status**: Dieksekusi

# Implementation Plan: Penyempurnaan Tampilan Cetak Buku Induk dan Audit Asal Sekolah (Seksi C, D, E)

Rencana ini dibuat untuk membenahi beberapa ketidaksesuaian tampilan cetak Buku Induk pada bagian Pendidikan Sebelumnya, serta melakukan audit/verifikasi bahwa seluruh data di Seksi C, D, dan E benar-benar diambil secara dinamis dari database.

---

## 1. Analisis Masalah (Root Cause)

Berdasarkan screenshot lembar cetak Buku Induk halaman 2 yang dilampirkan:
*   **Asal Sekolah vs Nama Sekolah**: 
    Data asal sekolah dari database (kolom `sekolah_asal` di tabel `siswa`) saat ini terisi `"SMPN 53 SURABAYA"`. Namun pada lembar cetak, nilai tersebut dimasukkan ke baris `1) Asal Sekolah` sehingga menghasilkan `"1) Asal Sekolah : SMPN 53 SURABAYA"`, sedangkan baris `2) Nama Sekolah` justru kosong/titik-titik (`"2) Nama Sekolah : ......................"`).
    *   *Solusi*: Kita akan memisahkan tingkat/kategori sekolah (misal: `"SMP / MTs"`) untuk dicetak di `1) Asal Sekolah` dan memindahkan nama sekolah lengkap (`"SMPN 53 SURABAYA"`) untuk dicetak di `2) Nama Sekolah`.
*   **Konfirmasi Integrasi Database**:
    Seluruh data Seksi C, D, dan E (Kesehatan, Tinggi/Berat, Prestasi, dan Beasiswa) **sudah dikueri langsung dari database** di controller. Namun untuk beberapa siswa, datanya tampil kosong (`.....`) karena baris data terkait di tabel `kesehatan_siswa`, `prestasi_siswa`, dan `riwayat_beasiswa` untuk siswa tersebut belum diisi oleh operator.

---

## 2. Rencana Perubahan (Proposed Changes)

### views/print_buku_induk.php

#### [MODIFY] print_buku_induk.php

1.  **Logika Pemisahan Asal & Nama Sekolah (Baris 52–60)**:
    Ubah parser variabel asal sekolah untuk memilah jenis sekolah (tingkat) dan nama sekolah:
    ```php
    // Pendidikan Sebelumnya
    $asalSekolahRaw = $siswa['sekolah_asal'] ?? '......................';
    $asalSekolahTingkat = '......................';
    $asalSekolahNama = '......................';
    
    if (!empty($siswa['sekolah_asal'])) {
        $asalLower = strtolower($siswa['sekolah_asal']);
        if (str_contains($asalLower, 'smp') || str_contains($asalLower, 'tsanawiyah') || str_contains($asalLower, 'mts')) {
            $asalSekolahTingkat = 'SMP / MTs';
        } elseif (str_contains($asalLower, 'sd') || str_contains($asalLower, 'ibtidaiyah') || str_contains($asalLower, 'mi')) {
            $asalSekolahTingkat = 'SD / MI';
        } elseif (str_contains($asalLower, 'sma') || str_contains($asalLower, 'aliyah') || str_contains($asalLower, 'ma') || str_contains($asalLower, 'smk')) {
            $asalSekolahTingkat = 'SMA / MA / SMK';
        }
        $asalSekolahNama = $siswa['sekolah_asal'];
    }
    ```

2.  **Perbaikan Formatting Tanggal Ijazah Sebelumnya (Baris 54)**:
    Format tanggal agar ramah dibaca (`d-m-Y`):
    ```php
    $tglIjazah = '......................';
    if (!empty($siswa['tanggal_ijazah_sebelumnya'])) {
        $tglIjazah = date('d-m-Y', strtotime($siswa['tanggal_ijazah_sebelumnya']));
    }
    ```

3.  **Penyesuaian Tampilan HTML (Baris 338–339)**:
    ```html
    <tr><td></td><td class="sub-label-2">1) Asal Sekolah</td><td class="col-colon">:</td><td class="col-val"><?= htmlspecialchars($asalSekolahTingkat) ?></td></tr>
    <tr><td></td><td class="sub-label-2">2) Nama Sekolah</td><td class="col-colon">:</td><td class="col-val"><?= htmlspecialchars($asalSekolahNama) ?></td></tr>
    ```

---

## 3. Verification Plan

### Manual Verification
1.  Buka detail cetak Buku Induk siswa yang memiliki data asal sekolah (misal: "SMPN 53 SURABAYA").
2.  Pastikan pada cetakan halaman 2:
    *   `1) Asal Sekolah` tercetak `SMP / MTs`.
    *   `2) Nama Sekolah` tercetak `SMPN 53 SURABAYA`.
3.  Pastikan data tanggal ijazah terformat rapi (misal: `15-06-2023`).
4.  Coba isi data kesehatan (tinggi/berat badan), prestasi, dan beasiswa untuk siswa sampel tersebut dari menu manajemen Buku Induk (tab detail), lalu cetak ulang dan pastikan data tersebut tampil otomatis di tabel bagian E.

---
## Perbaikan Kueri Data Kesehatan Siswa Cetak Buku Induk (Super Admin)
**Waktu**: 11:20 WIB
**Status**: Dieksekusi

# Implementation Plan: Perbaikan Kueri Data Kesehatan Siswa Cetak Buku Induk (Super Admin)

Rencana ini dibuat untuk membenahi kegagalan pemuatan data kesehatan siswa ketika masuk/dicetak oleh Super Admin (di mana tenant ID bernilai null).

---

## 1. Analisis Masalah (Root Cause)
*   **Penyebab**: Fungsi `getKesehatanSiswa($id)` di model `Siswa` secara ketat memfilter kueri dengan `tenant_id = ?`. Jika user yang mengakses adalah Super Admin (di mana `tenant_id` bernilai `null`), kueri tersebut gagal mencocokkan data kesehatan di database.
*   **Solusi**:
    1.  Modifikasi `getKesehatanSiswa` di model `Siswa` agar mengabaikan filter `tenant_id` jika bernilai null.
    2.  Modifikasi method `printBukuInduk` di `BukuIndukController` agar mengeset tenant ID pada objek model secara dinamis jika pengguna yang login adalah Super Admin.

---

## 2. Rencana Perubahan (Proposed Changes)

### app/Models/Siswa.php
#### [MODIFY] Siswa.php
```php
    public function getKesehatanSiswa(string $idSiswa): array {
        if ($this->tenantId) {
            $stmt = $this->db->prepare("SELECT * FROM kesehatan_siswa WHERE siswa_id = ? AND tenant_id = ? ORDER BY semester ASC");
            $stmt->execute([$idSiswa, $this->tenantId]);
        } else {
            $stmt = $this->db->prepare("SELECT * FROM kesehatan_siswa WHERE siswa_id = ? ORDER BY semester ASC");
            $stmt->execute([$idSiswa]);
        }
        // ...
```

### app/Controllers/BukuIndukController.php
#### [MODIFY] BukuIndukController.php
```php
        // Set tenant ID dynamically if logged in as Super Admin
        if ($tenantId === null && isset($siswa['tenant_id'])) {
            $siswaModel->setTenantId($siswa['tenant_id']);
        }
```

---

## 3. Verification Plan
*   Lakukan pencetakan Buku Induk untuk siswa sampel dari akun Super Admin.
*   Verifikasi tabel Kesehatan (Tinggi/Berat Badan) pada Halaman 2, pastikan data yang ada di database tampil secara otomatis.

---
## Dinamisasi Tahun Pelajaran Tabel Kesehatan Buku Induk
**Waktu**: 11:30 WIB
**Status**: Dieksekusi

# Implementation Plan: Dinamisasi Tahun Pelajaran Tabel Kesehatan Buku Induk

Rencana ini dibuat untuk mengganti placeholder kosong "Thn Pelajaran ........ / ........" pada tabel Tinggi & Berat Badan serta Kondisi Kesehatan dengan tahun pelajaran dinamis berdasarkan kelas/semester siswa dari database.

---

## 1. Analisis Masalah (Root Cause)
*   **Penyebab**: Header tabel untuk Tahun Pelajaran pada tabel Tinggi/Berat Badan dan Kondisi Kesehatan di Halaman 2 Buku Induk masih bertuliskan placeholder statis `Thn Pelajaran ........ / ........`.
*   **Solusi**:
    1.  Dapatkan tahun ajaran pertama siswa masuk (`id_tahun_ajaran` di tabel `siswa`) dan dapatkan string representasi tahun ajaran pertama (misal `2025/2026`).
    2.  Hitung tahun ajaran semester berikutnya secara berurutan:
        *   Tahun 1 (Semester 1 & 2): Tahun Ajaran Mulai (misal: `2025/2026`).
        *   Tahun 2 (Semester 3 & 4): Tahun Ajaran Mulai + 1 (misal: `2026/2027`).
        *   Tahun 3 (Semester 5 & 6): Tahun Ajaran Mulai + 2 (misal: `2027/2028`).
    3.  Tampilkan tahun ajaran hasil komputasi dinamis tersebut ke dalam header tabel.

---

## 2. Rencana Perubahan (Proposed Changes)

### app/Controllers/BukuIndukController.php
#### [MODIFY] BukuIndukController.php
```php
        // Fetch start academic year name
        $tahunAjaranMulai = '';
        if (!empty($siswa['id_tahun_ajaran'])) {
            try {
                $db = \App\Config\Database::getConnection();
                $stmtTa = $db->prepare("SELECT tahun_ajaran FROM tahun_ajaran WHERE id = ?");
                $stmtTa->execute([$siswa['id_tahun_ajaran']]);
                $tahunAjaranMulai = $stmtTa->fetchColumn() ?: '';
            } catch (\Throwable $e) {
                // Ignore
            }
        }
        $siswa['tahun_ajaran_mulai'] = $tahunAjaranMulai;
```

### views/print_buku_induk.php
#### [MODIFY] print_buku_induk.php
```php
// Resolve academic years (TA1, TA2, TA3)
$ta1 = '........ / ........';
$ta2 = '........ / ........';
$ta3 = '........ / ........';

if (!empty($siswa['tahun_ajaran_mulai']) && preg_match('/^(\d{4})\/(\d{4})$/', $siswa['tahun_ajaran_mulai'], $matches)) {
    $startYear = (int)$matches[1];
    $endYear = (int)$matches[2];
    
    $ta1 = $startYear . ' / ' . $endYear;
    $ta2 = ($startYear + 1) . ' / ' . ($endYear + 1);
    $ta3 = ($startYear + 2) . ' / ' . ($endYear + 2);
}
```
*Ganti seluruh label placeholder `Thn Pelajaran ........ / ........` pada head Tinggi/Berat Badan dan Kondisi Kesehatan menggunakan `$ta1`, `$ta2`, dan `$ta3`.*

---

## 3. Verification Plan
*   Lakukan pencetakan Buku Induk untuk siswa sampel.
*   Periksa header kolom tahun pelajaran pada halaman 2, pastikan tercetak secara dinamis sesuai tahun ajaran pertama siswa masuk (contoh untuk Abdullah Azzam Aufar: `2025/2026`, `2026/2027`, `2027/2028`).

---
## Kemudahan Akses Beasiswa (Buku Induk & Bimbingan Konseling)
**Waktu**: 13:50 WIB
**Status**: Dieksekusi

# Implementation Plan: Kemudahan Akses Beasiswa (Buku Induk & Bimbingan Konseling)

Rencana ini dibuat untuk mempermudah Super Admin dan Admin Sekolah dalam menginput dan mengakses menu beasiswa secara terpadu melalui tombol pintasan aksi cepat pada Buku Induk dan tab baru di Bimbingan Konseling -> Layanan & Kedisiplinan.

---

## 1. Rencana Perubahan (Proposed Changes)

### views/buku_induk.php
#### [MODIFY] buku_induk.php
*   Menambahkan tombol `🎓 Beasiswa` di sebelah tombol `👁️ Detail` pada tabel daftar siswa.
*   Mengubah fungsi `viewDetail(siswaId, defaultTab)` agar dapat langsung mengarahkan pengguna ke tab Beasiswa.

### views/bk/hub.php
#### [MODIFY] hub.php
*   Menambahkan `'beasiswa'` ke dalam daftar tab yang diizinkan untuk BK Layanan.

### views/master_bk.php
#### [MODIFY] master_bk.php
*   Menambahkan tombol tab navigasi `🎓 Beasiswa Siswa` pada deretan header tab.
*   Menyediakan panel pencarian siswa secara dinamis dengan pencarian auto-complete/dropdown yang terhubung ke database.
*   Menampilkan tabel riwayat beasiswa serta formulir input beasiswa baru terpadu langsung di konsol BK, terintegrasi dengan API beasiswa.
*   Menambahkan logika Vue 3 (Composition API) untuk mengelola data beasiswa.

---

## 2. Verification Plan
*   Buka menu Buku Induk, pastikan tombol pintasan Beasiswa dapat langsung membuka tab Beasiswa di modal siswa.
*   Buka menu Bimbingan Konseling -> Layanan & Kedisiplinan, pastikan tab Beasiswa Siswa muncul dan dapat memproses data beasiswa.

---
## Perbaikan Namespace PDO pada Hapus Prestasi BK
**Waktu**: 13:58 WIB
**Status**: Dieksekusi

# Implementation Plan: Perbaikan Namespace PDO pada Hapus Prestasi BK

Memperbaiki pemanggilan konstanta `PDO::FETCH_COLUMN` menjadi `\PDO::FETCH_COLUMN` pada file controller `BKController` untuk menyelesaikan error 500 saat menghapus data prestasi.

---

## 1. Rencana Perubahan (Proposed Changes)
### app/Controllers/BKController.php
#### [MODIFY] BKController.php
*   Mengubah `PDO::FETCH_COLUMN` menjadi `\PDO::FETCH_COLUMN` pada line ~1668.

---

## 2. Verification Plan
*   Lakukan hapus prestasi siswa di menu Bimbingan Konseling -> Prestasi Siswa.
*   Pastikan status hapus berhasil tanpa memicu Error 500 dari API `/SINTA-SaaS/api/v1/bk/prestasi/delete`.

---
## Perbaikan Endpoint Pencarian Siswa pada Tab Beasiswa BK
**Waktu**: 14:00 WIB
**Status**: Dieksekusi

# Implementation Plan: Perbaikan Endpoint Pencarian Siswa pada Tab Beasiswa BK

Memperbaiki endpoint API pencarian siswa pada tab Beasiswa di modul Bimbingan Konseling yang awalnya memanggil `/api/v1/bk/jurnal/siswa` menjadi `/api/v1/bk/siswa` (endpoint pencarian siswa yang valid).

---

## 1. Rencana Perubahan (Proposed Changes)
### views/master_bk.php
#### [MODIFY] master_bk.php
*   Mengubah pemanggilan API pencarian siswa dari `/api/v1/bk/jurnal/siswa` menjadi `/api/v1/bk/siswa` di fungsi `searchSiswaBeasiswaDebounce`.

---

## 2. Verification Plan
*   Ketik nama siswa pada tab Beasiswa Siswa di modul Bimbingan Konseling.
*   Pastikan pencarian berhasil mengembalikan data opsi siswa tanpa memicu Error 404 dari API `/SINTA-SaaS/api/v1/bk/siswa`.

---
## Daftar Semua Penerima Beasiswa & Ekspor Excel di BK
**Waktu**: 14:06 WIB
**Status**: Dieksekusi

# Implementation Plan: Daftar Semua Penerima Beasiswa & Ekspor Excel di BK

Menampilkan seluruh data penerima beasiswa sekolah secara real-time pada panel kanan tab Beasiswa Bimbingan Konseling, lengkap dengan filter Tahun Ajaran dan menu Download Excel (.xlsx).

---

## 1. Rencana Perubahan (Proposed Changes)
### index.php
#### [MODIFY] index.php
*   Menambahkan endpoint API `/api/v1/bk/beasiswa/list` dan `/api/v1/bk/beasiswa/export`.

### app/Controllers/BKController.php
#### [MODIFY] BKController.php
*   Membuat method `apiBeasiswaList()` untuk melayani data daftar penerima beasiswa secara real-time berdasarkan filter sekolah & tahun ajaran.
*   Membuat method `apiExportBeasiswa()` untuk mengunduh rekap beasiswa dalam format Excel (.xlsx) menggunakan parser `SimpleXLSXGen`.

### views/master_bk.php
#### [MODIFY] master_bk.php
*   Mengubah HTML Panel Kanan di tab Beasiswa agar memuat data list beasiswa semua siswa.
*   Menambahkan elemen select filter Tahun Ajaran dan tombol Ekspor Excel.
*   Menambahkan logika Vue 3 `allBeasiswaList`, `filterBeasiswaTahunAjaran`, `loadAllBeasiswa()`, dan `exportBeasiswaExcel()`.

---

## 2. Verification Plan
*   Buka tab Beasiswa di Bimbingan Konseling, pastikan seluruh data beasiswa siswa muncul otomatis di tabel sebelah kanan.
*   Pilih salah satu Tahun Ajaran di dropdown filter, pastikan data tersaring dengan tepat.
*   Klik tombol "Excel", pastikan file `.xlsx` terunduh dengan struktur kolom yang rapi dan data yang valid.

---
## Perbaikan SQL Parameter Duplikat & Reference Error Toast di BK
**Waktu**: 14:09 WIB
**Status**: Dieksekusi

# Implementation Plan: Perbaikan SQL Parameter Duplikat & Reference Error Toast di BK

Membenahi error SQLSTATE[HY093] (parameter number invalid) dengan menyusun klausa WHERE dinamis agar parameter `:tahun_ajaran_id` tidak di-bind ganda pada real prepared statements. Serta mendeklarasikan mixin `toast` pada master_bk Vue setup untuk mengatasi ReferenceError.

---

## 1. Rencana Perubahan (Proposed Changes)
### app/Controllers/BKController.php
#### [MODIFY] BKController.php
*   Menyusun SQL secara dinamis pada `apiBeasiswaList()` dan `apiExportBeasiswa()`.

### views/master_bk.php
#### [MODIFY] master_bk.php
*   Mendeklarasikan `const toast = Swal.mixin(...)` di dalam Vue setup.

---

## 2. Verification Plan
*   Buka tab Beasiswa BK, pastikan data termuat dengan sukses (HTTP status 200) tanpa error 500.
*   Simulasi error respon untuk memastikan toast notifikasi terpicu dengan benar tanpa ReferenceError.

---
## Perbaikan Error 500 Hapus Beasiswa Super Admin & Nullable Cache Invalidator
**Waktu**: 14:10 WIB
**Status**: Dieksekusi

# Implementation Plan: Perbaikan Error 500 Hapus Beasiswa Super Admin & Nullable Cache Invalidator

Memperbaiki error 500 saat menghapus beasiswa sebagai Super Admin akibat `tenant_id` bernilai null yang memicu TypeError pada `CacheInvalidator::clearStudentCache` (mengharapkan string). Kami mengubah parameter `tenantId` pada helper `CacheInvalidator` menjadi nullable dengan resolusi otomatis dari database, serta memodifikasi `deleteBeasiswaApi` agar mengambil `tenant_id` asli dari record sebelum dihapus.

---

## 1. Rencana Perubahan (Proposed Changes)
### app/Helpers/CacheInvalidator.php
#### [MODIFY] CacheInvalidator.php
*   Mengubah tipe parameter `$tenantId` menjadi nullable (`?string $tenantId = null`).
*   Menambahkan query otomatis untuk mencari `tenant_id` siswa jika parameter kosong.

### app/Controllers/BukuIndukController.php
#### [MODIFY] BukuIndukController.php
*   Mengubah `deleteBeasiswaApi` agar mengambil `tenant_id` dari database sebelum record dihapus, kemudian meneruskannya ke fungsi `clearStudentCache`.

### views/master_bk.php
#### [MODIFY] master_bk.php
*   Mengembalikan `toast` pada `setup()` return block untuk membenahi sisa ReferenceError pada runtime.

---

## 2. Verification Plan
*   Lakukan penghapusan data beasiswa sebagai Super Admin.
*   Pastikan operasi berhasil (HTTP status 200) dan cache Buku Induk siswa terhapus secara tepat.

---
## Perbaikan Error 500 Simpan Beasiswa Super Admin
**Waktu**: 14:14 WIB
**Status**: Dieksekusi

# Implementation Plan: Perbaikan Error 500 Simpan Beasiswa Super Admin

Memperbaiki error 500 saat menyimpan riwayat beasiswa baru sebagai Super Admin akibat `tenant_id` session bernilai null yang melanggar batasan `NOT NULL` pada kolom `tenant_id` tabel `riwayat_beasiswa`. Kami menambahkan logika otomatis untuk mengambil `tenant_id` dari data siswa jika `tenantId` session kosong pada `storeBeasiswaApi`.

---

## 1. Rencana Perubahan (Proposed Changes)
### app/Controllers/BukuIndukController.php
#### [MODIFY] BukuIndukController.php
*   Menambahkan query penyelesaian `tenant_id` otomatis dari tabel `siswa` pada fungsi `storeBeasiswaApi()` jika `$tenantId` dari session bernilai kosong (Super Admin).

---

## 2. Verification Plan
*   Lakukan pengisian form input beasiswa baru sebagai Super Admin di modul BK maupun Buku Induk.
*   Pastikan data beasiswa tersimpan dengan sukses (HTTP status 200 OK) dan `tenant_id` terisi sesuai sekolah asal siswa.

---
## Perbaikan Data Beasiswa Kosong di Modal Buku Induk Super Admin
**Waktu**: 14:17 WIB
**Status**: Dieksekusi

# Implementation Plan: Perbaikan Data Beasiswa Kosong di Modal Buku Induk Super Admin

Memperbaiki kendala riwayat beasiswa yang tampil kosong pada modal "Kartu Buku Induk Siswa Lengkap" saat diakses oleh akun Super Admin akibat session `tenant_id` bernilai null.

---

## 1. Rencana Perubahan (Proposed Changes)
### app/Controllers/BukuIndukController.php
#### [MODIFY] BukuIndukController.php
*   Menambahkan resolusi `tenant_id` otomatis dari tabel `siswa` di `getBeasiswaApi()` jika `SessionManager::getTenantId()` mengembalikan nilai null.

---

## 2. Verification Plan
*   Buka modal "Kartu Buku Induk Siswa Lengkap" untuk siswa (misal: AFIFAH DESY AYU WULANDARI) sebagai Super Admin.
*   Buka tab "Beasiswa" dan pastikan riwayat beasiswa (misal: PIP) langsung muncul dengan sempurna.

---
## Penyesuaian Tata Letak Cetak Buku Induk (Posisi Barcode & Hapus Tanda Tangan Kepsek)
**Waktu**: 14:26 WIB
**Status**: Dieksekusi

# Implementation Plan: Penyesuaian Tata Letak Cetak Buku Induk (Posisi Barcode & Hapus Tanda Tangan Kepsek)

Menghapus blok tanda tangan Kepala Sekolah pada bagian bawah lembar cetak Buku Induk (`print_buku_induk.php`) dan memindahkan posisi QR Code/Barcode verifikasi ke pojok kanan atas lembar cetak.

---

## 1. Rencana Perubahan (Proposed Changes)
### views/print_buku_induk.php
#### [MODIFY] print_buku_induk.php
*   Menambahkan kontainer QR Code verifikasi ber-posisi absolute di pojok kanan atas (`right: 0; top: 0;`).
*   Menghapus elemen `<div class="signature-box">` dan elemen QR Code lama di bagian bawah halaman cetak.

---

## 2. Verification Plan
*   Buka URL cetak Buku Induk (`/cetak-buku-induk?id=a20e48ed-21d1-4661-a0bc-4bdf381978b6&tanggal_cetak=2026-07-20&show_qrcode=1&token=...`).
*   Pastikan blok tanda tangan Kepala Sekolah di bagian bawah sudah tidak muncul.
*   Pastikan QR Code / Barcode verifikasi berada di posisi pojok kanan atas lembar cetak dengan rapi.

---
## Presisi Posisi QR Code Verifikasi di Samping Tabel Informasi Header Cetak Buku Induk
**Waktu**: 14:31 WIB
**Status**: Dieksekusi

# Implementation Plan: Presisi Posisi QR Code Verifikasi di Samping Tabel Informasi Header Cetak Buku Induk

Memposisikan kontainer QR Code / Barcode secara presisi di samping kanan tabel informasi header (sejajar vertikal dengan kolom KECAMATAN / KAB/KOTA / PROVINSI) sesuai acuan gambar sampel pengguna.

---

## 1. Rencana Perubahan (Proposed Changes)
### views/print_buku_induk.php
#### [MODIFY] print_buku_induk.php
*   Mengatur `top: 40px; right: 0;` pada kontainer QR Code verifikasi.
*   Mengatur lebar tabel informasi header menjadi `82%` agar memberikan ruang yang seimbang bagi QR Code di sebelah kanan.

---

## 2. Verification Plan
*   Buka URL cetak Buku Induk, pastikan posisi QR Code terletak pas di sebelah kanan informasi Kecamatan, Kab/Kota, dan Provinsi persis seperti pada acuan gambar sampel.

---
## Pembaruan Margin Cetak Halaman Buku Induk (Top 1cm, Right 0.8cm, Bottom 1cm, Left 2.5cm)
**Waktu**: 14:36 WIB
**Status**: Dieksekusi

# Implementation Plan: Pembaruan Margin Cetak Halaman Buku Induk (Top 1cm, Right 0.8cm, Bottom 1cm, Left 2.5cm)

Mengubah aturan CSS `@page` margin pada lembar cetak Buku Induk (`print_buku_induk.php`) sesuai permintaan terbaru pengguna: Margin Atas 1cm, Kanan 0.8cm, Bawah 1cm, dan Kiri 2.5cm.

---

## 1. Rencana Perubahan (Proposed Changes)
### views/print_buku_induk.php
#### [MODIFY] print_buku_induk.php
*   Mengubah aturan CSS `@page` margin menjadi `margin: 1cm 0.8cm 1cm 2.5cm;`.

---

## 2. Verification Plan
*   Buka URL cetak Buku Induk.
*   Periksa aturan CSS `@page` di devtools browser / dialog cetak, pastikan margin yang diterapkan adalah Top: 1cm, Right: 0.8cm, Bottom: 1cm, dan Left: 2.5cm.

---
## Otomatisasi Pembersihan (Garbage Collection) File Rate Limit Kedaluwarsa
**Waktu**: 14:50 WIB
**Status**: Dieksekusi

# Implementation Plan: Otomatisasi Pembersihan (Garbage Collection) File Rate Limit Kedaluwarsa

Menambahkan fitur pembersihan otomatis (*Garbage Collection*) untuk menghapus file `.json` di folder `storage/app/rate_limit/` yang usianya sudah lebih dari 1 jam (3600 detik).

---

## 1. Rencana Perubahan (Proposed Changes)
### app/Controllers/BukuIndukController.php
#### [MODIFY] BukuIndukController.php
*   Menambahkan fungsi private `cleanStaleRateLimitFiles(string $rateLimitDir)` dengan pembersihan probabilistik (10% peluang per request) untuk menghapus file rate limit berusia > 3600 detik.
*   Memanggil `cleanStaleRateLimitFiles()` pada method `verifyTranskrip()` dan `verifyTranskripApi()`.

---

## 2. Verification Plan
*   Lakukan request ke endpoint verifikasi dokumen.
*   Pastikan file-file `.json` lama di `storage/app/rate_limit/` yang berusia lebih dari 1 jam terhapus secara otomatis tanpa mengganggu proses request.

---
## Kelompok Mata Pelajaran, Cetak Rapor Berdasarkan Kurikulum, dan Data Dummy 12 Semester
**Waktu**: 14:57 WIB
**Status**: Dieksekusi

# Rencana Implementasi: Kelompok Mata Pelajaran, Cetak Rapor Berdasarkan Kurikulum, dan Data Dummy 12 Semester

Rencana ini bertujuan untuk memetakan kelompok mata pelajaran secara terstruktur (Umum, Pilihan, Peminatan, Lintas Minat, Mulok), menyesuaikan kolom dan tata letak cetak rapor berdasarkan pengaturan kurikulum (Kurikulum Merdeka & Kurikulum 2013/K-13), serta menyediakan data dummy lengkap sebanyak 12 semester untuk kebutuhan pengujian dan verifikasi transkrip/rapor.

---

## 1. Struktur Kelompok Mata Pelajaran

Setiap mata pelajaran akan dikategorikan ke dalam 5 kelompok standar nasional dan daerah:

1. **A. Kelompok Mata Pelajaran Umum** (Umum / Wajib A)
   - Pendidikan Agama dan Budi Pekerti
   - Pendidikan Pancasila / PKn
   - Bahasa Indonesia
   - Matematika
   - Bahasa Inggris
   - Pendidikan Jasmani, Olahraga, dan Kesehatan (PJOK)
   - Sejarah / Sejarah Indonesia
   - Seni dan Budaya (Seni Musik, Seni Rupa, Seni Teater, Seni Tari)

2. **B. Kelompok Mata Pelajaran Pilihan** (Pilihan / Umum B)
   - Biologi, Kimia, Fisika
   - Informatika, Matematika Tingkat Lanjut
   - Sosiologi, Ekonomi, Geografi, Antropologi
   - Bahasa Indonesia Tingkat Lanjut, Bahasa Inggris Tingkat Lanjut
   - Bahasa Asing (Arab, Jepang, Mandarin, Jerman, Prancis, Korea)
   - Prakarya dan Kewirausahaan (PKWU)

3. **C. Mata Pelajaran Peminatan** (C1. Peminatan MIPA / IPS / Bahasa)
   - Peminatan MIPA: Matematika Peminatan, Biologi, Fisika, Kimia
   - Peminatan IPS: Geografi, Sejarah Peminatan, Sosiologi, Ekonomi
   - Peminatan Bahasa: Bahasa & Sastra Indonesia, Bahasa & Sastra Inggris, Bahasa Asing Peminatan

4. **D. Mata Pelajaran Lintas Minat** (C2. Lintas Minat)
   - Mata pelajaran pilihan di luar peminatan utama (contoh: Bahasa & Sastra Inggris untuk siswa MIPA, Ekonomi untuk siswa MIPA)

5. **E. Mata Pelajaran Mulok / Khas Sekolah** (D. Mulok / Khas Sekolah)
   - Bahasa Daerah (Jawa / Sunda / Bali)
   - Keagamaan Khusus / Tahfidz / Khas Pesantren / Khas Sekolah

---

## 2. Penyesuaian Kolom Cetak Rapor Berdasarkan Settingan Kurikulum

### A. Template Kurikulum Merdeka (`print_rapot_merdeka.php` & `print_rapot_bulk_merdeka.php`)
- **Struktur Kolom**:
  | NO. | MATA PELAJARAN | NILAI AKHIR | CAPAIAN PEMBELAJARAN | KKTP |
- **Pengelompokan dalam Tabel**:
  - `A. Kelompok Mata Pelajaran Umum`
  - `B. Kelompok Mata Pelajaran Pilihan`
  - `E. Mata Pelajaran Mulok / Khas Sekolah`
- **Baris Rekapitulasi**:
  - Total **Jumlah Nilai**
  - **Rata-rata Nilai** Intrakurikuler

### B. Template Kurikulum 2013 / K-13 (`print_rapot_k13.php` & `print_rapot_bulk_k13.php`)
- **Seksi Sikap (KI-1 & KI-2)**:
  - Table Sikap Spiritual (KI-1) & Sikap Sosial (KI-2) memuat `Predikat` (SB/B/C/K) dan `Deskripsi`.
- **Struktur Kolom Pengetahuan & Keterampilan**:
  | NO. | MATA PELAJARAN | KKM | PENGETAHUAN (KI-3) [Nilai | Predikat | Deskripsi] | KETERAMPILAN (KI-4) [Nilai | Predikat | Deskripsi] |
- **Pengelompokan dalam Tabel**:
  - `NO. UMUM A (WAJIB)`
  - `NO. UMUM B (WAJIB)`
  - `NO. C1. PEMINATAN`
  - `NO. C2. LINTAS MINAT`
  - `NO. D. MULOK / KHAS SEKOLAH`

---

## 3. Rencana Pembuatan Data Dummy 12 Semester

Akan dibuatkan file migrasi/seeder database baru `2026_07_20_01_seed_dummy_12_semester_grades.php` yang akan mengisi data historis lengkap untuk siswa sampel selama **12 Semester** (Semester 1 s.d. 12 across 6 Tahun Ajaran):

1. **Tahun Ajaran & Semester**:
   - Semester 1 & 2 (Tahun Pelajaran 2020/2021) - Kelas 7 / Kelas 10
   - Semester 3 & 4 (Tahun Pelajaran 2021/2022) - Kelas 8 / Kelas 11
   - Semester 5 & 6 (Tahun Pelajaran 2022/2023) - Kelas 9 / Kelas 12
   - Semester 7 & 8 (Tahun Pelajaran 2023/2024)
   - Semester 9 & 10 (Tahun Pelajaran 2024/2025)
   - Semester 11 & 12 (Tahun Pelajaran 2025/2026)

2. **Cakupan Data yang Di-seed**:
   - Pemetaan Mata Pelajaran (`pemetaan_mapel`) untuk ke-5 kelompok mapel.
   - Nilai Rapor Akademik (`detail_nilai_rapor`) mencakup nilai pengetahuan, keterampilan, nilai akhir, KKTP/KKM, dan deskripsi capaian pembelajaran.
   - Nilai Sikap K-13 (`nilai_sikap_k13`) untuk predikat dan deskripsi spiritual/sosial.

---

## Proposed Changes

### Database / Migrations

#### [NEW] [2026_07_20_01_seed_dummy_12_semester_grades.php](file:///C:/xampp/htdocs/SINTA-SaaS/database/migrations/2026_07_20_01_seed_dummy_12_semester_grades.php)
- Membuat script migrasi berformat `return ['up' => function(PDO $pdo){...}]` untuk meng-insert data dummy 12 semester lengkap dengan kelompok mapel A s.d. E.

---

### Backend & Controller

#### [MODIFY] [BukuIndukController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/BukuIndukController.php)
- Memastikan query pencetakan rapor mengelompokkan nilai berdasarkan `kelompok_id` / `kelompok` secara konsisten untuk K-13 dan Kurikulum Merdeka.

---

### Views / Print Templates

#### [MODIFY] [print_rapot_merdeka.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/print_rapot_merdeka.php)
- Menyesuaikan header tabel, kolom (`NO`, `MATA PELAJARAN`, `NILAI AKHIR`, `CAPAIAN PEMBELAJARAN`, `KKTP`), pengelompokan A, B, E, serta baris Jumlah & Rata-rata Nilai.

#### [MODIFY] [print_rapot_k13.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/print_rapot_k13.php)
- Menyesuaikan header tabel, kolom Pengetahuan (KI-3) & Keterampilan (KI-4), serta sub-header pengelompokan (Umum A, Umum B, C1 Peminatan, C2 Lintas Minat, D Mulok).

---

## Verification Plan

### Automated Tests
- Menjalankan migrasi seeder via terminal:
  `php migrate.php`
- Memverifikasi keberadaan data dummy 12 semester di tabel `detail_nilai_rapor`, `pemetaan_mapel`, dan `nilai_sikap_k13`.

### Manual Verification
1. Buka halaman Cetak Rapor Semester untuk Kurikulum Merdeka & K-13.
2. Pastikan tabel nilai terbagi rapi berdasarkan kelompok A, B, C1, C2, D/E.
3. Pastikan kolom-kolom nilai (Pengetahuan, Keterampilan, Nilai Akhir, Capaian Pembelajaran, KKM/KKTP) sesuai dengan gambar sampel.
4. Buka transkrip / buku induk 12 semester dan pastikan seluruh 12 semester menampilkan data nilai yang lengkap.

---
## Proteksi Keamanan Produksi (Production Safeguard) Pada Seeder Data Dummy 12 Semester
**Waktu**: 15:41 WIB
**Status**: Dieksekusi

# Implementation Plan: Proteksi Keamanan Produksi (Production Safeguard) Pada Seeder Data Dummy 12 Semester

Menambahkan mekanisme pertahanan dan pemeriksaan lingkungan kerja (*environment check*) agar script seeder data dummy `2026_07_20_01_seed_dummy_12_semester_grades.php` secara otomatis menolak dieksekusi di server produksi.

---

## 1. Rencana Perubahan (Proposed Changes)
### database/migrations/2026_07_20_01_seed_dummy_12_semester_grades.php
#### [MODIFY] 2026_07_20_01_seed_dummy_12_semester_grades.php
*   Menambahkan pengecekan `APP_ENV === 'production'`, `APP_DEBUG === 'false'`, serta pengenalan nama host domain live di awal fungsi closure `up`. Jika terdeteksi lingkungan produksi, proses dibatalkan dengan pesan warning `[SAFETY BLOCKED]`.

---

## 2. Verification Plan
*   Jalankan script pengujian `scratch/test_production_safeguard.php` yang mensimulasikan `APP_ENV=production`.
*   Pastikan migrasi mengembalikan status `[SAFETY BLOCKED]` dan tidak ada data dummy yang di-insert ke database.

---
## Perbaikan Query Rapor Semester (Pencocokan Semester Ganjil/Genap & Hapus Kolom Non-Eksis m.kelompok)
**Waktu**: 15:46 WIB
**Status**: Dieksekusi

# Implementation Plan: Perbaikan Query Rapor Semester (Pencocokan Semester Ganjil/Genap & Hapus Kolom Non-Eksis m.kelompok)

Memperbaiki query pengambilan nilai rapor semester pada `BukuIndukController.php` agar mendukung pencocokan fleksibel nama semester (`Ganjil`/`Genap`) dengan angka semester (`1` s.d. `12`), serta menghapus kolom `m.kelompok` yang memicu SQL error.

---

## 1. Rencana Perubahan (Proposed Changes)
### app/Controllers/BukuIndukController.php
#### [MODIFY] BukuIndukController.php
*   Mengganti `COALESCE(pm.kelompok_id, m.kelompok, ...)` menjadi `COALESCE(pm.kelompok_id, 'A. Kelompok Mata Pelajaran Umum')` karena kolom `m.kelompok` tidak ada di tabel `mata_pelajaran`.
*   Menambahkan pemetaan otomatis variabel `$semester` (`Ganjil` -> `1,3,5,7,9,11`, `Genap` -> `2,4,6,8,10,12`) dalam klausa `WHERE d.semester IN (...)`.

---

## 2. Verification Plan
*   Buka URL cetak rapor semester untuk Afifah (`semester=Ganjil&ta=2020/2021`).
*   Pastikan seluruh 25 mata pelajaran pada TA 2020/2021 Semester 1 langsung muncul dengan lengkap pada kelompok A s.d. E.

---
## Perbaikan Filter Tahun Ajaran Halaman Buku Induk (Pencegahan Siswa Angkatan Masa Depan Tampil)
**Waktu**: 15:50 WIB
**Status**: Dieksekusi

# Implementation Plan: Perbaikan Filter Tahun Ajaran Halaman Buku Induk

Memperbaiki logika filter `fetchCetakMatrixApi` pada `BukuIndukController.php` dan seeder data dummy agar siswa angkatan masa depan (contoh: `2026/2027`) tidak muncul saat filter Tahun Ajaran terdahulu (contoh: `2022/2023`) dipilih.

---

## 1. Rencana Perubahan (Proposed Changes)
### app/Controllers/BukuIndukController.php
#### [MODIFY] BukuIndukController.php
*   Menambahkan batasan `(ta.tahun_ajaran IS NULL OR ta.tahun_ajaran <= :filter_ta_max)` dalam query pencarian siswa berdasarkan filter Tahun Ajaran.

### database/migrations/2026_07_20_01_seed_dummy_12_semester_grades.php
#### [MODIFY] 2026_07_20_01_seed_dummy_12_semester_grades.php
*   Mengatur `id_tahun_ajaran` (Tahun Masuk) siswa seeder ke `2020/2021` agar sinkron dengan periode 12 semester (2020/2021 s.d 2025/2026).

---

## 2. Verification Plan
*   Buka halaman Buku Induk (`http://localhost/SINTA-SaaS/buku-induk`) dan filter Tahun Ajaran `2022/2023`.
*   Pastikan siswa angkatan `2026/2027` (seperti Anisah Farah Karunia, Arjuna Ahza Rasyiid, Abdullah Azzam Aufar) tidak lagi muncul di daftar `2022/2023`.

---
## Perbaikan Tampilan Transkrip Nilai Kelulusan (Hapus Kolom m.kelompok & Dukungan Parsing Semester Angka 1-6)
**Waktu**: 15:58 WIB
**Status**: Dieksekusi

# Implementation Plan: Perbaikan Tampilan Transkrip Nilai Kelulusan

Memperbaiki query dan logika parsing semester pada Transkrip Nilai Kelulusan agar data 25 mata pelajaran beserta nilai semester 1 s.d. 6 tampil utuh.

---

## 1. Rencana Perubahan (Proposed Changes)
### app/Controllers/BukuIndukController.php
#### [MODIFY] BukuIndukController.php
*   Mengganti `m.kelompok` dengan `COALESCE(pm.kelompok_id, 'A. Kelompok Mata Pelajaran Umum') AS kelompok` pada query `printTranskripNilai()`.

### views/print_transkrip_merdeka.php & views/print_transkrip_standar.php
#### [MODIFY] print_transkrip_merdeka.php & print_transkrip_standar.php
*   Memperbarui logika pemetaan semester untuk mengenali angka semester `1` s.d. `6` secara presisi ke dalam kolom matriks transkrip nilai.

---

## 2. Verification Plan
*   Muat ulang URL cetak Transkrip Nilai untuk Afifah (`cetak-transkrip-nilai?id=a20e48ed-21d1-4661-a0bc-4bdf381978b6`).
*   Pastikan tabel transkrip terisi 27 baris mata pelajaran dengan nilai lengkap untuk Semester 1 s.d. 6.

---
## Perbaikan Vue Runtime Error (`Cannot read properties of undefined (reading '10')`)
**Waktu**: 16:04 WIB
**Status**: Dieksekusi

# Implementation Plan: Perbaikan Vue Runtime Error (`Cannot read properties of undefined (reading '10')`)

Menambahkan penanganan defensif (*null-guard checks*) pada metode Vue `getAverageGrade()`, `saveNilaiRapor()`, dan `formattedGrades` di `views/buku_induk.php` agar terhindar dari runtime error saat mengakses properti objek yang belum terdefinisi.

---

## 1. Rencana Perubahan (Proposed Changes)
### views/buku_induk.php
#### [MODIFY] views/buku_induk.php
*   Menambahkan pengecekan keberadaan `this.nilaiRapor.grades[studentId]` sebelum mengakses `[subjectId]`.
*   Memperbarui pemetaan semester di `formattedGrades` agar mendukung angka semester ganjil/genap.
*   Menutup tag `</td>` yang kurang pada baris matriks semester.

---

## 2. Verification Plan
*   Buka halaman Buku Induk (`http://localhost/SINTA-SaaS/buku-induk`) dan buka Console DevTools browser.
*   Pastikan tidak ada lagi pesan `[VUE RUNTIME ERROR] TypeError: Cannot read properties of undefined (reading '10')`.

---
## Pembersihan Berkas Seeder Data Dummy dari Direktori Migrasi
**Waktu**: 16:07 WIB
**Status**: Dieksekusi

# Implementation Plan: Pembersihan Berkas Seeder Data Dummy dari Direktori Migrasi

Menghapus berkas seeder dummy `2026_07_20_01_seed_dummy_12_semester_grades.php` dari direktori `database/migrations/` agar tidak terunggah ke server produksi.

---

## 1. Rencana Perubahan (Proposed Changes)
### database/migrations/2026_07_20_01_seed_dummy_12_semester_grades.php
#### [DELETE] 2026_07_20_01_seed_dummy_12_semester_grades.php
*   Menghapus berkas seeder dummy pengujian 12 semester.

---

## 2. Verification Plan
*   Jalankan `Test-Path` di PowerShell untuk memastikan file `database/migrations/2026_07_20_01_seed_dummy_12_semester_grades.php` bernilai `False`.

---
## Pengurutan Tahun Ajaran & Angkatan Berdasarkan Tahun Terbaru pada Master Data (`/master-data`)
**Waktu**: 16:13 WIB
**Status**: Dieksekusi

# Implementation Plan: Pengurutan Tahun Ajaran & Angkatan Berdasarkan Tahun Terbaru

Mengubah pengurutan data tabel Tahun Ajaran dan Tahun Angkatan pada halaman Master Data Kelembagaan agar menampilkan tahun ajaran terbaru di urutan paling atas.

---

## 1. Rencana Perubahan (Proposed Changes)
### app/Models/Kelembagaan.php
#### [MODIFY] Kelembagaan.php
*   Memperbarui fungsi `getPaginated()` agar menggunakan `ORDER BY k.tahun_ajaran DESC` untuk modul `tahun_ajaran` dan `ORDER BY k.tahun_angkatan DESC` untuk modul `angkatan`.
*   Memperbarui fungsi `getOptions()` agar menggunakan pengurutan `DESC` pada modul `tahun_ajaran` dan `angkatan`.

---

## 2. Verification Plan
*   Buka halaman Master Data Kelembagaan (`http://localhost/SINTA-SaaS/master-data`).
*   Pilih tab **Tahun Ajaran**. Pastikan daftar terurut dari tahun terbaru (contoh: 2026/2027, 2025/2026, 2024/2025...).

---
## Pembersihan & Reset Data Inputan Nilai Rapor & Setingan Kurikulum Localhost
**Waktu**: 16:20 WIB
**Status**: Dieksekusi

# Implementation Plan: Pembersihan & Reset Data Inputan Nilai Rapor & Setingan Kurikulum

Membuat dan mengeksekusi skrip pembersihan data `scratch/reset_buku_induk_nilai_kurikulum.php` untuk mengosongkan tabel inputan nilai rapor siswa dan pemetaan setingan kurikulum di lingkungan localhost.

---

## 1. Rencana Perubahan (Proposed Changes)
### scratch/reset_buku_induk_nilai_kurikulum.php
#### [NEW] reset_buku_induk_nilai_kurikulum.php
*   Menjalankan query `TRUNCATE TABLE` untuk tabel `detail_nilai_rapor`, `nilai_sikap_k13`, `absensi_semester`, `kesehatan_siswa`, `log_nilai_rapor`, `nilai_ujian_sekolah`, `kelas_kurikulum`, dan `pemetaan_mapel`.

---

## 2. Verification Plan
*   Jalankan `php scratch/check_all_tables.php` untuk memastikan seluruh tabel terkait bernilai 0 baris.

---
## Penambahan Informasi Kurikulum Aktif pada Header Kelompok Mata Pelajaran
**Waktu**: 16:23 WIB
**Status**: Dieksekusi

# Implementation Plan: Penambahan Informasi Kurikulum Aktif pada Header Kelompok Mata Pelajaran

Menampilkan nama Kurikulum Kelas yang sedang aktif di sebelah nama Kelas Fisik pada header section **Kelompok Mata Pelajaran**.

---

## 1. Rencana Perubahan (Proposed Changes)
### views/buku_induk.php
#### [MODIFY] views/buku_induk.php
*   Menambahkan fungsi helper `getKurikulumName(kurikulumId)` di objek `methods` Vue.js.
*   Menambahkan elemen *badge pill* `<span class="badge bg-primary-subtle ..."><i class="bi bi-journal-bookmark-fill me-1"></i>{{ getKurikulumName(kurikulum.kurikulumId) }}</span>` di dalam header `Kelompok Mata Pelajaran`.

---

## 2. Verification Plan
*   Buka tab **Setingan Kurikulum** pada Buku Induk (`http://localhost/SINTA-SaaS/buku-induk`).
*   Pilih Kelas dan Kurikulum. Pastikan judul header menampilkan `Kelompok Mata Pelajaran (Kelas: 10-Baru [Kurikulum Merdeka])`.

---
## Pengembangan Fitur Export & Import Excel 4 Komponen Nilai Rapor
**Waktu**: 16:25 WIB
**Status**: Dieksekusi

# Implementation Plan: Export & Import Excel 4 Komponen Nilai Rapor (KKTP, Nilai Akhir, Capaian Tertinggi, Capaian Terendah)

Mengembangkan template berkas Excel ekspor dan impor agar mencakup 4 kolom penilaian lengkap per mata pelajaran.

---

## 1. Rencana Perubahan (Proposed Changes)
### app/Controllers/NilaiRaporController.php
#### [MODIFY] NilaiRaporController.php
*   Memperbarui fungsi `export()` untuk menghasilkan 4 sub-kolom per mata pelajaran: `KKTP`, `Nilai Akhir`, `Capaian Tertinggi`, dan `Capaian Terendah`.
*   Memperbarui fungsi `import()` dan `validateExcelImportApi()` untuk memetakan dan menyimpan keempat komponen nilai tersebut ke kolom `kkm`, `nilai_akhir`, dan `nilai_detail_json`.

---

## 2. Verification Plan
*   Buka tab **Input Nilai Rapor** pada Buku Induk (`http://localhost/SINTA-SaaS/buku-induk`).
*   Klik **Unduh Format Excel**, buka berkas `.xlsx`, pastikan terdapat kolom KKTP, Nilai Akhir, Capaian Tertinggi, dan Capaian Terendah per mata pelajaran.
*   Isi data lalu upload melalui **Impor dari Excel**, pastikan seluruh data terimpor dengan sukses.

---
## Pemulihan Data Mapel Terhapus & Proteksi Penghapusan Master Data Ber-relasi
**Waktu**: 16:30 WIB
**Status**: Dieksekusi

# Implementation Plan: Pemulihan Data Mapel & Proteksi Penghapusan Master Data Ber-relasi

Memulihkan data mata pelajaran terhapus dan menambahkan proteksi penghapusan pada model Kelembagaan untuk memblokir aksi hapus pada data master yang sedang digunakan.

---

## 1. Rencana Perubahan (Proposed Changes)
### app/Models/Kelembagaan.php
#### [MODIFY] Kelembagaan.php
*   Menambahkan fungsi `checkDataInUse(string $table, int $id)` untuk mengecek relasi data di tabel transaksi (`pemetaan_mapel`, `detail_nilai_rapor`, `pdss_config_mapel`, `siswa`, `kelas_kurikulum`, dll.).
*   Memperbarui fungsi `delete()` agar melempar exception pencegahan jika data sedang digunakan.

---

## 2. Verification Plan
*   Buka **Master Data Kelembagaan** (`http://localhost/SINTA-SaaS/master-data`) pada tab **Mata Pelajaran**.
*   Pastikan 11 mata pelajaran yang sempat terhapus telah kembali aktif.
*   Coba hapus mata pelajaran yang sedang digunakan dalam Pemetaan Mapel. Pastikan sistem menampilkan pesan blokir ramah pengguna dan menyarankan untuk menggunakan saklar status nonaktif.

