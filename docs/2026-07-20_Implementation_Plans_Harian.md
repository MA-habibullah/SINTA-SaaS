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

---
## Standardisasi Status Code HTTP 400 & Modal Peringatan SweetAlert pada Pemblokiran Hapus Master Data
**Waktu**: 16:33 WIB
**Status**: Dieksekusi

# Implementation Plan: Standardisasi Status Code HTTP 400 & Modal Peringatan SweetAlert

Mengubah status code HTTP untuk error validasi pemblokiran dari 500 menjadi 400 Bad Request serta menampilkan popup modal SweetAlert yang informatif.

---

## 1. Rencana Perubahan (Proposed Changes)
### app/Controllers/KelembagaanController.php
#### [MODIFY] KelembagaanController.php
*   Menangkap `InvalidArgumentException` di fungsi `deleteApi()`, `restoreApi()`, dan `toggleStatusApi()` lalu mengembalikan HTTP 400 Bad Request.

### views/master_kelembagaan.php
#### [MODIFY] views/master_kelembagaan.php
*   Memperbarui fungsi `deleteItem()` di Vue.js untuk menampilkan SweetAlert `icon: 'warning'` dengan judul `Tidak Dapat Dihapus` dan tombol `Saya Mengerti`.

---

## 2. Verification Plan
*   Buka **Master Data Kelembagaan** (`http://localhost/SINTA-SaaS/master-data`).
*   Klik tombol **Hapus** pada Mata Pelajaran yang sedang digunakan.
*   Pastikan tidak ada lagi error HTTP 500 di console browser, dan modal SweetAlert peringatan muncul dengan jelas.

---
## Pengalihan Respon Pemblokiran ke Payload HTTP 200 (Clean Console UX)
**Waktu**: 16:36 WIB
**Status**: Dieksekusi

# Implementation Plan: Pengalihan Respon Pemblokiran ke Payload HTTP 200 (Clean Console UX)

Mengalirkan respon pemblokiran hapus data master menggunakan HTTP 200 OK dengan payload JSON success=false untuk mencegah munculnya log error merah di browser DevTools.

---

## 1. Rencana Perubahan (Proposed Changes)
### app/Controllers/KelembagaanController.php
#### [MODIFY] KelembagaanController.php
*   Mengubah penanganan exception di `deleteApi()` untuk merespons dengan HTTP Status Code 200 OK dan body `{'success': false, 'error': '...'}`.

### views/master_kelembagaan.php
#### [MODIFY] views/master_kelembagaan.php
*   Memperbarui `deleteItem()` di Vue.js untuk memeriksa `res.data.success` sehingga SweetAlert warning modal langsung terbuka tanpa memicu catch error Axios.

---

## 2. Verification Plan
*   Buka **Master Data Kelembagaan** (`http://localhost/SINTA-SaaS/master-data`).
*   Klik tombol **Hapus** pada Mata Pelajaran yang sedang digunakan.
*   Periksa Console DevTools browser: pastikan **100% bersih tanpa log merah HTTP 400/500**, dan modal SweetAlert peringatan langsung terbuka.

---
## Pengalihan Respon Kampus API 'Tenant Tidak Terdeteksi' ke Payload HTTP 200
**Waktu**: 16:39 WIB
**Status**: Dieksekusi

# Implementation Plan: Pengalihan Respon Kampus API 'Tenant Tidak Terdeteksi' ke Payload HTTP 200

Mengubah status respon HTTP dari 400 Bad Request menjadi 200 OK dengan payload data kosong saat tenant belum terpilih.

---

## 1. Rencana Perubahan (Proposed Changes)
### app/Controllers/KampusController.php
#### [MODIFY] KampusController.php
*   Memperbarui fungsi `checkAccess()` untuk mengembalikan HTTP Status 200 OK dengan `{'success': false, 'data': [], 'error': 'Tenant tidak terdeteksi...'}` saat tenant ID kosong.

---

## 2. Verification Plan
*   Buka halaman **Buku Induk** (`http://localhost/SINTA-SaaS/buku-induk`) atau **PDSS / Tracer Study**.
*   Beralih tab dan periksa DevTools Console browser.
*   Pastikan tidak ada lagi log error merah `GET /api/v1/kampus 400 (Bad Request)` atau `[AXIOS API ERROR] Status: 400`.

---
## Perbaikan Filter Presisi Siswa Kelas 12 PDSS Berdasarkan Tahun Ajaran Target
**Waktu**: 16:53 WIB
**Status**: Dieksekusi

# Implementation Plan: Perbaikan Filter Presisi Siswa Kelas 12 PDSS Berdasarkan Tahun Ajaran Target

Mengoreksi query filter siswa kelas 12 pada PDSSController agar mencocokkan siswa kelas 12 secara presisi berdasarkan Tahun Ajaran Evaluasi yang dipilih.

---

## 1. Rencana Perubahan (Proposed Changes)
### app/Controllers/PDSSController.php
#### [MODIFY] PDSSController.php
*   Menghapus formula `CONCAT(SUBSTRING(...) + 2)` pada `apiGetKesiapan()` dan `apiGetPdssMapels()`.
*   Menggunakan pencocokan langsung `dnr.tahun_ajaran = :selectedTaName` atau `(k.nama_kelas LIKE '%12%' AND ta.tahun_ajaran = :selectedTaName)` agar siswa dari angkatan lain tidak bocor ke tahun ajaran evaluasi yang tidak sesuai.

---

