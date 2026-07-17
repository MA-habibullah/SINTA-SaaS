# Penambahan Fitur Lampiran Bukti dengan Kompresi Otomatis

Kita akan menambahkan fitur bagi Kepala Sekolah atau atasan untuk mengunggah bukti (foto/dokumen) saat melaporkan kasus pelanggaran guru. Foto yang diunggah akan otomatis dikompresi di bawah 500 KB tanpa merusak kejelasan gambar, sehingga menghemat kapasitas penyimpanan *server*.

> [!NOTE]
> Semua *file* gambar (JPEG, PNG, JPG) akan dikompres menggunakan PHP GD Library bawaan dengan metode *rescaling* proporsional dan kompresi tingkat lanjut (Quality 70-80). Untuk dokumen selain gambar (misal PDF), akan diunggah normal tanpa modifikasi.

## Open Questions

> [!IMPORTANT]
> 1. Apakah unggahan ini bersifat wajib (Required) atau opsional saja untuk saat ini? Saya akan mengatur sebagai **opsional** jika tidak ada instruksi tambahan.
> 2. Format file yang diizinkan akan saya batasi pada: `jpg, jpeg, png, pdf, doc, docx`. Apakah Anda ingin menambahkan format lain?

## Proposed Changes

---

### Database Migration

Akan dibuat skrip migrasi resmi baru untuk menambahkan struktur kolom di *database*.

#### [NEW] [2026_07_02_05_add_lampiran_to_monitoring.php](file:///c:/xampp/htdocs/SINTA-SaaS/database/migrations/2026_07_02_05_add_lampiran_to_monitoring.php)
- Membuat skrip `ALTER TABLE guru_monitoring ADD COLUMN lampiran_bukti VARCHAR(255) NULL AFTER deskripsi_kasus`.

---

### Backend Controller (Upload & Compression Logic)

Menangani pemrosesan pengunggahan (*upload*) data dengan algoritma kompresi otomatis.

#### [MODIFY] [PembinaanController.php](file:///c:/xampp/htdocs/SINTA-SaaS/app/Controllers/PembinaanController.php)
- Modifikasi fungsi `store()` untuk menangani file terkirim `$_FILES['lampiran']`.
- Menulis logika *PHP Image Compression*:
  - Mengecek ekstensi gambar.
  - Membatasi dimensi maksimum (misal 1280px lebar).
  - Menyimpan gambar dengan resolusi yang padat tapi *size* di bawah 500 KB menggunakan `imagejpeg()`.
- Membuat direktori otomatis di `storage/uploads/pembinaan/`.
- Memasukkan nama/path *file* ke dalam eksekusi `INSERT` *database*.

---

### Frontend & UI

Menambahkan komponen antarmuka (*UI*) untuk memuat tombol *upload* dan tombol pratinjau (*preview*) dokumen.

#### [MODIFY] [pembinaan_index.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/sekolah/pembinaan_index.php)
- Menambahkan `enctype="multipart/form-data"` pada tag `<form id="addKasusModal">`.
- Menambahkan input khusus *file upload* dengan desain *Tailwind/Bootstrap*.
- Memodifikasi tabel "Kasus Aktif" dan "Riwayat" untuk menampilkan ikon Lampiran (Bisa di-klik untuk membuka tab baru melihat file bukti tersebut).

## Verification Plan

### Manual Verification
1. Saya akan menjalankan perintah `php migrate.php` untuk memasang kolom baru.
2. Saya akan mensimulasikan unggahan dan memeriksa skrip *backend*.
3. Anda dapat melakukan *testing* dengan mengunggah foto berukuran besar (>2 MB) lalu melihat file hasilnya, apakah ukurannya mengecil di bawah 500 KB tanpa menjadi buram.
