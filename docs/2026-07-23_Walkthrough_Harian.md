# Walkthrough Harian - 23 Juli 2026

---
## [Fitur Perpustakaan SaaS Multi-Tenant & Switcher Super Admin]
**Waktu**: 02:10 WIB
**Jenis**: Feature & Refactor Security

### Deskripsi Perubahan:
1. **Filter Switcher Sekolah / Tenant Super Admin**:
   - Menambahkan komponen parsial `views/perpustakaan/_tenant_filter.php` pada seluruh 10 sub-menu perpustakaan.
   - Super Admin dapat berganti tenant/sekolah yang dikelola secara dinamis melalui dropdown *Switch Sekolah*.
   - Menyimpan `active_tenant_id` pada session sehingga seluruh query data, input form, dan laporan otomatis terisolasi pada sekolah yang dipilih.

2. **Pengisian Data Berdasarkan Tenant**:
   - Menambahkan input dropdown pilihan sekolah `tenant_id` pada seluruh modal formulir (Tambah Buku, Distribusi Paket, Event OSN, Sync Anggota, Opname Baru) khusus untuk Super Admin.
   - Untuk Operator/Admin Sekolah biasa, `tenant_id` diset otomatis secara tersembunyi (*hidden input*) berdasarkan tenant miliknya tanpa perlu memilih.

3. **Visibilitas Kolom Tabel "Sekolah / Tenant"**:
   - Menambahkan kolom **Sekolah / Tenant** pada seluruh tabel data perpustakaan (`katalog.php`, `buku_paket.php`, `event_osn.php`, `anggota.php`, `opname.php`).
   - Melakukan `LEFT JOIN tenants t ON b.tenant_id = t.id` pada model `Perpustakaan.php` untuk menampilkan nama sekolah pada setiap baris data.

### Berkas yang Diubah / Ditambahkan:
- `app/Controllers/PerpustakaanController.php`
- `app/Models/Perpustakaan.php`
- `views/perpustakaan/_tenant_filter.php` [NEW]
- `views/perpustakaan/dashboard.php`
- `views/perpustakaan/katalog.php`
- `views/perpustakaan/sirkulasi.php`
- `views/perpustakaan/buku_paket.php`
- `views/perpustakaan/event_osn.php`
- `views/perpustakaan/anggota.php`
- `views/perpustakaan/denda.php`
- `views/perpustakaan/opname.php`
- `views/perpustakaan/laporan.php`
- `views/perpustakaan/pengaturan.php`
