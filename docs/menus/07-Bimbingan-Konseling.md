# Dokumentasi Modul 07 - Bimbingan Konseling (BK)

## 1. Pendahuluan
Modul **Bimbingan Konseling (BK)** merupakan sistem informasi spesifik di bawah kendali *Role* Guru BK. Modul ini menjadi repositori dari 3 pilar: Pelanggaran (Kedisiplinan), Prestasi (Capaian), dan Penjurusan (Akademik Lanjut).

## 2. Alur Kerja (Workflow) Utama
### A. Poin Pelanggaran & Tata Tertib
1. **Master Pelanggaran:** Guru BK mendaftarkan jenis pelanggaran beserta bobot pinaltinya (misal: "Merokok" -> 50 poin).
2. **Catat Kasus:** Guru BK menautkan `siswa_id` kepada pelanggaran tertentu di tanggal kejadian. *Backend* secara reaktif menjumlahkan total poin siswa tersebut.
3. **Pemberian Sanksi:** Jika siswa mencapai limit poin (misal: > 30 = SP 1, > 100 = Dikeluarkan), sistem merekomendasikan *Tindak Lanjut / Sanksi*. Rekaman sanksi disimpan di tabel `sanksi_bk`.

### B. Absensi Bulanan/Semester
Digunakan manakala sekolah tidak menggunakan alat *fingerprint* harian. BK bertugas memasukkan kompilasi absensi (Sakit, Izin, Alfa). Data ini kemudian ditembakkan (*merged*) otomatis saat Guru Kelas / Kurikulum akan mencetak Lembar Rapor Siswa. (Mencegah input ganda absensi di fitur rapor).

### C. Penjurusan & PDSS (Pangkalan Data Sekolah dan Siswa)
Modul untuk menyiapkan siswa eligible menuju SNBP / Seleksi Perguruan Tinggi.
- BK dapat menandai daftar Siswa yang telah masuk kuota Eligible (misal Top 40% terbaik).
- Terdapat tab khusus di profil Siswa yang mengizinkan mereka memetakan PTN dan Program Studi Impian mereka. Guru BK bisa meninjau apakah pemilihan prodi *overlap* dengan siswa lain (yang bisa menyebabkan persaingan internal) dan membimbing siswa secara tepat.

## 3. Komponen Backend
### Rantai Controller & Routing (`/api/v1/bk/*`)
Logika *Controller* sangat dimodulasi (terdapat `BkController`, `KasusController`, `PdssController`).
- Pengamanan ekstensif: Rute selalu divalidasi dengan `role = guru_bk` atau `operator_sekolah`. Jika `guru_biasa` menembak *endpoint* absensi BK, sistem membalas *HTTP 403 Forbidden*.
- **Poin Kalkulasi:** Saat API penambahan kasus dipanggil, *trigger* model menjalankan `SUM(poin)` di tabel kasus dan mem- *feedback* UI dengan total poin terkini dari si Anak tanpa perlu melakukan *full page reload*.

## 4. Komponen Frontend (View & UI)
- **Live Search & Auto-Complete:** Karena data siswa sangat masif (bisa >1000 anak), *input form* pencatatan kasus tidak menggunakan `<select>` statis, melainkan komponen *Searchable Dropdown* (AJAX). Saat Guru BK mengetikkan huruf awal nama, API merespon 5 kandidat teratas.
- **Tabel Peringatan Dini (Early Warning System):** *Dashboard* BK mengurutkan secara *descending* siswa dengan angka pinalti tertinggi. Warna baris berubah menjadi merah jika poin melewati batas kritis.
