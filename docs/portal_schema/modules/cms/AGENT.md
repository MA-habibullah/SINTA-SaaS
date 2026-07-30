# 🤖 AGENT GUIDE: Schema `cms`
> **Panduan Pembuatan Program** — Sinta SaaS PostgreSQL v21

---

## 1. Identitas Modul

| Property | Value |
|----------|-------|
| **Schema** | `cms` |
| **Icon** | 🌐 |
| **Judul** | CMS — Landing Page & Dynamic Web Sekolah |
| **Stack** | PHP 8.2 | Vue 3 / Nuxt (SSR) | PostgreSQL | S3 Storage (foto/file) |
| **Jumlah Tabel** | 8 tabel |

## 2. Deskripsi Modul

Manajemen konten website sekolah secara dinamis. Navbar, Hero Slider, Artikel, Galeri, Widget per sekolah/domain.

## 3. Service Classes yang Perlu Dibuat

- `LandingPageBuilder`
- `NavbarManager`
- `ArticleService`
- `GalleryService`
- `SliderService`

## 4. Key Architectural Patterns

- Multi-tenant CMS: satu platform, banyak website sekolah
- Dynamic Navbar dengan sorting urutan & parent-child recursive
- Konten di-render SSR (Nuxt) untuk SEO optimal
- File upload ke S3-compatible storage (foto, slider, lampiran)

## 5. Daftar Tabel (8 tabel)

| Nama Tabel | Deskripsi | Kolom Utama |
|------------|-----------|-------------|
| `cms_settings` | Pengaturan Utama Website Landing Page Sekolah (Branding, Logo, Favicon, Color Th | `id, tenant_id, site_title, site_tagline, logo_url...` |
| `cms_banners` | Slider Hero Banner & Carousel Promosi Landing Page Website Sekolah. | `id, tenant_id, title, subtitle, image_url...` |
| `cms_posts` | Berita, Pengumuman, Prestasi & Artikel Blog Kegiatan Sekolah. | `id, tenant_id, category_id, title, slug...` |
| `cms_categories` | Kategori Berita, Pengumuman & Event Sekolah. | `id, tenant_id, name, slug, description...` |
| `cms_pages` | Halaman Profil Dinamis Sekolah (Sejarah, Visi Misi, Fasilitas, Sambutan Kepala S | `id, tenant_id, slug, title, content_html...` |
| `cms_galleries` | Galeri Foto & Video Album Dokumentasi Kegiatan Sekolah. | `id, tenant_id, title, cover_image, media_type...` |
| `cms_widgets` | Widget & Seksi Komponen Interaktif Landing Page (Running Text, Stat Counter, Pop | `id, tenant_id, widget_name, widget_type, config_json...` |
| `cms_menus` | Manajemen Menu & Sub-Menu Navigasi Website Sekolah Dinamis (Dropdown Submenu, Li | `id, tenant_id, parent_id, title, link_type...` |


## 6. Critical JOIN Relationships

| Source Table | References | Via Column | Keterangan |
|-------------|-----------|-----------|-----------|
| `cms.cms_menus` | `cms_menus` | `parent_id` | SELF JOIN: Menu tree recursive untuk navbar |
| `cms.cms_articles` | `cms_categories` | `category_id` | CONTENT: Artikel → Kategori |
| `cms.cms_pages` | `tenants` | `tenant_id` | TENANT: Halaman milik sekolah mana |


## 7. Panduan Anti-Pattern (WAJIB DIIKUTI)

### ❌ SELECT * — Dilarang Keras
```sql
-- ❌ DILARANG
SELECT * FROM cms.nama_tabel;

-- ✅ WAJIB: Pilih kolom secara eksplisit
SELECT t.id, t.tenant_id, t.kolom1, t.kolom2
FROM cms.nama_tabel t
WHERE t.tenant_id = $1;
```

### ❌ Query Tanpa tenant_id Filter
```sql
-- ❌ DILARANG: Cross-tenant data leak!
SELECT id, nama FROM cms.nama_tabel;

-- ✅ WAJIB: Selalu filter tenant_id
SELECT t.id, t.nama
FROM cms.nama_tabel t
WHERE t.tenant_id = $1;  -- $1 = ID sekolah yang sedang login
```

### ❌ N+1 Query Problem
Jangan melakukan query di dalam loop. Gunakan JOIN atau Eager Loading.

```php
// ❌ DILARANG — N+1 problem: query di dalam loop
// $items = Query(...)->get();
// foreach ($items as $item) { $detail = Query(item_id)->first(); }

// ✅ WAJIB — Gunakan JOIN atau Eager Loading:
// DB::table('cms.nama_tabel AS t')
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
