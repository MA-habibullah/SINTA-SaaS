# 🤖 MASTER AGENT GUIDE — Sinta SaaS PostgreSQL v21
> **Panduan Lengkap Pembuatan Program** untuk Sistem Informasi Sekolah Multi-Tenant

---

## 📋 Gambaran Sistem

Sinta SaaS adalah platform **Sistem Informasi Sekolah berbasis SaaS Multi-Tenant** yang dibangun di atas PostgreSQL dengan **164 tabel eksplisit** dalam **16 schema**.

| Properti | Nilai |
|----------|-------|
| **Platform** | SaaS Multi-Tenant |
| **Database** | PostgreSQL 16 |
| **Backend** | PHP 8.2 + Laravel 11 |
| **Frontend** | Vue 3 / Inertia.js |
| **Cache** | Redis |
| **Storage** | S3-compatible (MinIO/AWS) |
| **Total Tabel** | 164 tabel |
| **Total Schema** | 16 schema |

---

## 🏗️ Arsitektur Database

```
sinta_saas_db (PostgreSQL 16)
├── core          ← SaaS Foundation: Tenant, Auth, Domain, Menu
├── cms           ← Website Builder: Landing Page, Navbar, Konten
├── akademik      ← Kurikulum: Rombel, Mapel, Nilai Rapor
├── siswa         ← Data Peserta Didik (Master Identity)
├── kesiswaan     ← Ekskul, Prestasi, PPDB, Kedisiplinan
├── bk            ← Bimbingan Konseling
├── pdss          ← SNBP & Perguruan Tinggi
├── tracer        ← Alumni & Karir (BMW Study)
├── kepegawaian   ← PTK/Guru, Berkas, Rekrutmen
├── keuangan      ← SPP, Tagihan, Pembayaran
├── perpustakaan  ← Library Digital & Sirkulasi
├── sarpras       ← Inventaris, Gudang, Maintenance
├── persuratan    ← E-Office & Tata Naskah Dinas
├── absensi       ← Presensi GPS + RFID
├── smk           ← PKL, UKK, BKK (khusus SMK)
└── sistem        ← Audit Log, Error Log, Helpdesk
```

---

## 📂 Struktur Module Folder

```
docs/portal_schema/modules/
├── MASTER_AGENT.md          ← File ini
├── core/
│   ├── AGENT.md             ← Panduan core schema
│   ├── join_patterns.sql    ← Contoh JOIN per tabel
│   ├── index_guide.sql      ← Rekomendasi index
│   ├── query_examples.sql   ← Contoh query SELECT eksplisit
│   └── no_select_star.sql   ← Anti-pattern SELECT *
├── cms/
│   └── ... (5 file sama)
├── akademik/
├── siswa/
├── kesiswaan/
├── bk/
├── pdss/
├── tracer/
├── kepegawaian/
├── keuangan/
├── perpustakaan/
├── sarpras/
├── persuratan/
├── absensi/
├── smk/
└── sistem/
```

---

## 📊 Summary Schema

