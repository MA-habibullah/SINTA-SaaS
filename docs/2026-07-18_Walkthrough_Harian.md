---
## Pengamanan Halaman Dashboard & Proteksi Cetak Buku Induk (OTT)
**Waktu**: 15:05 WIB
**Jenis**: Security Hardening

**Deskripsi pekerjaan:**
1. **Dashboard Zero Data Leakage:** Menghapus rendering data database statis dari file dashboard view. Seluruh data dashboard (statistik, profil tenant, pengumuman, siswa/GTK) kini dimuat dinamis via AJAX fetch menggunakan Vue 3 & Axios saat ter-mount di browser client, mengamankan data dari Ctrl+U.
2. **One-Time Token Cetak:** Menerapkan otentikasi token jangka pendek (60 detik) untuk setiap akses URL cetak (`printRapot`, `printBukuInduk`, `printRapotSemester`, dll.). Token tersebut langsung dimusnahkan (`unset`) dari session server seketika setelah divalidasi pertama kali untuk mencegah replay attack.
3. **Penyuntingan Client Cetak:** Memodifikasi javascript client cetak agar meminta token cetak terlebih dahulu via API `/api/v1/cetak/request-token` sebelum membuka dialog print preview.
