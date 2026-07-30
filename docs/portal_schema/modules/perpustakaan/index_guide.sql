-- ════════════════════════════════════════════════════
-- INDEX GUIDE: Schema PERPUSTAKAAN
-- Rekomendasi index untuk performa query optimal
-- Jalankan: psql -d sinta_db -f index_guide.sql
-- ════════════════════════════════════════════════════

-- ── perpustakaan.perpus_bibliografi ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_perpus_bibliografi_tenant ON perpustakaan.perpus_bibliografi (tenant_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_perpus_bibliografi_created ON perpustakaan.perpus_bibliografi (created_at DESC);

-- ── perpustakaan.perpus_eksemplar ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_perpus_eksemplar_tenant ON perpustakaan.perpus_eksemplar (tenant_id);

-- ✅ Composite: filter tenant + status (query paling umum)
CREATE INDEX IF NOT EXISTS idx_perpus_eksemplar_tenant_status ON perpustakaan.perpus_eksemplar (tenant_id, status);

-- ✅ FK JOIN index: bibliografi_id
CREATE INDEX IF NOT EXISTS idx_perpus_eksemplar_bibliografi_id ON perpustakaan.perpus_eksemplar (bibliografi_id);

-- ✅ FK JOIN index: lokasi_rak_id
CREATE INDEX IF NOT EXISTS idx_perpus_eksemplar_lokasi_rak_id ON perpustakaan.perpus_eksemplar (lokasi_rak_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_perpus_eksemplar_created ON perpustakaan.perpus_eksemplar (created_at DESC);

-- ── perpustakaan.perpus_sirkulasi ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_perpus_sirkulasi_tenant ON perpustakaan.perpus_sirkulasi (tenant_id);

-- ✅ Composite: filter tenant + status (query paling umum)
CREATE INDEX IF NOT EXISTS idx_perpus_sirkulasi_tenant_status ON perpustakaan.perpus_sirkulasi (tenant_id, status);

-- ✅ FK JOIN index: anggota_id
CREATE INDEX IF NOT EXISTS idx_perpus_sirkulasi_anggota_id ON perpustakaan.perpus_sirkulasi (anggota_id);

-- ✅ FK JOIN index: eksemplar_id
CREATE INDEX IF NOT EXISTS idx_perpus_sirkulasi_eksemplar_id ON perpustakaan.perpus_sirkulasi (eksemplar_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_perpus_sirkulasi_created ON perpustakaan.perpus_sirkulasi (created_at DESC);

-- ── perpustakaan.perpus_anggota ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_perpus_anggota_tenant ON perpustakaan.perpus_anggota (tenant_id);

-- ✅ Composite: filter tenant + status (query paling umum)
CREATE INDEX IF NOT EXISTS idx_perpus_anggota_tenant_status ON perpustakaan.perpus_anggota (tenant_id, status);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_perpus_anggota_created ON perpustakaan.perpus_anggota (created_at DESC);

-- ── perpustakaan.perpus_denda ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_perpus_denda_tenant ON perpustakaan.perpus_denda (tenant_id);

-- ✅ Composite: filter tenant + status (query paling umum)
CREATE INDEX IF NOT EXISTS idx_perpus_denda_tenant_status ON perpustakaan.perpus_denda (tenant_id, status);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_perpus_denda_created ON perpustakaan.perpus_denda (created_at DESC);

-- ── perpustakaan.perpus_reservasi ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_perpus_reservasi_tenant ON perpustakaan.perpus_reservasi (tenant_id);

-- ✅ Composite: filter tenant + status (query paling umum)
CREATE INDEX IF NOT EXISTS idx_perpus_reservasi_tenant_status ON perpustakaan.perpus_reservasi (tenant_id, status);

-- ✅ FK JOIN index: bibliografi_id
CREATE INDEX IF NOT EXISTS idx_perpus_reservasi_bibliografi_id ON perpustakaan.perpus_reservasi (bibliografi_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_perpus_reservasi_created ON perpustakaan.perpus_reservasi (created_at DESC);

-- ── perpustakaan.perpus_opname ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_perpus_opname_tenant ON perpustakaan.perpus_opname (tenant_id);

-- ✅ Composite: filter tenant + status (query paling umum)
CREATE INDEX IF NOT EXISTS idx_perpus_opname_tenant_status ON perpustakaan.perpus_opname (tenant_id, status);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_perpus_opname_created ON perpustakaan.perpus_opname (created_at DESC);

-- ── perpustakaan.perpus_kategori_ddc ──
-- Tabel perpus_kategori_ddc: sudah memiliki index optimal (PK only)

-- ── perpustakaan.perpus_lokasi_rak ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_perpus_lokasi_rak_tenant ON perpustakaan.perpus_lokasi_rak (tenant_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_perpus_lokasi_rak_created ON perpustakaan.perpus_lokasi_rak (created_at DESC);

-- ── perpustakaan.perpus_paket_buku ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_perpus_paket_buku_tenant ON perpustakaan.perpus_paket_buku (tenant_id);

-- ✅ Composite: filter tenant + status (query paling umum)
CREATE INDEX IF NOT EXISTS idx_perpus_paket_buku_tenant_status ON perpustakaan.perpus_paket_buku (tenant_id, status);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_perpus_paket_buku_created ON perpustakaan.perpus_paket_buku (created_at DESC);

-- ── perpustakaan.perpus_event ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_perpus_event_tenant ON perpustakaan.perpus_event (tenant_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_perpus_event_created ON perpustakaan.perpus_event (created_at DESC);

-- ── perpustakaan.perpus_serial_berkala ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_perpus_serial_berkala_tenant ON perpustakaan.perpus_serial_berkala (tenant_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_perpus_serial_berkala_created ON perpustakaan.perpus_serial_berkala (created_at DESC);

-- ── perpustakaan.perpus_staf_kompetensi ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_perpus_staf_kompetensi_tenant ON perpustakaan.perpus_staf_kompetensi (tenant_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_perpus_staf_kompetensi_created ON perpustakaan.perpus_staf_kompetensi (created_at DESC);

-- ── perpustakaan.perpus_notifikasi ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_perpus_notifikasi_tenant ON perpustakaan.perpus_notifikasi (tenant_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_perpus_notifikasi_created ON perpustakaan.perpus_notifikasi (created_at DESC);

-- ── perpustakaan.perpus_buku_tamu ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_perpus_buku_tamu_tenant ON perpustakaan.perpus_buku_tamu (tenant_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_perpus_buku_tamu_created ON perpustakaan.perpus_buku_tamu (created_at DESC);

-- ── perpustakaan.perpus_ulasan ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_perpus_ulasan_tenant ON perpustakaan.perpus_ulasan (tenant_id);

-- ✅ Composite: filter tenant + status (query paling umum)
CREATE INDEX IF NOT EXISTS idx_perpus_ulasan_tenant_status ON perpustakaan.perpus_ulasan (tenant_id, status);

-- ✅ FK JOIN index: bibliografi_id
CREATE INDEX IF NOT EXISTS idx_perpus_ulasan_bibliografi_id ON perpustakaan.perpus_ulasan (bibliografi_id);

-- ✅ FK JOIN index: anggota_id
CREATE INDEX IF NOT EXISTS idx_perpus_ulasan_anggota_id ON perpustakaan.perpus_ulasan (anggota_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_perpus_ulasan_created ON perpustakaan.perpus_ulasan (created_at DESC);

-- ── perpustakaan.perpus_usulan_buku ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_perpus_usulan_buku_tenant ON perpustakaan.perpus_usulan_buku (tenant_id);

-- ✅ Composite: filter tenant + status (query paling umum)
CREATE INDEX IF NOT EXISTS idx_perpus_usulan_buku_tenant_status ON perpustakaan.perpus_usulan_buku (tenant_id, status);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_perpus_usulan_buku_created ON perpustakaan.perpus_usulan_buku (created_at DESC);

-- ── perpustakaan.perpus_pengaturan ──
-- ✅ Tenant isolation index (wajib di semua tabel multi-tenant)
CREATE INDEX IF NOT EXISTS idx_perpus_pengaturan_tenant ON perpustakaan.perpus_pengaturan (tenant_id);

-- ✅ Tanggal index untuk ORDER BY / date-range laporan
CREATE INDEX IF NOT EXISTS idx_perpus_pengaturan_created ON perpustakaan.perpus_pengaturan (created_at DESC);
