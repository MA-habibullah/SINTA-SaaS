# Rencana Implementasi: Migrasi Menyeluruh Pemuatan Data Halaman ke Model AJAX Fetch Dinamis

Dokumen ini menjelaskan rencana perbaikan menyeluruh untuk menghentikan pencetakan data mentah (inline JSON dumps) dari PHP di dalam tag `<script>` pada seluruh berkas tampilan (*view files*) di aplikasi SINTA-SaaS. Kita akan memigrasikan semua pemuatan data tersebut ke model **AJAX Fetch secara dinamis via API**.

---

## Analisis & Tujuan Keamanan Menyeluruh

Saat ini, beberapa menu utama menyuntikkan data PHP secara langsung ke dalam JavaScript block menggunakan:
`const data = <?= json_encode($data) ?>;`

Hal ini menyebabkan:
1. **Kebocoran Sumber Halaman (View Source Leak)**: Data sensitif (seperti NIK, KK, alamat, email, log aktivitas, profil sekolah) dapat dibaca dalam bentuk teks biasa hanya dengan melakukan klik kanan -> **View Page Source (Ctrl+U)**.
2. **Kelemahan XSS (Cross-Site Scripting)**: Menulis data JSON mentah langsung di dalam blok tag `<script>` memiliki risiko XSS tinggi jika data tidak ter-escape secara sempurna.

### Solusi Komprehensif:
Kita akan meniadakan seluruh pencetakan data JSON mentah di dalam tag `<script>` pada 13 file view utama, dan menggantinya dengan pemanggilan AJAX terotorisasi pada saat inisialisasi Vue (`onMounted` / Vue lifecycle).

---

## Usulan Perubahan Global (Proposed Changes)

Kita akan menambahkan endpoint AJAX baru di masing-masing Controller dan mengubah cara inisialisasi variabel di View:

### 1. Modul Siswa (`tambah_siswa.php` & `SiswaController.php`)
* **Masalah:** Menyuntikkan seluruh profil siswa (NIK, KK, nama ibu, password) dan data kesehatan.
* **Solusi:** 
  * Di `SiswaController.php`, daftarkan action AJAX `get_siswa_detail` dan `get_siswa_draft`.
  * Di `tambah_siswa.php`, hilangkan variabel JSON inline dan panggil API secara asinkronus menggunakan Axios saat Vue dimuat.

### 2. Modul Dashboard (`dashboard_view.php` & `DashboardController.php`)
* **Masalah:** Menyuntikkan data `siswaList`, `gtkList`, `recentChanges`, dan `schoolInfo` di tag script.
* **Solusi:**
  * Di `DashboardController.php`, daftarkan action AJAX `get_dashboard_stats`.
  * Di `dashboard_view.php`, ambil seluruh statistik dashboard secara asinkronus setelah halaman dirender.

### 3. Modul Profil & Identitas Sekolah (`sekolah_profil.php`, `identitas_sekolah.php` & `SekolahController.php`)
* **Masalah:** Menyuntikkan konfigurasi profil sekolah `$tenant`.
* **Solusi:**
  * Di `SekolahController.php`, sediakan API `get_profile_detail`.
  * Di View, panggil API tersebut menggunakan Axios untuk mengisi form.

### 4. Modul Bimbingan Konseling (`master_bk.php`, `bk/master_kampus_prodi_layout.php` & `BKController.php`/`KampusController.php`)
* **Masalah:** Menyuntikkan data tahun ajaran list, prodi list, dan detail user.
* **Solusi:**
  * Migrasikan pemuatan tahun ajaran, program studi, dan data target ke AJAX endpoint terotorisasi.

### 5. Modul Agenda Terpadu (`agenda_terpadu.php` & `AgendaController.php`)
* **Masalah:** Menyuntikkan data `$events` dan `$ganttTasks`.
* **Solusi:**
  * Menggunakan pemuatan asinkronus bawaan FullCalendar (menggunakan URL sumber event) dan memuat tasks Gantt setelah halaman ter-mount.

### 6. Modul Buku Induk (`buku_induk.php` & `BukuIndukController.php`)
* **Masalah:** Menyuntikkan listTenants, jenjangOptions, dan kelasOptions.
* **Solusi:**
  * Mengambil opsi seleksi dropdown melalui pemanggilan AJAX secara dinamis.

### 7. Modul Log Aktivitas (`activity_logs.php` & `ActivityLogController.php`)
* **Masalah:** Menyuntikkan status admin log.
* **Solusi:**
  * Memuat log dan config awal secara dinamis.

---

## Contoh Pola Perubahan Kode (Pattern Example)

### Sebelum (Insecure & Leakable via View Source):
```html
<script>
    const schoolData = ref(<?= json_encode($tenant) ?>);
</script>
```

### Sesudah (Secure, Zero Plaintext Data in Page Source):
```html
<script>
    const schoolData = ref(null);
    
    onMounted(async () => {
        try {
            const response = await axios.get('/SINTA-SaaS/sekolah/profil?ajax=1&action=get_profile_detail');
            if (response.data && response.data.success) {
                schoolData.value = response.data.data;
            }
        } catch (err) {
            console.error("Gagal memuat profil sekolah:", err);
        }
    });
</script>
```

---

## Rencana Verifikasi (Verification Plan)

### Verifikasi Manual
1. **Inspeksi Kode Sumber (Page Source)**:
   * Buka seluruh menu aplikasi (Dashboard, BK, Siswa, Sekolah, Agenda, Buku Induk).
   * Klik kanan -> **View Page Source** (Ctrl+U).
   * Pastikan **TIDAK ADA LAGI** teks data sensitif (NIK, KK, nama siswa, email, detail sekolah) di dalam tag `<script>`.
2. **Keresponsifan Aplikasi**:
   * Pastikan semua tabel, grafik, kalender agenda, dan form edit tetap termuat dengan data lengkap tanpa adanya galat jaringan atau error Javascript di konsol pengembang (F12).
