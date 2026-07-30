-- ════════════════════════════════════════════════════
-- JOIN PATTERNS: Schema ABSENSI
-- Panduan JOIN yang benar berdasarkan FK Constraint
-- ════════════════════════════════════════════════════

-- ── Tabel: absensi.lokasi_absensi_setting ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: absensi.lokasi_absensi_setting
SELECT
    t.id,
    t.tenant_id,
    t.nama_lokasi,
    t.latitude,
    t.longitude,
    t.radius_maksimal_meter,
    t.alamat_lengkap,
    t.is_active
FROM absensi.lokasi_absensi_setting t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
  AND t.is_active = true
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: absensi.jam_kerja_shift ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: absensi.jam_kerja_shift
SELECT
    t.id,
    t.tenant_id,
    t.nama_shift,
    t.jam_masuk,
    t.jam_pulang,
    t.jam_mulai_absen_masuk,
    t.jam_akhir_absen_masuk,
    t.jam_mulai_absen_pulang
FROM absensi.jam_kerja_shift t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: absensi.kebijakan_absensi_ptk ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: absensi.kebijakan_absensi_ptk
SELECT
    t.id,
    t.tenant_id,
    t.ptk_id,
    t.mode_kerja_default,
    t.lokasi_absensi_id,
    t.jam_kerja_shift_id,
    t.wajib_swafoto_selfie,
    t.wajib_liveness_detection
FROM absensi.kebijakan_absensi_ptk t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
INNER JOIN kepegawaian.ptk_identitas ptk_ ON t.ptk_id = ptk_.id
INNER JOIN absensi.lokasi_absensi_setting loka ON t.lokasi_absensi_id = loka.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
  AND t.is_active = true
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: absensi.presensi_ptk_harian ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: absensi.presensi_ptk_harian
SELECT
    t.id,
    t.tenant_id,
    t.ptk_id,
    t.tgl_presensi,
    t.jam_kerja_shift_id,
    t.mode_kerja_saat_absen,
    t.jam_masuk,
    t.swafoto_masuk_url
FROM absensi.presensi_ptk_harian t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
INNER JOIN kepegawaian.ptk_identitas ptk_ ON t.ptk_id = ptk_.id
INNER JOIN absensi.jam_kerja_shift jam_ ON t.jam_kerja_shift_id = jam_.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: absensi.aturan_absensi_siswa ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: absensi.aturan_absensi_siswa
SELECT
    t.id,
    t.tenant_id,
    t.jam_masuk_gerbang,
    t.jam_pulang_gerbang,
    t.jam_akhir_tap_masuk,
    t.toleransi_keterlambatan_menit,
    t.metode_absen_diizinkan,
    t.kirim_notifikasi_wa_orang_tua
FROM absensi.aturan_absensi_siswa t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: absensi.presensi_siswa_harian ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: absensi.presensi_siswa_harian
SELECT
    t.id,
    t.tenant_id,
    t.siswa_id,
    t.rombongan_belajar_id,
    t.tgl_presensi,
    t.metode_pengambilan_data,
    t.device_tap_id,
    t.jam_masuk
FROM absensi.presensi_siswa_harian t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
INNER JOIN siswa.siswa sisw ON t.siswa_id = sisw.id
INNER JOIN akademik.rombongan_belajar romb ON t.rombongan_belajar_id = romb.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: absensi.presensi_siswa_kbm ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: absensi.presensi_siswa_kbm
SELECT
    t.id,
    t.tenant_id,
    t.jadwal_pelajaran_id,
    t.rombongan_belajar_id,
    t.mata_pelajaran_id,
    t.guru_ptk_id,
    t.siswa_id,
    t.tgl_kbm
FROM absensi.presensi_siswa_kbm t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
INNER JOIN akademik.jadwal_pelajaran jadw ON t.jadwal_pelajaran_id = jadw.id
INNER JOIN akademik.rombongan_belajar romb ON t.rombongan_belajar_id = romb.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: absensi.pengajuan_izin_cuti ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: absensi.pengajuan_izin_cuti
SELECT
    t.id,
    t.tenant_id,
    t.kategori_pemohon,
    t.ptk_id,
    t.siswa_id,
    t.jenis_pengajuan,
    t.durasi_izin,
    t.jam_mulai_izin
FROM absensi.pengajuan_izin_cuti t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
INNER JOIN kepegawaian.ptk_identitas ptk_ ON t.ptk_id = ptk_.id
INNER JOIN siswa.siswa sisw ON t.siswa_id = sisw.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;
