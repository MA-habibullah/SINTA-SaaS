# Implementation Plans Harian - 2026-07-30

---
## Migrasi Arsitektur & Audit Enrichment: MySQL → PostgreSQL Per-Modul SINTA-SaaS
**Waktu**: 15:36 WIB
**Status**: Disetujui / Persiapan Migrasi (Transisi ke Laragon)

### Latar Belakang & Root Cause
Sinta SaaS mengalami perubahan arsitektur fundamental dari sistem yang sebelumnya berjalan di atas MySQL monolitik menjadi PostgreSQL dengan struktur schema per-modul (16 schema, 164+ tabel). Di samping itu, perlu dilakukan enrichment dokumentasi SQL JOIN, Index, dan Explicit Column Selection di `docs/portal_schema/`, serta audit gap analysis terhadap 38 Controllers, 200+ Routes, dan 102 Migrations yang ada di aplikasi running MySQL.

---

## 🚨 BAGIAN I — Migrasi Arsitektur: MySQL → PostgreSQL Per-Modul

### Konteks Perubahan Besar

Sinta SaaS mengalami **perubahan arsitektur fundamental** dari sistem yang sebelumnya berjalan di atas **MySQL monolitik** menjadi **PostgreSQL dengan struktur schema per-modul**. Ini bukan sekadar migrasi database, melainkan **restrukturisasi menyeluruh** seluruh basis kode aplikasi.

| Aspek | Sebelum (MySQL) | Sesudah (PostgreSQL) |
|-------|----------------|----------------------|
| **Database Engine** | MySQL 5.7 / 8.0 | PostgreSQL 16 |
| **Struktur** | Flat — semua tabel dalam 1 database | Multi-Schema — 16 schema terpisah per modul |
| **Jumlah Tabel** | Beragam (belum terschema) | **164 tabel** terdokumentasi |
| **Namespace** | Tidak ada namespace | `core.`, `akademik.`, `siswa.`, dll. |
| **Multi-Tenancy** | `WHERE sekolah_id = ?` (manual) | `tenant_id` isolation by design |
| **Referensi FK** | Cross-table dalam 1 DB | Cross-schema FK (misal `siswa.siswa` → `akademik.rombel`) |
| **Query Syntax** | MySQL dialect (`LIMIT x,y`, `IFNULL`) | PostgreSQL dialect (`LIMIT x OFFSET y`, `COALESCE`, `$1 $2`) |
| **ORM** | Eloquent (MySQL driver) | Eloquent (PostgreSQL driver / `pgsql`) |
| **Arsitektur Kode** | Monolitik (1 folder besar) | **Per-Modul** (folder terpisah per schema) |

---

### Daftar 16 Schema (Pengganti Tabel Flat MySQL)

Seluruh schema telah didokumentasikan di `docs/portal_schema/` dalam bentuk HTML interaktif:

| # | Schema | Tabel | Fungsi | File Dokumentasi |
|---|--------|-------|--------|------------------|
| 1 | `core` | 17 | SaaS Foundation: Tenant, Auth, Menu, Wilayah | [core.html](docs/portal_schema/core.html) |
| 2 | `akademik` | 20 | Kurikulum, Rombel, Nilai Rapor | [akademik.html](docs/portal_schema/akademik.html) |
| 3 | `siswa` | 9 | Data Peserta Didik (Master Identity) | [siswa.html](docs/portal_schema/siswa.html) |
| 4 | `kesiswaan` | 9 | Ekskul, Prestasi, PPDB, Kedisiplinan | [kesiswaan.html](docs/portal_schema/kesiswaan.html) |
| 5 | `bk` | 8 | Bimbingan Konseling | [bk.html](docs/portal_schema/bk.html) |
| 6 | `pdss` | 7 | SNBP & Perguruan Tinggi | [pdss.html](docs/portal_schema/pdss.html) |
| 7 | `tracer` | 5 | Alumni & Karir | [tracer.html](docs/portal_schema/tracer.html) |
| 8 | `kepegawaian` | 10 | PTK, Dokumen Digital, Rekrutmen | [kepegawaian.html](docs/portal_schema/kepegawaian.html) |
| 9 | `keuangan` | 6 | SPP, Tagihan, Pembayaran | [keuangan.html](docs/portal_schema/keuangan.html) |
| 10 | `perpustakaan` | 18 | Library Digital & Sirkulasi | [perpustakaan.html](docs/portal_schema/perpustakaan.html) |
| 11 | `sarpras` | 17 | Inventaris, Gudang, Maintenance | [sarpras.html](docs/portal_schema/sarpras.html) |
| 12 | `persuratan` | 9 | E-Office & Tata Naskah Dinas | [persuratan.html](docs/portal_schema/persuratan.html) |
| 13 | `absensi` | 8 | Presensi GPS + RFID | [absensi.html](docs/portal_schema/absensi.html) |
| 14 | `smk` | 5 | PKL, UKK, BKK (khusus SMK) | [smk.html](docs/portal_schema/smk.html) |
| 15 | `sistem` | 8 | Audit Log, Error Log, Helpdesk | [sistem.html](docs/portal_schema/sistem.html) |
| 16 | `cms` | 8 | Landing Page & Dynamic Web Sekolah | [cms.html](docs/portal_schema/cms.html) |
| | **TOTAL** | **164** | | |