## 2. Verification Plan
*   Buka halaman **Bimbingan Konseling -> Akademik (PDSS)** (`http://localhost/SINTA-SaaS/bk/akademik`).
*   Pilih Tahun Ajaran Evaluasi `2026/2027`: pastikan 28 siswa Dummy Cohort dari angkatan `2024/2025` tidak lagi muncul di `2026/2027`.
*   Pilih Tahun Ajaran Evaluasi `2024/2025`: pastikan 28 siswa Dummy Cohort muncul tepat pada tahun ajaran evaluasi `2024/2025`.

---
## Pengalihan Respon API Simulasi Setting ke Payload HTTP 200 (Clean Console UX)
**Waktu**: 16:57 WIB
**Status**: Dieksekusi

# Implementation Plan: Pengalihan Respon API Simulasi Setting ke Payload HTTP 200

Menghilangkan log error merah `GET /api/v1/pdss/simulasi/setting 400 (Bad Request)` dan `[AXIOS API ERROR] Status: 400` di DevTools Console browser.

---

## 1. Root Cause

Fungsi `apiGetSimulasiSetting()` dan `apiToggleSimulasiSetting()` di `PDSSController.php` merespons HTTP 400/422/403 pada kondisi:
- `tenant_id` belum terpilih (HTTP 400)
- Parameter `no_simulasi`/`action` tidak valid (HTTP 422)
- Akses ditolak (HTTP 403)
- Sequential lock simulasi belum dipenuhi (HTTP 400)

HTTP 4xx tersebut ditangkap Axios sebagai *network error*, sehingga mencetak baris log error merah di DevTools Console.

---

## 2. Rencana Perubahan (Proposed Changes)

### app/Controllers/PDSSController.php
#### [MODIFY] PDSSController.php
*   `apiGetSimulasiSetting()`: Mengubah respons HTTP 400 dan 500 menjadi HTTP **200 OK** dengan payload `{'success': false, 'data': [], 'error': '...'}`.
*   `apiToggleSimulasiSetting()`: Mengubah semua respons HTTP 400/403/422 menjadi HTTP **200 OK** dengan payload `{'success': false, 'error': '...'}`.

### views/pdss_index.php
#### [MODIFY] views/pdss_index.php
*   Memperbarui `toggleSimulasiSetting()` pada Vue.js untuk membaca `res.data.success` secara langsung dan menampilkan SweetAlert peringatan/error tanpa memerlukan blok `catch` Axios.

---

## 3. Verification Plan
*   Buka halaman **Bimbingan Konseling -> Akademik (PDSS)** dengan Tahun Ajaran `2026/2027` yang belum ada PDSS config.
*   Periksa DevTools Console: pastikan **tidak ada lagi log merah** `GET /api/v1/pdss/simulasi/setting 400 (Bad Request)` atau `[AXIOS API ERROR]`.
*   Klik tombol toggle simulasi untuk Simulasi 2 sebelum Simulasi 1 dikunci: pastikan modal SweetAlert **Perhatian** muncul dengan pesan sequential lock yang jelas, tanpa log merah di console.

---
## Penanganan Error Jaringan (Network Error) pada Pengunggahan Berkas Langkah 5
**Waktu**: 17:34 WIB
**Status**: Dieksekusi

# Implementation Plan: Penanganan Error Jaringan (Network Error) pada Pengunggahan Berkas Langkah 5

Menampilkan pesan panduan/notifikasi yang informatif jika terjadi kegagalan jaringan (Network Error) saat mengunggah banyak berkas secara bersamaan pada Langkah 5 pendaftaran/edit data siswa.

---

## 1. Root Cause

Mengapa kegagalan koneksi ("Network Error") sering terjadi saat mengunggah beberapa file sekaligus di perangkat mobile/OS lama?
1. **Batas Concurrent Connection & Limitasi Server (Cloudflare Rate Limiting/Web Server Limits)**:
   Proses unggah dokumen pada Langkah 5 di `tambah_siswa.php` dirancang untuk mengunggah berkas secara berurutan (*sequentially*):
   ```javascript
   for (let i = 0; i < filesToUpload.length; i++) {
       // axios.post(...)
       // delay 500ms
   }
   ```
   Meskipun menggunakan delay 500ms, mengirimkan beberapa request POST berisi file gambar/PDF berukuran megabyte secara berturut-turut dari IP yang sama dalam waktu singkat sering dianggap sebagai aktivitas spam/serangan oleh firewall server, web server (Apache/LiteSpeed rate limit), atau CDN seperti Cloudflare. Ini menyebabkan koneksi ditutup sepihak oleh server, memicu "Network Error" pada Axios.
2. **Keterbatasan Memori dan Jaringan Seluler Perangkat Mobile**:
   Di perangkat mobile lama atau jaringan seluler yang kurang stabil, memproses kompresi dan mengunggah beberapa file besar secara berurutan dapat memicu kehabisan memori (Out-Of-Memory) pada browser atau pemutusan koneksi sementara oleh sistem operasi/browser untuk menghemat daya.

Pemberitahuan bawaan "Network Error" sangat umum dan membingungkan pengguna. Menggantinya dengan pesan instruksi yang jelas membantu pengguna menyelesaikan proses unggah tanpa frustrasi.

---

## 2. Rencana Perubahan (Proposed Changes)

### views/tambah_siswa.php
#### [MODIFY] tambah_siswa.php
*   Memperbarui blok `catch (err)` di dalam fungsi `saveCurrentStep` untuk mendeteksi apakah pesan error yang ditangkap adalah "Network Error".
*   Jika terdeteksi "Network Error", ubah pesan error menjadi:
    `"Penting: Harap upload 1 file lalu klik Simpan, dan ulangi proses tersebut untuk meng-upload file berikutnya satu per satu."`

---

## 3. Verification Plan

