# Rencana Implementasi: Filter Cohort Kelas 12, Mapel Kurikulum Terpetakan, & Opsi Bonus Kuota e-Rapor

Rencana ini merevisi modul **Pangkalan Data (PDSS)** untuk menyesuaikan dengan kebijakan kurikulum sekolah dan aturan kuota terbaru dari pemerintah.

## User Review Required

> [!IMPORTANT]
> 1. **Perubahan Filter Tahun Ajaran:** Sistem akan meloloskan siswa yang berada di kelas 12 saat ini jika tahun ajaran aktif dipilih, tanpa memandang tahun masuk kelas 10 mereka (menjaga agar siswa tidak naik kelas/mengulang tetap tampil). Untuk tahun ajaran lampau, siswa akan dicari berdasarkan riwayat nilai rapor Semester 5 mereka di tahun ajaran tersebut.
> 2. **Langkah 1 (Tentukan Mapel):** Daftar mata pelajaran yang tampil di Langkah 1 sekarang dibatasi secara ketat hanya pada mata pelajaran yang terpetakan di **Setting Kurikulum (`pemetaan_mapel`)** dari kelas 10 sampai kelas 12 untuk Tahun Ajaran Evaluasi terpilih. Mapel yang tidak digunakan tidak akan muncul di grid.
> 3. **Langkah 2 (e-Rapor Bonus Kuota):** Menambahkan checkbox opsi "Menggunakan e-Rapor (+5% Kuota SNBP)" yang secara dinamis meningkatkan kuota eligible (A: 40% -> 45%, B: 25% -> 30%, C: 5% -> 10%).

---

## Proposed Changes

### Backend: PDSS Controller

#### [MODIFY] [PDSSController.php](file:///c:/xampp/htdocs/SINTA-SaaS/app/Controllers/PDSSController.php)
* **`apiGetPdssMapels()`**:
  * Ubah query pengambilan `$allMapels` agar melakukan `JOIN` dengan tabel `pemetaan_mapel` dan `kelas` untuk menyaring mata pelajaran yang aktif dalam kurikulum kelas 10, 11, dan 12 pada tahun ajaran evaluasi terpilih:
    ```sql
    SELECT DISTINCT mp.id, mp.kode_mapel, mp.nama_mapel 
    FROM mata_pelajaran mp
    JOIN pemetaan_mapel pm ON mp.id = pm.mapel_id
    JOIN kelas k ON pm.kelas_id = k.id
    WHERE mp.tenant_id = ? 
      AND mp.is_active = 1 
      AND mp.deleted_at IS NULL
      AND pm.tahun_ajaran = ?
      AND (k.nama_kelas LIKE '%10%' OR k.nama_kelas LIKE '%X%' OR k.nama_kelas LIKE '%11%' OR k.nama_kelas LIKE '%XI%' OR k.nama_kelas LIKE '%12%' OR k.nama_kelas LIKE '%XII%')
    ORDER BY mp.nama_mapel ASC
    ```
* **`apiDownloadLeger()`**:
  * Perbarui query `$stmtSelected` agar menyaring mapel terkonfigurasi PDSS yang juga terpetakan di `pemetaan_mapel` pada tahun ajaran terpilih untuk memastikan kolom mapel di Excel steril dari mapel yang tidak digunakan di Buku Induk / kurikulum.

---

### Frontend: PDSS View

#### [MODIFY] [pdss_index.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/pdss_index.php)
* **Tambah State Baru:**
  * Tambahkan property `useERapor: false` di Vue data.
* **UI Checkbox di Langkah 2:**
  * Tempatkan checkbox di samping select kuota untuk mengaktifkan opsi e-Rapor:
    ```html
    <label class="flex items-center gap-1.5 text-xs font-semibold text-slate-600 cursor-pointer ml-3 select-none">
        <input type="checkbox" v-model="useERapor" :disabled="locks[2].is_locked" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
        Menggunakan e-Rapor (+5% Kuota SNBP)
    </label>
    ```
* **Update Opsi Dropdown Kuota:**
  * Tambahkan opsi kuota bonus (45%, 30%, 10%) ke dalam dropdown `quota-select`.
* **Watcher Vue:**
  * Buat watcher pada `useERapor` untuk otomatis menyesuaikan `quotaPercent` (A: 45%, B: 30%, C: 10%) ketika di-check/uncheck selama data kuota belum dikunci.

---

## Verification Plan

### Automated Tests
* Jalankan syntax check untuk memastikan tidak ada kesalahan kompilasi:
  ```powershell
  php -l app/Controllers/PDSSController.php views/pdss_index.php
  ```

### Manual Verification
1. Buka halaman PDSS, pilih Tahun Ajaran Evaluasi (misal 2025/2026).
2. Verifikasi Langkah 1 hanya menampilkan mapel yang terdaftar dalam Setting Kurikulum tahun ajaran tersebut.
3. Buka Langkah 2, centang checkbox "Menggunakan e-Rapor". Verifikasi Kuota SNBP otomatis bertambah 5% sesuai akreditasi sekolah.
4. Klik tombol Unduh Leger Nilai Semester 1. Buka file Excel yang diunduh dan verifikasi kolom-kolom mapel bersih dari mapel yang tidak digunakan.