| Schema | Tabel | Fungsi | Primary Stack |
|--------|-------|--------|---------------|
| [☁️ `core`](./core/AGENT.md) | 17 | SaaS Multi-Tenant, Auth & Wilayah | PHP 8.2 |
| [📚 `akademik`](./akademik/AGENT.md) | 20 | Kurikulum, Rombel & Penilaian | PHP 8.2 |
| [🎓 `siswa`](./siswa/AGENT.md) | 9 | Data Peserta Didik (Consolidated) | PHP 8.2 |
| [🤸 `kesiswaan`](./kesiswaan/AGENT.md) | 9 | Ekskul, Prestasi, PPDB & Kedisiplinan | PHP 8.2 |
| [💬 `bk`](./bk/AGENT.md) | 8 | Bimbingan Konseling | PHP 8.2 |
| [🏆 `pdss`](./pdss/AGENT.md) | 7 | SNBP & Perguruan Tinggi | PHP 8.2 |
| [🎯 `tracer`](./tracer/AGENT.md) | 5 | Alumni & Karir | PHP 8.2 |
| [👔 `kepegawaian`](./kepegawaian/AGENT.md) | 10 | PTK, Dokumen Digital & Rekrutmen | PHP 8.2 |
| [💰 `keuangan`](./keuangan/AGENT.md) | 6 | SPP, Tagihan & Pembayaran | PHP 8.2 |
| [📖 `perpustakaan`](./perpustakaan/AGENT.md) | 18 | Library Digital & Sirkulasi | PHP 8.2 |
| [🏢 `sarpras`](./sarpras/AGENT.md) | 17 | Inventaris, Gudang & Maintenance | PHP 8.2 |
| [📨 `persuratan`](./persuratan/AGENT.md) | 9 | E-Office & Tata Naskah Dinas | PHP 8.2 |
| [⏰ `absensi`](./absensi/AGENT.md) | 8 | Presensi PTK, Siswa & Geofencing GPS | PHP 8.2 |
| [🛠️ `smk`](./smk/AGENT.md) | 5 | PKL, UKK & Tracer BKK | PHP 8.2 |
| [⚙️ `sistem`](./sistem/AGENT.md) | 8 | Log, Audit Trail & Helpdesk | PHP 8.2 |
| [🌐 `cms`](./cms/AGENT.md) | 8 | Landing Page & Dynamic Web Sekolah | PHP 8.2 |

| **TOTAL** | **164** | | |

---

## 🔑 Aturan WAJIB Global (Berlaku untuk SEMUA Schema)

### 1. ❌ DILARANG — SELECT *
```sql
-- ❌ JANGAN PERNAH gunakan ini
SELECT * FROM core.tenants;
SELECT * FROM siswa.siswa;

-- ✅ WAJIB pilih kolom eksplisit
SELECT t.id, t.nama_sekolah, t.subdomain, t.is_active
FROM core.tenants t
WHERE t.is_active = true;
```

### 2. ✅ WAJIB — tenant_id Isolation
```sql
-- SETIAP query harus filter tenant_id
-- Kecuali query dari Super Admin
SELECT s.id, s.nama_lengkap, s.nis, s.nisn
FROM siswa.siswa s
WHERE s.tenant_id = :tenant_id   -- ← WAJIB SELALU ADA
  AND s.is_active = true;
```

### 3. ✅ WAJIB — Audit Log pada Setiap CUD
```php
// Setiap CREATE, UPDATE, DELETE HARUS dicatat
AuditLog::record([
    'tenant_id'   => $tenantId,
    'user_id'     => auth()->id(),
    'entity_type' => 'siswa.siswa',
    'entity_id'   => $siswa->id,
    'action'      => 'UPDATE',
    'old_data'    => json_encode($oldData),
    'new_data'    => json_encode($newData),
    'ip_address'  => request()->ip(),
]);
```

### 4. ✅ WAJIB — Prepared Statement (Anti SQL Injection)
```php
// ❌ DILARANG: String concatenation
$query = "SELECT * FROM siswa.siswa WHERE nama = '" . $nama . "'";

// ✅ WAJIB: Prepared statement via PDO/Eloquent
$siswa = DB::table('siswa.siswa')
    ->select(['id', 'nama_lengkap', 'nis', 'nisn'])
    ->where('tenant_id', $tenantId)
    ->where('nama_lengkap', 'ILIKE', '%' . $search . '%')
    ->get();
```

### 5. ✅ WAJIB — Index yang Harus Ada di Setiap Tabel

```sql
-- Setiap tabel dengan tenant_id WAJIB memiliki:
CREATE INDEX idx_TABEL_tenant ON SCHEMA.TABEL (tenant_id);

-- Tabel dengan FK kolom WAJIB:
CREATE INDEX idx_TABEL_FK_COL ON SCHEMA.TABEL (FK_COL);

-- Tabel dengan filter status umum:
CREATE INDEX idx_TABEL_tenant_status
    ON SCHEMA.TABEL (tenant_id, status);
```

