---
## Pengamanan Halaman Dashboard & Proteksi Cetak Buku Induk (OTT)
**Waktu**: 15:05 WIB
**Jenis**: Security Hardening

**Deskripsi pekerjaan:**
1. **Dashboard Zero Data Leakage:** Menghapus rendering data database statis dari file dashboard view. Seluruh data dashboard (statistik, profil tenant, pengumuman, siswa/GTK) kini dimuat dinamis via AJAX fetch menggunakan Vue 3 & Axios saat ter-mount di browser client, mengamankan data dari Ctrl+U.
2. **One-Time Token Cetak:** Menerapkan otentikasi token jangka pendek (60 detik) untuk setiap akses URL cetak (`printRapot`, `printBukuInduk`, `printRapotSemester`, dll.). Token tersebut langsung dimusnahkan (`unset`) dari session server seketika setelah divalidasi pertama kali untuk mencegah replay attack.
3. **Penyuntingan Client Cetak:** Memodifikasi javascript client cetak agar meminta token cetak terlebih dahulu via API `/api/v1/cetak/request-token` sebelum membuka dialog print preview.

---
## Perbaikan Bug 400 Bad Request pada Modul BK Akademik
**Waktu**: 15:21 WIB
**Jenis**: Bug Fix

**Deskripsi pekerjaan:**
1. **Perbaikan Fallback Operator $tenantId**: Mengganti inisialisasi variabel `$tenantId` di bagian atas file `pdss_index.php` agar dapat mengambil data fallback dari parent layout (yaitu dari `akademik_layout.php`), mencegah overwriting data tenant_id menjadi kosong ketika file ini di-include.
2. **Penanganan Balapan Data (Race Condition)**: Menambahkan guard check di method `fetchPdssMapels()`, `fetchSimulasiSettings()`, dan `fetchSimulasi()`. Mengatur urutan method di `refreshAll()` agar `fetchKesiapan()` diselesaikan terlebih dahulu (await) sebelum sisa API paralel dipanggil, mencegah pengiriman parameter `tahun_ajaran_id=` kosong ke server.

---
## Perbaikan Pemetaan Cohort Siswa Kelas 12 PDSS
**Waktu**: 15:33 WIB
**Jenis**: Bug Fix

**Deskripsi pekerjaan:**
1. **Perbaikan Subquery Semester 5**: Mengubah filter subquery `dnr.semester = 5` menjadi `dnr.semester = 'Ganjil' AND kelas_dnr.nama_kelas LIKE '%12%'` karena data semester di database bertipe string ('Ganjil'/'Genap').
2. **Logika Pemetaan Adaptif Tahun Ajaran**: Menambahkan pencocokan ganda pada logic query SQL (`CONCAT` angkatan masuk OR pencocokan langsung `ta.tahun_ajaran`) agar siswa `Dummy Cohort` yang tahun ajaran aktifnya langsung diset ke `2024/2025` terpetakan dengan tepat di tahun ajaran `2024/2025` dan tidak meloncat ke `2026/2027`. File yang diubah: `app/Controllers/PDSSController.php`.

---
## Perbaikan Kelayakan Simulasi, Error Toggle Setting & Ekspor XLSX
**Waktu**: 15:47 WIB
**Jenis**: Bug Fix & Feature

**Deskripsi pekerjaan:**
1. **Perbaikan Hitungan Rata-rata Nilai Rapor**: Mengubah kalkulasi rata-rata nilai rapor 5 semester di `apiGetSimulasi()` agar menggunakan pemetaan dinamis `getSemesterLevel()` di PHP (seperti di Kesiapan), menyelesaikan kegagalan query `dnr.semester IN (1,2,3,4,5)` yang mengembalikan rata-rata 0.
2. **Sinkronisasi Quota Map**: Menambahkan filter Tahun Ajaran evaluasi saat ini ke dalam query `$stmtQuota` pada API Simulasi.
3. **Perbaikan Error 500 Toggle Setting**: Menambahkan method static `getUserNama()` di `app/Core/SessionManager.php` untuk menghindari error `Call to undefined method` ketika menyimpan data log pembukaan/penutupan simulasi.
4. **Format Ekspor Spreadsheet Modern (XLSX)**: Mengubah format output file ekspor simulasi pada `apiExportSimulasi()` di `app/Controllers/PDSSController.php` dari format file CSV menjadi format berkas spreadsheet (.xlsx) menggunakan `\Shuchkin\SimpleXLSXGen`.
5. **Pencatatan Analisis Log**: Menambahkan rangkuman root cause analysis di akhir file `scratch/00 error, log, console, inject, dll.txt`.
