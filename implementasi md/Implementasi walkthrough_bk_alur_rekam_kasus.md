# Walkthrough: Pembaruan Alur Rekam Kasus BK (Pencarian Langsung & Form Dinamis)

Pembaruan ini mengimplementasikan alur pencarian siswa berbasis nama yang disederhanakan, di mana form detail kasus disembunyikan pada awalnya dan hanya akan muncul secara dinamis dengan transisi visual premium setelah siswa dipilih.

---

## 1. Konsep & Perubahan Antarmuka

**File:** [`master_bk.php`](file:///c:/xampp/htdocs/dapodik-spmb/views/master_bk.php)

### Alur Kerja (End-to-End)

```
[Kondisi Awal: Form Tersembunyi]
Guru/Admin BK hanya melihat panel input "Pencarian Siswa"
        │
        ▼ (Ketik "Budi")
[Autocomplete Suggestions]
Daftar saran menampilkan Nama Siswa dan Badge Kelas secara berdampingan:
┌────────────────────────────────────────────────────────┐
│ Budi Santoso                              [X IPA 1]    │
│ NISN: 1234567890 | NIS: 9988                           │
└────────────────────────────────────────────────────────┘
        │
        ▼ (Klik nama siswa)
[Siswa Terpilih (formKasus.id_siswa set)]
1. Panel pencarian nama digantikan oleh "Selected Student Profile Card"
2. Form detail rekam kasus BK muncul secara reaktif (Slide-Down / Fade-In)
3. Input Tanggal Pembuatan / Catat Kasus diisi otomatis dengan tanggal hari ini
        │
        ▼ (Mengisi detail kasus & klik "Rekam Kasus")
[apiStoreKasus()]
1. Ambil data snapshot siswa dari DB saat ini (Nama, NISN, NIS, Kelas)
2. Kunci snapshot tersebut ke tabel `catatan_bk` secara permanen (Historical Snapshot)
        │
        ▼ (Sukses & Refresh)
Tabel riwayat kasus terupdate secara asinkron. Detail siswa di-reset kembali ke [Kondisi Awal]
```

### Animasi & Transisi Visual
Ditambahkan class CSS `.animate-fade-in` dengan keyframes untuk memuluskan transisi pemunculan form detail rekam kasus:

```css
.animate-fade-in {
    animation: fadeIn 0.35s ease-out forwards;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
```

---

## 2. Struktur Visual Form Dinamis

Setelah nama siswa dipilih, form akan menyajikan data sebagai berikut:

```
┌─────────────────────────────────────────────────────────┐
│ 📝 Detail Rekam Kasus                                   │
├─────────────────────────────────────────────────────────┤
│ 👤 BUDI SANTOSO                           [IPA]         │
│ ┌────────────────────────┬────────────────────────────┐ │
│ │ KELAS (SNAPSHOT)       │ NISN                       │ │
│ │ 📷 X IPA 1             │ 📷 1234567890              │ │
│ └────────────────────────┴────────────────────────────┘ │
│ 🔄 Ganti Siswa (Reset)                                  │
├─────────────────────────────────────────────────────────┤
│ * Tanggal Pembuatan / Catat Kasus                        │
│   [ 2026-06-21 ]                                        │
│ * Jenis Kasus                                           │
│   [ Akademik / Perilaku / dll. ]                        │
│ * Catatan Konseling                                     │
│   [ Text area catatan konseling... ]                    │
│ * Tindak Lanjut & Keamanan (Rahasia / Status Kasus)      │
├─────────────────────────────────────────────────────────┤
│ [ Batal ]                          [ Rekam Kasus (Simpan) ]│
└─────────────────────────────────────────────────────────┘
```

* **NISN & Kelas** ditampilkan sebagai badge read-only yang jelas dan terstruktur.
* Tombol **"Ganti"** disediakan untuk membatalkan pilihan siswa aktif, yang akan mereset form ke panel pencarian awal dan menyembunyikan input detail kasus kembali.

---

## 3. Backend & Database (Tetap Kuat & Aman)

Mekanisme keamanan dan snapshot data historis tetap berjalan secara optimal di sisi backend:
* **Filter API (`status = 'Aktif'`)**: Kueri pencarian siswa di [BKController.php](file:///c:/xampp/htdocs/dapodik-spmb/app/Controllers/BKController.php) dibatasi ketat pada siswa yang berstatus aktif di dalam tenant sekolah guru yang bersangkutan.
* **Historical Snapshot**: Ketika data disimpan, backend melakukan query internal terhadap data siswa yang sedang terpilih, lalu mengunci nama lengkap, NISN, NIS, dan nama kelas saat kejadian ke dalam tabel `catatan_bk` (snapshot permanen).

---

## 4. Hasil Verifikasi

| Check | Status | Keterangan |
|---|---|---|
| `php -l master_bk.php` | ✅ PASS | Bebas dari syntax error |
| `php -l BKController.php` | ✅ PASS | Bebas dari syntax error |
| Unit Tests | ✅ PASS | Lulus 100% pada pengujian `test_advanced_features.php` |