---

## 🔗 JOIN Cheat Sheet (Cross-Schema)

```sql
-- ── Siswa + Rombel + Nilai ──────────────────────────────────────
SELECT
    s.id, s.nama_lengkap, s.nis,
    r.nama_rombel, r.tingkat,
    nr.nilai_akhir, nr.predikat
FROM siswa.siswa s
INNER JOIN akademik.rombel_siswa rs ON s.id = rs.siswa_id
INNER JOIN akademik.rombel r        ON rs.rombel_id = r.id
LEFT  JOIN akademik.nilai_rapor nr  ON s.id = nr.siswa_id
                                    AND r.id = nr.rombel_id
WHERE s.tenant_id = $1
  AND r.tahun_ajaran_id = $2;

-- ── PTK + Penugasan + Rombel ────────────────────────────────────
SELECT
    ptk.id, ptk.nama_lengkap, ptk.nip,
    mp.nama_mata_pelajaran,
    r.nama_rombel
FROM kepegawaian.ptk_identitas ptk
INNER JOIN akademik.penugasan_guru pg ON ptk.id = pg.ptk_id
INNER JOIN akademik.mata_pelajaran mp ON pg.mapel_id = mp.id
INNER JOIN akademik.rombel r          ON pg.rombel_id = r.id
WHERE ptk.tenant_id = $1
  AND r.tahun_ajaran_id = $2;

-- ── Absensi Siswa + Detail ──────────────────────────────────────
SELECT
    s.nama_lengkap, s.nis,
    ap.tanggal_presensi,
    ap.status_kehadiran,
    ap.jam_masuk, ap.metode_presensi
FROM absensi.presensi_siswa ap
INNER JOIN siswa.siswa s ON ap.siswa_id = s.id
WHERE ap.tenant_id = $1
  AND ap.tanggal_presensi BETWEEN $2 AND $3
ORDER BY ap.tanggal_presensi DESC, s.nama_lengkap;

-- ── Tagihan + Pembayaran Siswa ──────────────────────────────────
SELECT
    s.nama_lengkap, s.nis,
    ts.bulan_tagihan, ts.total_tagihan, ts.status_tagihan,
    tp.jumlah_bayar, tp.tanggal_bayar, tp.metode_bayar
FROM keuangan.tagihan_siswa ts
INNER JOIN siswa.siswa s           ON ts.siswa_id = s.id
LEFT  JOIN keuangan.transaksi_pembayaran tp ON ts.id = tp.tagihan_id
WHERE ts.tenant_id = $1
  AND ts.status_tagihan = 'belum_lunas'
ORDER BY ts.bulan_tagihan DESC;
```

---

## 📁 Cara Menggunakan Module Folder Ini

### Untuk Developer Baru
1. Baca **`MASTER_AGENT.md`** ini terlebih dahulu
2. Pilih schema yang akan dikerjakan → buka folder `<schema>/`
3. Baca **`AGENT.md`** di folder schema tersebut
4. Gunakan **`join_patterns.sql`** sebagai referensi JOIN
5. Gunakan **`index_guide.sql`** untuk setup index di database
6. Gunakan **`query_examples.sql`** sebagai template query di kode program

### Untuk AI Agent / Code Assistant
```
Konteks: Sistem Sinta SaaS PostgreSQL v21
Database: 16 schema, 164 tabel
Aturan SQL:
  1. SELALU explicit columns, DILARANG SELECT *
  2. SELALU sertakan tenant_id dalam WHERE clause
  3. SELALU gunakan prepared statement ($1, $2, ...)
  4. JOIN berdasarkan FK yang ada di join_patterns.sql
  5. Index sudah terdefinisi di index_guide.sql

Untuk schema <nama_schema>, lihat:
  modules/<nama_schema>/AGENT.md
  modules/<nama_schema>/join_patterns.sql
  modules/<nama_schema>/query_examples.sql
```

---

*Generated: 2026-07-30 | Sinta SaaS PostgreSQL v21 | 164 Tabel | 16 Schema*