---

### Perubahan Arsitektur Kode Aplikasi (Per-Modul)

Selain migrasi database, **struktur kode aplikasi** juga berubah dari monolitik menjadi **per-modul**, mengikuti struktur schema PostgreSQL:

```
Sebelum (MySQL Monolitik):             Sesudah (PostgreSQL Per-Modul):
app/                                   app/
├── Models/                            ├── Modules/
│   ├── Siswa.php                      │   ├── Core/
│   ├── Guru.php                       │   │   ├── Models/Tenant.php
│   ├── Absensi.php                    │   │   ├── Services/TenantService.php
│   └── Nilai.php                      │   │   └── Controllers/
├── Controllers/                       │   ├── Akademik/
│   ├── SiswaController.php            │   │   ├── Models/Rombel.php
│   ├── AbsensiController.php          │   │   ├── Models/NilaiRapor.php
│   └── NilaiController.php            │   │   ├── Services/NilaiService.php
└── (semua campur jadi 1)              │   │   └── Controllers/
                                       │   ├── Absensi/
                                       │   │   ├── Models/PresensiSiswa.php
                                       │   │   ├── Services/GeofencingService.php
                                       │   │   └── Controllers/
                                       │   └── ... (16 modul total)
```

---

### Panduan Migrasi Query: MySQL → PostgreSQL

Setiap query lama MySQL **wajib** dikonversi mengikuti aturan berikut:

#### 1. Konvensi Placeholder Parameter
```sql
-- ❌ MySQL (PDO :named atau ?)
SELECT id, nama FROM siswa WHERE tenant_id = :tenant_id;
SELECT id, nama FROM siswa WHERE tenant_id = ?;

-- ✅ PostgreSQL (positional $1, $2, ...)
SELECT s.id, s.nama_lengkap FROM siswa.siswa s WHERE s.tenant_id = $1;
```

#### 2. Konvensi Nama Tabel (Schema-prefixed)
```sql
-- ❌ MySQL (flat table name)
SELECT * FROM siswa;
SELECT * FROM rombel;
INNER JOIN absensi ON siswa.id = absensi.siswa_id;

-- ✅ PostgreSQL (schema.tabel)
SELECT s.id FROM siswa.siswa s;
SELECT r.id FROM akademik.rombel r;
INNER JOIN absensi.presensi_siswa_harian psh ON s.id = psh.siswa_id;
```

#### 3. Fungsi yang Berbeda
```sql
-- ❌ MySQL                        -- ✅ PostgreSQL
IFNULL(col, 'default')            COALESCE(col, 'default')
LIMIT 10, 20                      LIMIT 10 OFFSET 20
GROUP_CONCAT(col)                 STRING_AGG(col, ',')
NOW()                             NOW() ✅ (sama)
DATE_FORMAT(col, '%Y-%m')         TO_CHAR(col, 'YYYY-MM')
IF(cond, a, b)                    CASE WHEN cond THEN a ELSE b END
AUTO_INCREMENT                    SERIAL / BIGSERIAL / GENERATED ALWAYS
```

#### 4. Konfigurasi Laravel `.env`
```env
# ❌ Sebelum (MySQL)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sinta_saas
DB_USERNAME=root
DB_PASSWORD=

# ✅ Sesudah (PostgreSQL)
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=sinta_saas_db
DB_USERNAME=postgres
DB_PASSWORD=your_password
DB_SCHEMA=public   # atau per-modul jika multi-schema config
```

#### 5. Konfigurasi Eloquent Model (Per-Modul)
```php
// ❌ Sebelum: Model flat tanpa schema
class Siswa extends Model {
    protected $table = 'siswa';
}

// ✅ Sesudah: Model dengan schema prefix
class Siswa extends Model {
    protected $table    = 'siswa.siswa';   // schema.tabel
    protected $fillable = ['id', 'tenant_id', 'nama_lengkap', 'nis', 'nisn'];
    
    // WAJIB: Scope tenant isolation
    public function scopeTenant($query, int $tenantId) {
        return $query->where('tenant_id', $tenantId);
    }
}
```