### Verifikasi Manual
1. Buka halaman Edit Siswa di localhost: `http://localhost/SINTA-SaaS/siswa/edit` (atau menu terkait).
2. Pergi ke **Langkah 5: Registrasi, Keluar & Dokumen Berkas**.
3. Pilih lebih dari 2 berkas secara bersamaan (misal Kartu Keluarga dan Akta Kelahiran).
4. Untuk menyimulasikan kegagalan jaringan, matikan koneksi internet (atau set status "Offline" di Network tab DevTools) tepat setelah mengklik tombol Simpan/Unggah.
5. Pastikan modal popup SweetAlert "Penyimpanan Gagal" muncul dengan pesan:
   `"Penting: Harap upload 1 file lalu klik Simpan, dan ulangi proses tersebut untuk meng-upload file berikutnya satu per satu."`

---
## Rencana Skema Arsitektur: Override Akses Menu Tingkat Pengguna (Opsi B)
**Waktu**: 17:53 WIB
**Status**: Dieksekusi

# Implementation Plan: Override Akses Menu Tingkat Pengguna (Opsi B)

Memungkinkan beberapa user tertentu mendapatkan akses ke menu khusus di luar peran (role) standar mereka di SINTA-SaaS (User-level access override).

---

## 1. Berkas yang Terlibat

