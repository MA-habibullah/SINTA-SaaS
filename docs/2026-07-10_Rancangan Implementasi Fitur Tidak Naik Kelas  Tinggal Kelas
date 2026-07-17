# Rancangan Implementasi Fitur "Tidak Naik Kelas / Tinggal Kelas"

Penambahan fitur "Tinggal Kelas" (Retain) ini memungkinkan admin untuk menetapkan siswa yang tidak naik kelas ke dalam kelas yang sama, atau ke kelas lain namun masih pada jenjang (tingkat) yang sama. Riwayat dari proses ini akan disimpan secara rapi di dalam tabel riwayat historis (*Buku Induk*).

## User Review Required

> [!IMPORTANT]
> Mohon direview rancangan di bawah ini. Jika Anda menyetujuinya, klik tombol **Proceed** dan saya akan mengeksekusi langkah-langkah di bawah secara otomatis.

## Proposed Changes

---

### Database Layer (Migration)

#### [NEW] `database/migrations/2026_07_10_02_add_tinggal_kelas_enum.php`
- Membuat file migrasi baru untuk memperbarui tipe data ENUM pada kolom `jenis_aksi` di tabel `riwayat_kenaikan_kelas`.
- **Perubahan SQL**: `ALTER TABLE riwayat_kenaikan_kelas MODIFY COLUMN jenis_aksi enum('naik_kelas','lulus','tinggal_kelas','penempatan_awal') NOT NULL;`

---

### Backend (Model & Controller)

#### [MODIFY] `app/Models/Pengguna.php`
- Menambahkan metode baru `tinggalKelas(array $siswaIds, int $idKelasTujuan, string $tenantId, array $auditData)`.
- Mengubah *record* siswa ke kelas tujuan (bisa sama atau berbeda asal satu jenjang).
- Menyisipkan data (INSERT) ke dalam tabel `riwayat_kenaikan_kelas` dengan `jenis_aksi = 'tinggal_kelas'`.

#### [MODIFY] `app/Controllers/PenggunaController.php`
- Menambahkan *endpoint* API baru: `public function tinggalKelasApi(): void`.
- Memvalidasi input (sama seperti `naikkanKelasApi`).
- Memanggil *model* `tinggalKelas` dan merespon kembali ke tampilan (UI).

---

### Frontend (User Interface)

#### [MODIFY] `views/pengguna_index.php`
- **UI Tab Aksi Kolektif**:
  - Menambahkan *radio button* untuk **"Tinggal Kelas"** (`v-model="aksiMode" value="retain"`).
- **Logika "Kelas Tujuan"**:
  - Jika mode `promote` (Naik Kelas): Kelas asal akan disembunyikan dari pilihan kelas tujuan.
  - Jika mode `retain` (Tinggal Kelas): Kelas tujuan HANYA akan menampilkan kelas-kelas yang memiliki *jenjang yang sama* dengan kelas asal (termasuk kelas asalnya itu sendiri).
- **Javascript**:
  - Menambahkan metode `submitTinggalKelas()` pada instance Vue.js.
  - Menambahkan *endpoint* Axios `POST /api/v1/pengguna/aksi/tinggal-kelas`.

## Verification Plan

### Automated Tests
- Tidak ada skrip tes otomatis di environment ini.

### Manual Verification
1. **Database**: Menjalankan `/var/www/SINTA-SaaS/deploy.sh` untuk mengeksekusi migrasi tabel.
2. **UI Test**: Membuka halaman Pengguna -> Tab "Aksi Kolektif", memilih mode "Tinggal Kelas", lalu memastikan filter "Kelas Tujuan" memunculkan kelas yang se-jenjang.
3. **Fungsional**: Menyimpan aksi "Tinggal Kelas" untuk satu siswa, lalu mengecek histori di "Buku Induk Siswa" untuk memastikan histori terekam dengan tipe "Tinggal Kelas".