---

### Dampak Perubahan Per-Modul terhadap Fitur yang Sudah Ada

| Fitur Lama (MySQL) | Schema PostgreSQL Baru | Status |
|--------------------|------------------------|--------|
| Tabel `siswa` flat | `siswa.siswa` | 🔄 Perlu migrasi model |
| Tabel `guru` / `pegawai` | `kepegawaian.ptk_identitas` | 🔄 Perlu migrasi model |
| Tabel `nilai` / `rapor` | `akademik.nilai_rapor` | 🔄 Perlu migrasi model |
| Tabel `absensi` | `absensi.presensi_siswa_harian` | 🔄 Perlu migrasi model |
| Tabel `spp` / `pembayaran` | `keuangan.tagihan_siswa`, `keuangan.transaksi_pembayaran` | 🔄 Perlu migrasi model |
| Tabel `buku` / `peminjaman` | `perpustakaan.*` (18 tabel) | 🔄 Perlu migrasi model |
| Tabel `surat_masuk` / `surat_keluar` | `persuratan.*` | 🔄 Perlu migrasi model |
| Tabel `barang` / `inventaris` | `sarpras.*` (17 tabel) | 🔄 Perlu migrasi model |
| Config/menu/tenant | `core.*` (17 tabel) | 🔄 Perlu migrasi model |

---

### Urutan Prioritas Migrasi Per-Modul

```
Fase 1 — Fondasi (WAJIB SELESAI DULU):
  [1] core        → Tenant, Auth, User, Menu, Wilayah
  [2] siswa       → Master data peserta didik

Fase 2 — Modul Akademik Utama:
  [3] akademik    → Rombel, Mapel, Nilai Rapor
  [4] kesiswaan   → Ekskul, Prestasi, PPDB
  [5] absensi     → Presensi GPS + RFID

Fase 3 — Modul Kepegawaian & Keuangan:
  [6] kepegawaian → PTK, Dokumen, Rekrutmen
  [7] keuangan    → SPP, Tagihan, Pembayaran

Fase 4 — Modul Pendukung:
  [8]  perpustakaan → Library Digital
  [9]  sarpras      → Inventaris & Gudang
  [10] persuratan   → E-Office
  [11] bk           → Bimbingan Konseling
  [12] pdss         → SNBP & PT
  [13] tracer       → Alumni
  [14] smk          → PKL, UKK (khusus SMK)
  [15] sistem       → Audit Log, Helpdesk
  [16] cms          → Landing Page Sekolah
```

---

## 🔍 BAGIAN II — Audit & Enrichment: SQL JOIN, Index, dan Explicit Column Selection

### Latar Belakang (Bagian II)

Portal Schema di `docs/portal_schema` berisi dokumentasi **164 tabel PostgreSQL** yang terbagi dalam **16 schema**.

Tujuan bagian ini: **menyematkan section SQL Query Optimal ke setiap card tabel** di 17 halaman HTML portal schema, sehingga dokumentasi HTML menjadi self-contained.

---

### Proposed Changes (Script Python Generator)

#### `scratch/add_sql_query_sections.py`

```python
modules = ['core', 'cms', 'akademik', 'siswa', 'kesiswaan', 'bk',
           'pdss', 'tracer', 'kepegawaian', 'keuangan', 'perpustakaan',
           'sarpras', 'persuratan', 'absensi', 'smk', 'sistem']

for module in modules:
    query_sql  = read(f"modules/{module}/query_examples.sql")
    join_sql   = read(f"modules/{module}/join_patterns.sql")
    
    queries_per_table = parse_sql_blocks(query_sql, join_sql)
    html_file = f"{module}.html"
    inject_sql_sections(html_file, queries_per_table)

assert count_cards_with_sql() == 164
assert no_select_star_found()
```

---

## 🔎 BAGIAN III — Gap Analysis: Fitur Running (MySQL) vs Schema PostgreSQL Baru

### Inventaris Fitur/Modul Aktif di Aplikasi MySQL (Running)

