# Rencana Implementasi: Seeder Data Cohort Siswa PDSS Lengkap (Kelas 10 s.d 12)

## Latar Belakang

Untuk menguji fitur **Simulasi Pemilihan Kampus & Prodi PDSS** serta **Perhitungan Ranking Paralel**, dibutuhkan data dummy yang realistis dan lengkap. Pihak sekolah memerlukan data cohort siswa dari mulai masuk Kelas 10 hingga naik Kelas 12, lengkap dengan pemetaan mata pelajaran kurikulum dan nilai rapor 5 semester.

Rencana ini merancang script seeder khusus (`scratch/seed_student_cohort_data.php`) yang akan mengisi database lokal dengan data 3 kelas baru masing-masing berisi 10 siswa yang terorganisir dengan rapi.

---

## Proposed Changes

### 1. Script Seeder Dummy Data

#### [NEW] [seed_student_cohort_data.php](file:///c:/xampp/htdocs/SINTA-SaaS/scratch/seed_student_cohort_data.php)

Script seeder ini akan melakukan hal-hal berikut secara transaksional (ACID):

1. **Inisialisasi Sekolah (Tenant):**
   - Menggunakan tenant aktif `e33ddee4-3c36-4bbe-b4b8-6b4fc482dc46` (SMA NEGERI 11 SURABAYA).

2. **Inisialisasi Jurusan & Kelas Baru:**
   - Membuat/memastikan Jurusan: `MIPA` (Matematika dan Ilmu Pengetahuan Alam) dan `IPS` (Ilmu Pengetahuan Sosial).
   - Membuat 9 Kelas baru:
     - **Kelas 10 (TA 2022/2023):** `10 MIPA 1`, `10 MIPA 2`, `10 IPS 1`
     - **Kelas 11 (TA 2023/2024):** `11 MIPA 1`, `11 MIPA 2`, `11 IPS 1`
     - **Kelas 12 (TA 2024/2025):** `12 MIPA 1`, `12 MIPA 2`, `12 IPS 1`

3. **Inisialisasi Mata Pelajaran & Pemetaan Kurikulum:**
   - Memastikan ada mata pelajaran inti untuk PDSS:
     - **MIPA:** `Matematika`, `Fisika`, `Kimia`, `Biologi`, `Bahasa Indonesia`, `Bahasa Inggris`.
     - **IPS:** `Matematika`, `Ekonomi`, `Geografi`, `Sosiologi`, `Bahasa Indonesia`, `Bahasa Inggris`.
   - Mengisi tabel `pemetaan_mapel` untuk setiap kelas di tahun ajaran masing-masing.
   - Mengisi tabel `pdss_config_mapel` untuk Tahun Ajaran `2024/2025` (Semesters 1 s.d 5 dicentang).

4. **Pembuatan Siswa Cohort (30 Siswa):**
   - Membuat 10 siswa per kelas untuk kelas `12 MIPA 1`, `12 MIPA 2`, dan `12 IPS 1` (Total 30 siswa, status `'Aktif'`, Entry Year = `2022/2023` / ID `5`).
   - Mengisi log mutasi di `riwayat_kenaikan_kelas` untuk mencatat alur kenaikan kelas siswa:
     - `penempatan_awal` di Kelas 10 (TA `2022/2023`)
     - `naik_kelas` ke Kelas 11 (TA `2023/2024`)
     - `naik_kelas` ke Kelas 12 (TA `2024/2025`)

5. **Pengisian Nilai Rapor (Semester 1 s.d 5):**
   - Mengisi nilai (`nilai_akhir`) di `detail_nilai_rapor` untuk ke-30 siswa tersebut.
   - Nilai di-generate secara acak namun realistis (berkisar antara **75.00** s.d **98.00**) agar perhitungan rata-rata dan peringkat paralel di UI SINTA dapat menghasilkan data yang dinamis dan terurut sempurna.

---

## Verification Plan

### Run Seeder
```powershell
php scratch/seed_student_cohort_data.php
```

### Manual Verification
1. Login ke panel BK SMA Negeri 11 Surabaya.
2. Buka menu **Pangkalan Data (PDSS)**.
3. Ubah filter **Tahun Ajaran Evaluasi** ke `2024/2025`.
4. Pastikan data 30 siswa baru muncul di **Langkah 3 (Tinjau Kelayakan & Ranking Paralel)** dengan rata-rata nilai dan peringkat eligible dari #1 s.d #10 per jurusan.
5. Verifikasi bahwa mapel PDSS terisi dengan benar di **Langkah 1**.
6. Buka tab **Simulasi Pilihan Kampus**, coba masukkan simulasi pilihan prodi untuk beberapa siswa baru, dan pastikan deteksi konflik serta badge `⚠ Konflik` muncul secara benar jika siswa berperingkat lebih rendah memilih prodi yang sama.
