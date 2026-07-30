-- ════════════════════════════════════════════════════
-- QUERY EXAMPLES: Schema PERPUSTAKAAN
-- Contoh query optimal: JOIN + Explicit Columns + Index
-- ════════════════════════════════════════════════════

-- ── perpustakaan.perpus_bibliografi ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: perpustakaan.perpus_bibliografi
SELECT
    t.id,
    t.tenant_id,
    t.isbn,
    t.judul,
    t.judul_seri,
    t.edisi,
    t.penulis,
    t.penerbit
FROM perpustakaan.perpus_bibliografi t
INNER JOIN tenants tena ON t.tenant_id = tena.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── perpustakaan.perpus_eksemplar ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: perpustakaan.perpus_eksemplar
SELECT
    t.id,
    t.tenant_id,
    t.bibliografi_id,
    t.barcode,
    t.nomor_induk,
    t.tanggal_masuk,
    t.sumber_buku,
    t.lokasi_rak_id
FROM perpustakaan.perpus_eksemplar t
INNER JOIN tenants tena ON t.tenant_id = tena.id
INNER JOIN perpus_bibliografi perp ON t.bibliografi_id = perp.id
INNER JOIN perpus_lokasi_rak perp ON t.lokasi_rak_id = perp.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
  AND t.status = 'aktif' -- ✅ Manfaatkan composite index
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── perpustakaan.perpus_sirkulasi ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: perpustakaan.perpus_sirkulasi
SELECT
    t.id,
    t.tenant_id,
    t.no_transaksi,
    t.anggota_id,
    t.eksemplar_id,
    t.pustakawan_id,
    t.tanggal_pinjam,
    t.tanggal_kembali_rencana
FROM perpustakaan.perpus_sirkulasi t
INNER JOIN tenants tena ON t.tenant_id = tena.id
INNER JOIN perpus_anggota perp ON t.anggota_id = perp.id
INNER JOIN perpus_eksemplar perp ON t.eksemplar_id = perp.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
  AND t.status = 'aktif' -- ✅ Manfaatkan composite index
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── perpustakaan.perpus_anggota ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: perpustakaan.perpus_anggota
SELECT
    t.id,
    t.tenant_id,
    t.tipe_anggota,
    t.user_id,
    t.siswa_id,
    t.nisn,
    t.nip,
    t.nama_eksternal
FROM perpustakaan.perpus_anggota t
INNER JOIN tenants tena ON t.tenant_id = tena.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
  AND t.status = 'aktif' -- ✅ Manfaatkan composite index
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── perpustakaan.perpus_denda ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: perpustakaan.perpus_denda
SELECT
    t.id,
    t.tenant_id,
    t.sumber_transaksi,
    t.sirkulasi_id,
    t.distribusi_id,
    t.event_detail_id,
    t.anggota_id,
    t.jenis_denda
FROM perpustakaan.perpus_denda t
INNER JOIN tenants tena ON t.tenant_id = tena.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
  AND t.status = 'aktif' -- ✅ Manfaatkan composite index
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── perpustakaan.perpus_reservasi ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: perpustakaan.perpus_reservasi
SELECT
    t.id,
    t.tenant_id,
    t.bibliografi_id,
    t.anggota_id,
    t.tanggal_reservasi,
    t.tanggal_kadaluarsa,
    t.status,
    t.eksemplar_id
FROM perpustakaan.perpus_reservasi t
INNER JOIN tenants tena ON t.tenant_id = tena.id
INNER JOIN perpus_bibliografi perp ON t.bibliografi_id = perp.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
  AND t.status = 'aktif' -- ✅ Manfaatkan composite index
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── perpustakaan.perpus_opname ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: perpustakaan.perpus_opname
SELECT
    t.id,
    t.tenant_id,
    t.nama_sesi,
    t.tanggal_mulai,
    t.tanggal_selesai,
    t.status,
    t.petugas_id,
    t.total_scan
FROM perpustakaan.perpus_opname t
INNER JOIN tenants tena ON t.tenant_id = tena.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
  AND t.status = 'aktif' -- ✅ Manfaatkan composite index
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── perpustakaan.perpus_kategori_ddc ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: perpustakaan.perpus_kategori_ddc
SELECT
    t.kode,
    t.nama,
    t.induk_kode,
    t.tingkat
FROM perpustakaan.perpus_kategori_ddc t
-- (tabel standalone, tidak ada FK JOIN diperlukan)
WHERE t.id = $1
ORDER BY t.nama DESC
LIMIT 50 OFFSET 0;

