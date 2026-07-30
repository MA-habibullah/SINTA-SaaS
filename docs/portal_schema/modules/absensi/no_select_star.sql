-- ════════════════════════════════════════════════════
-- NO SELECT * GUIDE: Schema ABSENSI
-- Perbandingan: ❌ SELECT * vs ✅ Explicit Columns
-- ════════════════════════════════════════════════════

-- ── absensi.lokasi_absensi_setting ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM absensi.lokasi_absensi_setting;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.nama_lokasi, t.latitude, t.longitude, t.radius_maksimal_meter
FROM absensi.lokasi_absensi_setting t
WHERE t.tenant_id = $1;

-- ── absensi.jam_kerja_shift ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM absensi.jam_kerja_shift;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.nama_shift, t.jam_masuk, t.jam_pulang, t.jam_mulai_absen_masuk
FROM absensi.jam_kerja_shift t
WHERE t.tenant_id = $1;

-- ── absensi.kebijakan_absensi_ptk ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM absensi.kebijakan_absensi_ptk;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.ptk_id, t.mode_kerja_default, t.lokasi_absensi_id, t.jam_kerja_shift_id
FROM absensi.kebijakan_absensi_ptk t
WHERE t.tenant_id = $1;

-- ── absensi.presensi_ptk_harian ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM absensi.presensi_ptk_harian;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.ptk_id, t.tgl_presensi, t.jam_kerja_shift_id, t.mode_kerja_saat_absen
FROM absensi.presensi_ptk_harian t
WHERE t.tenant_id = $1;

-- ── absensi.aturan_absensi_siswa ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM absensi.aturan_absensi_siswa;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.jam_masuk_gerbang, t.jam_pulang_gerbang, t.jam_akhir_tap_masuk, t.toleransi_keterlambatan_menit
FROM absensi.aturan_absensi_siswa t
WHERE t.tenant_id = $1;

-- ── absensi.presensi_siswa_harian ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM absensi.presensi_siswa_harian;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.siswa_id, t.rombongan_belajar_id, t.tgl_presensi, t.metode_pengambilan_data
FROM absensi.presensi_siswa_harian t
WHERE t.tenant_id = $1;

-- ── absensi.presensi_siswa_kbm ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM absensi.presensi_siswa_kbm;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.jadwal_pelajaran_id, t.rombongan_belajar_id, t.mata_pelajaran_id, t.guru_ptk_id
FROM absensi.presensi_siswa_kbm t
WHERE t.tenant_id = $1;

-- ── absensi.pengajuan_izin_cuti ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM absensi.pengajuan_izin_cuti;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.kategori_pemohon, t.ptk_id, t.siswa_id, t.jenis_pengajuan
FROM absensi.pengajuan_izin_cuti t
WHERE t.tenant_id = $1;
