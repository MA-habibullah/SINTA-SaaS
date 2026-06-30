# Dokumentasi Modul 08 - Manajemen Rapot

## 1. Pendahuluan
Modul **Manajemen Rapot** (`/cetak-rapot` dan `/cetak-rapot-kelas`) merepresentasikan muara dari *Learning Management System* dalam entitas sekolah. Modul ini menghimpun nilai murni kognitif/pengetahuan dari Guru Mapel, nilai *soft-skill* dari Ekstrakurikuler, dan absensi kepribadian dari Buku Induk atau BK, lalu meramunya ke dalam format *PDF* resmi.

## 2. Alur Kerja (Workflow)
1. **Input Nilai (Guru Mapel / Wali Kelas):** 
   Bisa dilakukan dengan 2 *stream* kerja:
   - A. **Impor Excel:** Mengunduh *template* *.xlsx*, diisi saat *offline*, lalu di- *upload* kembali.
   - B. **Input via Grid (Online):** Memanfaatkan antarmuka mirip Excel bawaan web.
2. **Kalkulasi Predikat:** Berbasis KKM (*Kriteria Ketuntasan Minimal*), sistem mengkalkulasi skor mentah (contoh: 85) menjadi Predikat (B+) dan merajut "Deskripsi Kemampuan" (contoh: "Sangat baik dalam menganalisa...").
3. **Generate Rapor:** Wali kelas atau Admin menekan tombol "Cetak PDF". *Backend* mengambil seluruh tabel terpisah (Tabel *siswa*, *nilai*, *ekskul*, *absensi*) melalui satu *Query SQL (JOIN)* besar berdasar filter `tenant_id`, `tahun_ajaran_id`, dan `semester`.
4. **Rendering Dokumen:** Web merender HTML berformat cetak. Fungsi peramban (*window.print*) atau pustaka pihak ketiga kemudian menangkap struktur DOM dan menerjemahkannya sebagai file PDF A4/F4.

## 3. Komponen Backend
- **Controller Inti:** `App\Controllers\RaporController.php`
- Modul ini menggunakan prinsip **EAV (Entity-Attribute-Value)** atau penyederhanaan pivot untuk menyimpan triliunan celah nilai (karena 1 Anak * 15 Mapel * 1 Rapor = 15 Baris *Database*, yang berarti eksponensial dalam skala besar).
- **Transaction Rollback:** Seluruh proses Impor Excel dibungkus dalam blok `try...catch` PDO. Jika terdapat 1 baris *corrupt* (misal: *String* di- *input* ke kolom Numerik), **semua** *insert* dibatalkan (*Rollback*), sehingga menghindari ketimpangan data *(Partial Writes)*.
- **Data Integrasi Eksternal:** Pada *method* cetak rapor, *controller* memanggil *helper query* ke modul Ekskul (`SELECT ... FROM nilai_ekskul WHERE siswa_id = ?`) dan disisipkan di blok Ekstrakurikuler secara terpisah.

## 4. Komponen Frontend
- **ag-Grid Implementation:** Modul Rapor menggunakan *library external Javascript* paling cepat dan canggih untuk memanipulasi baris/kolom. Guru dapat menggunakan navigasi *Keyboard* (Panah Atas/Bawah/Kiri/Kanan, Tab, dan Enter) sama persis dengan sensasi memencet tombol *keyboard* di Microsoft Excel. Fitur *Copy-Paste* massal dari Excel *Desktop* ke Web Grid juga di- *support* secara *native*.
- **Cetak Massal (Bulk Print):** Tombol di antarmuka mampu merender 40 lembar rapor sekaligus di latar belakang, menggabungkannya ke dalam satu elemen *Iframe* tersembunyi, lalu men-*trigger* mesin cetak *(Print Dialog)* tanpa *lag*.
