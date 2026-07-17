# Revamp Halaman Kelola Akses Menu (/konfigurasi/akses)

## Ringkasan

Halaman `/konfigurasi/akses` saat ini memiliki dua masalah besar:
1. **Super Admin tidak bisa memilih sekolah/tenant** — akses hanya untuk global default, tidak bisa diatur per-sekolah
2. **Kolom header tabel hanya "Nama Menu (Sidebar)"** — perlu disamakan seperti `tenant_menus.php` yaitu **"Nama Menu / Fitur Sidebar"**

Solusinya: Refactor `kelola_akses.php` menjadi berbasis **Vue.js** (seperti `tenant_menus.php`) dengan fitur tambahan dropdown pemilih sekolah khusus untuk Super Admin.

---

## Perubahan yang Diusulkan

### Arsitektur

```
Super Admin → Pilih Tenant → Lihat matriks role x menu untuk tenant tersebut → Simpan
Operator Sekolah → Tidak ada dropdown (sudah terkunci ke tenant-nya sendiri) → Simpan
```

### Alur Data (Backend + Frontend)

| Action | Method | Endpoint | Keterangan |
|--------|--------|----------|------------|
| Load tenants + menus | GET | `/konfigurasi/akses` (PHP) | Inject `$tenants`, `$menus`, `$roles` ke view |
| Load access map per tenant | GET | `/api/v1/akses/fetch?tenant_id=X` | AJAX endpoint baru |
| Simpan matriks | POST | `/konfigurasi/akses/simpan` | Sudah ada, tambah `tenant_id` di payload |

---

## Proposed Changes

### [MODIFY] [kelola_akses.php](file:///c:/xampp/htdocs/SINTA-SaaS/views/kelola_akses.php)

- Bungkus seluruh halaman dalam `<div id="aksesApp">` Vue.js
- **Tambah dropdown pemilih sekolah** (hanya muncul jika role = `super_admin`)
  - Data tenants di-inject dari PHP ke JS variable
  - Saat tenant dipilih, fetch access map via AJAX
- **Perbaiki judul kolom**: "Nama Menu (Sidebar)" → "Nama Menu / Fitur Sidebar"  
- **Tampilan menu tree**: parent bold, child indent dengan ikon `└──` (sama persis dengan `tenant_menus.php`)
- Cascade logic (uncheck parent → uncheck children) dipertahankan
- **Submit form**: kirim `tenant_id` yang sedang dipilih bersama data akses
- Untuk `operator_sekolah`: tidak ada dropdown, langsung tampilkan matriks untuk tenant mereka

### [MODIFY] [AksesController.php](file:///c:/xampp/htdocs/SINTA-SaaS/app/Controllers/AksesController.php)

- `index()`: tambah inject `$tenants` (daftar semua sekolah) ke `$data` untuk Super Admin
- `store()`: baca `tenant_id` dari `$_POST` (jika Super Admin) untuk menentukan target tenant

### [NEW] API Endpoint untuk fetch access map per tenant
- Route: `GET /api/v1/akses/fetch?tenant_id=X&role_id=Y`
- Response: `{ success: true, access_map: {'role_id-menu_id': true} }`
- Ditambahkan ke `index.php` routing

---

## Verification Plan

- Login sebagai Super Admin → buka `/konfigurasi/akses` → dropdown sekolah muncul
- Pilih sekolah berbeda → matriks berganti sesuai konfigurasi sekolah tersebut
- Simpan → data tersimpan untuk tenant yang dipilih, bukan global
- Login sebagai Operator Sekolah → tidak ada dropdown, matriks tetap normal
- Cek sidebar siswa/guru/karyawan setelah diubah → ikuti konfigurasi baru

> [!IMPORTANT]
> Perubahan ini tidak merusak alur Operator Sekolah yang sudah berjalan — logika keamanan di `AksesController` tetap dipertahankan
