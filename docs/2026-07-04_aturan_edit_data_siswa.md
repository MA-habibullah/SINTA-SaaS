# Aturan Program Edit & Tambah Data Siswa (SINTA-SaaS)

Dokumen ini menjelaskan aturan logika bisnis (*business logic*) dan sistem validasi yang diterapkan pada fitur **Edit Data Siswa** di SINTA-SaaS, yang dikelola melalui `SiswaController.php` dan `tambah_siswa.php`.

## 1. Hak Akses dan Kepemilikan (Roles & Permissions)
- **Siswa**: Hanya dapat mengedit profil dan data miliknya sendiri. Jika mencoba mengedit ID siswa lain, sistem akan memblokir dengan status `403 Forbidden`.
- **Siswa (Proteksi Data Inti)**: Siswa **tidak diizinkan** mengubah `nama_lengkap`, `nisn`, dan `nis`. Jika data ini dikirimkan, sistem akan mengabaikannya dan tetap menggunakan data asli yang ada di database.
- **Admin & Super Admin**: Dapat mengedit seluruh data milik siswa siapapun, termasuk mereset/mengubah *Password*. Admin juga memiliki akses eksklusif ke bagian **Status Keluar/Mutasi** (Step 5) yang disembunyikan dari siswa.

## 2. Penguncian Data (State Lock Gatekeeper)
- Apabila status seorang siswa di database sudah berubah menjadi **"Lulus"** atau **"Pindah"**, maka akun siswa tersebut akan **dikunci (Locked)**.
- Siswa bersangkutan tidak akan bisa lagi mengakses halaman edit maupun menyimpan perubahan. Sistem akan menampilkan peringatan: *"Data Telah Dikunci. Hubungi Admin Sekolah jika ada kesalahan data"*.
- *Catatan: Aturan penguncian ini hanya berlaku untuk _role_ siswa. Admin tetap bisa mengubah data siswa yang sudah lulus/pindah.*

## 3. Sistem Draft dan Simpan Sebagian (Partial Save)
- Formulir dibagi menjadi 5 tahapan (Step).
- **Frontend & Backend Toleransi**: Baik siswa maupun admin diperbolehkan menggunakan tombol **"Lanjut"** atau **"Simpan Step"** tanpa diwajibkan mengisi keseluruhan form.
- Pada saat mode *Update* (Edit) atau penyimpanan draf per langkah (*Step*), pengecekan *"Wajib Diisi"* (*Required*) ditiadakan. Hal ini mempermudah admin jika hanya ingin mengupdate *Password* atau 1 data kecil tanpa perlu merapikan data yang belum lengkap.

## 4. Validasi Format Data (Enforced Formatting)
Meskipun pengecekan "wajib diisi" dilonggarkan saat edit, format data **tetap divalidasi** jika datanya diisi:
- **Data Pokok (Step 1):** 
  - `NISN`: Harus 10 digit angka dan bersifat **Unik** secara global (tidak boleh sama dengan siswa di sekolah lain).
  - `NIS`: Maksimal 20 karakter dan **Unik** di dalam sekolah tersebut.
  - `Tanggal Lahir`: Harus berformat baku (`YYYY-MM-DD`).
  - `Password`: Jika diubah, minimal harus **6 karakter**.
- **Kontak & Alamat (Step 2):**
  - `RT / RW`: Berupa angka maksimal 3 digit.
  - `Kode Pos`: Harus tepat 5 digit angka.
  - `Email`: Harus sesuai format penulisan email yang valid (`@...`).
  - `No. HP Siswa / Orang Tua`: Harus berupa angka antara 8 hingga 13/15 digit.
- **Fisik & Riwayat (Step 3):**
  - `Tinggi Badan`: Minimal 30 cm.
  - `Berat Badan`: Minimal 5 kg.
  - `Lingkar Kepala`: Minimal 20 cm.
  - Nilai logika numerik anak ke- (>= 1), jumlah saudara (>= 0), dan jarak (>= 1 km) tidak boleh bernilai irasional.
- **Orang Tua & Wali (Step 4):**
  - `NIK (Ibu/Ayah/Wali)`: Jika diisi, harus valid persis **16 digit angka**.
  - `Tahun Lahir Ibu`: Terdapat pembatasan batas masuk akal yaitu antara tahun **1930 sampai 2020**.
- **Status & Mutasi (Step 5):**
  - **Khusus Admin**: Jika Admin merubah status siswa menjadi selain "Aktif" (misal: Lulus/Pindah/Dikeluarkan), maka `Alasan Keluar` dan `Tanggal Keluar` menjadi **wajib diisi**.
