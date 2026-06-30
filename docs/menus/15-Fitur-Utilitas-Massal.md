# Dokumentasi Modul 15 - Fitur Utilitas Massal (Bulk Actions)

## 1. Pendahuluan
Selain operasi tunggal (satu-per-satu), SINTA-SaaS dilengkapi dengan modul khusus penanganan data berskala besar (*Bulk Actions*). Modul ini sangat berguna di awal tahun ajaran baru ketika sekolah harus memasukkan ribuan data siswa dan foto sekaligus.

## 2. Modul Import Data Excel (`ImportController.php`)
Modul ini bertindak sebagai mesin penerjemah (*Parser*) global yang mengubah lembar kerja Microsoft Excel (`.xlsx`) menjadi baris *database*.

### A. Alur Kerja Impor
1. **Unduh Template:** Pengguna (Admin) menekan tombol "Download Template". *Backend* menghasilkan *file* Excel kosong dengan *header* yang dikunci agar strukturnya tidak diubah-ubah oleh pengguna.
2. **Unggah File:** Pengguna mengunggah *file* yang sudah diisi. File diproses secara asinkron (atau dikirim ke *Background Jobs/Queue* jika datanya > 5000 baris).
3. **Validasi & Transaksi:** 
   - *Backend* membaca baris demi baris menggunakan pustaka `PhpSpreadsheet`.
   - Melakukan validasi relasional. Misalnya: Jika mengimpor Nilai Rapor, *backend* mengecek apakah NISN tersebut benar-benar ada di `tenant_id` bersangkutan.
   - Menggunakan mekanisme *Rollback* PDO jika satu sel saja bernilai *Error* (*Atomic Transaction*).

## 3. Modul Bulk Photo Upload (`BulkPhotoController.php`)
Modul ini merupakan *controller* terpisah untuk menangani unggahan foto profil siswa secara massal menggunakan format `.zip`.

### A. Alur Kerja (Workflow)
1. **Persiapan di Komputer Pengguna:**
   Operator sekolah menyiapkan ratusan foto. Foto-foto tersebut wajib diberi nama sesuai dengan **NISN** siswa (misalnya: `0051234567.jpg`).
2. **Zipping & Upload:** 
   Semua foto tersebut disatukan dalam 1 buah arsip `.zip`. Operator mengunggah file `.zip` tersebut ke menu *Bulk Photo*.
3. **Eksekusi Backend:**
   - *Backend* menerima file `.zip` dan memindahkannya ke *temporary directory* (`/storage/app/tmp`).
   - PHP mengekstrak `.zip` menggunakan modul `ZipArchive`.
   - Terjadi proses perulangan (*loop*): Untuk setiap *file* `.jpg/.png` yang diekstrak, *backend* menghapus ekstensi file, menganggap sisanya sebagai NISN, lalu mencari ID siswa berdasarkan NISN tersebut di tabel `siswa`.
   - Jika cocok, foto dilewatkan ke fungsi kompresi (mengurangi resolusi agar tidak lebih dari 200KB).
   - *File* hasil kompresi dipindahkan ke `/public/uploads/profiles/`, dan nama *file*-nya ditulis ke kolom `foto` di tabel `siswa`.
4. **Laporan (Feedback):**
   *Backend* mengembalikan JSON yang berisi laporan: "Berhasil: 250 foto. Gagal: 3 foto (NISN tidak ditemukan di database)".

## 4. Analisis Keamanan Ekstra
Kedua modul *Bulk Action* ini memiliki pengawasan keamanan tinggi:
- Limit ukuran *file upload* (`upload_max_filesize`) disesuaikan.
- Pencegahan Serangan *Zip Bomb*: Backend membatasi jumlah ekstrak maksimum dari dalam file `.zip` untuk mencegah *server crash*.
- Validasi *MIME type*: File disahkan berdasarkan MIME, bukan sekadar ekstensi (untuk menghindari *Webshell Upload* berkedok gambar).
