# 🤖 AGENT GUIDE: Schema `core`
> **Panduan Pembuatan Program** — Sinta SaaS PostgreSQL v21

---

## 1. Identitas Modul

| Property | Value |
|----------|-------|
| **Schema** | `core` |
| **Icon** | ☁️ |
| **Judul** | Core — SaaS Multi-Tenant, Auth & Wilayah |
| **Stack** | PHP 8.2 | Laravel 11 | PostgreSQL | Redis (session) | Nginx (domain routing) |
| **Jumlah Tabel** | 17 tabel |

## 2. Deskripsi Modul

Schema pondasi seluruh sistem. Mengelola Tenant (sekolah), User, Auth, Custom Domain, Menu Akses, & Referensi Wilayah.

## 3. Service Classes yang Perlu Dibuat

- `TenantService`
- `AuthService`
- `DomainRouter`
- `MenuAccessService`
- `WilayahService`

## 4. Key Architectural Patterns

- Row Level Security (RLS) berbasis tenant_id
- Dynamic subdomain routing via Nginx + wildcard SSL
- JWT token dengan scope per tenant
- Composite index (tenant_id + entity_id) di semua tabel child

## 5. Daftar Tabel (17 tabel)

| Nama Tabel | Deskripsi | Kolom Utama |
|------------|-----------|-------------|
| `tenants` | MASTER SEKOLAH | `id, nama_sekolah, npsn, subdomain, custom_domain...` |
| `tenant_menu_access` | Fitur/menu per tenant. | `id, tenant_id, menu_id, created_at, updated_at...` |
| `menus` | Daftar seluruh menu. | `id, nama_menu, url, icon, parent_id...` |
| `users` | Akun login: admin, guru, siswa, ortu. | `id, tenant_id, role_id, nama_lengkap, email...` |
| `user_roles` | Pivot user-role. | `user_id, role_id...` |
| `roles` | Master role. | `id, nama_role, deskripsi, created_at, updated_at...` |
| `role_menu_access` | Hak akses role. | `tenant_id, role_id, menu_id...` |
| `user_menu_access` | Override hak akses user. | `id, user_id, menu_id, tenant_id, created_at...` |
| `active_sessions` | Sesi login aktif. | `id, user_id, tenant_id, ip_address, user_agent...` |
| `pengaturan` | Config global. | `id_pengaturan, nama_pengaturan, nilai...` |
| `jenjang` | Master jenjang. | `id, tenant_id, kode_jenjang, nama_jenjang, is_active...` |
| `provinsi` | Master provinsi. | `id_provinsi, nama_provinsi...` |
| `kota` | Master kota. | `id_kota, id_provinsi, nama_kota...` |
| `kecamatan` | Master kecamatan. | `id_kecamatan, id_kota, nama_kecamatan...` |
| `kelurahan` | Master kelurahan. | `id_kelurahan, id_kecamatan, nama_kelurahan...` |
| `tenant_cms_pages` | Manajemen Halaman Landing Page CMS & Custom Sub-Portal Navigation per Sekolah /  | `id, tenant_id, slug, title, page_type...` |
| `tenant_domains` | Penambahan & Pemetaan Domain/Subdomain Dinamis per Sekolah (Custom Domain, Sub-P | `id, tenant_id, domain_name, domain_type, target_module...` |


## 6. Critical JOIN Relationships

| Source Table | References | Via Column | Keterangan |
|-------------|-----------|-----------|-----------|
| `core.users` | `tenants` | `tenant_id` | AUTH: Validasi user milik tenant mana |
| `core.menus` | `user_roles` | `role` | MENU: Menu apa yang bisa diakses role ini |
| `core.tenant_custom_domains` | `tenants` | `tenant_id` | DOMAIN: Resolve custom domain ke tenant |


## 7. Panduan Anti-Pattern (WAJIB DIIKUTI)

### ❌ SELECT * — Dilarang Keras
```sql
-- ❌ DILARANG
SELECT * FROM core.nama_tabel;

-- ✅ WAJIB: Pilih kolom secara eksplisit
SELECT t.id, t.tenant_id, t.kolom1, t.kolom2
FROM core.nama_tabel t
WHERE t.tenant_id = $1;
```

### ❌ Query Tanpa tenant_id Filter
```sql
-- ❌ DILARANG: Cross-tenant data leak!
SELECT id, nama FROM core.nama_tabel;

-- ✅ WAJIB: Selalu filter tenant_id
SELECT t.id, t.nama
FROM core.nama_tabel t
WHERE t.tenant_id = $1;  -- $1 = ID sekolah yang sedang login
```

### ❌ N+1 Query Problem
Jangan melakukan query di dalam loop. Gunakan JOIN atau Eager Loading.

```php
// ❌ DILARANG — N+1 problem: query di dalam loop
// $items = Query(...)->get();
// foreach ($items as $item) { $detail = Query(item_id)->first(); }

// ✅ WAJIB — Gunakan JOIN atau Eager Loading:
// DB::table('core.nama_tabel AS t')
//   ->join('schema2.detail AS d', 't.id', '=', 'd.item_id')
//   ->select(['t.id', 't.nama', 'd.detail_kolom'])
//   ->where('t.tenant_id', $tenantId)->get();
```

## 8. Security Rules

- **tenant_id WAJIB** ada di setiap WHERE clause query
- **Prepared Statement** (PDO/Eloquent) untuk semua query dengan input user
- **Audit Log** wajib di setiap operasi CUD (Create/Update/Delete) via `sistem.audit_log`
- **Input validation** sebelum masuk query: `strip_tags()`, `htmlspecialchars()`, `filter_var()`

## 9. File Referensi Modul Ini

| File | Isi |
|------|-----|
| [`index_guide.sql`](./index_guide.sql) | DDL Index definitions + rekomendasi tambahan |
| [`join_patterns.sql`](./join_patterns.sql) | Contoh JOIN patterns per tabel |
| [`query_examples.sql`](./query_examples.sql) | Contoh SELECT eksplisit per tabel (no SELECT *) |
| [`no_select_star.sql`](./no_select_star.sql) | Perbandingan ❌ SELECT * vs ✅ Explicit columns |

---
*Generated: 2026-07-30 | Sinta SaaS PostgreSQL v21 | 164 Tabel | 16 Schema*
