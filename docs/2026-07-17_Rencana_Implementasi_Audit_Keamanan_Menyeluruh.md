# Rencana Implementasi: Audit & Perbaikan Keamanan Menyeluruh (Anti-XSS & Pencegahan Kebocoran Data Krusial)

Dokumen ini menjelaskan rencana audit dan perbaikan menyeluruh terhadap celah keamanan injeksi data dari PHP ke JavaScript/HTML pada aplikasi SINTA-SaaS. Fokus utama adalah mencegah kebocoran data sensitif (hash password) dan celah Stored XSS di semua halaman.

---

## Analisis Celah Keamanan Global

Setelah dilakukan pemindaian kode di seluruh direktori `views/`, kami menemukan 13 file view yang menyuntikkan data PHP secara langsung menggunakan `json_encode`. Terdapat dua kategori celah keamanan:

### Kategori A: Injeksi JSON langsung ke dalam Tag `<script>` (Kerentanan XSS & Kebocoran Hash)
* **Masalah:** Menggunakan `json_encode()` tanpa flags default membiarkan karakter `<` dan `>` tidak tersandi. Jika data berisi string HTML penutup script (seperti `</script>`), browser akan menghentikan pembacaan script secara paksa dan mengeksekusi script baru setelahnya (Script Break XSS).
* **Solusi:** Menambahkan bendera `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT` pada fungsi `json_encode` PHP di dalam tag script untuk mengodekan karakter HTML ke bentuk unicode aman (misal `<` menjadi `\u003C`).
* **Pembersihan Data Sensitif:** Untuk data siswa (`$siswaFullData`, `$kesehatanData`, data draft/old), kita akan menghapus (`unset`) kolom `'password'` di PHP sebelum diekspor.

### Kategori B: Injeksi JSON ke dalam Atribut HTML (seperti `onclick='...'`)
* **Masalah:** Menggunakan `onclick='editAgenda(<?= json_encode($row) ?>)'` menggunakan kutip tunggal (`'`). Jika data JSON mengandung karakter kutip tunggal (`'`), HTML akan menganggap atribut `onclick` berakhir lebih cepat, memicu error JavaScript atau eksekusi perintah sewenang-wenang (Attribute Break XSS).
* **Solusi:** Membungkus hasil `json_encode` dengan fungsi PHP `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` agar semua kutip dan karakter HTML disandikan secara aman dalam konteks atribut HTML.

---

## Usulan Perubahan (Proposed Changes)

Berikut adalah daftar file yang akan diperbaiki berdasarkan kategorinya:

### 1. Kategori A: Proteksi Injeksi `<script>` & Pembersihan Hash Password

#### [MODIFY] [tambah_siswa.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/tambah_siswa.php)
* **Pembersihan Hash (PHP):**
  ```php
  $siswaFullData = $isEdit ? $data['siswa'] : [];
  if ($isEdit && isset($siswaFullData['password'])) {
      unset($siswaFullData['password']);
  }
  if (isset($data['draft']['password'])) {
      unset($data['draft']['password']);
  }
  if (isset($data['old']['password'])) {
      unset($data['old']['password']);
  }
  ```
* **Encoding Keamanan (JS):**
  * Ubah `json_encode($siswaFullData)` menjadi `json_encode($siswaFullData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)`
  * Ubah `json_encode($kesehatanData)` menjadi `json_encode($kesehatanData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)`
  * Ubah data draft & old error list agar menggunakan bendera hex yang sama.

#### [MODIFY] [sekolah_profil.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/sekolah_profil.php)
* Ubah `json_encode($tenant, ...)` menjadi `json_encode($tenant, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)`

#### [MODIFY] [identitas_sekolah.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/identitas_sekolah.php)
* Ubah `json_encode($tenant, ...)` menjadi `json_encode($tenant, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)`

#### [MODIFY] [pdss_index.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/pdss_index.php)
* Tambahkan bendera `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT` pada data `_userRole`, `_canWrite`, dan `_currentTenantId`.

#### [MODIFY] [master_kelembagaan.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/master_kelembagaan.php)
* Tambahkan bendera `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT` pada data `listTenants`.

#### [MODIFY] [master_bk.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/master_bk.php)
* Tambahkan bendera `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT` pada `_tenantId`, `_userRole`, `_userId`, dan `_tahunAjaranList`.

#### [MODIFY] [login_view.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/login_view.php) & [siswa_login_view.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/siswa_login_view.php)
* Tambahkan bendera `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT` pada data `INITIAL_TENANTS`.

#### [MODIFY] [dashboard_view.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/dashboard_view.php)
* Tambahkan bendera `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT` pada stats list (userRole, schoolInfo, siswaList, gtkList, recentChanges).

#### [MODIFY] [buku_induk.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/buku_induk.php)
* Tambahkan bendera `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT` pada listTenants, jenjangOptions, dan kelasOptions.

#### [MODIFY] [bk/master_kampus_prodi_layout.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/bk/master_kampus_prodi_layout.php)
* Tambahkan bendera `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT` pada data `canWrite`.

#### [MODIFY] [activity_logs.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/activity_logs.php)
* Tambahkan bendera `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT` pada data `isSuperAdmin`.

#### [MODIFY] [sekolah/agenda_terpadu.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/sekolah/agenda_terpadu.php) (Untuk bagian di dalam `<script>`)
* Tambahkan bendera `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT` pada data `events` dan `tasks` di dalam tag script.

---

### 2. Kategori B: Proteksi Injeksi Atribut HTML (Anti Attribute Break)

#### [MODIFY] [sekolah/agenda_terpadu.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/sekolah/agenda_terpadu.php) (Untuk bagian tombol edit agenda/kategori)
* Ubah `onclick='editAgenda(<?= json_encode($row) ?>)'` menjadi:
  `onclick="editAgenda(<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>)"`
* Ubah `onclick='editKategori(<?= json_encode($row) ?>)'` menjadi:
  `onclick="editKategori(<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>)"`

#### [MODIFY] [humas/pengumuman.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/humas/pengumuman.php)
* Ubah `onclick='editPengumuman(<?= json_encode($row) ?>)'` menjadi:
  `onclick="editPengumuman(<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>)"`
* Ubah `onclick='editKategori(<?= json_encode($k) ?>)'` menjadi:
  `onclick="editKategori(<?= htmlspecialchars(json_encode($k), ENT_QUOTES, 'UTF-8') ?>)"`

---

## Rencana Verifikasi (Verification Plan)

### Verifikasi Manual
1. **Pengecekan Kebocoran Hash Password**:
   * Buka menu edit siswa, lihat kode sumber halaman. Pastikan kolom kata sandi tidak dikirimkan sama sekali.
2. **Pengujian Penanganan XSS & Karakter Kutip**:
   * Masukkan nama/kategori agenda yang mengandung kutip tunggal (`'`) atau tag script (`</script><script>alert(1)</script>`).
   * Buka kembali halaman agenda terpadu, coba klik tombol edit.
   * Pastikan form edit terbuka dengan benar dan data terisi dengan aman tanpa adanya error JavaScript atau alert XSS.
