# Dokumentasi Modul 06 - Informasi & Agenda

## 1. Pendahuluan
Modul **Informasi & Agenda** (sering disebut modul Humas) bertanggung jawab atas penyebaran informasi satu arah (Pengumuman) dan pemetaan jadwal lintas periode (*Timeline* Sekolah). Modul ini sangat vital dalam menggantikan fungsi "Mading" (Majalah Dinding) secara digital di era *paperless*.

## 2. Alur Kerja (Workflow)
1. **Pembuatan Pengumuman / Agenda:** 
   - Humas atau Super Admin masuk ke menu **Manajemen Pengumuman**.
   - Mereka mengisi Form yang memuat Judul, Teks (melalui *Rich Text Editor*), dan mengatur **Visibilitas**.
2. **Targeting (Visibilitas):** 
   Aplikasi menyediakan 4 lapisan visibilitas (akses kontrol):
   - **Publik:** Tampil ke seluruh _role_ (Siswa, Karyawan, Guru, Admin).
   - **Guru:** Hanya muncul di _dashboard_ pengguna yang masuk dalam rumpun *role* Pendidik.
   - **Siswa:** Pengumuman eksklusif bagi *role* Siswa.
   - **Spesifik Role:** Memilih *role* dari basis data secara presisi (misalnya hanya diumumkan kepada "Waka Kurikulum" atau "Guru BK"). Pilihan `super_admin` dihilangkan agar pengumuman tidak tereskalasi keliru.
3. **Penyajian Data (Rendering):** 
   - Di sisi pengguna akhir (beranda/dashboard), kueri model secara pintar membaca `role_id` milik *user* dan mencari irisan (intersection) dengan array `target_roles` milik pengumuman/agenda.
   - Jika beririsan dan berstatus `is_active = 1`, data akan ter- *render*.

## 3. Komponen Backend
### Controllers
- `App\Controllers\PengumumanController.php`
- `App\Controllers\AgendaController.php`
Sangat mirip dalam struktur CRUD dasar. Perbedaannya terletak di penanganan *file upload*.
- Pada `PengumumanController->store()`, *backend* mencegat `$_FILES['lampiran']`. Ia membuat direktori unik (jika belum ada) di `storage/app/public/uploads/` lalu menggeser *file* asli, serta memproteksi rentan serangan XSS/Webshell melalui ekstensi yang diperbolehkan (`pdf`, `jpg`, `png`). 

### Models & Resolusi Tenant
- `App\Models\PengumumanModel.php`
- `App\Models\AgendaModel.php`
- Logika **Isolasi Tenant**: Pengumuman yang dibuat oleh Admin Sekolah (Operator) akan mengunci *field* `tenant_id` ke sekolah mereka. Namun, jika Super Admin membuat pengumuman, *field* `tenant_id` menjadi `NULL`. Konsekuensinya: Kueri `getActiveForUser()` di tingkat pengguna membaca logika `WHERE tenant_id = ? OR tenant_id IS NULL`, memungkinkan pengumuman *Global* dari platform terbaca oleh setiap institusi.

## 4. Komponen Frontend (View & UI)
- **Editor Teks Kaya (Rich Text):** Mengintegrasikan `Quill.js` *Snow Theme*. Input yang dikirim ke *backend* sudah berupa format HTML murni. 
- **Tabel Manajemen (DataTables / Standard Table):** Menampilkan daftar informasi yang berumur panjang dengan opsi Edit dan Delete. Opsi *Toggle Switch* digunakan untuk menonaktifkan Pengumuman secara instan tanpa menghapus rekaman di *database*.
- **Modal Add/Edit:** Mencegah perpindahan halaman, UI *form* dilakukan secara _pop-up_ menggunakan modal Bootstrap 5.
