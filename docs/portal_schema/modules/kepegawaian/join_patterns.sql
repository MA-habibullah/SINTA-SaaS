-- ════════════════════════════════════════════════════
-- JOIN PATTERNS: Schema KEPEGAWAIAN
-- Panduan JOIN yang benar berdasarkan FK Constraint
-- ════════════════════════════════════════════════════

-- ── Tabel: kepegawaian.ptk_identitas ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: kepegawaian.ptk_identitas
SELECT
    t.id,
    t.tenant_id,
    t.user_id,
    t.nik,
    t.nip,
    t.nuptk,
    t.nrb,
    t.nama_lengkap
FROM kepegawaian.ptk_identitas t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
INNER JOIN core.users user ON t.user_id = user.id
INNER JOIN akademik.mata_pelajaran mata ON t.mapel_utama_id = mata.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: kepegawaian.riwayat_kepangkatan ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: kepegawaian.riwayat_kepangkatan
SELECT
    t.id,
    t.tenant_id,
    t.ptk_id,
    t.pangkat_golongan,
    t.nomor_sk,
    t.tgl_sk,
    t.tmt_pangkat,
    t.masa_kerja_tahun
FROM kepegawaian.riwayat_kepangkatan t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
INNER JOIN kepegawaian.ptk_identitas ptk_ ON t.ptk_id = ptk_.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: kepegawaian.sertifikasi_ptk ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: kepegawaian.sertifikasi_ptk
SELECT
    t.id,
    t.tenant_id,
    t.ptk_id,
    t.jenis_sertifikasi,
    t.nomor_sertifikat,
    t.nrg,
    t.bidang_studi_sertifikasi,
    t.tahun_sertifikasi
FROM kepegawaian.sertifikasi_ptk t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
INNER JOIN kepegawaian.ptk_identitas ptk_ ON t.ptk_id = ptk_.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: kepegawaian.diklat_pelatihan ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: kepegawaian.diklat_pelatihan
SELECT
    t.id,
    t.tenant_id,
    t.ptk_id,
    t.nama_diklat,
    t.penyelenggara,
    t.peran,
    t.jumlah_jam_jp,
    t.tahun_diklat
FROM kepegawaian.diklat_pelatihan t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
INNER JOIN kepegawaian.ptk_identitas ptk_ ON t.ptk_id = ptk_.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: kepegawaian.riwayat_pendidikan_ptk ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: kepegawaian.riwayat_pendidikan_ptk
SELECT
    t.id,
    t.tenant_id,
    t.ptk_id,
    t.jenjang_pendidikan,
    t.gelar_akademik,
    t.nama_perguruan_tinggi,
    t.fakultas_prodi,
    t.tahun_lulus
FROM kepegawaian.riwayat_pendidikan_ptk t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
INNER JOIN kepegawaian.ptk_identitas ptk_ ON t.ptk_id = ptk_.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: kepegawaian.riwayat_kepala_sekolah ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: kepegawaian.riwayat_kepala_sekolah
SELECT
    t.id,
    t.tenant_id,
    t.ptk_id,
    t.nama_kepsek,
    t.nip_kepsek,
    t.nomor_sk_kepsek,
    t.tgl_sk,
    t.tmt_jabatan
FROM kepegawaian.riwayat_kepala_sekolah t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
INNER JOIN kepegawaian.ptk_identitas ptk_ ON t.ptk_id = ptk_.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: kepegawaian.dokumen_ptk ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: kepegawaian.dokumen_ptk
SELECT
    t.id,
    t.tenant_id,
    t.ptk_id,
    t.jenis_dokumen,
    t.nama_dokumen,
    t.nomor_dokumen,
    t.file_path,
    t.file_size_kb
FROM kepegawaian.dokumen_ptk t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
INNER JOIN kepegawaian.ptk ptk ON t.ptk_id = ptk.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: kepegawaian.lowongan_kerja ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: kepegawaian.lowongan_kerja
SELECT
    t.id,
    t.tenant_id,
    t.judul_lowongan,
    t.kode_lowongan,
    t.posisi_jabatan,
    t.kualifikasi_pendidikan,
    t.persyaratan_html,
    t.jumlah_kuota
FROM kepegawaian.lowongan_kerja t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
  AND t.status = 'aktif' -- ✅ Manfaatkan composite index
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: kepegawaian.pelamar_kerja ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: kepegawaian.pelamar_kerja
SELECT
    t.id,
    t.tenant_id,
    t.lowongan_id,
    t.nik,
    t.nama_lengkap,
    t.jenis_kelamin,
    t.tempat_lahir,
    t.tanggal_lahir
FROM kepegawaian.pelamar_kerja t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
INNER JOIN kepegawaian.lowongan_kerja lowo ON t.lowongan_id = lowo.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: kepegawaian.tahapan_seleksi_pelamar ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: kepegawaian.tahapan_seleksi_pelamar
SELECT
    t.id,
    t.tenant_id,
    t.pelamar_id,
    t.tahapan,
    t.jadwal_pelaksanaan,
    t.lokasi_ruangan,
    t.penguji_ptk_id,
    t.status_tahapan
FROM kepegawaian.tahapan_seleksi_pelamar t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
INNER JOIN kepegawaian.pelamar_kerja pela ON t.pelamar_id = pela.id
INNER JOIN kepegawaian.ptk ptk ON t.penguji_ptk_id = ptk.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;
