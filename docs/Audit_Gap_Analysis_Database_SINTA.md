# Analisis Kesenjangan (Gap Analysis) Fitur 3M

Berdasarkan rancangan Konsep Pendampingan Guru (3M) yang telah disepakati sebelumnya, berikut adalah status detail setiap tahapan beserta fitur apa saja yang **masih belum dibuat**:

## ✅ TAHAP 1: Deteksi & Input (Monitoring)
- **Status:** Sebagian besar Selesai (80%)
- **Sudah Dibuat:** Struktur tabel `guru_monitoring`, Dasbor _Traffic Light_ Utama (Merah, Kuning, Hijau), dan Formulir "Input Kasus Manual" untuk Kepala Sekolah.
- **BELUM Dibuat:** 
  - **Antarmuka (UI) Akun Guru:** Guru belum memiliki menu "Refleksi & Bimbingan" di dasbor mereka sendiri untuk melakukan "Pengajuan Mandiri" (Kasus Personal).

## ⏳ TAHAP 2: Sesi Pendampingan (Mentoring)
- **Status:** Database Selesai, Logika/UI Belum Dibuat (10%)
- **Sudah Dibuat:** Struktur tabel `sesi_mentoring`.
- **BELUM Dibuat:**
  - **Jadwalkan Sesi:** Tombol "Jadwalkan Sesi" di dasbor saat ini masih kosong (belum memunculkan pop-up jadwal pertemuan).
  - **Formulir Kolaborasi & TTD:** Layar khusus saat pertemuan (Coaching) berlangsung, tempat Kepala Sekolah mengetik "Fakta Masalah" dan "RTL".
  - **Kanvas Tanda Tangan Digital:** Fitur paling penting agar Guru dan Kepala Sekolah bisa menandatangani kesepakatan langsung di layar setelah sesi selesai.

## ⏳ TAHAP 3: Pemantauan (Masa Percobaan)
- **Status:** Database Selesai, Logika/UI Belum Dibuat (10%)
- **Sudah Dibuat:** Struktur tabel `evaluasi_pemantauan`.
- **BELUM Dibuat:**
  - **Form Evaluasi Akhir:** Tombol "Form Evaluasi" untuk kasus berstatus "Kuning" belum memicu tindakan apa pun. Harus dibuat logika di mana jika Kepala Sekolah memilih "Membaik", status guru otomatis berubah menjadi "Hijau" (Selesai).

## ❌ TAHAP 4: Laporan & Dokumen Akreditasi (Managing)
- **Status:** Belum Dibuat (0%)
- **BELUM Dibuat:**
  - **Cetak Laporan PDF:** Tombol "Laporan Akreditasi" di atas Dasbor belum terhubung dengan mesin pembuat PDF (seperti DomPDF atau mPDF) untuk mencetak dokumen resmi sebagai bukti fisik asesor.

---

### Saran Langkah Selanjutnya:
Tahap yang paling krusial untuk membuat sistem ini fungsional 100% adalah **Tahap 2 (Sesi Pendampingan & Tanda Tangan Digital)**. Jika Anda setuju, kita bisa mulai membuat antarmuka untuk penjadwalan dan kanvas tanda tangannya!
