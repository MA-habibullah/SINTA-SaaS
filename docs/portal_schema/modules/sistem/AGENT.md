# 🤖 AGENT GUIDE: Schema `sistem`
> **Panduan Pembuatan Program** — Sinta SaaS PostgreSQL v21

---

## 1. Identitas Modul

| Property | Value |
|----------|-------|
| **Schema** | `sistem` |
| **Icon** | ⚙️ |
| **Judul** | Sistem — Log, Audit Trail & Helpdesk |
| **Stack** | PHP 8.2 | Laravel 11 | PostgreSQL | Queue (audit write) | Slack/WA Webhook (alert error) |
| **Jumlah Tabel** | 8 tabel |

## 2. Deskripsi Modul

Schema infrastruktur sistem: Audit Trail semua aktivitas CRUD, Error Log, Backup Schedule, dan Tiket Helpdesk.

## 3. Service Classes yang Perlu Dibuat

- `AuditLogService`
- `ErrorLogService`
- `BackupService`
- `HelpdeskService`
- `NotifikasiService`

## 4. Key Architectural Patterns

- WAJIB: SETIAP operasi CREATE/UPDATE/DELETE di seluruh sistem wajib log ke sistem.audit_log
- Audit log: immutable (insert-only, tidak ada UPDATE/DELETE)
- Error log: severity level (DEBUG/INFO/WARNING/ERROR/CRITICAL)
- Helpdesk: tiket per tenant, SLA response time monitoring

## 5. Daftar Tabel (8 tabel)

| Nama Tabel | Deskripsi | Kolom Utama |
|------------|-----------|-------------|
| `activity_logs` | Audit Trail & System Log Terpusat untuk 100% Perubahan Data (CREATE, UPDATE, DEL | `id, tenant_id, user_id, user_role, module_name...` |
| `system_errors` | Log error PHP. | `id, tenant_id, error_level, message, file...` |
| `system_jobs` | Job queue. | `id, tenant_id, job_type, payload, status...` |
| `tickets` | UNIFIED HELPDESK TICKETS | `id, tenant_id, user_id, category_id, judul...` |
| `ticket_replies` | Balasan tiket. | `id, ticket_id, user_id, is_superadmin, pesan...` |
| `ticket_faqs` | FAQ helpdesk. | `id, pertanyaan, jawaban, kategori, created_at...` |
| `agenda_sekolah` | UNIFIED AGENDA | `id, tenant_id, created_by, judul, deskripsi...` |
| `pengumuman` | UNIFIED PENGUMUMAN | `id, tenant_id, created_by, judul, isi_pengumuman...` |


## 6. Critical JOIN Relationships

| Source Table | References | Via Column | Keterangan |
|-------------|-----------|-----------|-----------|
| `sistem.audit_log` | `core.users` | `user_id` | AUDIT: Log → User yang melakukan aksi |
| `sistem.audit_log` | `core.tenants` | `tenant_id` | AUDIT: Log → Sekolah/Tenant |
| `sistem.helpdesk_tiket` | `core.users` | `pelapor_user_id` | TIKET: Helpdesk → User Pelapor |


## 7. Panduan Anti-Pattern (WAJIB DIIKUTI)

### ❌ SELECT * — Dilarang Keras
```sql
-- ❌ DILARANG
SELECT * FROM sistem.nama_tabel;

-- ✅ WAJIB: Pilih kolom secara eksplisit
SELECT t.id, t.tenant_id, t.kolom1, t.kolom2
FROM sistem.nama_tabel t
WHERE t.tenant_id = $1;
```

### ❌ Query Tanpa tenant_id Filter
```sql
-- ❌ DILARANG: Cross-tenant data leak!
SELECT id, nama FROM sistem.nama_tabel;

-- ✅ WAJIB: Selalu filter tenant_id
SELECT t.id, t.nama
FROM sistem.nama_tabel t
WHERE t.tenant_id = $1;  -- $1 = ID sekolah yang sedang login
```

### ❌ N+1 Query Problem
Jangan melakukan query di dalam loop. Gunakan JOIN atau Eager Loading.

```php
// ❌ DILARANG — N+1 problem: query di dalam loop
// $items = Query(...)->get();
// foreach ($items as $item) { $detail = Query(item_id)->first(); }

// ✅ WAJIB — Gunakan JOIN atau Eager Loading:
// DB::table('sistem.nama_tabel AS t')
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
