# Rencana Implementasi: Pencocokan Duplikat Berbasis Kode Prodi & Kampus

Dokumen ini menjelaskan rencana penyesuaian logika pencarian prodi ganda agar didasarkan secara eksklusif pada **Kode Prodi** dan **Kampus ID**. Hal ini menyelesaikan masalah apabila terdapat nama prodi yang sama di satu kampus tetapi memiliki jenjang yang berbeda, atau jalur kelas berbeda dengan kode unik masing-masing.

## Rencana Perubahan

### 1. Backend Controller

#### [MODIFY] [KampusController.php](file:///c:/xampp/htdocs/SINTA-SaaS/app/Controllers/KampusController.php)

1. **Fungsi `apiImportExcel`:**
   * Ubah kueri pencarian prodi ganda agar hanya memeriksa `kode_prodi` dan `kampus_id`:
     ```php
     $stmtProdi = $db->prepare("
         SELECT id FROM master_kampus_prodi 
         WHERE kode_prodi = ? AND kampus_id = ?
         LIMIT 1
     ");
     ```
   * Jika Kode Prodi di Excel tidak cocok dengan data apa pun di database untuk kampus tersebut, baris tersebut akan dibuat sebagai **Prodi Baru** (sekalipun nama prodinya sama, misalnya Teknik Informatika S1 vs D3, karena keduanya memiliki Kode Prodi berbeda).
   * Jika Kode Prodi cocok, sistem akan memperbarui kolom `program_studi`, `jenjang`, dan `jenis_portofolio` sesuai dengan data terbaru dari Excel.

2. **Fungsi `apiImportKampusProdi`:**
   * Ubah kueri pencarian prodi ganda agar hanya memeriksa `kode_prodi` dan `kampus_id`:
     ```php
     $stmtFindProdi = $db->prepare("
         SELECT p.id FROM master_kampus_prodi p
         WHERE p.kode_prodi = ? AND p.kampus_id = ?
         LIMIT 1
     ");
     ```

---

## Verifikasi

### Pengujian Manual
1. Pastikan mengunggah file Excel berisi dua program studi dengan nama sama (misal: "TEKNIK INFORMATIKA") tetapi dengan Kode Prodi berbeda (misal: `12345` untuk S1 dan `67890` untuk D3).
2. Verifikasi bahwa kedua prodi tersebut berhasil terupload dan tidak saling menimpa.
3. Unggah kembali file Excel dengan kode prodi yang sama tetapi dengan perubahan nama (misal: "TEKNIK INFORMATIKA REVISI"). Verifikasi nama ter-update dan tidak membuat baris baru.
