# Rencana Implementasi: Pembaruan Tampilan Pengumuman Dashboard & Fitur Pencarian Arsip

Berdasarkan permintaan Anda, saya telah memperbarui tampilan pengumuman di Dashboard dan mengoptimalkan fitur pencarian di halaman Arsip Pengumuman. Berikut rincian pekerjaannya:

## 1. Pembaruan Dashboard (Hanya Pengumuman Terbaru)
- **Tampilan:** Dashboard hanya akan menampilkan **1 pengumuman paling terbaru** untuk menjaga tampilan tetap ringkas dan responsif.
- **Tombol "Lihat Semua":** Saya telah menambahkan tombol "Lihat Semua Pengumuman" di bagian bawah atau atas blok pengumuman yang akan mengarahkan pengguna ke halaman Arsip Pengumuman secara spesifik.

## 2. Fitur Pencarian di Halaman Arsip Pengumuman
Di halaman Arsip (`/pengumuman/arsip`), saya telah menambahkan formulir pencarian canggih (Advanced Search Form) yang mencakup:
- **Pencarian Kata Kunci (Keyword):** Kolom teks untuk mencari berdasarkan judul pengumuman.
- **Filter Kategori:** Menu *dropdown* (pilihan) untuk menampilkan pengumuman berdasarkan Kategori tertentu (contoh: Akademik, Ekstrakurikuler).
- **Filter Tanggal:** Input kalender untuk mencari pengumuman pada bulan/tanggal tertentu.

## 3. Pembaruan Logika (Backend)
- **Model (`PengumumanModel.php`):** Menambahkan dukungan parameter Kategori dan Tanggal pada fungsi pengambilan data aktif.
- **Controller (`DashboardController.php`):** Menerima *input* (GET parameter) dari form pencarian Arsip, mengambil daftar Kategori, dan meneruskannya ke View.
- **View (`pengumuman_arsip_view.php`):** Mendesain ulang tampilan antar-muka formulir pencarian agar terlihat modern (*glassmorphism/clean design*) selaras dengan keseluruhan tema aplikasi, dan memperbaiki tautan pagination agar mempertahankan filter (kategori & tanggal).