| # | Modul/Menu | Controller | Routes | Views |
|---|-----------|-----------|--------|-------|
| 1 | **Auth (Login/Logout)** | `AuthAdminController`, `AuthSiswaController` | `/admin`, `/login`, `/siswa/login` | `login_view.php`, `siswa_login_view.php` |
| 2 | **Dashboard** | `DashboardController` | `/dashboard` | `dashboard_view.php` |
| 3 | **Data Siswa (Buku Induk)** | `SiswaController`, `BukuIndukController` | `/siswa/*`, `/buku-induk`, `/cetak-rapot*` | `tambah_siswa.php`, `buku_induk.php` |
| 4 | **Nilai Rapor** | `NilaiRaporController` | `/api/v1/nilai-rapor/*` | `print_rapot*.php` |
| 5 | **Kurikulum** | `KurikulumController` | `/api/v1/kurikulum/*` | _(via buku induk)_ |
| 6 | **Ekskul (Kesiswaan)** | `EkskulController` | `/kesiswaan/ekskul`, `/api/v1/ekskul/*` | `kesiswaan_ekskul.php` |
| 7 | **BK (Bimbingan Konseling)** | `BKController`, `GuruKonselingController` | `/bk/*`, `/konseling`, `/pembinaan/*` | `master_bk.php`, `bk/` |
| 8 | **Pembinaan 3M** | `PembinaanController` | `/pembinaan/*` | `sekolah/pembinaan_*.php` |
| 9 | **PDSS / SNBP** | `PDSSController` | `/pdss/*`, `/api/v1/pdss/*` | `pdss_index.php`, `bk/pdss_*.php` |
| 10 | **Master Kampus & Prodi** | `KampusController` | `/api/v1/kampus/*` | `bk/master_kampus_prodi_layout.php` |
| 11 | **Tracer Study Alumni** | `TracerController` | `/tracer-study`, `/api/v1/tracer/*` | `tracer_study.php`, `arsip_alumni.php` |
| 12 | **Keuangan / SPP** | `SppController` | `/keuangan/*`, `/api/v1/keuangan/*` | `keuangan/` (9 views) |
| 13 | **Perpustakaan** | `PerpustakaanController` | `/perpustakaan/*`, `/api/v1/perpustakaan/*` | `perpustakaan/` (15 views) |
| 14 | **Kelembagaan / Master Data** | `KelembagaanController` | `/master-data`, `/api/v1/kelembagaan/*` | `master_kelembagaan.php` |
| 15 | **Identitas Sekolah** | `SekolahController` | `/sekolah/identitas` | `identitas_sekolah.php`, `sekolah_profil.php` |
| 16 | **Pengguna & Role** | `PenggunaController` | `/pengguna`, `/api/v1/pengguna/*` | `pengguna_index.php` |
| 17 | **Akses Menu (Konfigurasi)** | `AksesController` | `/konfigurasi/akses/*` | `kelola_akses.php` |
| 18 | **Pengumuman & Agenda** | `PengumumanController`, `AgendaController`, `KategoriAgendaController` | `/informasi/*` | `humas/pengumuman.php`, `sekolah/agenda_terpadu.php` |
| 19 | **Tenant Management** | `TenantController`, `TenantManagementController`, `SuperAdminController` | `/super-admin/*` | `tenants_index.php`, `tenant_menus.php` |
| 20 | **Sistem — Ticketing/Bantuan** | `BantuanController` | `/bantuan`, `/api/v1/bantuan/*` | `bantuan_admin.php`, `bantuan_user.php` |
| 21 | **Sistem — Error Monitor** | `ErrorMonitorController` | `/super-admin/error-monitor` | `error_monitor.php` |
| 22 | **Sistem — Server Monitor** | `ServerMonitorController` | `/super-admin/server-monitor` | `server_monitor.php` |
| 23 | **Sistem — Queue/Worker** | `QueueController` | `/utilitas/antrean` | `queue_monitoring.php` |
| 24 | **Sistem — Activity Log** | `ActivityLogController` | `/utilitas/log-aktivitas` | `activity_logs.php` |
| 25 | **Sistem — Active Sessions** | `ActiveSessionController` | `/utilitas/sesi-aktif` | `active_sessions.php` |
| 26 | **Utility — Document Scanner** | `UtilityController` | `/utility/document-scanner` | `utility/document_scanner.php` |
| 27 | **Import Siswa (Excel)** | `ImportController` | `/api/v1/siswa/import*` | _(modal di views)_ |
| 28 | **Bulk Photo Upload** | `BulkPhotoController` | `/api/v1/siswa/bulk-photo` | _(modal di views)_ |
| 29 | **Kunci Akademik** | `KunciAkademikController` | `/api/v1/kunci_akademik/*` | _(toggle di views)_ |

---

### ❌ 18 Fitur Ada di MySQL (Running) — BELUM Ada di Schema PostgreSQL