-- ── perpustakaan.perpus_lokasi_rak ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: perpustakaan.perpus_lokasi_rak
SELECT
    t.id,
    t.tenant_id,
    t.kode,
    t.nama,
    t.gedung,
    t.lantai,
    t.ruangan,
    t.nama_rak
FROM perpustakaan.perpus_lokasi_rak t
INNER JOIN tenants tena ON t.tenant_id = tena.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── perpustakaan.perpus_paket_buku ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: perpustakaan.perpus_paket_buku
SELECT
    t.id,
    t.tenant_id,
    t.nama_paket,
    t.kelas_id,
    t.jenjang,
    t.jurusan,
    t.tahun_ajaran_id,
    t.semester
FROM perpustakaan.perpus_paket_buku t
INNER JOIN tenants tena ON t.tenant_id = tena.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
  AND t.status = 'aktif' -- ✅ Manfaatkan composite index
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── perpustakaan.perpus_event ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: perpustakaan.perpus_event
SELECT
    t.id,
    t.tenant_id,
    t.nama_event,
    t.penyelenggara,
    t.tgl_mulai,
    t.tgl_selesai,
    t.lokasi,
    t.created_at
FROM perpustakaan.perpus_event t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── perpustakaan.perpus_serial_berkala ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: perpustakaan.perpus_serial_berkala
SELECT
    t.id,
    t.tenant_id,
    t.nama_media,
    t.jenis,
    t.frekuensi,
    t.issn,
    t.tanggal_berlangganan,
    t.status_aktif
FROM perpustakaan.perpus_serial_berkala t
INNER JOIN tenants tena ON t.tenant_id = tena.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── perpustakaan.perpus_staf_kompetensi ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: perpustakaan.perpus_staf_kompetensi
SELECT
    t.id,
    t.tenant_id,
    t.nama_staf,
    t.jabatan,
    t.nama_kegiatan,
    t.penyelenggara,
    t.tanggal_kegiatan,
    t.sertifikat_no
FROM perpustakaan.perpus_staf_kompetensi t
INNER JOIN tenants tena ON t.tenant_id = tena.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── perpustakaan.perpus_notifikasi ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: perpustakaan.perpus_notifikasi
SELECT
    t.id,
    t.tenant_id,
    t.anggota_id,
    t.tipe,
    t.pesan,
    t.referensi_id,
    t.referensi_tipe,
    t.sudah_baca
FROM perpustakaan.perpus_notifikasi t
INNER JOIN tenants tena ON t.tenant_id = tena.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── perpustakaan.perpus_buku_tamu ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: perpustakaan.perpus_buku_tamu
SELECT
    t.id,
    t.tenant_id,
    t.nisn,
    t.nama_pengunjung,
    t.kelas,
    t.tujuan,
    t.tanggal,
    t.jam_masuk
FROM perpustakaan.perpus_buku_tamu t
INNER JOIN tenants tena ON t.tenant_id = tena.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── perpustakaan.perpus_ulasan ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: perpustakaan.perpus_ulasan
SELECT
    t.id,
    t.tenant_id,
    t.bibliografi_id,
    t.anggota_id,
    t.rating,
    t.ulasan,
    t.status,
    t.created_at
FROM perpustakaan.perpus_ulasan t
INNER JOIN tenants tena ON t.tenant_id = tena.id
INNER JOIN perpus_bibliografi perp ON t.bibliografi_id = perp.id
INNER JOIN perpus_anggota perp ON t.anggota_id = perp.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
  AND t.status = 'aktif' -- ✅ Manfaatkan composite index
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── perpustakaan.perpus_usulan_buku ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: perpustakaan.perpus_usulan_buku
SELECT
    t.id,
    t.tenant_id,
    t.judul,
    t.pengarang,
    t.penerbit,
    t.pengusul_nama,
    t.tanggal_usulan,
    t.status
FROM perpustakaan.perpus_usulan_buku t
INNER JOIN tenants tena ON t.tenant_id = tena.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
  AND t.status = 'aktif' -- ✅ Manfaatkan composite index
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── perpustakaan.perpus_pengaturan ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: perpustakaan.perpus_pengaturan
SELECT
    t.id,
    t.tenant_id,
    t.nama_perpustakaan,
    t.nomor_pokok,
    t.kepala_perpustakaan,
    t.nip_kepala,
    t.tarif_denda_per_hari,
    t.max_hari_pinjam_siswa
FROM perpustakaan.perpus_pengaturan t
INNER JOIN tenants tena ON t.tenant_id = tena.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;
