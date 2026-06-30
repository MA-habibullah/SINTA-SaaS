# Dokumentasi Modul 04 - Buku Induk Siswa

## 1. Pendahuluan
Modul **Buku Induk Siswa** (`/buku-induk`) dirancang untuk menjadi gudang penyimpanan data pokok setiap siswa secara komprehensif, sesuai dengan regulasi pendataan Kemdikbudristek (Dapodik / PDSS). Akses ke modul ini terbatas untuk Operator Sekolah (Bisa mengubah seluruh data) dan Siswa (Hanya bisa membaca & mengelola datanya sendiri via profil).

## 2. Alur Kerja (Workflow)
1. **Pendaftaran (Entry Data):** Data induk masuk ke dalam tabel `siswa` melalui 3 cara: *Quick Add* dari Manajemen Pengguna, *Import Excel* massal, atau *Push* data dari sistem PMB.
2. **Penyajian Data List:** Operator sekolah melihat daftar siswa lengkap di antarmuka tabel interaktif.
3. **Penyuntingan Buku Induk:** Saat tombol "Detail / Edit" diklik, layar memuat *Tabbed Form* berukuran penuh. Pengguna menelusuri ratusan kolom isian yang telah dikelompokkan (Identitas, Ortu, Kontak, dll).
4. **Validasi (*Data Completeness*):** Setiap penyuntingan akan memicu metode kalkulasi kelengkapan. Jika *field* penting telah terisi, *progress bar* siswa meningkat.
5. **Pencetakan / Export:** Data siap diekspor untuk dilaporkan ke sistem provinsi atau pusat (Berupa *spreadsheet* rekapitulasi data induk lengkap).

## 3. Komponen Backend
### Controller: `App\Controllers\BukuIndukController.php`
- `index()`: Memanggil kerangka halaman UI utama.
- `fetchApi()`: Menangani filter rumit seperti pencarian berdasar Nama, NIK, NISN, atau bahkan filter berdasarkan status beasiswa/KIP.
- `detailApi($id)`: Menarik rekaman spesifik dari DB dan menyajikannya dalam format JSON yang siap dimuat ke dalam struktur form *front-end*.
- `saveApi()`: Memproses *submit* dari form buku induk.

### Model: `App\Models\Pengguna.php` (Fungsi Kelengkapan)
- Karena tabel buku induk menggunakan tabel yang sama dengan `siswa`, fungsi perhitungan probabilitas kelengkapan `fieldsToCheck` dijalankan di sisi model saat terjadi operasi baca (jika dihitung *on-the-fly*) atau *update* statis ke kolom `persentase_kelengkapan`.

## 4. Struktur Database (Schema)
Tabel utama adalah `siswa`, namun memiliki ekstensi ke tabel relasional lainnya:
1. `rincian_pelajar`: Menyimpan ukuran seragam, tinggi badan, berat badan, jarak tempuh ke sekolah.
2. `registrasi`: Menampung tanggal masuk, jenis pendaftaran (Siswa Baru / Pindahan), No. SKHUN, asal SMP.
3. `kip`: Menyimpan data spesifik Kartu Indonesia Pintar, No. KPS, Layak PIP beserta alasannya.

Saat kueri *fetch*, *backend* mengeksekusi `LEFT JOIN` dari tabel `siswa` ke `rincian_pelajar`, `registrasi`, dan `kip` menggunakan relasi `id_siswa = siswa.id`.

## 5. Komponen Frontend (View & UI)
- **View File:** `views/buku_induk/index.php` dan `views/buku_induk/detail_modal.php`.
- **Navigasi Form:** *Tab-nav* digunakan di dalam *modal* agar form sepanjang 50+ *input* tidak memberatkan layar *scroll*. Bagian dibagi menjadi:
  1. Identitas Utama (NISN, Nama, TTL)
  2. Data Ayah & Ibu (NIK, Pekerjaan, Gaji)
  3. Data Wali (Opsional)
  4. Kontak (Alamat, RT/RW, Dusun, Koordinat)
  5. Rincian Fisik & Periodik
  6. Beasiswa / KIP
- **Proteksi Input:** Terdapat validasi *JavaScript* untuk pembatasan jumlah digit NIK (16 karakter), format NISN (10 karakter), serta pembatasan tanggal lahir agar valid secara kalender.
