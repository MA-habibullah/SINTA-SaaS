# Audit & Enrichment: SQL JOIN, Index, dan Explicit Column Selection

## Latar Belakang

Portal Schema di `docs/portal_schema` berisi dokumentasi **164 tabel PostgreSQL** yang terbagi dalam 16 schema. Setelah audit, ditemukan:

- ✅ Section **Index** sudah ada di semua 164 tabel
- ❌ **Contoh SQL Query Optimal** (JOIN + explicit columns) **belum ada di satu pun tabel**
- ❌ **Tidak ada `SELECT *`** — ini harus dipertegas dengan contoh explicit columns per tabel

## Ruang Lingkup Perubahan

### Masalah yang Ditemukan

| Issue | Status | Detail |
|-------|--------|--------|
| `SELECT *` dilarang | ❌ Belum ada dokumentasi | Setiap tabel belum punya contoh explicit column SELECT |
| JOIN yang benar | ❌ Belum terdokumentasi | Relasi FK ada, tapi JOIN contoh belum tersedia |
| Missing Index | 🔍 Perlu audit per schema | Beberapa tabel mungkin kurang index untuk query kritis |
| Composite index | 🔍 Perlu verifikasi | Kolom yang sering di-JOIN butuh composite index |

## Proposed Changes

### Strategy

Karena ada **164 tabel** di 17 file HTML, pendekatan yang efisien adalah:
1. Membuat **Python script generator** yang akan menambahkan section "🔍 Contoh Query SQL Optimal" ke setiap card di master HTML
2. Section ini menampilkan:
   - `SELECT` dengan explicit kolom (bukan `SELECT *`)
   - `JOIN` yang sesuai dengan FK relationship tabel
   - `WHERE` clause dengan kondisi yang memanfaatkan index yang ada
3. Kemudian **regenerate semua 17 halaman** portal schema dari master HTML

### Konten Section SQL Query per Tabel

Untuk setiap tabel, section SQL akan berisi:

```sql
-- ✅ Query Optimal: Gunakan Explicit Columns (NO SELECT *)
SELECT
    t.id,
    t.kolom1,
    t.kolom2,
    ref.nama AS nama_referensi
FROM schema.nama_tabel t
INNER JOIN schema.tabel_referensi ref ON t.fk_kolom = ref.id
WHERE t.tenant_id = $1          -- ✅ Memanfaatkan index idx_tenant
  AND t.status = $2             -- ✅ Composite index
ORDER BY t.created_at DESC
LIMIT 50;
```

### Index Audit & Recommendations

Setiap tabel akan dianalisis untuk memastikan:

#### Index Wajib (Rule)
| Pola Kolom | Jenis Index | Alasan |
|------------|-------------|--------|
| `tenant_id` (semua tabel) | INDEX | Multi-tenant isolation query |
| `tenant_id + status` | COMPOSITE INDEX | Filter multi-tenant + status |
| `FK kolom` (misal `siswa_id`) | INDEX | JOIN performance |
| `created_at` (untuk laporan) | INDEX | ORDER BY DESC tanpa seq scan |
| UNIQUE constraint | UNIQUE INDEX | Integrity + query optimizer |

#### Schema-Specific Index Audit

**core schema:**
- `tenants`: Index pada `subdomain`, `custom_domain` → sudah ada ✅
- `users`: Index pada `email`, `tenant_id` → perlu audit
- `menus`: Index pada `parent_id`, `tenant_id`, `urutan` → perlu Composite

**akademik schema:**
- `nilai_rapor`: Perlu composite `(siswa_id, rombel_id, semester)` untuk query rapor
- `rombel`: Perlu index `(tahun_ajaran_id, tenant_id)` 

**kepegawaian schema:**
- `pelamar_kerja`: Perlu index `(lowongan_id, status_seleksi)` 
- `dokumen_ptk`: Perlu index `(ptk_id, jenis_dokumen)` — sudah ada ✅

**absensi schema:**
- `presensi_siswa`: Perlu composite `(siswa_id, tanggal_presensi)` untuk rekap harian
- `presensi_ptk`: Perlu composite `(ptk_id, tanggal, tenant_id)`

### Perubahan File

#### [MODIFY] master HTML: `docs/rencana_konsolidasi_database.html`
- Tambahkan section "🔍 Query SQL Optimal" di setiap card dengan:
  - Contoh SELECT explicit columns
  - JOIN pattern sesuai FK
  - WHERE memanfaatkan index

#### [MODIFY] Script Generator: `scratch/generate_portal_and_schema_pages.py`
- Tambahkan CSS styling untuk `sql-block` section
- Regenerate semua 17 HTML dari master yang sudah diupdate

#### [NEW] Script: `scratch/add_sql_query_sections.py`
- Script Python yang:
  1. Membaca master HTML
  2. Untuk setiap card, generate SQL query optimal berdasarkan nama tabel, FK, dan index
  3. Menyisipkan section baru ke dalam setiap card
  4. Menyimpan master HTML yang diupdate
  5. Memanggil `generate_portal_and_schema_pages.py` untuk regenerate portal

## Open Questions

> [!IMPORTANT]
> **Apakah section "Contoh SQL Query" harus:**
> - Menampilkan contoh query untuk SEMUA 164 tabel? (lebih lengkap, tapi script lebih kompleks)
> - Atau hanya tabel-tabel kritis yang memiliki FK/JOIN? (lebih ringan)

> [!NOTE]
> Script akan auto-generate SQL query berdasarkan:
> - Nama tabel & schema
> - FK relationships yang sudah terdokumentasi
> - Index yang sudah ada
> Sehingga tidak perlu menulis manual per tabel.

## Verification Plan

### Automated
```python
# Verifikasi semua card memiliki SQL section
cards_with_sql = [c for c in cards if 'sql' in c.get_text().lower()]
assert len(cards_with_sql) == 164, f"Expected 164, got {len(cards_with_sql)}"

# Verifikasi tidak ada SELECT * di contoh query
import re
select_star = re.findall(r'SELECT\s+\*', master_content, re.IGNORECASE)
assert len(select_star) == 0, f"Found {len(select_star)} SELECT * instances"
```

### Manual Verification
1. Buka `docs/portal_schema/absensi.html` → cek section SQL ada di setiap tabel
2. Buka `docs/portal_schema/core.html` → verifikasi JOIN pattern benar
3. Pastikan CSS `sql-block` terlihat dengan syntax highlighting yang jelas
