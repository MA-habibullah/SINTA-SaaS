-- ════════════════════════════════════════════════════
-- JOIN PATTERNS: Schema SISWA
-- Panduan JOIN yang benar berdasarkan FK Constraint
-- ════════════════════════════════════════════════════

-- ── Tabel: siswa.siswa ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: siswa.siswa
SELECT
    t.id,
    t.tenant_id,
    t.user_id,
    t.nisn,
    t.nis,
    t.nama_lengkap,
    t.tempat_lahir,
    t.tanggal_lahir
FROM siswa.siswa t
INNER JOIN tenants tena ON t.tenant_id = tena.id
INNER JOIN users user ON t.user_id = user.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: siswa.fisik_kesehatan_siswa ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: siswa.fisik_kesehatan_siswa
SELECT
    t.id,
    t.tenant_id,
    t.siswa_id,
    t.tinggi_badan_cm,
    t.berat_badan_kg,
    t.lingkar_kepala_cm,
    t.jarak_rumah_ke_sekolah_km,
    t.waktu_tempuh_menit
FROM siswa.fisik_kesehatan_siswa t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
INNER JOIN siswa.siswa sisw ON t.siswa_id = sisw.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: siswa.orang_tua ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: siswa.orang_tua
SELECT
    t.id_orang_tua,
    t.siswa_id,
    t.id_tempat_lahir_ayah,
    t.nik_ayah,
    t.nama_ayah,
    t.tahun_lahir_ayah,
    t.pendidikan_ayah,
    t.pekerjaan_ayah
FROM siswa.orang_tua t
INNER JOIN siswa sisw ON t.id_siswa = sisw.id
INNER JOIN kota kota ON t.id_tempat_lahir_ayah = kota.id_kota
INNER JOIN kota kota ON t.id_tempat_lahir_ibu = kota.id_kota
WHERE t.id = $1
ORDER BY t.siswa_id DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: siswa.registrasi ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: siswa.registrasi
SELECT
    t.id_registrasi,
    t.siswa_id,
    t.jalur_diterima,
    t.jenis_pendaftaran,
    t.tanggal_masuk,
    t.paud_formal,
    t.paud_non_formal,
    t.hobi
FROM siswa.registrasi t
INNER JOIN siswa sisw ON t.id_siswa = sisw.id
WHERE t.id = $1
ORDER BY t.siswa_id DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: siswa.absensi_semester ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: siswa.absensi_semester
SELECT
    t.id,
    t.tenant_id,
    t.siswa_id,
    t.tahun_ajaran_id,
    t.semester,
    t.sakit,
    t.izin,
    t.alfa
FROM siswa.absensi_semester t
INNER JOIN tenants tena ON t.tenant_id = tena.id
INNER JOIN siswa sisw ON t.siswa_id = sisw.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: siswa.riwayat_kenaikan_kelas ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: siswa.riwayat_kenaikan_kelas
SELECT
    t.id,
    t.tenant_id,
    t.siswa_id,
    t.jenis_aksi,
    t.id_kelas_asal,
    t.id_kelas_tujuan,
    t.nama_kelas_asal,
    t.nama_kelas_tujuan
FROM siswa.riwayat_kenaikan_kelas t
-- (tabel standalone, tidak ada FK JOIN diperlukan)
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: siswa.riwayat_beasiswa ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: siswa.riwayat_beasiswa
SELECT
    t.id,
    t.tenant_id,
    t.siswa_id,
    t.jenis_beasiswa,
    t.sumber,
    t.tahun_menerima,
    t.nominal,
    t.created_at
FROM siswa.riwayat_beasiswa t
INNER JOIN tenants tena ON t.tenant_id = tena.id
INNER JOIN siswa sisw ON t.siswa_id = sisw.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: siswa.anggota_kelas ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: siswa.anggota_kelas
SELECT
    t.id,
    t.tenant_id,
    t.semester_id,
    t.rombongan_belajar_id,
    t.siswa_id,
    t.no_urut_presensi,
    t.jenis_pendaftaran,
    t.status_anggota
FROM siswa.anggota_kelas t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
INNER JOIN akademik.semester seme ON t.semester_id = seme.id
INNER JOIN akademik.kelas kela ON t.rombongan_belajar_id = kela.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: siswa.dokumen ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: siswa.dokumen
SELECT
    t.id_dokumen,
    t.siswa_id,
    t.berkas_kk,
    t.berkas_akta,
    t.berkas_ijazah_sd,
    t.berkas_ijazah_smp,
    t.berkas_ijazah_sma,
    t.berkas_mutasi_masuk
FROM siswa.dokumen t
INNER JOIN siswa sisw ON t.id_siswa = sisw.id
WHERE t.id = $1
ORDER BY t.siswa_id DESC
LIMIT 50 OFFSET 0;
