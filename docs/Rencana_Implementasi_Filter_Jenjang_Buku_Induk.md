# Rencana Implementasi: Filter Jenjang pada Buku Induk Siswa

Rencana ini bertujuan untuk menambahkan filter **Jenjang** sebelum filter **Kelas** pada 4 tab utama di modul Buku Induk Siswa:
1. **Buku Induk Siswa**
2. **Seting Kurikulum**
3. **Input Nilai Rapor**
4. **Cetak Buku Induk**

Ketika Jenjang dipilih, opsi dropdown Kelas hanya akan menampilkan kelas-kelas yang berada di bawah Jenjang tersebut.

---

## Proposed Changes

### 1. Backend Controllers

#### [MODIFY] [BukuIndukController.php](file:///c:/xampp/htdocs/SINTA-SaaS/app/Controllers/BukuIndukController.php)
* Mengubah query penarikan data kelas pada method `index()` agar memuat kolom `id_jenjang`.
* Menambahkan query penarikan daftar `jenjang` aktif milik tenant dari database.
* Mengirimkan data `$jenjangList` ke view `buku_induk`.

#### [MODIFY] [KurikulumController.php](file:///c:/xampp/htdocs/SINTA-SaaS/app/Controllers/KurikulumController.php)
* Mengubah query penarikan kelas pada method `index()` (GET `/api/v1/kurikulum`) agar memuat kolom `id_jenjang`.
* Mengambil data `jenjang` aktif milik tenant dari database dan menambahkannya ke response payload JSON.

---

### 2. Frontend View

#### [MODIFY] [buku_induk.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/buku_induk.php)
* **Data State**:
  * Menambahkan properti state baru di Vue `data()`:
    * `jenjangOptions` (diisi dari inisialisasi awal PHP)
    * `filterJenjang` (model filter jenjang untuk Tab Buku Induk & Cetak Buku Induk)
    * `kurikulum.jenjangId` (model filter jenjang untuk Tab Seting Kurikulum)
    * `nilaiRapor.jenjangId` (model filter jenjang untuk Tab Input Nilai Rapor)
* **API Fetch Integration**:
  * Memperbarui `fetchKurikulumMaster()` dan `fetchNilaiRaporMaster()` agar memetakan data `jenjang` dari response payload ke `jenjangOptions` secara reaktif saat tenant berganti.
* **Computed Filtering**:
  * Membuat computed properties berikut untuk menyaring kelas berdasarkan jenjang:
    * `filteredKelasOptions`: Menyaring `kelasOptions` berdasarkan `filterJenjang`.
    * `filteredKurikulumKelas`: Menyaring `masterKurikulum.kelas` berdasarkan `kurikulum.jenjangId`.
    * `filteredNilaiRaporKelas`: Menyaring `masterNilaiRapor.kelas` (atau `masterKurikulum.kelas` yang dipetakan) berdasarkan `nilaiRapor.jenjangId`.
* **State Watchers**:
  * Menambahkan watcher untuk `filterJenjang`, `kurikulum.jenjangId`, dan `nilaiRapor.jenjangId` agar mereset model pilihan kelas (`filterKelas`, `kurikulum.kelasId`, `nilaiRapor.kelasId`) menjadi kosong `""` saat jenjang diganti.
* **UI Layout Updates**:
  * **Tab 1 (Buku Induk Siswa)**: Menambahkan elemen `<select>` Jenjang di depan filter Kelas dan menyesuaikan `col-md` layout kolom agar pas.
  * **Tab 2 (Seting Kurikulum)**: Menambahkan elemen `<select>` Jenjang di depan dropdown kelas fisik.
  * **Tab 3 (Input Nilai Rapor)**: Menambahkan elemen `<select>` Jenjang di depan dropdown kelas fisik.
  * **Tab 4 (Cetak Buku Induk)**: Menambahkan elemen `<select>` Jenjang di depan dropdown filter kelas.

---

## Verification Plan

### Automated/Manual Verification
1. **Verifikasi Tab 1 (Buku Induk Siswa)**:
   * Pilih Sekolah (jika Super Admin).
   * Verifikasi filter Jenjang terisi opsi jenjang (misal: 10, 11, 12).
   * Pilih Jenjang 10, pastikan filter Kelas hanya menampilkan kelas 10 (misal: 10 MIPA 1, 10 IPS 1) dan secara otomatis memicu refresh data pencarian.
   * Ganti Jenjang ke 11, pastikan pilihan kelas sebelumnya ter-reset ke "Semua Kelas" dan hanya memunculkan kelas tingkat 11.
2. **Verifikasi Tab 2 (Seting Kurikulum)**:
   * Pilih Jenjang, pastikan dropdown kelas fisik tersaring dengan benar.
3. **Verifikasi Tab 3 (Input Nilai Rapor)**:
   * Pilih Jenjang, pastikan dropdown kelas fisik tersaring dengan benar.
4. **Verifikasi Tab 4 (Cetak Buku Induk)**:
   * Pilih Jenjang, pastikan dropdown kelas fisik tersaring dengan benar.
