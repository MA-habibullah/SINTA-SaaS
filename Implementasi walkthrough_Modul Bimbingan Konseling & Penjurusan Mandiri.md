# Walkthrough — Modul Bimbingan Konseling & Penjurusan Mandiri

## ✅ Status Akhir: SELESAI DAN TERVERIFIKASI

**Hasil Lint Scan:** 0 syntax error di seluruh codebase  
**Hasil DB Check:** Semua tabel, role, menu, dan RBAC terverifikasi di DB

---

## 📦 File yang Dibuat / Dimodifikasi

| File | Aksi | Keterangan |
|---|---|---|
| [2026_06_18_create_bimbingan_konseling_module.php](file:///c:/xampp/htdocs/dapodik-spmb/database/migrations/2026_06_18_create_bimbingan_konseling_module.php) | **BARU** | Tabel `catatan_bk`, role `guru_bk`, menu id=30, seed RBAC 3 sekolah |
| [2026_06_19_create_penjurusan_tables.php](file:///c:/xampp/htdocs/dapodik-spmb/database/migrations/2026_06_19_create_penjurusan_tables.php) | **BARU** | Tabel `pilihan_penjurusan` + `pilihan_penjurusan_log` (audit trail ACID) |
| [BKController.php](file:///c:/xampp/htdocs/dapodik-spmb/app/Controllers/BKController.php) | **BARU** | Controller lengkap 9 metode: role guard, 5 tab API, 4 penjurusan endpoint |
| [master_bk.php](file:///c:/xampp/htdocs/dapodik-spmb/views/master_bk.php) | **BARU** | View hub 5 tab Vue 3 reaktif, Tab 2 kini full — tabel data, filter, modal override |
| [index.php](file:///c:/xampp/htdocs/dapodik-spmb/index.php) | **EDIT** | +9 routes: `/bk` + 8 API endpoint BK |

---

## 🗄️ Database Schema

### Tabel `pilihan_penjurusan`
```
id, id_siswa, tenant_id, id_jurusan, id_tahun_ajaran,
status [Diajukan|Diverifikasi|Ditolak|Override_BK],
catatan_bk, dikunci, diajukan_oleh, diproses_oleh, diproses_at,
created_at, updated_at, deleted_at
```

### Tabel `pilihan_penjurusan_log` (Immutable Audit Trail)
```
id, id_pilihan, id_siswa, tenant_id, id_jurusan_lama, id_jurusan_baru,
status_lama, status_baru, aksi [Buat|Verifikasi|Tolak|Override|Buka Kunci],
dilakukan_oleh, nama_pelaku, catatan, created_at
```

### Tabel `catatan_bk`
```
id, id_siswa, tenant_id, id_guru_bk, tanggal_konseling,
jenis_kasus, catatan, tindak_lanjut, status_kasus, is_rahasia,
created_at, updated_at, deleted_at
```

---

## 🔒 Security Architecture

```
HTTP Request
    │
    ├─ SessionManager::requireLogin()  ← redirect 302 jika belum login
    ├─ guardRole()                     ← 403 jika bukan super_admin / operator / guru_bk
    └─ getSecureTenantId()
            ├─ Admin / Guru BK → lock ke session tenant_id (tidak bisa diubah)
            └─ Super Admin     → validasi GET ?tenant_id ke DB (anti-spoofing)
                    │
                    └─ Semua query: WHERE tenant_id = ? ← isolasi multi-tenant ketat
```

**Anti-IDOR Protection:** Setiap operasi (verifikasi, override, kunci) memverifikasi `tenant_id` pada baris yang dituju sebelum mengubah data.

---

## 🌐 Routes yang Terdaftar

| Method | Path | Fungsi |
|---|---|---|
| GET | `/bk` | Halaman utama modul BK (5 tab) |
| GET | `/api/v1/bk/dashboard` | KPI: total siswa, kasus, alumni |
| GET/POST | `/api/v1/bk/kasus` | List & rekam catatan konseling |
| GET | `/api/v1/bk/tracer` | Summary tracer study alumni |
| GET | `/api/v1/bk/pdss` | Daftar siswa kelas 12 eligible SNBP |
| GET | `/api/v1/bk/penjurusan` | List pilihan jurusan + summary + dropdown |
| POST | `/api/v1/bk/penjurusan/verifikasi` | Verifikasi atau tolak pilihan |
| POST | `/api/v1/bk/penjurusan/override` | Override jurusan + ACID lock + audit |
| POST | `/api/v1/bk/penjurusan/kunci` | Toggle buka/kunci pilihan |

---

## 🖥️ UI — 5 Tab Master BK

### Tab 1 — Dashboard KPI ✅
- 4 KPI cards: Siswa Aktif, Kasus Bulan Ini, Kasus Terbuka, Total Alumni
- Distribusi kasus per jenis (legend chart)

### Tab 2 — Penjurusan Mandiri ✅ *(Sebelumnya: Placeholder)*
- **Summary cards** distribusi per jurusan (Pending / Verified / Ditolak)
- **Filter bar**: search nama/NISN, filter status, filter jurusan
- **Data table** dengan 4 aksi per baris:
  - ✅ **Verifikasi** — mengubah status menjadi `Diverifikasi`
  - ❌ **Tolak** — mengubah status menjadi `Ditolak`  
  - 🔄 **Override** — membuka modal dengan ACID warning
  - 🔒 **Kunci/Buka** — toggle `dikunci = 0|1`
- **Modal Override** dengan:
  - Peringatan ACID Lock (perubahan permanen)
  - Dropdown jurusan tujuan (jurusan saat ini di-disable)
  - Textarea alasan (wajib — untuk audit log)
  - Tombol Konfirmasi + loading state

### Tab 3 — Tracer Study ✅
- KPI kuliah / bekerja / total alumni
- Distribusi persentase
- Link ke halaman Tracer Study lengkap

### Tab 4 — Kesiapan PDSS ✅
- Daftar siswa kelas 12 eligible SNBP
- Filter search nama/NISN
- Badge ELIGIBLE

### Tab 5 — Jurnal BK ✅
- Form rekam kasus baru (autocomplete siswa, debounce 300ms)
- Kontrol privasi: checkbox Rahasia (Guru BK lain tidak bisa lihat)
- Tabel riwayat kasus dengan badge status warna

---

## 🏫 RBAC — Menu Bimbingan Konseling

| Role | Menu Muncul | Akses API |
|---|---|---|
| `super_admin` | ✅ Ya | Full + filter tenant |
| `operator_sekolah` | ✅ Ya | Locked ke tenant session |
| `guru_bk` | ✅ Ya | Locked ke tenant session |
| `siswa` | ❌ Tidak | 403 Forbidden |
| Role lain | ❌ Tidak | 403 Forbidden |

**Terverifikasi di DB:** Menu id=30 terdaftar untuk 3 role dan 3 sekolah.

---

## 🧪 Verifikasi Akhir

```
✅ PHP Lint: 0 error (seluruh app/, views/, database/migrations/)
✅ Migration: 2 tabel baru berhasil dibuat
✅ DB Check:
   [catatan_bk]            → 13 kolom ✅
   [pilihan_penjurusan]    → 14 kolom ✅
   [pilihan_penjurusan_log]→ 13 kolom ✅
   [roles] guru_bk         → id=20 ✅
   [menus] BK id=30        → /dapodik-spmb/bk ✅
   [RBAC]  menu_id=30      → super_admin, operator_sekolah, guru_bk ✅
   [TENANT] menu_id=30     → 3 sekolah tersinkron ✅
```
