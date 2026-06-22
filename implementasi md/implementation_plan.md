# Rencana Implementasi: Halaman Tambah / Edit Siswa (Multi-step Wizard)

Rencana ini merinci langkah-langkah untuk membangun frontend halaman **Tambah / Edit Data Siswa** dengan antarmuka modern, responsif, dan berbasis **Multi-step Wizard** menggunakan Vue.js dan Bootstrap 5. Skema form memetakan kolom database secara 1:1 dari file `laravel.sql` untuk tabel: `siswa`, `rincian_pelajar`, `rincian_alamat`, `kontak`, `orang_tua`, `kip`, `registrasi`, dan `dokumen`.

---

## User Review Required

> [!IMPORTANT]
> - **Integrasi PHP & Vue 3**: Form akan di-render di dalam file PHP `views/tambah_siswa.php`. Vue 3 global build (disediakan di `master.php`) akan memanage data state formulir secara reaktif.
> - **Self-Contained Ajax API**: Untuk mempermudah dan menjaga modularitas tanpa mengubah file routing utama (`index.php`), dynamic dropdown (wilayah dan opsi akademik) akan dilayani secara instan oleh kueri internal di bagian atas `tambah_siswa.php` saat mendeteksi parameter query `?ajax=1`.
> - **Form Submission**: Form akan dikirimkan secara POST biasa (standard multipart form post) ke URL `actionUrl` (`/dapodik-spmb/siswa/simpan` atau `/dapodik-spmb/siswa/update`). Penggunaan `v-show` (bukan `v-if`) memastikan seluruh elemen input (termasuk yang berada di langkah yang tidak aktif) tetap berada di DOM dan terkirim seutuhnya ke server.

---

## Open Questions

> [!NOTE]
> Ada beberapa detail yang perlu diperhatikan:
> 1. **Data Penyimpanan Multitabel**: Kueri penyimpanan di `SiswaModel->create` dan `update` saat ini hanya mengarah ke tabel `siswa`. Untuk menyimpan rincian anak (pelajar, alamat, kontak, orang tua, kip, registrasi, dokumen), controller di backend harus membaca POST data dan memasukkannya ke masing-masing tabel menggunakan transaksi.
> 2. **Foto Profil dan Dokumen**: Upload file (berkas) akan dikirimkan via input file standar. Di sisi backend, berkas-berkas tersebut perlu divalidasi, di-upload ke direktori penyimpanan, dan namanya disimpan ke dalam tabel `dokumen` (serta `foto_profil` di `rincian_pelajar`).

---

## Proposed Changes

### Frontend View

#### [MODIFY] [tambah_siswa.php](file:///c:/xampp/htdocs/dapodik-spmb/views/tambah_siswa.php)
Implementasi ulang form tambah/edit siswa dengan fitur berikut:
1. **Sistem Wizard 5 Langkah**:
   - **Langkah 1**: Data Pokok & Akademik (tabel `siswa`)
   - **Langkah 2**: Alamat & Kontak (tabel `rincian_alamat` & `kontak`)
   - **Langkah 3**: Fisik & Kesejahteraan (tabel `rincian_pelajar` & `kip`)
   - **Langkah 4**: Data Orang Tua & Wali (tabel `orang_tua` - dipilah menjadi 3 sub-tab: Ayah, Ibu, Wali)
   - **Langkah 5**: Registrasi & Dokumen Upload (tabel `registrasi` & `dokumen` dengan area upload interaktif)
2. **Progress Bar & Indikator Step**: visualisasi premium di bagian atas form dengan penanda centang hijau untuk langkah yang berhasil dilalui.
3. **State Management Vue 3**:
   - Mendefinisikan object model `form` yang memetakan kolom-kolom database 1:1.
   - Pemuatan data lama (old input) atau data edit dari PHP secara aman ke dalam object Vue `form`.
   - Mengambil list Wilayah (Provinsi, Kota, Kecamatan, Kelurahan) secara dinamis menggunakan Axios/Fetch berdasarkan event pilihan user.
   - Mengambil data akademik (Angkatan, Tahun Ajaran, Jenjang, Jurusan, Kelas, Pendidikan) dari database.
4. **Validasi Klien Instan**:
   - Memvalidasi digit NIK (16 digit), No. KK (16 digit), NISN (10 digit), email, dan nomor HP secara reaktif sebelum pengguna bergeser ke langkah berikutnya.
   - Tombol "Kembali" (Back) dan "Lanjut" (Next) yang memvalidasi integritas input sebelum bergeser step.

---

## Verification Plan

### Manual Verification
1. **Akses Form**: Masuk ke `/dapodik-spmb/siswa/tambah` dan pastikan tampilan wizard muncul dengan layout grid 2-3 kolom per baris, berpenampilan premium (SaaS style putih & biru modern).
2. **Peralihan Step**: Coba isi field-field di Langkah 1. Verifikasi tombol "Lanjut" memblokir peralihan jika ada field wajib (seperti Nama Lengkap, Jenis Kelamin) kosong.
3. **Dropdown Chaining**:
   - Pilih Provinsi (misal: Jawa Barat) -> pastikan list Kota terisi secara otomatis.
   - Pilih Kota -> pastikan list Kecamatan terisi secara otomatis.
   - Pilih Kecamatan -> pastikan list Kelurahan terisi secara otomatis.
4. **Sub-Tabs Orang Tua**: Di Langkah 4, pastikan sub-tab "Ayah", "Ibu", dan "Wali" berpindah dengan mulus dan menyimpan state masing-masing input.
5. **Kondisional KIP**: Di Langkah 3, pilih "Punya KIP" -> pastikan input "Nomor KIP" muncul secara interaktif. Pilih "Layak KIP" -> pastikan input "Alasan Layak" muncul.
6. **Form Submission**: Klik "Simpan Data Siswa" di Langkah 5, pastikan semua data dikirimkan melalui payload request POST yang utuh ke server.
