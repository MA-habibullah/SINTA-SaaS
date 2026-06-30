# Dokumentasi Modul 09 - Tracer Study

## 1. Pendahuluan
Modul **Tracer Study** dirancang sebagai alat pendataan bagi alumni. Sistem ini menjamin bahwa SINTA-SaaS memiliki siklus hidup yang lengkap: mulai dari Pendaftaran Siswa (PMB), Pembelajaran Aktif, hingga status Pasca Kelulusan, membantu meningkatkan poin akreditasi sekolah terkait daya serap alumni.

## 2. Alur Kerja (Workflow)
1. **Luluskan Siswa (Trigger Awal):** Admin menekan tombol "Luluskan" pada Modul Manajemen Pengguna.
   - Operasi ini memindahkan flag referensi kelas siswa, dan men- *switch* role-nya agar ia berubah menjadi Alumni.
2. **Kuesioner Alumni:** Mantan siswa (*Alumni*) akan terus memiliki akun SINTA-SaaS (kecuali di- *suspend*). Saat mereka *login*, sistem akan mendorong survei (*Tracer form*).
3. **Pendataan Kategorikal:** Alumni dipandu ke dalam alur logika terstruktur:
   - Apakah Kuliah? (Tarik basis data PTN/PTS se-Indonesia).
   - Apakah Bekerja? (Input jenis instansi / perusahaan).
   - Apakah Keduanya? / Wirausaha / Mencari Kerja?
4. **Analisis Laporan:** Pihak Sekolah (Admin / Kepala Sekolah) dapat mengunduh grafik agregat dan rincian tabulasi dari seluruh lulusan di suatu tahun kelulusan.

## 3. Komponen Backend
- Controller: Menangani Endpoint API `/api/v1/tracer/*`.
- *Controller* ini bekerja di atas dua tabel khusus:
  1. `alumni_study`: Menyimpan riwayat edukasi (*University*, *Faculty*, *Entry Year*).
  2. `alumni_work`: Menyimpan rekam jejak pekerjaan (*Company*, *Position*, *Salary Range*).
- **Relasi Database:** *Foreign Key* merujuk ke tabel `users` milik alumni tersebut, menggunakan konvensi `id_user`. Hal ini mencegah data *tracer* hilang manakala data primer `siswa` di- *archived*.

## 4. Komponen Frontend
- **Form Kuesioner (Wizard UI):** *Client-Side* menggunakan logika navigasi selangkah demi selangkah (*Step-by-step Wizard*). Alumni tidak ditodong dengan form panjang, melainkan ditanya satu persatu dengan animasi transisi yang mulus.
- **Validasi Sinkron (Live Form Check):** Ketika alumni memilih prodi kuliah, *Javascript* melakukan validasi untuk memastikan bahwa kolom teks tidak mengandung karakter tidak valid (*Regex Injection Filter*).
- **Grafik Chart.js:** Laporan daya serap bagi Kepala Sekolah dan Guru BK dimuat menggunakan pustaka *Chart.js*, menghasilkan grafik donat (Pie Chart) atau batang (Bar Chart) interaktif (bisa di- *hover* untuk melihat persen nilai agregat).