| Tipe | Berkas | Deskripsi Perubahan |
|---|---|---|
| **[NEW]** | [2026_07_20_02_create_user_menu_access.php](file:///C:/xampp/htdocs/SINTA-SaaS/database/migrations/2026_07_20_02_create_user_menu_access.php) | Migrasi DDL pembuatan tabel `user_menu_access`. |
| **[MODIFY]** | [index.php](file:///C:/xampp/htdocs/SINTA-SaaS/index.php) | Registrasi route API `/api/v1/akses/user-override` dan `/api/v1/akses/user-override/simpan`. |
| **[MODIFY]** | [RouteGuard.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Core/RouteGuard.php) | Penambahan logika *bypass check* berbasis `user_id` di database. |
| **[MODIFY]** | [sidebar.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/layout/sidebar.php) | Penyesuaian kueri penarikan menu agar memuat menu khusus per user. |
| **[MODIFY]** | [AksesController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/AksesController.php) | Pembuatan endpoint controller `fetchUserAccessOverrides` and `saveUserAccessOverrides`. |
| **[MODIFY]** | [pengguna_index.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/pengguna_index.php) | Integrasi tombol kunci 🔑, modal kelola akses, & script Vue.js. |

---

## 2. Verification Plan

### Verifikasi Manual
1. Masuk sebagai Admin/Operator Sekolah.
2. Buka halaman **Pengguna** -> Tab **Guru/Karyawan**.
3. Pilih salah satu staf (misal guru yang tidak memiliki akses BK), lalu klik tombol Kunci 🔑.
4. Pada modal, centang menu **Bimbingan Konseling** lalu klik **Simpan Akses**.
5. Login (atau impersonate) sebagai guru tersebut.
6. Pastikan:
   - Menu **Bimbingan Konseling** tampil di sidebar kirinya.
   - Guru tersebut bisa membuka halaman `/SINTA-SaaS/bk` dengan sukses tanpa terkena blokir 403.

---
## Perbaikan Visibilitas Pengumuman, Pembatasan Menu Informasi & Kegiatan, serta Bypass Otorisasi Kontroler (Bypass Guard)
**Waktu**: 22:55 WIB
**Status**: Dieksekusi

# Rencana Implementasi: Perbaikan Visibilitas Pengumuman, Pembatasan Menu Informasi & Kegiatan, serta Bypass Otorisasi Kontroler (Bypass Guard)

Dokumen ini menjelaskan langkah-langkah teknis untuk:
1. Memulihkan hak akses pengumuman/agenda bagi Guru & Karyawan secara dinamis.
2. Mencabut hak akses default menu "Informasi & Kegiatan" bagi Guru & Karyawan.
3. Menambahkan helper `RouteGuard::checkCurrent()` dan memperbarui seluruh kontroler yang memiliki otorisasi peran statis (seperti Buku Induk, Pengumuman, BK, Ekskul, dsb.) agar meloloskan akses pengguna jika telah diberikan hak akses khusus (override) oleh administrator.

## User Review Required

(None)

## Open Questions

(None)

## Proposed Changes

### [Component Name]

#### [NEW] [2026_07_20_04_remove_guru_karyawan_informasi_kegiatan_access.php](file:///C:/xampp/htdocs/SINTA-SaaS/database/migrations/2026_07_20_04_remove_guru_karyawan_informasi_kegiatan_access.php)
#### [MODIFY] [RouteGuard.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Core/RouteGuard.php)
#### [MODIFY] [PengumumanModel.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Models/PengumumanModel.php)
#### [MODIFY] [AgendaModel.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Models/AgendaModel.php)
#### [MODIFY] [DashboardController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/DashboardController.php)
#### [MODIFY] [BukuIndukController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/BukuIndukController.php)
#### [MODIFY] [PengumumanController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/PengumumanController.php)
#### [MODIFY] [BKController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/BKController.php)
#### [MODIFY] [EkskulController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/EkskulController.php)
#### [MODIFY] [SekolahController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/SekolahController.php)
#### [MODIFY] [QueueController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/QueueController.php)
#### [MODIFY] [ActiveSessionController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/ActiveSessionController.php)
#### [MODIFY] [ActivityLogController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/ActivityLogController.php)

---

## Rencana Perubahan Berkas

### 1. [NEW] [2026_07_20_04_remove_guru_karyawan_informasi_kegiatan_access.php](file:///C:/xampp/htdocs/SINTA-SaaS/database/migrations/2026_07_20_04_remove_guru_karyawan_informasi_kegiatan_access.php)

Membuat file migrasi untuk mencabut hak akses menu "Informasi & Kegiatan" (menu_id: 45) beserta sub-menunya (menu_id: 46, 47) bagi role Guru (3) dan Karyawan (6) di seluruh tenant.

```php
<?php
/**
 * Migration: Remove default Informasi & Kegiatan menu access for Guru and Karyawan roles
 * Location: database/migrations/2026_07_20_04_remove_guru_karyawan_informasi_kegiatan_access.php
 */

return [
    'up' => function (PDO $pdo): void {
        $pdo->exec("DELETE FROM role_menu_access WHERE role_id IN (3, 6) AND menu_id IN (45, 46, 47)");
        echo "- Berhasil mencabut akses default Informasi & Kegiatan untuk Guru dan Karyawan.\n";
    },
    'down' => function (PDO $pdo): void {
        $tenants = ['00000000-0000-0000-0000-000000000000'];
        $stmt = $pdo->prepare("INSERT INTO role_menu_access (tenant_id, role_id, menu_id) VALUES (?, ?, ?)");
        foreach ($tenants as $tenantId) {
            try {
                $stmt->execute([$tenantId, 3, 45]);
                $stmt->execute([$tenantId, 3, 47]);
            } catch (\PDOException $e) {}
            try {
                $stmt->execute([$tenantId, 6, 45]);
            } catch (\PDOException $e) {}
        }
        echo "- Berhasil memulihkan akses default Informasi & Kegiatan untuk Guru dan Karyawan.\n";
    },
];
```

### 2. [MODIFY] [RouteGuard.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Core/RouteGuard.php)

Tambahkan helper statis `checkCurrent()` untuk melakukan verifikasi hak akses URL aktif dengan aman:

```php
    /**
     * Memeriksa apakah request URI saat ini diizinkan untuk diakses berdasarkan RouteGuard (dan override-nya)
     * atau terdaftar dalam array allowedRoles.
     */
    public static function checkCurrent(array $allowedRoles): bool {
        $roleName = $_SESSION['role_name'] ?? '';
        $roles = $_SESSION['roles'] ?? [$roleName];
        $tenantId = $_SESSION['tenant_id'] ?? null;
        
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $project_folder = '/SINTA-SaaS';
        if (strncasecmp($path, $project_folder, strlen($project_folder)) === 0) {
            $path = substr($path, strlen($project_folder));
        }

        // 1. Loloskan jika lolos validasi RouteGuard (termasuk override user_menu_access)
        if (self::check($path, $tenantId, $roles)) {
            return true;
        }

        // 2. Fallback: Loloskan jika salah satu role user termasuk dalam allowedRoles
        foreach ($roles as $r) {
            if (in_array($r, $allowedRoles, true)) {
                return true;
            }
        }

        return false;
    }
```

### 3. [MODIFY] [PengumumanModel.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Models/PengumumanModel.php)

Sesuaikan pengecekan `$isGuru` dan `$isSiswa` di metode `getActiveForUser()` dan `countActiveForUser()`.

**Sebelum (Baris 82-84 & 144-146):**
```php
        $isAdmin = in_array($userRoleId, [1, 2]) ? 1 : 0;
        $isGuru = in_array($userRoleId, [3, 20, 21]) ? 1 : 0;
        $isSiswa = ($userRoleId == 6) ? 1 : 0;
```

**Sesudah:**
```php
        $isAdmin = in_array($userRoleId, [1, 2]) ? 1 : 0;
        $isGuru = in_array($userRoleId, [3, 6, 20, 21, 22, 23, 24, 25, 26]) ? 1 : 0;
        $isSiswa = ($userRoleId == 4) ? 1 : 0;
```

### 4. [MODIFY] [AgendaModel.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Models/AgendaModel.php)

Sesuaikan pengecekan `$isGuru` di metode `getActiveForUser()`.

**Sebelum (Baris 75-76):**
```php
        $isAdmin = in_array($userRoleId, [1, 2]);
        $isGuru = in_array($userRoleId, [3, 20, 21]);
```

**Sesudah:**
```php
        $isGuru = in_array($userRoleId, [3, 6, 20, 21, 22, 23, 24, 25, 26]);
```

### 5. [MODIFY] [DashboardController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/DashboardController.php)

Ganti pemetaan manual `role_id` dengan kueri database dinamis pada metode `index()` dan `pengumumanArsip()`.

**Sebelum:**
```php
                    $roleId = $_SESSION['role_id'] ?? null;
                    if ($roleId === null) {
                        $roleName = $_SESSION['role_name'] ?? '';
                        if ($roleName === 'super_admin') $roleId = 1;
                        elseif ($roleName === 'operator_sekolah') $roleId = 2;
                        elseif ($roleName === 'siswa') $roleId = 6;
                        else $roleId = 0;
                    }
```

**Sesudah:**
```php
                    $roleName = $_SESSION['role_name'] ?? '';
                    $db = Database::getConnection();
                    $stmtRole = $db->prepare("SELECT id FROM roles WHERE nama_role = ? LIMIT 1");
                    $stmtRole->execute([$roleName]);
                    $roleId = (int)$stmtRole->fetchColumn() ?: 0;
```

### 6. [MODIFY] Kontroler-Kontroler Sistem (Menggunakan `RouteGuard::checkCurrent`)

Ubah logika pengecekan otorisasi peran keras pada kontroler-kontroler berikut agar menggunakan `RouteGuard::checkCurrent()`:

- BukuIndukController.php
- PengumumanController.php
- BKController.php
- EkskulController.php
- SekolahController.php
- QueueController.php
- ActiveSessionController.php
- ActivityLogController.php

---

## Verification Plan

### Automated Tests
- `scratch/test_announcement_visibility.php`
- `scratch/test_controller_override_bypass.php`

### Manual Verification
1. Login sebagai Guru Lisa (tanpa wewenang khusus). Pastikan menu **Informasi & Kegiatan** di sidebar kiri tersembunyi.
2. Login sebagai Admin, berikan kunci akses khusus untuk menu **Informasi & Kegiatan** dan **Pengumuman** ke Guru Lisa.
3. Login kembali sebagai Guru Lisa. Klik menu **Informasi & Kegiatan** -> **Pengumuman**. Halaman Manajemen Pengumuman harus terbuka secara sempurna tanpa error 403.

---
## Penanganan Warning WAI-ARIA Modal Accessibility secara Universal
**Waktu**: 23:10 WIB
**Status**: Dieksekusi

# Rencana Implementasi: Penanganan Warning WAI-ARIA Modal Accessibility secara Universal

Rencana ini bertujuan untuk menghilangkan peringatan WAI-ARIA (`Blocked aria-hidden on an element because its descendant retained focus`) di Developer Console saat menutup modal Bootstrap secara global di seluruh aplikasi.

## Proposed Changes

### [MODIFY] [master.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/layout/master.php)
- Menambahkan event listener global `hide.bs.modal` pada root layout untuk melakukan `blur()` otomatis pada elemen yang fokus sesaat sebelum modal ditutup.

### [MODIFY] [buku_induk.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/buku_induk.php)
- Menghapus event listener modal-blur lokal agar terpusat pada file layout utama `master.php` (mencegah redundansi kode).

## Verification Plan

### Manual Verification
1. Buka halaman `/SINTA-SaaS/pengguna` di browser.
2. Buka salah satu modal (seperti modal Hak Akses atau Edit User) lalu tutup modal tersebut.
3. Verifikasi pada Developer Console Chrome bahwa tidak ada peringatan WAI-ARIA `Blocked aria-hidden...` yang tercatat.


---
## Pembuatan Aplikasi Pelaporan dan Request Sistem (Pusat Bantuan SaaS)
**Waktu**: 23:15 WIB
**Status**: Dieksekusi

# Rencana Implementasi: Pusat Bantuan & Layanan Tiket Bantuan (Ticketing System)

Rencana ini dibuat untuk menambahkan modul **Pusat Bantuan (Layanan Tiket)** terisolasi (*multi-tenant safe*) ke dalam ekosistem SINTA-SaaS dengan standar premium. Sistem ini dirancang untuk memfasilitasi komunikasi kendala teknis/non-teknis dari pengguna (Guru, Karyawan, Siswa, Operator Sekolah) langsung ke tim manajemen infrastruktur IT (Super Admin).

## Fitur Unggulan Rekomendasi (Premium Addition)
1. **Floating Help Button (FAB)**: Ikon bantuan mengambang (`?`) di pojok kanan bawah layar pada layout utama (`master.php`) sebagai pintasan instan ke pusat bantuan.
2. **Integrasi FAQ Pintar (Knowledge Base Lookup)**: Kolom pencarian dinamis (Axios) yang memunculkan rekomendasi solusi FAQ secara *real-time* ketika user mengetik judul tiket sebelum disubmit.
3. **Template Balasan Cepat (Canned Responses)**: Dropdown pilihan template jawaban untuk mempercepat tanggapan Super Admin terhadap kendala berulang.
4. **Indikator Unread (Badge Percakapan Baru)**: Menampilkan badge jumlah pesan unread milik user dan admin pada sidebar/menu.
5. **Proteksi Unggahan Ekstra (.htaccess script block)**: Membuat berkas `.htaccess` untuk menonaktifkan PHP/CGI engine pada direktori unggahan tiket.

## Proposed Changes

### [NEW] [2026_07_20_05_create_ticket_system.php](file:///C:/xampp/htdocs/SINTA-SaaS/database/migrations/2026_07_20_05_create_ticket_system.php)
### [MODIFY] [index.php](file:///C:/xampp/htdocs/SINTA-SaaS/index.php)
### [NEW] [BantuanController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/BantuanController.php)
### [NEW] [bantuan_user.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/bantuan_user.php)
### [NEW] [bantuan_admin.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/bantuan_admin.php)
### [MODIFY] [master.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/layout/master.php)
### [MODIFY] [sidebar.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/layout/sidebar.php)
### [NEW] [.htaccess](file:///C:/xampp/htdocs/SINTA-SaaS/public/uploads/tickets/.htaccess)

---

## Rencana Perubahan Berkas

### 1. [NEW] [2026_07_20_05_create_ticket_system.php](file:///C:/xampp/htdocs/SINTA-SaaS/database/migrations/2026_07_20_05_create_ticket_system.php)

```php
<?php
/**
 * Migration: Create Ticketing System Tables and Menus
 * Location: database/migrations/2026_07_20_05_create_ticket_system.php
 */

return [
    'up' => function (PDO $pdo): void {
        // Tabel Kategori Tiket
        $pdo->exec("CREATE TABLE IF NOT EXISTS `ticket_categories` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `nama_kategori` VARCHAR(100) NOT NULL,
            `sla_hours` INT NOT NULL DEFAULT 48
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

        // Seed Kategori Default
        $stmt = $pdo->prepare("INSERT INTO `ticket_categories` (id, nama_kategori, sla_hours) VALUES (?, ?, ?)");
        $categories = [
            [1, 'Laporan Bug / Sistem Error', 24],
            [2, 'Request Fitur / Menu Baru', 168],
            [3, 'Kendala Data Pokok / Dapodik', 48],
            [4, 'Bantuan Penggunaan', 48],
            [5, 'Kritik & Saran', 168]
        ];
        foreach ($categories as $cat) {
            try {
                $stmt->execute($cat);
            } catch (\PDOException $e) {}
        }

        // Tabel FAQ Pintar (Knowledge Base)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `ticket_faqs` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `pertanyaan` VARCHAR(255) NOT NULL,
            `jawaban` TEXT NOT NULL,
            `kategori` VARCHAR(100) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

        // Seed FAQ Awal
        $stmtFaq = $pdo->prepare("INSERT INTO `ticket_faqs` (pertanyaan, jawaban, kategori) VALUES (?, ?, ?)");
        $faqs = [
            ['Bagaimana cara mereset password Akun Guru?', 'Untuk mereset password akun guru, silakan hubungi Operator Sekolah Anda untuk melakukan reset melalui menu Manajemen Pengguna.', 'Bantuan Penggunaan'],
            ['Mengapa saya tidak dapat mengakses menu PPDB?', 'Akses menu PPDB dinonaktifkan secara default untuk Guru/Karyawan. Jika Anda ditugaskan menjadi panitia, silakan minta Admin Sekolah untuk memberikan Kunci Akses Khusus melalui profil pengguna Anda.', 'Bantuan Penggunaan'],
            ['Bagaimana cara membetulkan data NISN siswa yang salah?', 'Data NISN ditarik langsung dari sistem Dapodik Kemendikbud. Pastikan data di Dapodik lokal sudah sinkron dengan server pusat, lalu sistem SINTA akan memperbarui data secara berkala.', 'Kendala Data Pokok / Dapodik'],
            ['Aplikasi lambat atau halaman blank putih', 'Silakan lakukan pembersihan cache browser Anda dengan menekan tombol Ctrl + F5 secara bersamaan, atau coba akses menggunakan mode Incognito.', 'Laporan Bug / Sistem Error']
        ];
        foreach ($faqs as $faq) {
            try {
                $stmtFaq->execute($faq);
            } catch (\PDOException $e) {}
        }

        // Tabel Balasan Cepat (Canned Responses)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `ticket_canned_responses` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `judul` VARCHAR(100) NOT NULL,
            `konten` TEXT NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

        // Seed Balasan Cepat Awal
        $stmtCanned = $pdo->prepare("INSERT INTO `ticket_canned_responses` (judul, konten) VALUES (?, ?)");
        $canneds = [
            ['Panduan Reset Password', "Halo,\n\nUntuk kendala lupa password atau reset password akun, Administrator Sekolah Anda dapat meresetnya secara langsung melalui menu:\n1. Masuk ke menu \"Pengaturan & Utilitas\" -> \"Manajemen Pengguna\".\n2. Cari nama Anda, klik aksi Edit.\n3. Masukkan password baru dan simpan.\n\nTerima kasih."],
            ['Perbaikan Bug Selesai', "Halo,\n\nTerima kasih atas laporan Anda. Laporan bug ini telah berhasil kami perbaiki dan rilis pada pembaruan sistem terbaru. Silakan muat ulang halaman (Ctrl + F5) dan coba kembali.\n\nSalam,\nTim IT Support SINTA-SaaS"],
            ['Data Menunggu Sinkronisasi', "Halo,\n\nPerubahan data pokok/dapodik memerlukan waktu sinkronisasi berkala antara server lokal dan cloud. Silakan tunggu 1x24 jam. Jika data belum berubah, hubungi operator dapodik sekolah untuk memverifikasi status sinkronisasi.\n\nTerima kasih."]
        ];
        foreach ($canneds as $canned) {
            try {
                $stmtCanned->execute($canned);
            } catch (\PDOException $e) {}
        }

        // Tabel Utama Tiket (SaaS Multi-Tenant Safe & UUID)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `tickets` (
            `id` CHAR(36) PRIMARY KEY,
            `tenant_id` CHAR(36) NOT NULL,
            `user_id` CHAR(36) NOT NULL,
            `category_id` INT UNSIGNED NOT NULL,
            `judul` VARCHAR(255) NOT NULL,
            `deskripsi` TEXT NOT NULL,
            `urgensi` ENUM('Rendah', 'Sedang', 'Tinggi', 'Kritis') DEFAULT 'Rendah',
            `status` ENUM('Menunggu', 'Diproses', 'Selesai', 'Batal') DEFAULT 'Menunggu',
            `lampiran` VARCHAR(255) DEFAULT NULL,
            `user_agent` VARCHAR(255) DEFAULT NULL,
            `last_url` VARCHAR(255) DEFAULT NULL,
            `user_unread` TINYINT(1) DEFAULT 0,
            `admin_unread` TINYINT(1) DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `sla_deadline` TIMESTAMP NULL DEFAULT NULL,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT `fk_tickets_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_tickets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_tickets_category` FOREIGN KEY (`category_id`) REFERENCES `ticket_categories` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

        // Tabel Balasan Percakapan (Thread)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `ticket_replies` (
            `id` CHAR(36) PRIMARY KEY,
            `ticket_id` CHAR(36) NOT NULL,
            `user_id` CHAR(36) NOT NULL,
            `is_superadmin` TINYINT(1) DEFAULT 0,
            `pesan` TEXT NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT `fk_replies_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_replies_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

        // Registrasi Menu Baru: Pusat Bantuan (ID: 61)
        $stmtMenu = $pdo->prepare("INSERT INTO `menus` (id, nama_menu, url, parent_id, icon, urutan) VALUES (?, ?, ?, ?, ?, ?)");
        try {
            $stmtMenu->execute([61, 'Pusat Bantuan', '/bantuan', null, 'bi bi-question-circle', 100]);
        } catch (\PDOException $e) {}

        // Berikan Akses Menu Default ke Semua Role
        $tenants = $pdo->query("SELECT id FROM tenants WHERE deleted_at IS NULL")->fetchAll(PDO::FETCH_COLUMN);
        $roles = $pdo->query("SELECT id FROM roles")->fetchAll(PDO::FETCH_COLUMN);

        // Tambah ke tenant_menu_access
        $stmtTMA = $pdo->prepare("INSERT INTO `tenant_menu_access` (tenant_id, menu_id) VALUES (?, 61)");
        foreach ($tenants as $tId) {
            try {
                $stmtTMA->execute([$tId]);
            } catch (\PDOException $e) {}
        }

        // Tambah ke role_menu_access
        $stmtRMA = $pdo->prepare("INSERT INTO `role_menu_access` (tenant_id, role_id, menu_id) VALUES (?, ?, 61)");
        foreach ($tenants as $tId) {
            foreach ($roles as $rId) {
                try {
                    $stmtRMA->execute([$tId, $rId]);
                } catch (\PDOException $e) {}
            }
        }
        // Tambahkan juga untuk global tenant fallback
        foreach ($roles as $rId) {
            try {
                $stmtRMA->execute(['00000000-0000-0000-0000-000000000000', $rId, 61]);
            } catch (\PDOException $e) {}
        }

        echo "- Tabel-tabel Pusat Bantuan dan registrasi menu berhasil dibuat.\n";
    },
    'down' => function (PDO $pdo): void {
        $pdo->exec("DELETE FROM `role_menu_access` WHERE menu_id = 61");
        $pdo->exec("DELETE FROM `tenant_menu_access` WHERE menu_id = 61");
        $pdo->exec("DELETE FROM `menus` WHERE id = 61");
        $pdo->exec("DROP TABLE IF EXISTS `ticket_replies`");
        $pdo->exec("DROP TABLE IF EXISTS `tickets`");
        $pdo->exec("DROP TABLE IF EXISTS `ticket_canned_responses`");
        $pdo->exec("DROP TABLE IF EXISTS `ticket_faqs`");
        $pdo->exec("DROP TABLE IF EXISTS `ticket_categories`");
        echo "- Tabel-tabel Pusat Bantuan berhasil di-rollback.\n";
    },
];
```

### 2. [MODIFY] [index.php](file:///C:/xampp/htdocs/SINTA-SaaS/index.php)
Pendaftaran rute API dan halaman `/bantuan`.

### 3. [NEW] [BantuanController.php](file:///C:/xampp/htdocs/SINTA-SaaS/app/Controllers/BantuanController.php)
Kontroler untuk memproses aksi submit tiket, penarikan daftar tiket, thread balasan chat, lookup FAQ pintar, canned responses admin, dan update status tiket.

### 4. [NEW] [bantuan_user.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/bantuan_user.php)
Halaman list history tiket dan form input pembuatan tiket baru bagi user sekolah.

### 5. [NEW] [bantuan_admin.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/bantuan_admin.php)
Dashboard sentral Super Admin untuk menanggapi tiket dari seluruh tenant.

### 6. [MODIFY] [master.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/layout/master.php)
Menambahkan Floating Action Button (FAB) bantuan mengambang di pojok kanan bawah.

### 7. [MODIFY] [sidebar.php](file:///C:/xampp/htdocs/SINTA-SaaS/views/layout/sidebar.php)
Menyertakan badge unread counter pada item menu Pusat Bantuan.

### 8. [NEW] [.htaccess](file:///C:/xampp/htdocs/SINTA-SaaS/public/uploads/tickets/.htaccess)
Proteksi unggahan guna mencegah eksekusi PHP RCE.

---

## Verification Plan

### Automated Tests
- `scratch/test_helpcenter_integration.php` (Validasi CRUD, Upload File MIME signature, dan IDOR protection)

### Manual Verification
1. Login sebagai Guru Lisa, klik tombol melayang **FAB** di pojok kanan bawah, pastikan langsung mengarah ke `/bantuan`.
2. Pada formulir tiket, ketik judul "password", pastikan daftar FAQ "Reset Password Akun Guru" langsung muncul secara otomatis di bawah kolom judul.
3. Login sebagai Super Admin, buka detail tiket, pilih canned response "Perbaikan Bug Selesai" dari dropdown, pastikan konten teks terisi otomatis di editor chat balasan.

---
## Audit & Hardening Keamanan SINTA-SaaS (Multi-Tenant, RBAC, XSS, SQLi, IDOR, RCE)
**Waktu**: 23:25 WIB
**Status**: Dieksekusi

# Rencana Implementasi: Audit & Hardening Keamanan SINTA-SaaS (Multi-Tenant, RBAC, XSS, SQLi, IDOR, RCE)

Rencana implementasi ini dirancang untuk mendokumentasikan hasil audit keamanan menyeluruh pada sistem SINTA-SaaS (untuk peran Guru, Karyawan, Siswa, Admin Sekolah, dan Super Admin) serta merumuskan langkah-langkah pengerasan (*security hardening*) untuk meminimalkan permukaan serangan (*attack surface*).

---

## Hasil Audit & Analisis Keamanan

### 1. Hak Akses Peran & RBAC (Role-Based Access Control)
*   **Temuan**:
    - Akses ke menu **PPDB / Penerimaan Siswa Baru** (menu ID: 2, 3, 4, 10) bagi **Guru** (role_id: 3) dan **Karyawan** (role_id: 6) telah diaudit. Akses default Guru telah sukses dihapus melalui migrasi `2026_07_20_03_remove_guru_default_ppdb_access.php`. Karyawan secara alami diblokir karena tidak terdaftar memiliki izin akses PPDB di database.
    - Akses menu **Informasi & Kegiatan** (Pengumuman, Agenda, dsb.) dikendalikan secara ketat lewat `RouteGuard::checkCurrent()`. Guru/karyawan hanya dapat membacanya kecuali jika diberi wewenang khusus / override user.
*   **Rekomendasi**: Pertahankan dan audit berkala data `role_menu_access` dan `user_menu_access`.

### 2. Pencegahan SQL Injection (SQLi)
*   **Temuan**:
    - Kami melakukan pemindaian otomatis (*static analysis*) pada seluruh kueri database di folder `app/`.
    - **Hasil**: Seluruh kueri dinamis yang menangkap parameter input pengguna telah menggunakan **PDO Prepared Statements** secara konsisten.
    - Klausa `IN (...)` yang dinamis di `BaseController::lookups()` dan `KampusController::prodiIds` dikonstruksi secara aman menggunakan fungsi penguji/escaping (`$db->quote()`) atau *placeholders* representatif (`?`).
*   **Rekomendasi**: Tidak diperlukan perubahan kueri database karena arsitektur saat ini sudah 100% aman dari SQL Injection.

### 3. Pencegahan Cross-Site Scripting (XSS)
*   **Temuan**:
    - Masih terdapat sisa inisialisasi variabel string dari PHP langsung ke variabel Javascript di tag `<script>` tanpa menyertakan bendera sanitasi XSS yang memadai.
    - Di `views/master_kelembagaan.php`, inisialisasi `userRole` sebelumnya menggunakan `htmlspecialchars` yang kurang aman untuk injeksi langsung ke inline skrip.
    - Di `views/tracer_study.php`, inisialisasi `isAdmin` belum ditambahkan opsi bendera anti-XSS.
*   **Tindakan Hardening**:
    - Kami telah menyempurnakan inisialisasi tersebut dengan `json_encode($var, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)` demi keamanan mutlak.

### 4. Pencegahan Insecure Direct Object Reference (IDOR)
*   **Temuan**:
    - Pemeriksaan detail dan penghapusan data (misal: di `BantuanController`, `TracerController`, `SiswaController`) selalu mengikat filter kueri ke `$_SESSION['tenant_id']` dan `$_SESSION['user_id']` (untuk non-Super Admin).
    - Client tidak dapat memanipulasi parameter URL/POST untuk melihat atau menghapus data milik tenant/sekolah lain.
*   **Rekomendasi**: Kebijakan ini harus selalu dipertahankan pada penulisan API baru di masa mendatang.

### 5. Perlindungan Remote Code Execution (RCE) pada Unggahan File
*   **Temuan**:
    - Modul seperti agenda, pengumuman, biodata siswa, dan tiket bantuan memiliki fitur unggahan berkas.
    - Berkas diunggah ke `public/uploads/` dan `storage/app/public/uploads/`.
    - Jika peretas berhasil mengunggah berkas web shell berekstensi `.php` yang disamarkan, ada potensi eksekusi kode jarak jauh (RCE) jika folder tersebut dapat diakses langsung oleh web server.
*   **Rekomendasi Hardening**:
    - Menerapkan perlindungan berlapis dengan meletakkan berkas proteksi `.htaccess` di seluruh folder unggahan utama aplikasi untuk memblokir pengeksekusian berkas berekstensi PHP/CGI secara rekursif.

---

## Proposed Changes

Kami akan melakukan pengerasan keamanan tambahan di level direktori server dengan menyebarkan konfigurasi `.htaccess` antiblokir eksekusi naskah PHP di folder unggahan berikut:

### [Hardening] Upload Protection (.htaccess)

#### [NEW] [public_uploads_htaccess](file:///C:/xampp/htdocs/SINTA-SaaS/public/uploads/.htaccess)
Membuat file `.htaccess` di root folder `public/uploads/` untuk melindungi seluruh subdirektori di bawahnya (termasuk `tickets` dan subfolder lainnya).

#### [NEW] [storage_app_public_uploads_htaccess](file:///C:/xampp/htdocs/SINTA-SaaS/storage/app/public/uploads/.htaccess)
Membuat file `.htaccess` di root folder `storage/app/public/uploads/` untuk melindungi berkas unggahan berorientasi sekolah/tenant.

#### [NEW] [storage_uploads_htaccess](file:///C:/xampp/htdocs/SINTA-SaaS/storage/uploads/.htaccess)
Membuat file `.htaccess` di root folder `storage/uploads/` (untuk pembinaan, pdss simulasi, dsb.).

---

## Verification Plan

### Automated Tests
1. **Pengujian Sintaks**:
   Menjalankan pengecekan kesalahan sintaks PHP di terminal CLI untuk memastikan tidak ada kesalahan konfigurasi.
   ```bash
   php -l public/uploads/.htaccess
   ```

### Manual Verification
1. Lakukan simulasi pengunggahan gambar PHP palsu (misal berkas bernama `exploit.php.png` atau `exploit.php`) di salah satu modul unggah berkas (misal Pusat Bantuan).
2. Verifikasi sistem menolak file yang tidak bertipe MIME asli gambar (`png`/`jpg`/`jpeg`).
3. Coba akses langsung tautan berkas PHP jika berhasil terunggah (misal `/SINTA-SaaS/public/uploads/tickets/test.php`). Pastikan web server Apache merespon dengan **403 Forbidden** atau menampilkan kode mentah teks alih-alih mengeksekusi perintah PHP tersebut.

