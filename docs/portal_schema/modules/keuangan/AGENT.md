# 🤖 AGENT GUIDE: Schema `keuangan`
> **Panduan Pembuatan Program** — Sinta SaaS PostgreSQL v21

---

## 1. Identitas Modul

| Property | Value |
|----------|-------|
| **Schema** | `keuangan` |
| **Icon** | 💰 |
| **Judul** | Keuangan — SPP, Tagihan & Pembayaran |
| **Stack** | PHP 8.2 | Laravel 11 | PostgreSQL | Payment Gateway (Midtrans/Xendit) | Queue (generate tagihan) |
| **Jumlah Tabel** | 6 tabel |

## 2. Deskripsi Modul

Manajemen keuangan sekolah: Pos keuangan, Tarif SPP per jenis siswa, Tagihan bulanan, dan Transaksi Pembayaran.

## 3. Service Classes yang Perlu Dibuat

- `SPPService`
- `TagihanService`
- `PembayaranService`
- `LaporanKeuanganService`
- `PaymentGatewayAdapter`

## 4. Key Architectural Patterns

- Generate tagihan bulanan via Queue job (bulk insert untuk ribuan siswa)
- Idempotency key pada transaksi untuk mencegah double payment
- Satu siswa bisa punya banyak pos tagihan (SPP, praktik, seragam, dll)
- Audit trail WAJIB: setiap perubahan tagihan/pembayaran dicatat di sistem.audit_log

## 5. Daftar Tabel (6 tabel)

| Nama Tabel | Deskripsi | Kolom Utama |
|------------|-----------|-------------|
| `transaksi_spp_komponen` | Komponen SPP. | `id, tenant_id, nama_komponen, tipe_periode, is_active...` |
| `transaksi_spp_tarif` | Tarif SPP. | `id, tenant_id, komponen_id, kelas_id, jenjang_id...` |
| `transaksi_spp_tagihan` | Tagihan SPP. | `id, tenant_id, siswa_id, komponen_id, tarif_id...` |
| `transaksi_spp_pembayaran` | Pembayaran SPP. | `id, tenant_id, tagihan_id, siswa_id, nominal_dibayar...` |
| `transaksi_spp_keringanan` | Diskon/Keringanan SPP. | `id, tenant_id, siswa_id, komponen_id, tipe_keringanan...` |
| `transaksi_spp_audit_log` | Audit log keuangan & pengaturan. | `id, tenant_id, user_id, aksi, tabel_target...` |


## 6. Critical JOIN Relationships

| Source Table | References | Via Column | Keterangan |
|-------------|-----------|-----------|-----------|
| `keuangan.tagihan_siswa` | `siswa.siswa` | `siswa_id` | TAGIHAN: Tagihan → Siswa |
| `keuangan.tagihan_siswa` | `keuangan.pos_keuangan` | `pos_id` | TAGIHAN: Tagihan → Jenis Pos |
| `keuangan.transaksi_pembayaran` | `keuangan.tagihan_siswa` | `tagihan_id` | BAYAR: Transaksi → Tagihan |


## 7. Panduan Anti-Pattern (WAJIB DIIKUTI)

### ❌ SELECT * — Dilarang Keras
```sql
-- ❌ DILARANG
SELECT * FROM keuangan.nama_tabel;

-- ✅ WAJIB: Pilih kolom secara eksplisit
SELECT t.id, t.tenant_id, t.kolom1, t.kolom2
FROM keuangan.nama_tabel t
WHERE t.tenant_id = $1;
```

### ❌ Query Tanpa tenant_id Filter
```sql
-- ❌ DILARANG: Cross-tenant data leak!
SELECT id, nama FROM keuangan.nama_tabel;

-- ✅ WAJIB: Selalu filter tenant_id
SELECT t.id, t.nama
FROM keuangan.nama_tabel t
WHERE t.tenant_id = $1;  -- $1 = ID sekolah yang sedang login
```

### ❌ N+1 Query Problem
Jangan melakukan query di dalam loop. Gunakan JOIN atau Eager Loading.

```php
// ❌ DILARANG — N+1 problem: query di dalam loop
// $items = Query(...)->get();
// foreach ($items as $item) { $detail = Query(item_id)->first(); }

// ✅ WAJIB — Gunakan JOIN atau Eager Loading:
// DB::table('keuangan.nama_tabel AS t')
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
