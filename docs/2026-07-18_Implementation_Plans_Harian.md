---
## Zero Data Leakage Dashboard & Solusi Cetak Aman
**Waktu**: 15:00 WIB
**Status**: Dieksekusi

---
## Perbaikan Modul BK Akademik & PDSS (Error 400 Bad Request)
**Waktu**: 15:12 WIB
**Status**: Dieksekusi
**Deskripsi**: Mengatasi error 400 Bad Request akibat over-write variabel tenant_id dan balapan inisialisasi tahun_ajaran_id pada vue component saat memuat data simulasi pdss di halaman bk/akademik. File yang akan diubah: views/pdss_index.php.

---
## Perbaikan Pemetaan Cohort Siswa Kelas 12 PDSS (Tahun Ajaran 2024/2025)
**Waktu**: 15:27 WIB
**Status**: Dieksekusi
**Deskripsi**: Menyempurnakan logika SQL query pencarian siswa cohort kelas 12 agar adaptif terhadap inkonsistensi id_tahun_ajaran (tahun masuk awal vs tahun aktif kelas 12) serta memperbaiki filter semester 5 (dari 5 menjadi 'Ganjil' kelas 12). File yang akan diubah: app/Controllers/PDSSController.php.

---
## Perbaikan Kelayakan & Rata-rata Nilai Rapor Halaman Simulasi PDSS
**Waktu**: 15:42 WIB
**Status**: Dieksekusi
**Deskripsi**: Membenahi error logic penentuan eligible siswa di apiGetSimulasi akibat query dnr.semester IN (1,2,3,4,5) yang menghasilkan rata-rata 0 serta mengoreksi hitungan quota map agar terfilter berdasarkan tahun ajaran. File yang akan diubah: app/Controllers/PDSSController.php.
