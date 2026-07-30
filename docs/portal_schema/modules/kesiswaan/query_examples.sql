-- ════════════════════════════════════════════════════
-- QUERY EXAMPLES: Schema KESISWAAN
-- Contoh query optimal: JOIN + Explicit Columns + Index
-- ════════════════════════════════════════════════════

-- ── kesiswaan.master_ekskul ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: kesiswaan.master_ekskul
SELECT
    t.id,
    t.tenant_id,
    t.nama_ekskul,
    t.kategori,
    t.pembina_id,
    t.created_at,
    t.updated_at,
    t.deleted_at
FROM kesiswaan.master_ekskul t
INNER JOIN tenants tena ON t.tenant_id = tena.id
INNER JOIN users user ON t.pembina_id = user.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── kesiswaan.anggota_ekskul ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: kesiswaan.anggota_ekskul
SELECT
    t.id,
    t.tenant_id,
    t.ekskul_id,
    t.siswa_id,
    t.tahun_ajaran_id,
    t.created_at,
    t.updated_at
FROM kesiswaan.anggota_ekskul t
INNER JOIN tenants tena ON t.tenant_id = tena.id
INNER JOIN master_ekskul mast ON t.ekskul_id = mast.id
INNER JOIN siswa sisw ON t.siswa_id = sisw.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── kesiswaan.nilai_ekskul ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: kesiswaan.nilai_ekskul
SELECT
    t.id,
    t.tenant_id,
    t.ekskul_id,
    t.siswa_id,
    t.tahun_ajaran_id,
    t.nilai,
    t.deskripsi,
    t.created_at
FROM kesiswaan.nilai_ekskul t
INNER JOIN tenants tena ON t.tenant_id = tena.id
INNER JOIN master_ekskul mast ON t.ekskul_id = mast.id
INNER JOIN siswa sisw ON t.siswa_id = sisw.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── kesiswaan.kunci_ekskul ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: kesiswaan.kunci_ekskul
SELECT
    t.id,
    t.tenant_id,
    t.ekskul_id,
    t.tahun_ajaran_id,
    t.semester,
    t.kunci_anggota,
    t.kunci_nilai,
    t.created_at
FROM kesiswaan.kunci_ekskul t
-- (tabel standalone, tidak ada FK JOIN diperlukan)
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── kesiswaan.jadwal_ekskul ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: kesiswaan.jadwal_ekskul
SELECT
    t.id,
    t.tenant_id,
    t.ekskul_id,
    t.hari,
    t.waktu_mulai,
    t.waktu_selesai,
    t.ruangan,
    t.created_at
FROM kesiswaan.jadwal_ekskul t
INNER JOIN tenants tena ON t.tenant_id = tena.id
INNER JOIN master_ekskul mast ON t.ekskul_id = mast.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── kesiswaan.jurnal_ekskul ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: kesiswaan.jurnal_ekskul
SELECT
    t.id,
    t.tenant_id,
    t.ekskul_id,
    t.tanggal_kegiatan,
    t.materi,
    t.foto_kegiatan,
    t.created_at,
    t.updated_at
FROM kesiswaan.jurnal_ekskul t
INNER JOIN tenants tena ON t.tenant_id = tena.id
INNER JOIN master_ekskul mast ON t.ekskul_id = mast.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── kesiswaan.data_pembina ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: kesiswaan.data_pembina
SELECT
    t.id,
    t.tenant_id,
    t.user_id,
    t.no_hp,
    t.alamat,
    t.instansi_asal,
    t.keahlian_khusus,
    t.created_at
FROM kesiswaan.data_pembina t
INNER JOIN tenants tena ON t.tenant_id = tena.id
INNER JOIN users user ON t.user_id = user.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── kesiswaan.prestasi_siswa ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: kesiswaan.prestasi_siswa
SELECT
    t.id,
    t.tenant_id,
    t.tahun_ajaran_id,
    t.semester,
    t.bidang_lomba,
    t.nama_lomba,
    t.nomor_sertifikat,
    t.juara
FROM kesiswaan.prestasi_siswa t
INNER JOIN tenants tena ON t.tenant_id = tena.id
INNER JOIN tahun_ajaran tahu ON t.tahun_ajaran_id = tahu.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── kesiswaan.pendaftaran_spmb ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: kesiswaan.pendaftaran_spmb
SELECT
    t.id,
    t.tenant_id,
    t.siswa_id,
    t.nomor_pendaftaran,
    t.jalur_pendaftaran,
    t.status_pendaftaran,
    t.nilai_seleksi,
    t.berkas_dokumen
FROM kesiswaan.pendaftaran_spmb t
INNER JOIN tenants tena ON t.tenant_id = tena.id
INNER JOIN siswa sisw ON t.siswa_id = sisw.id
INNER JOIN users user ON t.diverifikasi_oleh = user.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;