| # | Fitur | Controller | Deskripsi | Schema Usulan |
|---|-------|-----------|-----------|---------------|
| 1 | **Ticketing / Bantuan** | `BantuanController` | Sistem helpdesk internal dengan tiket, balasan, canned responses, FAQ | `sistem.tiket_bantuan`, `sistem.balasan_tiket`, `sistem.faq_kategori` |
| 2 | **Document Scanner** | `UtilityController` | OCR & scanning dokumen digital siswa/guru | `sistem.document_scan_log` atau `siswa.dokumen_digital` |
| 3 | **Pembinaan 3M** | `PembinaanController` | Monitoring pembinaan sekolah oleh pengawas (3 M: monitoring, mentoring, membina) | `sistem.pembinaan_sekolah`, `sistem.sesi_pembinaan` |
| 4 | **Konfigurasi Akses Per-User** | `AksesController` | Override akses menu per individual user (bukan hanya per-role) | `core.user_menu_access` |
| 5 | **Queue / Worker Monitor** | `QueueController` | Monitoring background jobs (system_jobs table) | `sistem.system_jobs` |
| 6 | **Server Monitor** | `ServerMonitorController` | Monitor resource server (CPU, RAM, Disk), konfigurasi Netplan, deploy script | `sistem.*` |
| 7 | **Active Sessions** | `ActiveSessionController` | Track & audit sesi aktif pengguna + audit log sesi | `sistem.active_sessions` |
| 8 | **Kunci Akademik** | `KunciAkademikController` | Toggle lock input nilai rapor per periode akademik | `akademik.kunci_akademik` |
| 9 | **Kurikulum Dinamis** | `KurikulumController` | Kurikulum custom per tenant (KTSP/K13/Merdeka) — FK ke rombel | `akademik.ref_kurikulum` |
| 10 | **Penjurusan Siswa** | `BKController` (Tab Penjurusan) | Pilih jurusan SMA (IPA/IPS/Bahasa), verifikasi & override oleh BK | `bk.penjurusan` |
| 11 | **Beasiswa Siswa** | `BKController` (Tab Beasiswa) + `BukuIndukController` | Rekam beasiswa per siswa, export Excel | `siswa.beasiswa_siswa` |
| 12 | **Agenda Terpadu** | `AgendaController` + `KategoriAgendaController` | Kalender agenda kegiatan sekolah per tenant dengan kategori | `sistem.agenda` |
| 13 | **Pengumuman** | `PengumumanController` | Broadcast pengumuman ke semua user per tenant | `cms.pengumuman` |
| 14 | **Arsip Alumni** | `BukuIndukController` | Pengarsipan data alumni yang lulus | `siswa.arsip_alumni` |
| 15 | **Nilai Ujian Sekolah** | `NilaiRaporController` | Input nilai ujian sekolah terpisah dari rapor reguler | `akademik.nilai_ujian_sekolah` |
| 16 | **Riwayat Kepsek** | `BukuIndukController` | Daftar kepala sekolah dari masa ke masa | `core.riwayat_kepsek` |
| 17 | **Prestasi Siswa** | `BKController` (Tab Prestasi) | Rekam prestasi akademik/non-akademik siswa | `kesiswaan.prestasi_siswa` |
| 18 | **PPDB / Pendaftaran SPMB** | _(dalam migrations)_ | Pendaftaran peserta didik baru online | `kesiswaan.ppdb_pendaftar` |

---

## Verification Plan

### Automated Verification
```python
# File: scratch/verify_sql_sections.py
from bs4 import BeautifulSoup
import os, re

HTML_DIR = "docs/portal_schema"
SCHEMAS = ['absensi', 'akademik', 'bk', 'cms', 'core', 'kepegawaian',
           'kesiswaan', 'keuangan', 'pdss', 'perpustakaan', 'persuratan',
           'sarpras', 'sistem', 'siswa', 'smk', 'tracer']

total_cards = 0
cards_with_sql = 0

for schema in SCHEMAS:
    html_path = os.path.join(HTML_DIR, f"{schema}.html")
    soup = BeautifulSoup(open(html_path), 'html.parser')
    cards = soup.find_all(class_='table-card')
    sql_blocks = soup.find_all(class_='sql-block')
    total_cards += len(cards)
    cards_with_sql += len(sql_blocks)

assert total_cards == 164
assert cards_with_sql == 164
```

### Manual Verification
1. Buka `docs/portal_schema/absensi.html` untuk memverifikasi blok query SQL optimal di setiap card tabel.
2. Buka `docs/portal_schema/core.html` untuk memastikan skema JOIN cross-schema sudah tepat.
3. Menyiapkan repositori dan lingkungan database PostgreSQL di Laragon setelah proyek dipindahkan dari XAMPP.

---
