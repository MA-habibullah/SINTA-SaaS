# Dokumentasi Modul 13 - Manajemen Kurikulum

## 1. Pendahuluan
Modul **Kurikulum** (`/api/v1/kurikulum`) adalah perpanjangan dari Master Data yang berfokus pada penjadwalan, bobot SKS (Sistem Kredit Semester) atau Jam Pelajaran (JP), dan pemetaan Mata Pelajaran ke masing-masing kelas.

## 2. Alur Kerja (Workflow)
1. **Pemetaan Mata Pelajaran (Mapping):** Waka Kurikulum (atau admin) masuk ke menu pemetaan. Mereka mengalokasikan daftar *Master Mapel* ke rombongan belajar (Kelas) spesifik pada Tahun Ajaran yang sedang berjalan.
2. **Penugasan Guru (Teaching Assignment):** Kurikulum mendikte Guru siapa yang berhak mengajar mata pelajaran tertentu di kelas tertentu.
   - Hal ini berdampak langsung pada Modul Rapor. Seorang guru hanya bisa memberikan nilai di grid rapor untuk kelas dan mapel yang telah ditugaskan kepadanya di sini.
3. **Copy Kurikulum (Duplikasi):** Mengingat kurikulum jarang berubah secara radikal setiap semester, sistem menyediakan tombol *Clone* atau *Copy*. Fitur ini menyalin susunan guru dan mapel dari *Tahun Ajaran/Semester* sebelumnya ke periode saat ini, menghemat ratusan jam kerja operator.

## 3. Komponen Backend
- **Controller Terkait:** `App\Controllers\KurikulumController.php` (Jika digabung, kadang berada di bawah `MasterDataController` atau `KelembagaanController`).
- **Logika Relasional:**
  - Tabel `kurikulum_mapel`: Bertindak sebagai *pivot* yang mempertemukan `kelas_id`, `mapel_id`, `guru_id`, dan `tahun_ajaran_id`.
  - Sistem memiliki proteksi *Unique Index* pada *database* agar satu mapel di kelas yang sama tidak diisi oleh 2 guru yang berbeda pada semester yang sama (mencegah konflik *input* nilai ganda).

## 4. Komponen Frontend
- **Drag and Drop / Grid Mapping:** Pemetaan seringkali disajikan dalam bentuk antarmuka visual agar Waka Kurikulum mudah memantau apakah ada jadwal atau penugasan yang bentrok.
- Fitur *Copy* dilengkapi *progress bar* asinkron untuk mencegah *Time-out* jika data pemetaan sangat masif.
